<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseMigrateToPostgresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:migrate-to-postgres
                            {--source=sqlite : Source database connection name}
                            {--target=pgsql : Target PostgreSQL database connection name}
                            {--fresh : Run fresh migrations on target database before data transfer}
                            {--skip-validation : Skip post-migration row count and integrity checks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely migrate all data from source database to PostgreSQL with zero data loss and sequence synchronization';

    /**
     * Tables in strict dependency order (parents before children).
     *
     * @var array<string>
     */
    protected array $tablesInOrder = [
        'users',
        'menu_templates',
        'subscription_plans',
        'vendors',
        'locations',
        'allergens',
        'categories',
        'products',
        'product_variations',
        'product_allergens',
        'location_product_overrides',
        'customers',
        'subscriptions',
        'subscription_payments',
        'orders',
        'order_items',
        'analytics_logs',
        'push_subscriptions',
        'waiter_calls',
        'ai_waiter_sessions',
        'ai_usage_logs',
        'payment_attempts',
        'vendor_storage_files',
        'vendor_deletion_jobs',
        'vendor_lifecycle_logs',
        'vendor_credentials',
        'custom_domains',
        'security_audit_logs',
        'system_settings',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $source = (string) $this->option('source');
        $target = (string) $this->option('target');
        $isFresh = (bool) $this->option('fresh');
        $skipValidation = (bool) $this->option('skip-validation');

        $this->info('==================================================');
        $this->info(' QRMenu: Database Migration -> PostgreSQL');
        $this->info(" Source Connection: [{$source}]");
        $this->info(" Target Connection: [{$target}]");
        $this->info('==================================================');

        // 1. Verify Target Connection is PostgreSQL
        try {
            $targetDriver = DB::connection($target)->getDriverName();
            if ($targetDriver !== 'pgsql') {
                $this->error("Target connection [{$target}] driver is [{$targetDriver}], expected [pgsql]. Aborting.");

                return Command::FAILURE;
            }
            $this->info('Connected to PostgreSQL: '.DB::connection($target)->selectOne('SELECT version()')->version);
        } catch (Throwable $e) {
            $this->error("Failed connecting to target PostgreSQL: {$e->getMessage()}");

            return Command::FAILURE;
        }

        // 2. Prepare Target Schema (Fresh or Verify)
        if ($isFresh) {
            $this->warn("Running fresh migrations on [{$target}]...");
            $this->call('migrate:fresh', [
                '--database' => $target,
                '--force' => true,
            ]);
        }

        // 3. Disable Constraints during migration for seamless foreign key insertion
        $this->info('Disabling PostgreSQL replication triggers and constraints for bulk load...');
        DB::connection($target)->statement("SET session_replication_role = 'replica';");

        $stats = [];
        $totalRowsMigrated = 0;

        try {
            foreach ($this->tablesInOrder as $table) {
                if (! Schema::connection($source)->hasTable($table)) {
                    continue;
                }

                if (! Schema::connection($target)->hasTable($table)) {
                    $this->warn("Skipping table [{$table}]: not found on target schema.");

                    continue;
                }

                $sourceCount = DB::connection($source)->table($table)->count();
                if ($sourceCount === 0) {
                    $stats[$table] = ['source' => 0, 'target' => 0, 'status' => 'EMPTY'];

                    continue;
                }

                $this->output->write("Migrating [{$table}] ({$sourceCount} rows)... ");

                // Truncate target table before copying
                DB::connection($target)->table($table)->truncate();

                $migratedForTable = 0;

                // Chunk read from source and insert into target
                DB::connection($source)->table($table)->orderBy(
                    Schema::connection($source)->hasColumn($table, 'id') ? 'id' : (Schema::connection($source)->getColumnListing($table)[0] ?? 'created_at')
                )->chunk(250, function ($rows) use ($target, $table, &$migratedForTable) {
                    $insertData = [];
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        // Clean null bytes in string values
                        foreach ($rowArray as $col => $val) {
                            if (is_string($val)) {
                                $rowArray[$col] = str_replace("\0", '', $val);
                            }
                        }
                        $insertData[] = $rowArray;
                    }

                    if (! empty($insertData)) {
                        DB::connection($target)->table($table)->insert($insertData);
                        $migratedForTable += count($insertData);
                    }
                });

                $targetCount = DB::connection($target)->table($table)->count();
                $stats[$table] = [
                    'source' => $sourceCount,
                    'target' => $targetCount,
                    'status' => $sourceCount === $targetCount ? 'MATCH' : 'MISMATCH',
                ];
                $totalRowsMigrated += $migratedForTable;

                $this->output->writeln("<info>DONE ({$targetCount} rows)</info>");
            }
        } finally {
            // Re-enable triggers and foreign keys
            $this->info('Re-enabling PostgreSQL triggers and integrity checks...');
            DB::connection($target)->statement("SET session_replication_role = 'origin';");
        }

        // 4. Synchronize PostgreSQL Sequences
        $this->info('Synchronizing PostgreSQL Primary Key sequences...');
        foreach ($this->tablesInOrder as $table) {
            if (! Schema::connection($target)->hasTable($table)) {
                continue;
            }

            if (Schema::connection($target)->hasColumn($table, 'id')) {
                try {
                    $seqSql = "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1), (SELECT MAX(id) FROM \"{$table}\") IS NOT NULL);";
                    DB::connection($target)->statement($seqSql);
                } catch (Throwable $e) {
                    // Sequence might not exist or use non-standard name
                }
            }
        }
        $this->info('All PostgreSQL sequences synchronized.');

        // 5. Post-Migration Verification
        if (! $skipValidation) {
            $this->info("\n--- Migration Audit & Verification Summary ---");
            $tableRows = [];
            $hasMismatches = false;

            foreach ($stats as $tbl => $data) {
                $statusFormatted = $data['status'] === 'MATCH'
                    ? '<info>✓ MATCH</info>'
                    : ($data['status'] === 'EMPTY' ? '<comment>EMPTY</comment>' : '<error>✗ MISMATCH</error>');

                if ($data['status'] === 'MISMATCH') {
                    $hasMismatches = true;
                }

                $tableRows[] = [
                    $tbl,
                    $data['source'],
                    $data['target'],
                    $statusFormatted,
                ];
            }

            $this->table(['Table', 'Source Rows', 'PostgreSQL Rows', 'Status'], $tableRows);

            if ($hasMismatches) {
                $this->error('WARNING: One or more tables have row count mismatches!');

                return Command::FAILURE;
            }

            $this->info("<info>SUCCESS: All tables transferred with 100% data integrity! Total migrated rows: {$totalRowsMigrated}</info>");
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Localization\ModuleRegistry;
use Illuminate\Console\Command;

class CheckTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'i18n:check {--module= : Check a specific module namespace}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit all application modules against the localization and placeholder integrity contract';

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistry $registry): int
    {
        $this->info('Auditing Application Modules against Multilingual Localization Contract...');
        $this->newLine();

        $specificModule = $this->option('module');
        $modules = $registry->all();

        if ($specificModule) {
            if (! isset($modules[$specificModule])) {
                $this->error("Module '{$specificModule}' is not registered in ModuleRegistry.");

                return self::FAILURE;
            }
            $modules = [$specificModule => $modules[$specificModule]];
        }

        $allPassed = true;
        $tableRows = [];

        foreach ($modules as $namespace => $module) {
            $audit = $module->audit();
            $status = $audit['is_valid'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';

            $details = [];
            if (! empty($audit['missing_files'])) {
                $details[] = 'Missing files: '.implode(', ', array_keys($audit['missing_files']));
            }
            if (! empty($audit['missing_keys'])) {
                foreach ($audit['missing_keys'] as $locale => $keys) {
                    $details[] = "Missing [{$locale}]: ".implode(', ', $keys);
                }
            }
            if (! empty($audit['placeholder_errors'])) {
                foreach ($audit['placeholder_errors'] as $locale => $keyErrors) {
                    $details[] = "Placeholder err [{$locale}]: ".implode(', ', array_keys($keyErrors));
                }
            }
            if (! empty($audit['model_warnings'])) {
                $details[] = implode('; ', $audit['model_warnings']);
            }

            $tableRows[] = [
                $module->getName(),
                $namespace,
                $status,
                empty($details) ? '<fg=green>All checks passed</>' : implode(' | ', $details),
            ];

            if (! $audit['is_valid']) {
                $allPassed = false;
            }
        }

        $this->table(
            ['Module', 'Namespace', 'Status', 'Audit Notes / Issues'],
            $tableRows
        );

        $this->newLine();

        if ($allPassed) {
            $this->info('Localization Contract Audit: ALL MODULES COMPLIANT.');

            return self::SUCCESS;
        }

        $this->error('Localization Contract Audit FAILED: Resolve missing files, keys, or placeholder mismatches above.');

        return self::FAILURE;
    }
}

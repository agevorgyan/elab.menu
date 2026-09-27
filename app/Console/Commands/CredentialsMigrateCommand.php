<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Vendor;
use App\Services\CredentialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class CredentialsMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credentials:migrate
                            {--dry-run : Audit and preview credential migrations without modifying records}
                            {--vendor= : Specific vendor ID to migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy plaintext vendor secrets to encrypted vendor_credentials';

    /**
     * Execute the console command.
     */
    public function handle(CredentialService $credentialService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $vendorId = $this->option('vendor');

        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode. No database records will be modified.');
        }

        $query = Vendor::withoutGlobalScopes();
        if ($vendorId) {
            $query->where('id', $vendorId);
        }

        $vendors = $query->get();
        $this->info("Scanning {$vendors->count()} vendor(s) for plaintext credentials...");

        $migratedStats = [
            'ai' => 0,
            'telegram' => 0,
            'stripe' => 0,
            'idram' => 0,
            'telcell' => 0,
            'fastshift' => 0,
            'arca' => 0,
            'sms' => 0,
            'wifi' => 0,
        ];
        $totalMigrated = 0;

        foreach ($vendors as $vendor) {
            $vendorUpdated = false;

            // 1. AI API Key
            $ai = $vendor->ai_settings ?? [];
            if (! empty($ai['api_key'])) {
                $rawKey = trim((string) $ai['api_key']);
                $this->line("  [Vendor #{$vendor->id}] Found plaintext AI API Key (length: ".strlen($rawKey).')');
                if (! $isDryRun) {
                    $credentialService->set($vendor, 'ai', 'api_key', $rawKey);
                    $ai['api_key'] = null;
                    $vendor->ai_settings = $ai;
                    $vendorUpdated = true;
                }
                $migratedStats['ai']++;
                $totalMigrated++;
            }

            // 2. Telegram Bot Token
            $tg = $vendor->telegram_settings ?? [];
            if (! empty($tg['bot_token'])) {
                $rawToken = trim((string) $tg['bot_token']);
                $this->line("  [Vendor #{$vendor->id}] Found plaintext Telegram Bot Token (length: ".strlen($rawToken).')');
                if (! $isDryRun) {
                    $credentialService->set($vendor, 'telegram', 'bot_token', $rawToken);
                    $tg['bot_token'] = null;
                    $vendor->telegram_settings = $tg;
                    $vendorUpdated = true;
                }
                $migratedStats['telegram']++;
                $totalMigrated++;
            }

            // 3. Payment Gateways
            $payments = $vendor->payment_settings ?? [];
            $paymentsChanged = false;
            $gateways = $payments['gateways'] ?? [];

            $paymentSecretMap = [
                'stripe' => ['secret_key', 'publishable_key'],
                'idram' => ['secret_key'],
                'telcell' => ['key'],
                'fastshift' => ['api_key'],
                'arca' => ['secret_key'],
            ];

            foreach ($paymentSecretMap as $gw => $types) {
                foreach ($types as $type) {
                    if (! empty($gateways[$gw][$type])) {
                        $rawSecret = (string) $gateways[$gw][$type];
                        $this->line("  [Vendor #{$vendor->id}] Found plaintext {$gw} {$type}");
                        if (! $isDryRun) {
                            $credentialService->set($vendor, $gw, $type, $rawSecret);
                            $gateways[$gw][$type] = '';
                            $paymentsChanged = true;
                        }
                        $migratedStats[$gw]++;
                        $totalMigrated++;
                    }
                }
            }

            if ($paymentsChanged) {
                $payments['gateways'] = $gateways;
                $vendor->payment_settings = $payments;
                $vendorUpdated = true;
            }

            // 4. CRM SMS API Key
            $crm = $vendor->crm_settings ?? [];
            if (! empty($crm['sms_api_key'])) {
                $rawSmsKey = trim((string) $crm['sms_api_key']);
                $this->line("  [Vendor #{$vendor->id}] Found plaintext SMS API Key");
                if (! $isDryRun) {
                    $credentialService->set($vendor, 'sms', 'api_key', $rawSmsKey);
                    $crm['sms_api_key'] = '';
                    $vendor->crm_settings = $crm;
                    $vendorUpdated = true;
                }
                $migratedStats['sms']++;
                $totalMigrated++;
            }

            // 5. Vendor Wi-Fi Password Encryption
            $rawWifi = $vendor->getRawOriginal('wifi_password');
            if (! empty($rawWifi)) {
                $isEncrypted = false;
                try {
                    Crypt::decryptString($rawWifi);
                    $isEncrypted = true;
                } catch (\Throwable) {
                    $isEncrypted = false;
                }

                if (! $isEncrypted) {
                    $this->line("  [Vendor #{$vendor->id}] Encrypting legacy plaintext Wi-Fi password");
                    if (! $isDryRun) {
                        $vendor->wifi_password = $rawWifi; // triggers mutator encryption
                        $vendorUpdated = true;
                    }
                    $migratedStats['wifi']++;
                    $totalMigrated++;
                }
            }

            if ($vendorUpdated && ! $isDryRun) {
                $vendor->save();
            }

            // 6. Branch (Location) Wi-Fi Passwords
            $locations = Location::withoutGlobalScopes()->where('vendor_id', $vendor->id)->get();
            foreach ($locations as $loc) {
                $rawLocWifi = $loc->getRawOriginal('wifi_password');
                if (! empty($rawLocWifi)) {
                    $isLocEncrypted = false;
                    try {
                        Crypt::decryptString($rawLocWifi);
                        $isLocEncrypted = true;
                    } catch (\Throwable) {
                        $isLocEncrypted = false;
                    }

                    if (! $isLocEncrypted) {
                        $this->line("  [Location #{$loc->id}] Encrypting legacy plaintext Wi-Fi password");
                        if (! $isDryRun) {
                            $loc->wifi_password = $rawLocWifi; // triggers mutator encryption
                            $loc->save();
                        }
                        $migratedStats['wifi']++;
                        $totalMigrated++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Migration completed! Total credentials processed: {$totalMigrated}");

        $tableRows = [];
        foreach ($migratedStats as $category => $count) {
            $tableRows[] = [$category, $count];
        }
        $this->table(['Provider / Type', 'Count'], $tableRows);

        return Command::SUCCESS;
    }
}

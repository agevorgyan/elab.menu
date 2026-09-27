<?php

namespace App\Console\Commands;

use App\Models\AiUsageLog;
use App\Models\AiWaiterSession;
use App\Models\AnalyticsLog;
use App\Models\SecurityAuditLog;
use App\Services\AuditSanitizer;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PrunePrivacyDataCommand extends Command
{
    protected $signature = 'privacy:prune {--dry-run : Simulate data pruning without deleting records}';

    protected $description = 'Enforce documented privacy retention periods by pruning or anonymizing aged logs';

    public function handle(AuditSanitizer $sanitizer): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '🔍 SIMULATION: Checking data retention policies...' : '🧹 Pruning expired data per retention policies...');

        // 1. Security Audit Logs Retention
        $auditDays = (int) config('privacy.retention_periods.security_audit_logs_days', 365);
        $auditCutoff = Carbon::now()->subDays($auditDays);
        $auditQuery = SecurityAuditLog::withoutGlobalScopes()->where('created_at', '<', $auditCutoff);
        $auditCount = $auditQuery->count();

        if (! $dryRun && $auditCount > 0) {
            $auditQuery->delete();
        }
        $this->line("• Security Audit Logs (> {$auditDays} days): {$auditCount} record(s) ".($dryRun ? 'eligible for deletion' : 'deleted'));

        // 2. Analytics Logs Retention
        $analyticsDays = (int) config('privacy.retention_periods.analytics_logs_days', 90);
        $analyticsCutoff = Carbon::now()->subDays($analyticsDays);
        $analyticsQuery = AnalyticsLog::withoutGlobalScopes()->where(function ($q) use ($analyticsCutoff) {
            $q->where('visit_date', '<', $analyticsCutoff->toDateString())
                ->orWhere('created_at', '<', $analyticsCutoff);
        });
        $analyticsCount = $analyticsQuery->count();

        if (! $dryRun && $analyticsCount > 0) {
            $analyticsQuery->delete();
        }
        $this->line("• Analytics Logs (> {$analyticsDays} days): {$analyticsCount} record(s) ".($dryRun ? 'eligible for deletion' : 'deleted'));

        // 3. AI Usage & Conversation Logs Retention
        $aiDays = (int) config('privacy.retention_periods.ai_conversations_days', 60);
        $aiCutoff = Carbon::now()->subDays($aiDays);

        $aiUsageQuery = AiUsageLog::withoutGlobalScopes()->where('created_at', '<', $aiCutoff);
        $aiUsageCount = $aiUsageQuery->count();
        if (! $dryRun && $aiUsageCount > 0) {
            $aiUsageQuery->delete();
        }
        $this->line("• AI Usage Logs (> {$aiDays} days): {$aiUsageCount} record(s) ".($dryRun ? 'eligible for deletion' : 'deleted'));

        $aiSessionQuery = AiWaiterSession::withoutGlobalScopes()->where('created_at', '<', $aiCutoff);
        $aiSessionCount = $aiSessionQuery->count();
        if (! $dryRun && $aiSessionCount > 0) {
            $aiSessionQuery->delete();
        }
        $this->line("• AI Waiter Sessions (> {$aiDays} days): {$aiSessionCount} record(s) ".($dryRun ? 'eligible for deletion' : 'deleted'));

        // 4. IP Anonymization for records older than ip_anonymization_days
        $ipDays = (int) config('privacy.retention_periods.ip_anonymization_days', 30);
        $ipCutoff = Carbon::now()->subDays($ipDays);

        // Security Audit Logs IP anonymization
        $activeAuditIps = SecurityAuditLog::withoutGlobalScopes()
            ->where('created_at', '<', $ipCutoff)
            ->whereNotNull('ip_address')
            ->where('ip_address', 'not like', '%.0')
            ->get();

        $auditIpAnonymized = 0;
        foreach ($activeAuditIps as $log) {
            $masked = $sanitizer->anonymizeIp($log->ip_address);
            if ($masked !== $log->ip_address) {
                if (! $dryRun) {
                    $log->update(['ip_address' => $masked]);
                }
                $auditIpAnonymized++;
            }
        }
        $this->line("• Audit Log IP Addresses (> {$ipDays} days): {$auditIpAnonymized} record(s) ".($dryRun ? 'eligible for masking' : 'masked'));

        // Analytics Logs IP anonymization
        $activeAnalyticsIps = AnalyticsLog::withoutGlobalScopes()
            ->where('created_at', '<', $ipCutoff)
            ->whereNotNull('ip_address')
            ->where('ip_address', 'not like', '%.0')
            ->get();

        $analyticsIpAnonymized = 0;
        foreach ($activeAnalyticsIps as $log) {
            $masked = $sanitizer->anonymizeIp($log->ip_address);
            if ($masked !== $log->ip_address) {
                if (! $dryRun) {
                    $log->update(['ip_address' => $masked]);
                }
                $analyticsIpAnonymized++;
            }
        }
        $this->line("• Analytics IP Addresses (> {$ipDays} days): {$analyticsIpAnonymized} record(s) ".($dryRun ? 'eligible for masking' : 'masked'));

        $this->info($dryRun ? '✅ Retention simulation complete.' : '✅ Privacy retention successfully enforced.');

        return Command::SUCCESS;
    }
}

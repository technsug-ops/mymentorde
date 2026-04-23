<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─────────────────────────────────────────────────────────────────────────────
// Scheduled Tasks
// Business logic lives in app/Console/Commands/
// ─────────────────────────────────────────────────────────────────────────────

Schedule::command('manager:report-snapshot --type=weekly')->mondays()->at('08:00');
Schedule::command('manager:report-snapshot --type=monthly')->monthlyOn(1, '08:10');
Schedule::command('notifications:dispatch --limit=100')->everyMinute();
Schedule::command('escalations:process --limit=100')->hourly();
Schedule::command('risk-scores:calculate --limit=500')->dailyAt('01:15');
Schedule::command('archive:inactive-records --guest-days=180')->dailyAt('01:30');
Schedule::command('marketing:sync-external-metrics --days=7')
    ->hourlyAt(20)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/marketing-external-sync.log'));
Schedule::command('marketing:integrations-health --limit=300')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/marketing-integrations-health.log'));
Schedule::command('marketing:probe-third-party --company-limit=300')
    ->hourlyAt(40)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/marketing-third-party-probe.log'));
Schedule::command('tasks:process-automation --limit=200')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/task-automation.log'));
Schedule::command('mvp:smoke --cleanup')
    ->dailyAt('01:45')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/mvp-smoke.log'));
Schedule::command('api:regression-smoke')
    ->dailyAt('01:50')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/api-regression.log'));
Schedule::command('ops:self-heal --limit=100')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/self-heal.log'));
Schedule::command('ops:critical-check --limit=100')
    ->dailyAt('02:10')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/critical-check.log'));
Schedule::command('export:audit-report --type=all')
    ->monthlyOn(1, '02:20')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/audit-export.log'));

// audit_trails 90+ gün eski kayıtlar → jsonl.gz dump + DB'den sil
// Haftalık çalışır — GDPR 3 yıl retention için yeterli, OLTP tablosu şişmez
Schedule::command('archive:audit-trails --days=90 --chunk=1000')
    ->weeklyOn(0, '03:30') // Pazar 03:30
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/audit-archive.log'));

Schedule::command('gdpr:enforce-retention')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/gdpr-retention.log'));

Schedule::command('integrations:health-check')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/integration-health.log'));

Schedule::command('social:publish-scheduled')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/social-publish.log'));

Schedule::command('social:sync-metrics')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/social-sync.log'));

// ─── v3.0 Marketing & Sales Automation Schedulers ────────────────────────────

Schedule::command('workflow:process-waiting')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/workflow-engine.log'));

Schedule::command('abtest:check-winners')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/abtest-winners.log'));

Schedule::command('lead:apply-score-decay')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/score-decay.log'));

Schedule::command('attribution:recalculate-daily')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attribution-recalculate.log'));

Schedule::command('lead:re-engagement-check')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/re-engagement.log'));

Schedule::command('workflow:check-goals')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/workflow-goals.log'));

Schedule::command('email:process-queue --limit=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/email-queue.log'));

Schedule::command('email:dispatch-scheduled --limit=20')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/email-dispatch-scheduled.log'));

Schedule::command('reports:generate-scheduled')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduled-reports.log'));

// ─── v2.0 Task Board Schedulers ──────────────────────────────────────────────

Schedule::command('tasks:check-escalations')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/task-escalations.log'));

Schedule::command('tasks:send-due-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/task-due-reminders.log'));

Schedule::command('tasks:clone-recurring')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/task-recurring-clone.log'));

// ─── Senior Analytics Schedulers ─────────────────────────────────────────────

Schedule::command('leads:recalculate-scores --limit=1000')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/lead-score-recalculate.log'));

Schedule::command('senior:snapshot-performance')
    ->monthlyOn(1, '03:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/senior-snapshot.log'));

// ─── Currency Rate Sync ───────────────────────────────────────────────────────

Schedule::command('currency:sync-rates')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/currency-rates.log'));

// ─── Senior Daily Reminders ───────────────────────────────────────────────────

Schedule::command('senior:send-reminders')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/senior-reminders.log'));

// ─── Email Drip Campaign ──────────────────────────────────────────────────────

Schedule::command('email:process-drip')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/email-drip.log'));

// ─── Manager Scheduled Reports ────────────────────────────────────────────────

Schedule::command('manager:send-scheduled-reports')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/manager-scheduled-reports.log'));

// ─── Contract Reminders ───────────────────────────────────────────────────────

Schedule::command('contract:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/contract-reminders.log'));

// ─── K3: Scheduled Notifications ─────────────────────────────────────────────

Schedule::command('notifications:process-scheduled')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduled-notifications.log'));

// ─── RBAC K3: Security Anomaly Detection ─────────────────────────────────────

Schedule::command('security:anomaly-check')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/security-anomaly.log'));

// ─── Guest Inactivity Reminders ───────────────────────────────────────────────

Schedule::command('guest:milestone-reminders')
    ->dailyAt('08:00')
    ->appendOutputTo(storage_path('logs/guest-milestone-reminders.log'));

Schedule::command('guest:inactivity-reminder --days=7')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/guest-inactivity-reminder.log'));

// ─── Company Bulletins ────────────────────────────────────────────────────────

Schedule::command('bulletin:send-birthday-wishes')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/birthday-bulletins.log'));

// ─── System Cleanup ───────────────────────────────────────────────────────────

Schedule::command('system:cleanup')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/system-cleanup.log'));

// ─── University Deadline Reminder ─────────────────────────────────────────────

Schedule::command('university:deadline-reminder')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/university-deadline-reminder.log'));

// ─── Google Calendar 2-way Sync (Pull) ───────────────────────────────────────
// Portal'a Google'dan değişen event'leri çeker. Push observer ile anında,
// pull ise 15 dk'da bir — senior Google tarafında değişiklik yaparsa burada yakalanır.
Schedule::command('calendar:pull-google')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/calendar-pull-google.log'));

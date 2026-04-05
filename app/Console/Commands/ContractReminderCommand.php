<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class ContractReminderCommand extends Command
{
    protected $signature = 'contract:send-reminders
                            {--dry-run : Sadece raporla, gönderme}';

    protected $description = 'İmza bekleyen sözleşmeler için hatırlatma bildirimi gönder';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // 1. "requested" (misafir henüz imzalamadı) — 3+ gün geçti
        $pendingSign = GuestApplication::query()
            ->where('contract_status', 'requested')
            ->whereNotNull('contract_requested_at')
            ->where('contract_requested_at', '<=', now()->subDays(3))
            ->whereNull('contract_signed_at')
            ->get(['id', 'email', 'first_name', 'last_name', 'converted_student_id', 'company_id', 'contract_requested_at']);

        // 2. "signed_uploaded" (manager henüz karar vermedi) — 2+ gün geçti
        $pendingDecision = GuestApplication::query()
            ->where('contract_status', 'signed_uploaded')
            ->whereNotNull('contract_signed_at')
            ->where('contract_signed_at', '<=', now()->subDays(2))
            ->whereNull('contract_approved_at')
            ->get(['id', 'email', 'first_name', 'last_name', 'converted_student_id', 'company_id', 'contract_signed_at']);

        $this->info("İmza bekleyen: {$pendingSign->count()} | Karar bekleyen: {$pendingDecision->count()}");

        if ($dryRun) {
            $this->line('[dry-run] Bildirim gönderilmedi.');
            return Command::SUCCESS;
        }

        foreach ($pendingSign as $guest) {
            $studentId = trim((string) ($guest->converted_student_id ?? '')) !== ''
                ? (string) $guest->converted_student_id
                : 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);

            try {
                $this->notificationService->send([
                    'channel'     => 'in_app',
                    'category'    => 'system_alert',
                    'student_id'  => $studentId,
                    'company_id'  => (int) ($guest->company_id ?: 0),
                    'body'        => 'Sözleşmeniz imzalanmayı bekliyor. Lütfen portal üzerinden inceleyin ve imzalayın.',
                    'source_type' => 'guest_application',
                    'source_id'   => (string) $guest->id,
                ]);
            } catch (\Throwable $e) {
                $this->warn("Guest #{$guest->id} bildirimi gönderilemedi: {$e->getMessage()}");
            }
        }

        foreach ($pendingDecision as $guest) {
            // Manager / senior ekibine bildirim gönder (company_id bazlı staff bulunabilir)
            try {
                $this->notificationService->send([
                    'channel'     => 'in_app',
                    'category'    => 'system_alert',
                    'student_id'  => 'SYSTEM',
                    'company_id'  => (int) ($guest->company_id ?: 0),
                    'body'        => "Guest #{$guest->id} ({$guest->email}) imzalı sözleşmesi {$guest->contract_signed_at?->diffForHumans()} yüklendi ancak henüz karar verilmedi.",
                    'source_type' => 'guest_application',
                    'source_id'   => (string) $guest->id,
                ]);
            } catch (\Throwable $e) {
                $this->warn("Guest #{$guest->id} manager bildirimi gönderilemedi: {$e->getMessage()}");
            }
        }

        $this->info("Hatırlatmalar gönderildi. İmza bekleyen: {$pendingSign->count()}, karar bekleyen: {$pendingDecision->count()}");

        return Command::SUCCESS;
    }
}

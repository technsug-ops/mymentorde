<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Mevcut imzalı sözleşmelerden StudentPayment kayıtları oluşturur.
 * Tek seferlik çalıştırın: php artisan payments:sync-contracts
 *
 * converted_student_id olmayan guest'ler için GUEST-{tracking_token}
 * pseudo-ID kullanılır.
 */
class SyncContractPaymentsCommand extends Command
{
    protected $signature = 'payments:sync-contracts
                            {--dry-run : Kayıt oluşturmadan sadece sayıyı göster}';

    protected $description = 'İmzalı sözleşmelerden eksik StudentPayment kayıtlarını oluşturur';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $guests = GuestApplication::query()
            ->whereIn('contract_status', ['signed', 'approved'])
            ->where('contract_amount_eur', '>', 0)
            ->get([
                'id', 'company_id', 'first_name', 'last_name',
                'tracking_token', 'converted_student_id',
                'contract_amount_eur', 'contract_signed_at',
                'selected_package_title', 'contract_status',
            ]);

        $this->info("İmzalı sözleşme sayısı: {$guests->count()}");

        $created = 0;
        $skipped = 0;

        foreach ($guests as $guest) {
            // Öğrenciye convert edilmişse student_id, yoksa GUEST-{token}
            $studentId = $guest->converted_student_id
                ?? 'GUEST-' . $guest->tracking_token;

            // Aynı guest_id için zaten kayıt var mı?
            $exists = StudentPayment::where('notes', 'like', '%guest_id:' . $guest->id . '%')
                ->exists();

            if ($exists) {
                $this->line("  – Atlandı (zaten var): {$guest->first_name} {$guest->last_name}");
                $skipped++;
                continue;
            }

            $description = 'Danışmanlık Hizmeti'
                . ($guest->selected_package_title ? ' — ' . $guest->selected_package_title : '');

            $dueDate = $guest->contract_signed_at
                ? Carbon::parse($guest->contract_signed_at)->addDays(14)->toDateString()
                : now()->addDays(14)->toDateString();

            $label = "{$guest->first_name} {$guest->last_name} ({$studentId}) → €{$guest->contract_amount_eur}";

            if ($dryRun) {
                $this->line("  [dry-run] {$label}");
                $created++;
                continue;
            }

            StudentPayment::create([
                'company_id'     => $guest->company_id ?? null,
                'student_id'     => $studentId,
                'invoice_number' => StudentPayment::nextInvoiceNumber(),
                'description'    => $description,
                'amount_eur'     => (float) $guest->contract_amount_eur,
                'currency'       => 'EUR',
                'due_date'       => $dueDate,
                'status'         => 'pending',
                'notes'          => "Otomatik oluşturuldu — guest_id:{$guest->id} | {$guest->first_name} {$guest->last_name}",
                'created_by'     => null,
            ]);

            $this->line("  ✓ {$label}");
            $created++;
        }

        $action = $dryRun ? 'Oluşturulacak' : 'Oluşturuldu';
        $this->info("{$action}: {$created} | Atlandı (zaten var): {$skipped}");

        return Command::SUCCESS;
    }
}

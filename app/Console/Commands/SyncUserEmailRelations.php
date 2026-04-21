<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prod temizlik sonrası users.email değişti ama bağlı tablolardaki email
 * alanları eski değerde kaldı (guest_applications.email, senior_email,
 * assigned_senior_email vb.). Bu command o drift'i kapatır.
 *
 * Idempotent: çalışan her seferde user'ın şu anki email'iyle bağlı
 * tablo alanlarını senkronize eder, zaten senkron olan satıra dokunmaz.
 *
 * Sadece role = guest, student, senior, mentor, dealer için çalışır
 * (diğer staff rollerinin email drift'i sistem akışını bozmaz).
 */
class SyncUserEmailRelations extends Command
{
    protected $signature = 'system:sync-user-email-relations
                            {--dry-run : Sadece rapor g\u00f6ster, g\u00fcncelleme yapma}';

    protected $description = 'users.email ile bağlı tablolardaki (guest_applications.email, student_assignments.senior_email, guest_applications.assigned_senior_email) email drift\'ini kapatır.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? '[DRY-RUN] Sadece rapor.' : '[LIVE] Güncelleme yapılacak.');
        $this->newLine();

        $totals = [
            'guest_email'              => 0,  // guest_applications.email — guest_user_id ile eşleştir
            'assigned_senior_email'    => 0,  // guest_applications.assigned_senior_email — senior user'larına göre
            'student_assignment_senior_email' => 0, // student_assignments.senior_email
            'student_appointment_senior_email' => 0, // student_appointments.senior_email
        ];

        // 1. guest_applications.email — guest_user_id üzerinden user.email ile hizala
        if (Schema::hasTable('guest_applications')) {
            $rows = DB::table('guest_applications as ga')
                ->join('users as u', 'u.id', '=', 'ga.guest_user_id')
                ->whereNotNull('ga.guest_user_id')
                ->whereRaw('LOWER(ga.email) <> LOWER(u.email)')
                ->get(['ga.id', 'ga.email as old_email', 'u.email as new_email']);

            foreach ($rows as $r) {
                $this->line("  guest_applications#{$r->id}: {$r->old_email} -> {$r->new_email}");
                if (!$dryRun) {
                    DB::table('guest_applications')->where('id', $r->id)
                        ->update(['email' => $r->new_email, 'updated_at' => now()]);
                }
                $totals['guest_email']++;
            }
        }

        // 2. guest_applications.assigned_senior_email — old senior email → new
        if (Schema::hasTable('guest_applications')) {
            $seniors = User::query()
                ->withoutGlobalScopes()
                ->whereIn('role', ['senior', 'mentor'])
                ->get(['id', 'email']);

            // Eski email'leri kestirmek için her senior için en son kullanılan email
            // drift durumu olabilir ama biz sadece current email ile eşleşen ama farklı
            // yazım (case) olan kayıtları normalize edebiliriz. Asıl drift fix
            // senior_email değerinin BAŞKA bir email'e işaret etmesinde olur — ki cleanup
            // sonrası senior'ın eski email'i artık hiçbir user'a ait değil.
            //
            // Heuristic: assigned_senior_email'i olup karşılığı hiçbir user olmayan
            // satırları gel, uygun senior'u local part (@ öncesi) match'ine göre bul.
            $brokenRows = DB::table('guest_applications')
                ->whereNotNull('assigned_senior_email')
                ->where('assigned_senior_email', '!=', '')
                ->whereNotIn('assigned_senior_email', $seniors->pluck('email')->all())
                ->get(['id', 'assigned_senior_email']);

            foreach ($brokenRows as $r) {
                $oldLocal = strtolower(explode('@', (string) $r->assigned_senior_email)[0] ?? '');
                $match = $seniors->first(function ($s) use ($oldLocal) {
                    $newLocal = strtolower(explode('@', (string) $s->email)[0] ?? '');
                    return $newLocal === $oldLocal;
                });
                if ($match) {
                    $this->line("  guest_applications#{$r->id}.assigned_senior_email: {$r->assigned_senior_email} -> {$match->email}");
                    if (!$dryRun) {
                        DB::table('guest_applications')->where('id', $r->id)
                            ->update(['assigned_senior_email' => $match->email, 'updated_at' => now()]);
                    }
                    $totals['assigned_senior_email']++;
                }
            }
        }

        // 3. student_assignments.senior_email — aynı heuristik
        if (Schema::hasTable('student_assignments')) {
            $seniors = User::query()
                ->withoutGlobalScopes()
                ->whereIn('role', ['senior', 'mentor'])
                ->get(['id', 'email']);

            $brokenRows = DB::table('student_assignments')
                ->whereNotNull('senior_email')
                ->where('senior_email', '!=', '')
                ->whereNotIn('senior_email', $seniors->pluck('email')->all())
                ->get(['id', 'senior_email']);

            foreach ($brokenRows as $r) {
                $oldLocal = strtolower(explode('@', (string) $r->senior_email)[0] ?? '');
                $match = $seniors->first(function ($s) use ($oldLocal) {
                    $newLocal = strtolower(explode('@', (string) $s->email)[0] ?? '');
                    return $newLocal === $oldLocal;
                });
                if ($match) {
                    $this->line("  student_assignments#{$r->id}.senior_email: {$r->senior_email} -> {$match->email}");
                    if (!$dryRun) {
                        DB::table('student_assignments')->where('id', $r->id)
                            ->update(['senior_email' => $match->email, 'updated_at' => now()]);
                    }
                    $totals['student_assignment_senior_email']++;
                }
            }
        }

        // 4. student_appointments.senior_email — aynı heuristik
        if (Schema::hasTable('student_appointments')) {
            $seniors = User::query()
                ->withoutGlobalScopes()
                ->whereIn('role', ['senior', 'mentor'])
                ->get(['id', 'email']);

            $brokenRows = DB::table('student_appointments')
                ->whereNotNull('senior_email')
                ->where('senior_email', '!=', '')
                ->whereNotIn('senior_email', $seniors->pluck('email')->all())
                ->get(['id', 'senior_email']);

            foreach ($brokenRows as $r) {
                $oldLocal = strtolower(explode('@', (string) $r->senior_email)[0] ?? '');
                $match = $seniors->first(function ($s) use ($oldLocal) {
                    $newLocal = strtolower(explode('@', (string) $s->email)[0] ?? '');
                    return $newLocal === $oldLocal;
                });
                if ($match) {
                    $this->line("  student_appointments#{$r->id}.senior_email: {$r->senior_email} -> {$match->email}");
                    if (!$dryRun) {
                        DB::table('student_appointments')->where('id', $r->id)
                            ->update(['senior_email' => $match->email, 'updated_at' => now()]);
                    }
                    $totals['student_appointment_senior_email']++;
                }
            }
        }

        $this->newLine();
        $this->info('== Toplam güncelleme ==');
        foreach ($totals as $k => $v) {
            $this->line("  {$k}: {$v}");
        }

        return self::SUCCESS;
    }
}

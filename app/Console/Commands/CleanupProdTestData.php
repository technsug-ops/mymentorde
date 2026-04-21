<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CleanupProdTestData extends Command
{
    protected $signature = 'system:cleanup-prod-test
                            {--dry-run : Yalnızca rapor göster, hiçbir şey silme}
                            {--password=Mentorde2026! : Kalan kullanıcılar için şifre}';

    protected $description = 'Prod test temizliği: sabit 11 canonical test user kalır (manager, senior, student, guest, marketing_admin, marketing_staff, sales_admin, sales_staff + 3 dealer tier). Eksik roller otomatik oluşturulur. Diğer tüm user ve bağlı kayıtlar temizlenir. Emailler @panel.mentorde.com, şifreler sıfırlanır.';

    private const NEW_DOMAIN = 'panel.mentorde.com';

    /** Canonical hedef liste — bu 11 user korunur/oluşturulur. */
    private const TARGETS = [
        ['role' => 'manager',         'email_local' => 'manager',          'name' => 'Manager Test',          'match' => 'role_min',    'student_id' => null,          'dealer_type' => null],
        ['role' => 'senior',          'email_local' => 'senior',           'name' => 'Senior Test',           'match' => 'role_min',    'student_id' => 'SR-000001',   'dealer_type' => null],
        ['role' => 'student',         'email_local' => 'student',          'name' => 'Student Test',          'match' => 'role_min',    'student_id' => 'STU-000001',  'dealer_type' => null],
        ['role' => 'guest',           'email_local' => 'guest',            'name' => 'Guest Test',            'match' => 'role_min',    'student_id' => null,          'dealer_type' => null],
        ['role' => 'marketing_admin', 'email_local' => 'marketing.admin',  'name' => 'Marketing Admin Test',  'match' => 'role_min',    'student_id' => null,          'dealer_type' => null],
        ['role' => 'marketing_staff', 'email_local' => 'marketing.staff',  'name' => 'Marketing Staff Test',  'match' => 'role_min',    'student_id' => null,          'dealer_type' => null],
        ['role' => 'sales_admin',     'email_local' => 'sales.admin',      'name' => 'Sales Admin Test',      'match' => 'role_min',    'student_id' => null,          'dealer_type' => null],
        ['role' => 'sales_staff',     'email_local' => 'sales.staff',      'name' => 'Sales Staff Test',      'match' => 'role_min',    'student_id' => null,          'dealer_type' => null],
        ['role' => 'dealer',          'email_local' => 'dealer.lead',      'name' => 'Dealer Lead Gen Test',  'match' => 'dealer_type', 'student_id' => null,          'dealer_type' => 'LEA'],
        ['role' => 'dealer',          'email_local' => 'dealer.freelance', 'name' => 'Dealer Freelance Test', 'match' => 'dealer_type', 'student_id' => null,          'dealer_type' => 'FRE'],
        ['role' => 'dealer',          'email_local' => 'dealer.b2b',       'name' => 'Dealer B2B Test',       'match' => 'dealer_type', 'student_id' => null,          'dealer_type' => 'B2B'],
    ];

    public function handle(): int
    {
        $password = (string) $this->option('password');
        $dryRun   = (bool) $this->option('dry-run');

        $this->info($dryRun ? '[DRY-RUN] Hiçbir değişiklik yapılmayacak.' : '[LIVE] Değişiklikler yazılacak.');
        $this->newLine();

        $report = [];

        $run = function () use ($password, $dryRun, &$report) {
            $passwordHash = Hash::make($password);

            // 1. Dealer master tablosunda LEA/FRE/B2B varlığını garanti et
            $dealerEnsured = [];
            foreach (['LEA', 'FRE', 'B2B'] as $type) {
                $code = $this->ensureDealerMaster($type, $dryRun);
                if ($code) {
                    $dealerEnsured[$type] = $code;
                }
            }
            $report['dealer_masters_ensured'] = $dealerEnsured;

            // 2. Her target için existing user eşleştir, yoksa oluştur
            $keepEntries = [];
            $createdUsers = [];
            foreach (self::TARGETS as $target) {
                $user = $this->resolveTargetUser($target);
                if (!$user) {
                    if ($dryRun) {
                        $createdUsers[] = "{$target['role']} / {$target['email_local']}@" . self::NEW_DOMAIN . " (oluşturulacak)";
                        continue;
                    }
                    $user = $this->createCanonicalUser($target, $passwordHash, $dealerEnsured);
                    $createdUsers[] = "#{$user->id} [{$target['role']}] {$user->email} (oluşturuldu)";
                }
                $keepEntries[] = ['user' => $user, 'target' => $target];
            }
            $report['created_users'] = $createdUsers;

            $keepIds = array_map(fn ($e) => (int) $e['user']->id, $keepEntries);
            $report['keep_users'] = array_map(
                fn ($e) => "#{$e['user']->id} [{$e['user']->role}] {$e['user']->name} <{$e['user']->email}>"
                    . ($e['user']->student_id ? " sid={$e['user']->student_id}" : '')
                    . ($e['user']->dealer_code ? " dealer={$e['user']->dealer_code}" : ''),
                $keepEntries
            );

            $keepStudentIds = [];
            foreach ($keepEntries as $e) {
                if (!empty($e['user']->student_id)) {
                    $keepStudentIds[] = (string) $e['user']->student_id;
                }
                // Target'ın tanımladığı student_id (yeni oluşturulacak user için de)
                if (!empty($e['target']['student_id'])) {
                    $keepStudentIds[] = (string) $e['target']['student_id'];
                }
            }
            $keepStudentIds = array_values(array_unique($keepStudentIds));
            $report['keep_student_ids'] = $keepStudentIds;

            $keepDealerCodes = array_values(array_unique(array_filter(array_map(
                fn ($e) => (string) ($e['user']->dealer_code ?? ''),
                $keepEntries
            ))));
            $report['keep_dealer_codes'] = $keepDealerCodes;

            // 3. Users sil — cascade
            $deleteUserIds = User::whereNotIn('id', $keepIds)->pluck('id')->all();
            $report['deleted_user_count'] = count($deleteUserIds);
            if (!$dryRun && count($deleteUserIds) > 0) {
                User::whereIn('id', $deleteUserIds)->forceDelete();
            }

            // 4. student_id string-FK tabloları temizle
            $studentIdTables = $this->findTablesWithColumn('student_id');
            $studentDeletes = [];
            foreach ($studentIdTables as $table) {
                $count = $this->countAndDelete($table, 'student_id', $keepStudentIds, $dryRun);
                $studentDeletes[$table] = $count;
            }
            $report['student_id_deletes'] = $studentDeletes;

            // 5. guest_user_id tabloları
            $guestUserIdTables = $this->findTablesWithColumn('guest_user_id');
            $guestDeletes = [];
            foreach ($guestUserIdTables as $table) {
                $count = $this->countAndDelete($table, 'guest_user_id', $keepIds, $dryRun);
                $guestDeletes[$table] = $count;
            }
            $report['guest_user_id_deletes'] = $guestDeletes;

            // 6. Kalan user'ların email + password + name + student_id + dealer_code güncelle
            $emailUpdates = [];
            foreach ($keepEntries as $entry) {
                $user   = $entry['user'];
                $target = $entry['target'];
                $newEmail = $target['email_local'] . '@' . self::NEW_DOMAIN;

                $updates = [
                    'email'             => $newEmail,
                    'password'          => $passwordHash,
                    'email_verified_at' => now(),
                    'name'              => $target['name'],
                    'updated_at'        => now(),
                ];

                if (!empty($target['student_id']) && empty($user->student_id)) {
                    $updates['student_id'] = $target['student_id'];
                }
                if (!empty($target['dealer_type']) && empty($user->dealer_code)) {
                    $updates['dealer_code'] = $dealerEnsured[$target['dealer_type']] ?? null;
                }

                $emailUpdates[] = "#{$user->id} [{$user->role}] {$user->email} -> {$newEmail}, name='{$target['name']}'"
                    . (isset($updates['student_id']) ? ", sid={$updates['student_id']}" : '')
                    . (isset($updates['dealer_code']) ? ", dealer={$updates['dealer_code']}" : '');

                if (!$dryRun) {
                    $oldEmail = (string) $user->email;
                    User::where('id', $user->id)->update($updates);
                    // Email drift önleme: bağlı tablolardaki eski email'i yeni email'le
                    // eşitle (guest_applications.email, senior_email, assigned_senior_email,
                    // student_appointments.senior_email). Idempotent.
                    $this->propagateEmailChange($oldEmail, $newEmail);
                }
            }
            $report['email_updates'] = $emailUpdates;
            $report['password_reset_to'] = $password;
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
        }

        $this->renderReport($report);

        return self::SUCCESS;
    }

    private function resolveTargetUser(array $target): ?User
    {
        $query = User::query()->where('role', $target['role'])->orderBy('id');

        if (($target['match'] ?? null) === 'dealer_type' && !empty($target['dealer_type'])) {
            $dealerCodes = DB::table('dealers')
                ->where('dealer_type_code', $target['dealer_type'])
                ->pluck('code')
                ->all();
            if (empty($dealerCodes)) {
                return null;
            }
            $query->whereIn('dealer_code', $dealerCodes);
        }

        return $query->first();
    }

    private function createCanonicalUser(array $target, string $passwordHash, array $dealerEnsured): User
    {
        $newEmail = $target['email_local'] . '@' . self::NEW_DOMAIN;
        $data = [
            'name'              => $target['name'],
            'email'             => $newEmail,
            'password'          => $passwordHash,
            'email_verified_at' => now(),
            'role'              => $target['role'],
            'is_active'         => true,
            'student_id'        => $target['student_id'] ?? null,
        ];
        if (!empty($target['dealer_type'])) {
            $data['dealer_code'] = $dealerEnsured[$target['dealer_type']] ?? null;
        }
        return User::create($data);
    }

    private function ensureDealerMaster(string $type, bool $dryRun): ?string
    {
        $existing = DB::table('dealers')
            ->where('dealer_type_code', $type)
            ->orderBy('id')
            ->value('code');
        if ($existing) {
            return (string) $existing;
        }
        if ($dryRun) {
            return $type . '-000001 (oluşturulacak)';
        }
        $code = $type . '-000001';
        DB::table('dealers')->insertOrIgnore([
            'code'             => $code,
            'name'             => $type . ' Test Partner',
            'dealer_type_code' => $type,
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
        return $code;
    }

    /**
     * Kalan user'ın email'i değişince bağlı tablolardaki eski email'i yeni'ye güncelle.
     * Tek bir user'ın değişimi için idempotent tek update pass.
     */
    private function propagateEmailChange(string $oldEmail, string $newEmail): void
    {
        if (strcasecmp($oldEmail, $newEmail) === 0) {
            return;
        }
        $old = strtolower($oldEmail);
        $new = strtolower($newEmail);

        // guest_applications.email (guest kullanıcısı için)
        DB::table('guest_applications')
            ->whereRaw('LOWER(email) = ?', [$old])
            ->update(['email' => $new, 'updated_at' => now()]);

        // guest_applications.assigned_senior_email
        DB::table('guest_applications')
            ->whereRaw('LOWER(assigned_senior_email) = ?', [$old])
            ->update(['assigned_senior_email' => $new, 'updated_at' => now()]);

        // student_assignments.senior_email
        if (Schema::hasTable('student_assignments')) {
            DB::table('student_assignments')
                ->whereRaw('LOWER(senior_email) = ?', [$old])
                ->update(['senior_email' => $new, 'updated_at' => now()]);
        }

        // student_appointments.senior_email + student_email
        if (Schema::hasTable('student_appointments')) {
            DB::table('student_appointments')
                ->whereRaw('LOWER(senior_email) = ?', [$old])
                ->update(['senior_email' => $new, 'updated_at' => now()]);
            DB::table('student_appointments')
                ->whereRaw('LOWER(student_email) = ?', [$old])
                ->update(['student_email' => $new, 'updated_at' => now()]);
        }

        // manager_reports.senior_email
        if (Schema::hasTable('manager_reports')) {
            DB::table('manager_reports')
                ->whereRaw('LOWER(senior_email) = ?', [$old])
                ->update(['senior_email' => $new, 'updated_at' => now()]);
        }

        // senior_performance_snapshots.senior_email
        if (Schema::hasTable('senior_performance_snapshots')) {
            DB::table('senior_performance_snapshots')
                ->whereRaw('LOWER(senior_email) = ?', [$old])
                ->update(['senior_email' => $new, 'updated_at' => now()]);
        }
    }

    private function countAndDelete(string $table, string $column, array $keepValues, bool $dryRun): int
    {
        $q = DB::table($table);
        if (count($keepValues) > 0) {
            $q->whereNotIn($column, $keepValues);
        }
        $count = $q->count();

        if (!$dryRun && $count > 0) {
            $q2 = DB::table($table);
            if (count($keepValues) > 0) {
                $q2->whereNotIn($column, $keepValues);
            }
            $q2->delete();
        }
        return $count;
    }

    /**
     * information_schema'dan belirli bir kolona sahip tüm tabloları çek.
     * @return array<int, string>
     */
    private function findTablesWithColumn(string $column): array
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT TABLE_NAME AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ?
             ORDER BY TABLE_NAME',
            [$db, $column]
        );
        $excluded = ['users'];
        return array_values(array_filter(
            array_map(fn ($r) => (string) $r->t, $rows),
            fn ($t) => !in_array($t, $excluded, true) && !str_starts_with($t, 'information_schema')
        ));
    }

    private function renderReport(array $report): void
    {
        $this->newLine();
        $this->line('<fg=cyan>== DEALER MASTER (LEA/FRE/B2B) ==</>');
        foreach ($report['dealer_masters_ensured'] ?? [] as $type => $code) {
            $this->line("  {$type}: {$code}");
        }

        $this->newLine();
        $this->line('<fg=cyan>== KORUNAN / OLUŞTURULACAK KULLANICILAR ==</>');
        foreach ($report['keep_users'] ?? [] as $line) {
            $this->line('  ' . $line);
        }

        if (!empty($report['created_users'])) {
            $this->newLine();
            $this->line('<fg=green>== YENİ OLUŞTURULAN USER ==</>');
            foreach ($report['created_users'] as $line) {
                $this->line('  ' . $line);
            }
        }

        $this->newLine();
        $this->line('<fg=cyan>== KORUNAN STUDENT_ID ==</>');
        $this->line('  ' . (count($report['keep_student_ids']) ? implode(', ', $report['keep_student_ids']) : '(yok)'));

        $this->newLine();
        $this->line('<fg=cyan>== KORUNAN DEALER_CODE ==</>');
        $this->line('  ' . (count($report['keep_dealer_codes']) ? implode(', ', $report['keep_dealer_codes']) : '(yok)'));

        $this->newLine();
        $this->line("<fg=yellow>Silinen user sayısı: {$report['deleted_user_count']}</>");

        $this->newLine();
        $this->line('<fg=cyan>== STUDENT_ID TABLO SİLİMLERİ ==</>');
        foreach ($report['student_id_deletes'] ?? [] as $table => $count) {
            if ($count > 0) {
                $this->line("  {$table}: {$count}");
            }
        }

        $this->newLine();
        $this->line('<fg=cyan>== GUEST_USER_ID TABLO SİLİMLERİ ==</>');
        foreach ($report['guest_user_id_deletes'] ?? [] as $table => $count) {
            if ($count > 0) {
                $this->line("  {$table}: {$count}");
            }
        }

        $this->newLine();
        $this->line('<fg=cyan>== EMAIL + GÜNCELLEMELER ==</>');
        foreach ($report['email_updates'] ?? [] as $line) {
            $this->line('  ' . $line);
        }

        $this->newLine();
        $this->info("Tüm kalan kullanıcıların şifresi: {$report['password_reset_to']}");
    }
}

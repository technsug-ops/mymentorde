<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Dev ortamı için student@mentorde.local kullanıcısına
 * linked GuestApplication kaydı oluşturur.
 *
 * EnsureStudentRole middleware, StudentGuestResolver aracılığıyla
 * converted_to_student=true olan bir GuestApplication arar.
 * Bu kayıt olmadan student kullanıcısı portala giremez.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('guest_applications')) {
            return;
        }

        $studentEmail = 'student@mentorde.local';
        $studentId    = 'BCS100001';

        $user = DB::table('users')
            ->where('email', $studentEmail)
            ->first();

        if (!$user) {
            return; // Dev kullanıcısı yok, üretim ortamı — pas geç
        }

        // Zaten linked kayıt varsa tekrar oluşturma
        $exists = DB::table('guest_applications')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($user, $studentId) {
                $q->where('guest_user_id', $user->id)
                  ->orWhere('converted_student_id', $studentId)
                  ->orWhereRaw('lower(email) = ?', ['student@mentorde.local']);
            })
            ->exists();

        if ($exists) {
            // Kayıt var — sadece converted_to_student=true yap ve student_id linkle
            DB::table('guest_applications')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($user, $studentId) {
                    $q->where('guest_user_id', $user->id)
                      ->orWhere('converted_student_id', $studentId)
                      ->orWhereRaw('lower(email) = ?', ['student@mentorde.local']);
                })
                ->update([
                    'converted_to_student'  => true,
                    'converted_student_id'  => $studentId,
                    'guest_user_id'         => $user->id,
                    'lead_status'           => 'converted',
                    'updated_at'            => now(),
                ]);

            return;
        }

        // Şirket ID'sini al
        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId === 0 && Schema::hasTable('companies')) {
            $company   = DB::table('companies')->where('is_active', true)->orderBy('id')->first();
            $companyId = $company ? (int) $company->id : 1;
        }

        $now = now();

        DB::table('guest_applications')->insert([
            'company_id'           => $companyId,
            'guest_user_id'        => $user->id,
            'tracking_token'       => Str::random(32),
            'first_name'           => 'Ahmet',
            'last_name'            => 'Yılmaz',
            'email'                => $studentEmail,
            'phone'                => '+49 170 1234567',
            'application_type'     => 'bachelor',
            'lead_status'          => 'converted',
            'converted_to_student' => true,
            'converted_student_id' => $studentId,
            'kvkk_consent'         => true,
            'docs_ready'           => false,
            'is_archived'          => false,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);
    }

    public function down(): void
    {
        // Silmek güvenli değil — no-op
    }
};

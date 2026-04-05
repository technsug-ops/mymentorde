<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Örnek ticketların gösterilebilmesi için:
 * 1. Senior'un öğrencisi (BCS-26-03-SMQQ) için guest_application oluşturur.
 * 2. Mevcut [ÖRNEK] ticketları bu guest_application'a bağlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guest_tickets') || ! Schema::hasTable('guest_applications')) {
            return;
        }

        $studentId = 'BCS-26-03-SMQQ';
        $companyId = DB::table('companies')->value('id') ?? 1;

        // Zaten bu öğrenciye ait bir guest_application var mı?
        $ga = DB::table('guest_applications')
            ->whereNull('deleted_at')
            ->where('converted_student_id', $studentId)
            ->first();

        if (! $ga) {
            // Yoksa dev amaçlı minimal bir kayıt oluştur
            $gaId = DB::table('guest_applications')->insertGetId([
                'tracking_token'       => Str::random(48),
                'first_name'           => 'Test',
                'last_name'            => 'Öğrenci',
                'email'                => 'test.ogrenci@dev.local',
                'phone'                => '+905001234567',
                'application_type'     => 'bachelor',
                'converted_to_student' => true,
                'converted_student_id' => $studentId,
                'assigned_senior_email'=> 'seniorww@mentorde.local',
                'company_id'           => $companyId,
                'kvkk_consent'         => true,
                'status_message'       => 'registered',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        } else {
            $gaId = $ga->id;
        }

        // [ÖRNEK] ticketlarını bu guest_application'a yönlendir
        DB::table('guest_tickets')
            ->where('subject', 'like', '[ÖRNEK]%')
            ->update([
                'guest_application_id' => $gaId,
                'company_id'           => $companyId,
                'updated_at'           => now(),
            ]);
    }

    public function down(): void
    {
        // Oluşturulan dev guest_application'ı ve ticketları temizle
        DB::table('guest_applications')
            ->where('email', 'test.ogrenci@dev.local')
            ->delete();
    }
};

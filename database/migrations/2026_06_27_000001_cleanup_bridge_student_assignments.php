<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tek seferlik temizlik: docs_pending köprüsünün yanlışlıkla oluşturduğu
 * "aday öğrenci → aktif öğrenci" StudentAssignment kayıtlarını kaldırır.
 *
 * Köprü kaydı = converted_to_student=false + converted_student_id dolu olan
 * GuestApplication'a bağlı StudentAssignment. Tam dönüşmüş (sözleşmeli)
 * öğrencilere DOKUNMAZ. Soft-delete (kurtarılabilir).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Köprülenmiş aday öğrencilerin student_id'leri (converted_to_student=false)
        $bridgedSids = DB::table('guest_applications')
            ->where('converted_to_student', 0)
            ->whereNotNull('converted_student_id')
            ->where('converted_student_id', '!=', '')
            ->pluck('converted_student_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($bridgedSids)) {
            return;
        }

        $now = now();

        // 1. Köprü StudentAssignment'larını soft-delete (Aktif Öğrenciler'den çıkar)
        DB::table('student_assignments')
            ->whereIn('student_id', $bridgedSids)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        // 2. Kickoff task'larını soft-delete
        DB::table('marketing_tasks')
            ->where('source_type', 'application_prep_started')
            ->whereIn('source_id', $bridgedSids)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        // 3. Aday bağını temizle → tekrar sadece aday öğrenci olur
        DB::table('guest_applications')
            ->where('converted_to_student', 0)
            ->whereIn('converted_student_id', $bridgedSids)
            ->update(['converted_student_id' => null, 'updated_at' => $now]);
    }

    public function down(): void
    {
        // Geri alma yok (soft-delete'ler manuel restore edilebilir).
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant dönüşümü öncesi geri-doldurma.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * `BelongsToCompany` eklendiği anda okuma `company_id`'ye göre FİLTRELENİR.
 * Canlıda `company_id` boş kalmış satırlar varsa o kayıtlar ekranlardan
 * SESSİZCE KAYBOLUR — hata da vermez, sadece yok olurlar. Öğrencinin vize
 * başvurusu, ödemesi, konaklaması "hiç yokmuş gibi" görünür.
 *
 * Faz 6/7/9'un bugüne kadar açılmamasının asıl sebebi bu risk.
 *
 * ── NE YAPIYOR ──────────────────────────────────────────────────────────
 * Her kaydın firmasını ÜST KAYDINDAN türetiyor:
 *   öğrenci süreci tabloları → student_assignments.company_id
 *   aday geri bildirimi      → guest_applications.company_id
 *   personel kayıtları       → users.company_id
 *
 * Varsayılan firmaya toptan yazmıyor: yanlış firmaya bağlanan bir kayıt,
 * boş kalan kayıttan daha kötü — orada gerçek bir sızıntı olurdu.
 * Üst kaydı bulunamayan satır BOŞ BIRAKILIYOR ve rapor komutu
 * (`tenant:scope-report`) onları listeliyor.
 *
 * ── GERİ ALMA ───────────────────────────────────────────────────────────
 * down() bilerek boş: doldurulan değer DOĞRU değerdir, geri almak veriyi
 * yeniden bozmak olurdu. Migration tekrar çalışsa da zararsız — yalnızca
 * hâlâ boş olan satırlara dokunuyor.
 */
return new class extends Migration
{
    /**
     * tablo => [kendi kolonu, üst tablo, üst tablodaki eşleşme kolonu]
     *
     * @var array<string, array{0:string,1:string,2:string}>
     */
    private const SOURCES = [
        // Öğrenci süreci — firma öğrencinin atamasından geliyor
        'student_accommodations'          => ['student_id', 'student_assignments', 'student_id'],
        'student_appointments'            => ['student_id', 'student_assignments', 'student_id'],
        'student_checklists'              => ['student_id', 'student_assignments', 'student_id'],
        'student_feedback'                => ['student_id', 'student_assignments', 'student_id'],
        'student_institution_documents'   => ['student_id', 'student_assignments', 'student_id'],
        'student_language_courses'        => ['student_id', 'student_assignments', 'student_id'],
        'student_material_reads'          => ['student_id', 'student_assignments', 'student_id'],
        'student_payments'                => ['student_id', 'student_assignments', 'student_id'],
        'student_shipments'               => ['student_id', 'student_assignments', 'student_id'],
        'student_university_applications' => ['student_id', 'student_assignments', 'student_id'],
        'student_visa_applications'       => ['student_id', 'student_assignments', 'student_id'],
        'dm_threads'                      => ['student_id', 'student_assignments', 'student_id'],
        'notification_dispatches'         => ['student_id', 'student_assignments', 'student_id'],
        'notification_preferences'        => ['student_id', 'student_assignments', 'student_id'],

        // Aday tarafı
        'guest_feedback'                  => ['guest_application_id', 'guest_applications', 'id'],

        // Personel kayıtları — firma kullanıcıdan geliyor
        'audit_trails'                    => ['user_id', 'users', 'id'],
        'business_contracts'              => ['user_id', 'users', 'id'],
        'consent_records'                 => ['user_id', 'users', 'id'],
        'digital_asset_activity_log'      => ['user_id', 'users', 'id'],
        'marketing_teams'                 => ['user_id', 'users', 'id'],
        'staff_kpi_targets'               => ['user_id', 'users', 'id'],
    ];

    public function up(): void
    {
        foreach (self::SOURCES as $table => [$ownColumn, $parentTable, $parentColumn]) {
            if (! $this->usable($table, $ownColumn, $parentTable, $parentColumn)) {
                continue;
            }

            // ⚠ EXISTS ŞART. Alt sorgu eşleşme bulamazsa NULL döner ve bu
            // değer yazılmaya çalışılır. 16 tabloda `company_id` NOT NULL —
            // orada migration "Column 'company_id' cannot be null" ile
            // PATLAR ve kendinden sonraki hiçbir tablo işlenmez.
            //
            // Yerelde görünmedi: o tablolar boştu, hiçbir satır eşleşmedi.
            // Canlıda 2026-08-08'de dm_threads'te patladı.
            //
            // EXISTS, sahibi türetilebilen satırları seçiyor; türetilemeyen
            // satır BOŞ BIRAKILIYOR (bilerek — bkz. sınıf başlığı) ve son
            // süpürme migration'ı (140000) onları ana firmaya yazıyor.
            DB::statement(sprintf(
                'UPDATE %1$s SET company_id = ('
                . ' SELECT p.company_id FROM %2$s p'
                . ' WHERE p.%3$s = %1$s.%4$s AND p.company_id IS NOT NULL AND p.company_id > 0'
                . ' LIMIT 1'
                . ') WHERE (company_id IS NULL OR company_id = 0) AND %4$s IS NOT NULL'
                . ' AND EXISTS ('
                . ' SELECT 1 FROM %2$s q'
                . ' WHERE q.%3$s = %1$s.%4$s AND q.company_id IS NOT NULL AND q.company_id > 0'
                . ')',
                $table,
                $parentTable,
                $parentColumn,
                $ownColumn
            ));
        }
    }

    public function down(): void
    {
        // Bilerek boş — bkz. sınıf başlığı.
    }

    /** Tablo ve kolonların hepsi var mı? (eski kurulumlarda eksik olabilir) */
    private function usable(string $table, string $ownColumn, string $parentTable, string $parentColumn): bool
    {
        return Schema::hasTable($table)
            && Schema::hasTable($parentTable)
            && Schema::hasColumn($table, 'company_id')
            && Schema::hasColumn($table, $ownColumn)
            && Schema::hasColumn($parentTable, 'company_id')
            && Schema::hasColumn($parentTable, $parentColumn);
    }
};

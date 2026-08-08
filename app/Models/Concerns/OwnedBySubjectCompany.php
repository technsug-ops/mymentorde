<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Şirket, kaydın KONUSUNDAN gelir — kaydı yazandan değil.
 *
 * Vize başvurusu, ödeme, konaklama, randevu, bildirim… hepsi bir öğrencinin
 * ya da adayın kaydı. Süreci MentorDE yürütse bile kayıt ÖĞRENCİNİN firmasına
 * ait olmalı; aksi halde partner firma kendi öğrencisinin ilerlemesini
 * göremez — hata da almaz, ekran boş gelir.
 *
 * Aynı hata olay akışında yaşandı ve konu esas alınarak çözüldü; bkz.
 * EventLogService::companyOfSubject ve PartnerProgressVisibilityTest.
 *
 * `BelongsToCompany` ile birlikte gelir ve yalnızca INSERT anını etkiler —
 * okuma filtresi değişmez. Kullanan model `ResolvesOwnCompany` arayüzünü de
 * bildirmeli; yazma kancası modeli o arayüzle tanıyor.
 */
trait OwnedBySubjectCompany
{
    use BelongsToCompany;

    /**
     * Konu zinciri: kendi kolonu → üst tablo → üst tablodaki eşleşme kolonu.
     * Sırayla denenir, ilk bulan kazanır.
     *
     * ⚠ Öğrenci önce: bir kayıtta hem `student_id` hem `guest_id` doluysa
     * öğrenci hâli günceldir (aday öğrenciye dönüşmüş demektir).
     *
     * @var list<array{0:string,1:string,2:string}>
     */
    private const SUBJECT_CHAIN = [
        ['student_id', 'student_assignments', 'student_id'],
        ['guest_application_id', 'guest_applications', 'id'],
        ['guest_id', 'guest_applications', 'id'],
    ];

    /** Konunun şirketi; türetilemezse null (aktörün şirketine düşülür). */
    public function tenantOwnerCompanyId(): ?int
    {
        foreach (self::SUBJECT_CHAIN as [$ownColumn, $parentTable, $parentColumn]) {
            // ⚠ int'e ÇEVİRME. `student_id` bu şemada metin bir kimlik
            // ("STU-2026-014"); int'e çevrilseydi 0 olur ve zincir hiç
            // çalışmazdı — sessizce aktörün şirketine düşerdik.
            $value = trim((string) ($this->{$ownColumn} ?? ''));

            if ($value === '' || $value === '0') {
                continue;
            }

            $companyId = $this->companyFrom($parentTable, $parentColumn, $value);

            if ($companyId !== null) {
                return $companyId;
            }
        }

        return null;
    }

    /**
     * Konunun kendi tenant'ı okunuyor — kapsam BİLEREK atlanıyor (DB::table
     * zaten Eloquent kapsamını görmez): aktör o şirketi göremiyor olsa bile
     * kayıt doğru tarafa yazılmalı.
     */
    private function companyFrom(string $table, string $column, string $value): ?int
    {
        try {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                return null;
            }

            $companyId = (int) DB::table($table)
                ->where($column, $value)
                ->where('company_id', '>', 0)
                ->value('company_id');

            return $companyId > 0 ? $companyId : null;
        } catch (\Throwable) {
            // Tablo yok ya da kolon tipi uyumsuz — aktörün bağlamına düş.
            return null;
        }
    }
}

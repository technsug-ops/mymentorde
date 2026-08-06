<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentUploadToken;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Partnerin belge ekranları: "kimden ne istendi, ne geldi, ne eksik".
 *
 * ── NEDEN AYRI EKRAN ────────────────────────────────────────────────────
 * Partner menüsüne önce hazır sayfalar bağlanmıştı ve ikisi de yanlıştı:
 *
 *   /manager/required-documents        → zorunlu belge TANIMLAMA ekranı.
 *                                        Operasyon konfigürasyonu; partner
 *                                        burada kural yazmaz.
 *   /manager/document-requests/analytics → dönüşüm hunisi, hatırlatma
 *                                        etkinliği, ortalama yükleme süresi.
 *                                        SaaS metrik panosu; partnerin işi
 *                                        değil.
 *
 * Partnerin ihtiyacı bu ikisi de değil: kendi adayından hangi belgenin
 * istendiği ve gelip gelmediği. Bu iki ekran onu veriyor.
 *
 * ── KAPSAM TUZAĞI ───────────────────────────────────────────────────────
 * `DocumentUploadToken` firma kapsamlı. Talebi operasyon kendi bağlamında
 * oluşturduğunda token ÜST firmaya yazılır; partner kapsamıyla arasa
 * hiçbir şey bulamaz ve ekran sessizce boş gelirdi. Bu yüzden kapsamsız
 * okunuyor, yetki sınırı kişinin partnere ait olması (kimlik listesi
 * firma kapsamlı sorgulardan geliyor).
 */
class PartnerDocumentController extends Controller
{
    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    // ─── BELGE LİSTESİ (yüklenmiş belgeler) ─────────────────────────────────

    public function index(Request $request): View
    {
        $people = $this->people();
        $q      = trim((string) $request->query('q', ''));

        $rows = collect();

        if ($people->isNotEmpty()) {
            $rows = Document::query()
                ->whereIn('student_id', $people->keys()->all())
                ->when($q !== '', fn ($b) => $b->where(function ($x) use ($q) {
                    $x->where('original_file_name', 'like', "%{$q}%")
                      ->orWhere('standard_file_name', 'like', "%{$q}%");
                }))
                ->latest('id')
                ->limit(500)
                ->get(['id', 'student_id', 'original_file_name', 'standard_file_name',
                       'status', 'created_at']);
        }

        return view('manager.partner.documents-index', [
            'rows'   => $rows,
            'people' => $people,
            'q'      => $q,
        ]);
    }

    // ─── BELGE TALEPLERİ (gönderilmiş talepler ve durumu) ────────────────────

    public function requests(): View
    {
        $guests   = $this->guests();
        $students = $this->students();

        $guestIds   = $guests->keys()->map(fn ($id) => (string) $id)->all();
        $studentIds = $students->keys()->map(fn ($id) => (string) $id)->all();

        $rows = collect();

        if ($guestIds !== [] || $studentIds !== []) {
            // ⚠ Kapsamsız: talebi operasyon kendi bağlamında oluşturmuş
            // olabilir. Yetki sınırı aşağıdaki hedef listeleri.
            $rows = DocumentUploadToken::query()
                ->withoutGlobalScope('company')
                ->where(function ($q) use ($guestIds, $studentIds) {
                    if ($guestIds !== []) {
                        $q->orWhere(fn ($x) => $x
                            ->where('target_type', DocumentUploadToken::TARGET_GUEST)
                            ->whereIn('target_id', $guestIds));
                        // Eski kayıtlar polymorphic alanları doldurmuyordu.
                        $q->orWhereIn('guest_application_id', $guestIds);
                    }

                    if ($studentIds !== []) {
                        $q->orWhere(fn ($x) => $x
                            ->where('target_type', DocumentUploadToken::TARGET_STUDENT)
                            ->whereIn('target_id', $studentIds));
                        $q->orWhereIn('target_student_id', $studentIds);
                    }
                })
                ->latest('id')
                ->limit(500)
                ->get();
        }

        return view('manager.partner.documents-requests', [
            'rows'     => $rows,
            'guests'   => $guests,
            'students' => $students,
        ]);
    }

    // ─── Kimlik çözümleme ───────────────────────────────────────────────────

    /**
     * Partnerin adayları: id => ad.
     *
     * @return Collection<int,string>
     */
    private function guests(): Collection
    {
        $cid = $this->companyId();

        return collect(GuestApplication::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->get(['id', 'first_name', 'last_name', 'converted_student_id']))
            ->mapWithKeys(fn ($g) => [
                (int) $g->id => trim($g->first_name . ' ' . $g->last_name) ?: ('Aday #' . $g->id),
            ]);
    }

    /**
     * Partnerin öğrencileri: student_id => ad.
     *
     * @return Collection<string,string>
     */
    private function students(): Collection
    {
        $cid = $this->companyId();

        // ⚠ collect(): Eloquent koleksiyonunun merge'i model bekler, değerler
        // metin. Sarmalanmazsa people() içindeki merge "getKey() on string"
        // ile patlar — ve taraflardan biri boşken hiç görünmez.
        return collect(StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->get(['student_id', 'display_name']))
            ->mapWithKeys(fn ($s) => [
                (string) $s->student_id => (string) ($s->display_name ?: $s->student_id),
            ]);
    }

    /**
     * Belge sahibi kimlikleri: documents.student_id => ad.
     *
     * Dönüşmemiş adayın belgeleri `GST-00000123` biçiminde sanal bir sahip
     * altında duruyor (bkz. GuestDocumentAccessTrait). Aynı eşlemeyi burada
     * da kurmazsak adayların belgeleri listede hiç görünmez.
     *
     * @return Collection<string,string>
     */
    private function people(): Collection
    {
        $cid = $this->companyId();

        $fromGuests = collect(GuestApplication::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->get(['id', 'first_name', 'last_name', 'converted_student_id']))
            ->mapWithKeys(function ($g) {
                $ownerId = trim((string) ($g->converted_student_id ?? ''));

                if ($ownerId === '') {
                    $ownerId = 'GST-' . str_pad((string) $g->id, 8, '0', STR_PAD_LEFT);
                }

                return [$ownerId => trim($g->first_name . ' ' . $g->last_name) ?: ('Aday #' . $g->id)];
            });

        return $this->students()->merge($fromGuests);
    }
}

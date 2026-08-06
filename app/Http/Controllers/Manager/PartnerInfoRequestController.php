<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\DocumentUploadToken;
use App\Models\GuestApplication;
use App\Models\PartnerInfoRequest;
use App\Models\PartnerInfoRequestItem;
use App\Models\StudentAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Bilgi/belge talebi zinciri: Operasyon → Partner → Öğrenci.
 *
 * ── NEDEN BÖYLE ─────────────────────────────────────────────────────────
 * Eksik belge doğrudan öğrenciden isteniyordu. Oysa öğrenciyi partner
 * getiriyor ve müşteri ilişkisi onda. Operasyon eksiği PARTNERDEN ister;
 * partner de kendi öğrencisinden. Zincirin her halkası kendi muhatabıyla
 * konuşur.
 *
 * İki yüz, tek controller:
 *   outgoing  operasyon firmasının gönderdikleri
 *   incoming  partnere gelenler — cevaplanır ya da öğrenciye iletilir
 *
 * ⚠ GÖRÜNÜRLÜK. `PartnerInfoRequest` firma kapsamı kullanmıyor: kaydın iki
 * tarafı var ve tek firmalı global kapsam taraflardan birini her zaman kör
 * ederdi. Sınır burada, açıkça çiziliyor — her sorgu ya company_id ya
 * partner_company_id üzerinden.
 */
class PartnerInfoRequestController extends Controller
{
    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    // ─── OPERASYON: gönderilen talepler ─────────────────────────────────────

    public function outgoing(): View
    {
        $cid = $this->companyId();

        $rows = PartnerInfoRequest::query()
            ->where('company_id', $cid)
            ->with('items')
            ->latest('id')
            ->paginate(50);

        return view('manager.partner-requests.outgoing', [
            'rows'     => $rows,
            'partners' => $this->partnerOptions($cid),
        ]);
    }

    public function create(Request $request): View
    {
        $cid       = $this->companyId();
        $partners  = $this->partnerOptions($cid);
        $partnerId = (int) $request->query('partner_company_id', 0);

        // Firma seçilmeden kişi listesi anlamsız — iki adımlı, JS'siz akış.
        $people = $partnerId > 0 && $partners->has($partnerId)
            ? $this->peopleOf($partnerId)
            : collect();

        return view('manager.partner-requests.create', [
            'partners'   => $partners,
            'partnerId'  => $partnerId,
            'people'     => $people,
            'categories' => $this->catalogue(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cid = $this->companyId();

        $data = $request->validate([
            'partner_company_id' => ['required', 'integer'],
            'subject'            => ['required', 'string', 'max:120'],  // "guest:12" | "student:STU-1"
            'category_codes'     => ['nullable', 'array'],
            'category_codes.*'   => ['string', 'max:64'],
            'info_items'         => ['nullable', 'string', 'max:2000'],
            'note'               => ['nullable', 'string', 'max:1000'],
            'due_at'             => ['nullable', 'date'],
        ]);

        $partnerId = (int) $data['partner_company_id'];

        // Yalnızca kendi alt firmalarına talep açılabilir.
        abort_unless($this->partnerOptions($cid)->has($partnerId), 404);

        [$subjectType, $subjectId] = array_pad(explode(':', $data['subject'], 2), 2, '');

        $people = $this->peopleOf($partnerId);
        $key    = $subjectType . ':' . $subjectId;

        // Kişi gerçekten o partnere ait mi?
        abort_unless($people->has($key), 404);

        $items = $this->buildItems(
            $data['category_codes'] ?? [],
            (string) ($data['info_items'] ?? '')
        );

        if ($items === []) {
            return back()->withInput()->withErrors([
                'category_codes' => 'En az bir belge veya bilgi kalemi seçin.',
            ]);
        }

        $infoRequest = PartnerInfoRequest::create([
            'company_id'         => $cid,
            'partner_company_id' => $partnerId,
            'subject_type'       => $subjectType,
            'subject_id'         => $subjectId,
            'subject_name'       => $people->get($key),
            'note'               => $data['note'] ?? null,
            'due_at'             => $data['due_at'] ?? null,
            'status'             => PartnerInfoRequest::STATUS_OPEN,
            'created_by'         => (string) ($request->user()?->email ?? ''),
        ]);

        $infoRequest->items()->createMany($items);

        return redirect('/manager/partner-requests')
            ->with('status', 'Talep partner firmaya iletildi.');
    }

    // ─── PARTNER: gelen talepler ────────────────────────────────────────────

    public function incoming(): View
    {
        $cid = $this->companyId();

        $rows = PartnerInfoRequest::query()
            ->incomingFor($cid)
            ->with('items')
            ->orderByRaw("CASE status WHEN 'open' THEN 0 ELSE 1 END")
            ->latest('id')
            ->paginate(50);

        return view('manager.partner-requests.incoming', ['rows' => $rows]);
    }

    public function show(int $id): View
    {
        $cid = $this->companyId();

        $infoRequest = PartnerInfoRequest::query()
            ->visibleTo($cid)
            ->with('items')
            ->findOrFail($id);

        return view('manager.partner-requests.show', [
            'req'       => $infoRequest,
            'isPartner' => (int) $infoRequest->partner_company_id === $cid,
        ]);
    }

    /** Bilgi kalemini cevapla ya da belgeyi "sağlandı" işaretle. */
    public function respond(Request $request, int $id, int $itemId): RedirectResponse
    {
        $item = $this->ownItem($id, $itemId);

        $data = $request->validate([
            'response_text' => ['nullable', 'string', 'max:2000'],
        ]);

        $item->update([
            'status'        => PartnerInfoRequestItem::STATUS_PROVIDED,
            'response_text' => $data['response_text'] ?? null,
            'provided_by'   => (string) ($request->user()?->email ?? ''),
            'provided_at'   => now(),
        ]);

        $item->request->refreshStatus();

        return back()->with('status', 'Kalem yanıtlandı.');
    }

    /**
     * Zincirin son halkası: partner kalemi kendi öğrencisinden ister.
     *
     * Mevcut yükleme jetonu altyapısı kullanılıyor — öğrenciye giden link,
     * hatırlatma ve yükleme akışı zaten orada çalışıyor.
     */
    public function forward(Request $request, int $id, int $itemId): RedirectResponse
    {
        $item = $this->ownItem($id, $itemId);
        $req  = $item->request;

        abort_unless($item->kind === PartnerInfoRequestItem::KIND_DOCUMENT, 422);

        $isGuest = $req->subject_type === PartnerInfoRequest::SUBJECT_GUEST;

        $recipientEmail = $isGuest
            ? (string) (GuestApplication::withoutGlobalScope('company')
                ->whereKey($req->subject_id)->value('email') ?? '')
            : '';

        $token = DocumentUploadToken::create([
            'company_id'          => (int) $req->partner_company_id,
            'token'               => DocumentUploadToken::generateToken(),
            'target_type'         => $isGuest ? DocumentUploadToken::TARGET_GUEST : DocumentUploadToken::TARGET_STUDENT,
            'target_id'           => (string) $req->subject_id,
            'target_display_name' => (string) $req->subject_name,
            'category_code'       => $item->category_code,
            'category_name'       => $item->label,
            'recipient_email'     => $recipientEmail ?: null,
            'created_by_user_id'  => $request->user()?->id,
            'max_uses'            => 1,
            'used_count'          => 0,
            'expires_at'          => now()->addDays(7),
        ]);

        $item->update([
            'forwarded_token_id' => $token->id,
            'forwarded_at'       => now(),
        ]);

        return back()->with('status', 'Öğrenciye belge talebi gönderildi.');
    }

    // ─── Yardımcılar ────────────────────────────────────────────────────────

    /**
     * Kalem gerçekten bu firmaya gelen bir talebe mi ait?
     *
     * Cevaplama ve iletme yalnızca TALEP EDİLEN tarafa açık; talebi açan
     * firma kendi talebini kendi kapatamaz.
     */
    private function ownItem(int $id, int $itemId): PartnerInfoRequestItem
    {
        $cid = $this->companyId();

        $req = PartnerInfoRequest::query()->incomingFor($cid)->findOrFail($id);

        return $req->items()->findOrFail($itemId);
    }

    /**
     * Talep açılabilecek alt firmalar: id => ad.
     *
     * @return Collection<int,string>
     */
    private function partnerOptions(int $companyId): Collection
    {
        if ($companyId <= 0) {
            return collect();
        }

        $ids = Company::descendantIds($companyId);

        return Company::query()
            ->withoutGlobalScope('company')
            ->whereIn('id', $ids)
            ->where('id', '!=', $companyId)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Bir partnerin adayları ve öğrencileri: "guest:12" => "Ad Soyad".
     *
     * ⚠ Kapsamsız okunuyor: operasyon kendi bağlamında çalışırken partnerin
     * kişilerini firma kapsamlı sorguyla göremezdi.
     *
     * @return Collection<string,string>
     */
    private function peopleOf(int $partnerCompanyId): Collection
    {
        // ⚠ collect(): Eloquent koleksiyonunun merge'i model bekler, bizim
        // değerlerimiz metin. Sarmalamazsak "getKey() on string" ile patlar —
        // ve taraflardan biri boşken hata görünmez, sinsi bir tuzak.
        $guests = collect(GuestApplication::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $partnerCompanyId)
            ->orderByDesc('id')
            ->limit(500)
            ->get(['id', 'first_name', 'last_name']))
            ->mapWithKeys(fn ($g) => [
                PartnerInfoRequest::SUBJECT_GUEST . ':' . $g->id =>
                    (trim($g->first_name . ' ' . $g->last_name) ?: ('Aday #' . $g->id)) . ' (aday)',
            ]);

        $students = collect(StudentAssignment::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $partnerCompanyId)
            ->orderByDesc('id')
            ->limit(500)
            ->get(['student_id', 'display_name']))
            ->mapWithKeys(fn ($s) => [
                PartnerInfoRequest::SUBJECT_STUDENT . ':' . $s->student_id =>
                    ((string) ($s->display_name ?: $s->student_id)) . ' (öğrenci)',
            ]);

        return $students->merge($guests);
    }

    /**
     * İstenebilecek belgelerin tamamı, üst kategoriye göre gruplu.
     *
     * "En geniş perspektif" burası: katalog ortak, firmaya bağlı değil.
     *
     * @return Collection<string,Collection>
     */
    private function catalogue(): Collection
    {
        return DocumentCategory::query()
            ->where('is_active', true)
            ->orderBy('top_category_code')
            ->orderBy('sort_order')
            ->get(['code', 'name_tr', 'top_category_code'])
            ->groupBy(fn ($c) => DocumentCategory::TOP_CATEGORIES[$c->top_category_code]
                ?? ($c->top_category_code ?: 'Diğer'));
    }

    /**
     * Seçilen belgeler + satır satır yazılan bilgi soruları → kalem listesi.
     *
     * @param  list<string> $codes
     * @return list<array<string,string>>
     */
    private function buildItems(array $codes, string $infoText): array
    {
        $items = [];

        if ($codes !== []) {
            $names = DocumentCategory::query()
                ->whereIn('code', $codes)
                ->pluck('name_tr', 'code');

            foreach ($codes as $code) {
                if (!$names->has($code)) {
                    continue; // katalogda yoksa sessizce atla
                }

                $items[] = [
                    'kind'          => PartnerInfoRequestItem::KIND_DOCUMENT,
                    'category_code' => $code,
                    'label'         => (string) $names[$code],
                    'status'        => PartnerInfoRequestItem::STATUS_PENDING,
                ];
            }
        }

        foreach (preg_split('/\r\n|\r|\n/', $infoText) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $items[] = [
                'kind'   => PartnerInfoRequestItem::KIND_INFO,
                'label'  => mb_substr($line, 0, 180),
                'status' => PartnerInfoRequestItem::STATUS_PENDING,
            ];
        }

        return $items;
    }
}

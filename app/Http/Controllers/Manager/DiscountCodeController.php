<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\DiscountCodeRedemption;
use App\Services\EventLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Manager — indirim kodu CRUD + kullanım listesi.
 *
 * MVP sadece: kod, % veya sabit EUR, expiry, max use, max use/kişi, aktif/pasif.
 * Future kolonları (paket-spesifik, dealer attribution, min tutar) DB'de var
 * ama bu controller'ın UI'sında görünmez — sonraki sprint açılır.
 */
class DiscountCodeController extends Controller
{
    public function __construct(
        private readonly EventLogService $eventLogService,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all'); // all|active|inactive

        $query = DiscountCode::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%");
            });
        }
        if ($status === 'active')   $query->where('is_active', true);
        if ($status === 'inactive') $query->where('is_active', false);

        return view('manager.discount-codes.index', [
            'codes'  => $query->paginate(30)->appends($request->query()),
            'q'      => $q,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('manager.discount-codes.form', [
            'mode' => 'create',
            'code' => new DiscountCode(['discount_type' => 'percent', 'max_per_user' => 1, 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $companyId = (int) (Auth::user()?->company_id ?? 0);

        $exists = DiscountCode::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(code) = ?', [strtoupper($data['code'])])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => 'Bu kod zaten mevcut.']);
        }

        $code = DiscountCode::create(array_merge($data, [
            'company_id' => $companyId,
            'created_by' => Auth::id(),
        ]));

        $this->eventLogService->log(
            eventType: 'discount_code_created',
            entityType: 'discount_code',
            entityId: (string) $code->id,
            message: 'Manager #' . (Auth::id() ?? '?') . ' indirim kodu oluşturdu: ' . $code->code,
            meta: ['type' => $code->discount_type, 'value' => (float) $code->discount_value],
        );

        return redirect()->route('manager.discount-codes.index')->with('success', 'Kod oluşturuldu.');
    }

    public function edit(DiscountCode $discountCode): View
    {
        return view('manager.discount-codes.form', [
            'mode' => 'edit',
            'code' => $discountCode,
        ]);
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $data = $this->validatePayload($request, $discountCode);

        // Kod değişimine izin ver ama aynı şirkette unique kalsın
        $exists = DiscountCode::query()
            ->where('company_id', $discountCode->company_id)
            ->where('id', '!=', $discountCode->id)
            ->whereRaw('UPPER(code) = ?', [strtoupper($data['code'])])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => 'Bu kod başka bir kayıtta var.']);
        }

        $discountCode->update($data);

        return redirect()->route('manager.discount-codes.index')->with('success', 'Kod güncellendi.');
    }

    public function toggleActive(DiscountCode $discountCode): RedirectResponse
    {
        $discountCode->update(['is_active' => ! $discountCode->is_active]);
        return back()->with('success', $discountCode->is_active ? 'Kod aktif edildi.' : 'Kod pasif edildi.');
    }

    public function redemptions(Request $request): View
    {
        $codeId = $request->query('code_id');
        $query = DiscountCodeRedemption::query()
            ->with(['discountCode', 'guestApplication'])
            ->orderByDesc('redeemed_at');

        if ($codeId) {
            $query->where('discount_code_id', (int) $codeId);
        }

        return view('manager.discount-codes.redemptions', [
            'redemptions' => $query->paginate(50)->appends($request->query()),
            'filteredCode' => $codeId ? DiscountCode::find($codeId) : null,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validatePayload(Request $request, ?DiscountCode $existing = null): array
    {
        $data = $request->validate([
            'code'             => 'required|string|min:3|max:64|regex:/^[A-Za-z0-9_\-]+$/',
            'description'      => 'nullable|string|max:255',
            'discount_type'    => 'required|in:percent,fixed',
            'discount_value'   => 'required|numeric|min:0',
            'max_redemptions'  => 'nullable|integer|min:1',
            'max_per_user'     => 'required|integer|min:1|max:100',
            'valid_from'       => 'nullable|date',
            'valid_until'      => 'nullable|date|after_or_equal:valid_from',
            'is_active'        => 'sometimes|boolean',
        ]);

        // Yüzde sınırı
        if ($data['discount_type'] === 'percent' && (float) $data['discount_value'] > 100) {
            abort(422, 'Yüzde indirim 100\'den büyük olamaz.');
        }

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}

<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BusinessContract;
use App\Models\BusinessContractTemplate;
use App\Models\Dealer;
use App\Support\FileUploadRules;
use App\Models\User;
use App\Services\BusinessContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BusinessContractController extends Controller
{
    public function __construct(
        private readonly BusinessContractService $service
    ) {}

    public function index(Request $r): View
    {
        $companyId = (int) session('company_id', 0);

        $query = BusinessContract::query()
            ->with(['dealer:id,name,code', 'issuedByUser:id,name'])
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when($r->filled('type'), fn ($q) => $q->where('contract_type', $r->type))
            ->when($r->filled('status'), fn ($q) => $q->where('status', $r->status))
            ->when($r->filled('dealer_id'), fn ($q) => $q->where('dealer_id', $r->dealer_id))
            ->orderByDesc('created_at');

        $contracts = $query->paginate(25)->withQueryString();

        $dealers = Dealer::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $templates = BusinessContractTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'template_code', 'contract_type']);

        return view('manager.business-contracts.index', compact('contracts', 'dealers', 'templates'));
    }

    public function create(Request $r): View
    {
        $companyId = (int) session('company_id', 0);

        $dealers = Dealer::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'phone', 'email', 'dealer_type_code']);

        // İş sözleşmeleri için 3 bayi kategorisi:
        //  • 📣 Lead Generation      → dealer_referral_v1 template
        //  • 🎯 Freelance Danışmanlık → dealer_referral_v1 template
        //  • 🏢 Operasyon            → dealer_operations_v1 template
        // Lead Generation + Freelance Danışmanlık aynı "referans ortaklığı"
        // hukuki şablonunu kullanır; kategori adları sistemde ayrık durur.
        $dealerCategoryLabels = [
            'lead_generation' => [
                'label'    => '📣 Lead Generation',
                'codes'    => ['lead_generation', 'referrer'],
                'template' => 'dealer_referral_v1',
            ],
            'freelance'       => [
                'label'    => '🎯 Freelance Danışmanlık',
                'codes'    => ['freelance_danisman'],
                'template' => 'dealer_referral_v1',
            ],
            'operational'     => [
                'label'    => '🏢 Operasyon',
                'codes'    => ['operational', 'b2b_partner'],
                'template' => 'dealer_operations_v1',
            ],
        ];

        // Kategoriye göre grupla — UI `<optgroup>` için
        $dealersByCategory = [];
        foreach ($dealerCategoryLabels as $key => $cat) {
            $dealersByCategory[$key] = [
                'label'   => $cat['label'],
                'dealers' => $dealers->filter(fn ($d) => in_array($d->dealer_type_code, $cat['codes'], true))->values(),
            ];
        }
        // Kategorisiz bayiler (eski kayıtlar) — "Diğer" altında göster
        $uncategorized = $dealers->filter(function ($d) use ($dealerCategoryLabels) {
            foreach ($dealerCategoryLabels as $cat) {
                if (in_array($d->dealer_type_code, $cat['codes'], true)) return false;
            }
            return true;
        })->values();
        if ($uncategorized->isNotEmpty()) {
            $dealersByCategory['other'] = [
                'label'   => '📦 Diğer / Kategorisiz',
                'dealers' => $uncategorized,
            ];
        }

        $templates = BusinessContractTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'template_code', 'contract_type', 'body_text', 'notes']);

        $selectedDealer = null;
        if ($r->filled('dealer_id')) {
            $selectedDealer = $dealers->firstWhere('id', (int) $r->dealer_id);
        }

        $staffRoles = [
            'manager', 'senior', 'mentor', 'marketing_admin', 'marketing_staff',
            'finance_admin', 'finance_staff', 'operations_admin', 'operations_staff',
            'system_admin', 'system_staff', 'sales_admin', 'sales_staff',
        ];

        $users = User::query()
            ->whereIn('role', $staffRoles)
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('manager.business-contracts.create', compact('dealers', 'dealersByCategory', 'templates', 'selectedDealer', 'users'));
    }

    public function store(Request $r): RedirectResponse
    {
        $r->validate([
            'contract_type'      => 'required|in:dealer,staff',
            'template_id'        => 'required|integer|exists:business_contract_templates,id',
            'dealer_id'          => 'nullable|integer|exists:dealers,id',
            'user_id'            => 'nullable|integer|exists:users,id',
            'meta'               => 'nullable|array',
            'meta.*'             => 'nullable|string|max:500',
            'body_text_override' => 'nullable|string',
            'notes'              => 'nullable|string|max:2000',
        ]);

        $companyId = (int) session('company_id', 0);
        $meta      = array_map('strval', (array) $r->input('meta', []));

        $contract = $this->service->create(
            contractType: $r->contract_type,
            templateId: (int) $r->template_id,
            dealerId: (int) ($r->dealer_id ?? 0),
            userId: (int) ($r->user_id ?? 0),
            companyId: $companyId,
            issuedBy: (int) Auth::id(),
            meta: $meta,
            notes: (string) ($r->notes ?? ''),
            bodyTextOverride: (string) ($r->body_text_override ?? ''),
        );

        return redirect()->route('manager.business-contracts.show', $contract)
            ->with('success', 'Sözleşme oluşturuldu.');
    }

    private function authorizeContract(BusinessContract $businessContract): void
    {
        $cid = (int) session('company_id', 0);
        abort_if($cid > 0 && (int) ($businessContract->company_id ?? 0) !== $cid, 403);
    }

    public function show(BusinessContract $businessContract): View
    {
        $this->authorizeContract($businessContract);
        $businessContract->load(['dealer:id,name,code,dealer_type_code', 'staffUser:id,name,email', 'issuedByUser:id,name', 'approvedByUser:id,name']);

        return view('manager.business-contracts.show', [
            'contract'             => $businessContract,
            'dealerCategoryLabel'  => $businessContract->dealer
                ? self::dealerCategoryLabel((string) $businessContract->dealer->dealer_type_code)
                : null,
        ]);
    }

    /**
     * Sözleşme sistemindeki 3 bayi kategorisinden birine map'ler.
     * Lead Generation + Freelance aynı hukuki şablonu kullansa da
     * kategori etiketleri ayrıdır (iş modeli farkı için).
     */
    public static function dealerCategoryLabel(?string $typeCode): ?string
    {
        return match ($typeCode) {
            'lead_generation', 'referrer' => '📣 Lead Generation Bayi',
            'freelance_danisman'          => '🎯 Freelance Danışmanlık Bayi',
            'operational', 'b2b_partner'  => '🏢 Operasyon Bayi',
            default                       => null,
        };
    }

    /**
     * Bayi tipine göre önerilen sözleşme template_code'u.
     * Seçilen bayiden template otomatik önerilir.
     */
    public static function suggestedTemplateCode(?string $typeCode): ?string
    {
        return match ($typeCode) {
            'lead_generation', 'referrer', 'freelance_danisman' => 'dealer_referral_v1',
            'operational', 'b2b_partner'                        => 'dealer_operations_v1',
            default                                             => null,
        };
    }

    public function updateBody(Request $r, BusinessContract $businessContract): RedirectResponse
    {
        $this->authorizeContract($businessContract);
        if ($businessContract->status !== 'draft') {
            return back()->with('error', 'Yalnızca taslak sözleşmelerin içeriği düzenlenebilir.');
        }

        $r->validate(['body_text' => 'required|string']);

        $businessContract->update(['body_text' => $r->body_text]);

        return back()->with('success', 'Sözleşme içeriği güncellendi.');
    }

    public function issue(BusinessContract $businessContract): RedirectResponse
    {
        $this->authorizeContract($businessContract);
        if ($businessContract->status !== 'draft') {
            return back()->with('error', 'Yalnızca taslak sözleşmeler gönderilebilir.');
        }

        $this->service->issue($businessContract);

        return back()->with('success', 'Sözleşme dealer\'a gönderildi.');
    }

    public function uploadSigned(Request $r, BusinessContract $businessContract): RedirectResponse
    {
        $this->authorizeContract($businessContract);
        $r->validate([
            'signed_file' => FileUploadRules::signedContract(),
        ]);

        $this->service->uploadSigned($businessContract, $r->file('signed_file'));

        return back()->with('success', 'İmzalı sözleşme yüklendi.');
    }

    public function approve(BusinessContract $businessContract): RedirectResponse
    {
        $this->authorizeContract($businessContract);
        if ($businessContract->status !== 'signed_uploaded') {
            return back()->with('error', 'Yalnızca imzalı yüklenen sözleşmeler onaylanabilir.');
        }

        $this->service->approve($businessContract, (int) Auth::id());

        return back()->with('success', 'Sözleşme onaylandı.');
    }

    public function cancel(BusinessContract $businessContract): RedirectResponse
    {
        $this->authorizeContract($businessContract);
        $this->service->cancel($businessContract);

        return back()->with('success', 'Sözleşme iptal edildi.');
    }

    public function downloadSigned(BusinessContract $businessContract)
    {
        $this->authorizeContract($businessContract);
        if (!$businessContract->signed_file_path) {
            abort(404);
        }

        return response()->download(storage_path('app/' . $businessContract->signed_file_path));
    }
}

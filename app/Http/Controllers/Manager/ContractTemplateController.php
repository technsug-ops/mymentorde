<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ContractTemplate;
use App\Models\GuestApplication;
use App\Models\MarketingAdminSetting;
use App\Models\SystemEventLog;
use App\Models\User;
use App\Services\ContractTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{
    public function __construct(
        private readonly ContractTemplateService $contractTemplateService,
    ) {}

    public function show(Request $request)
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $active = $this->contractTemplateService->resolveActiveTemplate($companyId);
        $company = Company::query()
            ->when($companyId > 0, fn ($q) => $q->where('id', $companyId))
            ->where('is_active', true)
            ->first(['id', 'name', 'code']);
        $rows = ContractTemplate::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->orderByDesc('is_active')
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'code', 'name', 'version', 'is_active', 'updated_at']);

        $query        = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', 'active_contracts'));

        $studentBaseScoped = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->select([
                'id', 'tracking_token', 'converted_to_student', 'converted_student_id',
                'first_name', 'last_name', 'email', 'application_country', 'application_type',
                'selected_package_title', 'selected_package_price', 'selected_extra_services',
                'contract_status', 'contract_requested_at',
            ]);

        $requestedCount       = (clone $studentBaseScoped)->where('contract_status', 'requested')->count();
        $signedUploadedCount  = (clone $studentBaseScoped)->where('contract_status', 'signed_uploaded')->count();
        $pendingManagerCount  = (clone $studentBaseScoped)->where('contract_status', 'pending_manager')->count();
        $reopenRequestedCount = (clone $studentBaseScoped)->where('contract_status', 'reopen_requested')->count();
        $totalConvertedCount  = (clone $studentBaseScoped)->where('converted_to_student', true)->count();

        $studentBase = $studentBaseScoped;

        if ($query !== '') {
            $students = (clone $studentBase)
                ->where(function ($q) use ($query): void {
                    $q->where('converted_student_id', 'like', "%{$query}%")
                        ->orWhere('tracking_token', 'like', "%{$query}%")
                        ->orWhereRaw('lower(email) like ?', ['%' . strtolower($query) . '%'])
                        ->orWhereRaw('lower(first_name) like ?', ['%' . strtolower($query) . '%'])
                        ->orWhereRaw('lower(last_name) like ?', ['%' . strtolower($query) . '%']);
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        } else {
            $listQuery = (clone $studentBase);
            match ($statusFilter) {
                'pending_manager'  => $listQuery->where('contract_status', 'pending_manager'),
                'requested'        => $listQuery->where('contract_status', 'requested'),
                'signed_uploaded'  => $listQuery->where('contract_status', 'signed_uploaded'),
                'reopen_requested' => $listQuery->where('contract_status', 'reopen_requested'),
                'cancelled'        => $listQuery->where('contract_status', 'cancelled'),
                'approved'         => $listQuery->where('contract_status', 'approved'),
                'all'              => null,
                default            => $listQuery->whereIn('contract_status', [
                    'pending_manager', 'requested', 'signed_uploaded', 'reopen_requested',
                ]),
            };
            $students = $listQuery
                ->orderByDesc('contract_requested_at')
                ->orderByDesc('id')
                ->limit(30)
                ->get();
        }

        $selectedGuestId = (int) $request->query('guest_id', 0);
        if ($selectedGuestId <= 0 && $students->isNotEmpty()) {
            $selectedGuestId = (int) ($students->first()->id ?? 0);
        }

        $selectedGuest   = null;
        $contractPreview = null;
        $previewVariables = [];
        $advisorUser     = null;
        $contractEvents  = collect();

        if ($selectedGuestId > 0) {
            $selectedGuest = GuestApplication::query()
                ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
                ->where('id', $selectedGuestId)
                ->first();
            if ($selectedGuest) {
                $contractPreview  = $this->contractTemplateService->buildSnapshotCached($selectedGuest, $companyId);
                $previewVariables = $this->contractTemplateService->buildPreviewVariables($selectedGuest);
                $advisorEmail     = (string) ($selectedGuest->assigned_senior_email ?? '');
                if ($advisorEmail !== '') {
                    $advisorUser = User::query()
                        ->where('email', strtolower($advisorEmail))
                        ->first(['id', 'name', 'email', 'role']);
                }
                $contractEvents = SystemEventLog::query()
                    ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                    ->where('entity_type', 'guest_application')
                    ->where('entity_id', (string) $selectedGuest->id)
                    ->where(function ($q): void {
                        $q->where('event_type', 'like', 'guest_contract_%')
                            ->orWhere('event_type', 'manager_contract_%')
                            ->orWhere('event_type', 'student_contract_%');
                    })
                    ->orderByDesc('id')
                    ->limit(15)
                    ->get(['event_type', 'message', 'actor_email', 'created_at', 'meta']);
            }
        }

        return view('manager.contract-template', [
            'template'             => $active,
            'company'              => $company,
            'companySettings'      => $this->loadCompanyContractSettings($companyId),
            'templates'            => $rows,
            'searchQuery'          => $query,
            'statusFilter'         => $statusFilter,
            'pendingManagerCount'  => $pendingManagerCount,
            'requestedCount'       => $requestedCount,
            'signedUploadedCount'  => $signedUploadedCount,
            'reopenRequestedCount' => $reopenRequestedCount,
            'totalConvertedCount'  => $totalConvertedCount,
            'students'             => $students,
            'selectedGuest'        => $selectedGuest,
            'contractPreview'      => $contractPreview,
            'previewVariables'     => $previewVariables,
            'advisorUser'          => $advisorUser,
            'contractEvents'       => $contractEvents,
            'cancelReasons'        => config('contract_cancel_reasons', []),
            'placeholders' => [
                'contract_number', 'contract_date', 'advisor_company_name', 'advisor_company_address',
                'advisor_tax_info', 'advisor_authorized_person', 'advisor_phone', 'advisor_email',
                'advisor_website', 'student_full_name', 'student_id', 'student_email', 'student_phone',
                'student_identity_no', 'student_birth_date', 'student_address', 'guardian_full_name',
                'guardian_identity_no', 'guardian_relation', 'application_country', 'application_type',
                'education_level', 'package_name', 'service_total_price', 'service_scope', 'extra_services',
                'max_university_count', 'tax_status', 'payment_plan', 'installment_1_amount',
                'installment_2_date_or_condition', 'installment_2_amount', 'installment_3_date_or_condition',
                'installment_3_amount', 'bank_name', 'bank_branch', 'bank_iban', 'jurisdiction_city',
            ],
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $active = $this->contractTemplateService->resolveActiveTemplate($companyId);

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:180'],
            'body_text'             => ['required', 'string', 'min:100'],
            'annex_kvkk_text'       => ['nullable', 'string'],
            'annex_commitment_text' => ['nullable', 'string'],
            'annex_payment_text'    => ['nullable', 'string'],
            'print_header_html'     => ['nullable', 'string'],
            'print_footer_html'     => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string', 'max:2000'],
            'new_version'           => ['nullable', 'boolean'],
            'change_log'            => ['nullable', 'string', 'max:1000'],
        ]);

        $makeNew = (bool) ($data['new_version'] ?? false);
        if ($makeNew) {
            $parentId = (int) $active->id;
            ContractTemplate::query()
                ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
                ->update(['is_active' => false]);
            ContractTemplate::query()->create([
                'company_id'            => $companyId > 0 ? $companyId : (int) ($active->company_id ?: 1),
                'code'                  => (string) $active->code,
                'name'                  => (string) $data['name'],
                'version'               => (int) $active->version + 1,
                'parent_version_id'     => $parentId,
                'change_log'            => trim((string) ($data['change_log'] ?? '')),
                'is_active'             => true,
                'body_text'             => (string) $data['body_text'],
                'annex_kvkk_text'       => (string) ($data['annex_kvkk_text'] ?? ''),
                'annex_commitment_text' => (string) ($data['annex_commitment_text'] ?? ''),
                'annex_payment_text'    => (string) ($data['annex_payment_text'] ?? ''),
                'print_header_html'     => (string) ($data['print_header_html'] ?? ''),
                'print_footer_html'     => (string) ($data['print_footer_html'] ?? ''),
                'notes'                 => (string) ($data['notes'] ?? ''),
            ]);
        } else {
            $active->update([
                'name'                  => (string) $data['name'],
                'body_text'             => (string) $data['body_text'],
                'annex_kvkk_text'       => (string) ($data['annex_kvkk_text'] ?? ''),
                'annex_commitment_text' => (string) ($data['annex_commitment_text'] ?? ''),
                'annex_payment_text'    => (string) ($data['annex_payment_text'] ?? ''),
                'print_header_html'     => (string) ($data['print_header_html'] ?? ''),
                'print_footer_html'     => (string) ($data['print_footer_html'] ?? ''),
                'notes'                 => (string) ($data['notes'] ?? ''),
            ]);
        }

        return redirect()->route('manager.contract-template.show')->with('status', 'Sozlesme template kaydedildi.');
    }

    public function saveCompanySettings(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $company = Company::query()
            ->when($companyId > 0, fn ($q) => $q->where('id', $companyId))
            ->where('is_active', true)
            ->firstOrFail();

        $data = $request->validate([
            'company_name'              => ['required', 'string', 'max:180'],
            'company_code'              => ['required', 'string', 'max:64'],
            'advisor_company_address'   => ['nullable', 'string', 'max:255'],
            'advisor_tax_info'          => ['nullable', 'string', 'max:255'],
            'advisor_authorized_person' => ['nullable', 'string', 'max:180'],
            'advisor_phone'             => ['nullable', 'string', 'max:64'],
            'advisor_email'             => ['nullable', 'email', 'max:180'],
            'advisor_website'           => ['nullable', 'string', 'max:255'],
            'jurisdiction_city'         => ['nullable', 'string', 'max:120'],
            'tax_status'                => ['nullable', 'string', 'max:32'],
            'payment_plan'              => ['nullable', 'string', 'max:500'],
            'installment_1_amount'      => ['nullable', 'string', 'max:64'],
            'installment_2_condition'   => ['nullable', 'string', 'max:255'],
            'installment_2_amount'      => ['nullable', 'string', 'max:64'],
            'installment_3_condition'   => ['nullable', 'string', 'max:255'],
            'installment_3_amount'      => ['nullable', 'string', 'max:64'],
            'bank_name'                 => ['nullable', 'string', 'max:120'],
            'bank_branch'               => ['nullable', 'string', 'max:120'],
            'bank_iban'                 => ['nullable', 'string', 'max:64'],
            'max_university_count'      => ['nullable', 'string', 'max:8'],
        ]);

        $company->name = trim((string) $data['company_name']);
        $company->code = strtolower(trim((string) $data['company_code']));
        $company->save();

        $settingMap = [
            'advisor_company_address'   => trim((string) ($data['advisor_company_address'] ?? '')),
            'advisor_tax_info'          => trim((string) ($data['advisor_tax_info'] ?? '')),
            'advisor_authorized_person' => trim((string) ($data['advisor_authorized_person'] ?? '')),
            'advisor_phone'             => trim((string) ($data['advisor_phone'] ?? '')),
            'advisor_email'             => trim((string) ($data['advisor_email'] ?? '')),
            'advisor_website'           => trim((string) ($data['advisor_website'] ?? '')),
            'jurisdiction_city'         => trim((string) ($data['jurisdiction_city'] ?? '')),
            'tax_status'                => trim((string) ($data['tax_status'] ?? '')),
            'payment_plan'              => trim((string) ($data['payment_plan'] ?? '')),
            'installment_1_amount'      => trim((string) ($data['installment_1_amount'] ?? '')),
            'installment_2_condition'   => trim((string) ($data['installment_2_condition'] ?? '')),
            'installment_2_amount'      => trim((string) ($data['installment_2_amount'] ?? '')),
            'installment_3_condition'   => trim((string) ($data['installment_3_condition'] ?? '')),
            'installment_3_amount'      => trim((string) ($data['installment_3_amount'] ?? '')),
            'bank_name'                 => trim((string) ($data['bank_name'] ?? '')),
            'bank_branch'               => trim((string) ($data['bank_branch'] ?? '')),
            'bank_iban'                 => trim((string) ($data['bank_iban'] ?? '')),
            'max_university_count'      => trim((string) ($data['max_university_count'] ?? '')),
        ];

        foreach ($settingMap as $key => $value) {
            MarketingAdminSetting::query()->updateOrCreate(
                ['company_id' => (int) $company->id, 'setting_key' => $key],
                [
                    'setting_value'      => ['value' => $value],
                    'updated_by_user_id' => (int) (optional($request->user())->id ?? 0),
                ]
            );
        }

        return redirect()->route('manager.contract-template.show')
            ->with('status', 'Aktif firma sozlesme bilgileri kaydedildi.');
    }

    public function diff(Request $request): JsonResponse|\Illuminate\Contracts\View\View
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $guestId   = (int) $request->query('guest_id', 0);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', $guestId)
            ->first();

        if (! $guest) {
            return response()->json(['error' => 'Guest bulunamadı.'], 404);
        }

        $current = trim((string) ($guest->contract_snapshot_text ?? ''));
        $snapshots = \App\Models\GuestRegistrationSnapshot::query()
            ->where('guest_application_id', $guest->id)
            ->orderByDesc('snapshot_version')
            ->limit(5)
            ->get(['id', 'snapshot_version', 'submitted_at', 'meta_json']);

        $v1     = trim((string) $request->query('v1', ''));
        $v2     = trim((string) $request->query('v2', $current));
        $lines1 = $v1 !== '' ? explode("\n", $v1) : [];
        $lines2 = $v2 !== '' ? explode("\n", $v2) : [];

        $diff = [];
        $max  = max(count($lines1), count($lines2));
        for ($i = 0; $i < $max; $i++) {
            $l1 = $lines1[$i] ?? null;
            $l2 = $lines2[$i] ?? null;
            if ($l1 === $l2) {
                $diff[] = ['type' => 'same',    'text' => $l2 ?? ''];
            } elseif ($l1 === null) {
                $diff[] = ['type' => 'added',   'text' => $l2];
            } elseif ($l2 === null) {
                $diff[] = ['type' => 'removed', 'text' => $l1];
            } else {
                $diff[] = ['type' => 'removed', 'text' => $l1];
                $diff[] = ['type' => 'added',   'text' => $l2];
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['diff' => $diff, 'snapshots' => $snapshots]);
        }

        return view('manager.contract-diff', compact('guest', 'diff', 'snapshots', 'current'));
    }

    public function analytics(Request $request): \Illuminate\Contracts\View\View
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $base = GuestApplication::query()->when($companyId > 0, fn ($q) => $q->forCompany($companyId));

        $statusCounts = (clone $base)
            ->selectRaw('contract_status, COUNT(*) as cnt')
            ->whereNotNull('contract_status')
            ->groupBy('contract_status')
            ->pluck('cnt', 'contract_status')
            ->all();

        $avgApprovalDays = GuestApplication::query()
            ->where('contract_status', 'approved')
            ->whereNotNull('contract_signed_at')
            ->whereNotNull('contract_approved_at')
            ->selectRaw(config('database.default') === 'sqlite'
                ? 'AVG(CAST(julianday(contract_approved_at) - julianday(contract_signed_at) AS INTEGER)) as avg_days'
                : 'AVG(DATEDIFF(contract_approved_at, contract_signed_at)) as avg_days'
            )
            ->first()
            ?->avg_days ?? 0;

        $monthlyTrend = (clone $base)
            ->whereNotNull('contract_requested_at')
            ->where('contract_requested_at', '>=', now()->subMonths(6))
            ->selectRaw(config('database.default') === 'sqlite'
                ? "strftime('%Y-%m', contract_requested_at) as month, contract_status, COUNT(*) as cnt"
                : "DATE_FORMAT(contract_requested_at,'%Y-%m') as month, contract_status, COUNT(*) as cnt"
            )
            ->groupBy('month', 'contract_status')
            ->orderBy('month')
            ->get();

        $pendingDecision = (clone $base)
            ->where('contract_status', 'signed_uploaded')
            ->count();

        return view('manager.contract-analytics', compact(
            'statusCounts', 'avgApprovalDays', 'monthlyTrend', 'pendingDecision'
        ));
    }

    private function loadCompanyContractSettings(int $companyId): array
    {
        $settingKeys = [
            'advisor_company_address', 'advisor_tax_info', 'advisor_authorized_person',
            'advisor_phone', 'advisor_email', 'advisor_website', 'jurisdiction_city',
            'tax_status', 'payment_plan', 'installment_1_amount', 'installment_2_condition',
            'installment_2_amount', 'installment_3_condition', 'installment_3_amount',
            'bank_name', 'bank_branch', 'bank_iban', 'max_university_count',
        ];

        if ($companyId <= 0) {
            return array_fill_keys($settingKeys, '');
        }

        $rows = MarketingAdminSetting::query()
            ->forCompany($companyId)
            ->whereIn('setting_key', $settingKeys)
            ->get(['setting_key', 'setting_value']);

        $out = array_fill_keys($settingKeys, '');
        foreach ($rows as $row) {
            $raw   = $row->setting_value;
            $value = is_array($raw)
                ? (string) ($raw['value'] ?? $raw['text'] ?? $raw['tr'] ?? '')
                : (string) $raw;
            $out[(string) $row->setting_key] = trim($value);
        }

        return $out;
    }
}

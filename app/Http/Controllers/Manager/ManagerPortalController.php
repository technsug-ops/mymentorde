<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Concerns\GuestDocumentAccessTrait;
use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerPayoutRequest;
use App\Models\DealerStudentRevenue;
use App\Models\GuestApplication;
use App\Models\MarketingTrackingLink;
use App\Models\LeadSourceDatum;
use App\Models\StudentAccommodation;
use App\Models\StudentAssignment;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentUniversityApplication;
use App\Models\StudentVisaApplication;
use App\Models\SystemEventLog;
use App\Models\ProcessDefinition;
use App\Models\ProcessStepTask;
use App\Models\UniversityRequirementMap;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Services\EventLogService;
use App\Services\GuestListService;
use App\Services\SeniorPerformanceService;
use App\Services\StudentListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagerPortalController extends Controller
{
    use GuestDocumentAccessTrait;

    protected function authorizeGuestAccess(\App\Models\GuestApplication $guest): void
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $guest->company_id !== $cid, 403);
    }

    public function __construct(
        private readonly GuestListService   $guestList,
        private readonly StudentListService $studentList,
    ) {}

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    // ─── GUEST LİSTESİ ──────────────────────────────────────────────────────

    public function guests(Request $request): View
    {
        $cid       = $this->companyId();
        $q         = trim((string) $request->query('q', ''));
        $status    = trim((string) $request->query('status', ''));
        $senior    = trim((string) $request->query('senior', ''));
        $dealer    = trim((string) $request->query('dealer', ''));
        $converted = (string) $request->query('converted', '');

        $base = $this->guestList->filteredQuery($cid, compact('q', 'status', 'senior', 'dealer', 'converted'));
        $kpis = $this->guestList->kpis($base);

        $rows = $base->latest()
            ->paginate(50, ['id', 'tracking_token', 'first_name', 'last_name', 'email', 'phone',
                            'application_type', 'lead_status', 'assigned_senior_email', 'dealer_code',
                            'converted_to_student', 'converted_student_id', 'created_at']);

        ['seniorOptions' => $seniorOptions, 'statusOptions' => $statusOptions] = $this->guestList->filterOptions($cid);

        return view('manager.guests', compact(
            'rows', 'kpis', 'seniorOptions', 'statusOptions',
            'q', 'status', 'senior', 'dealer', 'converted'
        ));
    }

    // ─── GUEST CSV EXPORT ───────────────────────────────────────────────────

    public function guestsExportCsv(Request $request): StreamedResponse
    {
        $cid       = $this->companyId();
        $q         = trim((string) $request->query('q', ''));
        $status    = trim((string) $request->query('status', ''));
        $senior    = trim((string) $request->query('senior', ''));
        $dealer    = trim((string) $request->query('dealer', ''));
        $converted = (string) $request->query('converted', '');

        $filename = 'guests_' . now()->format('Ymd_His') . '.csv';

        // GDPR Madde 30 — toplu kişisel veri erişim denetim kaydı
        app(EventLogService::class)->log(
            'gdpr.bulk_export',
            'guest_application',
            null,
            'Manager guest CSV dışa aktardı.',
            ['filters' => compact('q', 'status', 'senior', 'dealer', 'converted'), 'ip' => $request->ip()],
            $request->user()?->email,
        );

        return response()->streamDownload(function () use ($cid, $q, $status, $senior, $dealer, $converted): void {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM — Excel'de Türkçe karakter sorunu önler
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'ID', 'Token', 'Ad', 'Soyad', 'E-posta', 'Telefon',
                'Tür', 'Durum', 'Senior', 'Dealer Kodu',
                'Dönüştü', 'Student ID', 'Tarih',
            ], ';');

            // cursor() ile bellek tasarrufu — tüm listeyi RAM'e çekmiyor
            $this->guestList
                ->filteredQuery($cid, compact('q', 'status', 'senior', 'dealer', 'converted'))
                ->latest()
                ->select(['id', 'tracking_token', 'first_name', 'last_name', 'email', 'phone',
                    'application_type', 'lead_status', 'assigned_senior_email', 'dealer_code',
                    'converted_to_student', 'converted_student_id', 'created_at'])
                ->cursor()
                ->each(function ($g) use ($out): void {
                    fputcsv($out, [
                        $g->id,
                        $g->tracking_token ?? '',
                        $g->first_name ?? '',
                        $g->last_name ?? '',
                        $g->email ?? '',
                        $g->phone ?? '',
                        $g->application_type ?? '',
                        $g->lead_status ?? '',
                        $g->assigned_senior_email ?? '',
                        $g->dealer_code ?? '',
                        $g->converted_to_student ? 'Evet' : 'Hayır',
                        $g->converted_student_id ?? '',
                        optional($g->created_at)->format('d.m.Y H:i') ?? '',
                    ], ';');
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ─── GUEST DETAY ────────────────────────────────────────────────────────

    public function guestShow(GuestApplication $guest): View
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $guest->company_id !== $cid, 403);

        // GDPR Madde 30 — kişisel veriye erişim denetim kaydı
        app(EventLogService::class)->log(
            'gdpr.pii_access',
            'guest_application',
            (string) $guest->id,
            'Manager guest başvuru detayını görüntüledi.',
            ['ip' => request()->ip()],
            request()->user()?->email,
        );

        $seniorOptions = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->distinct()->pluck('senior_email')->filter()->sort()->values();

        $student = ($guest->converted_to_student && $guest->converted_student_id)
            ? StudentAssignment::where('student_id', $guest->converted_student_id)->first()
            : null;

        return view('manager.guest-detail', compact('guest', 'seniorOptions', 'student'));
    }

    public function guestUpdateStatus(Request $request, GuestApplication $guest): RedirectResponse
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $guest->company_id !== $cid, 403);

        $data = [];
        if ($request->has('lead_status')) {
            $data['lead_status'] = $request->input('lead_status') ?: null;
        }
        if ($request->has('notes')) {
            $data['notes'] = trim((string) $request->input('notes', '')) ?: null;
        }
        if ($request->has('priority')) {
            $data['priority'] = $request->input('priority') ?: null;
        }
        if (!empty($data)) {
            $guest->update($data);
        }

        return back()->with('status', 'Başvuru güncellendi.');
    }

    public function guestAssignSenior(Request $request, GuestApplication $guest): RedirectResponse
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $guest->company_id !== $cid, 403);

        $email = trim((string) $request->input('assigned_senior_email', '')) ?: null;

        $guest->update([
            'assigned_senior_email' => $email,
            'assigned_at'           => $email ? now() : null,
            'assigned_by'           => $email ? $request->user()?->email : null,
        ]);

        return back()->with('status', $email ? 'Senior atandı: ' . $email : 'Senior ataması kaldırıldı.');
    }

    // ─── ÖĞRENCİ CSV EXPORT ─────────────────────────────────────────────────

    public function studentsExportCsv(Request $request): Response
    {
        $cid     = $this->companyId();
        $q       = trim((string) $request->query('q', ''));
        $senior  = trim((string) $request->query('senior', ''));
        $branch  = trim((string) $request->query('branch', ''));
        $risk    = trim((string) $request->query('risk', ''));
        $payment = trim((string) $request->query('payment', ''));
        $arch    = (string) $request->query('archived', '0');

        $rows = $this->studentList
            ->filteredQuery($cid, compact('q', 'senior', 'branch', 'risk', 'payment') + ['archived' => $arch])
            ->latest('updated_at')
            ->get(['student_id', 'senior_email', 'branch', 'risk_level',
                   'payment_status', 'dealer_id', 'student_type', 'is_archived', 'updated_at']);

        $csv = implode(',', [
            'Student ID', 'Senior E-posta', 'Şube', 'Risk', 'Ödeme', 'Dealer', 'Tür', 'Arşiv', 'Güncellendi',
        ]) . "\n";

        foreach ($rows as $s) {
            $csv .= implode(',', array_map(
                fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"',
                [
                    $s->student_id ?? '',
                    $s->senior_email ?? '',
                    $s->branch ?? '',
                    $s->risk_level ?? '',
                    $s->payment_status ?? '',
                    $s->dealer_id ?? '',
                    $s->student_type ?? '',
                    $s->is_archived ? 'Evet' : 'Hayır',
                    optional($s->updated_at)->format('d.m.Y H:i') ?? '',
                ]
            )) . "\n";
        }

        $filename = 'students_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ─── ÖĞRENCİLER (LİSTE) ─────────────────────────────────────────────────

    public function students(Request $request): View
    {
        $cid     = $this->companyId();
        $q       = trim((string) $request->query('q', ''));
        $senior  = trim((string) $request->query('senior', ''));
        $branch  = trim((string) $request->query('branch', ''));
        $risk    = trim((string) $request->query('risk', ''));
        $payment = trim((string) $request->query('payment', ''));
        $arch    = (string) $request->query('archived', '0');

        $rows = $this->studentList
            ->filteredQuery($cid, compact('q', 'senior', 'branch', 'risk', 'payment') + ['archived' => $arch])
            ->latest('updated_at')
            ->paginate(50, ['student_id', 'senior_email', 'branch', 'risk_level',
                            'payment_status', 'dealer_id', 'student_type', 'is_archived', 'updated_at']);

        $kpis = $this->studentList->kpis($cid);

        ['seniorOptions' => $seniorOptions, 'branchOptions' => $branchOptions] = $this->studentList->filterOptions($cid);

        return view('manager.students', compact(
            'rows', 'kpis', 'seniorOptions', 'branchOptions',
            'q', 'senior', 'branch', 'risk', 'payment', 'arch'
        ));
    }

    // ─── ÖĞRENCİ DETAY ──────────────────────────────────────────────────────

    public function studentShow(string $studentId): View
    {
        $cid = $this->companyId();

        $assignment = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->where('student_id', $studentId)
            ->firstOrFail();

        $guest   = GuestApplication::where('converted_student_id', $studentId)->first();
        $revenue = DealerStudentRevenue::where('student_id', $studentId)->first();

        $visa          = StudentVisaApplication::where('student_id', $studentId)->latest('id')->first();
        $accommodation = StudentAccommodation::where('student_id', $studentId)->latest('id')->first();
        $uniApplications = StudentUniversityApplication::where('student_id', $studentId)->orderBy('priority')->get();

        $seniorOptions = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->distinct()->pluck('senior_email')->filter()->sort()->values();

        $branchOptions = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->distinct()->pluck('branch')->filter()->sort()->values();

        return view('manager.student-detail', compact(
            'assignment', 'guest', 'revenue', 'studentId', 'seniorOptions', 'branchOptions',
            'visa', 'accommodation', 'uniApplications'
        ));
    }

    public function studentUpdateAssignment(Request $request, string $studentId): RedirectResponse
    {
        $cid = $this->companyId();

        $assignment = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->where('student_id', $studentId)
            ->firstOrFail();

        $assignment->update([
            'risk_level'     => $request->input('risk_level') ?: null,
            'payment_status' => $request->input('payment_status') ?: null,
            'senior_email'   => trim((string) $request->input('senior_email', $assignment->senior_email)),
            'branch'         => trim((string) $request->input('branch', $assignment->branch ?? '')),
        ]);

        return back()->with('status', 'Öğrenci bilgileri güncellendi.');
    }

    // ─── SENİOR YÖNETİMİ (LİSTE) ────────────────────────────────────────────

    public function seniors(Request $request): View
    {
        $cid = $this->companyId();

        [$seniors, $kpis] = Cache::remember("mgr_seniors_{$cid}", 300, function () use ($cid) {
            $activeData = StudentAssignment::query()
                ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
                ->where('is_archived', false)
                ->selectRaw('senior_email, count(*) as active_count')
                ->groupBy('senior_email')
                ->pluck('active_count', 'senior_email');

            $archivedData = StudentAssignment::query()
                ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
                ->where('is_archived', true)
                ->selectRaw('senior_email, count(*) as archived_count')
                ->groupBy('senior_email')
                ->pluck('archived_count', 'senior_email');

            $guestData = GuestApplication::query()
                ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
                ->whereNotNull('assigned_senior_email')
                ->where('converted_to_student', false)
                ->selectRaw('assigned_senior_email, count(*) as guest_count')
                ->groupBy('assigned_senior_email')
                ->pluck('guest_count', 'assigned_senior_email');

            $allEmails = $activeData->keys()
                ->merge($archivedData->keys())
                ->merge($guestData->keys())
                ->unique()->sort()->values();

            $userMap = User::query()
                ->whereIn('email', $allEmails->all())
                ->get(['name', 'email'])
                ->keyBy(fn ($u) => strtolower((string) $u->email));

            $seniors = $allEmails->map(function ($email) use ($activeData, $archivedData, $guestData, $userMap) {
                $user = $userMap[strtolower($email)] ?? null;
                return [
                    'email'       => $email,
                    'name'        => $user?->name ?: $email,
                    'active'      => (int) ($activeData[$email] ?? 0),
                    'archived'    => (int) ($archivedData[$email] ?? 0),
                    'guest_count' => (int) ($guestData[$email] ?? 0),
                ];
            })->sortByDesc('active')->values();

            $kpis = [
                'total'          => $seniors->count(),
                'total_students' => $seniors->sum('active'),
                'over_capacity'  => $seniors->filter(fn ($s) => $s['active'] >= 20)->count(),
            ];

            return [$seniors, $kpis];
        });

        return view('manager.seniors', compact('seniors', 'kpis'));
    }

    // ─── SENİOR DETAY ────────────────────────────────────────────────────────

    public function seniorShow(string $email): View
    {
        $cid = $this->companyId();

        $activeStudents = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->where('is_archived', false)
            ->whereRaw('lower(senior_email) = ?', [strtolower($email)])
            ->orderBy('student_id')
            ->get();

        $archivedStudents = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->where('is_archived', true)
            ->whereRaw('lower(senior_email) = ?', [strtolower($email)])
            ->orderBy('student_id')
            ->get();

        // Bulk guest lookup — N+1 önleme: tüm student_id'ler için tek sorguda
        $allStudentIds = $activeStudents->pluck('student_id')
            ->merge($archivedStudents->pluck('student_id'))
            ->filter()->unique()->values()->all();
        $guestByStudentId = $allStudentIds
            ? GuestApplication::whereIn('converted_student_id', $allStudentIds)
                ->get(['converted_student_id', 'first_name', 'last_name', 'email'])
                ->keyBy('converted_student_id')
            : collect();

        $pendingGuests = GuestApplication::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->whereRaw('lower(assigned_senior_email) = ?', [strtolower($email)])
            ->where('converted_to_student', false)
            ->where('is_archived', false)
            ->latest()
            ->get();

        $user = User::where('email', strtolower($email))->first();

        $stats = [
            'active'    => $activeStudents->count(),
            'archived'  => $archivedStudents->count(),
            'pending'   => $pendingGuests->count(),
            'high_risk' => $activeStudents->where('risk_level', 'high')->count(),
        ];

        return view('manager.senior-detail', compact(
            'email', 'user', 'activeStudents', 'archivedStudents', 'pendingGuests', 'stats', 'guestByStudentId'
        ));
    }

    // ─── BAYİ YÖNETİMİ (LİSTE) ──────────────────────────────────────────────

    public function dealers(Request $request): View
    {
        $cid = $this->companyId();

        [$enriched, $kpis] = Cache::remember("mgr_dealers_{$cid}", 300, function () use ($cid) {
            $dealers = Dealer::query()
                ->where('is_archived', false)
                ->orderBy('name')
                ->get();

            $revenueMap = DealerStudentRevenue::query()
                ->selectRaw('dealer_id, sum(total_earned) as earned, sum(total_pending) as pending, count(distinct student_id) as students')
                ->groupBy('dealer_id')
                ->get()
                ->keyBy('dealer_id');

            $leadMap = GuestApplication::query()
                ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
                ->whereNotNull('dealer_code')
                ->selectRaw('dealer_code, count(*) as leads, sum(case when converted_to_student then 1 else 0 end) as converted')
                ->groupBy('dealer_code')
                ->get()
                ->keyBy('dealer_code');

            $enriched = $dealers->map(function ($d) use ($revenueMap, $leadMap) {
                $rev  = $revenueMap[$d->code] ?? null;
                $lead = $leadMap[$d->code] ?? null;
                return [
                    'code'      => $d->code,
                    'name'      => $d->name,
                    'type'      => $d->dealer_type_code,
                    'is_active' => $d->is_active,
                    'students'  => (int) ($rev?->students ?? 0),
                    'earned'    => (float) ($rev?->earned ?? 0),
                    'pending'   => (float) ($rev?->pending ?? 0),
                    'leads'     => (int) ($lead?->leads ?? 0),
                    'converted' => (int) ($lead?->converted ?? 0),
                ];
            });

            $kpis = [
                'total'   => $dealers->count(),
                'active'  => $dealers->where('is_active', true)->count(),
                'earned'  => round((float) $enriched->sum('earned'), 2),
                'pending' => round((float) $enriched->sum('pending'), 2),
            ];

            return [$enriched->values(), $kpis];
        });

        return view('manager.dealers', compact('enriched', 'kpis'));
    }

    // ─── BAYİ DETAY ──────────────────────────────────────────────────────────

    public function dealerShow(string $code): View
    {
        $cid = $this->companyId();

        $dealer   = Dealer::where('code', $code)->firstOrFail();
        abort_if($cid > 0 && (int) ($dealer->company_id ?? 0) !== $cid, 403);
        $revenues = DealerStudentRevenue::where('dealer_id', $code)->get();
        $payouts  = DealerPayoutRequest::where('dealer_code', $code)->with('account')->latest()->paginate(25)->withQueryString();

        $leads = GuestApplication::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->where('dealer_code', $code)
            ->latest()
            ->paginate(25, ['id', 'first_name', 'last_name', 'email', 'lead_status',
                   'converted_to_student', 'converted_student_id', 'assigned_senior_email', 'created_at']);

        $revenueStats = [
            'total_earned'  => round((float) $revenues->sum('total_earned'), 2),
            'total_pending' => round((float) $revenues->sum('total_pending'), 2),
            'students'      => $revenues->count(),
        ];

        // UTM / Tracking link performansı
        $utmLinks = MarketingTrackingLink::where('dealer_code', $code)
            ->orderByDesc('click_count')
            ->get(['id', 'code', 'title', 'status', 'click_count', 'last_clicked_at',
                   'utm_source', 'utm_medium', 'utm_campaign', 'created_at']);

        $linkCodes = $utmLinks->pluck('code')->filter()->values()->all();

        $leadStatsByCode = collect();
        if (!empty($linkCodes)) {
            $leadStatsByCode = LeadSourceDatum::query()
                ->whereIn('referral_link_id', $linkCodes)
                ->selectRaw('referral_link_id as code,
                    COUNT(*) as lead_count,
                    SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
                ->groupBy('referral_link_id')
                ->get()
                ->keyBy('code');
        }

        $utmStats = [
            'total_links'   => $utmLinks->count(),
            'active_links'  => $utmLinks->where('status', 'active')->count(),
            'total_clicks'  => (int) $utmLinks->sum('click_count'),
            'total_leads'   => (int) $leadStatsByCode->sum('lead_count'),
            'total_converted' => (int) $leadStatsByCode->sum('converted_count'),
        ];

        return view('manager.dealer-detail', compact(
            'dealer', 'revenues', 'payouts', 'leads', 'revenueStats',
            'utmLinks', 'leadStatsByCode', 'utmStats'
        ));
    }

    // ─── BAYİ TİPİ YÖNETİMİ ─────────────────────────────────────────────────

    public function dealerTypes(): View
    {
        $types = \App\Models\DealerType::where('is_active', true)->orderBy('id')->get();

        $permissionLabels = [
            'canAccessSupport'       => ['label' => 'Danışman Desteği',     'desc' => 'Advisor sayfasına erişim'],
            'canAccessTraining'      => ['label' => 'Eğitim Merkezi',       'desc' => 'KB makaleleri ve sertifika'],
            'canAccessCalculator'    => ['label' => 'Komisyon Hesaplama',   'desc' => 'Komisyon simülatörü'],
            'canViewStudentDetails'  => ['label' => 'Öğrenci Detayları',    'desc' => 'Lead detay sayfasında öğrenci bilgisi'],
            'canViewDocuments'       => ['label' => 'Belge Görüntüleme',    'desc' => 'Öğrenci belgelerini görebilme'],
            'canUploadDocuments'     => ['label' => 'Belge Yükleme',        'desc' => 'Öğrenci adına belge yükleme'],
            'canMessageStudent'      => ['label' => 'Öğrenciye Mesaj',      'desc' => 'Direkt mesaj gönderme'],
            'canViewProcessDetails'  => ['label' => 'Süreç Takibi',         'desc' => 'Başvuru süreç detaylarını görme'],
            'canViewFinancials'      => ['label' => 'Finansal Bilgiler',    'desc' => 'Kazanç ve ödeme sayfaları'],
            'canViewTerritoryStats'  => ['label' => 'Bölge İstatistikleri', 'desc' => 'Coğrafi performans verileri'],
        ];

        // Her tip kaç dealer kullanıyor
        $dealerCounts = \App\Models\Dealer::where('is_active', true)
            ->selectRaw('dealer_type_code, COUNT(*) as cnt')
            ->groupBy('dealer_type_code')
            ->pluck('cnt', 'dealer_type_code');

        return view('manager.dealer-types', compact('types', 'permissionLabels', 'dealerCounts'));
    }

    public function updateDealerType(Request $request, string $code): \Illuminate\Http\RedirectResponse
    {
        $type = \App\Models\DealerType::where('code', $code)->firstOrFail();

        $validated = $request->validate([
            'name_tr'         => ['required', 'string', 'max:200'],
            'tier'            => ['required', 'integer', 'min:1', 'max:5'],
            'dashboardLevel'  => ['required', 'string', 'in:basic,standard,advanced'],
        ]);

        // Boolean permissions
        $boolPerms = [
            'canAccessSupport', 'canAccessTraining', 'canAccessCalculator',
            'canViewStudentDetails', 'canViewDocuments', 'canUploadDocuments',
            'canMessageStudent', 'canViewProcessDetails', 'canViewFinancials',
            'canViewTerritoryStats',
        ];

        $perms = $type->permissions ?? [];
        $perms['tier'] = (int) $validated['tier'];
        $perms['dashboardLevel'] = $validated['dashboardLevel'];

        foreach ($boolPerms as $perm) {
            $perms[$perm] = $request->boolean($perm);
        }

        $type->name_tr = $validated['name_tr'];
        $type->permissions = $perms;
        $type->save();

        \Illuminate\Support\Facades\Cache::flush();

        return redirect()->route('manager.dealer-types')->with('status', "{$type->name_tr} güncellendi.");
    }

    // ─── KOMİSYON YÖNETİMİ ──────────────────────────────────────────────────

    public function commissions(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));
        $dealer = trim((string) $request->query('dealer', ''));

        $rows = DealerPayoutRequest::query()
            ->when($status !== '', fn ($b) => $b->where('status', $status))
            ->when($dealer !== '', fn ($b) => $b->where('dealer_code', $dealer))
            ->with('account')
            ->latest()
            ->paginate(50);

        $kpis = [
            'requested' => DealerPayoutRequest::query()->where('status', 'requested')->count(),
            'approved'  => DealerPayoutRequest::query()->where('status', 'approved')->count(),
            'paid'      => DealerPayoutRequest::query()->where('status', 'paid')->count(),
            'rejected'  => DealerPayoutRequest::query()->where('status', 'rejected')->count(),
        ];

        $dealerOptions = DealerPayoutRequest::query()
            ->distinct()->pluck('dealer_code')->filter()->sort()->values();

        return view('manager.commissions', compact(
            'rows', 'kpis', 'dealerOptions', 'status', 'dealer'
        ));
    }

    private function authorizePayoutCompany(DealerPayoutRequest $payout): void
    {
        $cid = $this->companyId();
        if ($cid > 0) {
            $dealer = Dealer::where('code', $payout->dealer_code)->first();
            abort_if(!$dealer || (int) ($dealer->company_id ?? 0) !== $cid, 403);
        }
    }

    public function approveCommission(Request $request, DealerPayoutRequest $payout): RedirectResponse
    {
        $this->authorizePayoutCompany($payout);
        abort_if($payout->status !== 'requested', 422, 'Bu talep onaylanabilir durumda değil.');
        $payout->update([
            'status'      => 'approved',
            'approved_by' => $request->user()?->email,
            'approved_at' => now(),
        ]);
        return back()->with('status', '#' . $payout->id . ' talebi onaylandı.');
    }

    public function rejectCommission(Request $request, DealerPayoutRequest $payout): RedirectResponse
    {
        $this->authorizePayoutCompany($payout);
        abort_if($payout->status !== 'requested', 422, 'Bu talep reddedilebilir durumda değil.');
        $payout->update([
            'status'           => 'rejected',
            'rejection_reason' => trim((string) $request->input('rejection_reason', '')) ?: null,
        ]);
        return back()->with('status', '#' . $payout->id . ' talebi reddedildi.');
    }

    public function markPaid(Request $request, DealerPayoutRequest $payout): RedirectResponse
    {
        $this->authorizePayoutCompany($payout);
        abort_if($payout->status !== 'approved', 422, 'Sadece onaylanan talepler ödendi olarak işaretlenebilir.');
        $payout->update([
            'status'      => 'paid',
            'paid_at'     => now(),
            'receipt_url' => trim((string) $request->input('receipt_url', '')) ?: null,
        ]);
        return back()->with('status', '#' . $payout->id . ' ödeme tamamlandı olarak işaretlendi.');
    }

    /** Student kartındaki "Gelen Belgeler" sekmesi için JSON endpoint. */
    public function studentInstitutionDocs(string $studentId): JsonResponse
    {
        $docs = StudentInstitutionDocument::query()
            ->forStudent($studentId)
            ->latest()
            ->get([
                'id', 'institution_category', 'document_type_code', 'document_type_label',
                'institution_name', 'received_date', 'status',
                'is_visible_to_student', 'is_visible_to_dealer',
                'notes', 'file_id', 'made_visible_at', 'added_by', 'created_at',
            ]);

        $catalog = config('institution_document_catalog.categories', []);

        return response()->json([
            'student_id' => $studentId,
            'count'      => $docs->count(),
            'catalog'    => $catalog,
            'documents'  => $docs->values(),
        ]);
    }

    /** Student kartındaki "Üniversite Başvuruları" sekmesi için JSON endpoint. */
    public function studentUniversityApplications(string $studentId): JsonResponse
    {
        $apps = StudentUniversityApplication::query()
            ->forStudent($studentId)
            ->orderBy('priority')
            ->get([
                'id', 'university_name', 'city', 'state',
                'department_name', 'degree_type', 'semester',
                'application_portal', 'application_number',
                'status', 'priority', 'deadline', 'submitted_at', 'result_at',
                'notes', 'is_visible_to_student', 'is_visible_to_dealer', 'created_at',
            ]);

        return response()->json([
            'student_id'   => $studentId,
            'count'        => $apps->count(),
            'applications' => $apps->map(fn ($a) => array_merge($a->toArray(), [
                'status_label' => $a->statusLabel(),
                'degree_label' => $a->degreeLabel(),
                'badge_class'  => $a->statusBadgeClass(),
            ]))->values(),
        ]);
    }

    /** Senior leaderboard — son 3 ay snapshot verisi (JSON). */
    public function seniorLeaderboard(\Illuminate\Http\Request $request, SeniorPerformanceService $service): JsonResponse
    {
        $months = (int) $request->query('months', 3);
        $months = max(1, min(12, $months));

        $leaderboard = $service->getLeaderboard($this->companyId() ?: null, $months);

        return response()->json([
            'months'      => $months,
            'period_from' => now()->subMonths($months - 1)->format('Y-m'),
            'period_to'   => now()->format('Y-m'),
            'count'       => $leaderboard->count(),
            'seniors'     => $leaderboard->sortByDesc('converted_count')->values(),
        ]);
    }

    // ── Üniversite Belge Haritası ─────────────────────────────────────────────

    /** Manager: Üniversite gereksinim haritasını görüntüle + yönet. */
    public function universityRequirements(Request $request): View
    {
        $catalog   = config('university_catalog.universities', []);
        $docCatalog = config('university_application_documents.documents', []);
        $docCategories = config('university_application_documents.categories', []);

        $filterUni  = $request->query('university_code');
        $filterDept = $request->query('department_code');
        $filterDeg  = $request->query('degree_type');

        $query = UniversityRequirementMap::query()->orderBy('university_code')->orderBy('degree_type');

        if ($filterUni) {
            $query->where('university_code', $filterUni);
        }
        if ($filterDept) {
            $query->where('department_code', $filterDept);
        }
        if ($filterDeg) {
            $query->where('degree_type', $filterDeg);
        }

        $maps = $query->get();

        return view('manager.university-requirements.index', compact(
            'maps', 'catalog', 'docCatalog', 'docCategories'
        ));
    }

    /** Manager: Yeni gereksinim haritası ekle. */
    public function universityRequirementStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'university_code'            => 'required|string|max:32',
            'department_code'            => 'nullable|string|max:64',
            'degree_type'                => 'required|string|max:32',
            'semester'                   => 'required|in:WS,SS,both',
            'portal_name'                => 'required|string|max:32',
            'deadline_month_ws'          => 'nullable|integer|min:1|max:12',
            'deadline_day_ws'            => 'nullable|integer|min:1|max:31',
            'deadline_month_ss'          => 'nullable|integer|min:1|max:12',
            'deadline_day_ss'            => 'nullable|integer|min:1|max:31',
            'required_document_codes'    => 'required|array|min:1',
            'required_document_codes.*'  => 'string',
            'recommended_document_codes' => 'nullable|array',
            'language_requirement'       => 'nullable|string|max:128',
            'min_gpa'                    => 'nullable|numeric|min:1|max:4',
            'notes'                      => 'nullable|string|max:1000',
        ]);

        $data['created_by'] = auth()->id();

        UniversityRequirementMap::create($data);

        return redirect()->route('manager.university-requirements')
            ->with('status', 'Gereksinim haritası eklendi.');
    }

    /** Manager: Gereksinim haritasını güncelle. */
    public function universityRequirementUpdate(Request $request, UniversityRequirementMap $map): RedirectResponse
    {
        $data = $request->validate([
            'portal_name'                => 'required|string|max:32',
            'semester'                   => 'required|in:WS,SS,both',
            'deadline_month_ws'          => 'nullable|integer|min:1|max:12',
            'deadline_day_ws'            => 'nullable|integer|min:1|max:31',
            'deadline_month_ss'          => 'nullable|integer|min:1|max:12',
            'deadline_day_ss'            => 'nullable|integer|min:1|max:31',
            'required_document_codes'    => 'required|array|min:1',
            'required_document_codes.*'  => 'string',
            'recommended_document_codes' => 'nullable|array',
            'language_requirement'       => 'nullable|string|max:128',
            'min_gpa'                    => 'nullable|numeric|min:1|max:4',
            'notes'                      => 'nullable|string|max:1000',
            'is_active'                  => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $map->update($data);

        return redirect()->route('manager.university-requirements')
            ->with('status', 'Gereksinim haritası güncellendi.');
    }

    /** Manager: Gereksinim haritasını sil. */
    public function universityRequirementDelete(UniversityRequirementMap $map): RedirectResponse
    {
        $map->delete();

        return redirect()->route('manager.university-requirements')
            ->with('status', 'Gereksinim haritası silindi.');
    }

    /** API: Senior tarafından university_code+department_code+degree_type için gereksinim listesi. */
    public function universityRequirementLookup(Request $request): JsonResponse
    {
        $uniCode  = $request->query('university_code');
        $deptCode = $request->query('department_code');
        $degree   = $request->query('degree_type', 'master');
        $semester = $request->query('semester', 'WS');

        if (!$uniCode) {
            return response()->json(['error' => 'university_code gerekli'], 422);
        }

        $map = UniversityRequirementMap::where('university_code', $uniCode)
            ->where('department_code', $deptCode ?: null)
            ->where('degree_type', $degree)
            ->where(fn ($q) => $q->where('semester', $semester)->orWhere('semester', 'both'))
            ->where('is_active', true)
            ->first();

        if (!$map) {
            return response()->json(['found' => false, 'map' => null]);
        }

        $docCatalog = config('university_application_documents.documents', []);

        $required = collect($map->required_document_codes)->map(fn ($code) => [
            'code'     => $code,
            'label_tr' => $docCatalog[$code]['label_tr'] ?? $code,
            'category' => $docCatalog[$code]['category'] ?? 'diger',
        ])->values();

        $recommended = collect($map->recommended_document_codes ?? [])->map(fn ($code) => [
            'code'     => $code,
            'label_tr' => $docCatalog[$code]['label_tr'] ?? $code,
            'category' => $docCatalog[$code]['category'] ?? 'diger',
        ])->values();

        return response()->json([
            'found'              => true,
            'portal_name'        => $map->portal_name,
            'deadline_ws'        => $map->deadlineWsLabel(),
            'deadline_ss'        => $map->deadlineSsLabel(),
            'language_req'       => $map->language_requirement,
            'min_gpa'            => $map->min_gpa,
            'notes'              => $map->notes,
            'required'           => $required,
            'recommended'        => $recommended,
        ]);
    }

    // ── 2.5 Performance Targets ──────────────────────────────────────────────

    public function setSeniorTargets(Request $request, string $email): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'period'               => 'required|string|regex:/^\d{4}-\d{2}$/',
            'target_conversions'   => 'required|integer|min:0',
            'target_outcomes'      => 'required|integer|min:0',
            'target_doc_reviews'   => 'required|integer|min:0',
            'target_appointments'  => 'required|integer|min:0',
        ]);

        $companyId = (int) optional($request->user())->company_id;

        \App\Models\SeniorPerformanceTarget::updateOrCreate(
            ['senior_email' => strtolower($email), 'period' => $data['period']],
            array_merge($data, [
                'company_id'      => $companyId,
                'senior_email'    => strtolower($email),
                'set_by_user_id'  => $request->user()?->id,
            ])
        );

        return response()->json(['ok' => true]);
    }

    // =========================================================================
    // KATMAN 2.5 — Audit Log Görüntüleyici
    // =========================================================================

    public function auditLog(Request $request): View
    {
        $cid       = $this->companyId();
        $q         = trim((string) $request->query('q', ''));
        $eventType = trim((string) $request->query('event_type', ''));
        $actor     = trim((string) $request->query('actor', ''));

        $logs = SystemEventLog::query()
            ->when($cid > 0, fn ($w) => $w->where('company_id', $cid))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                ->where('message', 'like', "%{$q}%")
                ->orWhere('entity_id', 'like', "%{$q}%")))
            ->when($eventType !== '', fn ($w) => $w->where('event_type', $eventType))
            ->when($actor !== '', fn ($w) => $w->where('actor_email', $actor))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $eventTypes = SystemEventLog::query()
            ->when($cid > 0, fn ($w) => $w->where('company_id', $cid))
            ->distinct()->pluck('event_type')->sort()->values();

        return view('manager.audit-log', [
            'logs'       => $logs,
            'eventTypes' => $eventTypes,
            'filters'    => compact('q', 'eventType', 'actor'),
        ]);
    }

    // ── Süreç Adımı Sub-task Yönetimi ─────────────────────────────────────────

    public function processStepTasks(): View
    {
        $definitions = ProcessDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->with(['stepTasks' => fn($q) => $q->orderBy('sort_order')])
            ->get();

        return view('manager.process-step-tasks', compact('definitions'));
    }

    public function processStepTaskStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'process_definition_id' => ['required', 'integer', 'exists:process_definitions,id'],
            'label_tr'              => ['required', 'string', 'max:255'],
            'label_de'              => ['nullable', 'string', 'max:255'],
            'sort_order'            => ['nullable', 'integer', 'min:0'],
            'is_required'           => ['boolean'],
        ]);

        ProcessStepTask::create(array_merge($data, [
            'is_required' => $request->boolean('is_required'),
            'sort_order'  => $data['sort_order'] ?? 0,
            'added_by'    => $request->user()?->email,
        ]));

        return back()->with('status', 'Görev eklendi.');
    }

    public function processStepTaskUpdate(Request $request, ProcessStepTask $task): RedirectResponse
    {
        $data = $request->validate([
            'label_tr'    => ['required', 'string', 'max:255'],
            'label_de'    => ['nullable', 'string', 'max:255'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_required' => ['boolean'],
            'is_active'   => ['boolean'],
        ]);

        $task->update([
            'label_tr'    => $data['label_tr'],
            'label_de'    => $data['label_de'] ?? null,
            'sort_order'  => $data['sort_order'] ?? $task->sort_order,
            'is_required' => $request->boolean('is_required'),
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Görev güncellendi.');
    }

    public function processStepTaskDelete(ProcessStepTask $task): RedirectResponse
    {
        $task->delete();
        return back()->with('status', 'Görev silindi.');
    }
}

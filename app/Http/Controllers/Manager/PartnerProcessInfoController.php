<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentUniversityApplication;
use App\Models\StudentVisaApplication;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Partner firmaya "öğrencimin süreci ne durumda?" bilgisi — SALT OKUNUR.
 *
 * Partner öğrenciyi devrediyor, operasyonu MentorDE yürütüyor. Partner bu
 * ekranda yalnızca izler: burada hiçbir yazma yolu yok, form yok, aksiyon
 * yok. Süreci ilerletmek operasyonu yapan firmanın işi.
 *
 * ⚠ İKİ AYRI KAPSAM TUZAĞI VAR — ikisi de sessizce boş ekran üretirdi:
 *
 * 1) DANIŞMAN. `User` tenant kapsamlı ve danışman ÜST firmanın elemanı.
 *    `$assignment->senior()` ilişkisi partner bağlamında null döner —
 *    partner kendi öğrencisine atanmış danışmanı göremezdi. E-posta ile
 *    kapsamsız okuyoruz (aşağıda `resolveAdvisor`).
 *
 * 2) SÜREÇ KAYITLARI. Üniversite başvurusu / vize / kurum belgesi kayıtları
 *    operasyonu yapan firmanın bağlamında oluşuyor. Bu modellerde global
 *    kapsam yok, o yüzden student_id ile okumak doğru sonucu veriyor —
 *    yetki sınırı öğrencinin partnere ait olması (aşağıdaki firstOrFail).
 *
 * GÖRÜNÜRLÜK: partner üçüncü taraf. Bayi portalındaki `is_visible_to_dealer`
 * bayrağı aynen geçerli. ProcessOutcome'da öyle bir bayrak yok; orada
 * `is_visible_to_student` esas alınıyor — öğrenci zaten partnerin müşterisi,
 * onun gördüğünü partnerin görmesi yeni bir sızıntı değil. Henüz
 * paylaşılmamış kayıtların yalnızca SAYISI gösteriliyor: partner işin
 * sürdüğünü bilir, içeriğini görmez.
 */
class PartnerProcessInfoController extends Controller
{
    /** Öğrencinin geçtiği 6 ana süreç. */
    private const STEPS = [
        'application_prep'  => 'Başvuru Hazırlık',
        'uni_assist'        => 'Uni Assist',
        'visa_application'  => 'Vize Başvurusu',
        'language_course'   => 'Dil Kursu',
        'residence'         => 'İkamet',
        'official_services' => 'Resmi Hizmetler',
    ];

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    // ─── LİSTE ──────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $cid = $this->companyId();
        $q   = trim((string) $request->query('q', ''));

        $rows = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->where('is_archived', false)
            ->when($q !== '', fn ($b) => $b->where(function ($x) use ($q) {
                $x->where('student_id', 'like', "%{$q}%")
                  ->orWhere('display_name', 'like', "%{$q}%");
            }))
            ->orderByDesc('updated_at')
            ->paginate(50, ['id', 'student_id', 'display_name', 'senior_email', 'updated_at']);

        $studentIds = $rows->pluck('student_id')->all();

        // Son görünür süreç hareketi — her öğrenci için tek sorguda.
        $latest = ProcessOutcome::query()
            ->whereIn('student_id', $studentIds)
            ->where('is_visible_to_student', true)
            ->orderByDesc('id')
            ->get(['student_id', 'process_step', 'outcome_type', 'created_at'])
            ->groupBy('student_id')
            ->map(fn ($group) => $group->first());

        $names = $this->guestNames($studentIds);

        return view('manager.partner.process-info-index', [
            'rows'   => $rows,
            'latest' => $latest,
            'names'  => $names,
            'steps'  => self::STEPS,
            'q'      => $q,
        ]);
    }

    // ─── DETAY ──────────────────────────────────────────────────────────────

    public function show(string $studentId): View
    {
        $cid = $this->companyId();

        // YETKİ SINIRI: öğrenci bu firmaya ait değilse 404. Aşağıdaki süreç
        // sorguları kapsamsız okuduğu için tek koruma burası.
        $assignment = StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->where('student_id', $studentId)
            ->firstOrFail();

        $guest = GuestApplication::where('converted_student_id', $studentId)->first();

        $outcomes = ProcessOutcome::query()
            ->where('student_id', $studentId)
            ->where('is_visible_to_student', true)
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'process_step', 'outcome_type', 'university', 'program',
                   'details_tr', 'deadline', 'created_at']);

        // İçeriği değil, yalnızca "devam eden iş var" bilgisi.
        $pendingCount = ProcessOutcome::query()
            ->where('student_id', $studentId)
            ->where('is_visible_to_student', false)
            ->count();

        $uniApplications = StudentUniversityApplication::query()
            ->forStudent($studentId)
            ->where('is_visible_to_dealer', true)
            ->orderBy('priority')
            ->get(['id', 'university_name', 'city', 'department_name', 'degree_type',
                   'semester', 'status', 'priority', 'deadline', 'submitted_at', 'result_at']);

        $institutionDocs = StudentInstitutionDocument::query()
            ->forStudent($studentId)
            ->visibleToDealer()
            ->latest('id')
            ->get(['id', 'institution_category', 'document_type_label', 'institution_name',
                   'received_date', 'status', 'created_at']);

        $visa = StudentVisaApplication::query()
            ->where('student_id', $studentId)
            ->where('is_visible_to_student', true)
            ->latest('id')
            ->first(['id', 'visa_type', 'status', 'application_date', 'appointment_date',
                     'decision_date', 'consulate_city']);

        return view('manager.partner.process-info-show', [
            'assignment'      => $assignment,
            'studentId'       => $studentId,
            'studentName'     => $this->displayName($assignment, $guest),
            'advisor'         => $this->resolveAdvisor((string) $assignment->senior_email),
            'outcomes'        => $outcomes,
            'pendingCount'    => $pendingCount,
            'uniApplications' => $uniApplications,
            'institutionDocs' => $institutionDocs,
            'visa'            => $visa,
            'steps'           => self::STEPS,
            'progress'        => $this->progress($outcomes),
        ]);
    }

    // ─── Yardımcılar ────────────────────────────────────────────────────────

    /**
     * Atanan danışman — KAPSAMSIZ okunur.
     *
     * Danışman operasyonu yürüten üst firmanın elemanı; partnerin kapsamında
     * aranırsa bulunamaz ve ekranda sessizce "atanmamış" görünürdü. Partner
     * kimin ilgilendiğini bilmek zorunda, iletişim maddesi buna dayanıyor.
     */
    private function resolveAdvisor(string $email): ?User
    {
        if ($email === '') {
            return null;
        }

        return User::query()
            ->withoutGlobalScope('company')
            ->where('email', $email)
            ->first(['id', 'name', 'email', 'role', 'phone']);
    }

    /** @param list<string> $studentIds @return array<string,string> */
    private function guestNames(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        return GuestApplication::query()
            ->whereIn('converted_student_id', $studentIds)
            ->get(['converted_student_id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($g) => [
                (string) $g->converted_student_id => trim($g->first_name . ' ' . $g->last_name),
            ])
            ->filter(fn ($name) => $name !== '')
            ->all();
    }

    private function displayName(StudentAssignment $assignment, ?GuestApplication $guest): string
    {
        $fromGuest = $guest ? trim($guest->first_name . ' ' . $guest->last_name) : '';

        return $fromGuest !== ''
            ? $fromGuest
            : (string) ($assignment->display_name ?: $assignment->student_id);
    }

    /**
     * Her adım için "kaç kayıt / en son ne zaman" özeti.
     *
     * @param \Illuminate\Support\Collection<int,ProcessOutcome> $outcomes
     * @return list<array{step:string,label:string,count:int,last:?\Illuminate\Support\Carbon}>
     */
    private function progress($outcomes): array
    {
        $result = [];

        foreach (self::STEPS as $step => $label) {
            $subset   = $outcomes->where('process_step', $step);
            $result[] = [
                'step'  => $step,
                'label' => $label,
                'count' => $subset->count(),
                'last'  => $subset->first()?->created_at,
            ];
        }

        return $result;
    }
}

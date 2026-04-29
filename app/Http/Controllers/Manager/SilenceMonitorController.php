<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\EventLogService;
use App\Services\SilenceCheckinService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Manager — sessizlik monitörü.
 * Aday + öğrenciler için "X gündür hareket yok" toplu görünüm + manuel
 * tetikleme + kişi/şirket bazında cadence override.
 *
 * NOT: İleride finans gibi başka bir role devredilirse bu controller
 * Finance ya da Operations namespace'ine taşınır; route name "manager." prefix'i
 * sayesinde tek noktada değişir.
 */
class SilenceMonitorController extends Controller
{
    public function __construct(
        private readonly SilenceCheckinService $svc,
        private readonly EventLogService $eventLogService,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'guests') === 'students' ? 'students' : 'guests';

        if ($tab === 'students') {
            $rows = $this->buildStudentRows();
        } else {
            $rows = $this->buildGuestRows();
        }

        $defaults = (array) config('brand.silence_checkin_days', []);
        $companyId = (int) (Auth::user()?->company_id ?? 0);
        $company = $companyId ? Company::find($companyId) : null;
        $companyOverrides = is_array($company?->silence_checkin_overrides) ? $company->silence_checkin_overrides : [];

        return view('manager.silence-monitor.index', [
            'tab'              => $tab,
            'rows'             => $rows,
            'defaults'         => $defaults,
            'companyOverrides' => $companyOverrides,
            'stageLabels'      => [
                'application' => 'Başvuru (aday)',
                'uni_assist'  => 'Üniversite başvurusu',
                'visa'        => 'Vize',
                'general'     => 'Genel takip',
            ],
        ]);
    }

    public function trigger(Request $request, string $type, int $id): RedirectResponse
    {
        $entity = $this->resolveEntity($type, $id);
        if (! $entity) return back()->withErrors(['silence' => 'Kayıt bulunamadı.']);

        $stage = $type === 'guest'
            ? $this->svc->resolveGuestStage($entity)
            : $this->svc->resolveStudentStage($entity);

        if (! $stage) {
            return back()->withErrors(['silence' => 'Bu kayıt check-in için uygun değil (lost/converted/inactive).']);
        }

        $daysSilent = $this->svc->daysSinceLastActivity($entity);
        $this->svc->postCheckin($entity, $stage, $daysSilent, manual: true);

        $this->eventLogService->log(
            eventType: 'silence_checkin_manual_trigger',
            entityType: $type === 'guest' ? 'guest_application' : 'student',
            entityId: (string) $entity->id,
            message: 'Manager #' . (Auth::id() ?? '?') . ' manuel sessizlik check-in tetikledi.',
            meta: ['stage' => $stage, 'days_silent' => $daysSilent],
        );

        return back()->with('success', 'Check-in gönderildi.');
    }

    public function setOverride(Request $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);
        $entity = $this->resolveEntity($type, $id);
        if (! $entity) return back()->withErrors(['silence' => 'Kayıt bulunamadı.']);

        $entity->forceFill([
            'silence_checkin_days_override' => $data['days'] ?? null,
        ])->save();

        return back()->with('success', isset($data['days'])
            ? "Cadence override {$data['days']} güne ayarlandı."
            : 'Override kaldırıldı (default cadence kullanılacak).');
    }

    public function pause(Request $request, string $type, int $id): RedirectResponse
    {
        $entity = $this->resolveEntity($type, $id);
        if (! $entity) return back()->withErrors(['silence' => 'Kayıt bulunamadı.']);

        $entity->forceFill(['silence_checkin_paused_at' => now()])->save();
        return back()->with('success', 'Bu kayıt için check-in durduruldu.');
    }

    public function resume(string $type, int $id): RedirectResponse
    {
        $entity = $this->resolveEntity($type, $id);
        if (! $entity) return back()->withErrors(['silence' => 'Kayıt bulunamadı.']);

        $entity->forceFill(['silence_checkin_paused_at' => null])->save();
        return back()->with('success', 'Check-in yeniden açıldı.');
    }

    public function updateCompanyOverrides(Request $request): RedirectResponse
    {
        $defaults = (array) config('brand.silence_checkin_days', []);
        $rules = [];
        foreach (array_keys($defaults) as $stage) {
            $rules[$stage] = 'nullable|integer|min:1|max:365';
        }
        $data = $request->validate($rules);

        $companyId = (int) (Auth::user()?->company_id ?? 0);
        if (! $companyId) return back()->withErrors(['silence' => 'Şirket bulunamadı.']);

        $company = Company::findOrFail($companyId);
        $overrides = [];
        foreach ($data as $stage => $val) {
            if (is_numeric($val) && (int) $val > 0) $overrides[$stage] = (int) $val;
        }
        $company->silence_checkin_overrides = $overrides ?: null;
        $company->save();

        return back()->with('success', 'Şirket bazında cadence ayarları kaydedildi.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveEntity(string $type, int $id): ?Model
    {
        return match ($type) {
            'guest'   => GuestApplication::find($id),
            'student' => User::where('role', 'student')->find($id),
            default   => null,
        };
    }

    private function buildGuestRows()
    {
        return GuestApplication::query()
            ->whereNotIn('lead_status', ['converted', 'lost'])
            ->orderBy('updated_at')
            ->limit(300)
            ->get()
            ->map(function (GuestApplication $g) {
                $stage = $this->svc->resolveGuestStage($g);
                if (! $stage) return null;
                $cadence = $this->svc->effectiveCadenceDays($g, $stage);
                $silent  = $this->svc->daysSinceLastActivity($g);
                $cadenceSource = $this->cadenceSource($g, $stage, $cadence);
                return [
                    'type'              => 'guest',
                    'entity'            => $g,
                    'id'                => $g->id,
                    'name'              => trim(((string) ($g->first_name ?? '')) . ' ' . ((string) ($g->last_name ?? ''))),
                    'email'             => $g->email,
                    'stage'             => $stage,
                    'cadence_days'      => $cadence,
                    'cadence_source'    => $cadenceSource,
                    'days_silent'       => $silent,
                    'paused'            => ! empty($g->silence_checkin_paused_at),
                    'override_days'     => (int) ($g->silence_checkin_days_override ?? 0),
                    'last_checkin_at'   => $g->last_silence_checkin_at,
                    'last_activity_at'  => $g->updated_at,
                    'is_overdue'        => $silent >= $cadence,
                ];
            })
            ->filter()
            ->values();
    }

    private function buildStudentRows()
    {
        return User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->orderBy('updated_at')
            ->limit(300)
            ->get()
            ->map(function (User $u) {
                $stage = $this->svc->resolveStudentStage($u);
                if (! $stage) return null;
                $cadence = $this->svc->effectiveCadenceDays($u, $stage);
                $silent  = $this->svc->daysSinceLastActivity($u);
                $cadenceSource = $this->cadenceSource($u, $stage, $cadence);
                return [
                    'type'             => 'student',
                    'entity'           => $u,
                    'id'               => $u->id,
                    'name'             => $u->name,
                    'email'            => $u->email,
                    'stage'            => $stage,
                    'cadence_days'     => $cadence,
                    'cadence_source'   => $cadenceSource,
                    'days_silent'      => $silent,
                    'paused'           => ! empty($u->silence_checkin_paused_at),
                    'override_days'    => (int) ($u->silence_checkin_days_override ?? 0),
                    'last_checkin_at'  => $u->last_silence_checkin_at,
                    'last_activity_at' => $u->updated_at,
                    'is_overdue'       => $silent >= $cadence,
                ];
            })
            ->filter()
            ->values();
    }

    private function cadenceSource(Model $entity, string $stage, int $effective): string
    {
        if ((int) ($entity->silence_checkin_days_override ?? 0) === $effective && (int) ($entity->silence_checkin_days_override ?? 0) > 0) {
            return 'kişi';
        }
        $companyId = (int) ($entity->company_id ?? 0);
        if ($companyId > 0) {
            $company = Company::find($companyId);
            $overrides = is_array($company?->silence_checkin_overrides) ? $company->silence_checkin_overrides : [];
            if ((int) ($overrides[$stage] ?? 0) === $effective && (int) ($overrides[$stage] ?? 0) > 0) {
                return 'şirket';
            }
        }
        return 'default';
    }
}

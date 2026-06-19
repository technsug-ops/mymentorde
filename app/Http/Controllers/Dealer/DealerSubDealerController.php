<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\ConsentRecord;
use App\Models\Dealer;
use App\Models\DealerStudentRevenue;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Bölge bayisinin alt bayi yönetimi (2 seviye hiyerarşi).
 * Yalnızca 'dealer.regional' middleware'inden geçen bölge bayileri erişir.
 */
class DealerSubDealerController extends Controller
{
    use DealerPortalTrait;

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $data   = $this->baseData($request);
        $parent = $data['dealer'];

        $children = $parent
            ? $parent->children()->orderBy('name')->get()
            : collect();

        // Her alt bayi için agregat performans (lead/dönüşüm/kazanç).
        $childCodes = $children->pluck('code')->map(fn ($c) => strtoupper(trim((string) $c)))->all();

        $leadCounts = empty($childCodes) ? collect() : GuestApplication::query()
            ->whereIn('dealer_code', $childCodes)
            ->selectRaw('dealer_code, COUNT(*) as total, SUM(CASE WHEN converted_student_id IS NOT NULL AND converted_student_id <> "" THEN 1 ELSE 0 END) as converted')
            ->groupBy('dealer_code')
            ->get()
            ->keyBy(fn ($r) => strtoupper((string) $r->dealer_code));

        $revAgg = empty($childCodes) ? collect() : DealerStudentRevenue::query()
            ->whereIn('dealer_id', $childCodes)
            ->selectRaw('dealer_id, COALESCE(SUM(total_earned),0) as earned, COALESCE(SUM(total_pending),0) as pending')
            ->groupBy('dealer_id')
            ->get()
            ->keyBy(fn ($r) => strtoupper((string) $r->dealer_id));

        $rows = $children->map(function (Dealer $c) use ($leadCounts, $revAgg) {
            $code = strtoupper((string) $c->code);
            $lc   = $leadCounts->get($code);
            $rv   = $revAgg->get($code);
            return [
                'dealer'    => $c,
                'leads'     => (int) ($lc->total ?? 0),
                'converted' => (int) ($lc->converted ?? 0),
                'earned'    => (float) ($rv->earned ?? 0),
                'pending'   => (float) ($rv->pending ?? 0),
            ];
        });

        return view('dealer.sub-dealers.index', $data + ['rows' => $rows]);
    }

    public function create(Request $request): View
    {
        $data = $this->baseData($request);
        return view('dealer.sub-dealers.create', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data   = $this->baseData($request);
        $parent = $data['dealer'];
        abort_if(!$parent || !$parent->isRegional(), 403, 'Yalnizca bolge bayileri alt bayi olusturabilir.');

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'whatsapp'       => ['nullable', 'string', 'max:50'],
            'data_consent'   => ['accepted'],
        ], [
            'data_consent.accepted' => 'Alt bayinin lead verilerini görüntüleme onayını kabul etmelisiniz.',
            'email.unique'          => 'Bu e-posta zaten kayıtlı.',
        ]);

        $code = $this->generateSubDealerCode((string) $parent->dealer_type_code);

        $sub = Dealer::query()->create([
            'company_id'       => $parent->company_id,
            'parent_dealer_id' => $parent->id,
            'code'             => $code['code'],
            'internal_sequence'=> $code['internal_sequence'],
            'name'             => trim($validated['name']),
            'email'            => trim($validated['email']),
            'phone'            => $validated['phone'] ?? null,
            'whatsapp'         => $validated['whatsapp'] ?? null,
            'dealer_type_code' => $parent->dealer_type_code, // alt bayi üst bayi ile aynı tip
            'is_active'        => true,
            'is_archived'      => false,
        ]);

        // Alt bayi login hesabı (rol=dealer, dealer_code eşleşir) + şifre belirleme daveti.
        $user = User::query()->create([
            'company_id'  => $parent->company_id,
            'name'        => trim($validated['name']),
            'email'       => trim($validated['email']),
            'role'        => User::ROLE_DEALER,
            'dealer_code' => $sub->code,
            'is_active'   => true,
            'password'    => Hash::make(Str::random(40)),
        ]);

        try {
            Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $e) {
            // davet maili gönderilemese de hesap oluştu; manager/bölge bayisi tekrar tetikleyebilir
        }

        // KVKK: bölge bayisinin alt bayi verisine erişim onayı kaydı.
        try {
            ConsentRecord::query()->create([
                'company_id'   => $parent->company_id,
                'user_id'      => $request->user()?->id,
                'consent_type' => 'dealer_subdealer_data_access',
                'version'      => '1.0',
                'ip_address'   => $request->ip(),
                'user_agent'   => substr((string) $request->userAgent(), 0, 500),
                'accepted_at'  => now(),
            ]);
        } catch (\Throwable $e) {
        }

        try {
            $this->eventLogService->log(
                'dealer_subdealer_created',
                'dealer',
                (string) $sub->id,
                "Alt bayi olusturuldu: {$sub->code} (ust bayi: {$parent->code})",
                [
                    'parent_dealer_code' => $parent->code,
                    'sub_dealer_code'    => $sub->code,
                    'sub_dealer_email'   => $sub->email,
                ],
                $request->user()?->email,
                (int) $parent->company_id,
            );
        } catch (\Throwable $e) {
        }

        $this->forgetDealerStatsCache((string) $parent->code);

        return redirect('/dealer/sub-dealers')
            ->with('status', "Alt bayi oluşturuldu: {$sub->name} ({$sub->code}). Şifre belirleme daveti e-postayla gönderildi.");
    }

    /**
     * Alt bayi kodu üret (mevcut DealerController::generateDealerCode mantığıyla aynı,
     * 'S' öneki ile alt bayi olduğunu belli eder).
     */
    private function generateSubDealerCode(string $dealerTypeCode): array
    {
        $prefix = 'S' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $dealerTypeCode) ?: 'SUB', 0, 2));
        $base   = "{$prefix}-" . now()->format('y') . '-' . now()->format('m');
        $seq    = ((int) Dealer::query()->max('internal_sequence')) + 1;

        do {
            $token     = strtoupper(substr(hash('crc32b', "{$base}-{$seq}"), 0, 4));
            $candidate = "{$base}-{$token}";
            if (!Dealer::query()->where('code', $candidate)->exists()) {
                break;
            }
            $seq++;
        } while (true);

        return ['code' => $candidate, 'internal_sequence' => $seq];
    }
}

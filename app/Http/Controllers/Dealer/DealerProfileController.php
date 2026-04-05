<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\DealerPayoutRequest;
use App\Models\DealerStudentRevenue;
use App\Models\GuestApplication;
use App\Models\NotificationDispatch;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DealerProfileController extends Controller
{
    use DealerPortalTrait;

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function profile(Request $request)
    {
        $data        = $this->baseData($request);
        $prefs       = $this->prefs($request->user(), 'dealer_profile');
        $p           = $prefs;
        $displayName = old('display_name', $p['display_name'] ?? ($data['dealer']?->name ?? $request->user()?->name ?? ''));
        $companyName = old('company_name', $p['company_name'] ?? '');
        $phone       = old('phone',        $p['phone']        ?? '');
        $whatsapp    = old('whatsapp',     $p['whatsapp']     ?? '');
        $region      = old('region',       $p['region']       ?? '');
        $bio         = old('bio',          $p['bio']          ?? '');
        $workStatus  = old('work_status',  $p['work_status']  ?? '');

        return view('dealer.profile', $data + compact(
            'prefs', 'displayName', 'companyName', 'phone', 'whatsapp', 'region', 'bio', 'workStatus'
        ));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'phone'        => ['nullable', 'string', 'max:60'],
            'whatsapp'     => ['nullable', 'string', 'max:60'],
            'bio'          => ['nullable', 'string', 'max:2000'],
            'region'       => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'work_status'  => ['nullable', 'in:individual,fulltime,parttime,institution'],
        ]);

        $this->savePrefs($request->user()->id, 'dealer_profile', $validated);

        return back()->with('status', 'Profil kaydedildi.');
    }

    public function settings(Request $request)
    {
        $data  = $this->baseData($request);
        $prefs = $this->prefs($request->user(), 'dealer_settings');

        return view('dealer.settings', $data + ['prefs' => $prefs]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'preferred_locale' => ['nullable', 'in:tr,de,en'],
            'notify_email'     => ['nullable', 'boolean'],
            'notify_whatsapp'  => ['nullable', 'boolean'],
            'notify_inapp'     => ['nullable', 'boolean'],
        ]);
        $validated['notify_email']    = (bool) $request->boolean('notify_email');
        $validated['notify_whatsapp'] = (bool) $request->boolean('notify_whatsapp');
        $validated['notify_inapp']    = (bool) $request->boolean('notify_inapp');

        $this->savePrefs($request->user()->id, 'dealer_settings', $validated);

        return back()->with('status', 'Ayarlar kaydedildi.');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Oturum bulunamadi.');

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->max(128)],
        ]);

        if (!Hash::check((string) $data['current_password'], (string) $user->password)) {
            return redirect('/dealer/settings')->withErrors(['current_password' => 'Mevcut şifre hatalı.']);
        }

        $user->password = Hash::make((string) $data['new_password']);
        $user->save();

        return redirect('/dealer/settings')->with('status', 'Şifre güncellendi.');
    }

    public function dataExport(Request $request)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing.');

        $user       = $request->user();
        $dealerCode = $data['dealerCode'];

        $leads    = GuestApplication::query()->where('dealer_code', $dealerCode)->latest()
            ->get(['id', 'tracking_token', 'first_name', 'last_name', 'email', 'phone',
                   'application_type', 'lead_status', 'lead_source', 'converted_student_id',
                   'selected_package_code', 'contract_status', 'created_at']);
        $revenues = DealerStudentRevenue::query()->where('dealer_id', $dealerCode)
            ->get(['student_id', 'dealer_type', 'total_earned', 'total_pending', 'updated_at']);
        $payouts  = DealerPayoutRequest::query()->where('dealer_code', $dealerCode)
            ->get(['id', 'amount', 'currency', 'status', 'requested_by_email', 'created_at', 'paid_at']);

        $export = [
            'export_date'     => now()->toIso8601String(),
            'dealer_code'     => $dealerCode,
            'user'            => ['email' => $user->email, 'name' => $user->name],
            'dealer'          => $data['dealer'] ? ['name' => $data['dealer']->name, 'type' => $data['dealer']->dealer_type_code, 'is_active' => $data['dealer']->is_active] : null,
            'stats'           => $data['dealerStats'],
            'leads'           => $leads->map(fn ($g) => [
                'id' => $g->id, 'tracking_token' => $g->tracking_token, 'first_name' => $g->first_name,
                'last_name' => $g->last_name, 'email' => $g->email, 'phone' => $g->phone,
                'application_type' => $g->application_type, 'lead_status' => $g->lead_status,
                'lead_source' => $g->lead_source, 'converted_student_id' => $g->converted_student_id,
                'selected_package_code' => $g->selected_package_code, 'contract_status' => $g->contract_status,
                'created_at' => optional($g->created_at)->toIso8601String(),
            ])->values(),
            'revenues'        => $revenues->map(fn ($r) => ['student_id' => $r->student_id, 'dealer_type' => $r->dealer_type, 'total_earned' => $r->total_earned, 'total_pending' => $r->total_pending, 'updated_at' => optional($r->updated_at)->toIso8601String()])->values(),
            'payout_requests' => $payouts->map(fn ($p) => ['id' => $p->id, 'amount' => $p->amount, 'currency' => $p->currency, 'status' => $p->status, 'email' => $p->requested_by_email, 'created_at' => optional($p->created_at)->toIso8601String(), 'paid_at' => optional($p->paid_at)->toIso8601String()])->values(),
        ];

        $filename = 'dealer-data-'.$dealerCode.'-'.now()->format('Ymd').'.json';

        return response()->json($export, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function notifications(Request $request)
    {
        $data  = $this->baseData($request);
        $user  = $request->user();
        $q     = trim((string) $request->query('q', ''));
        $cat   = trim((string) $request->query('category', ''));

        $notifications = NotificationDispatch::query()
            ->where('user_id', $user?->id)
            ->when($q !== '', fn ($qr) => $qr->where(fn ($x) =>
                $x->where('subject', 'like', "%{$q}%")->orWhere('message_body', 'like', "%{$q}%")))
            ->when($cat !== '', fn ($qr) => $qr->where('category', $cat))
            ->orderByDesc('queued_at')
            ->paginate(30)->withQueryString();

        $categories = NotificationDispatch::query()
            ->where('user_id', $user?->id)
            ->distinct()->pluck('category')->filter()->sort()->values();

        return view('dealer.notifications', $data + compact('notifications', 'categories', 'q', 'cat'));
    }
}

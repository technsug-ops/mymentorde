<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\DealerApplication;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public dealer application form — /satis-ortagi/basvuru
 * Form doldur → onay bekle → manager onaylar → dealer/user oluşur.
 */
class DealerApplicationController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function create(Request $request): View
    {
        // UTM prefill — landing'den gelirken query string'te gelir
        return view('public.dealer-application', [
            'prefillUtm' => [
                'utm_source'   => $request->query('utm_source'),
                'utm_medium'   => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:180'],
            'phone'       => ['required', 'string', 'max:50'],
            'city'        => ['nullable', 'string', 'max:100'],
            'country'     => ['nullable', 'string', 'max:60'],
            'company_name'=> ['nullable', 'string', 'max:180'],
            'tax_number'  => ['nullable', 'string', 'max:60'],
            'business_type' => ['nullable', 'in:individual,company,freelance'],
            'preferred_plan' => ['required', 'in:lead_generation,freelance,unsure'],
            'expected_monthly_volume' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'education_experience' => ['nullable', 'boolean'],
            'experience_details' => ['nullable', 'string', 'max:2000'],
            'heard_from' => ['nullable', 'in:organic,social_media,referral,google,whatsapp,other'],
            'referrer_email' => ['nullable', 'email', 'max:180'],
            'motivation' => ['nullable', 'string', 'max:2000'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'consent' => ['accepted'], // KVKK/aydınlatma kabul
        ]);

        // Aynı email + son 7 gün içinde başvuru varsa duplicate say
        $exists = DealerApplication::where('email', $data['email'])
            ->where('created_at', '>=', now()->subDays(7))
            ->first();

        if ($exists) {
            return redirect()->route('public.dealer-application.success', ['ref' => $exists->id])
                ->with('info', 'Bu email ile yakın zamanda başvurduğunuz kayıt mevcut.');
        }

        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : null;

        $app = DealerApplication::create(array_merge($data, [
            'company_id' => $cid,
            'country' => $data['country'] ?? 'TR',
            'business_type' => $data['business_type'] ?? 'individual',
            'education_experience' => (bool) ($data['education_experience'] ?? false),
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]));

        // PostHog event
        try {
            $this->analytics->capture('dealer_application_submitted', [
                'application_id'   => $app->id,
                'preferred_plan'   => $app->preferred_plan,
                'business_type'    => $app->business_type,
                'expected_volume'  => $app->expected_monthly_volume,
                'has_experience'   => $app->education_experience,
                'utm_source'       => $app->utm_source,
                'utm_campaign'     => $app->utm_campaign,
                'heard_from'       => $app->heard_from,
                'company_id'       => $cid,
            ], 'dealer_app_' . $app->id);
        } catch (\Throwable) {}

        return redirect()->route('public.dealer-application.success', ['ref' => $app->id]);
    }

    public function success(Request $request): View
    {
        $ref = (int) $request->query('ref');
        $app = $ref ? DealerApplication::find($ref) : null;

        return view('public.dealer-application-success', [
            'app' => $app,
        ]);
    }
}

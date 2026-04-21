<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\SeniorBookingSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public booking landing — /randevu
 *   - Tüm public senior'ları listele (is_public=true & is_active=true)
 *   - Seçilen senior'un widget sayfasına yönlendir (/book/{slug})
 */
class BookingLandingController extends Controller
{
    public function index(Request $request): View
    {
        // Sadece is_public=true olan senior'lar bu listede
        $settings = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->where('is_public', true)
            ->whereNotNull('public_slug')
            ->orderBy('display_name')
            ->get();

        $seniorIds = $settings->pluck('senior_user_id')->all();
        $seniors = User::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $seniorIds)
            ->get(['id', 'name', 'email', 'bio', 'photo_url', 'expertise_tags', 'senior_type'])
            ->keyBy('id');

        $list = $settings->map(function (SeniorBookingSetting $s) use ($seniors) {
            $u = $seniors->get($s->senior_user_id);
            return [
                'slug'          => $s->public_slug,
                'display_name'  => $s->display_name ?: ($u?->name ?? 'Danışman'),
                'welcome'       => $s->welcome_message,
                'slot_duration' => $s->slot_duration,
                'timezone'      => $s->timezone,
                'name'          => $u?->name,
                'photo_url'     => $u?->photo_url,
                'bio'           => $u?->bio,
                'expertise'     => is_array($u?->expertise_tags) ? $u->expertise_tags : [],
            ];
        })->values();

        return view('booking.public.landing', [
            'seniors' => $list,
        ]);
    }
}

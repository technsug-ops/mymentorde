<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\Dealer;
use App\Rules\ValidFileMagicBytes;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Bayi mini-site ayarları (white-label). Bayi logo/renk/metinleri ve slug önerisini
 * düzenler; yayına alma (site_enabled) manager onayına bağlıdır.
 */
class DealerMiniSiteController extends Controller
{
    use DealerPortalTrait;

    /** Mini-site slug'ı olarak kullanılamayacak rezerve yollar. */
    private const RESERVED_SLUGS = [
        'admin', 'api', 'manager', 'dealer', 'apply', 'p', 'satis-ortagi', 'platform',
        'kayit', 'fiyatlar', 'pricing', 'login', 'randevu', 'uzman', 'go', 'promo',
        'share', 'brand', 'partner', 'signup', 'sss', 'uni-match',
    ];

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function edit(Request $request): View
    {
        $data = $this->baseData($request);
        return view('dealer.mini-site.edit', $data);
    }

    public function update(Request $request): RedirectResponse
    {
        $data   = $this->baseData($request);
        $dealer = $data['dealer'];
        abort_if(!$dealer instanceof Dealer, 403, 'Dealer not found');

        $validated = $request->validate([
            'public_slug'        => ['nullable', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9-]+$/',
                                     Rule::unique('dealers', 'public_slug')->ignore($dealer->id)],
            'site_accent_color'  => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'site_hero_title'    => ['nullable', 'string', 'max:160'],
            'site_hero_subtitle' => ['nullable', 'string', 'max:300'],
            'site_about_text'    => ['nullable', 'string', 'max:4000'],
            'site_phone'         => ['nullable', 'string', 'max:50'],
            'site_whatsapp'      => ['nullable', 'string', 'max:50'],
            'site_instagram'     => ['nullable', 'string', 'max:100'],
            'logo'               => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', new ValidFileMagicBytes()],
        ]);

        // Slug rezerve kontrolü
        if (!empty($validated['public_slug']) && in_array($validated['public_slug'], self::RESERVED_SLUGS, true)) {
            return back()->withErrors(['public_slug' => 'Bu slug rezerve, başka bir tane seçin.'])->withInput();
        }

        $payload = [
            'public_slug'        => $validated['public_slug'] ?: $dealer->public_slug,
            'site_accent_color'  => $validated['site_accent_color'] ?? null,
            'site_hero_title'    => $validated['site_hero_title'] ?? null,
            'site_hero_subtitle' => $validated['site_hero_subtitle'] ?? null,
            'site_about_text'    => $validated['site_about_text'] ?? null,
            'site_phone'         => $validated['site_phone'] ?? null,
            'site_whatsapp'      => $validated['site_whatsapp'] ?? null,
            'site_instagram'     => $validated['site_instagram'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            // Eski logoyu temizle
            if ($dealer->site_logo_path && Storage::disk('public')->exists($dealer->site_logo_path)) {
                Storage::disk('public')->delete($dealer->site_logo_path);
            }
            $path = $request->file('logo')->store('dealer-sites/' . Str::slug($dealer->code), 'public');
            $payload['site_logo_path'] = $path;
        }

        $dealer->update($payload);

        try {
            $this->eventLogService->log(
                'dealer_minisite_updated',
                'dealer',
                (string) $dealer->id,
                "Mini-site guncellendi: {$dealer->code}",
                ['slug' => $dealer->public_slug],
                $request->user()?->email,
                (int) $dealer->company_id,
            );
        } catch (\Throwable $e) {
        }

        $msg = 'Mini-site ayarların kaydedildi.';
        if (!$dealer->site_enabled) {
            $msg .= ' Yayına alınması için yönetici onayı gerekiyor.';
        }

        return redirect('/dealer/mini-site')->with('status', $msg);
    }
}

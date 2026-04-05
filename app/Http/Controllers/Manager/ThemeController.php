<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Support\PortalTheme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Support\SchemaCache;

class ThemeController extends Controller
{
    public function show()
    {
        $tableReady = SchemaCache::hasTable('marketing_admin_settings');
        $brand = $tableReady ? [
            'name'       => \App\Models\MarketingAdminSetting::getValue('brand_name', config('brand.name', 'MentorDE')),
            'accent'     => \App\Models\MarketingAdminSetting::getValue('brand_accent', config('brand.accent', 'DE')),
            'logo_url'   => \App\Models\MarketingAdminSetting::getValue('brand_logo_url', config('brand.logo_url', '')),
            'logo_height'=> (int) \App\Models\MarketingAdminSetting::getValue('brand_logo_height', config('brand.logo_height', 40)),
        ] : [
            'name' => config('brand.name', 'MentorDE'),
            'accent' => config('brand.accent', 'DE'),
            'logo_url' => config('brand.logo_url', ''),
            'logo_height' => (int) config('brand.logo_height', 40),
        ];
        return view('manager.theme', [
            'pageTitle'  => 'Theme Management',
            'theme'      => PortalTheme::resolve(),
            'tableReady' => $tableReady,
            'brand'      => $brand,
        ]);
    }

    public function update(Request $request)
    {
        if (!SchemaCache::hasTable('marketing_admin_settings')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Ayar tablosu bulunamadi. php artisan migrate calistirin.',
                ], Response::HTTP_CONFLICT);
            }

            return redirect('/manager/theme')->withErrors(['Ayar tablosu bulunamadi. php artisan migrate calistirin.']);
        }

        $rules = [
            'brand_primary'       => ['required', 'string', 'max:7'],
            'sidebar_text'        => ['required', 'string', 'max:7'],
            'bg'                  => ['required', 'string', 'max:7'],
            'surface'             => ['required', 'string', 'max:7'],
            'text'                => ['required', 'string', 'max:7'],
            'muted'               => ['required', 'string', 'max:7'],
            'line'                => ['required', 'string', 'max:7'],
            'ok'                  => ['required', 'string', 'max:7'],
            'warn'                => ['required', 'string', 'max:7'],
            'info'                => ['required', 'string', 'max:7'],
            'danger'              => ['required', 'string', 'max:7'],
            'radius'              => ['required', 'integer', 'min:4', 'max:20'],
            'font_size'           => ['required', 'integer', 'min:12', 'max:16'],
            'font_family'         => ['required', 'string', 'in:system,inter,roboto,opensans'],
        ];
        // Per-portal: font_size, accent ve hero_darkness
        foreach (PortalTheme::$portals as $p) {
            $rules["font_size_{$p}"]      = ['required', 'integer', 'min:12', 'max:36'];
            $rules["accent_{$p}"]         = ['required', 'string', 'max:7'];
            $rules["hero_darkness_{$p}"]  = ['required', 'integer', 'min:0', 'max:80'];
        }
        $data = $request->validate($rules);

        PortalTheme::persist($data, $request->user()?->id);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true], Response::HTTP_OK);
        }

        return redirect('/manager/theme')->with('status', 'Tema ayarlari kaydedildi.');
    }

    public function updateBrand(Request $request)
    {
        if (!SchemaCache::hasTable('marketing_admin_settings')) {
            return redirect('/manager/theme')->withErrors(['Ayar tablosu bulunamadi.']);
        }

        $data = $request->validate([
            'brand_name'        => ['required', 'string', 'max:60'],
            'brand_accent'      => ['nullable', 'string', 'max:20'],
            'brand_logo_url'    => ['nullable', 'url', 'max:500'],
            'brand_logo_height' => ['nullable', 'integer', 'min:20', 'max:120'],
        ]);

        $uid = $request->user()?->id;
        \App\Models\MarketingAdminSetting::setValue('brand_name',        $data['brand_name'],              $uid);
        \App\Models\MarketingAdminSetting::setValue('brand_accent',      $data['brand_accent'] ?? '',      $uid);
        \App\Models\MarketingAdminSetting::setValue('brand_logo_url',    $data['brand_logo_url'] ?? '',    $uid);
        \App\Models\MarketingAdminSetting::setValue('brand_logo_height', (string)($data['brand_logo_height'] ?? 40), $uid);

        return redirect('/manager/theme')->with('status', 'Marka ayarlari kaydedildi.');
    }
}


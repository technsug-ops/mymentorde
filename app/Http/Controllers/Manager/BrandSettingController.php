<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\MarketingAdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BrandSettingController extends Controller
{
    public function show()
    {
        $cid = auth()->user()?->company_id ?? 0;

        $brandName    = MarketingAdminSetting::where('company_id', $cid)
                            ->where('setting_key', 'brand_name')
                            ->value('setting_value') ?? config('brand.name', 'MentorDE');

        $brandLogoUrl = MarketingAdminSetting::where('company_id', $cid)
                            ->where('setting_key', 'brand_logo_url')
                            ->value('setting_value') ?? '';

        return view('manager.brand', compact('brandName', 'brandLogoUrl'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'brand_name'    => 'required|string|max:60',
            'brand_logo_url'=> 'nullable|url|max:500',
        ]);

        $cid = auth()->user()?->company_id ?? 0;

        MarketingAdminSetting::updateOrCreate(
            ['company_id' => $cid, 'setting_key' => 'brand_name'],
            ['setting_value' => $data['brand_name'], 'updated_by_user_id' => auth()->id()]
        );

        MarketingAdminSetting::updateOrCreate(
            ['company_id' => $cid, 'setting_key' => 'brand_logo_url'],
            ['setting_value' => $data['brand_logo_url'] ?? '', 'updated_by_user_id' => auth()->id()]
        );

        // Tüm portallardaki brand cache'ini temizle
        Cache::forget("brand_settings_{$cid}");

        return back()->with('status', 'Marka ayarları kaydedildi.');
    }
}

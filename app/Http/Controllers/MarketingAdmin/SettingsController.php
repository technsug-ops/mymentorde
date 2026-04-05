<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\MarketingAdminSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Support\SchemaCache;

class SettingsController extends Controller
{
    public function show()
    {
        $defaults = $this->defaults();
        $rows = collect();
        if (SchemaCache::hasTable('marketing_admin_settings')) {
            $rows = MarketingAdminSetting::query()->get(['setting_key', 'setting_value'])->keyBy('setting_key');
        }
        $settings = [];
        foreach ($defaults as $key => $meta) {
            $settings[$key] = [
                'label' => $meta['label'],
                'type' => $meta['type'],
                'options' => $meta['options'] ?? [],
                'value' => data_get($rows, "{$key}.setting_value.value", $meta['default']),
            ];
        }

        return view('marketing-admin.settings.index', [
            'pageTitle' => 'Panel Ayarlari',
            'title' => 'Ayarlar',
            'settings' => $settings,
            'tableReady' => SchemaCache::hasTable('marketing_admin_settings'),
        ]);
    }

    public function update(Request $request)
    {
        if (!SchemaCache::hasTable('marketing_admin_settings')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'marketing_admin_settings tablosu yok. php artisan migrate calistir.',
                ], Response::HTTP_CONFLICT);
            }
            return redirect('/mktg-admin/settings')->with('status', 'Ayar tablosu bulunamadi. `php artisan migrate` calistir.');
        }

        $defaults = $this->defaults();

        $rules = [];
        foreach ($defaults as $key => $meta) {
            $rule = ['nullable'];
            if ($meta['type'] === 'bool') {
                $rule[] = 'boolean';
            } elseif ($meta['type'] === 'int') {
                $rule[] = 'integer';
                $rule[] = 'min:0';
            } elseif ($meta['type'] === 'enum') {
                $rule[] = 'string';
                $rule[] = 'in:'.implode(',', (array) ($meta['options'] ?? []));
            } else {
                $rule[] = 'string';
                $rule[] = 'max:120';
            }
            $rules[$key] = $rule;
        }

        $data = $request->validate($rules);

        foreach ($defaults as $key => $meta) {
            $value = $data[$key] ?? null;
            if ($meta['type'] === 'bool') {
                $value = $request->boolean($key, (bool) $meta['default']);
            } elseif ($value === null || $value === '') {
                $value = $meta['default'];
            } elseif ($meta['type'] === 'int') {
                $value = (int) $value;
            }

            MarketingAdminSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => ['value' => $value],
                    'updated_by_user_id' => $request->user()?->id,
                ]
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true], Response::HTTP_OK);
        }

        return redirect('/mktg-admin/settings')->with('status', 'Ayarlar kaydedildi.');
    }

    private function defaults(): array
    {
        return [
            'default_locale' => [
                'label' => 'Varsayilan Dil',
                'type' => 'enum',
                'default' => 'tr',
                'options' => ['tr', 'de', 'en'],
            ],
            'default_timezone' => [
                'label' => 'Varsayilan Saat Dilimi',
                'type' => 'enum',
                'default' => 'Europe/Berlin',
                'options' => ['Europe/Berlin', 'Europe/Istanbul', 'UTC'],
            ],
            'dashboard_refresh_seconds' => [
                'label' => 'Dashboard Yenileme (sn)',
                'type' => 'int',
                'default' => 30,
            ],
            'daily_summary_hour' => [
                'label' => 'Gunluk Ozet Saati (0-23)',
                'type' => 'int',
                'default' => 9,
            ],
            'notify_on_new_lead' => [
                'label' => 'Yeni lead bildirimleri aktif',
                'type' => 'bool',
                'default' => true,
            ],
            'notify_on_campaign_error' => [
                'label' => 'Kampanya hata bildirimi aktif',
                'type' => 'bool',
                'default' => true,
            ],
            'brand_primary' => [
                'label' => 'Kurumsal Ana Renk',
                'type' => 'string',
                'default' => '#0a67d8',
            ],
            'brand_secondary' => [
                'label' => 'Kurumsal Ikinci Renk',
                'type' => 'string',
                'default' => '#10253e',
            ],
        ];
    }
}

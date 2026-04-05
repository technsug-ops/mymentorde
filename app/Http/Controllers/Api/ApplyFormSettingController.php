<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingAdminSetting;
use Illuminate\Http\Request;

class ApplyFormSettingController extends Controller
{
    private const SETTING_KEY_KVKK = 'apply_form.kvkk_text';

    public function show()
    {
        return response()->json([
            'kvkk_text' => $this->kvkkText(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'kvkk_text' => ['nullable', 'string', 'max:50000'],
        ]);

        $text = trim((string) ($data['kvkk_text'] ?? ''));

        $row = MarketingAdminSetting::query()->firstOrNew([
            'setting_key' => self::SETTING_KEY_KVKK,
        ]);

        $row->setting_value = ['text' => $text];
        $row->save();

        return response()->json([
            'ok' => true,
            'kvkk_text' => $this->kvkkText(),
        ]);
    }

    public static function kvkkText(): string
    {
        $row = MarketingAdminSetting::query()
            ->where('setting_key', self::SETTING_KEY_KVKK)
            ->first(['setting_value']);

        $value = data_get($row, 'setting_value', null);
        $text = is_array($value) ? (string) ($value['text'] ?? '') : (string) $value;
        $text = trim($text);

        if ($text !== '') {
            return $text;
        }

        return "KVKK AYDINLATMA METNI\n\n"
            ."Kisisel verileriniz MentorDE tarafindan basvuru surecinin yurutulmesi, iletisim kurulmasi "
            ."ve ilgili kurumlarla basvuru operasyonlarinin tamamlanmasi amaciyla islenir.\n\n"
            ."Detayli metin manager tarafindan Config > Guest bolumunden guncellenebilir.";
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }

        $now = now();

        // Tüm company_id'leri bul (0 dahil)
        $companyIds = DB::table('guest_registration_fields')
            ->select('company_id')
            ->distinct()
            ->pluck('company_id')
            ->all();

        if (empty($companyIds)) {
            return;
        }

        foreach ($companyIds as $cid) {

            // 1) high_school_type — high_school_name'den hemen sonra
            $alreadyHst = DB::table('guest_registration_fields')
                ->where('company_id', $cid)
                ->where('field_key', 'high_school_type')
                ->exists();

            if (!$alreadyHst) {
                $hsnRow = DB::table('guest_registration_fields')
                    ->where('company_id', $cid)
                    ->where('field_key', 'high_school_name')
                    ->first();

                if ($hsnRow) {
                    // high_school_grade ve sonrasını +10 kaydır
                    DB::table('guest_registration_fields')
                        ->where('company_id', $cid)
                        ->where('section_key', $hsnRow->section_key)
                        ->where('sort_order', '>', $hsnRow->sort_order)
                        ->increment('sort_order', 10);

                    DB::table('guest_registration_fields')->insert([
                        'company_id'    => $cid,
                        'section_key'   => $hsnRow->section_key,
                        'section_title' => $hsnRow->section_title,
                        'section_order' => $hsnRow->section_order,
                        'field_key'     => 'high_school_type',
                        'label'         => 'Lise türünüz',
                        'type'          => 'select',
                        'is_required'   => true,
                        'sort_order'    => (int) $hsnRow->sort_order + 5,
                        'max_length'    => 32,
                        'placeholder'   => null,
                        'help_text'     => '● Anadolu ve Fen Liseleri: Anadolu Lisesi, Fen Lisesi, Sosyal Bilimler Lisesi. ● Meslek Lisesi: MTAL, MESEM, Anadolu Teknik Prog.(ATP), İmam Hatip Lisesi, Anadolu İmam Hatip Lisesi, Güzel Sanatlar ve Spor Lisesi, Çok Programlı Anadolu Lisesi, Sağlık/Ticaret/Adalet/Turizm/Spor Meslek Lisesi. ● Açık Lise: Mesleki Açık Öğretim Lisesi (MAÖL), Açık Öğretim İmam Hatip Lisesi (AÖİHL), Açık Öğretim Lisesi (AOL).',
                        'options_json'  => json_encode([
                            ['value' => 'anadolu_fen', 'label' => 'Anadolu ve Fen Liseleri'],
                            ['value' => 'meslek',      'label' => 'Meslek Lisesi'],
                            ['value' => 'acik_lise',   'label' => 'Açık Lise'],
                        ], JSON_UNESCAPED_UNICODE),
                        'is_active'     => true,
                        'is_system'     => true,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }

            // 2) other_language — other_language_level'dan hemen önce
            $alreadyOl = DB::table('guest_registration_fields')
                ->where('company_id', $cid)
                ->where('field_key', 'other_language')
                ->exists();

            if (!$alreadyOl) {
                $ollRow = DB::table('guest_registration_fields')
                    ->where('company_id', $cid)
                    ->where('field_key', 'other_language_level')
                    ->first();

                if ($ollRow) {
                    DB::table('guest_registration_fields')->insert([
                        'company_id'    => $cid,
                        'section_key'   => $ollRow->section_key,
                        'section_title' => $ollRow->section_title,
                        'section_order' => $ollRow->section_order,
                        'field_key'     => 'other_language',
                        'label'         => 'Bildiğiniz diğer dil (varsa)',
                        'type'          => 'text',
                        'is_required'   => false,
                        'sort_order'    => (int) $ollRow->sort_order - 5,
                        'max_length'    => 120,
                        'placeholder'   => 'Örn: Fransızca, Rusça, Arapça',
                        'help_text'     => null,
                        'options_json'  => null,
                        'is_active'     => true,
                        'is_system'     => true,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }

            // 3) other_language_level label ve help_text güncelle
            DB::table('guest_registration_fields')
                ->where('company_id', $cid)
                ->where('field_key', 'other_language_level')
                ->update([
                    'label'      => 'Bu dilin seviyesi',
                    'help_text'  => 'Yukarıda bir dil yazdıysanız seviyesini seçiniz.',
                    'updated_at' => $now,
                ]);

            // 4) application_type options_json'a ausbildung ekle
            $appTypeRow = DB::table('guest_registration_fields')
                ->where('company_id', $cid)
                ->where('field_key', 'application_type')
                ->first();

            if ($appTypeRow) {
                $opts = is_string($appTypeRow->options_json) && $appTypeRow->options_json !== ''
                    ? json_decode($appTypeRow->options_json, true)
                    : [];

                if (!is_array($opts)) {
                    $opts = [];
                }

                $existingValues = array_column($opts, 'value');
                if (!in_array('ausbildung', $existingValues, true)) {
                    // master'dan sonra ekle
                    $newOpts = [];
                    foreach ($opts as $opt) {
                        $newOpts[] = $opt;
                        if ((string) ($opt['value'] ?? '') === 'master') {
                            $newOpts[] = ['value' => 'ausbildung', 'label' => 'Ausbildung (Mesleki Eğitim)'];
                        }
                    }
                    // master bulunamadıysa sona ekle
                    if (!in_array('ausbildung', array_column($newOpts, 'value'), true)) {
                        $newOpts[] = ['value' => 'ausbildung', 'label' => 'Ausbildung (Mesleki Eğitim)'];
                    }

                    DB::table('guest_registration_fields')
                        ->where('company_id', $cid)
                        ->where('field_key', 'application_type')
                        ->update([
                            'options_json' => json_encode($newOpts, JSON_UNESCAPED_UNICODE),
                            'updated_at'   => $now,
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }

        DB::table('guest_registration_fields')
            ->where('field_key', 'high_school_type')
            ->delete();

        DB::table('guest_registration_fields')
            ->where('field_key', 'other_language')
            ->delete();
    }
};

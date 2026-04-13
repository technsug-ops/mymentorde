<?php

namespace Database\Seeders;

use App\Models\GuestRegistrationField;
use Illuminate\Database\Seeder;

/**
 * "Eşinizle İlgili Bilgiler" section — marital_status=='married' seçilince
 * görünen conditional alanlar. Bu seeder her çalıştırıldığında idempotent:
 * var olan kayıtları güncellemez, eksik olanları ekler.
 *
 * Seeder çalıştırma zamanları:
 *   - İlk deploy sonrası: php artisan db:seed --class=GuestRegistrationSpouseFieldsSeeder
 *   - CI/CD'de DatabaseSeeder üzerinden otomatik
 */
class GuestRegistrationSpouseFieldsSeeder extends Seeder
{
    public function run(): void
    {
        $section = [
            'section_key'   => 'spouse_info',
            'section_title' => 'Eşinizle İlgili Bilgiler',
            'section_order' => 65,
        ];

        $fields = [
            ['field_key' => 'spouse_full_name',            'label' => 'Eşinizin adı soyadı',          'type' => 'text',   'is_required' => true,  'max_length' => 180, 'sort_order' => 1],
            ['field_key' => 'spouse_birth_date',           'label' => 'Eşinizin doğum tarihi',        'type' => 'date',   'is_required' => true,  'max_length' => 20,  'sort_order' => 2],
            ['field_key' => 'spouse_nationality',          'label' => 'Eşinizin uyruğu',              'type' => 'text',   'is_required' => true,  'max_length' => 120, 'sort_order' => 3],
            ['field_key' => 'spouse_occupation',           'label' => 'Eşinizin mesleği',             'type' => 'text',   'is_required' => true,  'max_length' => 120, 'sort_order' => 4],
            ['field_key' => 'marriage_date',               'label' => 'Nikah tarihi',                 'type' => 'date',   'is_required' => true,  'max_length' => 20,  'sort_order' => 5],
            ['field_key' => 'marriage_place',              'label' => 'Nikah yeri (şehir/ülke)',      'type' => 'text',   'is_required' => true,  'max_length' => 180, 'sort_order' => 6],
            [
                'field_key'   => 'spouse_currently_in_germany',
                'label'       => 'Eşiniz şu an Almanya\'da mı?',
                'type'        => 'select',
                'is_required' => true,
                'max_length'  => 10,
                'sort_order'  => 7,
                'options_json'=> json_encode([
                    ['value' => 'yes', 'label' => 'Evet'],
                    ['value' => 'no',  'label' => 'Hayır'],
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'field_key'   => 'has_children',
                'label'       => 'Çocuğunuz var mı?',
                'type'        => 'select',
                'is_required' => false,
                'max_length'  => 10,
                'sort_order'  => 8,
                'options_json'=> json_encode([
                    ['value' => 'yes', 'label' => 'Evet'],
                    ['value' => 'no',  'label' => 'Hayır'],
                ], JSON_UNESCAPED_UNICODE),
            ],
        ];

        // company_id=0 → "tüm şirketler için default" (yeni şirketler bu template'i devralır)
        // company_id>0 → override (manager panelinden eklenen şirket-spesifik alanlar)
        // Sadece default (company_id=0) set'ini ekle.
        foreach ($fields as $f) {
            GuestRegistrationField::firstOrCreate(
                [
                    'company_id' => 0,
                    'field_key'  => $f['field_key'],
                ],
                array_merge($f, [
                    'company_id'    => 0,
                    'section_key'   => $section['section_key'],
                    'section_title' => $section['section_title'],
                    'section_order' => $section['section_order'],
                    'is_active'     => true,
                    'is_system'     => true,
                ])
            );
        }

        // Eğer company_id=1 aktif company ise o da bu alanları alsın (mevcut kurulum için)
        $activeCompanyId = (int) \App\Models\Company::query()->where('is_active', true)->orderBy('id')->value('id');
        if ($activeCompanyId > 0) {
            foreach ($fields as $f) {
                GuestRegistrationField::firstOrCreate(
                    [
                        'company_id' => $activeCompanyId,
                        'field_key'  => $f['field_key'],
                    ],
                    array_merge($f, [
                        'company_id'    => $activeCompanyId,
                        'section_key'   => $section['section_key'],
                        'section_title' => $section['section_title'],
                        'section_order' => $section['section_order'],
                        'is_active'     => true,
                        'is_system'     => true,
                    ])
                );
            }
        }
    }
}

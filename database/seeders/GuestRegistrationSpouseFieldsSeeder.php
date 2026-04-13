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

        // NOT: Model'in `options_json` => 'array' cast'i var. Bu yüzden
        // burada ARRAY geçmeliyiz, JSON string değil. json_encode yaparsak
        // Eloquent tekrar encode eder → "[{...}]" double-stringify olur.
        $yesNo = [
            ['value' => 'yes', 'label' => 'Evet'],
            ['value' => 'no',  'label' => 'Hayır'],
        ];

        $fields = [
            ['field_key' => 'spouse_full_name',            'label' => 'Eşinizin adı soyadı',          'type' => 'text',   'is_required' => true,  'max_length' => 180, 'sort_order' => 1],
            ['field_key' => 'spouse_birth_date',           'label' => 'Eşinizin doğum tarihi',        'type' => 'date',   'is_required' => true,  'max_length' => 20,  'sort_order' => 2],
            ['field_key' => 'spouse_nationality',          'label' => 'Eşinizin uyruğu',              'type' => 'text',   'is_required' => true,  'max_length' => 120, 'sort_order' => 3],
            ['field_key' => 'spouse_occupation',           'label' => 'Eşinizin mesleği',             'type' => 'text',   'is_required' => true,  'max_length' => 120, 'sort_order' => 4],
            ['field_key' => 'marriage_date',               'label' => 'Nikah tarihi',                 'type' => 'date',   'is_required' => true,  'max_length' => 20,  'sort_order' => 5],
            ['field_key' => 'marriage_place',              'label' => 'Nikah yeri (şehir/ülke)',      'type' => 'text',   'is_required' => true,  'max_length' => 180, 'sort_order' => 6],
            [
                'field_key'    => 'spouse_currently_in_germany',
                'label'        => 'Eşiniz şu an Almanya\'da mı?',
                'type'         => 'select',
                'is_required'  => true,
                'max_length'   => 10,
                'sort_order'   => 7,
                'options_json' => $yesNo,
            ],
            [
                'field_key'    => 'has_children',
                'label'        => 'Çocuğunuz var mı?',
                'type'         => 'select',
                'is_required'  => false,
                'max_length'   => 10,
                'sort_order'   => 8,
                'options_json' => $yesNo,
            ],
            // has_children === 'yes' olduğunda görünür + zorunlu (client-side JS kontrolü).
            [
                'field_key'    => 'children_count',
                'label'        => 'Kaç çocuğunuz var?',
                'type'         => 'text',
                'is_required'  => false,
                'max_length'   => 2,
                'sort_order'   => 9,
                'placeholder'  => 'Örn: 2',
            ],
        ];

        // Aktif company_id'leri bul. BelongsToCompany trait yeni kayıtlarda
        // company_id'yi otomatik set ediyor (creating event), bu yüzden firstOrCreate
        // direkt company_id=0 ile çalışmıyor. Her aktif company için ayrı iterate.
        $companyIds = \App\Models\Company::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        // Fallback: hiç company yoksa default 0 tablosuna yaz
        if (empty($companyIds)) {
            $companyIds = [0];
        }

        foreach ($companyIds as $companyId) {
            foreach ($fields as $f) {
                // withoutGlobalScopes trait scope'undan kaçar, raw existence kontrolü
                $exists = GuestRegistrationField::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('field_key', $f['field_key'])
                    ->exists();
                if ($exists) {
                    continue;
                }
                $row = new GuestRegistrationField();
                $row->company_id    = $companyId;
                $row->section_key   = $section['section_key'];
                $row->section_title = $section['section_title'];
                $row->section_order = $section['section_order'];
                $row->is_active     = true;
                $row->is_system     = true;
                foreach ($f as $k => $v) {
                    $row->{$k} = $v;
                }
                $row->save();
            }
        }
    }
}

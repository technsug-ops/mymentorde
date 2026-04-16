<?php

namespace Database\Seeders;

use App\Models\GuestRegistrationField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            'section_order' => 15,
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

        // Spouse alanlarını company_id=0 (defaults) olarak ekle. Bu pattern'de:
        //   - company_id=0 → "tüm şirketler için varsayılan alanlar"
        //   - company_id=X → sadece X şirketine özel override
        // Service read sırasında company_id=X boşsa company_id=0'a fallback yapar.
        // (Eskiden company_id=1'e ekliyorduk, bu diğer section'ların company_id=0'dan
        //  gelmesini engelliyordu — new_guest form'unda sadece spouse gözüküyordu.)
        //
        // BelongsToCompany trait "creating" event'i company_id=0'ı boş sayıp otomatik
        // override ediyor. Bunu önlemek için doğrudan DB::table insert kullanıyoruz.
        $inserted = 0;
        foreach ($fields as $f) {
            $exists = DB::table('guest_registration_fields')
                ->where('company_id', 0)
                ->where('field_key', $f['field_key'])
                ->exists();
            if ($exists) {
                continue;
            }
            $insertData = [
                'company_id'    => 0,
                'section_key'   => $section['section_key'],
                'section_title' => $section['section_title'],
                'section_order' => $section['section_order'],
                'is_active'     => true,
                'is_system'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
            foreach ($f as $k => $v) {
                // options_json array ise JSON'a çevir (DB::table Eloquent cast yapmaz)
                if ($k === 'options_json' && is_array($v)) {
                    $insertData[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
                } else {
                    $insertData[$k] = $v;
                }
            }
            DB::table('guest_registration_fields')->insert($insertData);
            $inserted++;
        }
        echo 'GuestRegistrationSpouseFieldsSeeder: inserted ' . $inserted . ' rows at company_id=0' . PHP_EOL;
    }
}

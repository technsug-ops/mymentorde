<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('lead_scoring_rules')->insert([
            // Behavioral
            ['action_code' => 'portal_login',           'category' => 'behavioral',   'label' => 'Portal girişi',               'points' => 2,   'max_per_day' => 1,    'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'form_completed',          'category' => 'behavioral',   'label' => 'Kayıt formu tamamlandı',      'points' => 15,  'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'document_uploaded',       'category' => 'behavioral',   'label' => 'Belge yüklendi',              'points' => 10,  'max_per_day' => null, 'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'package_page_viewed',     'category' => 'behavioral',   'label' => 'Paket sayfası görüntülendi',  'points' => 5,   'max_per_day' => 1,    'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'package_selected',        'category' => 'behavioral',   'label' => 'Paket seçildi',               'points' => 20,  'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'contract_requested',      'category' => 'behavioral',   'label' => 'Sözleşme talep edildi',       'points' => 25,  'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'ticket_created',          'category' => 'behavioral',   'label' => 'Ticket açıldı',               'points' => 3,   'max_per_day' => null, 'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'profile_completed',       'category' => 'behavioral',   'label' => 'Profil tamamlandı',           'points' => 5,   'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'email_opened',            'category' => 'behavioral',   'label' => 'Email açıldı',                'points' => 1,   'max_per_day' => null, 'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'email_clicked',           'category' => 'behavioral',   'label' => 'Email linkine tıklandı',      'points' => 3,   'max_per_day' => null, 'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'event_registered',        'category' => 'behavioral',   'label' => 'Etkinliğe kayıt',             'points' => 5,   'max_per_day' => null, 'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'content_viewed',          'category' => 'behavioral',   'label' => 'CMS içerik okundu',           'points' => 1,   'max_per_day' => 3,    'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            // Demographic
            ['action_code' => 'german_b1_plus',          'category' => 'demographic',  'label' => 'Almanca B1+',                 'points' => 10,  'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'german_a1_a2',            'category' => 'demographic',  'label' => 'Almanca A1-A2',               'points' => 5,   'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'high_school_70plus',      'category' => 'demographic',  'label' => 'Lise notu 70+',               'points' => 5,   'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'uni_gpa_2_5plus',         'category' => 'demographic',  'label' => 'Üniversite GPA 2.5+',         'points' => 5,   'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'passport_exists',         'category' => 'demographic',  'label' => 'Pasaport mevcut',             'points' => 5,   'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'sperrkonto_info',         'category' => 'demographic',  'label' => 'Bloke hesap bilgisi var',     'points' => 10,  'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'country_turkey',          'category' => 'demographic',  'label' => 'Başvuru ülkesi: Türkiye',     'points' => 3,   'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'age_18_25',               'category' => 'demographic',  'label' => 'Yaş 18-25',                  'points' => 3,   'max_per_day' => null, 'is_one_time' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            // Decay
            ['action_code' => 'decay_7_14d',             'category' => 'decay',        'label' => 'Hareketsizlik 7-14 gün',     'points' => -1,  'max_per_day' => 1,    'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'decay_15_30d',            'category' => 'decay',        'label' => 'Hareketsizlik 15-30 gün',    'points' => -2,  'max_per_day' => 1,    'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['action_code' => 'decay_30plus',            'category' => 'decay',        'label' => 'Hareketsizlik 30+ gün',      'points' => -3,  'max_per_day' => 1,    'is_one_time' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('lead_scoring_rules')->truncate();
    }
};

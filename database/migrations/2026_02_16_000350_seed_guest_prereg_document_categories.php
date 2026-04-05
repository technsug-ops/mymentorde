<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('document_categories')) {
            return;
        }

        $now = now();
        $rows = [
            ['code' => 'passport', 'name_tr' => 'Pasaport', 'name_de' => 'Reisepass', 'name_en' => 'Passport', 'is_active' => 1, 'sort_order' => 10],
            ['code' => 'diploma', 'name_tr' => 'Diploma', 'name_de' => 'Diplom', 'name_en' => 'Diploma', 'is_active' => 1, 'sort_order' => 20],
            ['code' => 'transcript', 'name_tr' => 'Transkript', 'name_de' => 'Transcript', 'name_en' => 'Transcript', 'is_active' => 1, 'sort_order' => 30],
            ['code' => 'language_certificate', 'name_tr' => 'Dil Sertifikasi', 'name_de' => 'Sprachzertifikat', 'name_en' => 'Language Certificate', 'is_active' => 1, 'sort_order' => 40],
            ['code' => 'cv', 'name_tr' => 'Ozgecmis (CV)', 'name_de' => 'Lebenslauf', 'name_en' => 'CV', 'is_active' => 1, 'sort_order' => 50],
            ['code' => 'motivation_letter', 'name_tr' => 'Motivasyon Mektubu', 'name_de' => 'Motivationsschreiben', 'name_en' => 'Motivation Letter', 'is_active' => 1, 'sort_order' => 60],
        ];

        foreach ($rows as $row) {
            $existing = DB::table('document_categories')->where('code', $row['code'])->first();
            if ($existing) {
                DB::table('document_categories')
                    ->where('id', $existing->id)
                    ->update([
                        'name_tr' => $row['name_tr'],
                        'name_de' => $row['name_de'],
                        'name_en' => $row['name_en'],
                        'is_active' => $row['is_active'],
                        'sort_order' => $row['sort_order'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('document_categories')->insert($row + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep categories; no destructive rollback.
    }
};


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
            ['code' => 'DOC-DIPL', 'name_tr' => 'Diploma + Tercume', 'name_de' => 'Diplom + Ubersetzung', 'name_en' => 'Diploma + Translation', 'is_active' => 1, 'sort_order' => 110],
            ['code' => 'DOC-TRNS', 'name_tr' => 'Transkript + Tercume', 'name_de' => 'Transcript + Ubersetzung', 'name_en' => 'Transcript + Translation', 'is_active' => 1, 'sort_order' => 120],
            ['code' => 'DOC-UNWN', 'name_tr' => 'Universite Kazandi Belgesi', 'name_de' => 'Uni Zulassungsnachweis', 'name_en' => 'University Placement Proof', 'is_active' => 1, 'sort_order' => 130],
            ['code' => 'DOC-YKSP', 'name_tr' => 'YKS Yerlestirme Belgesi', 'name_de' => 'YKS Platzierungsnachweis', 'name_en' => 'YKS Placement Document', 'is_active' => 1, 'sort_order' => 140],
            ['code' => 'DOC-IDCR', 'name_tr' => 'Kimlik On-Arka', 'name_de' => 'Ausweis Vorder-Ruckseite', 'name_en' => 'Identity Front-Back', 'is_active' => 1, 'sort_order' => 150],
            ['code' => 'DOC-PASS', 'name_tr' => 'Pasaport Ilk 2 Sayfa', 'name_de' => 'Reisepass Erste 2 Seiten', 'name_en' => 'Passport First 2 Pages', 'is_active' => 1, 'sort_order' => 160],
            ['code' => 'DOC-CV__', 'name_tr' => 'CV', 'name_de' => 'Lebenslauf', 'name_en' => 'CV', 'is_active' => 1, 'sort_order' => 170],
            ['code' => 'DOC-MOTV', 'name_tr' => 'Motivasyon Mektubu', 'name_de' => 'Motivationsschreiben', 'name_en' => 'Motivation Letter', 'is_active' => 1, 'sort_order' => 180],
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
        // keep seeded categories
    }
};


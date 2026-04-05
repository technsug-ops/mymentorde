<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes Turkish characters in guest_required_documents and document_categories tables.
 * Previous seeds used ASCII-only names (no ş, ı, ğ, ü, ö, ç, İ etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── guest_required_documents ─────────────────────────────────────────
        if (Schema::hasTable('guest_required_documents')) {
            $fixes = [
                // bachelor rows
                ['old' => 'Lise Diplomasi + Almanca yeminli tercume',     'new' => 'Lise Diploması + Almanca yeminli tercüme'],
                ['old' => 'Transkript + Almanca yeminli tercume',          'new' => 'Transkript + Almanca yeminli tercüme'],
                ['old' => 'Universite Kazandi Belgesi + tercume',          'new' => 'Üniversite Kazandı Belgesi + tercüme'],
                ['old' => 'YKS Yerlestirme Belgesi',                       'new' => 'YKS Yerleştirme Belgesi'],
                // master rows
                ['old' => 'Universite Diplomasi + yeminli tercume',        'new' => 'Üniversite Diploması + yeminli tercüme'],
                ['old' => 'Universite Transkript + yeminli tercume',       'new' => 'Üniversite Transkript + yeminli tercüme'],
                ['old' => 'Universite Kabul/Referans Belgesi',             'new' => 'Üniversite Kabul/Referans Belgesi'],
                ['old' => 'Ek Akademik Yerlestirme Belgesi',               'new' => 'Ek Akademik Yerleştirme Belgesi'],
                // shared rows
                ['old' => 'Kimlik On-Arka Fotografi',                      'new' => 'Kimlik Ön-Arka Fotoğrafı'],
                ['old' => 'Pasaport Ilk 2 Sayfa',                          'new' => 'Pasaport İlk 2 Sayfa'],
            ];

            foreach ($fixes as $fix) {
                DB::table('guest_required_documents')
                    ->where('name', $fix['old'])
                    ->update(['name' => $fix['new']]);
            }

            // Fix description fields
            DB::table('guest_required_documents')
                ->where('description', 'like', '% icin zorunlu')
                ->get(['id', 'description'])
                ->each(function ($row) {
                    DB::table('guest_required_documents')
                        ->where('id', $row->id)
                        ->update(['description' => str_replace(' icin zorunlu', ' için zorunlu', $row->description)]);
                });
        }

        // ── document_categories (name_tr column) ─────────────────────────────
        if (Schema::hasTable('document_categories')) {
            $catFixes = [
                ['old' => 'Dil Sertifikasi',   'new' => 'Dil Sertifikası'],
                ['old' => 'Ozgecmis (CV)',      'new' => 'Özgeçmiş (CV)'],
                ['old' => 'Diploma + Tercume',  'new' => 'Diploma + Tercüme'],
                ['old' => 'Transkript + Tercume', 'new' => 'Transkript + Tercüme'],
                ['old' => 'Universite Kazandi Belgesi', 'new' => 'Üniversite Kazandı Belgesi'],
                ['old' => 'YKS Yerlestirme Belgesi',    'new' => 'YKS Yerleştirme Belgesi'],
                ['old' => 'Kimlik On-Arka',              'new' => 'Kimlik Ön-Arka'],
                ['old' => 'Pasaport Ilk 2 Sayfa',       'new' => 'Pasaport İlk 2 Sayfa'],
            ];

            foreach ($catFixes as $fix) {
                DB::table('document_categories')
                    ->where('name_tr', $fix['old'])
                    ->update(['name_tr' => $fix['new']]);
            }
        }
    }

    public function down(): void
    {
        // Not reversible — no-op.
    }
};

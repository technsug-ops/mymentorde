<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Belge isimlerinden '+ Tercüme', '+ Almanca yeminli tercüme', '+ tercüme'
 * gibi son ekleri çıkar. User feedback: belgelerimde tercüme talebi olmasın.
 *
 * Etkilenen tablolar:
 * - document_categories.name_tr
 * - guest_required_documents.name (varsa — tablo legacy)
 */
return new class extends Migration {
    public function up(): void
    {
        // Pattern listesi — string match'i, normalize hâli, ve hedef
        $renames = [
            // DocumentCategory.name_tr için
            ['table' => 'document_categories', 'col' => 'name_tr',
                'find' => 'Diploma + Tercüme',     'replace' => 'Diploma'],
            ['table' => 'document_categories', 'col' => 'name_tr',
                'find' => 'Transkript + Tercüme', 'replace' => 'Transkript'],
            ['table' => 'document_categories', 'col' => 'name_tr',
                'find' => 'Diploma + Tercume',     'replace' => 'Diploma'],
            ['table' => 'document_categories', 'col' => 'name_tr',
                'find' => 'Transkript + Tercume', 'replace' => 'Transkript'],
            // guest_required_documents.name için (ASCII varyantları)
            ['table' => 'guest_required_documents', 'col' => 'name',
                'find' => 'Lise Diplomasi + Almanca yeminli tercume', 'replace' => 'Lise Diplomasi'],
            ['table' => 'guest_required_documents', 'col' => 'name',
                'find' => 'Universite Diplomasi + yeminli tercume',   'replace' => 'Universite Diplomasi'],
            ['table' => 'guest_required_documents', 'col' => 'name',
                'find' => 'Transkript + Almanca yeminli tercume',     'replace' => 'Transkript'],
            ['table' => 'guest_required_documents', 'col' => 'name',
                'find' => 'Universite Transkript + yeminli tercume',  'replace' => 'Universite Transkripti'],
            ['table' => 'guest_required_documents', 'col' => 'name',
                'find' => 'Universite Kazandi Belgesi + tercume',     'replace' => 'Universite Kazandi Belgesi'],

            // guest_required_documents.name için (Türkçe karakter varyantları — runtime'da güncellenmiş)
            ['table' => 'guest_required_documents', 'col' => 'name',
                'find' => 'Lise Diploması + Almanca yeminli tercüme', 'replace' => 'Lise Diploması'],
            ['table' => 'guest_required_documents', 'col' => 'name',
                'find' => 'Üniversite Diploması + yeminli tercüme',   'replace' => 'Üniversite Diploması'],
            ['table' => 'guest_required_documents', 'col' => 'name',
                'find' => 'Üniversite Kazandı Belgesi + tercüme',     'replace' => 'Üniversite Kazandı Belgesi'],
        ];

        foreach ($renames as $r) {
            if (!Schema::hasTable($r['table']) || !Schema::hasColumn($r['table'], $r['col'])) {
                continue;
            }
            DB::table($r['table'])
                ->where($r['col'], $r['find'])
                ->update([$r['col'] => $r['replace']]);
        }

        // Yeminli Tercüme kategorisini deactive et — artık ayrıca talep edilmiyor
        if (Schema::hasTable('document_categories') && Schema::hasColumn('document_categories', 'is_active')) {
            DB::table('document_categories')
                ->where('code', 'DOC-BGLT')
                ->update(['is_active' => false]);
        }
    }

    public function down(): void
    {
        // Geri yüklenmesi gerekirse seed migration tekrar çalıştırılabilir.
        // Bu down() bir no-op — eski isimleri geri getirmiyoruz.
    }
};

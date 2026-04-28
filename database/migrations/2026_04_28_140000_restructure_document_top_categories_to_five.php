<?php

use App\Models\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Belge kategorilerini 5'li yeni yapıya geçirir:
 *   uni_assist / vize / dil_okulu / yurt / diger
 *
 * - guest_required_documents tablosuna top_category_code kolonu ekler
 *   (override path: bir belge birden fazla kategoriye taglenebilsin).
 * - document_categories.top_category_code'u eski → yeni haritalama ile günceller.
 * - guest_required_documents.top_category_code'u kategori lookup'tan doldurur.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1) Kolonu ekle
        if (Schema::hasTable('guest_required_documents')
            && !Schema::hasColumn('guest_required_documents', 'top_category_code')) {
            Schema::table('guest_required_documents', function (Blueprint $table) {
                $table->string('top_category_code', 60)->nullable()->after('category_code');
                $table->index('top_category_code', 'gr_doc_top_cat_idx');
            });
        }

        // 2) document_categories.top_category_code remap
        if (Schema::hasTable('document_categories')) {
            $rows = DB::table('document_categories')
                ->select('id', 'top_category_code')
                ->get();
            foreach ($rows as $row) {
                $newCode = DocumentCategory::normalizeTopCategoryCode($row->top_category_code);
                if ($newCode !== (string) $row->top_category_code) {
                    DB::table('document_categories')
                        ->where('id', $row->id)
                        ->update(['top_category_code' => $newCode, 'updated_at' => now()]);
                }
            }
        }

        // 3) guest_required_documents.top_category_code'u kategori lookup'tan doldur
        if (Schema::hasTable('guest_required_documents') && Schema::hasTable('document_categories')) {
            $cats = DB::table('document_categories')
                ->pluck('top_category_code', 'code');
            $rows = DB::table('guest_required_documents')
                ->select('id', 'category_code', 'top_category_code')
                ->get();
            foreach ($rows as $row) {
                if (!empty($row->top_category_code)) continue;
                $resolved = DocumentCategory::normalizeTopCategoryCode(
                    $cats[$row->category_code] ?? null
                );
                DB::table('guest_required_documents')
                    ->where('id', $row->id)
                    ->update(['top_category_code' => $resolved, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('guest_required_documents')
            && Schema::hasColumn('guest_required_documents', 'top_category_code')) {
            Schema::table('guest_required_documents', function (Blueprint $table) {
                $table->dropIndex('gr_doc_top_cat_idx');
                $table->dropColumn('top_category_code');
            });
        }
        // Top category remap geri alınmaz — eski 8'li yapıya manuel restore gerekir.
    }
};

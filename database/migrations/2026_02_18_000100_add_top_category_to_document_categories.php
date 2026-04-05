<?php

use App\Models\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_categories')) {
            return;
        }

        Schema::table('document_categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_categories', 'top_category_code')) {
                $table->string('top_category_code', 64)
                    ->default(DocumentCategory::defaultTopCategoryCode())
                    ->after('name_en');
                $table->index('top_category_code');
            }
        });

        $rows = DB::table('document_categories')->get(['id', 'code', 'name_tr']);
        foreach ($rows as $row) {
            $topCode = $this->detectTopCategory((string) $row->code, (string) ($row->name_tr ?? ''));
            DB::table('document_categories')
                ->where('id', $row->id)
                ->update([
                    'top_category_code' => $topCode,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_categories')) {
            return;
        }

        Schema::table('document_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('document_categories', 'top_category_code')) {
                $table->dropIndex(['top_category_code']);
                $table->dropColumn('top_category_code');
            }
        });
    }

    private function detectTopCategory(string $code, string $name): string
    {
        $codeUpper = strtoupper(trim($code));
        $nameLower = mb_strtolower(trim($name));

        if (in_array($codeUpper, ['DOC-IDCR', 'DOC-PASS'], true)) {
            return 'kisisel_dokumanlar';
        }
        if (in_array($codeUpper, ['DOC-DIPL', 'DOC-TRNS', 'DOC-UNWN', 'DOC-YKSP'], true)) {
            return 'uni_assist_dokumanlari';
        }
        if (str_contains($nameLower, 'vize')) {
            return 'vize_dokumanlari';
        }
        if (str_contains($nameLower, 'dil')) {
            return 'dil_okulu_dokumanlari';
        }
        if (str_contains($nameLower, 'ikamet') || str_contains($nameLower, 'oturum')) {
            return 'ikamet_kaydi_dokumanlari';
        }
        if (str_contains($nameLower, 'anmeldung') || str_contains($nameLower, 'burokrasi')) {
            return 'almanya_burokrasi_dokumanlari';
        }
        if (str_contains($nameLower, 'partner')) {
            return 'partner_dokumanlari';
        }

        return 'diger_dokumanlar';
    }
};


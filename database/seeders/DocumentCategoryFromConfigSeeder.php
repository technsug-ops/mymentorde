<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

/**
 * config/required_documents.php içinde tanımlı category_code'ların
 * document_categories tablosunda kayıtlı olmasını garanti eder.
 *
 * Neden: Guest belge upload formu bu config'den category_code alıyor,
 * controller ise DocumentCategory::where('code', ...)->first() yapıyor.
 * Eşleşme yoksa upload 404 veriyordu (B16).
 *
 * Idempotent: firstOrCreate kullanır, var olanlara dokunmaz.
 */
class DocumentCategoryFromConfigSeeder extends Seeder
{
    public function run(): void
    {
        $seen = [];
        $stages = config('required_documents', []);

        foreach ($stages as $items) {
            foreach ($items as $docs) {
                if (!is_array($docs)) {
                    continue;
                }
                foreach ($docs as $doc) {
                    $code = (string) ($doc['category_code'] ?? '');
                    if ($code === '' || isset($seen[$code])) {
                        continue;
                    }
                    $seen[$code] = [
                        'name' => (string) ($doc['name'] ?? $code),
                        'top'  => (string) ($doc['top_category_code'] ?? 'diger'),
                    ];
                }
            }
        }

        $order = (int) (DocumentCategory::max('sort_order') ?? 0);

        foreach ($seen as $code => $info) {
            DocumentCategory::firstOrCreate(
                ['code' => $code],
                [
                    'name_tr'           => $info['name'],
                    'name_de'           => $info['name'],
                    'name_en'           => $info['name'],
                    'top_category_code' => $info['top'],
                    'is_active'         => true,
                    'sort_order'        => ++$order,
                ]
            );
        }
    }
}

<?php

use App\Models\Marketing\CmsCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $categories = [
            [
                'code'           => 'success-stories',
                'name_tr'        => 'Başarı Hikayeleri',
                'name_de'        => 'Erfolgsgeschichten',
                'name_en'        => 'Success Stories',
                'description_tr' => 'MentorDE öğrencilerinin gerçek başarı hikayeleri ve deneyimleri. Her kayıt bir öğrenci yorumudur.',
                'sort_order'     => 10,
                'is_active'      => true,
            ],
            [
                'code'           => 'university-guide',
                'name_tr'        => 'Üniversite Rehberi',
                'name_de'        => 'Universitätsführer',
                'name_en'        => 'University Guide',
                'description_tr' => 'Almanya üniversiteleri hakkında rehber içerikler. Üniversite türleri, başvuru portalları ve şehir rehberleri.',
                'sort_order'     => 20,
                'is_active'      => true,
            ],
        ];

        foreach ($categories as $cat) {
            CmsCategory::query()->firstOrCreate(
                ['code' => $cat['code']],
                $cat
            );
        }
    }

    public function down(): void
    {
        CmsCategory::query()->whereIn('code', ['success-stories', 'university-guide'])->delete();
    }
};

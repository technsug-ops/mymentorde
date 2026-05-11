<?php

namespace App\Models\Marketing;

use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CmsContent extends Model
{
    protected $fillable = [
        'type', 'slug', 'content_code', 'title_tr', 'title_de', 'title_en',
        'summary_tr', 'summary_de', 'summary_en',
        'content_tr', 'content_de', 'content_en',
        'cover_image_url', 'cover_image_alt', 'gallery_urls',
        'video_url', 'video_thumbnail_url',
        'seo_meta_title_tr', 'seo_meta_title_de', 'seo_meta_title_en',
        'seo_meta_description_tr', 'seo_meta_description_de', 'seo_meta_description_en',
        'seo_keywords', 'seo_canonical_url', 'seo_og_image_url',
        'status', 'published_at', 'scheduled_at', 'archived_at',
        'is_featured', 'featured_order',
        'target_audience', 'target_student_types', 'linked_campaign_id',
        'category', 'tags',
        'current_revision', 'created_by', 'last_edited_by', 'approved_by',
        'author_name', 'author_role',
    ];

    protected $casts = [
        'gallery_urls' => 'array',
        'seo_keywords' => 'array',
        'target_student_types' => 'array',
        'tags' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function revisions()
    {
        return $this->hasMany(CmsContentRevision::class)->orderByDesc('revision_number');
    }

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'linked_campaign_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true)->orderBy('featured_order');
    }

    /**
     * "Yeni içerik" eşiği — son 14 gün içinde yayınlananlar listede öne çıkar +
     * card'da "🆕 Yeni" rozeti gösterilir. Tek yerde değiştir, her yer otomatik uyar.
     */
    public const NEW_WINDOW_DAYS = 14;

    /**
     * Kategori bazlı içerik kodu prefix'leri (örn UNI-001, BLOG-014).
     * Yeni kategori eklerken buraya prefix ekle, yeni blog'a otomatik atanır.
     */
    public const CATEGORY_CODE_PREFIXES = [
        'uni-content'     => 'UNI',
        'city-content'    => 'CITY',
        'careers'         => 'CAR',
        'culture-fun'     => 'CULT',
        'success-stories' => 'SUCC',
        'tips-tricks'     => 'TIPS',
        'student-life'    => 'STUD',
        'blog'            => 'BLOG',
        'rehber'          => 'REH',
        'duyuru'          => 'DUYR',
        'kurumsal'        => 'KURU',
    ];

    /**
     * AI içerik üretimi için kategori labelleri + prompt context'i.
     * Her kategori için Gemini'ye özel context iletilir → daha alakalı içerik.
     *
     * Format: code => [label (UI dropdown), context (AI prompt'a göm)]
     */
    public const AI_CATEGORY_CONTEXTS = [
        'uni-content'     => ['label' => '🏛 Üniversite Rehberi',     'context' => 'Spesifik bir Alman üniversitesinin başvuru süreci, kabul kriterleri, fakülteler, kontenjan, sıralama, kampüs'],
        'city-content'    => ['label' => '🏙 Şehir / Bölge',           'context' => 'Almanya\'da bir şehrin öğrenci yaşamı: kira, ulaşım, üniversiteler, gezi noktaları, sosyal hayat'],
        'careers'         => ['label' => '💼 Kariyer & Sektör',       'context' => 'Almanya\'da iş bulma, sektör rehberi, mezun kariyer yolu, maaş aralığı, çalışma vizesi (Bluekart), CV/Lebenslauf'],
        'culture-fun'     => ['label' => '🎭 Kültür & Eğlence',       'context' => 'Almanya\'da etkinlik, festival (Oktoberfest, Karnaval), müze, yemek/Alman mutfağı, gece hayatı, gezi, gelenekler'],
        'tips-tricks'     => ['label' => '💡 Pratik İpuçları',         'context' => 'Bürokratik işlemler (Anmeldung, Sperrkonto, BAföG, vize), günlük hayat hack\'leri, finans, sağlık sigortası, konaklama, banka hesabı'],
        'student-life'    => ['label' => '🎓 Öğrenci Hayatı',          'context' => 'Kampüs yaşamı, burslar (DAAD, Erasmus), yurtlar (WG, Studentenwohnheim), mensa, semester ticket, öğrenci kulüpleri, AStA'],
        'success-stories' => ['label' => '⭐ Başarı Hikayesi',         'context' => 'Türk öğrencinin Almanya\'da başarı hikayesi: öğrenim yolculuğu, kariyer geçişi, kişisel deneyim'],
        'blog'            => ['label' => '📝 Genel Blog',              'context' => 'Genel Almanya/öğrencilik konulu blog yazısı, herhangi bir spesifik kategoriye girmeyen'],
        'rehber'          => ['label' => '📋 Adım Adım Rehber',        'context' => 'Adım adım nasıl yapılır rehberi: vize başvurusu, uni-assist, APS, diploma denklik, Anerkennung, TestDaF/DSH'],
        'duyuru'          => ['label' => '📰 Duyuru',                  'context' => 'MentorDE platformuna dair veya genel öğrencilik dünyasına önemli duyuru/haber/etkinlik'],
        'kurumsal'        => ['label' => '🏢 Kurumsal',                'context' => 'MentorDE platformu, hizmetler, ekibimiz, misyon/vizyon, partner programı'],
    ];

    /**
     * Kategori için bir sonraki sıralı content_code üret.
     * Mevcut kayıtlardan en büyük numarayı bulup +1 yapar.
     */
    public static function generateContentCode(?string $category): string
    {
        $prefix = self::CATEGORY_CODE_PREFIXES[$category ?? ''] ?? 'GEN';
        $latest = self::query()
            ->where('content_code', 'like', $prefix . '-%')
            ->orderByDesc('content_code')
            ->value('content_code');
        $nextNum = 1;
        if ($latest !== null) {
            $nextNum = (int) substr($latest, strlen($prefix) + 1) + 1;
        }
        return $prefix . '-' . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::creating(function (CmsContent $content): void {
            if (empty($content->content_code)) {
                $content->content_code = self::generateContentCode($content->category);
            }
        });
    }

    public function getIsNewAttribute(): bool
    {
        if (!$this->published_at) return false;
        return $this->published_at->gte(now()->subDays(self::NEW_WINDOW_DAYS));
    }

    /**
     * content_tr rendered → blog detail sayfasında {!! $item->rendered_content_tr !!}.
     * - HTML zaten olan içerik (Quill çıktısı: <h2>, <p>, vb.) olduğu gibi gösterilir.
     * - Markdown olan içerik (**bold**, ## başlık, - liste) HTML'e çevrilir.
     * - HTML+Markdown karışık içerik için CommonMark default HTML pass-through.
     */
    public function getRenderedContentTrAttribute(): string
    {
        return $this->renderContent((string) ($this->content_tr ?? ''));
    }

    public function getRenderedContentDeAttribute(): string
    {
        return $this->renderContent((string) ($this->content_de ?? ''));
    }

    public function getRenderedContentEnAttribute(): string
    {
        return $this->renderContent((string) ($this->content_en ?? ''));
    }

    private function renderContent(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';
        // İçerik zaten HTML ise (<p>, <h2>, <ul>, vb. ile başlıyorsa) doğrudan döndür.
        if (preg_match('/^\s*<(p|h[1-6]|ul|ol|blockquote|div|article|section|figure)\b/i', $raw)) {
            return $raw;
        }
        // Markdown parse — Laravel built-in Str::markdown (League CommonMark).
        // HTML pass-through default açık, yani <h2>...</h2> markdown içinde de korunur.
        try {
            return \Illuminate\Support\Str::markdown($raw);
        } catch (\Throwable $e) {
            // Markdown parser hata verirse en azından \n → <br> ile sun
            return nl2br(e($raw));
        }
    }

    public function scopeForAudience(Builder $q, string $audience): Builder
    {
        return $q->whereIn('target_audience', ['all', $audience]);
    }

    public function title(string $lang = 'tr'): string
    {
        return $this->{"title_{$lang}"} ?? $this->title_tr;
    }

    public function incrementViews(bool $unique = false): void
    {
        $this->increment('metric_total_views');
        if ($unique) {
            $this->increment('metric_unique_views');
        }
    }
}

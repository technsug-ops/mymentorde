# MentorDE İçerik Hub — İçerik Üretim Rehberi

Bu rehber, `/guest/discover` ve `/student/discover` sayfalarına eklenecek içeriklerin
veritabanına nasıl kaydedileceğini açıklar. Her içerik bir `CmsContent` kaydıdır.

---

## Zorunlu Alanlar

| Alan | Tip | Açıklama |
|------|-----|----------|
| `slug` | string | URL dostu benzersiz ID — sadece küçük harf, rakam, tire. Türkçe harf YOK. |
| `title_tr` | string | Başlık (Türkçe) |
| `summary_tr` | string | 1-2 cümle özet — kart önizlemesinde görünür |
| `content_tr` | HTML | Tam içerik gövdesi (blog/deneyim için uzun HTML, video için embed kodu veya açıklama) |
| `type` | enum | Aşağıdaki tip listesine bak |
| `category` | enum | Aşağıdaki kategori listesine bak |
| `status` | string | Daima `published` yaz |
| `target_audience` | string | `guests` / `students` / `all` |

## Opsiyonel Ama Önemli Alanlar

| Alan | Tip | Açıklama |
|------|-----|----------|
| `video_url` | string | YouTube/Spotify/Google Slides embed URL'i (video, podcast, presentation tipleri için) |
| `cover_image_url` | string | Kapak görseli URL'i (yoksa otomatik gradient kullanılır) |
| `tags` | JSON array | Şehir slug'ları ekle → `["berlin", "münchen"]` → city-detail sayfasında otomatik görünür |
| `is_featured` | boolean | `true` → Ana sayfada "Öne Çıkanlar" bölümünde gösterilir (max 3) |
| `featured_order` | integer | Öne çıkan sıralama (1 = en üst) |
| `published_at` | datetime | Yayın tarihi — `now()` yaz |

---

## İçerik Tipleri (`type`)

| Değer | Türkçe Adı | Ne Zaman Kullan |
|-------|-----------|-----------------|
| `blog` | Blog Yazısı | Uzun formatlı metin içerik, rehber, analiz |
| `video_feature` | Video | YouTube videosu — `video_url` alanına embed URL |
| `podcast` | Podcast | Spotify/SoundCloud — `video_url` alanına embed URL |
| `presentation` | Sunum | Google Slides/Canva — `video_url` alanına public iframe URL |
| `experience` | Kişisel Deneyim | Birinci şahıs anlatım, "Almanya'da benim hikayem" tarzı |
| `career_guide` | Kariyer Rehberi | Sektör/meslek bazlı kariyer yol haritası |
| `tip` | Hızlı İpucu | 1-3 cümle pratik bilgi — kompakt kart |

---

## Kategoriler (`category`)

| Değer | Türkçe Adı | Renk | İçerik Örnekleri |
|-------|-----------|------|-----------------|
| `student-life` | Öğrenci Hayatı | Mavi | Mensa, WG, kampüs hayatı, adaptasyon |
| `culture-fun` | Kültür & Eğlence | Mor | Müzeler, festivaller, gece hayatı, doğa |
| `careers` | Kariyer | Yeşil | Sektörler, maaşlar, staj, iş ilanları |
| `tips-tricks` | Pratik İpuçları | Amber | Anmeldung, banka, sigorta, sperrkonto |
| `city-content` | Şehir Rehberleri | Cyan | Berlin, München, Hamburg, Frankfurt şehir rehberleri |
| `uni-content` | Üniversite Rehberleri | İndigo | TU, FH, LMU, uni başvuru rehberleri |
| `success-stories` | Başarı Hikayeleri | Altın | Mezun hikayeleri, "nasıl başardım" |

---

## Şehir Tag'leri (city-detail entegrasyonu)

`tags` alanına bu slug'ları ekle → İlgili şehir detay sayfasında otomatik görünür:

```
berlin, münchen, hamburg, frankfurt, köln, stuttgart, düsseldorf,
dresden, heidelberg, mannheim, nürnberg, bremen, hannover, leipzig,
freiburg, darmstadt, karlsruhe, aachen, bochum, münster
```

**Örnek:** Berlin hakkında içerik → `"tags": ["berlin"]`
**Çok şehirli içerik** → `"tags": ["münchen", "berlin"]`

---

## Seeder Formatı (PHP Array)

Her içerik şu formatta bir PHP array:

```php
[
    'slug'            => 'berlin-ogrenci-hayati-rehberi',
    'title_tr'        => 'Berlin\'de Öğrenci Hayatı: Kapsamlı Rehber',
    'summary_tr'      => 'Berlin\'in en popüler öğrenci mahallelerinden bütçe ipuçlarına kadar her şey.',
    'content_tr'      => '<h2>Berlin\'e Hoş Geldin</h2><p>Berlin, Almanya\'nın...</p>',
    'type'            => 'blog',
    'category'        => 'city-content',
    'status'          => 'published',
    'target_audience' => 'all',
    'tags'            => json_encode(['berlin']),
    'is_featured'     => false,
    'video_url'       => null,
    'cover_image_url' => null,
    'published_at'    => now()->subDays(3),
    'metric_total_views'           => rand(100, 1500),
    'metric_avg_read_time_seconds' => 8 * 60,  // 8 dakika
],
```

---

## Video/Podcast/Sunum için `video_url` Formatları

### YouTube (video_feature)
```
https://www.youtube.com/embed/VIDEO_ID
```
Örnek: `https://www.youtube.com/embed/dQw4w9WgXcQ`

### Spotify Podcast (podcast)
```
https://open.spotify.com/embed/episode/EPISODE_ID
```

### Google Slides (presentation)
```
https://docs.google.com/presentation/d/SLIDE_ID/embed?start=false&loop=false&delayms=3000
```

### Canva (presentation)
```
https://www.canva.com/design/DESIGN_ID/view?embed
```

---

## İçerik Uzunluk Rehberi

| Tip | `summary_tr` | `content_tr` | `metric_avg_read_time_seconds` |
|-----|-------------|-------------|-------------------------------|
| `blog` | 1-2 cümle | 600-1500 kelime HTML | 5-12 dk × 60 |
| `career_guide` | 1-2 cümle | 800-2000 kelime HTML | 8-15 dk × 60 |
| `experience` | 1-2 cümle | 400-900 kelime HTML | 4-8 dk × 60 |
| `tip` | 1-3 cümle (içeriğin kendisi) | 1-3 cümle | 1-2 dk × 60 |
| `video_feature` | 1-2 cümle | Video açıklaması (100-300 kelime) | 0 (null) |
| `podcast` | 1-2 cümle | Bölüm özeti (100-200 kelime) | 0 (null) |
| `presentation` | 1-2 cümle | Sunum notları (100-300 kelime) | 0 (null) |

---

## Örnek `content_tr` HTML Şablonları

### Blog / Kariyer Rehberi
```html
<h2>Giriş</h2>
<p>...</p>

<h2>1. Bölüm Başlığı</h2>
<p>...</p>
<ul>
  <li>Madde 1</li>
  <li>Madde 2</li>
</ul>

<h2>2. Bölüm Başlığı</h2>
<p>...</p>

<h2>Sonuç</h2>
<p>...</p>
```

### Kişisel Deneyim (experience)
```html
<blockquote>
  "Almanya'ya geldiğimde en çok şaşırdığım şey..."
</blockquote>
<p>Hikaye devamı...</p>
<h3>Ne Öğrendim?</h3>
<p>...</p>
```

### Hızlı İpucu (tip)
```html
<p><strong>Önemli:</strong> Anmeldung için randevu almayı unutmayın — ortalama 3-4 hafta bekleme süresi var.</p>
<p>Online randevu: berlin.de/bürgeramt</p>
```

---

## Seeder'a Ekleme

Hazırlanan içerikler şu dosyaya eklenir:
`database/seeders/ContentHubSeeder.php`

Çalıştırma:
```bash
php artisan db:seed --class=ContentHubSeeder
```

Tek bir içerik test etmek için tinker:
```bash
php artisan tinker
>>> \App\Models\Marketing\CmsContent::create([...])
```

---

## Claude'a Vereceğin Prompt Şablonu

```
Aşağıda MentorDE içerik hub sayfasının HTML'i ve içerik üretim rehberi var.

Bu sisteme eklemek için [KATEGORİ] kategorisinde [TİP] tipinde [SAYI] adet içerik üret.
Her içerik için şu alanları doldur:
- slug (ASCII, tire ile ayrılmış)
- title_tr
- summary_tr (1-2 cümle)
- content_tr (HTML, rehberdeki şablona uygun)
- type
- category
- tags (varsa şehir slug'ları)
- metric_avg_read_time_seconds

Konu: Almanya'da öğrenci/aday öğrenci için [KONU]
Hedef kitle: Türk öğrenciler
Dil: Türkçe, samimi ama bilgilendirici ton
```

---

## Mevcut İçerik Sayıları (2026-03-26 itibariyle)

| Kategori | Blog | Video | Podcast | Sunum | Deneyim | Kariyer | İpucu | Toplam |
|---------|------|-------|---------|-------|---------|---------|-------|--------|
| student-life | 2 | 1 | 1 | 1 | 2 | 0 | 1 | 8 |
| culture-fun | 3 | 0 | 0 | 0 | 0 | 0 | 0 | 3 |
| careers | 2 | 2 | 1 | 0 | 2 | 5 | 1 | 13 |
| tips-tricks | 6 | 1 | 1 | 1 | 0 | 0 | 1 | 10 |
| city-content | 2 | 2 | 0 | 1 | 2 | 2 | 1 | 10 |
| uni-content | 2 | 0 | 0 | 1 | 1 | 1 | 1 | 6 |
| success-stories | 2 | 1 | 1 | 1 | 2 | 1 | 1 | 9 |
| **Toplam** | **19** | **7** | **4** | **5** | **9** | **9** | **5** | **59** |

**Eksik / az olan kombinasyonlar (öncelikli üretim):**
- `culture-fun`: video, podcast, sunum, deneyim, kariyer, ipucu — hepsi 0
- `city-content`: podcast yok
- `uni-content`: video, podcast yok
- `careers`: sunum yok

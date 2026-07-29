# Partner Sitesine Yeni Şablon Ekleme

Operasyon partner (`b2b_partner`) bayileri `/dealer/mini-site` panelinden bir site şablonu
seçer, `/p/{slug}` adresinde o şablonla yayınlanır. Tüm şablonlar **aynı veriyi** kullanır:
partner içeriğini bir kez girer, şablon değişince aynı veriyle dolar.

## Modüler yapı (bölüm sırası + aç/kapa)

Partner `/dealer/mini-site` → **Sayfa Kurgusu** panelinden bölümleri sıralar ve kapatır
(`dealers.site_sections`). Şablon bunu desteklemek için bölümlerini **ayrı partial** dosyalara
koyar ve sırayla include eder:

```blade
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.{key}.sections.' . $sectionKey)
@endforeach
```

- Partial yolu: `resources/views/public/partner-templates/{key}/sections/{sectionKey}.blade.php`
- Bölüm anahtarları: `App\Support\PartnerSiteSections::SECTIONS`
  (`unis, services, steps, stats, about, testimonials, why, packages, faq`)
- `@includeIf` kullan: olmayan partial sayfayı düşürmez (addon-bağımsız).
- Partial'lar ana dosyanın `@php` bloğundaki `$accent`, `$icon`, `$cols` gibi yerel
  değişkenlerini otomatik görür (Blade include tüm tanımlı değişkenleri geçirir).
- Nav linkleri **elle yazılmaz**: `$navLinks` kullan — kapalı/boş bölümün linki menüde çıkmaz.
  Bölüm id'leri sözleşmeyle aynı olmalı: `hizmetler, surec, paketler, sss, basvuru`.
- Sabit parçalar (partial'a çıkmaz): nav, hero, başvuru/iletişim kutusu, footer.

Örnek: `lavanta.blade.php` + `lavanta/sections/*.blade.php`.

## 2 adım

**1. Blade dosyasını oluştur**

```bash
cp resources/views/public/partner-templates/_starter.blade.php \
   resources/views/public/partner-templates/{key}.blade.php
mkdir -p resources/views/public/partner-templates/{key}/sections
```

`_starter.blade.php` sözleşmenin tamamını kullanan iskelettir (kayıtlı değildir, public
olarak render edilmez). Tasarımın CSS + markup'ını bunun üzerine giydir, sıralanabilir
bölümleri partial'lara taşı.

**2. Kayıt defterine satır ekle** — `app/Support/PartnerTemplates.php`

```php
'{key}' => [
    'name'     => 'Görünen Ad',
    'desc'     => 'Partnerin panelde göreceği kısa tanım.',
    'accent'   => '#0d9488',   // bu tasarıma yakışan varsayılan renk önerisi
    'modular'  => true,        // bölüm sırası/aç-kapa uygulanıyor mu
    'sections' => ['unis', 'services', 'steps', 'stats', 'about', 'testimonials', 'why', 'packages', 'faq'],
],
```

`modular`/`sections` editöre yansır: şablonun basmadığı bölüm "… şablonunda yok" etiketiyle,
`modular: false` şablonlar "sıra bu şablonda uygulanmaz" uyarısıyla gösterilir.
Seçici kart, kaydetme doğrulaması ve önizleme linki otomatik gelir; başka yeri değiştirmen gerekmez.

## Veri sözleşmesi

Hepsi `App\Support\PartnerSiteData::forDealer()` çıktısıdır.

| Değişken | Tip | Not |
|---|---|---|
| `$dealer` | Dealer | ham alanlar (örn. `site_hero_image_path`) |
| `$brandName` / `$brandLogoUrl` | string / ?string | logo yoksa metin logo kullan |
| `$accentColor` | string | doğrulanmış hex; `PartnerSiteData::accent()` ile normalize et |
| `$heroTitle` / `$heroSubtitle` / `$aboutText` | string | **her zaman dolu** (default'u var) |
| `$services` | list | `[title, desc, icon, items[]]` — **her zaman ≥1** |
| `$stats` | list | `[value, label]` — **boş olabilir** |
| `$team` | list | `[name, title, photo]` — **boş olabilir** |
| `$testimonials` | list | `[text, name, school]` — **boş olabilir** |
| `$heroTrust` | list | `[value, label]`, max 3, `$stats`'tan türetilir — **boş olabilir** |
| `$steps` | list | `[no, title, desc]` — **her zaman 4** (firmadan bağımsız, DB alanı yok) |
| `$whyUs` | list | `[icon, title, desc]` — **her zaman 4** (firmadan bağımsız, DB alanı yok) |
| `$packages` | list | `[name, tag, desc, items[], featured]` — **boş olabilir** (default paket ÜRETİLMEZ) |
| `$packageNote` | string | paket bölümü açıklaması; `''` ise basma (paket yoksa hep `''`) |
| `$faq` | list | `[q, a]` — **her zaman ≥1** (partner girmezse genel default set) |
| `$universities` | list | üniversite adları (string) — **boş olabilir** |
| `$sections` | list | açık bölüm anahtarları, partnerin seçtiği **sırayla** |
| `$navLinks` | list | `[href, label]` — sadece açık + içeriği olan bölümler (menüyü buradan bas) |
| `$showBadge` | bool | `false` → MentorDE adı sayfada **hiç geçmemeli** |
| `$phone` `$whatsapp` `$instagram` `$address` | ?string | **boş olabilir** |
| `$applyUrl` | string | tüm CTA'lar buraya gider (lead o bayiye etiketlenir) |

İkonlar paylaşımlı: `{!! \App\Support\PartnerSiteData::icon('cap') !!}`
(`cap, passport, coins, home, check, arrow, chart, bolt, shield, gear, users, clock, star,
work, pin, phone, wa, instagram, default`)

## Kurallar

- **JS yok.** Inline `<script>` / `onclick` kullanma. Public sayfa; etkileşim gerekiyorsa
  CSS ile çöz (`:target`, `details/summary`, checkbox hack).
- **Font sadece lokal.** Google Fonts CDN'e istek atma — DSGVO (LG München I, 2022).
  `public/fonts/local-fonts.css` yüklenir; içinde **Plus Jakarta Sans** (variable 200–800),
  **DM Serif Display** (regular + italic), **Poppins** (400–800), **Public Sans**
  (variable 400–700) ve **IBM Plex Mono** (400, 500) var. Başka aile gerekiyorsa önce
  woff2'yi `public/fonts/`'a indir (latin + **latin-ext** — Türkçe karakterler latin-ext'te)
  ve `local-fonts.css`'e `@font-face` ekle. Variable font'ta `font-weight: 400 700;` yaz,
  tek ağırlık yazarsan tarayıcı kalın metni taklit eder (fake bold).
- **Uydurma veri yok.** Örnek yorum, hayali memnuniyet puanı, "1200+ öğrenci" gibi rakam
  YAZMA. Veri boşsa bölümü hiç basma (`@if(!empty($testimonials))`). Gerçek olmayan rakam,
  gerçek bir firmanın canlı sayfasında yanıltıcı reklamdır.
- **White-label guard.** Yeni view `public.partner-templates.*` altında olduğu sürece
  `AppServiceProvider`'daki global `View::composer('*')` guard'ı zaten atlar. Bu klasörün
  DIŞINA bir public white-label view koyarsan guard'a elle eklemen gerekir, yoksa
  `brandName`/`brandLogoUrl` MentorDE ile ezilir.
- **Renk.** Tüm vurguları `var(--accent)` üzerinden türet; partner kendi kurumsal rengini
  seçtiğinde site tamamen onunla boyansın.

## Doğrulama

```bash
php artisan view:clear
```

Sonra `/dealer/mini-site` → şablonu seç → **Önizle ↗**
(veya doğrudan `/p/{slug}?preview=1&tpl={key}` — giriş yapmış sahibi bayi ya da manager gerekir).

Yayına almadan önce kontrol et:

- [ ] `script=0`, `onclick=0` (kaynağı görüntüle → CSP güvenli)
- [ ] Sayfada `fonts.googleapis.com` / başka CDN isteği yok
- [ ] Rozet kapalıyken sayfada "MentorDE" hiç geçmiyor
- [ ] `$stats` / `$team` / `$testimonials` / `$packages` / `$universities` boşken bölümler
      görünmüyor, sayfa bozulmuyor
- [ ] Bölüm sırası değiştirilince sayfa o sırayla basılıyor, kapatılan bölüm hiç yok
- [ ] Kapalı bölümün üst menü linki de yok (`$navLinks` kullanıldı mı)
- [ ] Mobil (≤480px) düzen bozulmuyor
- [ ] Tüm CTA'lar `$applyUrl`'e gidiyor

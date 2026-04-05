# MentorDE Reklam Link Sistemi (Kayitli Link Envanteri + UTM)

Bu sistem iki seviyeli calisir:

1. Marketing panelinde kayitli reklam linki olusturulur (`/mktg-admin/tracking-links`).
2. Disariya dagitilan link tek bir sabit format olur: `/go/{code}`.
3. `/go/{code}` tiklamayi loglar ve kullaniciyi `apply` formuna UTM + `trk` bilgisiyle yonlendirir.
4. Basvuru geldigi anda guest ve lead_source_data kaydi bu `trk` koduna baglanir.

## 1) Desteklenen Parametreler

- Zorunluya yakin: `utm_source`, `utm_medium`, `utm_campaign`
- Opsiyonel: `utm_term`, `utm_content`
- Tiklama id: `gclid`, `fbclid`, `ttclid` (otomatik `click_id` alanina yazilir)

## 2) Kayit Akisi

1. Marketing ekibi panelde linki kategorize eder (source/medium/campaign/dealer vb.).
2. Sistem `code` uretir ve paylasim linki verir: `https://DOMAIN/go/{code}`.
3. Kullanici linke tiklayinca tiklama logu tutulur.
4. Sistem kullaniciyi `apply` ekranina UTM + `trk` ile yonlendirir.
5. `apply` formu query'yi cookie'ye de yazar.
6. Form gonderiminde `guest_applications.tracking_link_code` ve `lead_source_data.referral_link_id` dolu gelir.
7. Conversion oldugunda ayni kayit uzerinden donusum oranlari hesaplanir.

## 3) Link Formati

Disariya dagitilan temel format:

```text
https://DOMAIN/go/{code}
```

Sistem icinde olusacak yonlendirme ornegi:

```text
/apply?utm_source=instagram&utm_medium=paid_social&utm_campaign=de_winter_2026&utm_content=story_a&trk=adigs03
```

## 4) Kod Kategoriyasyonu (Zorunlu)

Kod formulu:

```text
[kategori:2] + [platform:2] + [tip/yerlesim:1] + [varyasyon:2]
```

Ornek:

```text
adigs03
```

Anlam:

- `ad` = Reklam
- `ig` = Instagram Ads
- `s` = Story
- `03` = 3. varyasyon

Not:

- Varyasyon 2 hanelidir (`01` ... `99`).
- Ayni kategori/platform/tip kombinasyonunda bos birakilirsa sistem ilk bos varyasyonu otomatik atar.

## 5) UTM Standardi (Onerilen)

- `utm_source`: platform (`google`, `instagram`, `youtube`, `tiktok`)
- `utm_medium`: trafik tipi (`cpc`, `paid_social`, `video`, `organic`)
- `utm_campaign`: donem+hedef (`de_winter_2026`, `ausbildung_q3_2026`)
- `utm_content`: kreatif varyanti (`hero_video_v1`, `carousel_b`)
- `utm_term`: keyword (varsa)

## 6) HTML Sayfa Uygulamasi

Landing page CTA linkleri `#` olmamali; dogrudan kayitli redirect linke gitmeli.

Yanlis:

```html
<a href="#" class="btn btn-primary">Ucretsiz On Gorusme Al</a>
```

Dogru:

```html
<a href="/go/adigs03" class="btn btn-primary">Ucretsiz On Gorusme Al</a>
```

## 7) Not

- Formdaki "Bizi nereden buldunuz?" alanini dijital manager dinamik yonetir.
- UTM varsa sistem kaynak verisini otomatik doldurur; manuel secim fallback olarak kalir.

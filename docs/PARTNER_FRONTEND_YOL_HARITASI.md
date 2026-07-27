# Partner Frontend & Yeni Partner Ekleme — Yol Haritası

> **Bağlam:** Şu an 4 partner var — **1 operasyon partner** + **3 freelance partner**.
> Bu doküman: (a) yeni partner ekleme akışı, (b) partner tipine göre frontend stratejisi,
> (c) operasyon partner web sitesi spec'i, (d) faz planı ve teknik notlar.

**Tarih:** 30 Haziran 2026 · **Durum:** Taslak / onay bekliyor

---

## 1. Yönetici Özeti

MentorDE'nin partner (bayi) altyapısının **büyük kısmı zaten hazır**: başvuru → onay →
otomatik hesap provizyonu, partner CRM paneli, white-label mini-site (`/p/slug`),
tracking link ve tier sistemi çalışıyor. Eksik olan: **operasyon partner için kurumsal,
çok-bölümlü web sitesi** ve **kendi alan adı (custom domain)** akışı.

**Strateji:** İki katmanlı frontend.
- **Freelance partner** → mevcut tek-sayfa mini-site + tracking link (ek geliştirme gerekmez).
- **Operasyon partner** → çok-bölümlü kurumsal site + custom domain (yeni geliştirme).

---

## 2. Mevcut Durum — Envanter

| Parça | Durum | Yer |
|---|---|---|
| Partner başvuru formu | ✅ Hazır | `/apply/partner` |
| Onay → otomatik hesap+user+davet | ✅ Hazır | `DealerProvisioningService` |
| Partner CRM paneli | ✅ Hazır | `/dealer/dashboard` ve alt sayfalar |
| → Lead pipeline | ✅ | `/dealer/lead-pipeline` |
| → Komisyon / kazanç | ✅ | `/dealer/earnings`, `/dealer/calculator` |
| → Profil + fotoğraf | ✅ | `/dealer/profile` |
| → Alt-bayi (hiyerarşi) | ✅ | 2 seviye: bölge → alt-bayi |
| **White-label mini-site** | ✅ Hazır | `/p/{slug}` → `dealer-landing.blade` |
| → Düzenleyici (logo/renk/hero/slug) | ✅ | `/dealer/mini-site` |
| Tracking link (attribution) | ✅ Hazır | `/go/{code}` |
| Tier / rol sistemi | ✅ Hazır | `lead_generation` · `freelance` · `b2b_partner` |
| **Custom domain (kendi alan adı)** | ⚠️ Yarım | Schema var (`custom_domain`, `custom_domain_token`, `custom_domain_verified_at`), **DNS doğrulama akışı yok** |
| Çok-bölümlü kurumsal site | ❌ Yok | Yapılacak |

> **Teknik not:** "Operasyon partner" iş tarafında bir kavram; teknik olarak en yetkili
> tier olan **`b2b_partner`** rolüne karşılık gelir. 3 freelance ise **`freelance`** tier'ı.

---

## 3. Partner Tipleri ve İhtiyaçları

### 3.1. Freelance Partner (3 kişi) — Bireysel Yönlendirici
- **Ne yapar:** Sosyal medya / çevresi üzerinden öğrenci adayı yönlendirir.
- **Frontend ihtiyacı:** Dijital kartvizit düzeyinde tek sayfa + paylaşılabilir link.
- **Mevcut çözüm yeterli:** `/p/slug` mini-site + `/go/{code}` tracking link.
- **Aksiyon:** Ek geliştirme gerekmez; sadece kendi içeriklerini girmeleri lazım.

### 3.2. Operasyon Partner (1 firma) — Kurumsal Operasyon Ortağı
- **Ne yapar:** Bir bölge/ofis işletir, kendi alt-bayileri olabilir, öğrenciyi (genelde)
  uçtan uca yönetir. Kendi markası önemli.
- **Frontend ihtiyacı:** Gerçek bir **kurumsal web sitesi** — çok bölümlü, kendi alan adıyla.
- **Mevcut çözüm yetersiz:** Tek-sayfa mini-site kurumsal görünüm vermiyor.
- **Aksiyon:** Çok-bölümlü site + custom domain akışı geliştirilecek.

---

## 4. Yeni Partner Ekleme — Adım Adım Yol Haritası

```
1) BAŞVURU
   - Self-servis: /apply/partner formu doldurur
   - VEYA: Manager elle ekler (/manager/dealers → + Yeni)

2) ONAY (Manager)
   - /manager/dealers → başvuruyu "Onayla"
   - Otomatik: Dealer kaydı + User hesabı + davet (şifre belirleme) maili
   - (DealerProvisioningService)

3) TIER / ROL ATAMA
   - Operasyon mu, Freelance mi?
     • Operasyon → b2b_partner
     • Freelance → freelance
   - Kademe (komisyon seviyesi) atanır

4) PARTNER GİRİŞİ
   - Davet mailinden "Şifremi Belirle" → şifre oluşturur
   - /dealer/dashboard'a düşer

5) MİNİ-SİTE / SİTE KURULUMU
   - /dealer/mini-site → slug, logo, marka rengi, hero metni
   - Freelance: tek sayfa hazır → /p/slug yayında
   - Operasyon: çok-bölümlü site + (istenirse) custom domain

6) PAYLAŞIM & DAĞITIM
   - Tracking link: /go/{code}  (WhatsApp, sosyal, reklam)
   - Site adresi: /p/slug veya firma.com (operasyon)

7) LEAD AKIŞI
   - Ziyaretçi site/link → "Başvur" CTA → /apply/partner/{code}
   - Lead otomatik o partnere etiketlenir → partnerin pipeline'ına düşer
   - Partner takip eder, komisyon hak edişi otomatik hesaplanır
```

> Adım 1–7 **bugün çalışıyor.** Şifre-belirleme adımı (4) yakın zamanda sağlamlaştırıldı.

---

## 5. Frontend Stratejisi — İki Katman

### Katman A — Freelance (Hafif) ✅ Mevcut
- **Tek sayfa mini-site** (`/p/slug`): marka + hero + tek CTA → lead capture.
- **Tracking link** (`/go/{code}`).
- Kendi panelinde pipeline + komisyon.
- **Sonuç:** Hazır. Dijital kartvizit + yönlendirme aracı olarak yeterli.

### Katman B — Operasyon (Tam) ❌ Yapılacak
- **Çok-bölümlü kurumsal site** (Hizmetler / Hakkımızda / Ekip / İletişim).
- **Custom domain** (kendi alan adı).
- Alt-bayi yönetimi (hiyerarşi — mevcut).
- Kurumsal güven öğeleri (MentorDE partnerlik rozeti, istatistik, yorumlar).

> **Tek altyapı, iki görünüm:** Aynı mini-site sistemi, partnerin tier'ına göre
> (b2b → tam, freelance → tek sayfa) farklı render edilir. Ayrı kod tabanı gerekmez.

---

## 6. Operasyon Partner Web Sitesi — Spec

### 6.1. Sayfa Yapısı (Mockup)

```
┌────────────────────────────────────────────────────┐
│  [Partner Logo]        Menü: Hizmetler · Hakkımızda  │
│                        · Ekip · İletişim   [Başvur]  │
├────────────────────────────────────────────────────┤
│  HERO                                                │
│  "Almanya'da Eğitim Yolculuğunuz Burada Başlıyor"    │
│  Partner sloganı + arka plan görseli                 │
│  [ Ücretsiz Danışmanlık Al ]  ← büyük CTA            │
├────────────────────────────────────────────────────┤
│  HİZMETLER (kart grid)                                │
│  🎓 Üniversite Başvurusu   🛂 Vize Süreci            │
│  💰 Finansal İşlemler      🏠 Konaklama              │
│  (MentorDE paket kataloğundan beslenebilir)          │
├────────────────────────────────────────────────────┤
│  HAKKIMIZDA                                          │
│  Partnerin kendi tanıtım metni                        │
│  + "X+ öğrenciye yardımcı olduk" istatistikleri      │
├────────────────────────────────────────────────────┤
│  EKİP / DANIŞMANLAR  (opsiyonel)                      │
│  Foto + isim + ünvan kartları                         │
├────────────────────────────────────────────────────┤
│  GÜVEN                                                │
│  ✓ MentorDE Yetkili Partneri rozeti                  │
│  ✓ Öğrenci yorumları / başarı hikayeleri             │
├────────────────────────────────────────────────────┤
│  İLETİŞİM / BAŞVURU                                   │
│  Başvuru formu → lead pipeline                        │
│  + WhatsApp + telefon + adres                         │
├────────────────────────────────────────────────────┤
│  Powered by MentorDE   (ince alt bilgi)              │
└────────────────────────────────────────────────────┘
```

### 6.2. Freelance vs Operasyon — Net Fark

| | Freelance Mini-Site | Operasyon Sitesi |
|---|---|---|
| Amaç | Dijital kartvizit | Kurumsal web sitesi |
| Bölüm sayısı | 1 (hero + CTA) | 5–6 (çok bölümlü) |
| Alan adı | `/p/slug` | Kendi domain'i (`firma.com`) |
| İçerik | Logo + slogan | Hizmet + ekip + hakkımızda + yorum |
| Hedef | Hızlı lead yakalama | Marka + güven + lead |

### 6.3. Düzenlenebilir Alanlar (Panel)
Operasyon partner kendi panelinden düzenleyebilmeli:
- Logo, marka rengi, hero başlık/alt başlık
- Hizmet kartları (başlık + açıklama + ikon)
- Hakkımızda metni + istatistikler
- Ekip üyeleri (foto + isim + ünvan)
- İletişim bilgileri (telefon, WhatsApp, adres)
- (İleride) Yorumlar

---

## 7. Custom Domain Akışı (F3b)

Schema hazır (`custom_domain`, `custom_domain_token`, `custom_domain_verified_at`).
Eksik: doğrulama + bağlama akışı.

```
1) Partner panelden domain girer: firma.com
2) Sistem bir doğrulama token'ı üretir
3) Partner kendi DNS'ine TXT kaydı ekler:
   _mentorde-verify.firma.com  TXT  "mentorde-verify=<token>"
4) Partner "Doğrula" der → sistem DNS TXT'i kontrol eder
5) Doğrulanınca custom_domain_verified_at set edilir
6) firma.com → partnerin mini-site'ına yönlenir
   (web sunucusu / reverse proxy seviyesinde host eşleme)
```

> **Not:** KASSERVER shared hosting'de custom domain bağlama, hosting paneli
> (subdomain/parking) tarafında ek yapılandırma gerektirebilir. Önce DNS doğrulama
> mantığı uygulanır; canlı bağlama hosting kısıtlarına göre netleştirilir.

---

## 8. Faz Planı ve Öncelik

| Faz | İş | Değer | Tahmini Süre |
|---|---|---|---|
| **F1** | Operasyon partner çok-bölümlü site (tier-aware render) | ⭐⭐⭐ Yüksek | ~1–2 gün |
| **F2** | Mini-site editörünü zenginleştir (hizmet/ekip/hakkımızda alanları) | ⭐⭐ Orta | ~1 gün |
| **F3** | Custom domain DNS doğrulama akışı | ⭐⭐ Orta | ~1 gün |
| **F4** | Kurumsal güven öğeleri (rozet, istatistik, yorum) | ⭐ Düşük | ~yarım gün |

**Önerilen başlangıç:** F1 (operasyon partnerin çok-bölümlü sitesi) — en görünür değer.

---

## 9. Teknik Notlar (Mevcut Yapı)

- **Public mini-site:** `App\Http\Controllers\Public\DealerMiniSiteController@show`
  → `resources/views/public/dealer-landing.blade.php`
- **Editör:** `App\Http\Controllers\Dealer\DealerMiniSiteController` → `/dealer/mini-site`
- **Model:** `App\Models\Dealer` (slug, logo, hero, custom_domain, tier alanları)
- **Tier sabitleri:** `Dealer::ROLE_LEAD_GENERATION`, `ROLE_FREELANCE`, `ROLE_B2B_PARTNER`
- **Lead attribution:** `/apply/partner/{code}` → guest application'a `dealer_code` etiketlenir
- **Provizyon:** `App\Services\DealerProvisioningService`

> Çok-bölümlü site için: `dealer-landing.blade` tier'a göre genişletilir
> (b2b → tam bölümler, freelance → mevcut tek sayfa). Yeni alanlar `dealers` tablosuna
> (veya bir `dealer_site_sections` tablosuna) nullable eklenir — addon bağımsızlığı korunur.

---

## 10. Açık Sorular (Netleşmesi Gereken)

1. Operasyon partner öğrenciyi **uçtan uca mı** yönetiyor, yoksa sadece **lead mi** topluyor?
   (Bu, sitenin "danışmanlık firması" mı yoksa "yönlendirme sayfası" mı olacağını belirler.)
2. Operasyon partnerin kendi **danışmanları/ekibi** var mı? (Ekip bölümü gerekli mi?)
3. Custom domain **şart mı**, yoksa `/p/slug` yeterli mi? (Hosting kısıtı bu kararı etkiler.)
4. Operasyon partnerin alt-bayileri olacak mı? (Recruiting/alt-bayi davet akışı gerekli mi?)

---

*MentorDE — Partner Frontend Yol Haritası · İç doküman*

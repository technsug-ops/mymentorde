# TODO

## 🔨 DEVAM EDİYOR — 10 dış tasarımın şablona çevrilmesi (29 Tem)
**Kaynak:** `Desktop/PHP -LARAVEL Mentorde/Fatma Özkan Almanya Danışmanlığı.zip` — DC formatında
(`<x-dc>`, `sc-for`, `image-slot`, `style-hover`) 10 landing + ortak `fz-data.js` içerik dosyası.
Pastel: Lavanta ✅ / Şeftali Sabahı / Nane · Canlı: Elektrik / Mercan Enerji / Sedef / Manyeta ·
Premium: Bulut / Mermer / Ufuk.

**Bu turda yapılan (pilot = Lavanta):**
1. **Sözleşme genişletildi** — `dealers`: `site_packages`, `site_package_note`, `site_faq`,
   `site_universities` (hepsi nullable, migration `2026_07_29_100000`). `PartnerSiteData`:
   `packages/packageNote/faq/universities` (DB) + `steps/whyUs` (firmadan bağımsız kod default'u).
   Yeni ikonlar: work, pin, phone, wa, instagram.
2. **Editör** (`/dealer/mini-site`): Destek Paketleri (3 slot + öne çıkar checkbox + not),
   S.S.S. (6 slot), Yerleşilen Üniversiteler (satır satır) — validation + `cleanLines()` eklendi.
3. **Fontlar self-host** (DSGVO): Poppins 400–800, Public Sans (variable 400–700),
   IBM Plex Mono 400/500 → 16 woff2 + `local-fonts.css`. Variable font'ta `font-weight: 400 700`.
4. **`lavanta.blade.php`** + registry satırı; S.S.S. `<details>` ile (JS yok), tüm renkler
   `var(--accent)`'ten `color-mix` ile türetiliyor.
5. Uydurma veri temizliği: tasarımdaki `4.9/5`, `1.200+`, `%98 vize başarısı`, yıldız satırı ve
   "Vize onaylandı" balonu → partnerin kendi `stats`'ından besleniyor, veri yoksa bölüm basılmıyor.

**Doğrulama (29 Tem):** 45 otomatik kontrol geçti — boş partner (bölümler gizli, default S.S.S./
süreç/neden-biz var), dolu partner (paket/üniversite/istatistik/yorum var, öne çıkan paket vurgulu),
rozet kapalı → sayfada "MentorDE" 0 kez; 4 şablon da `script=0`, `cdnfont=0`; editör round-trip
(boş slot düşüyor, newline→dizi, checkbox→bool, hepsi boş→null).
Görsel kontrol: `/p/operasyon-partner-demo?preview=1&tpl=lavanta` (manager/sahibi bayi girişiyle).

**29 Tem ikinci tur — modülerlik (kullanıcı isteği: "kartlar/modüller sabit olmasın"):**
- **Kart seviyesi (A):** editörde 6 grup (hizmet/istatistik/ekip/yorum/paket/S.S.S.) artık
  sabit slot değil → **ekle / sil / ↑ ↓**. Sıra = input index sırası; tek nonce'lu JS
  (`[data-repeat]`) her değişiklikte satırları yeniden numaralar. Ortak parçalar:
  `dealer/mini-site/_repeat.blade.php` + `rows/*.blade.php`.
- **Bölüm seviyesi (B):** `dealers.site_sections` `[{key,on}]` + `App\Support\PartnerSiteSections`
  (bilinmeyen key düşer, eksik bölüm varsayılan sırayla sona eklenir). Lavanta 9 partial'a
  ayrıldı (`partner-templates/lavanta/sections/*.blade.php`), `$sections` üzerinde döner.
  Editörde "Sayfa Kurgusu" paneli (↑↓ + aç/kapa).
- **Menü artık türetiliyor:** `$navLinks` — kapalı/boş bölümün üst menü linki basılmaz
  (bulunan bug: sıra/kapatma sonrası ölü anchor kalıyordu).
- **Şablon yetenek beyanı:** `PartnerTemplates::TEMPLATES[*]['modular'|'sections']`;
  aurora/minimal/bold sabit kurgulu (modular=false) → editör uyarı + "bu şablonda yok" etiketi.
  Bu 3 eski şablon paket/S.S.S./üniversite bölümlerini de basmıyor — 10 yeni tasarım
  tamamlanınca ya modülere çevrilecek ya da kaldırılacak (karar bekliyor).
- **Kart hizalama:** grid auto-fit → ortalayan flex + satır başına kart sayısı içerikten
  hesaplanıyor (6 kart artık 4+2 değil 3+3; eksik son sıra ortalanıyor, kartlar şişmiyor).
- `_starter` iskeleti de modüler kalıba geçti (9 bölüm partial'ı) — kalan 9 tasarım
  doğrudan bu kalıpla çevrilecek.
- **Doğrulama:** 30 maddelik uçtan uca test (GET editör → POST → public sayfa: sıra değişti,
  kapalı bölüm basılmadı, kart eklendi/sıralandı, nav linki kayboldu) + 45 render kontrolü
  regresyon temiz; 4 şablon `script=0 cdn=0`; 20 blade derleme kontrolü.

**30 Tem — 10 ŞABLONUN TAMAMI ÇEVRİLDİ (modüler kalıp):**
lavanta · seftali · nane · elektrik · mercan · sedef · manyeta · bulut · mermer · ufuk
(registry'de 13 şablon: 10 modüler + eski 3 sabit kurgulu aurora/minimal/bold)

Her şablon: ana dosya (head+CSS+nav+hero+CTA+footer) + `{key}/sections/*.blade.php` 9 partial.
Kimlikler gerçekten farklı — sadece renk kopyası değil:
- **nane:** hairline ızgara (1px gap = çizgi), ortalanmış hero, gölgesiz
- **seftali:** editoryal serif (Newsreader), sola dayalı başlık, üst-çizgi kartlar, koyu alıntı bandı
- **elektrik:** Space Grotesk sıkı tipografi, koyu lacivert panel, tam genişlik accent bandı
- **mercan:** sticker rozetler (rotate), gradient, dashed ayraç, bento (2 büyük + küçükler)
- **sedef:** Manrope/DM Sans, 24-32px yuvarlatma, çerçevesiz gölge, büyük beyaz panel
- **manyeta:** neon karanlık, glow blob, gradient metin, hizmetler NUMARALI SATIR listesi
- **bulut:** cam (glass) kartlar + blur, yatay hizmet kartı + tag çipleri
- **mermer:** Playfair + altın vurgu, sticky bölüm başlığı + numaralı satırlar, merkez alıntı
- **ufuk:** kurumsal lacivert + bronz, çerçeveli hizmet TABLOSU, lacivert bantlar
Fontlar: 12 aile / 44 face / 1.1 MB, hepsi lokal (Sora, Newsreader, Space Grotesk, Playfair,
Manrope, DM Sans, IBM Plex Sans + önceki 5).

**Doğrulama (30 Tem):** 113 blade derleme 0 hata · 10 şablon dolu+boş+rozet-kapalı render OK
(script=0, cdn=0, paket boşta gizli, rozet kapalı → "MentorDE" 0 kez) · 45 render + 30 uçtan
uca test suite regresyon temiz · 7 yeni font HTTP 200.

**Sıradaki:** görsel gözden geçirme (10 şablonu tarayıcıda karşılaştır) · aurora/minimal/bold
kararı (modülere çevir veya kaldır) · F3 custom domain.
Atlanan: DC tasarımlarındaki "Almanya'da yaşam / şehir galerisi" bölümü — partnere ait fotoğraf
yok, lisanslı stok görsel de yok. Şehir foto seti tedarik edilirse ortak bölüm olarak eklenebilir.
Fontlar: kalan şablonlar Sora / Newsreader / Space Grotesk / Playfair / Manrope / DM Sans /
IBM Plex Sans isteyecek — her biri kullanılacağı turda self-host edilmeli.

## ✅ KAPANDI — Aday öğrenci → süreç takibi köprüsü (REDDEDİLDİ)
**Karar (özet):** Köprü yaklaşımı denendi ve GERİ ALINDI. Aday öğrenciye StudentAssignment
oluşturma reddedildi.
- `b848268` köprü eklendi → `b963671` fix(senior): köprü StudentAssignment kayıtlarını
  temizle + **auto-bridge'i durdur** → `4f5a7c5` `/system/bridge-rollback` eklendi.
- İlke: aday ≠ öğrenci; process-tracking SADECE dönüşmüş öğrenci içindir.
- `StudentBridgeService` kodda duruyor ama auto-bridge KAPALI (elle backfill/rollback endpoint'leri var).
- Faz A (guest_applications.assigned_staff_email / görevli alanı) **hiç yapılmadı** — köprü
  terk edildiği için gündemde değil.
- Detay: memory/project_senior_activity_and_aday_separation.md

---

## ✅ TAMAM — Partner Frontend F2.5: çok-template sistemi (27 Tem doğrulandı + commit)
Partner sitesi tek tasarımdan **şablon seçmeli** sisteme geçti. Partner içeriğini bir kez girer,
şablon değiştirince aynı veriyle dolar.
- Ortak veri sözleşmesi: `App\Support\PartnerSiteData::forDealer()` (services/stats/team/hero/... + `icon()`)
- Registry: `App\Support\PartnerTemplates` — DEFAULT `aurora`, canlı 3: **aurora / minimal / bold**
- DB: `dealers.site_template` (nullable → aurora) + F2 bölüm alanları (site_services/stats/team/address/show_badge)
- Public: `/p/{slug}` seçili şablonu render eder; `?preview=1&tpl={key}` ile diğerleri denenebilir
  (geçersiz key sessizce default'a düşer)
- Editör `/dealer/mini-site`: şablon seçici radyo kartları + "Önizle ↗" (sadece b2b_partner'a görünür)
- White-label guard: `AppServiceProvider` global View composer'ı `public.partner-templates.*` view'larını
  atlar — yoksa MentorDE markası bayininkini ezerdi

**Doğrulama (27 Tem):** 3 şablon da render 200 (script=0, onclick=0 → CSP güvenli) · preview tpl geçişi
çalışıyor · geçersiz tpl → default · rozet kapalı iken sayfada "MentorDE" geçişi = 0 (tam white-label) ·
editör POST round-trip: boş kartlar düşüyor, `items` newline→dizi, geçersiz template validation ile reddediliyor.

**Sıradaki:** yeni şablonlar (hedef ~10, elde 3 — tasarımlar dışarıda hazırlanıyor) · F3 custom domain (ertelendi).
Yeni template = 2 adım: `_starter.blade.php`'yi kopyala + `PartnerTemplates::TEMPLATES`'a satır ekle.
Kontrol listesi: `docs/PARTNER_TEMPLATE_EKLEME.md`.

---

## ✅ TAMAM — 27 Tem: partner sistemi eksik kapatma turu
1. **Önizleme yetkisizdi (güvenlik):** `?preview=1` auth'suzdu → yayına alınmamış (manager onayı
   bekleyen) site herkese açıktı, `?tpl=` ile şablon değiştirilebiliyordu. `canPreview()` eklendi:
   sahibi bayi (`users.dealer_code === dealers.code`) veya `User::ADMIN_PANEL_ROLES`. 8 senaryo test edildi.
2. **Uydurma sosyal kanıt (yasal):** 3 şablon da `4.9/5 memnuniyet`, `1200+ öğrenci`, `%98 vize başarısı`
   ve isimli sahte yorumlar ("Elif K. — TU München") yayınlıyordu — gerçek firmaların canlı sayfasında.
   `heroTrust` artık sadece partnerin girdiği stats'tan (boşsa bölüm gizli), yorumlar yeni
   `dealers.site_testimonials` alanından (editörde 4 slot, boşsa bölüm hiç basılmaz).
3. **Google Fonts CDN (DSGVO):** minimal (Fraunces) + bold (Sora) Google'dan font çekiyordu →
   self-hosted ailelere geçirildi (DM Serif Display / Plus Jakarta Sans). 3 şablon + iskelet cdn=0.
4. **Bayat handbook HTML'leri:** `public/handbooks/*.html` Nisan'dan beri elle üretilmiş ve bayattı →
   `php artisan handbook:build` komutu (MD tek kaynak, başlık id'leri + `--check` bayatlık denetimi).
5. **Şablon entegrasyonu mekanikleşti:** `_starter.blade.php` (sözleşmenin tamamı, registry'de yok)
   + `docs/PARTNER_TEMPLATE_EKLEME.md` kontrol listesi.

---

## ✅ TAMAM — Partner Frontend F1 + F2 (operasyon partner öğrenci-lead sitesi + editör)
**F1:** b2b_partner → ayrı çok-bölümlü partner-site.blade (hero + hizmetler + süreç + hakkımızda/
istatistik bandı + neden biz + ekip + rozet + iletişim/başvuru). JS yok (CSP güvenli), accent boyalı.
**F2:** /dealer/mini-site editörü b2b'ye açıldı — her firma kendi rengi + hizmet/istatistik/ekip
kartları + adres + "MentorDE rozeti" aç/kapa (kapatınca powered-by da gizlenir = tam white-label).
Sabit-slot dizi input (JS'siz). Lokal render + view:cache + toggle testleri geçti.
Sonraki: F3 custom domain (ertelendi).

## 🔨 (arşiv) Partner Frontend F1 — orijinal madde listesi
**Bağlam:** docs/PARTNER_FRONTEND_YOL_HARITASI.md · Kararlar: operasyon partner SADECE lead
topluyor (danışmanlık-CRM değil), custom domain F3'e ertelendi, F1 = AYRI yeni blade.

- [ ] migration: dealers'a nullable — site_services (JSON), site_stats (JSON), site_team (JSON),
      site_address, site_show_badge
- [ ] Dealer model: cast + fillable
- [ ] Public/DealerMiniSiteController@show: tier b2b_partner + site_enabled → yeni blade,
      değilse mevcut dealer-landing davranışı
- [ ] resources/views/public/partner-site.blade.php (YENİ): hero + hizmetler + hakkımızda/
      istatistik + ekip + MentorDE partner rozeti + iletişim/başvuru formu → apply.partner CTA.
      Brandbook #7e58bf default, accent override.
- [ ] Boş içerikte mantıklı default'lar (hizmetler MentorDE paketlerinden), addon-bağımsız try/catch
- [ ] Lokal doğrulama (b2b_partner mini-site render + freelance/lead bozulmadı)

### Sonra (bu F1 değil)
- F2: mini-site editörünü zenginleştir (hizmet/ekip kartları panelden düzenlenebilir)
- F3: custom domain DNS doğrulama akışı

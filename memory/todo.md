# İndirim Kodu — Public Share Page + 5 Template + PNG İndir

**Başlangıç:** 2026-04-29 (devam)

## Hedef

Manager kuponlu link üretir, sosyal medya / WhatsApp'a yapıştırır.
Karşı taraf güzel mobile-friendly bir landing görür, "**Görsel indir**" butonu ile PNG kaydeder.

## 5 Template (görsel stiller)

1. **Classic** — White bg, brand purple gradient header, ticket-style dashed code box
2. **Bold** — Full purple→pink gradient bg, big confetti, devasa kod
3. **Premium** — Dark navy + altın aksanlar, serif font, az emoji
4. **Playful** — Lavanta + sarı, emoji-rich, "Almanya'ya uçuşa hazır mısın? ✈️" tone
5. **Urgency** — Kırmızı/turuncu gradient, "LIMITED" ribbon, "SADECE X GÜN" badge

## Düzenlenebilir Metinler (her template'de aynı slot'lar)

- `landing_title` — Hero başlık (default: kod açıklaması veya "Sana özel indirim")
- `landing_subtitle` — Alt başlık
- `landing_cta_text` — CTA buton metni (default: "Hemen Başvur")
- `landing_disclaimer` — Footer disclaimer

Hepsi opsiyonel — boş bırakılırsa template-spesifik default kullanılır.

## Adımlar

- [x] Plan
- [ ] **1. Migration** — `discount_codes` tablosuna 5 kolon:
  - `template_id` tinyInt nullable (1-5, null = 1 default)
  - `landing_title` varchar(255) nullable
  - `landing_subtitle` varchar(500) nullable
  - `landing_cta_text` varchar(120) nullable
  - `landing_disclaimer` text nullable
- [ ] **2. Model fillable + cast** güncelle
- [ ] **3. Manager form** — yeni bölüm "Paylaşım kartı":
  - Template seçici (5 görsel preview thumbnails)
  - 4 metin alanı (placeholder'da default text gösterilir)
- [ ] **4. Manager controller** — validation + save
- [ ] **5. Manager index** — her satıra:
  - "🔗 Paylaş Linki Kopyala" butonu (clipboard JS)
  - "👁 Önizleme" → /promo/{code} yeni sekmede
- [ ] **6. Public route** — `GET /promo/{code}` (auth YOK, throttle var)
- [ ] **7. PromoController** — kodu çöz (case-insensitive), expired/inactive durumda farklı view
- [ ] **8. Public layout + 5 template partials**:
  - `resources/views/promo/layout.blade.php` (head, OG meta, html2canvas script)
  - `resources/views/promo/templates/{1-5}.blade.php`
  - `resources/views/promo/expired.blade.php`
- [ ] **9. html2canvas entegrasyonu** — "Görsel İndir" butonu kart div'ini PNG olarak indirir
- [ ] **10. Open Graph meta** — WhatsApp/sosyal medyada link önizleme
- [ ] **11. PostHog event** — `discount_code_landing_viewed`
- [ ] **12. Smoke test** — manager kod üret + paylaş linki aç + PNG indir

# İndirim Kodu — Public Share Page (TAMAMLANDI)

**Tamamlanma:** 2026-06-09

Önceki backlog plan'ı: 12 madde. Hepsi tamamlandı ve canlıya alındı.

## Sonuç

- ✅ Migration `add_landing_fields_to_discount_codes` (5 kolon)
- ✅ Model fillable + cast
- ✅ Manager form `Paylaşım kartı` (template selector + 4 metin)
- ✅ Manager controller validation + save
- ✅ Manager index: `🔗 Linki Kopyala` + `👁 Önizle` butonları
- ✅ Public route `GET /promo/{code}` (throttle:60,1)
- ✅ PromoController — code resolve, expired view fallback
- ✅ Templates 1-5 (Classic, Bold, Premium, Playful, Urgency) + styles
- ✅ html2canvas: `📥 Görsel İndir` butonu PNG indirir
- ✅ Open Graph + Twitter Card meta (og:image fallback chain)
- ✅ PostHog event: `discount_code_landing_viewed` + `discount_code_landing_expired_viewed`
- ⚪ Smoke test — user prod doğrulaması bekleniyor

## Sonraki ileride yapılabilir (opsiyonel)

- **Promo OG image** — `public/img/promo-og.png` (1200x630, brand identity) eklendiğinde otomatik kullanılır
- **Brand OG image** — `public/img/brand-og.png` fallback olarak da çalışır
- **PNG önizleme thumbnails** — manager index'inde her kart için template thumbnail
- **A/B test** — manager template seçerken hangisinin daha çok download olduğunu PostHog'dan ölç (template_id breakdown)

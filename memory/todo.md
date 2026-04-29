# İndirim Kodu Sistemi (MVP — Genişletilebilir)

**Başlangıç:** 2026-04-29
**Sahibi:** Manager (kod üreten + listeyen). Bayi/dealer kendi kodunu üretmesi sonraki sprint'te.

## MVP Kapsamı (kullanıcının kararı)

- ✅ Manager kod üretir (code, % veya sabit EUR, expiry, max use, max use/kişi)
- ✅ Aday `services` sayfasında havale talebi öncesi kupon yazar
- ✅ Doğrulanırsa `GuestPaymentRequest.amount_eur` indirimli kaydedilir + meta'ya kod izi
- ✅ Manager kullanım listesi: kim, ne zaman, ne kadar tasarruf

## Future-proof altyapı (UI'da YOK ama şemada VAR)

- `applies_to_package_codes` JSON — paket-spesifik (null = hepsi)
- `min_purchase_amount_eur` — minimum tutar şartı
- `dealer_id` — bayi attribution (komisyon hesaplama hook için)
- `metadata` JSON — catch-all
- Redemption tablosu **polymorphic** (`redeemable_type`/`redeemable_id`) — ileride StudentPayment, BookingPayment de aynı tabloyu kullanır

## Adımlar

- [ ] **1. DB migration** — 2 tablo:
  - `discount_codes` (company_id, code unique per company, type, value, expiry, limits, future cols, is_active, created_by, redemption_count denormalize)
  - `discount_code_redemptions` (company_id, discount_code_id, redeemable_type, redeemable_id, guest_application_id, user_id, original/discount/final amount, redeemed_at)
- [ ] **2. Model'ler** — `DiscountCode` (BelongsToCompany), `DiscountCodeRedemption`
- [ ] **3. Servis** — `App\Services\DiscountCodeService`:
  - `validateForGuest(string $code, GuestApplication $g, float $amount): array` → ['ok' => bool, 'code' => DiscountCode?, 'discount' => float, 'final' => float, 'error' => string?]
  - `applyToPaymentRequest(DiscountCode $c, GuestPaymentRequest $req, GuestApplication $g): DiscountCodeRedemption`
- [ ] **4. Manager Controller** — `Manager/DiscountCodeController`:
  - index (liste + filtre)
  - create / store
  - edit / update
  - destroy (soft? aktif/pasif toggle daha iyi)
  - toggleActive
  - redemptions (kullanım listesi)
- [ ] **5. Manager Views** — `manager/discount-codes/{index,form,redemptions}.blade.php`
- [ ] **6. Routes + nav link**
- [ ] **7. Guest entegrasyonu** — `guest/services` sayfasına kupon input + `POST /guest/discount-codes/validate` (AJAX)
- [ ] **8. WorkflowController.processPayment update** — kupon submit edildiyse discount uygula + redemption kaydı
- [ ] **9. Smoke test** — tinker'la kod üret + AJAX validate + payment request oluştur, redemption sayaç artıyor mu

## Kapsam dışı (sonraki sprint)
- Bayi-kendi-kodu üretimi (bayi paneline taşınacak)
- Paket-spesifik / minimum tutar UI'ları (şema hazır, form'da gösterme)
- Stripe checkout discount entegrasyonu (havale akışı yeter MVP'de)
- E-posta kuponu / one-time link

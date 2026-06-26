# TODO — Bayi çoklu rol (lead-gen + freelance) + plan editable

**Karar (26 Haziran):** Kapasite + tek primary tier yaklaşımı. `dealer_type_code` primary kalır (freelance = izin superset), "çift rol" `roles` JSON kapasite seti olarak eklenir. Komisyon lead'in `referral_type`'ına göre zaten ayrışıyor. M2M YOK (30 dosya riski).

## Adımlar — HEPSİ TAMAM (kod), prod migrate bekliyor
- [x] 1. Migration: `dealers.roles` + `dealer_applications.roles` JSON nullable + backfill
- [x] 2. Dealer model: roles cast + sabitler + helper'lar (rolesList/hasRole/primaryTypeForRoles/roleLabels)
- [x] 3. DealerApplication model: roles cast + rolesList + rolesFromPlan
- [x] 4. Başvuru detay editable form (CSP-safe)
- [x] 5. updateRoles() + onaylı dealer senkron
- [x] 6. provisionDealerFromApplication roles entegrasyonu
- [x] 7. Routes (manager + mktg-admin + manager.dealers.roles) — route:list ✓
- [x] 8. Bayi detay editable + updateDealerRoles()
- [x] 9. php -l temiz, route:list ✓, view:clear ✓ (lokal MySQL kapalı → migrate prod'da)

## DEPLOY SONRASI ŞART
`php artisan migrate --force` (roles kolonları + backfill) — yoksa 500.

## Notlar
- DealerType kodları: lead_generation, freelance_danisman, b2b_partner
- primaryTypeForRoles: freelance varsa freelance_danisman, yoksa lead_generation
- show.blade satır 156-159'da mevcut inline onclick var (CSP riski) — yeni kodda tekrarlama

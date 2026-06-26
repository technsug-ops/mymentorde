# TODO — VIP rolü (owner ile premium arası) — TAMAM

**26 Haziran:** Yeni `vip` rolü (UI: "VIP Ortak"). owner (altyapı sahibi) ile manager/premium arası üst yetkili. Kendi şirketi/ağı kapsamında: bayi ağı + raporlar + başvuru onay/red + denetim (read-only). Platform-altyapıya (modül/güvenlik/IP/GDPR/rol) EREMEZ.

- [x] User: ROLE_VIP + ADMIN_PANEL_ROLES
- [x] EnsureSystemAccess middleware (`system.access`) + bootstrap alias
- [x] Owner-only route'lar sertleştirildi: gdpr, webhooks, ropa, avv → system.access (mevcut açık da kapandı)
- [x] Layout: $__isVip + VIP audit-log linki + SaaS Planım gizli; "Sistem (Platform)" zaten owner-only
- [x] AuthController redirectByRole: VIP → /manager/dashboard (yoksa login'de logout!)
- [x] `php artisan mentorde:make-vip {email}` komutu (+ --revert)
- [x] role kolonu string(32), 'vip' uyumlu; lint+route:list+view:clear ✓
- NOT: landing-inventory/page-visibility guard'sız bırakıldı (low-sens, link VIP'e gizli, hariç tutma listesinde değil)

**DEPLOY SONRASI:** `php artisan mentorde:make-vip <email>` ile hesabı VIP yap.

---

# (önceki) Bayi çoklu rol (lead-gen + freelance) + plan editable — TAMAM (commit 936032a)

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

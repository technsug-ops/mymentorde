# TODO

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
Yeni template = 2 adım: blade'i `public/partner-templates/{key}.blade.php`'ye koy + `PartnerTemplates::TEMPLATES`'a satır ekle.

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

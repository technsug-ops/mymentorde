# Claude Code — Çıkarılan Dersler

Bu dosya, kullanıcı düzeltmeleri ve seans deneyimlerinden öğrenilen kuralları tutar.
Aynı hatayı bir daha yapmamak için her seansın başında gözden geçirilmeli.

---

## 19 Haziran 2026 — Admin kayıt yönetimi: list+generate yetmez, CRUD tam olmalı

**Olay:** Platform Owner Faturalama modülü smoke test'inde kullanıcı uyardı: "fatura yanlış kesildiğinde silme veya değiştirme opsiyonumuz olmalı". Modül sadece generate/send/mark-paid içeriyordu; düzenle/iptal/sil yoktu.

**Kural:** Bir admin "kayıt üreten" (fatura, sözleşme, sipariş vb.) modül kurarken **baştan** edit + cancel/void + delete ekle. Operatör hata yapacak; geri dönüş yolu olmadan canlıya çıkarma.

**Pattern (muhasebe-güvenli statü kuralları):**
- Taslak (draft) → düzenle + sil (hard delete)
- Gönderilmiş/gecikmiş → iptal et (void = `cancelled` statüsü, audit korunur), sil yok
- Ödenmiş → kilitli (finansal kayıt, hiçbir değişiklik yok)
- Türev alanlar (KDV, toplam) edit'te **yeniden hesaplanır**; bağlı yan kayıtlar (promo redemption) silme/iptalde **geri alınır** (`current_uses--`)
- Her işlem `PlatformAuditLog`'a yazılır

---

## 3 Mayıs 2026 — UniMatch full revamp seansı

### 1. Schedule edilen agent sandbox'ı external CDN'e erişemiyor

**Olay:** Yarın 09:00 routine'i (`trig_01UMjhPqWi9FVvDdmoScgd9x`) UniMatch wizard image'larını Unsplash'tan indirip self-host'a taşıyacaktı. Agent fire oldu, refactor'ı tamamladı, branch açtı, ama **8 image dosyasını indiremedi** — sadece `.gitkeep` ekleyip "BEKLEYEN: image'lar manuel eklenmeli" notu bıraktı.

**Sebep:** Anthropic cloud sandbox external HTTP/CDN erişimini muhtemelen kısıtlıyor (curl Unsplash → timeout/fail).

**Kural:** Agent'lara **internet'ten download gerektiren** task verme. Image/asset/binary indirme işlerini local'de yap, agent'a sadece kod-only refactor + PR-açma bırak.

**Pratik:**
- Image indirme → local Bash + git push
- Refactor + branch + PR → agent (sandbox uygun)
- Test çalıştırma → her ikisi de OK

---

### 2. `<html>` flag → body class transfer pattern (special-mode aktivasyonu)

**Olay:** Wizard step page'i için `body.sb-wizard-mode` istedik ama layout shared (uni-match.layout). Sadece step pages'da aktif, landing/result etkilenmesin.

**Çözüm pattern'i:**
```blade
{{-- Child template (step.blade.php, show.blade.php) @push('head') --}}
<script nonce="{{ $cspNonce ?? '' }}">
    document.documentElement.classList.add('sb-wizard-active');
</script>
```

```blade
{{-- layout.blade.php body açılırken --}}
<script nonce="{{ $cspNonce ?? '' }}">
    if (document.documentElement.classList.contains('sb-wizard-active')) {
        document.body.classList.add('sb-wizard-mode');
    }
    if (document.documentElement.classList.contains('sb-detail-active')) {
        document.body.classList.add('sb-detail-mode');
    }
</script>
```

**Neden:** `<html>` `<head>` içinde @push erişiliyor, body class henüz set edilmemiş. Flag transfer ile body açılışında doğru class atanıyor — CSS ona göre tetikleniyor.

**Nerede kullanıldı:** wizard mode, detail mode. Gelecekte başka special page modları (kayıt formu hero modu, manager dashboard fullscreen, vb.) için aynı pattern uygulanabilir.

---

### 3. Pastel cycle pattern: CSS variable + `:nth-child(Nn+X)`

**Olay:** Wizard option kartlarına monoton mor yerine her seçeneğe farklı pastel renk atamak. 19 step'in 3-8 option'ı var, her step için manuel atama yapmak zor.

**Çözüm:**
```css
.options .option:nth-child(5n+1) {
    --pastel-bg: #fef2f2; --pastel-bg-strong: #fecaca;
    --pastel-dark: #dc2626; --pastel-border: #fca5a5;
}
.options .option:nth-child(5n+2) { /* mint */ }
.options .option:nth-child(5n+3) { /* sky */ }
.options .option:nth-child(5n+4) { /* peach */ }
.options .option:nth-child(5n+5) { /* lavender */ }

.option { background: linear-gradient(135deg, #fff, var(--pastel-bg)); }
.option-icon { background: var(--pastel-bg-strong); color: var(--pastel-dark); }

/* Selected state pastel'i ezerek brand'e döner — tutarlı feedback */
.option.selected .option-icon {
    background: linear-gradient(135deg, #7e58bf, #a07ed9) !important;
}
```

**Neden:** Cycle pattern HTML'e dokunmadan görsel çeşitlilik verir. Brandbook gold yasaklı, 5 ton (coral/mint/sky/peach/lavender) yeterli.

**Önemli:** Selected state pastel'i `!important` ile ezerek brand mor'a sabitle — kullanıcının "seçildi" feedback'i renk-tutarlı kalsın.

---

### 4. `current_step` overflow → progress %105 bug

**Olay:** Lead-capture sayfası "%105 tamamlandı" gösteriyordu.

**Sebep:** `saveStep` son adımda `current_step = max(current_step, n+1)` yapıyor. Step 19 tamamlanınca `current_step = 20` oluyor. Lead-capture view `(current_step / 19) * 100` → %105.

**Kural:** Progress hesaplamasında **her zaman** `min(100, ...)` ve `min(currentStep, totalSteps)` kullan:
```php
$effectiveStep = min((int) $response->current_step, $totalSteps);
$progress      = (int) min(100, round(($effectiveStep / $totalSteps) * 100));
```

Bonus: Status-aware metinler için `isCompleted` flag'i view'a ayrıca geç. Aynı sayfa hem mid-funnel hem post-completion durumda kullanılıyorsa (lead-capture gibi), her duruma uygun başlık/buton metni gerekli.

---

### 5. POST endpoint'e GET redirect → method extraction

**Olay:** `leadCaptureSubmit` lead bilgisi alındıktan sonra `/uni-match/convert`'e yönlendirmek istiyordu, ama convert POST endpoint → GET redirect 405 döner.

**Çözüm:** Convert mantığının core'unu private method'a çıkar:
```php
public function convert(Request $r): RedirectResponse {
    // ... validation, gate kontrol
    return $this->performConvert($response);
}

private function performConvert(UniMatchResponse $r): RedirectResponse {
    // Asıl business logic
}

public function leadCaptureSubmit(Request $r): RedirectResponse {
    // ... lead save
    if ($r->getAnswer('_convert_after_lead')) {
        return $this->performConvert($response); // POST→POST, redirect yok
    }
}
```

**Kural:** İki farklı request method'undan aynı business logic'i tetiklemen gerekiyorsa, **HTTP redirect kullanma** — private method'a extract et.

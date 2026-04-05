# Claude Code — Proje Workflow Kuralları

## Workflow Orchestration

### 1. Plan Node Default
- Non-trivial her görev için (3+ adım veya mimari karar) plan moduna gir
- Bir şeyler ters giderse DUR ve hemen yeniden planla — körü körüne devam etme
- Sadece inşa etmek için değil, doğrulama adımları için de plan modunu kullan
- Belirsizliği azaltmak için önce detaylı spec yaz

### 2. Subagent Strategy
- Ana context penceresini temiz tutmak için subagent'leri serbestçe kullan
- Araştırma, keşif ve paralel analizleri subagent'lere devret
- Karmaşık sorunlar için daha fazla compute gücü kullan (subagent üzerinden)
- Odaklanmış çalışma için subagent başına tek görev ver

### 3. Self-Improvement Loop
- Kullanıcıdan HERHANGI bir düzeltme sonrası: `memory/lessons.md` dosyasını o hatayla güncelle
- Aynı hatayı bir daha yapmamanı sağlayan kurallar yaz
- Bu dersleri hata oranı düşene kadar acımasızca tekrarla
- Oturum başında proje için ilgili dersleri gözden geçir

### 4. Verification Before Done
- Çalıştığını kanıtlamadan görevi tamamlandı olarak işaretleme
- Uygunsa main ile değişikliklerin arasındaki farkı kontrol et
- "Bir staff engineer bunu onaylar mıydı?" diye sor
- Test çalıştır, logları kontrol et, doğruluğu göster

### 5. Demand Elegance (Balanced)
- Non-trivial değişiklikler için: "Daha zarif bir yol var mı?" diye sor
- Bir düzeltme hack gibi hissettiriyorsa: "Şimdi bildiklerimle daha elegant çözümü uygula"
- Basit, açık düzeltmelerde bunu atlat — aşırı mühendislik yapma
- Sunmadan önce kendi çalışmana meydan oku

### 6. Autonomous Bug Fixing
- Bug raporu verildiğinde: direkt düzelt, el tutmayı bekleme
- Log, hata, başarısız testlere işaret et — sonra onları çöz
- Kullanıcıdan sıfır context geçişi gerekli
- Söylenmeden başarısız CI testlerini düzelt

---

## Task Management

1. **Plan First:** Planı `memory/todo.md` dosyasına kontrol edilebilir maddelerle yaz
2. **Verify Plan:** Uygulamaya başlamadan önce kontrol et
3. **Track Progress:** İlerledikçe maddeleri tamamlandı olarak işaretle (TodoWrite tool)
4. **Explain Changes:** Her adımda üst düzey özet ver
5. **Document Results:** Sonuçlar bölümünü `memory/todo.md` dosyasına ekle
6. **Capture Lessons:** Düzeltmelerden sonra `memory/lessons.md` dosyasını güncelle

---

## Core Principles

- **Simplicity First:** Her değişikliği olabildiğince basit tut. Minimal kodu etkile.
- **No Laziness:** Kök nedenleri bul. Geçici düzeltme yok. Senior developer standartları.
- **Minimal Impact:** Değişiklikler yalnızca gerekli olanı dokunmalı. Bug ekleme.

---

## Proje Bağlamı

- **Stack:** PHP / Laravel, Blade templates, vanilla CSS (`portal-unified-v2.css`)
- **Mimari:** Çoklu portal (Student, Guest, Senior, Dealer, Manager, Marketing Admin)
- **CSS:** `public/css/portal-unified-v2.css` — tüm portallerin paylaşımlı CSS dosyası
- **Layout:** Her portal `resources/views/{rol}/layouts/app.blade.php` kullanır
- **Memory:** `memory/` klasöründe oturumlar arası notlar tutulur

---

## Tema Sistemi (Dark Mode + Minimalist)

### Nasıl Çalışır

Her portal layout'unda iki ayrı CSS teması var:
- **Dark mode:** `[data-theme="dark"]` CSS değişken override'ları (`resources/css/premium.css`)
- **Minimalist tema:** `minimalist.css` dinamik olarak yüklenir, `premium.css` devre dışı bırakılır

### Kritik: CSP + onclick Sorunu

`SecurityHeaders` middleware **her request'te** bir nonce üretir ve CSP header'ına ekler:
```
Content-Security-Policy: script-src 'self' 'unsafe-inline' 'nonce-{random}' ...
```

**CSP Level 3 kuralı:** `script-src` içinde `nonce-*` varsa `unsafe-inline` **görmezden gelinir**.  
Bu yüzden `onclick="__dmToggle()"` gibi inline event handler'lar **Chrome/Firefox'ta bloklanır**.

### Doğru Pattern (DEĞİŞTİRME)

Tüm 4 layout (`senior`, `dealer`, `guest`, `student`) şu yapıyı kullanır:

```html
{{-- Theme + Toast + Dark Mode --}}
<script nonce="{{ $cspNonce ?? '' }}">
function __designToggle(){ ... }   ← burada tanımlanır
function __dmToggle(){ ... }       ← burada tanımlanır

// ── İkon başlangıç değerleri ──
document.addEventListener('DOMContentLoaded', function(){
    if(localStorage.getItem('mentorde_design')==='minimalist'){...}
    if(localStorage.getItem('mentorde_dark')==='true'){...}
});

// ── Tema butonları (CSP-safe, aynı nonce bloğu) ──
(function(){
    document.getElementById('dm-btn')?.addEventListener('click', __dmToggle);
    document.getElementById('theme-toggle')?.addEventListener('click', __dmToggle);
    document.getElementById('design-btn')?.addEventListener('click', __designToggle);
})();
</script>
```

**Neden böyle:** `addEventListener` çağrısı nonce'lu script bloğundan yapıldığı için CSP geçer.  
`onclick="__dmToggle()"` HTML attribute olarak eklersen **çalışmaz** (CSP Level 3 bloğu).

### Minimalist Tema için jm-minimalist Class

`__designToggle()` hem CSS yükler hem `document.documentElement.classList.toggle('jm-minimalist', ...)` çağırır.  
Dark restore script'inde (`<head>` içi) da `classList.add('jm-minimalist')` olmalı.  
**Guest layout'a özgü:** Bu class eklenmezse minimalist tema görsel olarak uygulanmaz.

### Buton Konumları

| ID | Yer | Tetikler |
|----|-----|----------|
| `#dm-btn` | Topbar sağ | `__dmToggle()` |
| `#design-btn` | Topbar sağ | `__designToggle()` |
| `#theme-toggle` | FAB (sağ alt) | `__dmToggle()` |

### Tema CSS

- `resources/css/premium.css` — light mode varsayılan + `[data-theme="dark"]` override'ları
- `resources/css/minimalist.css` — minimalist tema stilleri
- Build: `npm run build` gereklidir; kaynak değiştirilirse Vite'ı yeniden çalıştır

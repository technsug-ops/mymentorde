---
# MentorDE Guest Portal — Geliştirme Planı v2.0
**Tarih:** 2026-03-20 | **Stack:** Laravel 12 + Blade + Vanilla JS | **Hazırlayan:** Claude Code

---

## YÖNETİCİ ÖZETİ

| Metrik | Mevcut Tahmin | Hedef | Sprint |
|--------|--------------|-------|--------|
| Form tamamlama oranı | ~40% | ~70% | Sprint 1–2 |
| Belge yükleme oranı | ~55% | ~80% | Sprint 3 |
| Ortalama form doldurma süresi | ~25 dk | ~12 dk | Sprint 2 |
| Destek talep hacmi | Yüksek | -%30 | Sprint 3–4 |
| Guest → Student dönüşümü | Bilinmiyor | Ölçülebilir baseline | Sprint 1 |
| Mobil kullanılabilirlik skoru | Orta | Yüksek | Sprint 2 |

**Kapsam:** 4 sprint, 4 hafta, tamamen Laravel 12 Blade + vanilla JS. Mevcut 74 PHPUnit test korunacak. Vue.js, WebSocket, A/B test framework yok.

---

## KARŞILAŞTIRMALI ANALİZ — Mevcut vs Hedef

| Alan | Mevcut Durum | Hedef Durum | Etki |
|------|-------------|-------------|------|
| Dashboard hero mesajı | Statik tek metin | PHP segment bazlı 5-6 dinamik mesaj | Kişiselleştirme, bağlılık |
| İlerleme çubuğu | 5 adım statik bar | Genişletilebilir milestone kartlar, durum ikonları, hover tooltip | Motivasyon, netlik |
| Next Step CTA | F5 ile kısmen var | 4 durum (not_started/in_progress/waiting/completed) aware buton | Yönlendirme kalitesi |
| Aktivite feed | Yok | Anonim "Son 24 saatte X aday belgelerini tamamladı" | Sosyal kanıt |
| Başvuru formu | Tek uzun sayfa | 4 adımlı wizard + localStorage auto-save | ~%60 abandon fix |
| Form validasyonu | Sadece submit sonrası | Satır içi real-time (vanilla JS) | Hata azaltma |
| Belge rehberliği | Yok | Her belge türü için açıklama + örnek | Destek talebi azaltma |
| Upload deneyimi | Basic input | Drag-drop zone + PDF iframe preview + boyut uyarısı | Kullanılabilirlik |
| Eksik belge özeti | Dashboard'da yok | "N belge eksik" summary widget | Tamamlama oranı |
| Senior profil | Sadece isim/email | Fotoğraf, uzmanlık, başarılı öğrenci sayısı, mesaj CTA | Güven oluşturma |
| Email tetikleyiciler | Manuel | Belge yükleme + 3 gün hareketsizlik otomatik | Retention |
| In-app bildirim | Yok | session() flash banner | Anlık geri bildirim |
| Mobil optimizasyon | Kısmi | 44px touch target, responsive form | Mobil dönüşüm |
| Yardım tooltipleri | Yok | CSS-only "?" tooltip sistemi | Self-servis |

---

## BOLUM 1 — DASHBOARD UX (P1 — Yüksek Etki)

### 1.1 Hero Section — Dinamik Mesaj

**Mevcut Durum:** Statik tek karşılama mesajı, her ziyaretçiye aynı içerik.

**Hedef Durum:** `PortalController::dashboard()` içinde PHP tarafında 5-6 segment hesapla, Blade ile render et. A/B framework yok, JS gerekmez.

**Teknik Yaklaşım:**

```php
// Guest/PortalController.php — dashboard() metoduna eklenecek
$heroSegment = match(true) {
    $application->stage === 'new' && $application->created_at->diffInHours() < 48
        => 'welcome_new',       // "Hoş geldin! İlk adımını atmak için hazır mısın?"
    $application->missingDocuments()->count() > 3
        => 'docs_urgent',       // "3 belge eksik — hadi tamamlayalım"
    $application->senior_id === null
        => 'awaiting_senior',   // "Danışmanın yakında atanacak"
    $application->stage === 'documents_complete'
        => 'docs_complete',     // "Belgeler tamam! Değerlendirme aşamasındasın"
    $application->stage === 'converted'
        => 'converted',         // "Tebrikler! Artık resmi öğrencisin"
    default
        => 'in_progress',       // "Süreç devam ediyor — {tamamlama_%} tamamlandı"
};
// Blade: @include('guest.partials.hero-segment', ['segment' => $heroSegment])
```

**Blade partial:** `resources/views/guest/partials/hero-segment.blade.php` — `@switch($segment)` ile 5-6 kart, her biri farklı başlık, alt başlık, ikon rengi. Mevcut `.card` CSS kullanılacak.

**Öncelik:** P1 | **Tahmini Süre:** 0.5 gün

---

### 1.2 İnteraktif Timeline

**Mevcut Durum:** Statik 5 adımlı ilerleme çubuğu, adım açıklaması yok.

**Hedef Durum:** Her milestone için genişletilebilir kart: durum ikonu (tamamlandi / aktif pulse / bekliyor / kilitli), hover tooltip, "tahmini süre" etiketi, tıklayınca açılan açıklama.

**Teknik Yaklaşım:**

- CSS `@keyframes pulse` animasyonu — aktif adım için mavi nokta (JS gerekmez)
- Vanilla JS `toggleExpand(stepId)` — kart genişletme, tek fonksiyon
- `$progress` array zaten controller'da mevcut (`buildBaseViewData()`)
- Her adım için `duration_label` (örn. "2-3 gün") ve `hint` alanı PHP'de tanımlanacak

```javascript
// public/js/guest-dashboard.js — yeni fonksiyon
function toggleStep(el) {
    const detail = el.nextElementSibling;
    const isOpen = detail.style.maxHeight;
    document.querySelectorAll('.timeline-detail').forEach(d => d.style.maxHeight = '');
    if (!isOpen) detail.style.maxHeight = detail.scrollHeight + 'px';
}
```

CSS pulse (portal-unified-v2.css'e eklenecek):
```css
@keyframes guestPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(59,130,246,.4); }
    50%       { box-shadow: 0 0 0 6px rgba(59,130,246,0); }
}
.timeline-step.active .step-dot { animation: guestPulse 2s infinite; }
```

**Öncelik:** P1 | **Tahmini Süre:** 1 gün

---

### 1.3 Next Step CTA Card

**Mevcut Durum:** F5 (Portal Optimizasyon Paketi v5.0) ile kısmen uygulandı — `$nextStep` değişkeni controller'da mevcut.

**Hedef Durum:** 4 durum-aware buton durumu; her durum için farklı renk, etiket, ikon.

**Teknik Yaklaşım:**

```php
// Controller'da $nextStep zaten var — sadece status alanı eklenir
$nextStep['btn_state'] = match($nextStep['status']) {
    'not_started' => ['label' => 'Başla', 'class' => 'btn ok',  'icon' => '▶'],
    'in_progress' => ['label' => 'Devam Et', 'class' => 'btn', 'icon' => '→'],
    'waiting'     => ['label' => 'Bekleniyor', 'class' => 'btn alt', 'icon' => '⏳'],
    'completed'   => ['label' => 'Tamamlandı', 'class' => 'btn ok', 'icon' => '✓'],
};
```

Blade partial: `resources/views/guest/partials/next-step-cta.blade.php` — mevcut F5 implementasyonunu genişletecek, yeni dosya oluşturmayacak.

**Öncelik:** P1 | **Tahmini Süre:** 0.5 gün

---

### 1.4 Aktivite Feed (Sosyal Kanıt)

**Mevcut Durum:** Yok.

**Hedef Durum:** Dashboard'da küçük bir bilgi şeridi: "Son 24 saatte 7 aday belgelerini tamamladı" — gerçek zamanlı değil, 5 dakikada bir cache'lenen DB sorgusu.

**Teknik Yaklaşım:**

```php
// Guest/PortalController.php dashboard() metoduna eklenecek
$activityFeed = Cache::remember('guest_activity_feed', 300, function () {
    return [
        'docs_completed_24h' => GuestApplication::whereHas('documents', function($q) {
            $q->where('updated_at', '>=', now()->subDay());
        })->distinct()->count(),
        'applications_today' => GuestApplication::whereDate('created_at', today())->count(),
    ];
});
```

Blade'de: tek satır `<p class="activity-feed-text">...` olarak render edilir. WebSocket yok, polling yok — sadece sayfa yüklendiğinde gösterilir.

**Öncelik:** P2 | **Tahmini Süre:** 0.5 gün

---

## BOLUM 2 — BASVURU FORMU UX (P1 — En Yüksek Etki)

**Mevcut kod:** `resources/views/guest/registration-form.blade.php` — `grf-*` CSS sınıfları, sticky nav, metrics strip, progress bar mevcut. Form muhtemelen tek sayfada tüm alanları gösteriyor.

### 2.1 Multi-Step Wizard (abandon rate düzeltme)

**Mevcut Durum:** Tüm alanlar tek sayfada → tahmini %60 abandon rate.

**Hedef Durum:** 4 adımlı wizard (AI plan önerisi 7 adım — mevcut kod yapısına göre 4 ile sınırlandırıldı):
- **Adım 1 — Kişisel:** isim, email, telefon, doğum tarihi, uyruk
- **Adım 2 — Akademik:** eğitim seviyesi, GPA, dil seviyeleri, sınav puanları
- **Adım 3 — Program:** hedef şehirler, bölüm tercihi, paket seçimi, bütçe
- **Adım 4 — Onay:** özet + KVKK onayı + gönder

**Teknik Yaklaşım — Vanilla JS, localStorage, PHP session:**

```javascript
// public/js/guest-registration-form.js — mevcut dosyaya eklenecek

const TOTAL_STEPS = 4;
let currentStep = parseInt(localStorage.getItem('grf_step') || '1');

function showStep(n) {
    document.querySelectorAll('.grf-step-panel').forEach((el, i) => {
        el.hidden = (i + 1) !== n;
    });
    currentStep = n;
    localStorage.setItem('grf_step', n);
    updateProgressBar(n);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateProgressBar(n) {
    const pct = Math.round((n / TOTAL_STEPS) * 100);
    document.querySelector('.grf-progress-fill').style.width = pct + '%';
    document.querySelector('.grf-progress-pct').textContent = pct + '%';
}

function gotoNext() {
    if (!validateCurrentStep()) return;
    autoSaveToLocalStorage();
    if (currentStep < TOTAL_STEPS) showStep(currentStep + 1);
}

function gotoPrev() {
    if (currentStep > 1) showStep(currentStep - 1);
}
```

Blade'de her adım `<div class="grf-step-panel" data-step="N" hidden>` ile sarılır. PHP tarafında `session('grf_draft')` ile yedek kayıt tutulur — `POST /guest/registration/draft` endpoint.

**Öncelik:** P1 | **Tahmini Süre:** 2 gün

---

### 2.2 Form İlerleme Göstergesi

**Mevcut Durum:** `grf-progress-bar` ve `grf-progress-fill` CSS sınıfları zaten mevcut (dosya 39. satır).

**Hedef Durum:** "Adım 2/4 — %50 tamamlandı" metni + bar birlikte. Adım numarası ve tahmini kalan süre.

**Teknik Yaklaşım:** Mevcut `.grf-progress-card` bloğuna `<span class="grf-progress-pct">` ve `<span class="grf-step-label">Adım 1 / 4</span>` eklenir. JS'de `updateProgressBar()` bu iki span'ı da günceller.

Adım başlıkları için küçük breadcrumb:
```html
<!-- grf-nav mevcut — step aktif class eklenir -->
<button class="grf-nav-btn active" data-step="1" onclick="showStep(1)">Kişisel</button>
<button class="grf-nav-btn" data-step="2" onclick="showStep(2)">Akademik</button>
...
```

**Öncelik:** P1 | **Tahmini Süre:** 0.25 gün

---

### 2.3 Satır İçi Validasyon

**Mevcut Durum:** Validasyon sadece form gönderiminde tetikleniyor.

**Hedef Durum:** Her alan `blur` olduğunda gerçek zamanlı kontrol. Hata mesajları Türkçe, spesifik.

**Teknik Yaklaşım:**

```javascript
// public/js/guest-registration-form.js — mevcut dosyaya eklenecek

const validators = {
    email: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)
                  ? null : 'Geçerli bir e-posta adresi girin (ör. ad@gmail.com)',
    phone: (v) => v.replace(/\D/g,'').length >= 10
                  ? null : 'Telefon numarası en az 10 rakam içermelidir',
    first_name: (v) => v.trim().length >= 2
                  ? null : 'İsim en az 2 karakter olmalıdır',
};

document.querySelectorAll('.grf-field[data-validate]').forEach(input => {
    input.addEventListener('blur', function() {
        const fn = validators[this.dataset.validate];
        const err = fn ? fn(this.value) : null;
        showFieldError(this, err);
    });
});

function showFieldError(input, msg) {
    const errEl = input.parentElement.querySelector('.grf-field-error');
    if (errEl) errEl.textContent = msg || '';
    input.classList.toggle('is-invalid', !!msg);
}
```

Blade'de her input için: `<span class="grf-field-error" aria-live="polite"></span>`. CSS `.is-invalid` border-color kırmızı. Hata mesajları Türkçe ve açıklayıcı.

**Öncelik:** P1 | **Tahmini Süre:** 0.5 gün

---

### 2.4 Adım 4 — Özet & Onay

**Hedef Durum:** Son adımda tüm girilen bilgiler okunabilir tabloda gösterilir ("Değiştir" linkleri ile). KVKK onay checkbox'ları. Büyük, belirgin "Başvuruyu Gönder" butonu.

**Teknik Yaklaşım:** Blade partial `guest/partials/registration-summary.blade.php` — localStorage'daki veriyi okuyup JS ile doldurur, ya da PHP session'dan render eder. Gönder butonu mevcut formu POST eder.

**Öncelik:** P1 | **Tahmini Süre:** 0.5 gün (Bölüm 2.1 ile birlikte)

---

## BOLUM 3 — BELGE YONETIMI UX (P1)

**Mevcut kod:** `resources/views/guest/registration-documents.blade.php` — `grd-*` CSS sınıfları, KPI strip (4 kart), progress bar, missing-strip zaten mevcut (dosya ilk 50 satır). Altyapı iyi, UX katmanı eksik.

### 3.1 Belge Bazlı Rehberlik

**Mevcut Durum:** Belge listesi var fakat her belge türü için "ne yüklemeliyim?" açıklaması yok.

**Hedef Durum:** Her belge satırında küçük "Ne yüklemeliyim?" bölümü: açıklama metni, kabul edilen format, maks boyut, örnek dosya linki (opsiyonel).

**Teknik Yaklaşım:**

`config/institution_document_catalog.php`'ye mevcut kodlar için `description` ve `accepted_formats` alanları eklenecek:

```php
// config/institution_document_catalog.php — mevcut kayıtlara eklenecek
'PASSPORT' => [
    'name'             => 'Pasaport',
    'description'      => 'Kimlik sayfası (fotoğraflı sayfa) ve son giriş/çıkış damgaları dahil tüm sayfalar.',
    'accepted_formats' => ['pdf', 'jpg', 'png'],
    'max_size_mb'      => 5,
    'example_hint'     => 'Pasaportunuzun 2. sayfasını tarayın.',
],
```

Blade'de her belge satırına collapse toggle:
```html
<button class="grd-doc-hint-toggle" onclick="toggleHint(this)" aria-expanded="false">
    Ne yüklemeliyim?
</button>
<div class="grd-doc-hint" hidden>
    {{ $doc->description }} — Kabul edilen: {{ implode(', ', $doc->accepted_formats) }}
</div>
```

Vanilla JS: `toggleHint(btn)` → `btn.nextElementSibling.hidden = !btn.nextElementSibling.hidden`

**Öncelik:** P1 | **Tahmini Süre:** 1 gün

---

### 3.2 Yükleme Durumu Görsel Feedback

**Mevcut Durum:** Native `<input type="file">` veya basit upload formu.

**Hedef Durum:** Drag-drop zone + PDF iframe preview + dosya boyutu / format uyarısı. Yükleme sırasında spinner.

**Teknik Yaklaşım — Vanilla JS FileReader API:**

```javascript
// public/js/guest-registration-documents.js — mevcut dosyaya eklenecek

function initDropZone(zoneEl, inputEl) {
    zoneEl.addEventListener('dragover', e => {
        e.preventDefault();
        zoneEl.classList.add('drag-over');
    });
    zoneEl.addEventListener('dragleave', () => zoneEl.classList.remove('drag-over'));
    zoneEl.addEventListener('drop', e => {
        e.preventDefault();
        zoneEl.classList.remove('drag-over');
        handleFiles(e.dataTransfer.files, zoneEl);
    });
    inputEl.addEventListener('change', e => handleFiles(e.target.files, zoneEl));
}

function handleFiles(files, zoneEl) {
    const file = files[0];
    if (!file) return;

    const maxMB = parseInt(zoneEl.dataset.maxMb || '5');
    const allowed = (zoneEl.dataset.formats || 'pdf,jpg,png').split(',');
    const ext = file.name.split('.').pop().toLowerCase();

    if (file.size > maxMB * 1024 * 1024) {
        showUploadError(zoneEl, `Dosya boyutu ${maxMB}MB'ı aşıyor (${(file.size/1048576).toFixed(1)}MB)`);
        return;
    }
    if (!allowed.includes(ext)) {
        showUploadError(zoneEl, `Kabul edilen formatlar: ${allowed.join(', ').toUpperCase()}`);
        return;
    }

    // PDF preview via iframe
    if (ext === 'pdf') {
        const url = URL.createObjectURL(file);
        const preview = zoneEl.querySelector('.grd-doc-preview');
        if (preview) {
            preview.innerHTML = `<iframe src="${url}" style="width:100%;height:200px;border:none;border-radius:8px;"></iframe>`;
        }
    }
    // Image preview
    if (['jpg','jpeg','png'].includes(ext)) {
        const reader = new FileReader();
        reader.onload = ev => {
            const preview = zoneEl.querySelector('.grd-doc-preview');
            if (preview) preview.innerHTML = `<img src="${ev.target.result}" style="max-height:160px;border-radius:8px;">`;
        };
        reader.readAsDataURL(file);
    }

    // Transfer to real input
    const dt = new DataTransfer();
    dt.items.add(file);
    zoneEl.querySelector('input[type=file]').files = dt.files;
    zoneEl.querySelector('.grd-drop-label').textContent = file.name;
}
```

CSS: `.grd-drop-zone` dashed border, `.drag-over` aktif renk. Dosya input'u `display:none`, label ile tetikleme (mevcut CSS kuralı uyarınca).

**Öncelik:** P1 | **Tahmini Süre:** 1 gün

---

### 3.3 Eksik Belge Hatırlatıcı Widget

**Mevcut Durum:** `grd-missing-strip` zaten var (dosya 47. satır) — kırmızı border, uyarı alanı. İçerik eksik.

**Hedef Durum:** Dashboard ana sayfasında da özet widget: "3 zorunlu belge eksik" + "Belgelere git" linki.

**Teknik Yaklaşım:**

```php
// Guest/PortalController.php — dashboard() metoduna eklenecek
$missingDocCount = $application->requiredDocuments()
    ->whereNull('uploaded_at')
    ->whereNull('waived_at')
    ->count();
// Blade: @if($missingDocCount > 0) ... @endif
```

Dashboard'da mevcut `.card` içinde render edilir. `grd-missing-strip` CSS'i `portal-unified-v2.css`'e taşınabilir ya da mevcut `registration-documents.blade.php`'den kopyalanır.

**Öncelik:** P1 | **Tahmini Süre:** 0.25 gün

---

## BOLUM 4 — SENIOR ATAMA UX (P2)

### 4.1 Senior Profil Kartı

**Mevcut Durum:** Atanan senior için sadece isim ve e-posta gösteriliyor.

**Hedef Durum:** Bilgi dolu profil kartı: avatar (baş harf ile fallback), uzmanlık etiketleri, başarılı öğrenci sayısı, ilk mesaj CTA.

**Teknik Yaklaşım:**

`users` tablosuna migration ile ek alanlar (eğer yoksa):
```php
// Yeni migration: add_profile_fields_to_users_table
$table->string('bio', 500)->nullable();
$table->string('expertise_tags')->nullable(); // CSV: "Almanya,Mühendislik,Yurt Dışı"
$table->string('photo_url')->nullable();
```

Blade: `resources/views/guest/partials/senior-profile-card.blade.php` — yeni partial. Avatar: photo_url varsa `<img>`, yoksa ismin baş harfi ile `<div class="avatar-initials">`.

```html
<!-- guest/partials/senior-profile-card.blade.php -->
<div class="card senior-card">
    <div class="senior-avatar">
        @if($senior->photo_url)
            <img src="{{ $senior->photo_url }}" alt="{{ $senior->full_name }}">
        @else
            <div class="avatar-initials">{{ strtoupper(substr($senior->first_name,0,1)) }}</div>
        @endif
    </div>
    <div class="senior-info">
        <p class="senior-name">{{ $senior->full_name }}</p>
        @foreach(explode(',', $senior->expertise_tags ?? '') as $tag)
            <span class="badge info">{{ trim($tag) }}</span>
        @endforeach
        <p class="senior-stat">{{ $senior->successfulStudentsCount() }} öğrenci yerleştirdi</p>
    </div>
    <a href="{{ route('guest.messages') }}" class="btn ok">İlk Mesajı Gönder</a>
</div>
```

`User::successfulStudentsCount()` — `student_assignments` üzerinden `stage = 'converted'` count. `Cache::remember(3600)` ile.

**Öncelik:** P2 | **Tahmini Süre:** 1 gün

---

### 4.2 İlk Randevu CTA

**Mevcut Durum:** Atama sonrası yönlendirme yok.

**Hedef Durum:** Senior atanmışsa ve henüz randevu yoksa dashboard'da banner: "Danışmanınızla ilk görüşmeyi planlayın."

**Teknik Yaklaşım:**

```php
// PortalController::dashboard()
$showAppointmentCta = $application->senior_id !== null
    && $application->appointments()->where('status', '!=', 'cancelled')->count() === 0;
```

Blade'de `@if($showAppointmentCta)` ile conditional banner. `/guest/appointments` route'una link verir. Kapatılabilir: `<button onclick="this.closest('.appt-cta').remove()">✕</button>` — localStorage'a `grf_appt_cta_closed` kaydedilir.

**Öncelik:** P2 | **Tahmini Süre:** 0.5 gün

---

## BOLUM 5 — BİLDİRİM & İLETİŞİM (P2)

### 5.1 Bağlamsal Yardım Tooltipleri

**Mevcut Durum:** Hiçbir alanda "?" yardım ikonu yok.

**Hedef Durum:** Önemli alanlarda (GPA nedir? Pasaport geçerlilik süresi? Paket farkları?) `?` ikonu, üzerine gelince CSS tooltip açılır.

**Teknik Yaklaşım — CSS only, JS yok:**

```css
/* portal-unified-v2.css'e eklenecek */
.u-tooltip-wrap { position: relative; display: inline-block; }
.u-tooltip-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 16px; height: 16px; border-radius: 50%;
    background: var(--u-muted); color: #fff; font-size: 10px;
    font-weight: 700; cursor: help; margin-left: 4px;
}
.u-tooltip-text {
    display: none; position: absolute; bottom: calc(100% + 6px); left: 50%;
    transform: translateX(-50%); background: #1e293b; color: #fff;
    font-size: 12px; line-height: 1.4; padding: 8px 12px; border-radius: 6px;
    width: 220px; z-index: 100; pointer-events: none;
}
.u-tooltip-wrap:hover .u-tooltip-text,
.u-tooltip-wrap:focus-within .u-tooltip-text { display: block; }
```

Blade kullanımı:
```html
<label>GPA
    <span class="u-tooltip-wrap">
        <span class="u-tooltip-icon" tabindex="0">?</span>
        <span class="u-tooltip-text">Not ortalamanız (4.0 üzerinden). Almanya üniversiteleri genellikle 2.5+ bekler.</span>
    </span>
</label>
```

**Öncelik:** P2 | **Tahmini Süre:** 0.5 gün

---

### 5.2 Email Tetikleyicileri

**Mevcut Durum:** Manuel email gönderimi. `NotificationDispatch` ve `SendNotificationJob` altyapısı mevcut.

**Hedef Durum:**
1. Belge yükleme sonrası → "Belgeniz alındı" onay e-postası
2. 3 gün hareketsizlik → "Sizi bekliyoruz" hatırlatma e-postası

**Teknik Yaklaşım — Mevcut altyapı genişletilecek:**

**Tetikleyici 1 — Belge yükleme:**
```php
// GuestOpsController veya DocumentController — upload() metoduna eklenecek
SendNotificationJob::dispatch($application->user, 'document_received', [
    'document_name' => $document->name,
    'uploaded_at'   => now()->format('d.m.Y H:i'),
]);
```

**Tetikleyici 2 — Hareketsizlik (scheduled command):**
```php
// app/Console/Commands/GuestInactivityReminderCommand.php — yeni command
// Zamanlama: Kernel.php'de daily('10:00')
GuestApplication::where('stage', '!=', 'converted')
    ->where('updated_at', '<', now()->subDays(3))
    ->whereDoesntHave('notificationDispatches', function($q) {
        $q->where('type', 'inactivity_reminder')
          ->where('created_at', '>', now()->subDays(3));
    })
    ->chunk(50, function($applications) {
        foreach ($applications as $app) {
            SendNotificationJob::dispatch($app->user, 'inactivity_reminder', []);
        }
    });
```

`notification_templates.php`'e `document_received` ve `inactivity_reminder` şablonları eklenecek.

**Öncelik:** P2 | **Tahmini Süre:** 1 gün

---

### 5.3 In-App Bildirim Banner

**Mevcut Durum:** Başarı/hata mesajları için mevcut flash sistemi sınırlı.

**Hedef Durum:** Dashboard üstünde "Belgeniz onaylandı" tarzı flash banner, 5 saniye sonra otomatik kapanır.

**Teknik Yaklaşım:**

PHP tarafında flash:
```php
// Controller action sonrası
session()->flash('portal_notice', ['type' => 'ok', 'msg' => 'Pasaportunuz onaylandı.']);
```

Blade layout `guest/layouts/app.blade.php`'e eklenecek:
```html
@if(session('portal_notice'))
<div class="portal-flash-banner badge {{ session('portal_notice.type') }}"
     id="portalFlash" role="alert" aria-live="polite">
    {{ session('portal_notice.msg') }}
    <button onclick="document.getElementById('portalFlash').remove()" aria-label="Kapat">✕</button>
</div>
<script>setTimeout(() => document.getElementById('portalFlash')?.remove(), 5000);</script>
@endif
```

CSS sınıfları mevcut `.badge.ok/.warn/.danger` kullanılır — yeni CSS yazılmaz.

**Öncelik:** P2 | **Tahmini Süre:** 0.25 gün

---

## BOLUM 6 — MOBİL & PERFORMANS (P2)

### 6.1 Mobil Form Optimizasyonu

**Mevcut Durum:** `grf-metrics` grid responsive (max-width:600px → 1 kolon). Input boyutları standart.

**Hedef Durum:** Touch-friendly minimum 44px yükseklik, uygun `inputmode` öznitelikleri, büyük butonlar.

**Teknik Yaklaşım:**

```css
/* portal-unified-v2.css'e responsive block eklenecek */
@media (max-width: 600px) {
    .grf-step-panel input,
    .grf-step-panel select,
    .grf-step-panel textarea {
        min-height: 44px;
        font-size: 16px; /* iOS zoom önleme */
    }
    .grf-step-panel .btn {
        min-height: 48px;
        width: 100%;
    }
}
```

Blade'de `inputmode` öznitelikleri:
- E-posta: `inputmode="email"` `autocomplete="email"`
- Telefon: `inputmode="tel"` `autocomplete="tel"`
- Sayı: `inputmode="numeric"`

**Öncelik:** P2 | **Tahmini Süre:** 0.5 gün

---

### 6.2 Form Auto-Save (localStorage)

**Mevcut Durum:** Sayfa yenilenince form verisi kaybolur.

**Hedef Durum:** Her alan değiştiğinde 800ms debounce ile localStorage'a kaydet. Sayfa yüklenince restore et. "Otomatik kaydedildi" göstergesi.

**Teknik Yaklaşım:**

```javascript
// public/js/guest-registration-form.js — mevcut dosyaya eklenecek

const LS_KEY = 'mentorde_grf_draft';
let saveTimer = null;

function autoSaveToLocalStorage() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
        const form = document.getElementById('guestRegForm');
        const data = {};
        new FormData(form).forEach((v, k) => { data[k] = v; });
        localStorage.setItem(LS_KEY, JSON.stringify({ step: currentStep, data, ts: Date.now() }));
        showSaveIndicator();
    }, 800);
}

function restoreFromLocalStorage() {
    const raw = localStorage.getItem(LS_KEY);
    if (!raw) return;
    const { step, data, ts } = JSON.parse(raw);
    // 7 günden eskiyse görmezden gel
    if (Date.now() - ts > 7 * 86400 * 1000) { localStorage.removeItem(LS_KEY); return; }
    Object.entries(data).forEach(([k, v]) => {
        const el = document.querySelector(`[name="${k}"]`);
        if (el) el.value = v;
    });
    showStep(step);
}

function showSaveIndicator() {
    const ind = document.getElementById('grfSaveIndicator');
    if (ind) { ind.textContent = 'Otomatik kaydedildi'; ind.hidden = false;
               setTimeout(() => ind.hidden = true, 2000); }
}

document.addEventListener('DOMContentLoaded', restoreFromLocalStorage);
document.getElementById('guestRegForm')?.addEventListener('input', autoSaveToLocalStorage);
```

PHP session backup: form submit edildiğinde `session(['grf_draft' => $validated])` — kullanıcı giriş yaparsa başka cihazdan devam edebilir (gelecek sprint).

**Öncelik:** P2 | **Tahmini Süre:** 0.5 gün

---

## BOLUM 7 — DIŞARIDA BIRAKILAN (ROADMAP V3+)

| Özellik | Neden Şimdi Değil | Gelecek Versiyon |
|---------|------------------|------------------|
| Vue.js / React bileşenleri | Stack değişikliği gerektirir, mevcut Blade kod tabanını kırar | v4.0+ (karar alınırsa) |
| WebSocket real-time | Pusher / Soketi sunucu altyapısı, maliyet, bakım gerektirir | v3.5+ |
| A/B testing framework | Analytics altyapısı (event tracking) önce kurulmalı | v3.0+ analytics ile birlikte |
| AI chatbot 7/24 | Dış SaaS bağımlılığı, maliyet, KVKK uyum sorunu | v3.5+ |
| Topluluk / peer forum | Ayrı bir ürün; moderasyon gerektiriyor | Müstakil modül |
| 30+ hero mesaj varyasyonu | 6 segment yeterli; fazlası bakım yükü | İhtiyaç olursa konfigürasyon dosyasına taşınır |
| Gamification (rozet animasyonları) | Nice-to-have, MVP öncelikleri tamamlanmadan erken | v3.0+ |
| Üniversite kabul API entegrasyonu | Alman üniversitelerinin halka açık API'si yok | v4.0+ (üniversite ortaklıkları sonrası) |
| 7 adımlı form wizard (AI plan'daki) | Mevcut form alanı sayısına göre 4 adım yeterli; 7 adım kullanıcıyı yorar | Alan sayısı büyürse v3.0 |
| Çokdil desteği (DE/AR/ZH) | i18n altyapısı hazırlık gerektirir, şu an TR + EN yeterli | v3.0 |
| SMS/WhatsApp 2FA form aşaması | Mevcut 2FA altyapısı (RBAC v1.1) guest'e taşınabilir ama MVP dışı | v3.0 |

---

## UYGULAMA TAKVİMİ

| Sprint | Özellikler | Tahmini Süre | Öncelik |
|--------|-----------|-------------|---------|
| Sprint 1 — Hafta 1 | Dashboard hero segment (1.1) + interaktif timeline (1.2) + next step CTA (1.3) + eksik belge widget (3.3) | 2.25 gün | P1 |
| Sprint 2 — Hafta 2 | Multi-step form wizard (2.1) + ilerleme göstergesi (2.2) + satır içi validasyon (2.3) + form auto-save (6.2) | 3.25 gün | P1 |
| Sprint 3 — Hafta 3 | Belge rehberliği (3.1) + drag-drop upload + preview (3.2) + aktivite feed (1.4) + mobil optimizasyon (6.1) | 3 gün | P1/P2 |
| Sprint 4 — Hafta 4 | Senior profil kartı (4.1) + randevu CTA (4.2) + email tetikleyiciler (5.2) + tooltip yardım (5.1) + flash banner (5.3) | 3.25 gün | P2 |

**Toplam tahmini:** ~11.75 gün (yaklaşık 2.5 hafta yoğun çalışma ile, 4 haftaya yayılmış)

---

## BASARI METRİKLERİ

| Metrik | Mevcut | Hedef | Ölçüm Yöntemi |
|--------|--------|-------|---------------|
| Form tamamlama oranı | ~40% (tahmin) | 65–70% | `guest_applications` kayıt sayısı / form sayfa hit'i (access log) |
| Belge yükleme oranı | ~55% (tahmin) | 75–80% | `documents` yüklenen / `guest_required_documents` zorunlu sayısı |
| Ortalama form doldurma süresi | ~25 dk | ~12 dk | `created_at` - session başlangıç timestamp farkı |
| Destek talep hacmi | Ölçülmüyor | -%30 (6 hafta sonra) | `guest_tickets` sayısı, haftalık karşılaştırma |
| Guest → Student dönüşümü | Bilinmiyor | Baseline oluştur, sonra %10 artış hedefle | `converted_student_id IS NOT NULL` count / toplam guest |
| Mobil tamamlama farkı | Ölçülmüyor | Mobil = masaüstü ±10% | User-agent bazlı `guest_applications` ayrımı |
| Hareketsizlik sonrası geri dönüş | 0 (e-posta yok) | %15 geri dönüş | `inactivity_reminder` sonrası login istatistiği |

---

## TEKNİK NOTLAR

### CSS Animasyonlar
Sadece `@keyframes` — JS gerekmez. `guestPulse` aktif timeline adımı için. CSS transition `width 0.4s ease` progress bar için (zaten `grf-progress-fill`'de mevcut).

### Form Wizard
`localStorage` birincil kayıt, PHP `session()` ikincil yedek. Wizard step'leri `hidden` özniteliği ile toggle — CSS `display:none` değil (erişilebilirlik). ARIA: `aria-current="step"`, `aria-live="polite"` hata bölgesi.

### Upload Preview
`FileReader` API + `URL.createObjectURL()` — PDF için `<iframe>`, görsel için `<img>`. `URL.revokeObjectURL()` bellek sızıntısını önlemek için çağrılacak.

### Segment Mesajları
`PortalController::dashboard()` içinde PHP `match()` — 5-6 sabit segment, config'e taşınmaz (basit kalması için). Mesaj metinleri doğrudan Blade partial'da.

### Aktivite Feed
Tek `COUNT(DISTINCT guest_application_id)` sorgusu. `Cache::remember('guest_activity_feed', 300, ...)` — 5 dakika TTL. WebSocket yok, polling yok.

### Mobil
`portal-unified-v2.css`'e `@media (max-width: 600px)` bloğu eklenecek. `font-size: 16px` iOS'ta otomatik zoom'u engeller.

### Mevcut Testler
74 PHPUnit test + 598 assertion korunacak. Yeni email command için `GuestInactivityReminderCommandTest` eklenecek. Wizard endpoint için `GuestRegistrationWizardTest` eklenecek.

### Dosya Değişikliği Özeti (Sprint Başlamadan Önce)
- **Değiştirilecek:** `PortalController.php`, `registration-form.blade.php`, `registration-documents.blade.php`, `guest/layouts/app.blade.php`, `portal-unified-v2.css`, `guest-registration-form.js`, `guest-registration-documents.js`
- **Yeni eklenecek:** `partials/hero-segment.blade.php`, `partials/senior-profile-card.blade.php`, `partials/next-step-cta.blade.php`, `GuestInactivityReminderCommand.php`
- **Migration:** `add_profile_fields_to_users_table` (bio, expertise_tags, photo_url — eğer yoksa)
- **Config:** `institution_document_catalog.php`'e `description`, `accepted_formats`, `max_size_mb` alanları

---

*Plan hazırlanma tarihi: 2026-03-20. Sonraki revizyon: Sprint 2 sonunda (2026-04-03 tahmini).*

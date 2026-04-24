<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $brand = $brandName ?? config('brand.name', 'MentorDE');
@endphp
<title>Satış Ortaklığı Başvurusu — {{ $brand }}</title>
<meta name="robots" content="noindex, follow">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">

<style>
:root { --primary:#5b2e91; --primary-dark:#4a2377; --primary-deep:#3d1c67; --primary-soft:#f1e8fb; --accent:#e8b931; --text:#12233a; --muted:#5e7187; --line:#d9e2ee; --success:#16a34a; --danger:#dc2626; }
* { box-sizing:border-box; }
html, body { margin:0; padding:0; }
body { font-family:"Plus Jakarta Sans", sans-serif; background:linear-gradient(140deg, #f7f3ff, #f9fafd); min-height:100vh; color:var(--text); line-height:1.6; }
.serif { font-family:"DM Serif Display", serif; }
a { color:var(--primary); text-decoration:none; }

.wz-shell { max-width:720px; margin:30px auto; padding:0 18px; }
.wz-header { text-align:center; margin-bottom:24px; }
.wz-logo { font-family:"DM Serif Display", serif; font-size:32px; color:var(--primary); line-height:1; }
.wz-logo span { color:var(--primary-dark); font-style:italic; }
.wz-back { display:inline-block; margin-top:6px; color:var(--muted); font-size:13px; }

/* Progress bar */
.wz-progress { background:#fff; border:1px solid var(--line); border-radius:16px; padding:20px 24px; margin-bottom:16px; }
.wz-progress-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; position:relative; }
.wz-progress-bar::before {
    content:''; position:absolute; top:50%; left:8%; right:8%; height:2px;
    background:var(--line); z-index:0;
}
.wz-progress-step {
    width:34px; height:34px; border-radius:50%; background:#fff;
    border:2px solid var(--line); display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:14px; color:var(--muted); position:relative; z-index:1;
    transition:all .2s;
}
.wz-progress-step.active { background:var(--primary); border-color:var(--primary); color:#fff; box-shadow:0 0 0 5px rgba(91,46,145,.15); }
.wz-progress-step.done { background:var(--success); border-color:var(--success); color:#fff; }
.wz-progress-labels { display:flex; justify-content:space-between; margin-top:6px; }
.wz-progress-labels span { font-size:10px; color:var(--muted); text-align:center; width:34px; overflow:visible; white-space:nowrap; }
.wz-progress-labels span.active { color:var(--primary); font-weight:700; }
@media(max-width:620px) { .wz-progress-labels { display:none; } }

.wz-percent {
    height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden; margin-top:4px;
}
.wz-percent-fill {
    height:100%; background:linear-gradient(90deg, var(--primary), var(--accent));
    border-radius:3px; transition:width .4s ease;
}

/* Card */
.wz-card { background:#fff; border:1px solid var(--line); border-radius:20px; padding:36px; box-shadow:0 8px 24px rgba(91,46,145,.08); }
.wz-step { display:none; animation:fadeIn .3s ease; }
.wz-step.active { display:block; }
@keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
.wz-step-title { font-family:"DM Serif Display", serif; font-size:28px; color:var(--primary-deep); margin:0 0 8px; line-height:1.2; }
.wz-step-sub { color:var(--muted); font-size:14px; margin:0 0 24px; }

/* Form fields */
.wz-field { margin-bottom:16px; }
.wz-field label { display:block; font-size:13px; font-weight:700; margin-bottom:6px; color:var(--text); }
.wz-field label .req { color:var(--danger); margin-left:3px; }
.wz-field .hint { font-size:12px; color:var(--muted); margin-top:4px; }
.wz-field input, .wz-field select, .wz-field textarea {
    width:100%; padding:12px 14px; border:1.5px solid var(--line); border-radius:10px;
    font-size:14px; font-family:inherit; background:#fff; transition:all .15s;
}
.wz-field input:focus, .wz-field select:focus, .wz-field textarea:focus {
    outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(91,46,145,.12);
}
.wz-field textarea { min-height:100px; resize:vertical; }
.wz-field.error input, .wz-field.error select, .wz-field.error textarea { border-color:var(--danger); }
.wz-field-error { color:var(--danger); font-size:12px; margin-top:4px; display:none; }
.wz-field.error .wz-field-error { display:block; }

.wz-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:560px) { .wz-grid2 { grid-template-columns:1fr; } }

/* Plan cards */
.wz-plan-cards { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; }
@media(max-width:620px) { .wz-plan-cards { grid-template-columns:1fr; } }
.wz-plan-card {
    border:2.5px solid var(--line); border-radius:14px; padding:20px 16px;
    cursor:pointer; text-align:center; transition:all .2s; background:#fff;
    position:relative;
}
.wz-plan-card:hover { border-color:var(--primary); background:var(--primary-soft); transform:translateY(-2px); }
.wz-plan-card.active { border-color:var(--primary); background:var(--primary-soft); box-shadow:0 8px 20px rgba(91,46,145,.18); }
.wz-plan-card.active::after { content:'✓'; position:absolute; top:12px; right:12px; width:22px; height:22px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; }
.wz-plan-card input { position:absolute; opacity:0; pointer-events:none; }
.wz-plan-card .icon { font-size:34px; margin-bottom:8px; }
.wz-plan-card .name { font-weight:800; font-size:14px; color:var(--primary-deep); margin-bottom:4px; }
.wz-plan-card .desc { font-size:11px; color:var(--muted); line-height:1.4; }

/* Option toggle (business_type) */
.wz-radio-group { display:flex; gap:8px; flex-wrap:wrap; }
.wz-radio-card {
    flex:1; min-width:140px; border:2px solid var(--line); border-radius:10px; padding:14px;
    text-align:center; cursor:pointer; transition:all .15s; background:#fff; font-size:13px; font-weight:700;
    color:var(--muted);
}
.wz-radio-card:hover { border-color:var(--primary); }
.wz-radio-card.active { border-color:var(--primary); background:var(--primary-soft); color:var(--primary-deep); }
.wz-radio-card input { display:none; }

/* Checkbox */
.wz-check-box {
    display:flex; gap:12px; align-items:flex-start; padding:14px 16px;
    border:1.5px solid var(--line); border-radius:10px; cursor:pointer;
    transition:all .15s;
}
.wz-check-box:hover { border-color:var(--primary); background:var(--primary-soft); }
.wz-check-box input { margin-top:3px; }
.wz-check-box span { font-size:13px; color:var(--text); }

/* Review summary */
.wz-summary { background:var(--primary-soft); border-radius:12px; padding:18px 20px; margin-bottom:16px; }
.wz-summary-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid rgba(91,46,145,.1); font-size:13px; }
.wz-summary-row:last-child { border-bottom:0; }
.wz-summary-row .lbl { color:var(--muted); }
.wz-summary-row .val { color:var(--primary-deep); font-weight:700; text-align:right; }

/* Navigation */
.wz-nav {
    display:flex; justify-content:space-between; gap:12px; margin-top:28px; padding-top:22px;
    border-top:1px solid var(--line);
}
.wz-btn {
    padding:13px 22px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; border:none;
    display:inline-flex; align-items:center; gap:8px; transition:all .15s; font-family:inherit;
}
.wz-btn.prev { background:#fff; color:var(--muted); border:1.5px solid var(--line); }
.wz-btn.prev:hover { border-color:var(--primary); color:var(--primary); }
.wz-btn.next, .wz-btn.submit { background:var(--primary); color:#fff; box-shadow:0 4px 12px rgba(91,46,145,.3); margin-left:auto; }
.wz-btn.next:hover, .wz-btn.submit:hover { background:var(--primary-dark); transform:translateY(-1px); box-shadow:0 8px 20px rgba(91,46,145,.4); }
.wz-btn.submit { background:var(--success); box-shadow:0 4px 12px rgba(22,163,74,.3); }
.wz-btn.submit:hover { background:#15803d; box-shadow:0 8px 20px rgba(22,163,74,.4); }

/* Info note */
.wz-note {
    background:var(--primary-soft); border-left:3px solid var(--primary);
    padding:12px 14px; border-radius:8px; margin:14px 0; font-size:13px; color:var(--primary-deep);
}

/* Error box */
.wz-errors {
    background:#fee2e2; color:#991b1b; padding:12px 14px; border-radius:10px;
    margin-bottom:16px; font-size:13px;
}
</style>
</head>
<body>

<div class="wz-shell">
    <div class="wz-header">
        <a href="{{ route('public.dealer-landing') }}" class="wz-logo">
            mentor<span>de</span>
        </a>
        <div class="wz-back">
            <a href="{{ route('public.dealer-landing') }}">← Programa geri dön</a>
        </div>
    </div>

    {{-- Progress --}}
    <div class="wz-progress">
        <div class="wz-progress-bar">
            <div class="wz-progress-step active" data-step="1">1</div>
            <div class="wz-progress-step" data-step="2">2</div>
            <div class="wz-progress-step" data-step="3">3</div>
            <div class="wz-progress-step" data-step="4">4</div>
            <div class="wz-progress-step" data-step="5">5</div>
        </div>
        <div class="wz-progress-labels">
            <span class="active" data-step="1">Kişisel</span>
            <span data-step="2">Plan</span>
            <span data-step="3">Profil</span>
            <span data-step="4">Kaynak</span>
            <span data-step="5">Onay</span>
        </div>
        <div class="wz-percent"><div class="wz-percent-fill" id="wz-percent" style="width:20%;"></div></div>
    </div>

    <div class="wz-card">
        @if ($errors->any())
            <div class="wz-errors">
                <strong>Formda hata var:</strong>
                <ul style="margin:6px 0 0; padding-left:20px;">
                @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form id="wz-form" action="{{ route('public.dealer-application.store') }}" method="POST" data-track-skip>
            @csrf
            <input type="hidden" name="utm_source" value="{{ old('utm_source', $prefillUtm['utm_source'] ?? '') }}">
            <input type="hidden" name="utm_medium" value="{{ old('utm_medium', $prefillUtm['utm_medium'] ?? '') }}">
            <input type="hidden" name="utm_campaign" value="{{ old('utm_campaign', $prefillUtm['utm_campaign'] ?? '') }}">

            {{-- STEP 1: Kişisel --}}
            <div class="wz-step active" data-step="1">
                <h2 class="wz-step-title">👋 Tanışalım</h2>
                <p class="wz-step-sub">Öncelikle seni tanıyalım — sana nasıl ulaşacağımızı bilmemiz lazım.</p>

                <div class="wz-grid2">
                    <div class="wz-field">
                        <label>Ad <span class="req">*</span></label>
                        <input type="text" name="first_name" data-required value="{{ old('first_name') }}" autocomplete="given-name">
                        <div class="wz-field-error">Ad gerekli.</div>
                    </div>
                    <div class="wz-field">
                        <label>Soyad <span class="req">*</span></label>
                        <input type="text" name="last_name" data-required value="{{ old('last_name') }}" autocomplete="family-name">
                        <div class="wz-field-error">Soyad gerekli.</div>
                    </div>
                </div>

                <div class="wz-grid2">
                    <div class="wz-field">
                        <label>Email <span class="req">*</span></label>
                        <input type="email" name="email" data-required data-email value="{{ old('email') }}" autocomplete="email">
                        <div class="wz-field-error">Geçerli email girin.</div>
                    </div>
                    <div class="wz-field">
                        <label>Telefon <span class="req">*</span></label>
                        <input type="tel" name="phone" data-required value="{{ old('phone') }}" placeholder="+90 532 ..." autocomplete="tel">
                        <div class="wz-field-error">Telefon gerekli.</div>
                    </div>
                </div>

                <div class="wz-grid2">
                    <div class="wz-field">
                        <label>Şehir</label>
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="İstanbul" autocomplete="address-level2">
                    </div>
                    <div class="wz-field">
                        <label>Ülke</label>
                        <input type="text" name="country" value="{{ old('country', 'TR') }}" autocomplete="country">
                    </div>
                </div>
            </div>

            {{-- STEP 2: Plan --}}
            <div class="wz-step" data-step="2">
                <h2 class="wz-step-title">🎯 Nasıl Kazanmak İstiyorsun?</h2>
                <p class="wz-step-sub">İki farklı model var — hangisi sana daha uygun?</p>

                <div class="wz-plan-cards">
                    <label class="wz-plan-card active" data-plan="lead_generation">
                        <input type="radio" name="preferred_plan" value="lead_generation" checked>
                        <div class="icon">🤝</div>
                        <div class="name">Lead Generation</div>
                        <div class="desc">Adayları biz ararız, satışı biz kapatırız.<br><strong style="color:var(--primary);">€200-400/kayıt</strong></div>
                    </label>
                    <label class="wz-plan-card" data-plan="freelance">
                        <input type="radio" name="preferred_plan" value="freelance">
                        <div class="icon">🎯</div>
                        <div class="name">Freelance</div>
                        <div class="desc">Ön görüşmeyi sen yaparsın, vize/okul biz.<br><strong style="color:var(--primary);">€500-750/kayıt</strong></div>
                    </label>
                    <label class="wz-plan-card" data-plan="unsure">
                        <input type="radio" name="preferred_plan" value="unsure">
                        <div class="icon">💡</div>
                        <div class="name">Kararsızım</div>
                        <div class="desc">Temsilcimiz sana en uygun olanı önerir.<br><strong style="color:var(--primary);">Görüşelim</strong></div>
                    </label>
                </div>

                <div class="wz-field" style="margin-top:24px;">
                    <label>Aylık kaç aday yönlendirmeyi hedefliyorsun? <span style="color:var(--muted); font-weight:normal;">(opsiyonel)</span></label>
                    <input type="number" name="expected_monthly_volume" value="{{ old('expected_monthly_volume') }}" min="0" max="500" placeholder="Örn: 5">
                    <div class="hint">Tahmin — taahhüt değil. Kademeni planlamak için.</div>
                </div>
            </div>

            {{-- STEP 3: Profil --}}
            <div class="wz-step" data-step="3">
                <h2 class="wz-step-title">🏢 Çalışma Şeklin</h2>
                <p class="wz-step-sub">Bireysel mi, freelance mi, şirket olarak mı başvuruyorsun?</p>

                <div class="wz-field">
                    <label>Başvuru şeklim</label>
                    <div class="wz-radio-group">
                        <label class="wz-radio-card active" data-biztype="individual">
                            <input type="radio" name="business_type" value="individual" checked>
                            👤 Bireysel
                        </label>
                        <label class="wz-radio-card" data-biztype="freelance">
                            <input type="radio" name="business_type" value="freelance">
                            💼 Freelance / Serbest
                        </label>
                        <label class="wz-radio-card" data-biztype="company">
                            <input type="radio" name="business_type" value="company">
                            🏢 Şirket / Kurum
                        </label>
                    </div>
                </div>

                <div id="wz-company-fields" style="display:none;">
                    <div class="wz-grid2">
                        <div class="wz-field">
                            <label>Firma Adı</label>
                            <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="ABC Danışmanlık">
                        </div>
                        <div class="wz-field">
                            <label>Vergi No</label>
                            <input type="text" name="tax_number" value="{{ old('tax_number') }}">
                        </div>
                    </div>
                </div>

                <div class="wz-field">
                    <label class="wz-check-box">
                        <input type="checkbox" name="education_experience" value="1" {{ old('education_experience') ? 'checked' : '' }}>
                        <span>Eğitim danışmanlığı / yurt dışı eğitim sektöründe deneyimim var</span>
                    </label>
                </div>

                <div class="wz-field">
                    <label>Deneyim detayı <span style="color:var(--muted); font-weight:normal;">(opsiyonel)</span></label>
                    <textarea name="experience_details" placeholder="Önceki çalıştığın firma, süre, uzmanlık alanı...">{{ old('experience_details') }}</textarea>
                </div>
            </div>

            {{-- STEP 4: Kaynak --}}
            <div class="wz-step" data-step="4">
                <h2 class="wz-step-title">📍 Sizi Tanıyalım</h2>
                <p class="wz-step-sub">Bizi nereden buldun ve neden bizimle çalışmak istiyorsun?</p>

                <div class="wz-field">
                    <label>Bizi nereden duydun?</label>
                    <select name="heard_from">
                        <option value="">— Seçiniz —</option>
                        <option value="organic" {{ old('heard_from') === 'organic' ? 'selected' : '' }}>Web arama (organik)</option>
                        <option value="google" {{ old('heard_from') === 'google' ? 'selected' : '' }}>Google reklamı</option>
                        <option value="social_media" {{ old('heard_from') === 'social_media' ? 'selected' : '' }}>Sosyal medya</option>
                        <option value="referral" {{ old('heard_from') === 'referral' ? 'selected' : '' }}>Arkadaş tavsiyesi</option>
                        <option value="whatsapp" {{ old('heard_from') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="other" {{ old('heard_from') === 'other' ? 'selected' : '' }}>Diğer</option>
                    </select>
                </div>

                <div class="wz-field">
                    <label>Sizi yönlendiren kişinin email'i <span style="color:var(--muted); font-weight:normal;">(varsa)</span></label>
                    <input type="email" name="referrer_email" value="{{ old('referrer_email') }}" placeholder="arkadas@email.com">
                    <div class="hint">💡 Yönlendiren kişi varsa ona referral bonusu tanımlanır.</div>
                </div>

                <div class="wz-field">
                    <label>Neden bizimle çalışmak istiyorsun? <span style="color:var(--muted); font-weight:normal;">(opsiyonel)</span></label>
                    <textarea name="motivation" placeholder="Kısa bir cümle yeterli — seni anlamamız için...">{{ old('motivation') }}</textarea>
                </div>
            </div>

            {{-- STEP 5: Onay --}}
            <div class="wz-step" data-step="5">
                <h2 class="wz-step-title">✅ Son Kontrol</h2>
                <p class="wz-step-sub">Bilgilerini gözden geçir, onay ver ve gönder.</p>

                <div class="wz-summary" id="wz-summary">
                    {{-- JS tarafından doldurulur --}}
                </div>

                <div class="wz-note">
                    📌 <strong>Sonraki adım:</strong> Form iletilir → 48 saat içinde ekibimiz arar
                    → onay sonrası panele erişim + 100€ hoş geldin bonusu hesabına tanımlanır.
                </div>

                <div class="wz-field">
                    <label class="wz-check-box">
                        <input type="checkbox" name="consent" value="1" required>
                        <span>
                            <a href="/legal/privacy" target="_blank">Gizlilik Politikası</a> ve KVKK aydınlatma metnini okudum, onaylıyorum.
                            İletişim bilgilerimle <strong>{{ $brand }}</strong>'nin başvuru değerlendirme sürecinde benimle iletişime geçmesine izin veriyorum.
                        </span>
                    </label>
                    <div class="wz-field-error" id="consent-error">Devam etmek için onay gerekli.</div>
                </div>
            </div>

            {{-- Navigation --}}
            <div class="wz-nav">
                <button type="button" class="wz-btn prev" id="wz-prev" style="display:none;">← Geri</button>
                <button type="button" class="wz-btn next" id="wz-next">Devam Et →</button>
                <button type="submit" class="wz-btn submit" id="wz-submit" style="display:none;"
                        data-track="cta_clicked"
                        data-ph-cta-name="dealer_wizard_submit"
                        data-ph-location="dealer_application_wizard">
                    🚀 Başvuruyu Gönder
                </button>
            </div>
        </form>
    </div>

    <p style="text-align:center; font-size:12px; color:var(--muted); margin-top:20px;">
        Destek: <a href="mailto:info@mentorde.com">info@mentorde.com</a> · <a href="https://wa.me/4915203253691">WhatsApp</a>
    </p>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    let currentStep = 1;
    const totalSteps = 5;
    const form = document.getElementById('wz-form');
    const nextBtn = document.getElementById('wz-next');
    const prevBtn = document.getElementById('wz-prev');
    const submitBtn = document.getElementById('wz-submit');
    const percentFill = document.getElementById('wz-percent');
    const summary = document.getElementById('wz-summary');

    function showStep(n) {
        currentStep = Math.max(1, Math.min(totalSteps, n));

        // Steps
        document.querySelectorAll('.wz-step').forEach(s => {
            s.classList.toggle('active', parseInt(s.dataset.step) === currentStep);
        });

        // Progress dots
        document.querySelectorAll('.wz-progress-step').forEach(d => {
            const stepNum = parseInt(d.dataset.step);
            d.classList.remove('active', 'done');
            if (stepNum < currentStep) d.classList.add('done');
            else if (stepNum === currentStep) d.classList.add('active');
        });

        // Progress labels
        document.querySelectorAll('.wz-progress-labels span').forEach(s => {
            s.classList.toggle('active', parseInt(s.dataset.step) === currentStep);
        });

        // Percent fill
        percentFill.style.width = (currentStep / totalSteps * 100) + '%';

        // Buttons
        prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
        submitBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';

        // On step 5, fill summary
        if (currentStep === totalSteps) {
            fillSummary();
        }

        // Scroll top
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // PostHog event
        if (window.posthog) {
            window.posthog.capture('dealer_wizard_step_viewed', { step: currentStep });
        }
    }

    function validateStep(n) {
        let isValid = true;
        const step = document.querySelector(`.wz-step[data-step="${n}"]`);
        if (!step) return true;

        step.querySelectorAll('[data-required]').forEach(input => {
            const field = input.closest('.wz-field');
            const val = (input.value || '').trim();
            const isEmpty = !val;
            const isBadEmail = input.hasAttribute('data-email') && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);

            if (isEmpty || isBadEmail) {
                field.classList.add('error');
                isValid = false;
            } else {
                field.classList.remove('error');
            }
        });

        return isValid;
    }

    function fillSummary() {
        const fd = new FormData(form);
        const rows = [
            ['👤 Ad Soyad', (fd.get('first_name') || '') + ' ' + (fd.get('last_name') || '')],
            ['📧 Email', fd.get('email')],
            ['📞 Telefon', fd.get('phone')],
            ['📍 Şehir', fd.get('city') || '—'],
            ['🎯 Plan', {
                'lead_generation': '🤝 Lead Generation (€200-400/kayıt)',
                'freelance': '🎯 Freelance (€500-750/kayıt)',
                'unsure': '💡 Kararsız — Temsilci önerir',
            }[fd.get('preferred_plan')] || '—'],
            ['📊 Aylık Hedef', fd.get('expected_monthly_volume') ? fd.get('expected_monthly_volume') + ' aday' : '—'],
            ['🏢 Tip', {
                'individual': 'Bireysel',
                'freelance': 'Freelance',
                'company': 'Şirket' + (fd.get('company_name') ? ' (' + fd.get('company_name') + ')' : ''),
            }[fd.get('business_type')] || '—'],
            ['✨ Deneyim', fd.get('education_experience') ? '✅ Eğitim sektöründe deneyim' : '—'],
        ];
        summary.innerHTML = rows.map(r => `
            <div class="wz-summary-row">
                <span class="lbl">${r[0]}</span>
                <span class="val">${r[1] || '—'}</span>
            </div>
        `).join('');
    }

    // Next button
    nextBtn.addEventListener('click', () => {
        if (!validateStep(currentStep)) return;
        showStep(currentStep + 1);
    });

    // Prev button
    prevBtn.addEventListener('click', () => showStep(currentStep - 1));

    // Plan cards
    document.querySelectorAll('.wz-plan-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.tagName === 'INPUT') return;
            document.querySelectorAll('.wz-plan-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const input = card.querySelector('input');
            if (input) input.checked = true;
        });
    });

    // Biz type radio cards
    document.querySelectorAll('.wz-radio-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.tagName === 'INPUT') return;
            document.querySelectorAll('.wz-radio-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const input = card.querySelector('input');
            if (input) input.checked = true;

            // Toggle company fields
            const companyFields = document.getElementById('wz-company-fields');
            if (companyFields) {
                companyFields.style.display = (card.dataset.biztype === 'company') ? 'block' : 'none';
            }
        });
    });

    // Form submit validation
    form.addEventListener('submit', (e) => {
        const consent = form.querySelector('[name=consent]');
        if (!consent.checked) {
            e.preventDefault();
            document.getElementById('consent-error').style.display = 'block';
            return false;
        }
    });

    // Enter key advances (but not in textarea)
    form.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            if (currentStep < totalSteps) {
                nextBtn.click();
            }
        }
    });

    showStep(1);
})();
</script>

<x-analytics.posthog-snippet :portal="'public'" />
<x-analytics.consent-banner />

</body>
</html>

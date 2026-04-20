<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'MentorDE') }} Başvuru</title>
    <style>
        :root {
            --bg:      #eef3fb;
            --panel:   #ffffff;
            --line:    #d8e2f0;
            --line-s:  #c6d5ea;
            --ink:     #11243d;
            --muted:   #5f7392;
            --primary: #1f66d1;
            --primary2:#1149a8;
            --navy:    #132f59;
            --shadow:  0 18px 48px rgba(15,30,60,.13);
            --danger-bg:#fff0f0; --danger-line:#efb0b0; --danger-text:#a32323;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 12%, #dce9ff 0, transparent 36%),
                radial-gradient(circle at 92% 18%, #e6f2ff 0, transparent 32%),
                linear-gradient(160deg, #ecf2fb 0%, #f7faff 100%);
            padding: 24px;
            display: grid;
            place-items: center;
        }
        .shell {
            width: 100%;
            max-width: 1080px;
            display: grid;
            grid-template-columns: .88fr 1.12fr;
            gap: 18px;
            align-items: stretch;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }
        /* ── SOL: MARKA PANELI ───────────────────────────── */
        .brand-panel {
            padding: 32px 28px;
            background:
                linear-gradient(180deg, rgba(19,47,89,.98), rgba(14,32,64,.97)),
                #0e2040;
            color: #fff;
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255,255,255,.055);
            pointer-events: none;
        }
        .brand-panel::before { width: 280px; height: 280px; right: -80px; top: -100px; }
        .brand-panel::after  { width: 200px; height: 200px; left: -70px; bottom: -80px; }

        .brand-logo { margin-bottom: 20px; position: relative; z-index: 1; }
        .brand-logo img { height: 44px; width: auto; display: block; }
        .brand-logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.5px;
        }
        .brand-logo-text span { color: #f59e0b; }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(255,255,255,.07);
            color: #cde0ff;
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 18px;
            width: fit-content;
            position: relative; z-index: 1;
        }
        .brand-panel h1 {
            margin: 0 0 12px;
            font-size: 26px;
            line-height: 1.2;
            letter-spacing: -.4px;
            position: relative; z-index: 1;
        }
        .brand-panel p {
            margin: 0 0 24px;
            color: #d2e4fa;
            line-height: 1.5;
            font-size: 14px;
            position: relative; z-index: 1;
        }
        .steps {
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative; z-index: 1;
            margin-top: auto;
        }
        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border: 1px solid rgba(255,255,255,.11);
            background: rgba(255,255,255,.05);
            border-radius: 12px;
            padding: 12px;
        }
        .step-num {
            flex-shrink: 0;
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #c8dbfb;
        }
        .step-txt .t { font-size: 13px; font-weight: 600; color: #e8f2ff; margin-bottom: 2px; }
        .step-txt .s { font-size: 12px; color: #9db8da; }

        /* ── SAĞ: FORM PANELI ───────────────────────────── */
        .form-panel {
            padding: 28px 28px;
            overflow-y: auto;
            max-height: calc(100vh - 48px);
        }
        .form-head {
            margin-bottom: 20px;
        }
        .form-head h2 {
            margin: 0 0 4px;
            font-size: 26px;
            letter-spacing: -.4px;
        }
        .form-head .sub {
            color: var(--muted);
            font-size: 13px;
        }
        .error-box {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 13px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            color: var(--danger-text);
        }
        .section-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .07em;
            font-weight: 700;
            color: #8aa0be;
            margin: 16px 0 8px;
        }
        .section-label:first-of-type { margin-top: 0; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .grid1 { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .field { display: flex; flex-direction: column; gap: 4px; }
        .field label {
            font-size: 12px;
            font-weight: 600;
            color: #324a6a;
        }
        .field label .req { color: #c0392b; margin-left: 2px; }
        input, select {
            width: 100%;
            border: 1px solid var(--line-s);
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 14px;
            background: #fbfdff;
            color: var(--ink);
            transition: border-color .15s, box-shadow .15s, background .15s;
            font-family: inherit;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #7faaf2;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(31,102,209,.10);
        }
        .phone-row { display: flex; gap: 8px; }
        .phone-row select { flex: 0 0 148px; }
        .phone-row input { flex: 1; }
        .divider {
            border: none;
            border-top: 1px dashed var(--line);
            margin: 16px 0 4px;
        }
        .check-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 14px 0 18px;
            font-size: 13px;
            color: var(--muted);
        }
        .check-row input[type="checkbox"] { width: auto; flex-shrink: 0; }
        .link-btn {
            border: 0;
            background: transparent;
            color: var(--primary);
            padding: 0;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: underline;
            font-family: inherit;
        }
        .submit-btn {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 13px 14px;
            font-size: 15px;
            font-weight: 700;
            background: linear-gradient(180deg, var(--primary), var(--primary2));
            color: #fff;
            cursor: pointer;
            transition: filter .15s, box-shadow .15s, transform .05s;
            box-shadow: 0 8px 20px rgba(31,102,209,.22);
            font-family: inherit;
        }
        .submit-btn:hover  { filter: brightness(1.04); }
        .submit-btn:active { transform: translateY(1px); }
        .guide-box {
            margin-top: 16px;
            border-top: 1px dashed var(--line);
            padding-top: 14px;
        }
        .guide-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #8aa0be;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .guide-box ol {
            margin: 0;
            padding-left: 18px;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.7;
        }
        /* MODAL */
        .modal-bg {
            position: fixed; inset: 0;
            background: rgba(14,32,64,.55);
            display: none; align-items: center; justify-content: center;
            padding: 20px; z-index: 60;
        }
        .modal-bg.open { display: flex; }
        .modal-box {
            width: min(700px, 100%);
            max-height: 86vh;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,.18);
            display: flex; flex-direction: column;
        }
        .modal-head {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid var(--line);
            background: #f6faff;
        }
        .modal-head h3 { margin: 0; font-size: 15px; }
        .modal-close {
            border: 1px solid var(--line-s);
            background: #fff;
            color: var(--ink);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }
        .modal-body {
            padding: 18px; overflow: auto;
            font-size: 13px; line-height: 1.6;
            white-space: pre-wrap;
        }
        @media (max-width: 820px) {
            body { padding: 14px; }
            .shell { grid-template-columns: 1fr; max-width: 540px; }
            .brand-panel { display: none; }
            .form-panel { max-height: none; }
            .phone-row { flex-direction: column; }
            .phone-row select { flex-basis: auto; }
        }
    </style>
</head>
<body>
@if(!empty($partner))
<div style="position:fixed;top:0;left:0;right:0;background:linear-gradient(90deg, #1e40af, #3b82f6);color:#fff;padding:10px 20px;text-align:center;font-size:13px;font-weight:600;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.15);">
    🤝 <strong>{{ $partner->name }}</strong> ile işbirliği başvurusu
    <span style="opacity:.85;margin-left:10px;font-weight:400;">· Bayi Kodu: {{ $partner->code }}</span>
</div>
<div style="height:42px;"></div>
@endif
<div class="shell">

    {{-- ── SOL MARKA PANELİ ─────────────────────────── --}}
    @php
        $_hasBrandTable = \Illuminate\Support\Facades\Schema::hasTable('marketing_admin_settings');
        $brandName   = $_hasBrandTable ? \App\Models\MarketingAdminSetting::getValue('brand_name',        config('brand.name',   'MentorDE')) : config('brand.name',   'MentorDE');
        $brandAccent = $_hasBrandTable ? \App\Models\MarketingAdminSetting::getValue('brand_accent',      config('brand.accent', 'DE'))       : config('brand.accent', 'DE');
        $logoUrl     = $_hasBrandTable ? \App\Models\MarketingAdminSetting::getValue('brand_logo_url',    config('brand.logo_url',   ''))     : config('brand.logo_url', '');
        $logoHeight  = (int) ($_hasBrandTable ? \App\Models\MarketingAdminSetting::getValue('brand_logo_height', config('brand.logo_height', 40)) : config('brand.logo_height', 40));
        $logoPath    = config('brand.logo_path', '');
        $resolvedLogoSrc = $logoUrl !== '' ? $logoUrl
            : ($logoPath !== '' ? asset('storage/' . $logoPath) : '');
    @endphp
    <section class="brand-panel" aria-label="Marka bilgisi">
        <div class="brand-logo">
            @if($resolvedLogoSrc !== '')
                <img src="{{ $resolvedLogoSrc }}" alt="{{ $brandName }}"
                     style="height:{{ $logoHeight }}px;width:auto;filter:brightness(0) invert(1);">
            @else
                <div class="brand-logo-text">
                    @if($brandAccent !== '')
                        {{ str_replace($brandAccent, '', $brandName) }}<span>{{ $brandAccent }}</span>
                    @else
                        {{ $brandName }}
                    @endif
                </div>
            @endif
        </div>

        <div class="brand-badge">Öğrenci Danışmanlık Platformu</div>

        <h1>Almanya'da Kariyer ve Eğitim Yolculuğun Başlıyor</h1>
        <p>Uzman danışmanlarımız seni üniversite başvurusundan vizeye, dil kursundan yerleşime kadar her adımda yönlendirir.</p>

        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-txt">
                    <div class="t">Başvuruyu Tamamla</div>
                    <div class="s">Formu doldur, danışmanına ulaşalım</div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-txt">
                    <div class="t">Üniversite Seçimi</div>
                    <div class="s">Profiline uygun okul ve bölüm planla</div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-txt">
                    <div class="t">Başvuru & Vize</div>
                    <div class="s">Belgeler, başvuru ve konsolosluk süreci</div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-txt">
                    <div class="t">Yerleşim</div>
                    <div class="s">Konaklama, kayıt ve ilk günler</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── SAĞ FORM PANELİ ──────────────────────────── --}}
    <section class="panel form-panel" aria-label="Başvuru formu">
        @php
            $oldPhone = (string) old('phone', '');
            $dialOptions = [
                ['dial' => '+90', 'label' => 'TR'],
                ['dial' => '+49', 'label' => 'DE'],
                ['dial' => '+43', 'label' => 'AT'],
                ['dial' => '+41', 'label' => 'CH'],
                ['dial' => '+31', 'label' => 'NL'],
                ['dial' => '+32', 'label' => 'BE'],
                ['dial' => '+33', 'label' => 'FR'],
                ['dial' => '+39', 'label' => 'IT'],
                ['dial' => '+34', 'label' => 'ES'],
                ['dial' => '+44', 'label' => 'UK'],
                ['dial' => '+1',  'label' => 'US'],
                ['dial' => '+971','label' => 'AE'],
            ];
            $selectedDial = '+90';
            foreach ($dialOptions as $dialOpt) {
                if ($oldPhone !== '' && str_starts_with($oldPhone, $dialOpt['dial'])) {
                    $selectedDial = $dialOpt['dial'];
                    break;
                }
            }
            $phoneLocal = trim(preg_replace('/^\+' . preg_quote(ltrim($selectedDial, '+'), '/') . '\s*/', '', $oldPhone));

            $termList = [];
            $termCursor = now()->startOfDay();
            $seenTerms = [];
            while (count($termList) < 4) {
                $month = (int) $termCursor->month;
                $year  = (int) $termCursor->year;
                $nextTerm = $month < 4
                    ? ['year' => $year,   'label' => $year . ' Summer']
                    : ($month < 10
                        ? ['year' => $year,   'label' => $year . ' Winter']
                        : ['year' => $year+1, 'label' => ($year+1) . ' Summer']);
                if (!isset($seenTerms[$nextTerm['label']])) {
                    $seenTerms[$nextTerm['label']] = true;
                    $termList[] = $nextTerm['label'];
                }
                $termCursor = $termCursor->addMonths(6);
            }
        @endphp

        <div class="form-head">
            <h2>Başvuru Formu</h2>
            <div class="sub">Bilgilerini gir, seni en kısa sürede arayalım.</div>
        </div>

        {{-- Hata — controller'dan HTML link içerebilir (strip_tags allowlist) --}}
        @if($errors->any())
            <div class="error-box">
                @foreach($errors->all() as $error)
                    <div>{!! strip_tags($error, '<a><br><strong><b>') !!}</div>
                @endforeach
            </div>
        @endif

        {{-- Kampanya --}}
        @if(($activeCampaigns ?? collect())->isNotEmpty())
        <div style="border:1px solid #dae8fe;background:#f3f8ff;border-radius:10px;padding:12px;margin-bottom:16px;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.07em;font-weight:700;color:#6d90bb;margin-bottom:8px;">Aktif Kampanya</div>
            @foreach($activeCampaigns as $camp)
                <div style="font-size:13px;font-weight:600;color:#1b3f77;">{{ $camp->name }}</div>
                @if($camp->description)
                    <div style="font-size:12px;color:#5f7392;margin-top:2px;">{{ $camp->description }}</div>
                @endif
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('apply.store') }}" data-apply-form="1">
            @csrf

            {{-- Ad / Soyad --}}
            <div class="section-label">Kişisel Bilgiler</div>
            <div class="grid2" style="margin-bottom:10px;">
                <div class="field">
                    <label>Ad <span class="req">*</span></label>
                    <input name="first_name" placeholder="Adınız" value="{{ old('first_name') }}" required>
                </div>
                <div class="field">
                    <label>Soyad <span class="req">*</span></label>
                    <input name="last_name" placeholder="Soyadınız" value="{{ old('last_name') }}" required>
                </div>
            </div>

            {{-- E-posta / Telefon (aynı satır) --}}
            <div class="grid2" style="margin-bottom:10px;">
                <div class="field">
                    <label>E-posta <span class="req">*</span></label>
                    <input name="email" type="email" placeholder="ornek@mail.com" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label>Telefon <span class="req">*</span></label>
                    <div style="display:flex;gap:6px;">
                        <select id="applyPhoneCountryCode" aria-label="Ülke kodu" style="flex:0 0 84px;padding-left:8px;padding-right:4px;">
                            @foreach($dialOptions as $dialOpt)
                                <option value="{{ $dialOpt['dial'] }}" @selected($selectedDial === $dialOpt['dial'])>
                                    {{ $dialOpt['dial'] }} {{ $dialOpt['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <input id="applyPhoneLocal" placeholder="5XX XXX XXXX" value="{{ $phoneLocal }}" style="flex:1;min-width:0;">
                        <input type="hidden" id="applyPhoneCombined" name="phone" value="{{ old('phone') }}">
                    </div>
                </div>
            </div>

            {{-- Cinsiyet / İletişim dili --}}
            <div class="grid2" style="margin-bottom:10px;">
                <div class="field">
                    <label>Cinsiyet <span class="req">*</span></label>
                    <select name="gender" required>
                        <option value="">Seçiniz</option>
                        <option value="male"          @selected(old('gender')==='male')>Erkek</option>
                        <option value="female"        @selected(old('gender')==='female')>Kadın</option>
                        <option value="not_specified" @selected(old('gender')==='not_specified')>Belirtmek istemiyorum</option>
                    </select>
                </div>
                <div class="field">
                    <label>İletişim dili <span class="req">*</span></label>
                    <select name="communication_language" required>
                        <option value="">Seçiniz</option>
                        <option value="tr" @selected(old('communication_language')==='tr')>Türkçe</option>
                        <option value="de" @selected(old('communication_language')==='de')>Almanca</option>
                        <option value="en" @selected(old('communication_language')==='en')>İngilizce</option>
                    </select>
                </div>
            </div>

            <hr class="divider">
            {{-- Başvuru Detayı --}}
            <div class="section-label">Başvuru Detayı</div>

            {{-- Başvuru ülkesi / Tipi --}}
            <div class="grid2" style="margin-bottom:10px;">
                <div class="field">
                    <label>Başvuru ülkesi <span class="req">*</span></label>
                    <select name="application_country" required>
                        <option value="">Seçiniz</option>
                        @php $oldCountry = old('application_country'); @endphp
                        @foreach(($applicationCountries ?? []) as $country)
                            <option value="{{ $country['label'] }}" @selected($oldCountry === $country['label'])>
                                {{ $country['label'] }} ({{ $country['code'] }})
                            </option>
                        @endforeach
                        <option value="Diğer" @selected($oldCountry === 'Diğer')>Diğer</option>
                    </select>
                </div>
                <div class="field">
                    <label>Başvuru tipi <span class="req">*</span></label>
                    <select name="application_type" required>
                        <option value="">Seçiniz</option>
                        @foreach(($studentTypes ?? []) as $type)
                            <option value="{{ $type->code }}" @selected(old('application_type')===$type->code)>
                                {{ $type->name_tr }} ({{ $type->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Hedef dönem / Lead source --}}
            <div class="grid2" style="margin-bottom:10px;">
                <div class="field">
                    <label>Hedef dönem</label>
                    <select name="target_term">
                        <option value="">Seçiniz</option>
                        @foreach($termList as $term)
                            <option value="{{ $term }}" @selected(old('target_term') === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Bizi nereden buldunuz? <span class="req">*</span></label>
                    <select id="applyLeadSource" name="lead_source" required>
                        @php $oldLeadSource = old('lead_source', 'organic'); @endphp
                        @foreach(($leadSourceOptions ?? []) as $opt)
                            <option value="{{ $opt['code'] }}" @selected($oldLeadSource === $opt['code'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Bayi kodu (tam satır) --}}
            <div class="field" style="margin-bottom:4px;">
                <label>Bayi / Referans kodu <span style="font-weight:400;color:#8aa0be;">(opsiyonel)</span></label>
                <input id="applyDealerCode" name="dealer_code" list="applyDealerSuggestions"
                       placeholder="Varsa bayi ya da referans kodunu girin"
                       value="{{ old('dealer_code', $prefill['dealer_code'] ?? '') }}"
                       @if(!empty($prefill['dealer_code'])) readonly style="background:#f3f4f6;cursor:not-allowed;" @endif>
                @if(!empty($prefill['dealer_code']))
                <div style="font-size:11px;color:#16a34a;margin-top:3px;font-weight:600;">✓ Partner başvurusu — kod otomatik dolduruldu</div>
                @endif
            </div>
            @if(!empty($prefill['lead_source']))
            <input type="hidden" name="lead_source" value="{{ $prefill['lead_source'] }}">
            @endif
            <datalist id="applyDealerSuggestions">
                @foreach(($dealerCodes ?? []) as $dealerCode)
                    <option value="{{ $dealerCode }}"></option>
                @endforeach
            </datalist>

            {{-- Gizli alanlar --}}
            <input type="hidden" id="applyCampaignCode"     name="campaign_code"     value="{{ old('campaign_code') }}">
            <input type="hidden" id="applyTrackingLinkCode" name="tracking_link_code" value="{{ old('tracking_link_code') }}">
            <input type="hidden" id="applyUtmSource"        name="utm_source"        value="{{ old('utm_source') }}">
            <input type="hidden" id="applyUtmMedium"        name="utm_medium"        value="{{ old('utm_medium') }}">
            <input type="hidden" id="applyUtmCampaign"      name="utm_campaign"      value="{{ old('utm_campaign') }}">
            <input type="hidden" id="applyUtmTerm"          name="utm_term"          value="{{ old('utm_term') }}">
            <input type="hidden" id="applyUtmContent"       name="utm_content"       value="{{ old('utm_content') }}">
            <input type="hidden" id="applyClickId"          name="click_id"          value="{{ old('click_id') }}">
            <input type="hidden" id="applyLandingUrl"       name="landing_url"       value="{{ old('landing_url') }}">
            <input type="hidden" id="applyReferrerUrl"      name="referrer_url"      value="{{ old('referrer_url') }}">

            <hr class="divider">
            <label class="check-row">
                <input type="checkbox" name="kvkk_consent" value="1" required>
                KVKK aydınlatma metnini okudum ve kabul ediyorum.
                <button type="button" class="link-btn" id="kvkkOpenBtn">KVKK Metnini Oku</button>
            </label>

            <button type="submit" class="submit-btn" id="applySubmitBtn">Başvuruyu Gönder →</button>
        </form>

        <div class="guide-box">
            <div class="guide-title">Kullanım Kılavuzu</div>
            <ol>
                <li>Zorunlu alanları doldurup başvuru tipini seçin.</li>
                <li>"Bizi nereden buldunuz?" seçimi raporlama için kaydedilir.</li>
                <li>Formu gönderdikten sonra takip kodu ile durum sayfasını açabilirsiniz.</li>
            </ol>
        </div>
    </section>
</div>

{{-- KVKK Modal --}}
<div id="kvkkModalBackdrop" class="modal-bg" aria-hidden="true">
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="kvkkModalTitle">
        <div class="modal-head">
            <h3 id="kvkkModalTitle">KVKK Aydınlatma Metni</h3>
            <button type="button" class="modal-close" id="kvkkCloseBtn" aria-label="Kapat">✕ Kapat</button>
        </div>
        <div class="modal-body">{!! nl2br(e($kvkkText ?? 'KVKK metni tanımlı değil.')) !!}</div>
    </div>
</div>

<script defer src="{{ Vite::asset('resources/js/apply-form.js') }}" defer></script>
@include('partials.cookie-consent')
</body>
</html>

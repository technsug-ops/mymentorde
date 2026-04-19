@extends('dealer.layouts.app')

@section('title', 'Öğrenci Yönlendir')
@section('page_title', 'Öğrenci Yönlendir')
@section('page_subtitle', 'Kanal 1 — Doğrudan form ile aday öğrenci yönlendirme')

@push('head')
<style>
.lf-hero {
    background: linear-gradient(to right, #0891b2, #16a34a);
    border-radius: 14px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
    color: #fff;
    flex-wrap: wrap;
}
.lf-hero-title  { font-size: 18px; font-weight: 800; margin: 0 0 4px; }
.lf-hero-sub    { font-size: 13px; opacity: .85; }
.lf-hero-code   { background: rgba(255,255,255,.18); border-radius: 10px; padding: 10px 18px; text-align: center; }
.lf-hero-code strong { display:block; font-size: 22px; font-weight: 900; letter-spacing: .04em; }
.lf-hero-code span   { font-size: 11px; opacity: .8; }

.lf-card { background: var(--surface,#fff); border: 1px solid var(--border,#e2e8f0); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.lf-card-head { padding: 16px 20px; border-bottom: 1px solid var(--border,#e2e8f0); display:flex; align-items:center; gap:10px; }
.lf-card-head .lf-icon { width:32px;height:32px;border-radius:8px;background:var(--accent-soft,#16a34a18);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0; }
.lf-card-head h3 { margin:0; font-size:14px; font-weight:700; }
.lf-card-head .lf-sub { font-size:12px; color:var(--muted,#64748b); margin:0; }
.lf-card-body { padding: 20px; }

.lf-field { margin-bottom: 16px; }
.lf-field label { display:block; font-size:12px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
.lf-field input,
.lf-field select,
.lf-field textarea {
    width: 100%; box-sizing: border-box;
    border: 1.5px solid var(--border,#e2e8f0);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    color: var(--text,#0f172a);
    background: var(--surface,#fff);
    transition: border-color .15s, box-shadow .15s;
}
.lf-field input:focus,
.lf-field select:focus,
.lf-field textarea:focus {
    outline: none;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22,163,74,.12);
}
.lf-field .lf-err { font-size: 12px; color: var(--c-danger,#dc2626); margin-top: 4px; }
.lf-field .lf-hint { font-size: 11px; color: var(--muted,#64748b); margin-top: 4px; }

.lf-divider { border: none; border-top: 1px solid var(--border,#e2e8f0); margin: 4px 0 16px; }

.lf-kvkk { display:flex; gap:10px; align-items:flex-start; padding:14px 16px; background:rgba(22,163,74,.06); border:1px solid rgba(22,163,74,.2); border-radius:10px; margin-bottom:20px; cursor:pointer; }
.lf-kvkk input[type=checkbox] { width:16px;height:16px;flex-shrink:0;margin-top:1px;accent-color:#16a34a; }
.lf-kvkk span { font-size:13px; color:var(--text,#0f172a); }

.lf-submit-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.lf-submit-row .btn.btn-primary { padding: 11px 28px; font-size:14px; border-radius: 10px; background:#16a34a; color:#fff; border:none; }
.lf-submit-row .btn.btn-primary:hover { background:#15803d; }

.lf-guide { background:var(--bg,#f1f5f9); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:16px 20px; }
.lf-guide-title { font-size:12px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:10px; }
.lf-guide ul { margin:0; padding-left:18px; }
.lf-guide li  { font-size:13px; color:var(--muted,#64748b); margin-bottom:6px; }
</style>
@endpush

@section('content')
@if(!$dealerCode)
    <div class="panel" style="border-left:4px solid var(--c-danger,#dc2626);">
        Bu kullanıcıya dealer code atanmadığı için yönlendirme oluşturamazsın. Yönetici ile iletişime geç.
    </div>
@else

{{-- Hero --}}
<div class="lf-hero">
    <div>
        <div class="lf-hero-title">Öğrenci Yönlendir</div>
        <div class="lf-hero-sub">Formu doldur, yönlendirme otomatik sisteme kaydedilsin</div>
    </div>
    <div class="lf-hero-code">
        <strong>{{ $dealerCode }}</strong>
        <span>Dealer Code</span>
    </div>
</div>

{{-- Yönlendirme Tipi Seçimi --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;" id="referral-type-picker">
    <label style="cursor:pointer;">
        <input type="radio" name="referral_type" value="recommendation" style="display:none;" checked>
        <div class="lf-card" id="rt-recommendation" style="border:2px solid #16a34a;transition:border-color .2s,box-shadow .2s;box-shadow:0 0 0 3px rgba(22,163,74,.15);">
            <div style="padding:20px;text-align:center;">
                <div style="font-size:32px;margin-bottom:8px;">📋</div>
                <div style="font-size:15px;font-weight:700;color:var(--text,#111);margin-bottom:6px;">Tavsiye Yönlendirme</div>
                <div style="font-size:12px;color:var(--muted,#64748b);line-height:1.5;">
                    Bu kişi henüz karar vermedi.<br>
                    Yönlendirme ile potansiyel müşteri olabilir.
                </div>
                <div style="margin-top:10px;display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#fef9c3;color:#854d0e;">Potansiyel</div>
            </div>
        </div>
    </label>
    <label style="cursor:pointer;">
        <input type="radio" name="referral_type" value="confirmed_referral" style="display:none;">
        <div class="lf-card" id="rt-confirmed" style="border:2px solid transparent;transition:border-color .2s,box-shadow .2s;">
            <div style="padding:20px;text-align:center;">
                <div style="font-size:32px;margin-bottom:8px;">✅</div>
                <div style="font-size:15px;font-weight:700;color:var(--text,#111);margin-bottom:6px;">Kesin Yönlendirme</div>
                <div style="font-size:12px;color:var(--muted,#64748b);line-height:1.5;">
                    Bu kişi ile görüşüldü.<br>
                    Kayıt için hazır, kesin potansiyel müşteri.
                </div>
                <div style="margin-top:10px;display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#dcfce7;color:#166534;">Kesin Kayıt</div>
            </div>
        </div>
    </label>
</div>

<form method="POST" action="{{ route('dealer.lead-create.store') }}">
@csrf
<input type="hidden" name="referral_type" id="referral_type_hidden" value="recommendation">
<div class="grid2" style="align-items:start;">

    {{-- Kisisel Bilgiler --}}
    <div class="lf-card">
        <div class="lf-card-head">
            <div class="lf-icon">👤</div>
            <div>
                <h3>Kişisel Bilgiler</h3>
                <p class="lf-sub">Ad, soyad, iletişim</p>
            </div>
        </div>
        <div class="lf-card-body">
            <div class="grid2" style="margin-bottom:0;">
                <div class="lf-field">
                    <label>Ad *</label>
                    <input name="first_name" value="{{ old('first_name') }}" placeholder="Örn: Ahmet" required>
                    @error('first_name')<div class="lf-err">{{ $message }}</div>@enderror
                </div>
                <div class="lf-field">
                    <label>Soyad *</label>
                    <input name="last_name" value="{{ old('last_name') }}" placeholder="Örn: Yılmaz" required>
                    @error('last_name')<div class="lf-err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="lf-field">
                <label>Telefon *</label>
                <input name="phone" value="{{ old('phone') }}" placeholder="+90 5xx xxx xx xx" required>
                @error('phone')<div class="lf-err">{{ $message }}</div>@enderror
            </div>
            <div class="lf-field" style="margin-bottom:0;">
                <label>E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="ornek@mail.com">
                <div class="lf-hint">Opsiyonel — varsa girmeniz önerilir</div>
                @error('email')<div class="lf-err">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- Basvuru Bilgileri --}}
    <div class="lf-card">
        <div class="lf-card-head">
            <div class="lf-icon">🎓</div>
            <div>
                <h3>Başvuru Bilgileri</h3>
                <p class="lf-sub">Talep türü ve hedef ülke</p>
            </div>
        </div>
        <div class="lf-card-body">
            <div class="lf-field">
                <label>Talep Türü *</label>
                <select name="application_type" required>
                    @foreach(['bachelor'=>'Lisans','master'=>'Yüksek Lisans','language_course'=>'Dil Kursu','ausbildung'=>'Ausbildung','studienkolleg'=>'Studienkolleg','visa_consulting'=>'Vize Danışmanlığı','housing'=>'Konaklama','other'=>'Diğer'] as $code=>$lbl)
                        <option value="{{ $code }}" @selected(old('application_type')===$code)>{{ $lbl }}</option>
                    @endforeach
                </select>
                @error('application_type')<div class="lf-err">{{ $message }}</div>@enderror
            </div>
            <div class="lf-field">
                <label>Hedef Ülke</label>
                <select name="application_country">
                    <option value="">– Seçiniz –</option>
                    @foreach(($applicationCountries ?? []) as $country)
                        <option value="{{ $country['label'] }}" @selected(old('application_country') === $country['label'])>{{ $country['label'] }} ({{ $country['code'] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="lf-field" style="margin-bottom:0;">
                <label>Not / Detay</label>
                <textarea name="notes" rows="4" placeholder="Öğrencinin durumu, özel talepleri veya ek bilgiler...">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

</div>

{{-- KVKK --}}
<label class="lf-kvkk">
    <input type="checkbox" name="kvkk_consent" value="1" @checked(old('kvkk_consent'))>
    <span>Öğrencinin <strong>KVKK onayı</strong> alınmıştır. Kişisel verilerinin {{ config('brand.name', 'MentorDE') }} bünyesinde işlenmesine rıza gösterdiğini beyan ediyorum.</span>
</label>

{{-- Submit --}}
<div class="lf-submit-row">
    <button type="submit" class="btn btn-primary">Yönlendirmeyi Gönder →</button>
    <span class="muted" style="font-size:var(--tx-xs);">Kayıt oluştuktan sonra "Yönlendirmelerim" ekranında takip edebilirsin.</span>
</div>
</form>

{{-- Kilavuz --}}
<div class="lf-guide" style="margin-top:20px;">
    <div class="lf-guide-title">💡 Nasıl Çalışır?</div>
    <ul>
        <li>Form gönderilince sistem otomatik bir aday öğrenci kaydı oluşturur ve dealer kodunu ilişkilendirir.</li>
        <li>KVKK onayı işaretlenmeden kayıt oluşturulmaz — bu zorunludur.</li>
        <li>Kayıt oluştuktan sonra <strong>Yönlendirmelerim</strong> ekranında durumu takip edebilirsin.</li>
        <li>Referans linki ile gelen başvurular için ayrıca <strong>Referans Linklerim</strong> ekranını kullan.</li>
    </ul>
</div>

@endif

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var radios = document.querySelectorAll('#referral-type-picker input[type=radio]');
    var hidden = document.getElementById('referral_type_hidden');
    var cardRec = document.getElementById('rt-recommendation');
    var cardCon = document.getElementById('rt-confirmed');

    function updateCards(){
        var val = document.querySelector('#referral-type-picker input[type=radio]:checked')?.value || 'recommendation';
        if(hidden) hidden.value = val;
        if(cardRec){
            cardRec.style.borderColor = val === 'recommendation' ? '#16a34a' : 'transparent';
            cardRec.style.boxShadow = val === 'recommendation' ? '0 0 0 3px rgba(22,163,74,.15)' : 'none';
        }
        if(cardCon){
            cardCon.style.borderColor = val === 'confirmed_referral' ? '#16a34a' : 'transparent';
            cardCon.style.boxShadow = val === 'confirmed_referral' ? '0 0 0 3px rgba(22,163,74,.15)' : 'none';
        }
    }

    radios.forEach(function(r){ r.addEventListener('change', updateCards); });
    updateCards();
}());
</script>
@endpush
@endsection

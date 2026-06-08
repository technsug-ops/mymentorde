@extends(\App\Support\PartnerRouting::layout())

@section('title', 'Yeni Partner · ' . config('brand.name', 'MentorDE'))
@section('page_title', 'Yeni API Partner')
@section('page_subtitle', 'Kardeş site / iş ortağı için API anahtarı oluştur')

@section('content')
<style>
.apc-form-wrap { max-width: 640px; margin: 0 auto; }
.apc-form-card { background: var(--u-card,#fff); border: 1px solid var(--u-line,#e2e8f0); border-radius: 12px; padding: 22px; }
.apc-field { margin-bottom: 16px; }
.apc-field label { display: block; font-size: 12px; font-weight: 700; color: var(--u-text); margin-bottom: 6px; }
.apc-field label .req { color: #dc2626; }
.apc-field input, .apc-field textarea { width: 100%; box-sizing: border-box; padding: 9px 12px; font-size: 13px; border: 1px solid var(--u-line,#cbd5e1); border-radius: 7px; background: var(--u-card,#fff); color: var(--u-text); outline: none; font-family: inherit; }
.apc-field input:focus, .apc-field textarea:focus { border-color: #5b2e91; box-shadow: 0 0 0 3px rgba(91,46,145,.1); }
.apc-field .help { margin-top: 4px; font-size: 11.5px; color: var(--u-muted,#64748b); }
.apc-actions { display: flex; gap: 10px; margin-top: 18px; }
.apc-btn { padding: 9px 18px; background: #5b2e91; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; }
.apc-btn:hover { background: #4a2578; }
.apc-btn-ghost { padding: 9px 16px; background: transparent; color: var(--u-muted,#64748b); border: 1px solid var(--u-line,#cbd5e1); border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; }
.apc-field-err { color: #dc2626; font-size: 11.5px; margin-top: 4px; }
</style>

<div class="apc-form-wrap">

    <a href="{{ \App\Support\PartnerRouting::url('index') }}" style="display:inline-block;margin-bottom:14px;color:#5b2e91;font-size:12.5px;text-decoration:none;">← Tüm partnerlar</a>

    <div class="apc-form-card">
        <form method="POST" action="{{ \App\Support\PartnerRouting::url('store') }}">
            @csrf

            <div class="apc-field">
                <label>Partner Adı <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Örn: Bestparents Almanya">
                @error('name') <div class="apc-field-err">{{ $message }}</div> @enderror
                <div class="apc-help">İç görünüm için. Otomatik slug oluşturulur (örn. "bestparents-almanya").</div>
            </div>

            <div class="apc-field">
                <label>İletişim E-postası</label>
                <input type="email" name="contact_email" value="{{ old('contact_email') }}" maxlength="160" placeholder="partner@example.com">
                @error('contact_email') <div class="apc-field-err">{{ $message }}</div> @enderror
            </div>

            <div class="apc-field">
                <label>Web Sitesi</label>
                <input type="url" name="website" value="{{ old('website') }}" maxlength="200" placeholder="https://example.com">
                @error('website') <div class="apc-field-err">{{ $message }}</div> @enderror
            </div>

            <div class="apc-field">
                <label>Rate Limit (request/saat)</label>
                <input type="number" name="rate_limit_per_hour" value="{{ old('rate_limit_per_hour', 1000) }}" min="10" max="100000">
                @error('rate_limit_per_hour') <div class="apc-field-err">{{ $message }}</div> @enderror
                <div class="help">Bu key ile saatte kaç request atılabilir. Default 1000.</div>
            </div>

            <div class="apc-field">
                <label>İç Notlar</label>
                <textarea name="notes" rows="3" maxlength="2000" placeholder="Anlaşma detayları, kontak kişi vb.">{{ old('notes') }}</textarea>
                @error('notes') <div class="apc-field-err">{{ $message }}</div> @enderror
            </div>

            <div class="apc-actions">
                <button type="submit" class="apc-btn">🔐 Partner Oluştur + Key Üret</button>
                <a href="{{ \App\Support\PartnerRouting::url('index') }}" class="apc-btn-ghost">Vazgeç</a>
            </div>
        </form>
    </div>

    <div style="margin-top:14px;padding:12px 16px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:8px;font-size:12px;color:#78350f;">
        ⚠️ <strong>Önemli:</strong> Oluşturulan API anahtarı yalnızca <strong>bir kez</strong> gösterilir. Partner'a güvenli bir kanaldan ilet.
        Sonradan ihtiyaç olursa <em>rotate</em> ile yeni key üretilir (eski geçersiz olur).
    </div>
</div>
@endsection

@extends('marketing-admin.layouts.app')

@section('title', 'Panel Ayarları')

@section('page_subtitle', 'Panel Ayarları — dil, bildirim ve marka tercihlerini yönet')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input, .wf-field select {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.wf-field input:focus, .wf-field select:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}
</style>

<div style="display:grid;gap:12px;">

    @if(isset($tableReady) && !$tableReady)
    <div style="border:1px solid var(--u-danger,#dc2626);background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,var(--u-card,#fff));color:var(--u-danger,#dc2626);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        Ayar tablosu bulunamadı. Terminalde <code>php artisan migrate</code> çalıştır.
    </div>
    @endif

    @if(session('status'))
    <div style="border:1px solid var(--u-ok,#16a34a);background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,var(--u-card,#fff));color:var(--u-ok,#16a34a);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        {{ session('status') }}
    </div>
    @endif

    @if($errors->any())
    <div style="border:1px solid var(--u-danger,#dc2626);background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,var(--u-card,#fff));color:var(--u-danger,#dc2626);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
    @endif

    <form method="POST" action="/mktg-admin/settings" id="settings-form">
        @csrf @method('PUT')

        <div class="grid2" style="gap:12px;">

            {{-- Genel --}}
            <div class="card">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Genel</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['default_locale']['label'] ?? 'Dil' }}</label>
                        <select name="default_locale">
                            @foreach(($settings['default_locale']['options'] ?? ['tr','de','en']) as $opt)
                            <option value="{{ $opt }}" @selected(($settings['default_locale']['value'] ?? 'tr') === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wf-field">
                        <label>{{ $settings['default_timezone']['label'] ?? 'Saat Dilimi' }}</label>
                        <select name="default_timezone">
                            @foreach(($settings['default_timezone']['options'] ?? ['Europe/Berlin']) as $opt)
                            <option value="{{ $opt }}" @selected(($settings['default_timezone']['value'] ?? 'Europe/Berlin') === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wf-field">
                        <label>{{ $settings['dashboard_refresh_seconds']['label'] ?? 'Dashboard Yenileme (sn)' }}</label>
                        <input type="number" min="0" name="dashboard_refresh_seconds" value="{{ $settings['dashboard_refresh_seconds']['value'] ?? 30 }}">
                    </div>
                    <div class="wf-field">
                        <label>{{ $settings['daily_summary_hour']['label'] ?? 'Günlük Özet Saati' }}</label>
                        <input type="number" min="0" max="23" name="daily_summary_hour" value="{{ $settings['daily_summary_hour']['value'] ?? 9 }}">
                    </div>
                </div>
            </div>

            {{-- Bildirim & Marka --}}
            <div class="card">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Bildirim & Marka</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['notify_on_new_lead']['label'] ?? 'Yeni Lead Bildirimi' }}</label>
                        <select name="notify_on_new_lead">
                            <option value="1" @selected((bool)($settings['notify_on_new_lead']['value'] ?? true) === true)>aktif</option>
                            <option value="0" @selected((bool)($settings['notify_on_new_lead']['value'] ?? true) === false)>pasif</option>
                        </select>
                    </div>
                    <div class="wf-field">
                        <label>{{ $settings['notify_on_campaign_error']['label'] ?? 'Kampanya Hata Bildirimi' }}</label>
                        <select name="notify_on_campaign_error">
                            <option value="1" @selected((bool)($settings['notify_on_campaign_error']['value'] ?? true) === true)>aktif</option>
                            <option value="0" @selected((bool)($settings['notify_on_campaign_error']['value'] ?? true) === false)>pasif</option>
                        </select>
                    </div>
                    <div class="wf-field">
                        <label>{{ $settings['brand_primary']['label'] ?? 'Birincil Marka Rengi' }}</label>
                        <input name="brand_primary" value="{{ $settings['brand_primary']['value'] ?? '#0a67d8' }}">
                    </div>
                    <div class="wf-field">
                        <label>{{ $settings['brand_secondary']['label'] ?? 'İkincil Marka Rengi' }}</label>
                        <input name="brand_secondary" value="{{ $settings['brand_secondary']['value'] ?? '#10253e' }}">
                    </div>
                </div>
            </div>

        </div>

        <div class="card" style="margin-top:12px;">
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn ok">Ayarları Kaydet</button>
                <a href="/mktg-admin/settings" class="btn alt">Yenile</a>
            </div>
        </div>

    </form>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Pazarlama Ayarları</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Genel ayarlar panel davranışını belirler (dil, saat dilimi, otomatik yenileme).</li>
            <li>Bildirim ayarları kuyruk ve hata alarmlarının açık/kapalı olmasını kontrol eder.</li>
            <li>Marka renkleri sonraki frontend ekranlarında ortak tema için referans olarak kullanılır.</li>
            <li>Değişiklikten sonra "Ayarları Kaydet" ile kalıcı hale getir.</li>
        </ol>
    </details>

</div>
@endsection

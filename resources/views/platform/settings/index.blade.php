@extends('platform.layouts.app')

@section('title', 'Platform Ayarları — DGmarkt')

@push('styles')
<style>
    .pset-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--plat-border); margin-bottom: 22px; flex-wrap: wrap; }
    .pset-tab { background: transparent; border: none; color: var(--plat-muted); padding: 10px 18px; font-size: 13px; font-weight: 600; cursor: pointer; border-bottom: 2px solid transparent; display: inline-flex; align-items: center; gap: 6px; }
    .pset-tab:hover { color: var(--plat-text); }
    .pset-tab.active { color: var(--plat-accent-2); border-bottom-color: var(--plat-accent); }
    .pset-panel { display: none; }
    .pset-panel.active { display: block; }
    .pset-row { display: grid; grid-template-columns: 280px 1fr; gap: 18px; padding: 14px 0; border-bottom: 1px dashed var(--plat-border); align-items: center; }
    .pset-row:last-child { border-bottom: none; }
    .pset-row-label { font-size: 13px; font-weight: 600; color: var(--plat-text); }
    .pset-row-key   { font-size: 10px; color: var(--plat-muted); font-family: monospace; margin-top: 3px; display: block; }
    .pset-row-desc  { font-size: 11px; color: var(--plat-muted); margin-top: 4px; }
    .pset-input-wrap { display: flex; align-items: center; gap: 10px; }
    .pset-input-wrap .plat-input { flex: 1; }
    .pset-secret-toggle { background: var(--plat-panel-2); border: 1px solid var(--plat-border); color: var(--plat-muted); padding: 8px 10px; border-radius: 8px; cursor: pointer; }
    .pset-secret-toggle:hover { color: var(--plat-accent-2); }
    @media (max-width: 768px) {
        .pset-row { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
@php
    use App\Models\PlatformSetting;

    // Helper: setting değer veya default
    $val = fn(string $k, $def = '') => PlatformSetting::get($k, $def);

    // UI metadata — her key için label + açıklama + input type
    $meta = [
        // Genel
        'platform.brand_name'           => ['label' => 'Marka adı',          'cat' => 'system',        'type' => 'text',     'desc' => 'Tüm e-posta ve faturada görünen marka.', 'tab' => 'genel'],
        'platform.support_email'        => ['label' => 'Destek e-posta',     'cat' => 'system',        'type' => 'email',    'desc' => 'Kullanıcı sorunlarının yönlendirildiği adres.', 'tab' => 'genel'],
        'platform.default_locale'       => ['label' => 'Varsayılan dil',     'cat' => 'system',        'type' => 'text',     'desc' => 'tr / en / de', 'tab' => 'genel'],
        'platform.default_timezone'     => ['label' => 'Varsayılan zaman dilimi', 'cat' => 'system',   'type' => 'text',     'desc' => 'Europe/Berlin gibi.', 'tab' => 'genel'],
        'platform.kvkk_dpo_email'       => ['label' => 'KVKK / DSGVO DPO',   'cat' => 'system',        'type' => 'email',    'desc' => 'Veri Sorumlusu kontak adresi.', 'tab' => 'genel'],

        // Faturalama
        'platform.billing_company'      => ['label' => 'Şirket adı',         'cat' => 'billing',       'type' => 'text',     'desc' => 'Resmi şirket adı (faturada).', 'tab' => 'faturalama'],
        'platform.billing_iban'         => ['label' => 'IBAN',               'cat' => 'billing',       'type' => 'text',     'desc' => 'Banka hesap numarası.', 'tab' => 'faturalama'],
        'platform.billing_vat'          => ['label' => 'KDV / VAT numarası', 'cat' => 'billing',       'type' => 'text',     'desc' => 'VAT-ID veya vergi numarası.', 'tab' => 'faturalama'],
        'platform.billing_email'        => ['label' => 'Faturalama e-postası','cat' => 'billing',      'type' => 'email',    'desc' => 'Fatura gönderim adresi.', 'tab' => 'faturalama'],

        // E-posta
        'platform.smtp_host'            => ['label' => 'SMTP host',          'cat' => 'email',         'type' => 'text',     'desc' => 'smtp.resend.com vb.', 'tab' => 'eposta'],
        'platform.smtp_port'            => ['label' => 'SMTP port',          'cat' => 'email',         'type' => 'number',   'desc' => '587 / 465 / 25', 'tab' => 'eposta'],
        'platform.smtp_user'            => ['label' => 'SMTP user',          'cat' => 'email',         'type' => 'text',     'desc' => '', 'tab' => 'eposta'],
        'platform.smtp_password'        => ['label' => 'SMTP password',      'cat' => 'email',         'type' => 'password', 'desc' => 'Boş bırakırsanız mevcut değer korunur.', 'tab' => 'eposta', 'secret' => true],

        // Bildirim
        'platform.notif_in_app'             => ['label' => 'Uygulama içi bildirim',  'cat' => 'notifications', 'type' => 'toggle', 'desc' => 'Manager paneline canlı bildirim gösterilsin mi?', 'tab' => 'bildirim'],
        'platform.daily_report_recipients'  => ['label' => 'Günlük rapor alıcıları', 'cat' => 'notifications', 'type' => 'list',   'desc' => 'Virgülle ayrılmış e-posta listesi.', 'tab' => 'bildirim'],
    ];

    $tabs = [
        'genel'       => ['name' => 'Genel',       'icon' => 'settings'],
        'faturalama'  => ['name' => 'Faturalama',  'icon' => 'dollar-sign'],
        'eposta'      => ['name' => 'E-posta',     'icon' => 'mail'],
        'bildirim'    => ['name' => 'Bildirim',    'icon' => 'bell'],
    ];
@endphp

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Platform Ayarları</h1>
        <p class="plat-page-sub">Marka, faturalama, e-posta ve bildirim varsayılanları.</p>
    </div>
</div>

<form method="POST" action="{{ route('platform.settings.update') }}" id="pset-form">
    @csrf

    <div class="pset-tabs">
        @foreach ($tabs as $key => $t)
            <button type="button" class="pset-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $key }}">
                <x-icon :name="$t['icon']" size="14" /> {{ $t['name'] }}
            </button>
        @endforeach
    </div>

    @foreach ($tabs as $tabKey => $tab)
        <div class="pset-panel {{ $loop->first ? 'active' : '' }}" data-panel="{{ $tabKey }}">
            <div class="plat-card">
                <h3 class="plat-card-title">
                    <x-icon :name="$tab['icon']" size="16" /> {{ $tab['name'] }}
                </h3>

                @foreach ($meta as $key => $info)
                    @continue($info['tab'] !== $tabKey)
                    @php
                        $current = $val($key);
                        $isSecret = !empty($info['secret']);
                        $displayVal = match ($info['type']) {
                            'list'   => is_array($current) ? implode(', ', $current) : (string) ($current ?? ''),
                            'toggle' => $current ? '1' : '0',
                            default  => is_scalar($current) ? (string) $current : '',
                        };
                        $inputId = 'pset-' . str_replace('.', '-', $key);
                    @endphp

                    <div class="pset-row">
                        <div>
                            <label class="pset-row-label" for="{{ $inputId }}">{{ $info['label'] }}</label>
                            <code class="pset-row-key">{{ $key }}</code>
                            @if (!empty($info['desc']))
                                <div class="pset-row-desc">{{ $info['desc'] }}</div>
                            @endif
                        </div>
                        <div class="pset-input-wrap">
                            @if ($info['type'] === 'toggle')
                                <label class="plat-switch">
                                    <input type="hidden" name="settings[{{ $key }}][value]" value="0">
                                    <input type="checkbox"
                                           id="{{ $inputId }}"
                                           name="settings[{{ $key }}][value]"
                                           value="1"
                                           @checked((bool) $current)>
                                    <span class="plat-switch-slider"></span>
                                </label>
                            @elseif ($info['type'] === 'password')
                                <input type="password"
                                       id="{{ $inputId }}"
                                       class="plat-input"
                                       name="settings[{{ $key }}][value]"
                                       value=""
                                       placeholder="••••• (değiştirmek için yeni değer girin)"
                                       autocomplete="new-password">
                                <button type="button" class="pset-secret-toggle" data-toggle-secret="{{ $inputId }}" title="Göster/Gizle">
                                    <x-icon name="key-round" size="14" />
                                </button>
                            @else
                                <input type="{{ in_array($info['type'], ['number','email']) ? $info['type'] : 'text' }}"
                                       id="{{ $inputId }}"
                                       class="plat-input"
                                       name="settings[{{ $key }}][value]"
                                       value="{{ $displayVal }}">
                            @endif
                            <input type="hidden" name="settings[{{ $key }}][category]" value="{{ $info['cat'] }}">
                            @if ($isSecret)
                                <input type="hidden" name="settings[{{ $key }}][is_secret]" value="1">
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- SMTP test butonu sadece email tab'inde — form="..." attribute ile dış form'la ilişkilendirilmedi (kendi alt form'u olacak ama dış form'un içinde DEĞİL; bu yüzden bu blok dış form'un dışına taşındı). --}}
                @if ($tabKey === 'eposta')
                    <div style="margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--plat-border);">
                        <p style="font-size: 12px; color: var(--plat-muted); margin: 0 0 10px;">
                            <x-icon name="mail" size="12" /> SMTP test e-postası, sayfanın altındaki "Test SMTP gönder" bölümünden gönderilir.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px;">
        <a href="{{ route('platform.dashboard') }}" class="plat-btn plat-btn-ghost">
            <x-icon name="arrow-left" size="14" /> Dashboard
        </a>
        <button type="submit" class="plat-btn plat-btn-primary">
            <x-icon name="check" size="14" /> Tüm değişiklikleri kaydet
        </button>
    </div>
</form>

{{-- SMTP test — ana form'un dışında bağımsız form --}}
<div class="plat-card" style="margin-top: 24px;">
    <h3 class="plat-card-title"><x-icon name="mail" size="16" /> SMTP Test E-postası</h3>
    <p class="plat-card-sub" style="margin-bottom: 14px;">
        Mevcut SMTP ayarları ile bir test mesajı gönderir. Önce yukarıdaki form ile değişikliklerinizi kaydedin.
    </p>
    <form method="POST" action="{{ route('platform.settings.test-email') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        @csrf
        <div style="flex:1; min-width:240px;">
            <label class="plat-form-label" for="test-to">Alıcı e-posta</label>
            <input type="email" id="test-to" name="to" class="plat-input"
                   placeholder="{{ PlatformSetting::get('platform.support_email', 'support@mentorde.com') }}">
        </div>
        <button type="submit" class="plat-btn plat-btn-ghost">
            <x-icon name="mail" size="14" /> Test gönder
        </button>
    </form>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    // Tab switching
    const tabs = document.querySelectorAll('.pset-tab');
    const panels = document.querySelectorAll('.pset-panel');
    tabs.forEach(t => {
        t.addEventListener('click', () => {
            const target = t.dataset.tab;
            tabs.forEach(x => x.classList.toggle('active', x === t));
            panels.forEach(p => p.classList.toggle('active', p.dataset.panel === target));
        });
    });

    // Password visibility toggle
    document.querySelectorAll('[data-toggle-secret]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.toggleSecret;
            const input = document.getElementById(id);
            if (input) input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
})();
</script>
@endpush

@endsection

@extends('student.layouts.app')

@section('title', 'Hesap Kasam')
@section('page_title', 'Hesap Kasam')

@push('head')
<style>
/* ── vlt-* Vault scoped ── */

/* Header */
.vlt-header {
    display: flex; align-items: center; gap: 16px;
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    border-radius: 14px; padding: 18px 22px; margin-bottom: 20px; color: #fff;
}
.vlt-header-icon {
    width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
    background: rgba(255,255,255,.15); display: flex; align-items: center;
    justify-content: center; font-size: 24px;
}
.vlt-header-title { font-size: 18px; font-weight: 800; margin-bottom: 3px; }
.vlt-header-sub   { font-size: 12px; opacity: .75; }
.vlt-count-pill {
    margin-left: auto; flex-shrink: 0;
    background: rgba(255,255,255,.2); border-radius: 999px;
    padding: 5px 14px; font-size: 13px; font-weight: 700;
}

/* Cards grid */
.vlt-grid {
    display: grid; grid-template-columns: repeat(2,1fr); gap: 14px; margin-bottom: 20px;
}
@media(max-width:820px){ .vlt-grid { grid-template-columns: 1fr; } }

.vlt-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; overflow: hidden;
    transition: border-color .15s, box-shadow .15s;
}
.vlt-card:hover { border-color: #7c3aed; box-shadow: 0 4px 16px rgba(124,58,237,.08); }

.vlt-card-head {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; border-bottom: 1px solid var(--u-line);
}
.vlt-icon {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: rgba(124,58,237,.08); border: 1px solid rgba(124,58,237,.15);
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.vlt-service-name { font-size: 14px; font-weight: 800; color: var(--u-text); line-height: 1.2; }
.vlt-service-url  {
    font-size: 11px; color: #7c3aed; margin-top: 3px; display: block;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;
    text-decoration: none;
}
.vlt-service-url:hover { text-decoration: underline; }

/* Credential rows */
.vlt-creds { padding: 12px 16px; display: flex; flex-direction: column; gap: 8px; }
.vlt-row {
    display: grid; grid-template-columns: 68px 1fr auto; gap: 6px;
    align-items: center; background: var(--u-bg);
    border: 1px solid var(--u-line); border-radius: 8px; padding: 8px 10px;
}
.vlt-row-lbl {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: var(--u-muted); white-space: nowrap;
}
.vlt-row-val {
    font-size: 12px; font-family: 'Courier New', monospace; color: var(--u-text);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0;
}
.vlt-copy-btn {
    flex-shrink: 0; padding: 4px 10px; border: 1px solid var(--u-line);
    border-radius: 6px; background: var(--u-card); color: var(--u-text);
    font-size: 11px; font-weight: 600; cursor: pointer; white-space: nowrap;
    transition: border-color .15s, color .15s;
}
.vlt-copy-btn:hover { border-color: #7c3aed; color: #7c3aed; }
.vlt-copy-btn.copied { border-color: #16a34a; color: #16a34a; background: #f0fdf4; }

/* Password row special */
.vlt-pw-val { letter-spacing: 3px; color: var(--u-muted); }
.vlt-pw-val.revealed { letter-spacing: normal; color: var(--u-text); }
.vlt-reveal-btn {
    flex-shrink: 0; padding: 4px 10px; border: 1px solid #7c3aed;
    border-radius: 6px; background: rgba(124,58,237,.06); color: #7c3aed;
    font-size: 11px; font-weight: 700; cursor: pointer; white-space: nowrap;
    transition: background .15s;
}
.vlt-reveal-btn:hover { background: rgba(124,58,237,.12); }
.vlt-reveal-btn:disabled { opacity: .5; cursor: not-allowed; }
.vlt-pw-actions { display: flex; gap: 4px; }

/* Notes */
.vlt-notes {
    margin: 0 16px 14px; padding: 10px 12px;
    background: var(--u-bg); border: 1px solid var(--u-line);
    border-left: 3px solid #7c3aed; border-radius: 8px;
    font-size: 12px; color: var(--u-muted); line-height: 1.5;
}

/* Security notice */
.vlt-notice {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 14px 18px;
    display: flex; gap: 14px; align-items: flex-start;
}
.vlt-notice-icon { font-size: 22px; flex-shrink: 0; }
.vlt-notice-title { font-size: 13px; font-weight: 700; color: var(--u-text); margin-bottom: 6px; }
.vlt-notice ul { margin: 0; padding-left: 16px; display: flex; flex-direction: column; gap: 3px; }
.vlt-notice li  { font-size: 12px; color: var(--u-muted); }

/* Empty */
.vlt-empty {
    text-align: center; padding: 48px 20px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; color: var(--u-muted); margin-bottom: 20px;
}
</style>
@endpush

@section('content')
@php
    $vaultList = $vaults ?? collect();

    $iconMap = [
        'uni'      => '🎓', 'tu'       => '🎓', 'assist'   => '🎓', 'hochschul' => '🎓',
        'bank'     => '🏦', 'sperr'    => '🏦', 'deutsch'  => '🏦',
        'visa'     => '🛂', 'konsol'   => '🛂', 'diplo'    => '🛂',
        'wohnung'  => '🏠', 'dorm'     => '🏠', 'studieren' => '🏠', 'yurt'     => '🏠',
        'email'    => '✉️', 'gmail'    => '✉️', 'outlook'  => '✉️',
        'sprach'   => '🌍', 'goethe'   => '🌍', 'telc'     => '🌍',
    ];
    $getIcon = function(string $label) use ($iconMap): string {
        $lower = mb_strtolower($label);
        foreach ($iconMap as $key => $icon) {
            if (str_contains($lower, $key)) return $icon;
        }
        return '🔑';
    };
@endphp

{{-- Header --}}
<div class="vlt-header">
    <div class="vlt-header-icon">🔐</div>
    <div>
        <div class="vlt-header-title">Hesap Kasam</div>
        <div class="vlt-header-sub">Danışmanınız tarafından paylaşılan portal hesap bilgileri</div>
    </div>
    @if($vaultList->isNotEmpty())
    <div class="vlt-count-pill">{{ $vaultList->count() }} hesap</div>
    @endif
</div>

{{-- Vault cards --}}
@if($vaultList->isEmpty())
<div class="vlt-empty">
    <div style="font-size:40px;margin-bottom:10px;">🔐</div>
    <div style="font-size:var(--tx-base);font-weight:700;margin-bottom:6px;">Henüz paylaşılan hesap yok</div>
    <div style="font-size:var(--tx-sm);">Danışmanınız hesap bilgilerini oluşturup paylaştığında burada görünecek.</div>
</div>
@else
<div class="vlt-grid">
    @foreach($vaultList as $vault)
    @php $icon = $getIcon($vault->service_label ?? $vault->service_name ?? ''); @endphp
    <div class="vlt-card">
        {{-- Head --}}
        <div class="vlt-card-head">
            <div class="vlt-icon">{{ $icon }}</div>
            <div style="flex:1;min-width:0;">
                <div class="vlt-service-name">{{ $vault->service_label }}</div>
                @if($vault->account_url)
                    <a class="vlt-service-url" href="{{ $vault->account_url }}" target="_blank" rel="noopener">
                        🔗 {{ parse_url($vault->account_url, PHP_URL_HOST) ?? $vault->account_url }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Credentials --}}
        <div class="vlt-creds">
            {{-- Email --}}
            <div class="vlt-row">
                <span class="vlt-row-lbl">E-posta</span>
                <span class="vlt-row-val">{{ $vault->account_email }}</span>
                <button class="vlt-copy-btn" onclick="vltCopy('{{ addslashes($vault->account_email) }}', this)">Kopyala</button>
            </div>

            {{-- Username --}}
            @if($vault->account_username)
            <div class="vlt-row">
                <span class="vlt-row-lbl">Kullanıcı</span>
                <span class="vlt-row-val">{{ $vault->account_username }}</span>
                <button class="vlt-copy-btn" onclick="vltCopy('{{ addslashes($vault->account_username) }}', this)">Kopyala</button>
            </div>
            @endif

            {{-- Password --}}
            <div class="vlt-row">
                <span class="vlt-row-lbl">Şifre</span>
                <span class="vlt-pw-val" id="pw-{{ $vault->id }}">••••••••</span>
                <div class="vlt-pw-actions">
                    <button class="vlt-reveal-btn"
                        id="reveal-btn-{{ $vault->id }}"
                        data-vault-id="{{ $vault->id }}"
                        data-reveal-url="{{ route('student.vault.reveal', $vault->id) }}"
                        onclick="vltReveal(this)">👁 Göster</button>
                    <button class="vlt-copy-btn" id="copy-pw-{{ $vault->id }}" style="display:none;"
                        onclick="vltCopy(document.getElementById('pw-{{ $vault->id }}').textContent, this)">Kopyala</button>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        @if($vault->notes)
        <div class="vlt-notes">📌 {{ $vault->notes }}</div>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- Security notice --}}
<div class="vlt-notice">
    <div class="vlt-notice-icon">🛡️</div>
    <div>
        <div class="vlt-notice-title">Güvenlik Notu</div>
        <ul>
            <li>Şifreler sistem tarafından şifrelenmiş olarak saklanır, açık metin tutulmaz.</li>
            <li>Her "Göster" tıklaması erişim kaydı olarak kayıt altına alınır.</li>
            <li>Bu sayfayı başkalarıyla paylaşmayın veya ekran görüntüsü almayın.</li>
        </ul>
    </div>
</div>

<script>
function vltReveal(btn) {
    const id  = btn.dataset.vaultId;
    const url = btn.dataset.revealUrl;
    const pw  = document.getElementById('pw-' + id);
    const cpBtn = document.getElementById('copy-pw-' + id);

    if (btn.dataset.revealed === '1') {
        pw.textContent = '••••••••';
        pw.classList.remove('revealed');
        btn.innerHTML = '👁 Göster';
        btn.dataset.revealed = '0';
        if (cpBtn) cpBtn.style.display = 'none';
        return;
    }

    btn.disabled = true;
    btn.textContent = '...';

    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.password) {
                pw.textContent = data.password;
                pw.classList.add('revealed');
                btn.innerHTML = '🙈 Gizle';
                btn.dataset.revealed = '1';
                btn.disabled = false;
                if (cpBtn) cpBtn.style.display = 'inline-block';
            } else {
                btn.textContent = 'Hata';
                btn.disabled = false;
            }
        })
        .catch(() => { btn.textContent = 'Hata'; btn.disabled = false; });
}

function vltCopy(text, btn) {
    if (!text || text === '••••••••') return;
    navigator.clipboard.writeText(text).catch(() => {
        const el = Object.assign(document.createElement('textarea'), { value: text });
        Object.assign(el.style, { position: 'fixed', opacity: '0' });
        document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el);
    }).finally?.(() => {});
    const orig = btn.textContent;
    btn.textContent = '✓ Kopyalandı';
    btn.classList.add('copied');
    setTimeout(() => { btn.textContent = orig; btn.classList.remove('copied'); }, 1800);
}
</script>
@endsection

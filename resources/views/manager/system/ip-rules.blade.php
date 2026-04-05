@extends('manager.layouts.app')
@section('title', 'IP Erişim Kuralları')
@section('page_title', 'IP Erişim Kuralları')

@section('content')

@if(session('status'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#15803d;">{{ session('status') }}</div>
@endif

@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#991b1b;">
    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

{{-- Breadcrumb --}}
<div style="display:flex;gap:6px;align-items:center;margin-bottom:14px;font-size:11px;color:var(--u-muted);">
    <a href="/manager/system" style="color:#1e40af;text-decoration:none;font-weight:700;">Sistem Paneli</a>
    <span>›</span>
    <span>IP Erişim Kuralları</span>
</div>

{{-- Açıklama --}}
<div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:12px;color:#1e40af;">
    <strong>IP Erişim Kuralları</strong> — Beyaz liste (sadece bu IP'lere izin ver) veya kara liste (bu IP'leri engelle) tanımlayabilirsiniz.
    Kurallar rol bazlı uygulanabilir. CIDR notasyonu desteklenir (örn. <code>192.168.1.0/24</code>).
</div>

<div class="grid2" style="gap:14px;align-items:start;">

{{-- Kural Listesi --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
        <div style="font-weight:700;font-size:var(--tx-sm);">🌐 Mevcut Kurallar ({{ $rules->count() }})</div>
    </div>
    @if($rules->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--u-muted);font-size:13px;">
        <div style="font-size:28px;margin-bottom:8px;">🌐</div>
        Henüz IP kuralı tanımlanmamış.
    </div>
    @else
    @foreach($rules as $rule)
    @php
        $isWhite = $rule->rule_type === 'whitelist';
        $typeColor = $isWhite ? '#16a34a' : '#dc2626';
        $typeBg    = $isWhite ? '#dcfce7' : '#fee2e2';
        $typeLabel = $isWhite ? 'Beyaz Liste' : 'Kara Liste';
    @endphp
    <div style="padding:11px 16px;border-bottom:1px solid var(--u-line);display:flex;gap:10px;align-items:center;{{ !$rule->is_active ? 'opacity:.5;' : '' }}">
        <span style="background:{{ $typeBg }};color:{{ $typeColor }};font-size:10px;font-weight:800;padding:3px 8px;border-radius:5px;white-space:nowrap;">{{ $typeLabel }}</span>
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text);font-family:monospace;">{{ $rule->ip_range }}</div>
            @if($rule->description)
            <div style="font-size:10px;color:var(--u-muted);">{{ $rule->description }}</div>
            @endif
            @if($rule->applies_to_roles && count($rule->applies_to_roles))
            <div style="font-size:10px;color:var(--u-muted);margin-top:2px;">
                Roller: {{ implode(', ', $rule->applies_to_roles) }}
            </div>
            @endif
        </div>
        <div style="display:flex;gap:5px;flex-shrink:0;">
            {{-- Toggle --}}
            <form method="POST" action="/manager/system/ip-rules/{{ $rule->id }}/toggle">
                @csrf @method('PATCH')
                <button type="submit" class="btn {{ $rule->is_active ? 'warn' : 'ok' }}" style="font-size:10px;padding:4px 10px;">
                    {{ $rule->is_active ? 'Pasif Yap' : 'Aktif Et' }}
                </button>
            </form>
            {{-- Delete --}}
            <form method="POST" action="/manager/system/ip-rules/{{ $rule->id }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn warn" style="font-size:10px;padding:4px 10px;"
                        onclick="return confirm('Kuralı silmek istediğinize emin misiniz?')">✕</button>
            </form>
        </div>
    </div>
    @endforeach
    @endif
</section>

{{-- Yeni Kural Formu --}}
<section class="panel">
    <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">➕ Yeni Kural Ekle</div>
    <form method="POST" action="/manager/system/ip-rules">
        @csrf
        <div style="margin-bottom:12px;">
            <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Kural Türü</label>
            <select name="rule_type" required style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                <option value="whitelist">✅ Beyaz Liste (İzin Ver)</option>
                <option value="blacklist">🚫 Kara Liste (Engelle)</option>
            </select>
        </div>
        <div style="margin-bottom:12px;">
            <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">IP Adresi / CIDR</label>
            <input type="text" name="ip_range" required placeholder="192.168.1.1 veya 10.0.0.0/8"
                   style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;font-family:monospace;">
            <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">Tek IP veya CIDR notasyonu (ör. 192.168.0.0/16)</div>
        </div>
        <div style="margin-bottom:12px;">
            <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Açıklama (İsteğe Bağlı)</label>
            <input type="text" name="description" placeholder="Ofis ağı, VPN, vb."
                   style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;">
        </div>
        <div style="margin-bottom:16px;">
            <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:6px;">Uygulanan Roller (İsteğe Bağlı)</label>
            @php
            $availRoles = ['manager','senior','system_admin','system_staff','operations_admin','operations_staff',
                           'finance_admin','finance_staff','marketing_admin','marketing_staff','sales_admin','sales_staff'];
            @endphp
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
            @foreach($availRoles as $r)
            <label style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:var(--u-text);cursor:pointer;padding:3px 8px;border:1.5px solid var(--u-line);border-radius:6px;background:var(--u-bg);">
                <input type="checkbox" name="applies_to_roles[]" value="{{ $r }}" style="margin:0;">
                {{ str_replace('_', ' ', $r) }}
            </label>
            @endforeach
            </div>
            <div style="font-size:10px;color:var(--u-muted);margin-top:4px;">Hiçbir şey seçilmezse tüm rollere uygulanır.</div>
        </div>
        <button type="submit" class="btn" style="width:100%;">Kural Ekle</button>
    </form>
</section>

</div>

@endsection

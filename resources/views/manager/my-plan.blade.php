@extends('manager.layouts.app')

@section('title', 'Planım')
@section('page_title', 'Planım & Modüller')

@section('content')
@php
    $tierColors = [
        'trial'   => ['bg' => 'rgba(100,116,139,.12)', 'fg' => '#475569', 'border' => 'rgba(100,116,139,.3)'],
        'basic'   => ['bg' => 'rgba(37,99,235,.12)',   'fg' => '#1d4ed8', 'border' => 'rgba(37,99,235,.3)'],
        'gold'    => ['bg' => 'rgba(217,119,6,.15)',   'fg' => '#b45309', 'border' => 'rgba(217,119,6,.35)'],
        'premium' => ['bg' => 'rgba(124,58,237,.15)',  'fg' => '#6b21a8', 'border' => 'rgba(124,58,237,.35)'],
    ];
    $currentColor = $tierColors[$tierKey] ?? $tierColors['trial'];
    $currentLabel = $tierConfig['label'] ?? ucfirst($tierKey);
    $currentMrr   = $tierConfig['mrr_eur'] ?? 0;

    $moduleLabels = [
        'core'                    => 'Temel Modul',
        'application_guides'      => 'Basvuru Rehberleri (Uni-Assist, Vize, APS)',
        'doc_request'             => 'Belge Talep Linki',
        'manager_password_reset'  => 'Sifre Sifirlama Yonetimi',
        'booking'                 => 'Randevu Sistemi',
        'dam'                     => 'DAM (Doküman Yönetimi)',
        'content_hub'             => 'Icerik Hub',
        'multi_provider_ai'       => 'Çoklu AI Saglayici',
        'doc_builder_ai'          => 'AI Belge Olusturucu',
        'ai_labs'                 => 'AI Labs',
        'dealer'                  => 'Bayi Yonetimi',
        'page_visibility'         => 'Sayfa Görünürlük Kontrolü',
        'silence_checkin'         => 'Sessizlik Monitoru',
        'marketing_admin'         => 'Marketing Admin Paneli',
        'contracts_hub'           => 'Sozlesme Hub',
        'discount_codes'          => 'Indirim Kodlari',
        'event_management'        => 'Etkinlik Yonetimi',
    ];
@endphp

<div style="max-width:1100px;">

    @if(session('status'))
    <div style="padding:12px 16px;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);border-radius:8px;color:#047857;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <x-icon name="circle-check" size="18" /> {{ session('status') }}
    </div>
    @endif

    {{-- ─── Mevcut Plan Karti ─────────────────────────────────────────── --}}
    <section class="panel" style="margin-bottom:16px;padding:24px;border:1px solid {{ $currentColor['border'] }};background:{{ $currentColor['bg'] }};">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:{{ $currentColor['fg'] }};margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                    <x-icon name="crown" size="14" /> Mevcut Plan
                </div>
                <div style="font-size:28px;font-weight:800;color:{{ $currentColor['fg'] }};line-height:1;letter-spacing:-.5px;">
                    {{ $currentLabel }}
                </div>
                <div style="font-size:13px;color:var(--u-muted,#64748b);margin-top:8px;">
                    {{ $company->name }}
                    @if($company->billing_email)
                        · {{ $company->billing_email }}
                    @endif
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--u-muted,#64748b);margin-bottom:4px;">Aylik Ucret</div>
                <div style="font-size:32px;font-weight:800;color:var(--u-text,#0f172a);line-height:1;">
                    €{{ number_format($currentMrr, 0, ',', '.') }}
                </div>
                @if($tierKey === 'trial' && $company->trial_ends_at)
                    <div style="font-size:11px;color:#dc2626;margin-top:6px;font-weight:600;">
                        Trial bitimi: {{ \Illuminate\Support\Carbon::parse($company->trial_ends_at)->format('d.m.Y') }}
                    </div>
                @elseif($company->subscription_renews_at)
                    <div style="font-size:11px;color:var(--u-muted,#64748b);margin-top:6px;">
                        Yenileme: {{ \Illuminate\Support\Carbon::parse($company->subscription_renews_at)->format('d.m.Y') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Tier limitleri (varsa) --}}
        @php $limits = $tierConfig['limits'] ?? []; @endphp
        @if(!empty($limits))
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;margin-top:18px;padding-top:16px;border-top:1px solid {{ $currentColor['border'] }};">
            @foreach($limits as $limitKey => $limitValue)
            <div style="font-size:12px;">
                <div style="font-weight:700;color:{{ $currentColor['fg'] }};font-size:14px;">
                    {{ $limitValue === null ? 'Sinirsiz' : number_format($limitValue, 0, ',', '.') }}
                </div>
                <div style="color:var(--u-muted,#64748b);font-size:11px;text-transform:capitalize;">
                    {{ str_replace('_', ' ', $limitKey) }}
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

    {{-- ─── Aktif Modüller Listesi ────────────────────────────────────── --}}
    <section class="panel" style="margin-bottom:16px;padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;">
                    <x-icon name="package" size="18" /> Aktif Modüller
                </h3>
                <div style="font-size:12px;color:var(--u-muted,#64748b);margin-top:2px;">
                    Planınıza dahil olan ve şu an kullanabileceğiniz özellikler
                </div>
            </div>
            <span style="font-size:12px;font-weight:700;padding:4px 12px;border-radius:999px;background:rgba(22,163,74,.12);color:#047857;">
                {{ count($enabledModules) }} aktif / {{ count($allModules) }} toplam
            </span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:8px;">
            @foreach($allModules as $module)
                @php $isEnabled = in_array($module, $enabledModules, true); @endphp
                <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:{{ $isEnabled ? 'rgba(22,163,74,.05)' : 'var(--u-bg,#f8fafc)' }};{{ $isEnabled ? '' : 'opacity:.55;' }}">
                    @if($isEnabled)
                        <span style="color:#16a34a;flex-shrink:0;"><x-icon name="circle-check" size="18" /></span>
                    @else
                        <span style="color:#94a3b8;flex-shrink:0;"><x-icon name="x" size="18" /></span>
                    @endif
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;color:var(--u-text,#0f172a);">
                            {{ $moduleLabels[$module] ?? $module }}
                        </div>
                        @if(!$isEnabled)
                            <div style="font-size:10.5px;color:var(--u-muted,#94a3b8);font-style:italic;">Daha üst plana yükseltin</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ─── Yükseltme Talebi Formu ───────────────────────────────────── --}}
    @if($tierKey !== 'premium')
    <section class="panel" style="padding:20px;border:2px dashed rgba(124,58,237,.3);background:rgba(124,58,237,.04);">
        <h3 style="margin:0 0 6px;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;color:#6b21a8;">
            <x-icon name="trending-up" size="18" /> Planı Yükselt
        </h3>
        <div style="font-size:13px;color:var(--u-muted,#64748b);margin-bottom:18px;line-height:1.55;">
            Daha fazla modüle, daha yüksek limitlere ve premium özelliklere erişmek için planınızı yükseltin.
            Talebiniz Mentorde Platform sahibine iletilir, en kısa sürede dönüş yapılır.
        </div>

        {{-- Tier karşılaştırma --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-bottom:18px;">
            @foreach($allTiers as $tKey => $tConfig)
                @php
                    $isCurrent = $tKey === $tierKey;
                    $tColor = $tierColors[$tKey] ?? $tierColors['trial'];
                @endphp
                <div style="padding:14px;border:2px solid {{ $isCurrent ? $tColor['border'] : 'var(--u-line,#e2e8f0)' }};border-radius:10px;background:{{ $isCurrent ? $tColor['bg'] : 'var(--u-card,#fff)' }};position:relative;">
                    @if($isCurrent)
                        <div style="position:absolute;top:-10px;right:10px;background:{{ $tColor['fg'] }};color:#fff;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;text-transform:uppercase;letter-spacing:.5px;">Şu Anki</div>
                    @endif
                    <div style="font-size:13px;font-weight:700;color:{{ $tColor['fg'] }};margin-bottom:4px;">
                        {{ $tConfig['label'] ?? ucfirst($tKey) }}
                    </div>
                    <div style="font-size:24px;font-weight:800;color:var(--u-text,#0f172a);line-height:1;margin-bottom:8px;">
                        €{{ number_format($tConfig['mrr_eur'] ?? 0, 0, ',', '.') }}<span style="font-size:12px;font-weight:600;color:var(--u-muted,#64748b);">/ay</span>
                    </div>
                    @php $modules = $tConfig['modules'] ?? []; @endphp
                    <div style="font-size:11px;color:var(--u-muted,#64748b);">
                        {{ $modules === '*' ? 'Tüm modüller (' . count($allModules) . ')' : count($modules) . ' modül' }}
                    </div>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('manager.my-plan.upgrade-request') }}" style="margin-top:8px;">
            @csrf
            <div style="display:grid;grid-template-columns:200px 1fr;gap:10px;align-items:end;margin-bottom:12px;">
                <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;">
                    <span>İstediğiniz tier</span>
                    <select name="desired_tier" required style="padding:8px 10px;border:1px solid var(--u-line,#cbd5e1);border-radius:7px;font-size:13px;">
                        @foreach($allTiers as $tKey => $tConfig)
                            @if($tKey !== $tierKey && $tKey !== 'trial')
                                <option value="{{ $tKey }}">{{ $tConfig['label'] }} (€{{ $tConfig['mrr_eur'] }}/ay)</option>
                            @endif
                        @endforeach
                    </select>
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;">
                    <span>Mesajınız (opsiyonel)</span>
                    <input type="text" name="message" maxlength="500" placeholder="Hangi modüle ihtiyacınız var, ne zamana kadar..." style="padding:8px 10px;border:1px solid var(--u-line,#cbd5e1);border-radius:7px;font-size:13px;">
                </label>
            </div>
            <button type="submit" style="padding:10px 20px;background:#7e58bf;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                <x-icon name="send" size="14" /> Yükseltme Talebi Gönder
            </button>
        </form>
    </section>
    @endif
</div>
@endsection

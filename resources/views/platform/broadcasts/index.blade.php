@extends('platform.layouts.app')

@section('title', 'Duyurular — DGmarkt Platform')

@section('content')
    <div class="plat-page-header">
        <div>
            <h1 class="plat-page-title"><x-icon name="megaphone" size="22" /> Duyurular</h1>
            <p class="plat-page-sub">Tum musteri company'lere cross-tenant duyuru / kampanya gonder.</p>
        </div>
        <a href="{{ route('platform.broadcasts.create') }}" class="plat-btn plat-btn-primary">
            <x-icon name="plus" size="14" /> Yeni Duyuru
        </a>
    </div>

    {{-- KPI'lar --}}
    <div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="send" size="12" /> Toplam Gonderilen</div>
            <div class="plat-kpi-value">{{ number_format($totalSent) }}</div>
            <div class="plat-kpi-sub">tum zamanlar</div>
        </div>
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="eye" size="12" /> Ort. Acilma Orani</div>
            <div class="plat-kpi-value">{{ $avgOpenRate }}%</div>
            <div class="plat-kpi-sub">son gonderilenler</div>
        </div>
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="mouse-pointer-click" size="12" /> Ort. Tiklama Orani</div>
            <div class="plat-kpi-value">{{ $avgClickRate }}%</div>
            <div class="plat-kpi-sub">CTA tiklamalari</div>
        </div>
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="calendar" size="12" /> Bu Ay Gonderilen</div>
            <div class="plat-kpi-value">{{ $thisMonthSent }}</div>
            <div class="plat-kpi-sub">duyuru sayisi</div>
        </div>
    </div>

    {{-- Filtreler --}}
    <div class="plat-card" style="margin-bottom:18px;">
        <form method="GET" style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items:end;">
            <div class="plat-form-group" style="margin:0;">
                <label class="plat-form-label">Durum</label>
                <select name="status" class="plat-select">
                    <option value="">Tumu</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="plat-form-group" style="margin:0;">
                <label class="plat-form-label">Kanal</label>
                <select name="channel" class="plat-select">
                    <option value="">Tumu</option>
                    @foreach ($channels as $c)
                        <option value="{{ $c }}" {{ ($filters['channel'] ?? '') === $c ? 'selected' : '' }}>
                            {{ $c === 'in_app' ? 'In-App' : ucfirst($c) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="plat-btn plat-btn-ghost">
                <x-icon name="filter" size="14" /> Filtrele
            </button>
        </form>
    </div>

    {{-- Tablo --}}
    <div class="plat-card" style="padding:0;">
        @if ($broadcasts->isEmpty())
            <div style="padding:60px 20px; text-align:center; color:var(--plat-muted);">
                <x-icon name="megaphone" size="40" />
                <p style="margin:14px 0 6px; color:#fff; font-weight:700;">Henuz duyuru yok</p>
                <p style="margin:0; font-size:13px;">Ilk duyurunu olusturmak icin "Yeni Duyuru" butonuna bas.</p>
            </div>
        @else
            <table class="plat-table">
                <thead>
                <tr>
                    <th>Baslik</th>
                    <th>Durum</th>
                    <th>Kanal</th>
                    <th>Hedef</th>
                    <th>Gonderilen</th>
                    <th>Acilma %</th>
                    <th>Tiklama %</th>
                    <th>Tarih</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($broadcasts as $b)
                    <tr>
                        <td>
                            <a href="{{ route('platform.broadcasts.show', $b->id) }}" style="font-weight:700; color:#fff;">
                                {{ $b->title }}
                            </a>
                            @if ($b->cta_label)
                                <div style="font-size:11px; color:var(--plat-muted); margin-top:2px;">
                                    CTA: {{ $b->cta_label }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="plat-badge {{ $b->statusBadgeClass() }}">{{ $b->statusLabel() }}</span>
                        </td>
                        <td>
                            <span style="text-transform:capitalize; font-weight:600;">
                                {{ $b->channel === 'in_app' ? 'In-App' : $b->channel }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--plat-muted);">
                                {{ ucfirst($b->target_segment) }}
                                @if (is_array($b->target_tiers) && $b->target_tiers)
                                    &middot; {{ implode(', ', $b->target_tiers) }}
                                @endif
                            </span>
                        </td>
                        <td><strong>{{ $b->sent_count }}</strong></td>
                        <td>{{ $b->openRate() }}%</td>
                        <td>{{ $b->clickRate() }}%</td>
                        <td>
                            @if ($b->sent_at)
                                <span style="font-size:12px;">{{ $b->sent_at->format('d.m.Y H:i') }}</span>
                            @elseif ($b->scheduled_for)
                                <span style="font-size:12px; color:var(--plat-info);">
                                    <x-icon name="clock" size="12" /> {{ $b->scheduled_for->format('d.m.Y H:i') }}
                                </span>
                            @else
                                <span style="font-size:12px; color:var(--plat-muted);">{{ $b->created_at->format('d.m.Y') }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('platform.broadcasts.show', $b->id) }}" class="plat-btn plat-btn-ghost plat-btn-sm">
                                <x-icon name="arrow-right" size="12" />
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div style="padding:14px 20px;">
                {{ $broadcasts->links() }}
            </div>
        @endif
    </div>
@endsection

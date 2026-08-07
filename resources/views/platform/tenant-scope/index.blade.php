@extends('platform.layouts.app')

@section('title', 'Tenant Kapsam Raporu')

@section('content')

<h1 class="plat-page-title">Tenant Kapsam Raporu</h1>
<p class="plat-page-sub">
    Bir tabloya firma izolasyonu (<code>BelongsToCompany</code>) eklendiğinde okuma
    <code>company_id</code>'ye göre filtrelenir. Sahibi belli olmayan satırlar o anda
    ekranlardan <strong>sessizce kaybolur</strong> — hata bile vermeden.
    Bu rapor, izolasyon açılmadan önce o satırları sayar.
</p>

@php
    $blocked = $report['unowned'];
    $factory = $report['factory'];
@endphp

@if($blocked === 0)
    <div class="plat-card" style="border-color:rgba(22,163,74,.4);background:rgba(22,163,74,.06);margin-bottom:18px;">
        <strong style="color:#4ade80;">Sahibi bilinmeyen satır yok.</strong>
        Bu tablolara izolasyon eklenebilir; hiçbir kayıt ekrandan kaybolmaz.
    </div>
@else
    <div class="plat-card" style="border-color:rgba(220,38,38,.45);background:rgba(220,38,38,.08);margin-bottom:18px;">
        <strong style="color:#fca5a5;">{{ number_format($blocked, 0, ',', '.') }} satırın sahibi bilinmiyor.</strong>
        Bu tablolara izolasyon <strong>eklenmemeli</strong>: eklenirse bu kayıtlar
        ilgili ekranlardan kaybolur. Önce geri-doldurma çalışmalı.
    </div>
@endif

@if($factory > 0)
    <div class="plat-card" style="border-color:rgba(217,119,6,.4);background:rgba(217,119,6,.07);margin-bottom:18px;">
        <strong style="color:#fbbf24;">{{ number_format($factory, 0, ',', '.') }} fabrika satırı.</strong>
        <code>company_id = 0</code> bu projede sahipsizlik değil, bilinçli
        <strong>fabrika şablonu</strong> işaretidir — form tanımı ve hizmet kataloğu
        böyle çalışır: firmanın kendi satırı → üst firma → fabrika.
        Bu satırları kapsam yine gizler, okuma miras yolundan geçer.
    </div>
@endif

<div class="plat-card">
    <h3 class="plat-card-title">Tablolar</h3>

    <div style="overflow-x:auto;">
        <table class="plat-table" style="width:100%;">
            <thead>
                <tr>
                    <th style="text-align:left;">Tablo</th>
                    <th style="text-align:right;">Satır</th>
                    <th style="text-align:right;">Sahipsiz</th>
                    <th style="text-align:right;">Fabrika</th>
                    <th style="text-align:left;">Durum</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['rows'] as $row)
                    <tr>
                        <td style="font-family:ui-monospace,monospace;font-size:12.5px;">{{ $row['table'] }}</td>
                        <td style="text-align:right;">{{ number_format($row['total'], 0, ',', '.') }}</td>
                        <td style="text-align:right;color:{{ $row['unowned'] > 0 ? '#fca5a5' : 'var(--plat-muted)' }};">
                            {{ $row['unowned'] > 0 ? number_format($row['unowned'], 0, ',', '.') : '—' }}
                        </td>
                        <td style="text-align:right;color:{{ $row['factory'] > 0 ? '#fbbf24' : 'var(--plat-muted)' }};">
                            {{ $row['factory'] > 0 ? number_format($row['factory'], 0, ',', '.') : '—' }}
                        </td>
                        <td>
                            @if($row['status'] === \App\Support\TenantScopeReport::STATUS_BLOCKED)
                                <span style="color:#fca5a5;font-weight:700;">BEKLE</span>
                            @elseif($row['status'] === \App\Support\TenantScopeReport::STATUS_FACTORY)
                                <span style="color:#fbbf24;font-weight:700;">FABRİKA</span>
                            @else
                                <span style="color:#4ade80;font-weight:700;">HAZIR</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($report['skipped'] !== [])
        <div class="plat-card-sub" style="margin-top:12px;">
            Atlandı (tablo ya da kolon yok): {{ implode(', ', $report['skipped']) }}
        </div>
    @endif
</div>

@endsection

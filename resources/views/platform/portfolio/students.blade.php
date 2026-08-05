@extends('platform.layouts.app')

@section('title', 'Öğrenci Hacmi — Platform')

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Öğrenci Hacmi</h1>
        <p class="plat-page-sub">Şirket başına öğrenci sayıları — kişisel veri gösterilmez</p>
    </div>
</div>

<div class="plat-card" style="margin-bottom:18px;border-left:3px solid var(--plat-accent-2);">
    <div style="font-size:12px;color:var(--plat-muted);line-height:1.7;">
        <strong style="color:#fff;">Neden isim yok:</strong>
        DGmarkt yazılım servisi sağlar; müşterilerinin öğrencileri için veri sorumlusu değildir.
        Ad, e-posta ve telefon bu konsolda <strong style="color:#fff;">bilerek gösterilmez</strong>.
        <br>Kişi düzeyindeki işler operasyonu yürüten şirkete aittir.
    </div>
</div>

<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="graduation-cap" size="12" /> Toplam Öğrenci</div>
        <div class="plat-kpi-value">{{ number_format($grandTotal, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">tüm şirketler</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="building-2" size="12" /> Öğrencisi Olan</div>
        <div class="plat-kpi-value">{{ $companies->where('students', '>', 0)->count() }}</div>
        <div class="plat-kpi-sub">/ {{ $companies->count() }} şirket</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="users" size="12" /> Bekleyen Aday</div>
        <div class="plat-kpi-value">{{ number_format($companies->sum('leads'), 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">henüz dönüşmemiş</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="check" size="12" /> Aktif Şirket</div>
        <div class="plat-kpi-value">{{ $companies->where('active', true)->count() }}</div>
        <div class="plat-kpi-sub">/ {{ $companies->count() }} toplam</div>
    </div>
</div>

<div class="plat-card">
    <h3 class="plat-card-title"><x-icon name="building-2" size="16" /> Şirket Başına</h3>

    @if($companies->isEmpty())
        <p style="margin:0;color:var(--plat-muted);">Şirket yok.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="plat-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Şirket</th>
                        <th style="text-align:right;">Öğrenci</th>
                        <th style="text-align:right;">Aday</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $c)
                        <tr>
                            <td>
                                <span style="font-weight:600;">{{ $c['name'] }}</span>
                                <span style="display:block;font-size:11px;color:var(--plat-muted);">
                                    #{{ $c['id'] }} · {{ $c['code'] }}@if($c['parent']) · üst firma #{{ $c['parent'] }}@endif
                                </span>
                            </td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:600;">
                                {{ number_format($c['students'], 0, ',', '.') }}
                            </td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">
                                {{ number_format($c['leads'], 0, ',', '.') }}
                            </td>
                            <td>
                                @if($c['active'])
                                    <span class="plat-badge plat-badge-active">Aktif</span>
                                @else
                                    <span class="plat-badge plat-badge-inactive">Pasif</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

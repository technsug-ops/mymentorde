@extends('manager.layouts.app')

@section('title', 'Finans Özeti — ' . config('brand.name', 'MentorDE'))
@section('page_title', 'Finans Özeti')
@section('page_subtitle', 'Sözleşme gelirleri + manuel kayıtlar · ' . $period)

@section('topbar-actions')
    <a href="{{ route('manager.finance.reports') }}" class="btn alt">📈 Raporlar & Projeksiyon</a>
    <a href="{{ route('manager.finance.entries') }}" class="btn alt">📒 Kayıtlar</a>
    <a href="{{ route('manager.finance.entries') }}?action=new" class="btn ok">+ Yeni Kayıt</a>
@endsection

@section('content')
@php
    $allCategories = \App\Models\CompanyFinanceEntry::allCategories();
    $thisMonthNet  = $thisMonthIncome - $thisMonthExpense;
    $yearNet       = $yearIncome - $yearExpense;
    $maxTrendVal   = $trend->max(fn($r) => max($r['income'], $r['expense'])) ?: 1;

    // Sözleşme kısayolları
    $crConfirmed = $cr['confirmedTotal'];
    $crPending   = $cr['pendingTotal'];
    $crRate      = $cr['collectionRate'];
@endphp

{{-- Dönem Seçici --}}
<div class="panel" style="margin-bottom:16px;padding:12px 16px;">
    <form method="GET" action="{{ route('manager.finance.dashboard') }}"
          style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <label style="font-size:13px;font-weight:600;color:var(--u-muted);">Dönem:</label>
        <select name="period" onchange="this.form.submit()"
                style="padding:6px 12px;border-radius:6px;font-size:13px;min-width:140px;">
            @foreach($periods as $p)
                <option value="{{ $p }}" @selected($p === $period)>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $p)->locale('tr')->isoFormat('MMMM YYYY') }}
                </option>
            @endforeach
        </select>
        <span style="font-size:12px;color:var(--u-muted);">Son 12 aylık görünüm</span>
    </form>
</div>

{{-- KPI Kartları --}}
<div class="grid4" style="margin-bottom:18px;">
    <div class="panel" style="border-left:4px solid var(--u-ok);">
        <div style="font-size:11px;color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Bu Ay Toplam Gelir</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-ok);">{{ number_format($thisMonthIncome, 0, ',', '.') }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">
            EUR · Sözleşme <strong style="color:var(--u-ok);">{{ number_format($contractThisMonth, 0, ',', '.') }}</strong>
            + Manuel <strong>{{ number_format($manualIncome, 0, ',', '.') }}</strong>
        </div>
    </div>
    <div class="panel" style="border-left:4px solid var(--u-danger);">
        <div style="font-size:11px;color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Bu Ay Gider</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-danger);">{{ number_format($thisMonthExpense, 0, ',', '.') }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">EUR · Manuel kayıtlar</div>
    </div>
    <div class="panel" style="border-left:4px solid {{ $thisMonthNet >= 0 ? 'var(--u-ok)' : 'var(--u-danger)' }};">
        <div style="font-size:11px;color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Bu Ay Net</div>
        <div style="font-size:26px;font-weight:800;color:{{ $thisMonthNet >= 0 ? 'var(--u-ok)' : 'var(--u-danger)' }};">
            {{ $thisMonthNet >= 0 ? '+' : '' }}{{ number_format($thisMonthNet, 0, ',', '.') }}
        </div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Gelir − Gider</div>
    </div>
    <div class="panel" style="border-left:4px solid {{ $crRate >= 80 ? 'var(--u-ok)' : ($crRate >= 50 ? 'var(--u-warn)' : 'var(--u-danger)') }};">
        <div style="font-size:11px;color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Tahsilat Oranı</div>
        <div style="font-size:26px;font-weight:800;color:{{ $crRate >= 80 ? 'var(--u-ok)' : ($crRate >= 50 ? 'var(--u-warn)' : 'var(--u-danger)') }};">
            %{{ number_format($crRate, 1) }}
        </div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">
            Onaylı <strong>{{ number_format($crConfirmed, 0, ',', '.') }}</strong> /
            Bekleyen <strong>{{ number_format($crPending, 0, ',', '.') }}</strong> EUR
        </div>
    </div>
</div>

{{-- Sözleşme Gelirleri (canlı) --}}
<div class="panel" style="margin-bottom:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
        <h3 style="margin:0;">📄 Sözleşme Gelirleri <span style="font-size:12px;font-weight:400;color:var(--u-muted);">— canlı, guest_applications'dan</span></h3>
        <a href="{{ route('manager.finance.entries') }}" style="font-size:13px;color:var(--u-brand);text-decoration:none;font-weight:600;">Manuel Kayıtlar →</a>
    </div>

    {{-- Tahsilat İlerleme Çubuğu --}}
    @php
        $totalPipeline = $crConfirmed + $crPending;
        $confirmedPct  = $totalPipeline > 0 ? round($crConfirmed / $totalPipeline * 100) : 0;
        $pendingPct    = $totalPipeline > 0 ? round($crPending   / $totalPipeline * 100) : 0;
    @endphp
    <div style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <span style="font-size:12px;color:var(--u-text);font-weight:600;">Tahsilat Pipeline</span>
            <span style="font-size:12px;color:var(--u-muted);">Toplam {{ number_format($totalPipeline, 0, ',', '.') }} EUR</span>
        </div>
        <div style="height:12px;background:var(--u-line);border-radius:999px;overflow:hidden;display:flex;">
            <div style="width:{{ $confirmedPct }}%;background:#16a34a;transition:width .4s;"
                 title="Tahsil Edildi: {{ number_format($crConfirmed,0,',','.') }} EUR"></div>
            <div style="width:{{ $pendingPct }}%;background:#f59e0b;transition:width .4s;"
                 title="Bekleyen: {{ number_format($crPending,0,',','.') }} EUR"></div>
        </div>
        <div style="display:flex;gap:16px;margin-top:6px;flex-wrap:wrap;">
            <span style="font-size:11px;color:#16a34a;font-weight:700;">
                ■ Tahsil Edildi {{ number_format($crConfirmed,0,',','.') }} EUR (%{{ $confirmedPct }})
            </span>
            <span style="font-size:11px;color:#f59e0b;font-weight:700;">
                ■ Bekleyen {{ number_format($crPending,0,',','.') }} EUR (%{{ $pendingPct }})
            </span>
            @if($cr['cancelledTotal'] > 0)
            <span style="font-size:11px;color:var(--u-danger);font-weight:700;">
                ■ İptal {{ number_format($cr['cancelledTotal'],0,',','.') }} EUR
            </span>
            @endif
        </div>
    </div>

    {{-- Durum Kırılımı + Son Sözleşmeler --}}
    <div class="grid2">
        {{-- Durum bazlı dağılım --}}
        <div>
            <div style="font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">Durum Dağılımı</div>
            @php
                $statusLabels = [
                    'signed'      => ['Onaylı (İmzalı)',  '#16a34a'],
                    'approved'    => ['Onaylı (Yönetici)','#0891b2'],
                    'requested'   => ['İmza Bekliyor',    '#f59e0b'],
                    'cancelled'   => ['İptal Edildi',     '#dc2626'],
                    'not_requested'=> ['Sözleşme Yok',   '#9ca3af'],
                ];
            @endphp
            @forelse($cr['statusBreakdown'] as $row)
            @php
                [$lbl,$color] = $statusLabels[$row->contract_status] ?? [$row->contract_status, '#6b7280'];
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--u-line);">
                <div style="display:flex;align-items:center;gap:7px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:{{ $color }};flex-shrink:0;"></div>
                    <span style="font-size:13px;color:var(--u-text);">{{ $lbl }}</span>
                    <span class="badge info" style="font-size:10px;">{{ $row->cnt }}</span>
                </div>
                <span style="font-size:13px;font-weight:700;color:{{ $color }};">
                    {{ number_format((float)$row->total, 0, ',', '.') }} EUR
                </span>
            </div>
            @empty
            <p style="font-size:13px;color:var(--u-muted);text-align:center;padding:20px 0;">Henüz sözleşme kaydı yok.</p>
            @endforelse
        </div>

        {{-- Son imzalanan sözleşmeler --}}
        <div>
            <div style="font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">Son Sözleşmeler</div>
            @forelse($cr['recentContracts'] as $c)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--u-line);gap:8px;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:var(--u-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $c->first_name }} {{ $c->last_name }}
                    </div>
                    <div style="font-size:11px;color:var(--u-muted);">
                        {{ $c->selected_package_title ?? '—' }} ·
                        {{ $c->contract_signed_at?->format('d.m.Y') ?? '—' }}
                    </div>
                </div>
                <span style="font-size:13px;font-weight:800;color:#16a34a;white-space:nowrap;">
                    +{{ number_format((float)$c->contract_amount_eur, 0, ',', '.') }} EUR
                </span>
            </div>
            @empty
            <p style="font-size:13px;color:var(--u-muted);text-align:center;padding:20px 0;">Onaylı sözleşme yok.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Trend + Gider Dağılımı --}}
<div class="grid2" style="margin-bottom:18px;">

    {{-- 12 Ay Trend --}}
    <div class="panel">
        <h3 style="margin-bottom:14px;">Son 12 Ay Trend</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="border-bottom:2px solid var(--u-line);">
                        <th style="text-align:left;padding:6px 8px;color:var(--u-muted);font-size:11px;font-weight:700;">AY</th>
                        <th style="text-align:right;padding:6px 8px;color:#16a34a;font-size:11px;font-weight:700;">GELİR</th>
                        <th style="text-align:right;padding:6px 8px;color:#7c3aed;font-size:11px;font-weight:700;">SÖZ.</th>
                        <th style="text-align:right;padding:6px 8px;color:var(--u-danger);font-size:11px;font-weight:700;">GİDER</th>
                        <th style="text-align:right;padding:6px 8px;color:var(--u-text);font-size:11px;font-weight:700;">NET</th>
                        <th style="padding:6px 8px;min-width:70px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trend as $row)
                    @php
                        $net = $row['income'] - $row['expense'];
                        $isCurrent = $row['period'] === $period;
                        $incPct = $maxTrendVal > 0 ? round(($row['income'] / $maxTrendVal) * 100) : 0;
                        $expPct = $maxTrendVal > 0 ? round(($row['expense'] / $maxTrendVal) * 100) : 0;
                    @endphp
                    <tr style="border-bottom:1px solid var(--u-line);{{ $isCurrent ? 'background:rgba(30,64,175,.04);' : '' }}">
                        <td style="padding:6px 8px;font-weight:{{ $isCurrent ? '700' : '500' }};color:var(--u-text);white-space:nowrap;">
                            @if($isCurrent)<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--u-brand);margin-right:5px;vertical-align:middle;"></span>@endif
                            {{ $row['label'] }}
                        </td>
                        <td style="padding:6px 8px;text-align:right;color:#16a34a;font-weight:600;">
                            {{ $row['income'] > 0 ? number_format($row['income'],0,',','.') : '—' }}
                        </td>
                        <td style="padding:6px 8px;text-align:right;color:#7c3aed;font-size:11px;">
                            {{ $row['contract_income'] > 0 ? number_format($row['contract_income'],0,',','.') : '—' }}
                        </td>
                        <td style="padding:6px 8px;text-align:right;color:var(--u-danger);font-weight:600;">
                            {{ $row['expense'] > 0 ? number_format($row['expense'],0,',','.') : '—' }}
                        </td>
                        <td style="padding:6px 8px;text-align:right;font-weight:700;color:{{ $net >= 0 ? '#16a34a' : 'var(--u-danger)' }};">
                            {{ $net >= 0 ? '+' : '' }}{{ number_format($net,0,',','.') }}
                        </td>
                        <td style="padding:6px 8px;">
                            <div style="display:flex;flex-direction:column;gap:2px;">
                                <div style="height:5px;border-radius:3px;background:#16a34a;opacity:.8;width:{{ $incPct }}%;min-width:{{ $row['income']>0 ? '2px' : '0' }};"></div>
                                <div style="height:5px;border-radius:3px;background:var(--u-danger);opacity:.8;width:{{ $expPct }}%;min-width:{{ $row['expense']>0 ? '2px' : '0' }};"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Yıl Geneli KPI --}}
        <div class="panel" style="padding:16px;">
            <div style="font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">{{ substr($period,0,4) }} Yılı Özet</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="background:color-mix(in srgb,var(--u-card) 85%,#16a34a);border-radius:8px;padding:10px 12px;">
                    <div style="font-size:11px;color:var(--u-muted);margin-bottom:3px;">Yıllık Gelir</div>
                    <div style="font-size:18px;font-weight:800;color:#16a34a;">{{ number_format($yearIncome,0,',','.') }}</div>
                    <div style="font-size:10px;color:var(--u-muted);margin-top:2px;">EUR</div>
                </div>
                <div style="background:color-mix(in srgb,var(--u-card) 85%,#dc2626);border-radius:8px;padding:10px 12px;">
                    <div style="font-size:11px;color:var(--u-muted);margin-bottom:3px;">Yıllık Gider</div>
                    <div style="font-size:18px;font-weight:800;color:var(--u-danger);">{{ number_format($yearExpense,0,',','.') }}</div>
                    <div style="font-size:10px;color:var(--u-muted);margin-top:2px;">EUR</div>
                </div>
                <div style="grid-column:1/-1;background:color-mix(in srgb,var(--u-card) 85%,{{ $yearNet >= 0 ? '#16a34a' : '#dc2626' }});border-radius:8px;padding:10px 12px;">
                    <div style="font-size:11px;color:var(--u-muted);margin-bottom:3px;">Yıllık Net Kâr/Zarar</div>
                    <div style="font-size:22px;font-weight:800;color:{{ $yearNet >= 0 ? '#16a34a' : 'var(--u-danger)' }};">
                        {{ $yearNet >= 0 ? '+' : '' }}{{ number_format($yearNet,0,',','.') }} EUR
                    </div>
                </div>
            </div>
        </div>

        {{-- Gider Kategorileri --}}
        <div class="panel" style="flex:1;">
            <h3 style="margin-bottom:14px;">Bu Ay Gider Dağılımı</h3>
            @if($expenseByCategory->isEmpty())
                <p style="color:var(--u-muted);font-size:13px;text-align:center;padding:16px 0;">Bu ay gider kaydı yok.</p>
            @else
            @php $totalExp = $expenseByCategory->sum(); @endphp
            @foreach($expenseByCategory->sortByDesc(fn($v)=>$v) as $cat => $total)
            @php
                $pct   = $totalExp > 0 ? round(($total/$totalExp)*100) : 0;
                $label = $allCategories[$cat] ?? $cat;
            @endphp
            <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                    <span style="font-size:12px;color:var(--u-text);font-weight:500;">{{ $label }}</span>
                    <span style="font-size:12px;color:var(--u-muted);">{{ number_format($total,0,',','.') }} ({{ $pct }}%)</span>
                </div>
                <div style="height:6px;border-radius:3px;background:var(--u-line);overflow:hidden;">
                    <div style="height:100%;border-radius:3px;background:var(--u-danger);opacity:.75;width:{{ $pct }}%;"></div>
                </div>
            </div>
            @endforeach
            @endif
        </div>

        {{-- Hızlı İşlemler --}}
        <div class="panel">
            <h3 style="margin-bottom:12px;">Hızlı İşlemler</h3>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('manager.finance.entries') }}?type=expense&month={{ $period }}" class="btn warn" style="justify-content:center;">+ Gider Ekle</a>
                <a href="{{ route('manager.finance.entries') }}?type=income&month={{ $period }}" class="btn ok" style="justify-content:center;">+ Manuel Gelir</a>
                <a href="{{ route('manager.finance.entries') }}?month={{ $period }}" class="btn alt" style="justify-content:center;">📒 Tüm Kayıtlar</a>
            </div>
        </div>
    </div>
</div>

{{-- Son Manuel Kayıtlar --}}
<div class="panel">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <h3 style="margin:0;">Son Manuel Kayıtlar</h3>
        <a href="{{ route('manager.finance.entries') }}" style="font-size:13px;color:var(--u-brand);text-decoration:none;font-weight:600;">Tümünü Gör →</a>
    </div>
    @if($recentEntries->isEmpty())
        <p style="color:var(--u-muted);font-size:13px;text-align:center;padding:24px 0;">Henüz manuel kayıt yok.</p>
    @else
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid var(--u-line);">
                <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">TARİH</th>
                <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">BAŞLIK</th>
                <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">KATEGORİ</th>
                <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">TÜR</th>
                <th style="text-align:right;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">TUTAR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentEntries as $entry)
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:8px 10px;color:var(--u-muted);white-space:nowrap;">{{ $entry->entry_date->format('d.m.Y') }}</td>
                <td style="padding:8px 10px;color:var(--u-text);font-weight:500;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $entry->title }}</td>
                <td style="padding:8px 10px;">
                    <span style="font-size:11px;padding:2px 8px;border-radius:999px;background:var(--u-line);color:var(--u-muted);font-weight:600;">
                        {{ $allCategories[$entry->category] ?? $entry->category }}
                    </span>
                </td>
                <td style="padding:8px 10px;">
                    <span class="badge {{ $entry->type==='income' ? 'ok' : 'danger' }}">{{ $entry->type==='income' ? 'Gelir' : 'Gider' }}</span>
                </td>
                <td style="padding:8px 10px;text-align:right;font-weight:700;color:{{ $entry->type==='income' ? '#16a34a' : 'var(--u-danger)' }};white-space:nowrap;">
                    {{ $entry->type==='income' ? '+' : '−' }}{{ number_format($entry->amount,2,',','.') }}
                    <span style="font-size:11px;font-weight:400;color:var(--u-muted);">{{ $entry->currency }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection

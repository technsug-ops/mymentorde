@extends('senior.layouts.app')
@section('title','Performans Metrikleri')
@section('page_title','Performans Metrikleri')

@push('head')
<style>
.perf-bar-track { height:7px; border-radius:999px; background:var(--u-line); overflow:hidden; margin-top:6px; }
.perf-bar-fill  { height:7px; border-radius:999px; background:linear-gradient(90deg,#7c3aed,#a78bfa); transition:width .4s; }
.perf-bar-fill.ok   { background:linear-gradient(90deg,#16a34a,#4ade80); }
.perf-bar-fill.warn { background:linear-gradient(90deg,#d97706,#fbbf24); }
.trend-row:hover { background:var(--u-bg) !important; }
</style>
@endpush

@section('content')

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">📊 Performans Metrikleri</div>
            <div style="font-size:var(--tx-sm);opacity:.8;">Eğitim Danışmanı raporu · dönüşüm, risk, süreç hızı</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="/senior/performance/report-print" target="_blank"
               style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);border-radius:8px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;color:#fff;text-decoration:none;">
                🖨 Yazdır / PDF
            </a>
            <a href="/senior/performance/report-csv"
               style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);border-radius:8px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;color:#fff;text-decoration:none;">
                📥 CSV İndir
            </a>
        </div>
    </div>

    {{-- KPI chips in header --}}
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
        @foreach([
            ['Aktif Öğrenci',      $activeStudents ?? 0,      '🎓'],
            ['Bu Ay Outcome',      $outcomeThisMonth ?? 0,    '🏁'],
            ['Belge Onay Bekliyor',$pendingDocApprovals ?? 0, '📄'],
            ['Randevu Bekliyor',   $pendingAppointments ?? 0, '📅'],
        ] as [$lbl,$val,$ic])
        <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:10px 16px;text-align:center;min-width:90px;">
            <div style="font-size:var(--tx-lg);">{{ $ic }}</div>
            <div style="font-size:var(--tx-xl);font-weight:800;line-height:1.1;margin:2px 0;">{{ $val }}</div>
            <div style="font-size:var(--tx-xs);opacity:.8;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">{{ $lbl }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- Aday Öğrenci Dönüşüm + Risk Dağılımı --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
    {{-- Dönüşüm --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;">
        <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">🔄 Aday Öğrenci Dönüşüm</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;">Toplam Aday Öğrenci</div>
                <div style="font-size:var(--tx-2xl);font-weight:800;color:var(--u-text);line-height:1.1;">{{ $guestCount ?? 0 }}</div>
            </div>
            <div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;">Öğrenci'ye Dönüşen</div>
                <div style="font-size:var(--tx-2xl);font-weight:800;color:#16a34a;line-height:1.1;">{{ $guestConverted ?? 0 }}</div>
            </div>
        </div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">Dönüşüm Oranı
            <strong style="color:var(--u-text);">%{{ $conversionRate ?? 0 }}</strong>
        </div>
        <div class="perf-bar-track"><div class="perf-bar-fill ok" style="width:{{ min(100,$conversionRate??0) }}%;"></div></div>
    </div>

    {{-- Risk --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;">
        <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">⚠️ Risk Dağılımı</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach([
                ['low',      'Düşük',  'ok',     '#16a34a'],
                ['medium',   'Orta',   'warn',   '#d97706'],
                ['high',     'Yüksek', 'danger', '#dc2626'],
                ['critical', 'Kritik', 'danger', '#991b1b'],
            ] as [$key,$label,$cls,$clr])
            @php $cnt = (int)($riskBreakdown[$key] ?? 0); $total = array_sum(array_values($riskBreakdown ?? [])); @endphp
            @if($cnt > 0 || $key === 'low')
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="badge {{ $cls }}" style="font-size:var(--tx-xs);min-width:52px;text-align:center;">{{ $label }}</span>
                <div style="flex:1;">
                    <div class="perf-bar-track" style="margin-top:0;">
                        <div style="height:7px;border-radius:999px;background:{{ $clr }};width:{{ $total > 0 ? min(100,round($cnt/$total*100)) : 0 }}%;transition:width .4s;"></div>
                    </div>
                </div>
                <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);min-width:28px;text-align:right;">{{ $cnt }}</span>
            </div>
            @endif
            @endforeach
            @if(array_sum(array_values($riskBreakdown ?? [])) === 0)
                <div style="font-size:var(--tx-sm);color:var(--u-muted);">Risk skoru atanmamış.</div>
            @endif
        </div>
    </div>
</div>

{{-- Dönüşüm Funnel --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;margin-bottom:12px;">
    <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;">🏗 Dönüşüm Funnel</div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
        @foreach([
            ['Toplam Lead',         $guestCount ?? 0,      null,           '#7c3aed'],
            ['Sözleşme İmzaladı',   $contractSigned ?? 0,  $guestCount,    '#6d28d9'],
            ["Öğrenci'ye Dönüşen",  $guestConverted ?? 0,  $guestCount,    '#16a34a'],
            ['Vize Onaylanan',       $visaApproved ?? 0,    $guestConverted,'#15803d'],
        ] as [$lbl,$val,$base,$clr])
        @php $pct = $base > 0 ? round($val/$base*100) : 0; @endphp
        <div style="text-align:center;padding:12px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;">
            <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;margin-bottom:6px;">{{ $lbl }}</div>
            <div style="font-size:var(--tx-2xl);font-weight:800;color:{{ $clr }};line-height:1;">{{ $val }}</div>
            @if($base !== null && $base > 0)
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;">%{{ $pct }}</div>
            @endif
        </div>
        @endforeach
    </div>
</div>

{{-- Üniversite Kabul + Süreç Hızı --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
    {{-- Üniversite --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;">
        <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">🏛 Üniversite Kabul Oranı</div>
        @if(($uniTotal ?? 0) > 0)
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:12px;">
            <div style="text-align:center;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;">Toplam</div>
                <div style="font-size:var(--tx-2xl);font-weight:800;color:var(--u-text);">{{ $uniTotal }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;">Kabul</div>
                <div style="font-size:var(--tx-2xl);font-weight:800;color:#16a34a;">{{ $uniAccepted ?? 0 }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;">Ret</div>
                <div style="font-size:var(--tx-2xl);font-weight:800;color:#dc2626;">{{ $uniRejected ?? 0 }}</div>
            </div>
        </div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">Kabul Oranı <strong style="color:var(--u-text);">%{{ $uniAcceptanceRate ?? 0 }}</strong></div>
        <div class="perf-bar-track"><div class="perf-bar-fill ok" style="width:{{ min(100,$uniAcceptanceRate??0) }}%;"></div></div>
        @else
        <div style="font-size:var(--tx-sm);color:var(--u-muted);margin-bottom:12px;">Henüz üniversite başvurusu kaydedilmemiş.</div>
        <a href="/senior/institution-documents" style="font-size:var(--tx-xs);padding:6px 14px;background:#7c3aed;color:#fff;border-radius:7px;text-decoration:none;font-weight:600;">Belge Takibine Git</a>
        @endif
    </div>

    {{-- Süreç Hızı --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;">
        <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">⏱ Süreç Hızı</div>
        @if($avgProcessDays !== null)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div style="text-align:center;padding:10px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;">Benim Ortalaması</div>
                <div style="font-size:var(--tx-2xl);font-weight:800;color:#7c3aed;">{{ $avgProcessDays }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">gün</div>
            </div>
            <div style="text-align:center;padding:10px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;">Sistem Ortalaması</div>
                <div style="font-size:var(--tx-2xl);font-weight:800;color:var(--u-muted);">{{ $systemAvgDays ?? '—' }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">gün</div>
            </div>
        </div>
        @if($systemAvgDays !== null)
            @php $diff = $avgProcessDays - $systemAvgDays; @endphp
            @if($diff < 0)
                <span class="badge ok">🚀 Sistem ortalamasından {{ abs($diff) }} gün hızlı</span>
            @elseif($diff > 0)
                <span class="badge warn">🐢 Sistem ortalamasından {{ $diff }} gün yavaş</span>
            @else
                <span class="badge info">Sistem ortalamasında</span>
            @endif
        @endif
        @else
        <div style="font-size:var(--tx-sm);color:var(--u-muted);">Henüz dönüştürülmüş öğrenci yok — süreç süresi hesaplanamıyor.</div>
        @endif
    </div>
</div>

{{-- Outcome Adım Dağılımı --}}
@if(($outcomeByStep ?? collect())->isNotEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;margin-bottom:12px;">
    <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">🏁 Outcome Adım Dağılımı</div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        @foreach($outcomeByStep as $row)
        @php $pct = $outcomeCount > 0 ? (int)round(($row->cnt/$outcomeCount)*100) : 0; @endphp
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:var(--tx-xs);font-weight:600;color:var(--u-text);min-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row->process_step }}</span>
            <div style="flex:1;" class="perf-bar-track" style="margin-top:0;">
                <div class="perf-bar-fill" style="width:{{ $pct }}%;margin-top:0;"></div>
            </div>
            <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-text);min-width:24px;text-align:right;">{{ $row->cnt }}</span>
            <span style="font-size:var(--tx-xs);color:var(--u-muted);min-width:30px;text-align:right;">%{{ $pct }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Hedef vs Gerçek --}}
@if(!empty($performanceTarget))
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:18px 20px;margin-bottom:12px;">
    <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;">🎯 {{ $currentPeriod ?? '' }} — Hedef vs Gerçek</div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
        @foreach([
            ['Dönüşüm',       'conversions'],
            ['Süreç Adımı',   'outcomes'],
            ['Belge İnceleme','doc_reviews'],
            ['Randevu',       'appointments'],
        ] as [$lbl,$key])
        @php
            $t = $performanceTarget[$key]['target'] ?? 0;
            $a = $performanceTarget[$key]['actual'] ?? 0;
            $pct = $t > 0 ? min(100,(int)round($a/$t*100)) : 0;
            $fillClass = $pct >= 100 ? 'ok' : ($pct >= 70 ? '' : 'warn');
        @endphp
        <div style="padding:12px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;">
            <div style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">{{ $lbl }}</div>
            <div style="display:flex;align-items:baseline;gap:5px;margin-bottom:8px;">
                <span style="font-size:var(--tx-2xl);font-weight:800;color:var(--u-text);line-height:1;">{{ $a }}</span>
                <span style="font-size:var(--tx-sm);color:var(--u-muted);">/ {{ $t }}</span>
                <span class="badge {{ $pct>=100?'ok':($pct>=70?'info':'warn') }}" style="font-size:var(--tx-xs);margin-left:auto;">%{{ $pct }}</span>
            </div>
            <div class="perf-bar-track"><div class="perf-bar-fill {{ $fillClass }}" style="width:{{ $pct }}%;"></div></div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- 6 Aylık Trend --}}
@if(!empty($performanceTrend) && count($performanceTrend) > 0)
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;overflow:hidden;margin-bottom:12px;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--u-line);">
        <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;">📈 6 Aylık Trend</div>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
        <thead>
            <tr style="background:var(--u-bg);">
                <th style="padding:9px 16px;text-align:left;font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;">Dönem</th>
                <th style="padding:9px 16px;text-align:center;font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;">Dönüşüm</th>
                <th style="padding:9px 16px;text-align:center;font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;">Süreç Adımı</th>
            </tr>
        </thead>
        <tbody>
            @foreach($performanceTrend as $row)
            <tr class="trend-row" style="border-top:1px solid var(--u-line);">
                <td style="padding:10px 16px;font-weight:600;color:var(--u-text);">{{ $row['label'] }}</td>
                <td style="padding:10px 16px;text-align:center;font-weight:700;color:#7c3aed;">{{ $row['conversions'] }}</td>
                <td style="padding:10px 16px;text-align:center;font-weight:700;color:var(--u-text);">{{ $row['outcomes'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Hızlı Erişim --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:16px 20px;">
    <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Hızlı Erişim</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach([
            ['Öğrenci Listesi',  '/senior/students'],
            ['Belge Onayı',      '/senior/registration-documents'],
            ['Süreç Takibi',     '/senior/process-tracking'],
            ['Randevular',       '/senior/appointments'],
        ] as [$lbl,$href])
        <a href="{{ $href }}" style="font-size:var(--tx-xs);padding:6px 14px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:7px;color:var(--u-text);text-decoration:none;font-weight:600;">{{ $lbl }}</a>
        @endforeach
    </div>
</div>

@endsection

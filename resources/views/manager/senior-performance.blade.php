@extends('manager.layouts.app')
@section('title', 'Danışman Performans')
@section('page_title', 'Danışman Performans')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;">👤 Danışman Performans Panosu</h1>
        <div class="u-muted" style="font-size:var(--tx-sm);">Her danışmanın lead dönüşümü, öğrenci memnuniyeti, görev tamamlama ve gelir katkısı</div>
    </div>
</div>

<form method="GET" class="card" style="margin-bottom:20px;display:flex;gap:10px;align-items:end;flex-wrap:wrap;padding:14px;">
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">BAŞLANGIÇ</label>
        <input type="date" name="start_date" value="{{ $filters['start_date'] }}" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">BİTİŞ</label>
        <input type="date" name="end_date" value="{{ $filters['end_date'] }}" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
    </div>
    <button class="btn" type="submit" style="padding:8px 16px;">🔍 Uygula</button>
    <a href="{{ route('manager.senior-performance') }}" class="u-muted" style="font-size:12px;text-decoration:none;margin-left:6px;">Sıfırla</a>
</form>

{{-- KPI Cards --}}
<div class="grid4" style="display:grid;grid-template-columns:repeat(4, 1fr);gap:12px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">AKTİF DANIŞMAN</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;">{{ $totalSeniors }}</div>
        <div class="u-muted" style="font-size:11px;">Toplam personel</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">ORT. DÖNÜŞÜM ORANI</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:{{ $avgConvPct >= 20 ? '#16a34a' : ($avgConvPct >= 10 ? '#f59e0b' : '#dc2626') }};">%{{ $avgConvPct }}</div>
        <div class="u-muted" style="font-size:11px;">Danışman ortalaması</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">TOPLAM GELİR</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:#0891b2;">€ {{ number_format($totalRevenue, 0, ',', '.') }}</div>
        <div class="u-muted" style="font-size:11px;">Bu cohort'tan</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">🏆 EN İYİ PERFORMANS</div>
        @if($topPerformer)
        <div style="font-size:16px;font-weight:700;margin-top:6px;">{{ $topPerformer['name'] }}</div>
        <div class="u-muted" style="font-size:11px;">Skor: <strong>{{ $topPerformer['score'] }}</strong> · %{{ $topPerformer['convPct'] }} dönüşüm</div>
        @else
        <div class="u-muted" style="margin-top:6px;">Veri yok</div>
        @endif
    </div>
</div>

{{-- Leaderboard --}}
<div class="card" style="padding:0;margin-bottom:20px;overflow:hidden;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--u-line);">
        <div class="card-title" style="font-weight:700;">🏅 Performans Tablosu (skor sırasına göre)</div>
        <div class="u-muted" style="font-size:12px;">Skor = %40 dönüşüm + %30 memnuniyet × 20 + %30 görev tamamlama</div>
    </div>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead style="background:rgba(0,0,0,.03);">
            <tr>
                <th style="text-align:left;padding:10px 12px;font-weight:700;">Danışman</th>
                <th style="text-align:right;padding:10px 12px;font-weight:700;">Skor</th>
                <th style="text-align:right;padding:10px 12px;font-weight:700;">Lead</th>
                <th style="text-align:right;padding:10px 12px;font-weight:700;">Dönüşüm</th>
                <th style="text-align:right;padding:10px 12px;font-weight:700;">Aktif Öğrenci</th>
                <th style="text-align:right;padding:10px 12px;font-weight:700;">Gelir</th>
                <th style="text-align:right;padding:10px 12px;font-weight:700;">Memnuniyet</th>
                <th style="text-align:right;padding:10px 12px;font-weight:700;">Görevler</th>
                <th style="text-align:right;padding:10px 12px;font-weight:700;">Ort. Süre</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $idx => $r)
            <tr style="border-top:1px solid rgba(0,0,0,.06);">
                <td style="padding:12px;">
                    <div style="font-weight:600;">
                        @if($idx === 0) 🥇 @elseif($idx === 1) 🥈 @elseif($idx === 2) 🥉 @endif
                        {{ $r['name'] }}
                    </div>
                    <div class="u-muted" style="font-size:11px;">{{ $r['email'] }}</div>
                </td>
                <td style="padding:12px;text-align:right;">
                    <div style="font-weight:700;font-size:16px;color:{{ $r['score'] >= 60 ? '#16a34a' : ($r['score'] >= 30 ? '#f59e0b' : '#dc2626') }};">
                        {{ $r['score'] }}
                    </div>
                </td>
                <td style="padding:12px;text-align:right;">{{ $r['leadCount'] }}</td>
                <td style="padding:12px;text-align:right;">
                    <div><strong>{{ $r['converted'] }}</strong></div>
                    <div class="u-muted" style="font-size:11px;">%{{ $r['convPct'] }}</div>
                </td>
                <td style="padding:12px;text-align:right;">{{ $r['activeStudents'] }}</td>
                <td style="padding:12px;text-align:right;color:#0891b2;font-weight:600;">€ {{ number_format($r['revenue'], 0, ',', '.') }}</td>
                <td style="padding:12px;text-align:right;">
                    @if($r['feedbackRating'] > 0)
                    <div>⭐ {{ number_format($r['feedbackRating'], 2, ',', '') }}</div>
                    <div class="u-muted" style="font-size:11px;">{{ $r['feedbackCount'] }} yanıt</div>
                    @else
                    <span class="u-muted">—</span>
                    @endif
                </td>
                <td style="padding:12px;text-align:right;">
                    <div><strong>{{ $r['tasksDone'] }}</strong> / {{ $r['tasksTotal'] }}</div>
                    @if($r['tasksOverdue'] > 0)
                    <div style="color:#dc2626;font-size:11px;font-weight:600;">⚠️ {{ $r['tasksOverdue'] }} gecikmiş</div>
                    @else
                    <div class="u-muted" style="font-size:11px;">%{{ $r['taskCompletionPct'] }} tamam</div>
                    @endif
                </td>
                <td style="padding:12px;text-align:right;">
                    @if($r['avgDaysToConvert'] > 0)
                    <div>{{ $r['avgDaysToConvert'] }}</div>
                    <div class="u-muted" style="font-size:11px;">gün</div>
                    @else
                    <span class="u-muted">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="9" style="padding:20px;text-align:center;" class="u-muted">Bu aralıkta danışman verisi yok.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .grid4 { grid-template-columns: repeat(2, 1fr) !important; }
}
</style>
@endsection

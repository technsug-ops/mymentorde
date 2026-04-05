@extends('manager.layouts.app')

@section('title', 'Devam Raporu')
@section('page_title', 'HR Devam Raporu')
@section('page_subtitle', 'Aylık personel devam analizi')

@section('content')
<div style="display:grid;gap:14px;">

    {{-- Ay Seçici --}}
    <div class="panel">
        <form method="GET" action="/manager/hr/attendance" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;font-weight:600;color:var(--u-muted);">Ay</label>
                <input type="month" name="month" value="{{ $month }}"
                       style="height:34px;padding:0 10px;border-radius:8px;font-size:13px;min-width:160px;">
            </div>
            <button type="submit" class="btn" style="height:34px;padding:0 18px;font-size:13px;">Görüntüle</button>
        </form>
    </div>

    {{-- KPI Kartları --}}
    <div class="grid4">
        <div class="panel" style="text-align:center;">
            <div style="font-size:26px;font-weight:800;color:var(--u-brand);">{{ number_format($stats['total_days']) }}</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">Toplam Kayıt</div>
        </div>
        <div class="panel" style="text-align:center;">
            <div style="font-size:26px;font-weight:800;color:var(--u-warn);">{{ number_format($stats['late_count']) }}</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">Geç Geliş</div>
        </div>
        <div class="panel" style="text-align:center;">
            <div style="font-size:26px;font-weight:800;color:var(--u-danger);">{{ number_format($stats['absent_count']) }}</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">Devamsızlık</div>
        </div>
        <div class="panel" style="text-align:center;">
            <div style="font-size:26px;font-weight:800;color:var(--u-ok);">{{ round($stats['avg_minutes'] / 60, 1) }}s</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">Ort. Çalışma</div>
        </div>
    </div>

    {{-- Kullanıcı Bazlı Tablo --}}
    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:14px 16px 10px;font-size:14px;font-weight:700;border-bottom:1px solid var(--u-line);">
            Personel Devam Özeti — {{ $month }}
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:color-mix(in srgb,var(--u-brand) 4%,var(--u-card));">
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Personel</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:center;border-bottom:1px solid var(--u-line);">Toplam Gün</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:center;border-bottom:1px solid var(--u-line);">Geç</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:center;border-bottom:1px solid var(--u-line);">Erken Çıkış</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:center;border-bottom:1px solid var(--u-line);">Devamsız</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:center;border-bottom:1px solid var(--u-line);">Ort. Dakika</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byUser as $userId => $userRecords)
                    @php
                        $usr         = $userRecords->first()->user;
                        $totalDays   = $userRecords->count();
                        $lateCount   = $userRecords->where('status', 'late')->count();
                        $earlyCount  = $userRecords->where('status', 'early_leave')->count();
                        $absentCount = $userRecords->where('status', 'absent')->count();
                        $avgMinutes  = $userRecords->where('work_minutes', '>', 0)->avg('work_minutes') ?? 0;
                    @endphp
                    <tr style="border-bottom:1px solid var(--u-line);">
                        <td style="padding:10px 14px;">
                            <div style="font-size:13px;font-weight:600;">{{ $usr?->name ?? 'Bilinmiyor' }}</div>
                            <div style="font-size:11px;color:var(--u-muted);">{{ $usr?->email ?? '' }}</div>
                        </td>
                        <td style="padding:10px 14px;text-align:center;font-size:14px;font-weight:700;color:var(--u-brand);">{{ $totalDays }}</td>
                        <td style="padding:10px 14px;text-align:center;">
                            @if($lateCount > 0)
                                <span class="badge warn">{{ $lateCount }}</span>
                            @else
                                <span style="color:var(--u-muted);font-size:13px;">0</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px;text-align:center;">
                            @if($earlyCount > 0)
                                <span class="badge warn">{{ $earlyCount }}</span>
                            @else
                                <span style="color:var(--u-muted);font-size:13px;">0</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px;text-align:center;">
                            @if($absentCount > 0)
                                <span class="badge danger">{{ $absentCount }}</span>
                            @else
                                <span style="color:var(--u-muted);font-size:13px;">0</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px;text-align:center;font-size:13px;">
                            {{ $avgMinutes > 0 ? round($avgMinutes) . ' dk' : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:32px;text-align:center;color:var(--u-muted);font-size:13px;">
                            Bu ay için devam kaydı bulunamadı.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

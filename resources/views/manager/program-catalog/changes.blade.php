@extends('manager.layouts.app')

@section('title', 'Program Kataloğu — Değişiklik Takibi')
@section('page_title', '📚 Program Kataloğu Değişiklik Takibi')
@section('page_subtitle', 'Kaynak (Expatrio/HK) sync\'lerinde tespit edilen değişiklikleri review et — kabul, reddet, manuel override veya yoksay')

@push('head')
<style>
.pcc-wrap { max-width: 1200px; margin: 0 auto; }

/* Stats grid */
.pcc-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 22px; }
.pcc-stat-card {
    background: var(--u-card); border: 1px solid var(--u-line); border-radius: 10px;
    padding: 14px 18px;
}
.pcc-stat-num { font-size: 28px; font-weight: 800; color: #7e58bf; letter-spacing: -0.5px; }
.pcc-stat-num.crit { color: #dc2626; }
.pcc-stat-num.warn { color: #d97706; }
.pcc-stat-num.ok { color: #16a34a; }
.pcc-stat-label { font-size: 12px; color: var(--u-muted); margin-top: 2px; }

/* Filter bar */
.pcc-filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.pcc-filter-pill {
    padding: 6px 14px; background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 999px; text-decoration: none; font-size: 12.5px; font-weight: 600; color: var(--u-text);
}
.pcc-filter-pill.active { background: #7e58bf; color: #fff; border-color: #7e58bf; }

/* Log table */
.pcc-table { width: 100%; border-collapse: collapse; background: var(--u-card); border: 1px solid var(--u-line); border-radius: 10px; overflow: hidden; }
.pcc-table th, .pcc-table td { padding: 11px 14px; text-align: left; border-bottom: 1px solid var(--u-line); font-size: 12.5px; vertical-align: top; }
.pcc-table th { background: var(--u-bg); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--u-muted); }
.pcc-table tr:last-child td { border-bottom: none; }
.pcc-table .field-changed { font-family: monospace; font-size: 11.5px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }

.pcc-sev { padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
.pcc-sev-info { background: rgba(59,130,246,.15); color: #1d4ed8; }
.pcc-sev-warning { background: rgba(217,119,6,.15); color: #b45309; }
.pcc-sev-critical { background: rgba(220,38,38,.15); color: #b91c1c; }

.pcc-diff { font-family: monospace; font-size: 11px; }
.pcc-diff .old { color: #b91c1c; text-decoration: line-through; }
.pcc-diff .new { color: #15803d; font-weight: 600; }

.pcc-action-form { display: inline-flex; gap: 4px; }
.pcc-action-btn { padding: 4px 10px; border: none; border-radius: 5px; font-size: 11px; font-weight: 700; cursor: pointer; }
.pcc-action-btn.accept { background: #16a34a; color: #fff; }
.pcc-action-btn.reject { background: #dc2626; color: #fff; }
.pcc-action-btn.override { background: #7e58bf; color: #fff; }
.pcc-action-btn.ignore { background: var(--u-line); color: var(--u-text); }

.pcc-empty { text-align: center; padding: 60px 20px; color: var(--u-muted); }

.pcc-info-banner {
    background: linear-gradient(135deg, rgba(126,88,191,.06), rgba(126,88,191,.02));
    border: 1px solid rgba(126,88,191,.3); border-radius: 10px;
    padding: 12px 16px; margin-bottom: 16px; font-size: 12.5px; color: var(--u-text); line-height: 1.55;
}
</style>
@endpush

@section('content')
<div class="pcc-wrap">

    @if(session('success'))<div style="background:rgba(22,163,74,.08); color:#15803d; border:1px solid rgba(22,163,74,.3); padding:10px 14px; border-radius:10px; margin-bottom:14px;">✅ {{ session('success') }}</div>@endif

    <div class="pcc-info-banner">
        <strong>📚 Canonical Program Kataloğu:</strong> Almanya program verileri Expatrio/Hochschulkompass kaynaklarından senkronize edilir. Kaynaklarda değişiklik olursa <strong>buradan</strong> bildirim alırsınız. <em>Manuel Override</em> ile kayıtları kaynak güncellemelerinden korumak mümkün.
    </div>

    {{-- Stats --}}
    <div class="pcc-stats">
        <div class="pcc-stat-card">
            <div class="pcc-stat-num">{{ number_format($stats['total_programs']) }}</div>
            <div class="pcc-stat-label">Canonical Program</div>
        </div>
        <div class="pcc-stat-card">
            <div class="pcc-stat-num">{{ number_format($stats['total_source_links']) }}</div>
            <div class="pcc-stat-label">Kaynak Linki</div>
        </div>
        <div class="pcc-stat-card">
            <div class="pcc-stat-num crit">{{ $stats['critical_unreviewed'] }}</div>
            <div class="pcc-stat-label">Critical (review bekliyor)</div>
        </div>
        <div class="pcc-stat-card">
            <div class="pcc-stat-num warn">{{ $stats['warning_unreviewed'] }}</div>
            <div class="pcc-stat-label">Warning (review bekliyor)</div>
        </div>
        <div class="pcc-stat-card">
            <div class="pcc-stat-num">{{ $stats['total_unreviewed'] }}</div>
            <div class="pcc-stat-label">Toplam Review Bekliyor</div>
        </div>
        <div class="pcc-stat-card">
            <div class="pcc-stat-num ok">{{ $stats['recent_7days'] }}</div>
            <div class="pcc-stat-label">Son 7 günde tespit</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="pcc-filters">
        <a href="{{ route('manager.program-catalog.changes') }}" class="pcc-filter-pill {{ ! $severity && $reviewed === 'unreviewed' ? 'active' : '' }}">Bekleyen</a>
        <a href="{{ route('manager.program-catalog.changes', ['severity' => 'critical']) }}" class="pcc-filter-pill {{ $severity === 'critical' ? 'active' : '' }}">🔴 Critical</a>
        <a href="{{ route('manager.program-catalog.changes', ['severity' => 'warning']) }}" class="pcc-filter-pill {{ $severity === 'warning' ? 'active' : '' }}">🟡 Warning</a>
        <a href="{{ route('manager.program-catalog.changes', ['severity' => 'info']) }}" class="pcc-filter-pill {{ $severity === 'info' ? 'active' : '' }}">🔵 Info</a>
        <a href="{{ route('manager.program-catalog.changes', ['reviewed' => 'reviewed']) }}" class="pcc-filter-pill {{ $reviewed === 'reviewed' ? 'active' : '' }}">✅ Review Edilmiş</a>
        <a href="{{ route('manager.program-catalog.changes', ['reviewed' => 'all']) }}" class="pcc-filter-pill {{ $reviewed === 'all' ? 'active' : '' }}">Hepsi</a>
    </div>

    {{-- Logs table --}}
    @if($logs->isEmpty())
        <div class="pcc-empty">
            <h3 style="margin:0 0 8px;">🎉 Bekleyen değişiklik yok</h3>
            <p style="margin:0;">Kaynak senkronlarında yeni değişiklik tespit edilmedi.</p>
        </div>
    @else
        <table class="pcc-table">
            <thead>
                <tr>
                    <th style="width:90px;">Severity</th>
                    <th>Program</th>
                    <th>Field</th>
                    <th>Değişim</th>
                    <th style="width:90px;">Kaynak</th>
                    <th style="width:130px;">Tespit</th>
                    <th style="width:240px;">Aksiyon</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td><span class="pcc-sev pcc-sev-{{ $log->severity }}">{{ $log->severity }}</span></td>
                        <td>
                            @if($log->program)
                                <strong>{{ $log->program->course_name }}</strong><br>
                                <small style="color:var(--u-muted);">{{ $log->program->university_name_cached }}</small>
                            @else
                                <em>(silinmiş program)</em>
                            @endif
                        </td>
                        <td><span class="field-changed">{{ $log->field_changed }}</span></td>
                        <td>
                            <div class="pcc-diff">
                                <span class="old">{{ json_encode($log->old_value, JSON_UNESCAPED_UNICODE) }}</span><br>
                                <span class="new">{{ json_encode($log->new_value, JSON_UNESCAPED_UNICODE) }}</span>
                            </div>
                        </td>
                        <td>{{ $log->source }}</td>
                        <td><small>{{ $log->detected_at?->diffForHumans() }}</small></td>
                        <td>
                            @if($log->reviewed_at)
                                <small style="color:#15803d;">✓ {{ $log->reviewer_action }}</small>
                                @if($log->reviewer_note)<br><small style="color:var(--u-muted);">{{ $log->reviewer_note }}</small>@endif
                            @else
                                <form method="POST" action="{{ route('manager.program-catalog.changes.review', $log) }}" class="pcc-action-form">
                                    @csrf
                                    <button type="submit" name="action" value="accepted" class="pcc-action-btn accept" title="Kabul et">✓</button>
                                    <button type="submit" name="action" value="rejected" class="pcc-action-btn reject" title="Reddet">✗</button>
                                    <button type="submit" name="action" value="manual_override" class="pcc-action-btn override" title="Manuel override (bu programa re-sync dokunmasın)">🔒</button>
                                    <button type="submit" name="action" value="ignored" class="pcc-action-btn ignore" title="Yoksay">—</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:16px;">{{ $logs->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

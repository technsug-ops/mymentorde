@extends('marketing-admin.layouts.app')

@section('title', 'Bildirimler')

@section('page_subtitle', 'Bildirim Merkezi — kuyruk yönetimi ve gönderim takibi')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.nt-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.nt-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); }
.nt-stat:last-child { border-right:none; }
.nt-val   { font-size:22px; font-weight:700; line-height:1.1; }
.nt-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; min-width:1080px; border-collapse:collapse; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }
.mono { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
</style>

<div style="display:grid;gap:12px;">

    @if(session('status'))
    <div style="border:1px solid var(--u-ok,#16a34a);background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,var(--u-card,#fff));color:var(--u-ok,#16a34a);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        {{ session('status') }}
    </div>
    @endif

    {{-- KPI Bar --}}
    <div class="nt-stats">
        <div class="nt-stat">
            <div class="nt-val" style="color:var(--u-brand,#1e40af);">{{ $stats['queued'] ?? 0 }}</div>
            <div class="nt-lbl">Queued</div>
        </div>
        <div class="nt-stat">
            <div class="nt-val" style="color:var(--u-danger,#dc2626);">{{ $stats['failed'] ?? 0 }}</div>
            <div class="nt-lbl">Failed</div>
        </div>
        <div class="nt-stat">
            <div class="nt-val" style="color:var(--u-ok,#16a34a);">{{ $stats['sent_24h'] ?? 0 }}</div>
            <div class="nt-lbl">Sent (24h)</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;">

            {{-- Filtre --}}
            <form method="GET" action="/mktg-admin/notifications" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                <select name="status" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-xs);outline:none;appearance:auto;">
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tüm durumlar</option>
                    <option value="queued" @selected(($filters['status'] ?? 'all') === 'queued')>Kuyrukta</option>
                    <option value="sent" @selected(($filters['status'] ?? 'all') === 'sent')>Gönderildi</option>
                    <option value="failed" @selected(($filters['status'] ?? 'all') === 'failed')>Başarısız</option>
                </select>
                <select name="channel" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-xs);outline:none;appearance:auto;">
                    <option value="all" @selected(($filters['channel'] ?? 'all') === 'all')>Tüm kanallar</option>
                    <option value="email" @selected(($filters['channel'] ?? 'all') === 'email')>email</option>
                    <option value="whatsapp" @selected(($filters['channel'] ?? 'all') === 'whatsapp')>whatsapp</option>
                    <option value="in_app" @selected(($filters['channel'] ?? 'all') === 'in_app')>in_app</option>
                    <option value="inApp" @selected(($filters['channel'] ?? 'all') === 'inApp')>inApp</option>
                </select>
                <input name="student_id" value="{{ $filters['student_id'] ?? '' }}" placeholder="student id"
                    style="height:34px;padding:0 10px;width:120px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-xs);outline:none;">
                <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                <a href="/mktg-admin/notifications" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;">Temizle</a>
            </form>

            {{-- Dispatch & Retry --}}
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                <form method="POST" action="/mktg-admin/notifications/dispatch-now" style="display:flex;gap:6px;align-items:center;">
                    @csrf
                    <input type="number" name="limit" min="1" max="500" value="100"
                        style="height:34px;padding:0 10px;width:80px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-xs);outline:none;text-align:center;">
                    <button type="submit" class="btn ok" style="height:34px;font-size:var(--tx-xs);padding:0 12px;">Dispatch Şimdi</button>
                </form>
                <form method="POST" action="/mktg-admin/notifications/retry-failed" style="display:flex;gap:6px;align-items:center;">
                    @csrf
                    <input type="number" name="limit" min="1" max="500" value="100"
                        style="height:34px;padding:0 10px;width:80px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-xs);outline:none;text-align:center;">
                    <button type="submit" class="btn warn" style="height:34px;font-size:var(--tx-xs);padding:0 12px;">Failed Retry</button>
                </form>
            </div>

        </div>
    </div>

    {{-- Tablo --}}
    <div class="card">
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:60px;">ID</th>
                    <th>Channel / Category</th>
                    <th>Student</th>
                    <th>Recipient</th>
                    <th style="width:90px;">Durum</th>
                    <th>Queue Time</th>
                    <th>Reason</th>
                    <th style="width:130px;">Aksiyon</th>
                </tr></thead>
                <tbody>
                    @forelse(($rows ?? []) as $row)
                    @php
                        $statusBadge = match($row->status) {
                            'sent'   => 'ok',
                            'failed' => 'danger',
                            default  => 'pending',
                        };
                        $statusLabel = ['sent' => 'Gönderildi', 'failed' => 'Başarısız', 'queued' => 'Kuyrukta'][$row->status] ?? ucfirst($row->status);
                    @endphp
                    <tr>
                        <td class="mono" style="color:var(--u-muted,#64748b);">#{{ $row->id }}</td>
                        <td>
                            <strong>{{ $row->channel }}</strong>
                            <span style="color:var(--u-muted,#64748b);"> / {{ $row->category }}</span>
                        </td>
                        <td class="mono" style="color:var(--u-muted,#64748b);">{{ $row->student_id ?: '—' }}</td>
                        <td>
                            <div>{{ $row->recipient_email ?: '—' }}</div>
                            <div style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">{{ $row->recipient_phone ?: '—' }}</div>
                        </td>
                        <td><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                            <div>q: {{ $row->queued_at ?: '—' }}</div>
                            <div>s: {{ $row->sent_at ?: '—' }}</div>
                            <div>f: {{ $row->failed_at ?: '—' }}</div>
                        </td>
                        <td style="font-size:var(--tx-xs);max-width:160px;">{{ $row->fail_reason ?: '—' }}</td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                <form method="POST" action="/mktg-admin/notifications/{{ $row->id }}/mark-sent" style="display:contents;">
                                    @csrf
                                    <button type="submit" class="btn ok" style="font-size:var(--tx-xs);padding:3px 8px;">Sent</button>
                                </form>
                                <form method="POST" action="/mktg-admin/notifications/{{ $row->id }}/mark-failed" style="display:contents;">
                                    @csrf
                                    <input type="hidden" name="reason" value="manual-fail-mark">
                                    <button type="submit" class="btn warn" style="font-size:var(--tx-xs);padding:3px 8px;">Failed</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Bildirim kaydı yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $rows->links() }}</div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Bildirim Merkezi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Filtrelerle queue/sent/failed durumlarını daraltıp backlog'u izle.</li>
            <li><strong>Dispatch Şimdi</strong> — kuyruktaki kayıtları anında işlemeye zorlar.</li>
            <li><strong>Failed Retry</strong> — başarısız kayıtları yeniden queue'a alır.</li>
            <li>Satır aksiyonlarından tek tek <strong>Sent</strong> veya <strong>Failed</strong> işaretleyebilirsin.</li>
        </ol>
    </details>

</div>
@endsection

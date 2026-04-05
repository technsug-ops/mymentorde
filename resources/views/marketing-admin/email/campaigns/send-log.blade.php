@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn alt" href="/mktg-admin/email/templates" style="font-size:var(--tx-xs);padding:6px 12px;">Templates</a>
<a class="btn alt" href="/mktg-admin/email/segments" style="font-size:var(--tx-xs);padding:6px 12px;">Segments</a>
<a class="btn alt" href="/mktg-admin/email/campaigns" style="font-size:var(--tx-xs);padding:6px 12px;">Campaigns</a>
<a class="btn {{ request()->is('mktg-admin/email/log*') ? '' : 'alt' }}" href="/mktg-admin/email/log" style="font-size:var(--tx-xs);padding:6px 12px;">Send Log</a>
@endsection

@section('title', 'E-posta Send Log')
@section('page_subtitle', 'Her e-postanın gönderim geçmişi, açılma ve tıklama takibi')

@section('content')
<style>
.pl-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.pl-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.pl-stat:last-child { border-right:none; }
.pl-val   { font-size:22px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.pl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:860px; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); white-space:nowrap; }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }

details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }
</style>

<div style="display:grid;gap:12px;">

    {{-- KPI Bar --}}
    @php
        $logTotal    = $rows->total() ?? 0;
        $logSent     = $rows->getCollection()->where('status','sent')->count();
        $logFailed   = $rows->getCollection()->where('status','failed')->count();
        $logBounced  = $rows->getCollection()->where('status','bounced')->count();
        $logOpened   = $rows->getCollection()->whereNotNull('opened_at')->count();
        $openRate    = $logSent > 0 ? round($logOpened / max($logSent,1) * 100, 1) : 0;
    @endphp
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ $logTotal }}</div>
            <div class="pl-lbl">Toplam Kayıt</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-ok,#16a34a);">{{ $logSent }}</div>
            <div class="pl-lbl">Gönderildi</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-danger,#dc2626);">{{ $logFailed }}</div>
            <div class="pl-lbl">Başarısız</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-warn,#d97706);">{{ $logBounced }}</div>
            <div class="pl-lbl">Bounced</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ $logOpened }}</div>
            <div class="pl-lbl">Açıldı (bu sayfa)</div>
        </div>
    </div>

    {{-- Filtre + Tablo --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);flex-wrap:wrap;">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Gönderim Geçmişi</div>
            <form method="GET" action="/mktg-admin/email/log" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="e-posta / konu ara"
                    style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;min-width:160px;">
                <select name="campaign_id" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                    <option value="0">Tüm kampanyalar</option>
                    @foreach(($campaignOptions ?? []) as $c)
                    <option value="{{ $c->id }}" @selected((int)($filters['campaign_id']??0)===(int)$c->id)>#{{ $c->id }} {{ $c->name }}</option>
                    @endforeach
                </select>
                <select name="status" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                    <option value="all"     @selected(($filters['status']??'all')==='all')>Tüm durumlar</option>
                    <option value="sent"    @selected(($filters['status']??'all')==='sent')>Gönderildi</option>
                    <option value="failed"  @selected(($filters['status']??'all')==='failed')>Başarısız</option>
                    <option value="bounced" @selected(($filters['status']??'all')==='bounced')>Bounced</option>
                </select>
                <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                <a href="/mktg-admin/email/log" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;color:var(--u-muted,#64748b);">Temizle</a>
            </form>
        </div>

        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:40px;">ID</th>
                    <th>Kampanya</th>
                    <th>Template</th>
                    <th>Alıcı</th>
                    <th>Konu</th>
                    <th style="width:80px;">Durum</th>
                    <th style="width:130px;">Gönderildi</th>
                    <th style="width:60px;text-align:center;">Açıldı</th>
                    <th style="width:60px;text-align:center;">Tıklandı</th>
                </tr></thead>
                <tbody>
                    @forelse(($rows ?? []) as $row)
                    @php
                        $sBadge = match($row->status) {
                            'sent'    => 'ok',
                            'failed'  => 'danger',
                            'bounced' => 'warn',
                            default   => '',
                        };
                        $sLabel = match($row->status) {
                            'sent'    => 'Gönderildi',
                            'failed'  => 'Başarısız',
                            'bounced' => 'Bounced',
                            default   => $row->status,
                        };
                    @endphp
                    <tr>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);font-family:ui-monospace,monospace;">#{{ $row->id }}</td>
                        <td style="font-size:var(--tx-xs);">{{ $row->campaign->name ?? '—' }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->template->name ?? '—' }}</td>
                        <td style="font-size:var(--tx-xs);">{{ $row->recipient_email }}</td>
                        <td style="font-size:var(--tx-xs);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row->subject }}</td>
                        <td><span class="badge {{ $sBadge }}" style="font-size:var(--tx-xs);">{{ $sLabel }}</span></td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                            {{ $row->sent_at ? \Illuminate\Support\Carbon::parse($row->sent_at)->format('d.m.Y H:i') : '—' }}
                        </td>
                        <td style="text-align:center;">
                            @if($row->opened_at)
                                <span class="badge ok" style="font-size:var(--tx-xs);" title="{{ \Illuminate\Support\Carbon::parse($row->opened_at)->format('d.m.Y H:i') }}">✓</span>
                            @else
                                <span style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($row->clicked_at)
                                <span class="badge ok" style="font-size:var(--tx-xs);" title="{{ \Illuminate\Support\Carbon::parse($row->clicked_at)->format('d.m.Y H:i') }}">✓</span>
                            @else
                                <span style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" style="text-align:center;padding:28px;color:var(--u-muted,#64748b);">Send log kaydı yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $rows->links() }}</div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — E-posta Gönderim Logu</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Campaign filtresi ile belirli bir gönderimi izole et.</li>
            <li>Status filtresi ile hatalı (failed/bounced) kayıtları hızlı tespit et.</li>
            <li>Açıldı/Tıklandı sütunlarında ✓ işareti o e-postanın etkileşim aldığını gösterir; üzerine gel ile tarih görünür.</li>
            <li>Yüksek bounce oranı varsa alıcı listesini temizle veya segment kuralını güncelle.</li>
        </ol>
    </details>

</div>
@endsection

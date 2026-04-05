@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn alt" href="/mktg-admin/email/templates" style="font-size:var(--tx-xs);padding:6px 12px;">Templates</a>
<a class="btn alt" href="/mktg-admin/email/segments" style="font-size:var(--tx-xs);padding:6px 12px;">Segments</a>
<a class="btn {{ request()->is('mktg-admin/email/campaigns*') ? '' : 'alt' }}" href="/mktg-admin/email/campaigns" style="font-size:var(--tx-xs);padding:6px 12px;">Campaigns</a>
<a class="btn alt" href="/mktg-admin/email/log" style="font-size:var(--tx-xs);padding:6px 12px;">Send Log</a>
@endsection

@section('title', 'E-posta Kampanyaları')
@section('page_subtitle', 'Gönderim planları — template + segment birleşimi')

@section('content')
@php
$isEdit      = !empty($editing);
$action      = $isEdit ? '/mktg-admin/email/campaigns/'.$editing->id : '/mktg-admin/email/campaigns';
$segmentsOld = old('segment_ids', $isEdit ? (array)($editing->segment_ids ?? []) : []);
$stLabels    = ['draft'=>'Taslak','scheduled'=>'Planlandı','sent'=>'Gönderildi','cancelled'=>'İptal'];
@endphp
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.pl-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.pl-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.pl-stat:last-child { border-right:none; }
.pl-val   { font-size:22px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.pl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:860px; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); white-space:nowrap; }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input, .wf-field select { border:1px solid var(--u-line,#e2e8f0); border-radius:8px; padding:0 10px; height:36px; background:var(--u-card,#fff); color:var(--u-text,#0f172a); font-size:13px; outline:none; font-family:inherit; width:100%; box-sizing:border-box; appearance:auto; }
.wf-field input:focus, .wf-field select:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }

.seg-pick { border:1px solid var(--u-line,#e2e8f0); border-radius:8px; max-height:150px; overflow-y:auto; background:var(--u-bg,#f8fafc); padding:6px 8px; }
.seg-pick label { display:flex; align-items:center; gap:8px; font-size:12px; padding:5px 6px; border-radius:6px; cursor:pointer; }
.seg-pick label:hover { background:color-mix(in srgb,var(--u-brand,#1e40af) 6%,var(--u-card,#fff)); }
</style>

<div style="display:grid;gap:12px;">

    @if(session('status'))
    <div style="border:1px solid var(--u-ok,#16a34a);background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,var(--u-card,#fff));color:var(--u-ok,#16a34a);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        {{ session('status') }}
    </div>
    @endif
    @if($errors->any())
    <div style="border:1px solid var(--u-danger,#dc2626);background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,var(--u-card,#fff));color:var(--u-danger,#dc2626);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
    @endif

    {{-- KPI Bar --}}
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ $stats['total'] ?? 0 }}</div>
            <div class="pl-lbl">Toplam</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-muted,#64748b);">{{ $stats['draft'] ?? 0 }}</div>
            <div class="pl-lbl">Taslak</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:#0891b2;">{{ $stats['scheduled'] ?? 0 }}</div>
            <div class="pl-lbl">Planlandı</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-ok,#16a34a);">{{ $stats['sent'] ?? 0 }}</div>
            <div class="pl-lbl">Gönderildi</div>
        </div>
    </div>

    {{-- Ana Grid --}}
    <div style="display:grid;grid-template-columns:360px 1fr;gap:12px;align-items:start;">

        {{-- Sol: Form --}}
        <div style="display:grid;gap:12px;">

            <details class="card" {{ $isEdit ? 'open' : '' }}>
                <summary class="det-sum">
                    <h3>{{ $isEdit ? 'Kampanya Düzenle #'.$editing->id : '+ Yeni Kampanya' }}</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <form method="POST" action="{{ $action }}" style="display:grid;gap:8px;">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="wf-field">
                        <label>Kampanya Adı</label>
                        <input name="name" placeholder="Kampanya adı" value="{{ old('name', $editing->name ?? '') }}" required>
                    </div>
                    <div class="wf-field">
                        <label>Template</label>
                        <select name="template_id" required>
                            <option value="">Template seç</option>
                            @foreach(($templates ?? []) as $tpl)
                            <option value="{{ $tpl->id }}" @selected((string)old('template_id', $editing->template_id ?? '') === (string)$tpl->id)>
                                {{ $tpl->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div class="wf-field">
                            <label>Durum</label>
                            <select name="status">
                                @foreach(($statusOptions ?? []) as $st)
                                <option value="{{ $st }}" @selected(old('status', $editing->status ?? 'draft') === $st)>{{ $stLabels[$st] ?? ucfirst($st) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wf-field">
                            <label>Planlanan Gönderim</label>
                            <input type="datetime-local" name="scheduled_at"
                                value="{{ old('scheduled_at', !empty($editing->scheduled_at) ? \Illuminate\Support\Carbon::parse($editing->scheduled_at)->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    </div>
                    <div class="wf-field">
                        <label>Bağlı Marketing Kampanyası (opsiyonel)</label>
                        <select name="linked_marketing_campaign_id">
                            <option value="">Seç (opsiyonel)</option>
                            @foreach(($marketingCampaigns ?? []) as $mc)
                            <option value="{{ $mc->id }}" @selected((string)old('linked_marketing_campaign_id', $editing->linked_marketing_campaign_id ?? '') === (string)$mc->id)>
                                #{{ $mc->id }} {{ $mc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wf-field">
                        <label>Zoho Campaign ID (opsiyonel)</label>
                        <input name="zoho_campaign_id" placeholder="zoho_campaign_id" value="{{ old('zoho_campaign_id', $editing->zoho_campaign_id ?? '') }}">
                    </div>
                    <div class="wf-field">
                        <label>Segmentler</label>
                        <div class="seg-pick">
                            @forelse(($segments ?? []) as $seg)
                            <label>
                                <input type="checkbox" name="segment_ids[]" value="{{ $seg->id }}"
                                    @checked(in_array($seg->id, array_map('intval', (array)$segmentsOld), true))>
                                <span>
                                    <strong>#{{ $seg->id }}</strong> {{ $seg->name }}
                                    <span style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">— {{ (int)$seg->estimated_size }} kişi</span>
                                </span>
                            </label>
                            @empty
                            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);padding:6px;">Segment bulunamadı.</div>
                            @endforelse
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:2px;">
                        <button type="submit" class="btn ok">{{ $isEdit ? 'Güncelle' : 'Kampanya Ekle' }}</button>
                        <a href="/mktg-admin/email/campaigns" class="btn alt">Temizle</a>
                    </div>
                </form>
            </details>

            {{-- Rehber --}}
            <details class="card">
                <summary class="det-sum">
                    <h3>Kullanım Rehberi</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
                    <li>Template seç, segmentleri işaretle, adı gir ve kaydet.</li>
                    <li><strong>Gönder</strong> — anında kuyruğa alır, send log kaydı oluşturur.</li>
                    <li><strong>+1s Planla</strong> — hızlı planlama; tam saat için Düzenle formunu kullan.</li>
                    <li>Performansı <strong>Stats</strong> ve <strong>Send Log</strong> sekmesinden izle.</li>
                </ol>
            </details>

        </div>

        {{-- Sağ: Liste --}}
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);flex-wrap:wrap;">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Kampanya Listesi</div>
                <form method="GET" action="/mktg-admin/email/campaigns" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ad ara"
                        style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;min-width:130px;">
                    <select name="status" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                        <option value="all" @selected(($filters['status']??'all')==='all')>Tüm durumlar</option>
                        @foreach(($statusOptions ?? []) as $st)
                        <option value="{{ $st }}" @selected(($filters['status']??'all')===$st)>{{ $stLabels[$st] ?? ucfirst($st) }}</option>
                        @endforeach
                    </select>
                    <select name="template_id" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                        <option value="0">Tüm template</option>
                        @foreach(($templates ?? []) as $tpl)
                        <option value="{{ $tpl->id }}" @selected((int)($filters['template_id']??0)===(int)$tpl->id)>{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                    <a href="/mktg-admin/email/campaigns" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;color:var(--u-muted,#64748b);">Temizle</a>
                </form>
            </div>

            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th style="width:40px;">ID</th>
                        <th>Kampanya</th>
                        <th>Template</th>
                        <th style="width:90px;">Durum</th>
                        <th style="width:60px;text-align:right;">Alıcı</th>
                        <th>Zamanlama</th>
                        <th>İstatistik</th>
                        <th style="width:160px;">İşlem</th>
                    </tr></thead>
                    <tbody>
                        @forelse(($rows ?? []) as $row)
                        @php
                            $badgeClass = ['draft'=>'','scheduled'=>'info','sent'=>'ok','cancelled'=>'danger'][$row->status] ?? '';
                        @endphp
                        <tr>
                            <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);font-family:ui-monospace,monospace;">#{{ $row->id }}</td>
                            <td><strong>{{ $row->name }}</strong></td>
                            <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->template->name ?? '—' }}</td>
                            <td><span class="badge {{ $badgeClass }}" style="font-size:var(--tx-xs);">{{ $stLabels[$row->status] ?? $row->status }}</span></td>
                            <td style="text-align:right;font-weight:600;">{{ (int)$row->total_recipients }}</td>
                            <td style="font-size:var(--tx-xs);">
                                @if($row->scheduled_at)
                                <span style="color:var(--u-muted,#64748b);">Plan:</span> {{ \Illuminate\Support\Carbon::parse($row->scheduled_at)->format('d.m.Y H:i') }}<br>
                                @endif
                                @if($row->sent_at)
                                <span style="color:var(--u-ok,#16a34a);">Gönderildi:</span> {{ \Illuminate\Support\Carbon::parse($row->sent_at)->format('d.m.Y H:i') }}
                                @endif
                                @if(!$row->scheduled_at && !$row->sent_at)
                                <span style="color:var(--u-muted,#64748b);">—</span>
                                @endif
                            </td>
                            <td style="font-size:var(--tx-xs);">
                                <div>Gönderim: <strong>{{ (int)($row->stat_sent ?? 0) }}</strong></div>
                                <div>Açılma: <strong>{{ number_format((float)($row->stat_open_rate ?? 0), 1) }}%</strong></div>
                                <div>Tıklama: <strong>{{ number_format((float)($row->stat_click_rate ?? 0), 1) }}%</strong></div>
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                    <a class="btn alt" href="/mktg-admin/email/campaigns?edit_id={{ $row->id }}" style="font-size:var(--tx-xs);padding:4px 8px;">Düzenle</a>
                                    <form method="POST" action="/mktg-admin/email/campaigns/{{ $row->id }}/send" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn ok" style="font-size:var(--tx-xs);padding:4px 8px;">Gönder</button>
                                    </form>
                                    <form method="POST" action="/mktg-admin/email/campaigns/{{ $row->id }}/schedule" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="scheduled_at" value="{{ now()->addHour()->format('Y-m-d H:i:s') }}">
                                        <button type="submit" class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;">+1s</button>
                                    </form>
                                    <a class="btn alt" href="/mktg-admin/email/campaigns/{{ $row->id }}/stats" style="font-size:var(--tx-xs);padding:4px 8px;">Stats</a>
                                    <form method="POST" action="/mktg-admin/email/campaigns/{{ $row->id }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn warn" style="font-size:var(--tx-xs);padding:4px 8px;" onclick="return confirm('Kampanya silinsin mi?')">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--u-muted,#64748b);">Kampanya kaydı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;">{{ $rows->links() }}</div>
        </div>

    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — E-posta Kampanyaları</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📧 Kampanya İş Akışı</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Yeni kampanya → Şablon seç → Segment belirle → Gönderim zamanı ayarla</li>
                    <li><strong>draft → scheduled → sending → sent</strong> durum akışı</li>
                    <li>Gönderilmeden önce test e-postası gönder (Test Send)</li>
                    <li>Segment yanlışsa kampanya gönderiminden önce değiştirilebilir</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 Performans Metrikleri</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Gönderilen/Açılan/Tıklanan oranlarını Stats sekmesinden izle</li>
                    <li>Bireysel alıcı durumlarını Send Log sekmesinden incele</li>
                    <li>Düşük açılma oranı → Konu satırını A/B test et</li>
                    <li>Yüksek bounce → Segment listesini temizle</li>
                </ul>
            </div>
        </div>
    </details>

</div>
@endsection

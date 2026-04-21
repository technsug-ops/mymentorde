@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a href="/mktg-admin/campaigns/roi" class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;">ROI Analiz</a>
@endsection

@section('title', 'Kampanya Yönetimi')
@section('page_subtitle', 'Reklam bütçe ve dönüşüm metrik yönetimi')

@section('content')

@include('partials.manager-hero', [
    'label' => 'Reklam Yönetimi',
    'title' => 'Kampanyalar',
    'sub'   => 'Google/Meta/TikTok reklam kampanyaları, bütçe kullanımı ve dönüşüm metrikleri. Hangi kanal ROI açısından kazandırıyor, tek bakışta gör.',
    'icon'  => '📢',
    'bg'    => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1400&q=80',
    'tone'  => 'rose',
    'stats' => [
        ['icon' => '📊', 'text' => (isset($campaigns) ? $campaigns->count() : 0) . ' kampanya'],
    ],
])

@php
$stLabels = ['draft'=>'Taslak','active'=>'Aktif','paused'=>'Duraklatıldı','completed'=>'Tamamlandı','cancelled'=>'İptal'];
$chLabels = ['google_ads'=>'Google Ads','instagram_ads'=>'Instagram Ads','facebook_ads'=>'Facebook Ads','youtube_ads'=>'YouTube Ads','tiktok_ads'=>'TikTok Ads','email'=>'E-posta','other'=>'Diğer'];
$chShort  = ['google_ads'=>'Google','instagram_ads'=>'Instagram','facebook_ads'=>'Facebook','youtube_ads'=>'YouTube','tiktok_ads'=>'TikTok','email'=>'E-posta','other'=>'Diğer'];
$isEdit   = !empty($editing);
$action   = $isEdit ? '/mktg-admin/campaigns/'.$editing->id : '/mktg-admin/campaigns';
$metrics  = is_array($editing->metrics ?? null) ? $editing->metrics : [];
@endphp
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.cp-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.cp-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.cp-stat:last-child { border-right:none; }
.cp-val   { font-size:22px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.cp-val.ok   { color:var(--u-ok,#16a34a); }
.cp-val.warn { color:var(--u-warn,#d97706); }
.cp-val.muted { color:var(--u-muted,#64748b); font-size:16px; }
.cp-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); white-space:nowrap; }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }
.tl-tbl tbody tr { cursor:pointer; transition:background .1s; }
.tl-tbl tbody tr:hover { background:color-mix(in srgb,var(--u-brand,#1e40af) 3%,var(--u-card,#fff)); }
.tl-tbl tbody tr.row-open { background:color-mix(in srgb,var(--u-brand,#1e40af) 6%,var(--u-card,#fff)); }

.cp-detail-row td { padding:0 !important; }
.cp-detail-inner { padding:12px 16px; background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-bg,#f8fafc)); border-top:1px solid var(--u-line,#e2e8f0); }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input, .wf-field select, .wf-field textarea { border:1px solid var(--u-line,#e2e8f0); border-radius:8px; padding:0 10px; height:36px; background:var(--u-card,#fff); color:var(--u-text,#0f172a); font-size:13px; outline:none; font-family:inherit; width:100%; box-sizing:border-box; }
.wf-field textarea { height:72px; padding:8px 10px; resize:vertical; }
.wf-field input:focus, .wf-field select:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }
.wf-field input[type=date] { padding:0 8px; }

.stat-bar-left  { border-left:3px solid var(--u-ok,#16a34a); }
.stat-bar-warn  { border-left:3px solid var(--u-warn,#d97706); }
.stat-bar-draft { border-left:3px solid var(--u-muted,#94a3b8); }
.stat-bar-done  { border-left:3px solid #3b82f6; }
</style>

<div style="display:grid;gap:12px;">

    {{-- Flash --}}
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
    <div class="cp-stats">
        <div class="cp-stat">
            <div class="cp-val">{{ $totals['campaign_count'] ?? 0 }}</div>
            <div class="cp-lbl">Kampanya</div>
        </div>
        <div class="cp-stat">
            <div class="cp-val muted">{{ number_format((int)($totals['impressions']??0),0,'.',',') }}</div>
            <div class="cp-lbl">Impression</div>
        </div>
        <div class="cp-stat">
            <div class="cp-val muted">{{ number_format((int)($totals['clicks']??0),0,'.',',') }}</div>
            <div class="cp-lbl">Click</div>
        </div>
        <div class="cp-stat">
            <div class="cp-val">{{ number_format((int)($totals['leads']??0),0,'.',',') }}</div>
            <div class="cp-lbl">Lead</div>
        </div>
        <div class="cp-stat">
            <div class="cp-val ok">{{ number_format((int)($totals['converted']??0),0,'.',',') }}</div>
            <div class="cp-lbl">Dönüştü</div>
        </div>
        <div class="cp-stat">
            <div class="cp-val" style="font-size:var(--tx-lg);">{{ number_format((float)($totals['ctr']??0),2,'.',',') }}%</div>
            <div class="cp-lbl">CTR</div>
        </div>
        <div class="cp-stat">
            <div class="cp-val warn" style="font-size:var(--tx-base);">{{ number_format((float)($totals['cpl']??0),0,'.',',') }} / {{ number_format((float)($totals['cpa']??0),0,'.',',') }}</div>
            <div class="cp-lbl">CPL / CPA (€)</div>
        </div>
    </div>

    {{-- Ana Grid: Tablo sol, Form sağ --}}
    <div style="display:grid;grid-template-columns:1fr 340px;gap:12px;align-items:start;">

        {{-- Sol: Tablo --}}
        <div class="card">
            {{-- Filtre + Başlık --}}
            <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);flex-wrap:wrap;">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">
                    Kampanya Listesi
                    <span class="badge info" style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:6px;">{{ count($rows ?? []) }}</span>
                </div>
                <form method="GET" action="/mktg-admin/campaigns" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ad / kanal ara"
                        style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;min-width:140px;">
                    <select name="status" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                        <option value="all" @selected(($filters['status']??'all')==='all')>Tüm durumlar</option>
                        @foreach(($statusOptions ?? []) as $st)
                        <option value="{{ $st }}" @selected(($filters['status']??'all')===$st)>{{ $stLabels[$st] ?? ucfirst($st) }}</option>
                        @endforeach
                    </select>
                    <select name="channel" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                        <option value="all" @selected(($filters['channel']??'all')==='all')>Tüm kanallar</option>
                        @foreach(($channelOptions ?? []) as $ch)
                        <option value="{{ $ch }}" @selected(($filters['channel']??'all')===$ch)>{{ $chShort[$ch] ?? $ch }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                    <a href="/mktg-admin/campaigns" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;color:var(--u-muted,#64748b);">Temizle</a>
                </form>
            </div>

            {{-- Tablo --}}
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Kampanya</th>
                        <th>Kanal</th>
                        <th>Durum</th>
                        <th>İmpr / Click</th>
                        <th>Lead / Doğ / Dön</th>
                        <th style="text-align:right;">CTR</th>
                        <th style="text-align:right;">CPL €</th>
                        <th style="text-align:right;">CPA €</th>
                        <th style="width:28px;"></th>
                    </tr></thead>
                    <tbody>
                        @forelse(($rows ?? []) as $row)
                        @php
                            $bdrClass = ['active'=>'stat-bar-left','paused'=>'stat-bar-warn','draft'=>'stat-bar-draft','completed'=>'stat-bar-done','cancelled'=>'stat-bar-done'][$row['status']] ?? '';
                            $badgeClass = ['draft'=>'','active'=>'ok','paused'=>'warn','completed'=>'info','cancelled'=>'danger'][$row['status']] ?? '';
                        @endphp
                        <tr id="cpRow-{{ $row['id'] }}" class="{{ $bdrClass }}" onclick="cpToggle({{ $row['id'] }},event)">
                            <td>
                                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);background:var(--u-bg,#f3f4f6);padding:1px 5px;border-radius:4px;margin-right:5px;">#{{ $row['id'] }}</span>
                                <strong style="font-size:var(--tx-sm);">{{ $row['name'] }}</strong>
                            </td>
                            <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $chShort[$row['channel']] ?? $row['channel'] }}</td>
                            <td><span class="badge {{ $badgeClass }}" style="font-size:var(--tx-xs);">{{ $stLabels[$row['status']] ?? $row['status'] }}</span></td>
                            <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ number_format($row['impressions'],0,'.',',') }} / {{ number_format($row['clicks'],0,'.',',') }}</td>
                            <td style="font-size:var(--tx-xs);">{{ $row['lead_count'] }} / {{ $row['verified_count'] }} / {{ $row['converted_count'] }}</td>
                            <td style="text-align:right;font-size:var(--tx-xs);">{{ number_format((float)$row['ctr'],2,'.',',') }}%</td>
                            <td style="text-align:right;font-size:var(--tx-xs);">{{ number_format((float)$row['cpl'],2,'.',',') }}</td>
                            <td style="text-align:right;font-size:var(--tx-xs);">{{ number_format((float)$row['cpa'],2,'.',',') }}</td>
                            <td style="text-align:center;">
                                <button class="cp-xbtn" id="cpBtn-{{ $row['id'] }}" title="Detay"
                                    style="background:none;border:none;cursor:pointer;color:var(--u-muted,#64748b);font-size:var(--tx-sm);width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:4px;transition:transform .2s;"
                                    onclick="cpToggle({{ $row['id'] }},event)">▾</button>
                            </td>
                        </tr>
                        {{-- Detail Row --}}
                        <tr class="cp-detail-row" id="cpDetail-{{ $row['id'] }}" style="display:none;">
                            <td colspan="9" style="padding:0;">
                                <div class="cp-detail-inner">
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:center;">
                                        <a class="btn alt" href="/mktg-admin/campaigns?edit_id={{ $row['id'] }}" style="font-size:var(--tx-xs);padding:5px 10px;">Düzenle</a>
                                        @if($row['status'] === 'active')
                                            <form method="POST" action="/mktg-admin/campaigns/{{ $row['id'] }}/pause" style="display:inline;">@csrf @method('PUT')
                                                <button class="btn alt" type="submit" style="font-size:var(--tx-xs);padding:5px 10px;">Durdur</button>
                                            </form>
                                        @elseif(in_array($row['status'], ['paused','draft']))
                                            <form method="POST" action="/mktg-admin/campaigns/{{ $row['id'] }}/resume" style="display:inline;">@csrf @method('PUT')
                                                <button class="btn ok" type="submit" style="font-size:var(--tx-xs);padding:5px 10px;">Aktif Et</button>
                                            </form>
                                        @endif
                                        <a class="btn alt" href="/mktg-admin/campaigns/{{ $row['id'] }}/report" style="font-size:var(--tx-xs);padding:5px 10px;">Rapor</a>
                                        <form method="POST" action="/mktg-admin/campaigns/{{ $row['id'] }}" style="display:inline;">@csrf @method('DELETE')
                                            <button type="submit" class="btn warn" style="font-size:var(--tx-xs);padding:5px 10px;" onclick="return confirm('Kampanya silinsin mi?')">Sil</button>
                                        </form>
                                    </div>
                                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;font-size:var(--tx-xs);">
                                        <div><span style="color:var(--u-muted,#64748b);">Bütçe:</span> <strong>{{ number_format((float)$row['budget'],2,'.',',') }} {{ $row['currency'] }}</strong></div>
                                        <div><span style="color:var(--u-muted,#64748b);">Harcanan:</span> <strong>{{ number_format((float)$row['spent_amount'],2,'.',',') }} {{ $row['currency'] }}</strong></div>
                                        <div><span style="color:var(--u-muted,#64748b);">Dönüşüm:</span> <strong>{{ number_format((float)$row['lead_to_conversion_rate'],1,'.',',') }}%</strong></div>
                                        @if($row['start_date'])<div><span style="color:var(--u-muted,#64748b);">Başlangıç:</span> {{ $row['start_date'] }}</div>@endif
                                        @if($row['end_date'])<div><span style="color:var(--u-muted,#64748b);">Bitiş:</span> {{ $row['end_date'] }}</div>@endif
                                    </div>
                                    @if(!empty($row['match_keys']))
                                    <div style="margin-top:6px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">Match keys: {{ implode(', ', $row['match_keys']) }}</div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--u-muted,#64748b);">Kampanya bulunamadı.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sağ: Form + Hedef Kitle --}}
        <div style="display:grid;gap:12px;">

            {{-- Yeni / Düzenle Formu --}}
            <details class="card" id="cpNewWrap" {{ $isEdit ? 'open' : '' }}>
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
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div class="wf-field">
                            <label>Kanal</label>
                            <select name="channel" id="cp-channel-sel" onchange="loadAudienceSuggestions()">
                                @foreach(($channelOptions ?? []) as $ch)
                                <option value="{{ $ch }}" @selected(old('channel',$editing->channel??'other')===$ch)>{{ $chShort[$ch] ?? $ch }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wf-field">
                            <label>Durum</label>
                            <select name="status">
                                @foreach(($statusOptions ?? []) as $st)
                                <option value="{{ $st }}" @selected(old('status',$editing->status??'draft')===$st)>{{ $stLabels[$st] ?? ucfirst($st) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wf-field">
                            <label>Bütçe</label>
                            <input type="number" step="0.01" min="0" name="budget" placeholder="0.00" value="{{ old('budget', $editing->budget ?? 0) }}" required>
                        </div>
                        <div class="wf-field">
                            <label>Para Birimi</label>
                            <input name="currency" placeholder="EUR" value="{{ old('currency', $editing->currency ?? 'EUR') }}">
                        </div>
                        <div class="wf-field">
                            <label>Başlangıç</label>
                            <input type="date" name="start_date" value="{{ old('start_date', !empty($editing->start_date) ? \Illuminate\Support\Carbon::parse($editing->start_date)->toDateString() : '') }}">
                        </div>
                        <div class="wf-field">
                            <label>Bitiş</label>
                            <input type="date" name="end_date" value="{{ old('end_date', !empty($editing->end_date) ? \Illuminate\Support\Carbon::parse($editing->end_date)->toDateString() : '') }}">
                        </div>
                        <div class="wf-field">
                            <label>Impression</label>
                            <input type="number" min="0" name="metrics[impressions]" placeholder="0" value="{{ old('metrics.impressions', $metrics['impressions'] ?? 0) }}">
                        </div>
                        <div class="wf-field">
                            <label>Click</label>
                            <input type="number" min="0" name="metrics[clicks]" placeholder="0" value="{{ old('metrics.clicks', $metrics['clicks'] ?? 0) }}">
                        </div>
                    </div>
                    <div class="wf-field">
                        <label>Hedef Ülke (opsiyonel)</label>
                        <input name="target_country" placeholder="Almanya, Türkiye..." value="{{ old('target_country', $editing->target_country ?? '') }}">
                    </div>
                    <div class="wf-field">
                        <label>Hedef Kitle</label>
                        <textarea name="target_audience" placeholder="Hedef kitle açıklaması">{{ old('target_audience', $editing->target_audience ?? '') }}</textarea>
                    </div>
                    <div class="wf-field">
                        <label>Açıklama (opsiyonel)</label>
                        <textarea name="description" placeholder="Kampanya notları">{{ old('description', $editing->description ?? '') }}</textarea>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="btn ok" type="submit">{{ $isEdit ? 'Güncelle' : 'Kampanya Ekle' }}</button>
                        @if($isEdit)<a class="btn alt" href="/mktg-admin/campaigns">İptal</a>@endif
                    </div>
                </form>
            </details>

            {{-- Hedef Kitle Önerileri --}}
            <details class="card" id="audience-panel">
                <summary class="det-sum">
                    <h3>Hedef Kitle Önerileri</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;align-items:center;">
                    <select id="aud-type-sel" onchange="loadAudienceSuggestions()"
                        style="height:32px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;flex:1;">
                        <option value="">Tüm tipler</option>
                        <option value="awareness">Farkındalık</option>
                        <option value="lead_gen">Lead Toplama</option>
                        <option value="conversion">Dönüşüm</option>
                        <option value="retention">Elde Tutma</option>
                    </select>
                    <button onclick="loadAudienceSuggestions()" class="btn alt" style="font-size:var(--tx-xs);padding:5px 10px;" type="button">Yenile</button>
                </div>
                <div id="aud-results" style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">Yükleniyor...</div>
            </details>

            {{-- Rehber --}}
            <details class="card">
                <summary class="det-sum">
                    <h3>Metrik Rehberi</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <div style="display:grid;gap:6px;">
                    @foreach(['CTR'=>'Tıklama oranı = Click÷Impression×100 (%2+ iyi)','CPL'=>'Lead başına maliyet = Harcama÷Lead','CPA'=>'Dönüşüm başına maliyet = Harcama÷Dönüşüm','Lead'=>'utm_campaign veya tracking code eşleşen başvuru'] as $metric => $desc)
                    <div style="display:flex;gap:8px;font-size:var(--tx-xs);padding:5px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                        <span style="min-width:44px;font-weight:700;color:var(--u-brand,#1e40af);">{{ $metric }}</span>
                        <span style="color:var(--u-muted,#64748b);">{{ $desc }}</span>
                    </div>
                    @endforeach
                    <div style="margin-top:6px;font-size:var(--tx-xs);">
                        <span style="font-weight:700;font-size:var(--tx-xs);color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Durum Akışı</span>
                        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin-top:6px;">
                            <span class="badge" style="font-size:var(--tx-xs);">Taslak</span>→
                            <span class="badge ok" style="font-size:var(--tx-xs);">Aktif</span>→
                            <span class="badge warn" style="font-size:var(--tx-xs);">Durdu</span>→
                            <span class="badge info" style="font-size:var(--tx-xs);">Bitti</span>
                        </div>
                    </div>
                </div>
            </details>

        </div>
    </div>

</div>

<script>
function cpToggle(id, e) {
    if (e) e.stopPropagation();
    var det = document.getElementById('cpDetail-'+id);
    var btn = document.getElementById('cpBtn-'+id);
    var row = document.getElementById('cpRow-'+id);
    if (!det) return;
    var isOpen = det.style.display !== 'none';
    document.querySelectorAll('.cp-detail-row').forEach(function(d){ d.style.display='none'; });
    document.querySelectorAll('.cp-xbtn').forEach(function(b){ b.style.transform=''; });
    document.querySelectorAll('tbody tr').forEach(function(r){ r.classList.remove('row-open'); });
    if (!isOpen) {
        det.style.display = 'table-row';
        if (btn) btn.style.transform = 'rotate(180deg)';
        if (row) row.classList.add('row-open');
    }
}

function loadAudienceSuggestions() {
    var type = (document.getElementById('aud-type-sel')||{}).value||'';
    var results = document.getElementById('aud-results');
    if (!results) return;
    results.innerHTML = 'Yükleniyor...';
    fetch('/mktg-admin/suggestions/audience?campaign_type='+encodeURIComponent(type),{headers:{'Accept':'application/json'}})
        .then(function(r){return r.json();})
        .then(function(data){
            if (!Array.isArray(data)||data.length===0){results.innerHTML='<em>Öneri bulunamadı (90 gün içinde yeterli veri yok).</em>';return;}
            var html='<div class="tl-wrap"><table class="tl-tbl"><thead><tr>'
                +'<th>Kaynak</th><th style="text-align:right;">Dönüşüm%</th><th style="text-align:right;">Lead</th>'
                +'</tr></thead><tbody>';
            data.forEach(function(s){
                html+='<tr><td>'+esc(s.source_label)+'</td>'
                    +'<td style="text-align:right;font-weight:700;color:var(--u-brand,#1e40af);">'+s.conversion_rate+'%</td>'
                    +'<td style="text-align:right;">'+s.lead_count+'</td></tr>';
            });
            results.innerHTML=html+'</tbody></table></div>';
        })
        .catch(function(){results.innerHTML='<em>Veri yüklenemedi.</em>';});
}
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

document.addEventListener('DOMContentLoaded',function(){
    var panel = document.getElementById('audience-panel');
    if (panel && panel.open) loadAudienceSuggestions();
});
</script>

<details class="card" style="margin-top:12px;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;">
        <h4 style="margin:0 0 10px;font-size:var(--tx-sm);font-weight:700;">Kampanya Yönetimi</h4>
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 12px;">Ücretli reklam ve pazarlama kampanyalarını oluştur, takip et, analiz et.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">➕ Yeni Kampanya Oluşturma</strong>
                <ol style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Sağdaki formdan kampanya adını gir</li>
                    <li>Kanal seç: Google Ads, Facebook, Instagram, TikTok, LinkedIn</li>
                    <li>Bütçe ve başlangıç/bitiş tarihi belirle</li>
                    <li>Hedef kitle ve hedef lead sayısını gir</li>
                    <li><strong>Kaydet</strong> → kampanya tabloya eklenir</li>
                </ol>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 KPI Sütunları</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><strong>Impression:</strong> Reklamın kaç kişiye gösterildiği</li>
                    <li><strong>CTR:</strong> Tıklama oranı (Click/Impression × 100)</li>
                    <li><strong>CPL:</strong> Bir lead için ödenen ortalama maliyet</li>
                    <li><strong>CPA:</strong> Bir dönüşüm için ödenen maliyet</li>
                    <li>Satıra tıkla → genişlemiş detay görünümü açılır</li>
                </ul>
            </div>
        </div>
        <div style="margin-top:10px;padding:10px;background:color-mix(in srgb,var(--u-warn,#d97706) 8%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
            ⚠️ <strong>Dikkat:</strong> CPL 50€ üzerine çıkarsa kampanya verimliliği düşüktür — bütçeyi yeniden dağıt veya hedeflemeyi daralt.
        </div>
    </div>
</details>
@endsection

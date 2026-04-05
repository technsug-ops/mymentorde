@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn" style="font-size:var(--tx-xs);padding:6px 12px;background:var(--u-brand,#1e40af);color:#fff;border-color:transparent;" href="/mktg-admin/email/templates">Templates</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/email/segments">Segments</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/email/campaigns">Campaigns</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/email/log">Send Log</a>
@endsection

@section('title', 'E-posta Şablonları')
@section('page_subtitle', 'E-posta Şablonları — otomatik ve manuel gönderim şablonları')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }

/* Stats bar */
.tp-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.tp-stat  { flex:1; padding:10px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.tp-stat:last-child { border-right:none; }
.tp-val   { font-size:20px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.tp-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

/* 2-col layout */
.tp-grid { display:grid; grid-template-columns:1fr 1.2fr; gap:12px; }
@media(max-width:1100px){ .tp-grid { grid-template-columns:1fr; } }

/* Form inputs */
.fm-row   { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px; }
.fm-row-1 { margin-bottom:8px; }
.fm-row input, .fm-row select, .fm-row textarea,
.fm-row-1 input, .fm-row-1 select, .fm-row-1 textarea {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.fm-row textarea, .fm-row-1 textarea { height:96px; padding:8px 10px; resize:vertical; }
.fm-row input:focus, .fm-row select:focus, .fm-row textarea:focus,
.fm-row-1 input:focus, .fm-row-1 select:focus, .fm-row-1 textarea:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}

/* Filter bar */
.fl-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:8px 0 4px; }
.fl-bar input, .fl-bar select {
    height:34px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0);
    border-radius:8px; background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:12px; outline:none; min-width:110px; appearance:auto;
}
.fl-bar input:focus, .fl-bar select:focus { border-color:var(--u-brand,#1e40af); }

/* Table */
.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; margin-top:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:900px; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }
.tl-tbl tbody tr:hover { background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); }
.tl-acts { display:flex; gap:4px; flex-wrap:wrap; }
.tp-ph   { display:inline-block; border:1px solid var(--u-line,#e2e8f0); border-radius:999px; padding:2px 7px; background:color-mix(in srgb,var(--u-brand,#1e40af) 6%,var(--u-card,#fff)); color:var(--u-brand,#1e40af); margin:2px 2px 0 0; font-size:11px; font-family:ui-monospace,monospace; }

/* Details guide */
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

/* Alerts */
.flash   { border:1px solid var(--u-ok,#16a34a); background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,#fff); color:var(--u-ok,#16a34a); border-radius:10px; padding:10px 14px; font-size:13px; }
.err-box { border:1px solid var(--u-danger,#dc2626); background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,#fff); color:var(--u-danger,#dc2626); border-radius:10px; padding:10px 14px; font-size:13px; }
</style>

<div style="display:grid;gap:12px;">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="err-box">@foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach</div>
    @endif

    {{-- KPI bar --}}
    <div class="tp-stats">
        <div class="tp-stat"><div class="tp-val">{{ $stats['total'] ?? 0 }}</div><div class="tp-lbl">Toplam</div></div>
        <div class="tp-stat"><div class="tp-val">{{ $stats['active'] ?? 0 }}</div><div class="tp-lbl">Aktif</div></div>
        <div class="tp-stat"><div class="tp-val">{{ $stats['automated'] ?? 0 }}</div><div class="tp-lbl">Automated</div></div>
        <div class="tp-stat"><div class="tp-val">{{ $stats['manual'] ?? 0 }}</div><div class="tp-lbl">Manual</div></div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — E-posta Şablonları</h3>
            <span class="det-chev">▼</span>
        </summary>
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 14px;line-height:1.6;">
            E-posta modülü 4 bileşenden oluşur: <strong>Template</strong> (şablon) → <strong>Segment</strong> (hedef kitle) → <strong>Campaign</strong> (gönderim) → <strong>Send Log</strong> (takip).
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div style="display:flex;flex-direction:column;gap:8px;font-size:var(--tx-xs);line-height:1.5;">
                @foreach(['Template oluştur — Templates sekmesinde e-posta şablonu yaz. {{name}} gibi değişkenler kullanabilirsin.','Segment tanımla — Segments sekmesinde filtreler ile hedef kitleyi belirle; ön izleme ile kişi sayısını gör.','Kampanya oluştur — Campaigns sekmesinde template + segment seç ve gönderim zamanını ayarla.','Sonuçları izle — Send Log sekmesinde her e-postanın durumunu ve açılma/tıklama verilerini gör.'] as $i => $step)
                <div style="display:flex;gap:8px;align-items:flex-start;">
                    <span style="background:var(--u-brand,#1e40af);color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:var(--tx-xs);font-weight:700;flex-shrink:0;">{{ $i+1 }}</span>
                    <span>{{ $step }}</span>
                </div>
                @endforeach
            </div>
            <div>
                <div style="font-size:var(--tx-xs);font-weight:600;margin-bottom:8px;">Sekme Özeti</div>
                <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;overflow:hidden;font-size:var(--tx-xs);">
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);"><span style="min-width:80px;font-weight:600;">Templates</span><span style="color:var(--u-muted);">E-posta şablonları — içerik ve tasarım</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);"><span style="min-width:80px;font-weight:600;">Segments</span><span style="color:var(--u-muted);">Hedef kitleler — filtre tabanlı gruplar</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);"><span style="min-width:80px;font-weight:600;">Campaigns</span><span style="color:var(--u-muted);">Gönderim planları — template + segment birleşimi</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;"><span style="min-width:80px;font-weight:600;">Send Log</span><span style="color:var(--u-muted);">Her e-postanın gönderim geçmişi</span></div>
                </div>
                <div style="margin-top:10px;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                    💡 Template'lerde <code style="background:var(--u-line,#e2e8f0);padding:1px 4px;border-radius:3px;">@{{name}}</code>, <code style="background:var(--u-line,#e2e8f0);padding:1px 4px;border-radius:3px;">@{{email}}</code> gibi değişkenler gönderim sırasında otomatik doldurulur.
                </div>
            </div>
        </div>
    </details>

    {{-- 2-col: form + list --}}
    <div class="tp-grid">

        {{-- Form --}}
        @php
            $isEdit = !empty($editing);
            $formAction = $isEdit ? '/mktg-admin/email/templates/'.$editing->id : '/mktg-admin/email/templates';
            $oldPlaceholders      = old('placeholders');
            $editingPlaceholders  = $isEdit ? implode(',', (array) ($editing->available_placeholders ?? [])) : '';
        @endphp
        <details class="card" {{ $isEdit ? 'open' : '' }}>
            <summary class="det-sum">
                <h3>{{ $isEdit ? '✏️ Template Düzenle #'.$editing->id : '+ Yeni Template' }}</h3>
                <span class="det-chev">▼</span>
            </summary>
            <form method="POST" action="{{ $formAction }}" style="margin-top:12px;">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="fm-row">
                    <input name="name" placeholder="Template adı" value="{{ old('name', $editing->name ?? '') }}" required>
                    <select name="type" required>
                        @foreach(($templateTypes ?? []) as $tp)
                            <option value="{{ $tp }}" @selected(old('type', $editing->type ?? 'manual') === $tp)>{{ $tp }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-row">
                    <input name="category" placeholder="Kategori (welcome, reminder…)" value="{{ old('category', $editing->category ?? '') }}" required>
                    <select name="trigger_event">
                        <option value="">Trigger event (opsiyonel)</option>
                        @foreach(($triggerEvents ?? []) as $ev)
                            <option value="{{ $ev }}" @selected(old('trigger_event', $editing->trigger_event ?? '') === $ev)>{{ $ev }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-row">
                    <input type="number" min="0" name="trigger_delay_minutes" placeholder="Trigger delay (dk)" value="{{ old('trigger_delay_minutes', $editing->trigger_delay_minutes ?? 0) }}">
                    <input name="placeholders" placeholder="Placeholders (virgüllü)" value="{{ $oldPlaceholders ?? $editingPlaceholders }}">
                </div>
                <div class="fm-row">
                    <input name="subject_tr" placeholder="Subject TR" value="{{ old('subject_tr', $editing->subject_tr ?? '') }}" required>
                    <input name="subject_de" placeholder="Subject DE (opsiyonel)" value="{{ old('subject_de', $editing->subject_de ?? '') }}">
                </div>
                <div class="fm-row-1">
                    <textarea name="body_tr" placeholder="Body TR" required>{{ old('body_tr', $editing->body_tr ?? '') }}</textarea>
                </div>
                <div class="fm-row">
                    <input name="from_name" placeholder="From name" value="{{ old('from_name', $editing->from_name ?? 'MentorDE') }}">
                    <input type="email" name="from_email" placeholder="From email" value="{{ old('from_email', $editing->from_email ?? 'noreply@mentorde.com') }}">
                </div>
                <div class="fm-row">
                    <input type="email" name="reply_to" placeholder="Reply-to (opsiyonel)" value="{{ old('reply_to', $editing->reply_to ?? '') }}">
                    <select name="is_active">
                        <option value="1" @selected((string) old('is_active', isset($editing) ? (int) $editing->is_active : 1) === '1')>Aktif</option>
                        <option value="0" @selected((string) old('is_active', isset($editing) ? (int) $editing->is_active : 1) === '0')>Pasif</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;margin-top:6px;">
                    <button type="submit" class="btn ok">{{ $isEdit ? 'Güncelle' : 'Template Ekle' }}</button>
                    <a href="/mktg-admin/email/templates" class="btn alt">Temizle</a>
                </div>
            </form>
        </details>

        {{-- Liste --}}
        <article class="card" style="min-width:0;">
            <h3 style="margin:0 0 2px;font-size:var(--tx-sm);font-weight:700;">Template Listesi</h3>
            <form method="GET" action="/mktg-admin/email/templates">
                <div class="fl-bar">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ad / kategori / event" style="flex:1;min-width:120px;">
                    <select name="type">
                        <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>Tüm tipler</option>
                        <option value="automated" @selected(($filters['type'] ?? 'all') === 'automated')>automated</option>
                        <option value="manual" @selected(($filters['type'] ?? 'all') === 'manual')>manual</option>
                    </select>
                    <select name="status">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tüm durumlar</option>
                        <option value="active" @selected(($filters['status'] ?? 'all') === 'active')>aktif</option>
                        <option value="passive" @selected(($filters['status'] ?? 'all') === 'passive')>pasif</option>
                    </select>
                    <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                    <a href="/mktg-admin/email/templates" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 14px;display:flex;align-items:center;">Temizle</a>
                </div>
            </form>

            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>ID</th><th>Ad</th><th>Tip</th><th>Kategori / Event</th><th>Durum</th><th>Placeholders</th><th>Stats</th><th>İşlem</th>
                    </tr></thead>
                    <tbody>
                    @forelse(($rows ?? []) as $row)
                        <tr style="cursor:pointer;" onclick="showPreview({{ $row->id }})">
                            <td style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);font-family:ui-monospace,monospace;">#{{ $row->id }}</td>
                            <td>
                                <strong style="color:var(--u-brand,#1e40af);">{{ $row->name }}</strong><br>
                                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->subject_tr }}</span>
                            </td>
                            <td style="font-size:var(--tx-xs);">{{ $row->type }}</td>
                            <td>
                                <span style="font-size:var(--tx-xs);">{{ $row->category }}</span><br>
                                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->trigger_event ?: '—' }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $row->is_active ? 'ok' : 'danger' }}">
                                    {{ $row->is_active ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td>
                                @foreach((array) ($row->available_placeholders ?? []) as $ph)
                                    <span class="tp-ph">{{ $ph }}</span>
                                @endforeach
                            </td>
                            <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);white-space:nowrap;">
                                {{ $row->stat_total_sent }} gönderim<br>
                                Açılma: {{ number_format((float) $row->stat_open_rate, 1) }}%<br>
                                Tıklama: {{ number_format((float) $row->stat_click_rate, 1) }}%
                            </td>
                            <td onclick="event.stopPropagation()">
                                <div class="tl-acts">
                                    <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" href="/mktg-admin/email/templates?edit_id={{ $row->id }}">Düzenle</a>
                                    <form method="POST" action="/mktg-admin/email/templates/{{ $row->id }}/test-send" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;">Test</button>
                                    </form>
                                    <form method="POST" action="/mktg-admin/email/templates/{{ $row->id }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn warn" style="font-size:var(--tx-xs);padding:4px 8px;" onclick="return confirm('Template silinsin mi?')">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Template kaydı yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">{{ $rows->links() }}</div>
        </article>
    </div>
</div>

{{-- ── Önizleme Modal ── --}}
<div id="tplModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.45);backdrop-filter:blur(2px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--u-card,#fff);border-radius:14px;max-width:680px;width:100%;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        {{-- Header --}}
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid var(--u-line,#e2e8f0);flex-shrink:0;">
            <div>
                <div id="tplModalName" style="font-weight:700;font-size:var(--tx-base);"></div>
                <div id="tplModalMeta" style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-top:2px;"></div>
            </div>
            <button onclick="closePreview()" style="background:none;border:none;cursor:pointer;font-size:var(--tx-xl);color:var(--u-muted,#64748b);line-height:1;padding:4px 8px;">×</button>
        </div>
        {{-- Tabs --}}
        <div style="display:flex;border-bottom:1px solid var(--u-line,#e2e8f0);flex-shrink:0;">
            <button onclick="switchTab('tr')" id="tabTr" style="padding:10px 18px;font-size:var(--tx-xs);font-weight:700;border:none;background:none;cursor:pointer;border-bottom:2px solid var(--u-brand,#1e40af);color:var(--u-brand,#1e40af);">TR</button>
            <button onclick="switchTab('de')" id="tabDe" style="padding:10px 18px;font-size:var(--tx-xs);font-weight:700;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;color:var(--u-muted,#64748b);">DE</button>
            <button onclick="switchTab('info')" id="tabInfo" style="padding:10px 18px;font-size:var(--tx-xs);font-weight:700;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;color:var(--u-muted,#64748b);">Bilgi</button>
        </div>
        {{-- Content --}}
        <div style="overflow-y:auto;flex:1;padding:20px;">

            {{-- TR Paneli --}}
            <div id="panelTr">
                <div style="margin-bottom:12px;">
                    <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Konu</div>
                    <div id="tplSubjectTr" style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px 12px;font-size:var(--tx-sm);background:color-mix(in srgb,var(--u-brand,#1e40af) 3%,var(--u-card,#fff));"></div>
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">İçerik</div>
                    <pre id="tplBodyTr" style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:14px;font-size:var(--tx-xs);font-family:inherit;line-height:1.7;white-space:pre-wrap;word-break:break-word;background:var(--u-bg,#f8fafc);color:var(--u-text,#0f172a);margin:0;"></pre>
                </div>
            </div>

            {{-- DE Paneli --}}
            <div id="panelDe" style="display:none;">
                <div style="margin-bottom:12px;">
                    <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Konu (DE)</div>
                    <div id="tplSubjectDe" style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px 12px;font-size:var(--tx-sm);background:color-mix(in srgb,var(--u-brand,#1e40af) 3%,var(--u-card,#fff));"></div>
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">İçerik (DE)</div>
                    <pre id="tplBodyDe" style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:14px;font-size:var(--tx-xs);font-family:inherit;line-height:1.7;white-space:pre-wrap;word-break:break-word;background:var(--u-bg,#f8fafc);color:var(--u-text,#0f172a);margin:0;"></pre>
                </div>
            </div>

            {{-- Info Paneli --}}
            <div id="panelInfo" style="display:none;">
                <div style="display:grid;gap:8px;font-size:var(--tx-sm);">
                    <div style="display:grid;grid-template-columns:160px 1fr;gap:4px;padding:8px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                        <span style="color:var(--u-muted,#64748b);font-weight:600;">Tip</span>
                        <span id="infoType"></span>
                    </div>
                    <div style="display:grid;grid-template-columns:160px 1fr;gap:4px;padding:8px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                        <span style="color:var(--u-muted,#64748b);font-weight:600;">Kategori</span>
                        <span id="infoCategory"></span>
                    </div>
                    <div style="display:grid;grid-template-columns:160px 1fr;gap:4px;padding:8px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                        <span style="color:var(--u-muted,#64748b);font-weight:600;">Trigger Event</span>
                        <span id="infoTrigger"></span>
                    </div>
                    <div style="display:grid;grid-template-columns:160px 1fr;gap:4px;padding:8px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                        <span style="color:var(--u-muted,#64748b);font-weight:600;">Trigger Gecikme</span>
                        <span id="infoDelay"></span>
                    </div>
                    <div style="display:grid;grid-template-columns:160px 1fr;gap:4px;padding:8px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                        <span style="color:var(--u-muted,#64748b);font-weight:600;">Gönderici</span>
                        <span id="infoFrom"></span>
                    </div>
                    <div style="display:grid;grid-template-columns:160px 1fr;gap:4px;padding:8px 0;">
                        <span style="color:var(--u-muted,#64748b);font-weight:600;">Placeholders</span>
                        <span id="infoPlaceholders"></span>
                    </div>
                </div>
            </div>

        </div>
        {{-- Footer --}}
        <div style="padding:12px 20px;border-top:1px solid var(--u-line,#e2e8f0);display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <span id="tplModalId" style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);font-family:ui-monospace,monospace;"></span>
            <div style="display:flex;gap:8px;">
                <a id="tplEditLink" class="btn alt" style="font-size:var(--tx-xs);padding:6px 14px;">Düzenle</a>
                <button onclick="closePreview()" class="btn" style="font-size:var(--tx-xs);padding:6px 14px;">Kapat</button>
            </div>
        </div>
    </div>
</div>

@php
$_tplData = $rows->getCollection()->map(fn($r) => [
    'id'            => $r->id,
    'name'          => $r->name,
    'type'          => $r->type,
    'category'      => $r->category,
    'trigger_event' => $r->trigger_event,
    'trigger_delay' => $r->trigger_delay_minutes,
    'subject_tr'    => $r->subject_tr,
    'subject_de'    => $r->subject_de,
    'body_tr'       => $r->body_tr,
    'body_de'       => $r->body_de,
    'from_name'     => $r->from_name,
    'from_email'    => $r->from_email,
    'placeholders'  => $r->available_placeholders ?? [],
    'is_active'     => $r->is_active,
])->keyBy('id')->toArray();
@endphp
<script>
var __tpl = {!! json_encode($_tplData) !!};

function showPreview(id) {
    var t = __tpl[id]; if (!t) return;
    document.getElementById('tplModalName').textContent  = t.name;
    document.getElementById('tplModalMeta').textContent  = t.type + ' · ' + t.category + (t.trigger_event ? ' · ' + t.trigger_event : '');
    document.getElementById('tplModalId').textContent    = '#' + t.id;
    document.getElementById('tplSubjectTr').textContent  = t.subject_tr || '(boş)';
    document.getElementById('tplBodyTr').textContent     = t.body_tr || '(boş)';
    document.getElementById('tplSubjectDe').textContent  = t.subject_de || '(çeviri yok)';
    document.getElementById('tplBodyDe').textContent     = t.body_de || '(çeviri yok)';
    document.getElementById('infoType').textContent      = t.type;
    document.getElementById('infoCategory').textContent  = t.category;
    document.getElementById('infoTrigger').textContent   = t.trigger_event || '—';
    document.getElementById('infoDelay').textContent     = t.trigger_delay ? t.trigger_delay + ' dakika' : '—';
    document.getElementById('infoFrom').textContent      = t.from_name + ' <' + t.from_email + '>';
    var phEl = document.getElementById('infoPlaceholders');
    phEl.innerHTML = (t.placeholders||[]).map(function(p){
        return '<span style="display:inline-block;background:color-mix(in srgb,#1e40af 8%,#fff);color:#1e40af;border-radius:4px;padding:1px 6px;font-size:11px;font-family:ui-monospace,monospace;margin:2px;">{{'+p+'}}</span>';
    }).join('') || '—';
    document.getElementById('tplEditLink').href = '/mktg-admin/email/templates?edit_id=' + t.id;
    switchTab('tr');
    var modal = document.getElementById('tplModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closePreview() {
    document.getElementById('tplModal').style.display = 'none';
    document.body.style.overflow = '';
}

function switchTab(tab) {
    ['tr','de','info'].forEach(function(t) {
        document.getElementById('panel' + t.charAt(0).toUpperCase() + t.slice(1)).style.display = t === tab ? 'block' : 'none';
        var btn = document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
        btn.style.borderBottomColor = t === tab ? 'var(--u-brand,#1e40af)' : 'transparent';
        btn.style.color             = t === tab ? 'var(--u-brand,#1e40af)' : 'var(--u-muted,#64748b)';
    });
}

document.getElementById('tplModal').addEventListener('click', function(e) {
    if (e.target === this) closePreview();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePreview();
});
</script>
@endsection

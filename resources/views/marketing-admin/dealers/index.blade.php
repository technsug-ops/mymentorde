@extends('marketing-admin.layouts.app')

@section('title', 'Bayi İlişkileri')

@section('page_subtitle', 'Bayi İlişkileri — performans, kazanç ve toplu iletişim yönetimi')

@section('topbar-actions')
<a class="btn {{ request()->is('mktg-admin/dealers') && !request()->is('mktg-admin/dealers/*') ? '' : 'alt' }}" href="/mktg-admin/dealers" style="font-size:var(--tx-xs);padding:6px 12px;">Dealer Relations</a>
<a class="btn alt" href="/mktg-admin/tracking-links" style="font-size:var(--tx-xs);padding:6px 12px;">Tracking Links</a>
<a class="btn alt" href="/mktg-admin/pipeline" style="font-size:var(--tx-xs);padding:6px 12px;">Pipeline</a>
@endsection

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.dl-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.dl-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.dl-stat:last-child { border-right:none; }
.dl-val   { font-size:20px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.dl-val.ok   { color:var(--u-ok,#16a34a); }
.dl-val.warn { color:var(--u-warn,#d97706); }
.dl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; min-width:980px; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input, .wf-field select, .wf-field textarea { border:1px solid var(--u-line,#e2e8f0); border-radius:8px; padding:0 10px; height:36px; background:var(--u-card,#fff); color:var(--u-text,#0f172a); font-size:13px; outline:none; font-family:inherit; width:100%; box-sizing:border-box; }
.wf-field textarea { height:80px; padding:8px 10px; resize:vertical; }
.wf-field input:focus, .wf-field select:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }
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
    <div class="dl-stats">
        <div class="dl-stat">
            <div class="dl-val">{{ $stats['total'] ?? 0 }}</div>
            <div class="dl-lbl">Toplam Bayi</div>
        </div>
        <div class="dl-stat">
            <div class="dl-val ok">{{ $stats['active'] ?? 0 }}</div>
            <div class="dl-lbl">Aktif Bayi</div>
        </div>
        <div class="dl-stat">
            <div class="dl-val">{{ $stats['students'] ?? 0 }}</div>
            <div class="dl-lbl">Atanan Öğrenci</div>
        </div>
        <div class="dl-stat">
            <div class="dl-val ok">{{ number_format((float)($stats['revenue_earned'] ?? 0), 2, ',', '.') }} €</div>
            <div class="dl-lbl">Toplam Kazanç</div>
        </div>
        <div class="dl-stat">
            <div class="dl-val warn">{{ number_format((float)($stats['revenue_pending'] ?? 0), 2, ',', '.') }} €</div>
            <div class="dl-lbl">Bekleyen Kazanç</div>
        </div>
    </div>

    {{-- Ana Grid --}}
    <div style="display:grid;grid-template-columns:1.2fr .8fr;gap:12px;align-items:start;">

        {{-- Sol: Tablo --}}
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);flex-wrap:wrap;">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Bayi Performans Listesi</div>
                <form method="GET" action="/mktg-admin/dealers" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="code / isim ara"
                        style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;min-width:150px;">
                    <select name="type" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                        <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>tüm tipler</option>
                        @foreach(($typeOptions ?? []) as $tp)
                        <option value="{{ $tp->code }}" @selected(($filters['type'] ?? 'all') === $tp->code)>{{ $tp->code }} – {{ $tp->name_tr }}</option>
                        @endforeach
                    </select>
                    <select name="active" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                        <option value="all" @selected(($filters['active'] ?? 'all') === 'all')>tüm durumlar</option>
                        <option value="active" @selected(($filters['active'] ?? 'all') === 'active')>sadece aktif</option>
                        <option value="inactive" @selected(($filters['active'] ?? 'all') === 'inactive')>sadece pasif</option>
                    </select>
                    <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                    <a href="/mktg-admin/dealers" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;color:var(--u-muted,#64748b);">Temizle</a>
                </form>
            </div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Dealer</th>
                        <th>Tip</th>
                        <th>Öğrenci</th>
                        <th>Kazanç</th>
                        <th style="width:80px;text-align:right;">Dönüşüm</th>
                        <th style="width:70px;text-align:right;">Clicks</th>
                        <th style="width:80px;">Aksiyon</th>
                    </tr></thead>
                    <tbody>
                        @forelse(($statusRows ?? []) as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['code'] }}</strong> — {{ $row['name'] }}<br>
                                <span class="badge {{ $row['is_active'] ? 'ok' : 'danger' }}" style="font-size:var(--tx-xs);">{{ $row['is_active'] ? 'aktif' : 'pasif' }}</span>
                            </td>
                            <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row['type_name'] }}</td>
                            <td style="font-size:var(--tx-xs);">{{ $row['students_active'] }} aktif / {{ $row['students_total'] }} toplam</td>
                            <td>
                                <span style="font-weight:600;">{{ number_format((float)$row['total_earned'], 2, ',', '.') }} €</span><br>
                                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">bekl: {{ number_format((float)$row['total_pending'], 2, ',', '.') }} €</span>
                            </td>
                            <td style="text-align:right;font-weight:600;">{{ $row['converted_total'] }}</td>
                            <td style="text-align:right;">{{ $row['tracking_clicks'] }}</td>
                            <td><a class="btn" href="/mktg-admin/dealers/{{ $row['code'] }}/performance" style="font-size:var(--tx-xs);padding:4px 10px;">Detay</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Bayi kaydı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;">{{ $rows->links() }}</div>
        </div>

        {{-- Sağ: Formlar --}}
        <div style="display:grid;gap:12px;">

            <details class="card">
                <summary class="det-sum">
                    <h3>📢 Toplu Duyuru</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <form method="POST" action="/mktg-admin/dealers/broadcast" style="display:grid;gap:8px;">
                    @csrf
                    <div class="wf-field">
                        <label>Dealer Kodları (virgülle)</label>
                        <input name="dealer_codes" list="dealerCodes" placeholder="OPE-...,REF-..." value="{{ old('dealer_codes') }}" required>
                        <datalist id="dealerCodes">
                            @foreach(($dealerSuggestions ?? []) as $code)
                            <option value="{{ $code }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div class="wf-field">
                            <label>Kanal</label>
                            <select name="channel">
                                <option value="inApp">inApp</option>
                                <option value="email">email</option>
                                <option value="whatsapp">whatsapp</option>
                            </select>
                        </div>
                        <div class="wf-field">
                            <label>Konu</label>
                            <input name="subject" placeholder="konu" value="{{ old('subject') }}" required>
                        </div>
                    </div>
                    <div class="wf-field">
                        <label>Mesaj</label>
                        <textarea name="message" placeholder="duyuru metni" required>{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn ok">Duyuruyu Kuyruğa Al</button>
                </form>
            </details>

            <details class="card">
                <summary class="det-sum">
                    <h3>📁 Materyal Paylaşımı</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <form method="POST" action="/mktg-admin/dealers/materials" style="display:grid;gap:8px;">
                    @csrf
                    <div class="wf-field">
                        <label>Dealer Kodları (virgülle)</label>
                        <input name="dealer_codes" list="dealerCodes" placeholder="dealer kodları" value="{{ old('dealer_codes') }}" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div class="wf-field">
                            <label>Materyal Başlığı</label>
                            <input name="material_title" placeholder="başlık" value="{{ old('material_title') }}" required>
                        </div>
                        <div class="wf-field">
                            <label>Tür</label>
                            <select name="material_type">
                                <option value="pdf">pdf</option>
                                <option value="image">image</option>
                                <option value="video">video</option>
                                <option value="doc">doc</option>
                                <option value="link">link</option>
                            </select>
                        </div>
                    </div>
                    <div class="wf-field">
                        <label>URL</label>
                        <input name="material_url" placeholder="https://..." value="{{ old('material_url') }}" required>
                    </div>
                    <div class="wf-field">
                        <label>Not (opsiyonel)</label>
                        <textarea name="note" placeholder="opsiyonel not">{{ old('note') }}</textarea>
                    </div>
                    <button type="submit" class="btn ok">Materyali Kuyruğa Al</button>
                </form>
            </details>

        </div>

    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Bayi İlişkileri</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Dealer listesinde performans, dönüşüm ve kazanç özetlerini takip et.</li>
            <li>Detay ekranıyla tek bayinin öğrenci/revenue/tracking kayıtlarına in.</li>
            <li>Toplu duyuru ve materyal paylaşımı aksiyonları Notification kuyruğuna iş emri yazar.</li>
            <li>Kodları virgülle girerek aynı anda birden fazla bayi hedefleyebilirsin.</li>
        </ol>
    </details>

</div>
@endsection

@extends('marketing-admin.layouts.app')

@section('title', 'Scoring Kural Yapılandırma')
@section('page_subtitle', 'Scoring Kural Yapılandırma — puan eşikleri ve aktivite kuralları')

@section('topbar-actions')
<a class="btn {{ request()->is('mktg-admin/scoring') && !request()->is('mktg-admin/scoring/*') ? '' : 'alt' }}" href="/mktg-admin/scoring" style="font-size:var(--tx-xs);padding:6px 12px;">Genel Bakış</a>
<a class="btn {{ request()->is('mktg-admin/scoring/leaderboard') ? '' : 'alt' }}" href="/mktg-admin/scoring/leaderboard" style="font-size:var(--tx-xs);padding:6px 12px;">Liderlik Tablosu</a>
<a class="btn {{ request()->is('mktg-admin/scoring/config') ? '' : 'alt' }}" href="/mktg-admin/scoring/config" style="font-size:var(--tx-xs);padding:6px 12px;">Kural Yapılandırma</a>
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

/* Tier bar */
.tier-bar { display:flex; border-radius:10px; overflow:hidden; border:1px solid var(--u-line,#e2e8f0); }
.tier-seg { flex:1; padding:12px 14px; display:flex; flex-direction:column; gap:3px; }
.tier-seg + .tier-seg { border-left:1px solid rgba(255,255,255,.25); }
.tier-seg-name  { font-size:12px; font-weight:700; color:#fff; }
.tier-seg-range { font-size:11px; color:rgba(255,255,255,.75); }

/* Rules table */
.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }
.tl-tbl tr.edit-row td { background:var(--u-bg,#f8fafc); padding:12px; border-bottom:1px solid var(--u-line,#e2e8f0); }
</style>

<div style="display:grid;gap:12px;">

    {{-- Tier Görsel Bar --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Scoring Eşikleri</div>
        <div class="tier-bar">
            <div class="tier-seg" style="background:#64748b;">
                <div class="tier-seg-name">Cold</div>
                <div class="tier-seg-range">0 – 19 puan</div>
            </div>
            <div class="tier-seg" style="background:#7c3aed;">
                <div class="tier-seg-name">Warm</div>
                <div class="tier-seg-range">20 – 49 puan</div>
            </div>
            <div class="tier-seg" style="background:#d97706;">
                <div class="tier-seg-name">Hot</div>
                <div class="tier-seg-range">50 – 79 puan</div>
            </div>
            <div class="tier-seg" style="background:#0891b2;">
                <div class="tier-seg-name">Sales Ready</div>
                <div class="tier-seg-range">80 – 99 puan</div>
            </div>
            <div class="tier-seg" style="background:#16a34a;">
                <div class="tier-seg-name">Champion</div>
                <div class="tier-seg-range">100+ puan</div>
            </div>
        </div>
    </div>

    {{-- Kategori Kuralları --}}
    @foreach($categories as $catKey => $catLabel)
    @php $catRules = $rules->where('category', $catKey); @endphp
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            {{ $catLabel }} Kuralları
            <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">{{ $catRules->count() }} kural</span>
        </div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Kural</th>
                    <th style="width:80px;text-align:right;">Puan</th>
                    <th style="width:90px;text-align:right;">Günlük Max</th>
                    <th style="width:80px;text-align:center;">Tek Sefer</th>
                    <th style="width:70px;text-align:center;">Durum</th>
                    <th style="width:80px;text-align:right;"></th>
                </tr></thead>
                <tbody>
                    @foreach($catRules as $rule)
                    <tr id="row-{{ $rule->id }}">
                        <td>
                            <div style="font-weight:600;">{{ $rule->label }}</div>
                            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-top:2px;font-family:ui-monospace,monospace;">{{ $rule->action_code }}</div>
                        </td>
                        <td style="text-align:right;">
                            <span style="font-weight:700;font-size:var(--tx-base);color:{{ $rule->points >= 0 ? 'var(--u-ok,#16a34a)' : 'var(--u-danger,#dc2626)' }}">
                                {{ $rule->points >= 0 ? '+' : '' }}{{ $rule->points }}
                            </span>
                        </td>
                        <td style="text-align:right;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                            {{ $rule->max_per_day ?? '∞' }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $rule->is_one_time ? 'info' : 'pending' }}">
                                {{ $rule->is_one_time ? 'Evet' : 'Hayır' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $rule->is_active ? 'ok' : 'danger' }}">
                                {{ $rule->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <button onclick="toggleEdit({{ $rule->id }})" id="edit-btn-{{ $rule->id }}"
                                    class="btn alt" style="padding:3px 10px;font-size:var(--tx-xs);">Düzenle</button>
                        </td>
                    </tr>
                    <tr id="edit-{{ $rule->id }}" style="display:none;" class="edit-row">
                        <td colspan="6">
                            <form method="POST" action="/mktg-admin/scoring/config/{{ $rule->id }}"
                                  style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                                @csrf @method('PUT')
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted,#64748b);">Puan</label>
                                    <input type="number" name="points" value="{{ $rule->points }}" min="-100" max="100"
                                           style="width:80px;height:34px;padding:0 8px;border:1px solid var(--u-line,#e2e8f0);border-radius:6px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-sm);outline:none;">
                                </div>
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted,#64748b);">Günlük Max</label>
                                    <input type="number" name="max_per_day" value="{{ $rule->max_per_day ?? '' }}" min="1" max="100" placeholder="∞"
                                           style="width:80px;height:34px;padding:0 8px;border:1px solid var(--u-line,#e2e8f0);border-radius:6px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-sm);outline:none;">
                                </div>
                                <div style="display:flex;align-items:center;gap:6px;padding-bottom:2px;">
                                    <input type="checkbox" name="is_active" id="active-{{ $rule->id }}" value="1" {{ $rule->is_active ? 'checked' : '' }}
                                           style="accent-color:var(--u-brand,#1e40af);width:14px;height:14px;">
                                    <label for="active-{{ $rule->id }}" style="font-size:var(--tx-xs);cursor:pointer;">Aktif</label>
                                </div>
                                <button type="submit" class="btn ok" style="padding:5px 14px;font-size:var(--tx-xs);">Kaydet</button>
                                <button type="button" onclick="toggleEdit({{ $rule->id }})"
                                        class="btn alt" style="padding:5px 12px;font-size:var(--tx-xs);">İptal</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if($catRules->isEmpty())
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Bu kategoride kural yok.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Scoring Kural Rehberi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li><strong>Puan Eşikleri:</strong> Cold (0–19) → Warm (20–49) → Hot (50–79) → Sales Ready (80–99) → Champion (100+).</li>
            <li><strong>Günlük Max:</strong> Aynı aktiviteden aynı günde kazanılabilecek maksimum puan sayısı. Boş bırakılırsa sınırsız.</li>
            <li><strong>Tek Sefer:</strong> "Evet" ise bu aktiviteden lead başına yalnızca bir kez puan kazanılır (ör. ilk kayıt).</li>
            <li><strong>Aktif/Pasif:</strong> Pasif kural puanlama motorundan çıkar; mevcut lead puanlarını etkilemez.</li>
            <li>Düzenle butonuyla satır içi form açılır; değişiklik anlık olarak kaydedilir.</li>
        </ol>
    </details>

</div>

<script>
function toggleEdit(id) {
    var row   = document.getElementById('edit-' + id);
    var btn   = document.getElementById('edit-btn-' + id);
    var shown = row.style.display !== 'none';
    row.style.display = shown ? 'none' : 'table-row';
    btn.textContent   = shown ? 'Düzenle' : 'Kapat';
}
</script>
@endsection

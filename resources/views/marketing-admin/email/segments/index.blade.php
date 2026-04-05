@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn alt" href="/mktg-admin/email/templates" style="font-size:var(--tx-xs);padding:6px 12px;">Templates</a>
<a class="btn {{ request()->is('mktg-admin/email/segments*') ? '' : 'alt' }}" href="/mktg-admin/email/segments" style="font-size:var(--tx-xs);padding:6px 12px;">Segments</a>
<a class="btn alt" href="/mktg-admin/email/campaigns" style="font-size:var(--tx-xs);padding:6px 12px;">Campaigns</a>
<a class="btn alt" href="/mktg-admin/email/log" style="font-size:var(--tx-xs);padding:6px 12px;">Send Log</a>
@endsection

@section('title', 'E-posta Segmentleri')
@section('page_subtitle', 'Hedef kitle grupları — filtre tabanlı dinamik ve manuel segmentler')

@section('content')
@php
$isEdit      = !empty($editing);
$action      = $isEdit ? '/mktg-admin/email/segments/'.$editing->id : '/mktg-admin/email/segments';
$membersValue = old('member_user_ids', $isEdit ? implode(',', (array)($editing->member_user_ids ?? [])) : '');
$rulesValue   = old('rules_text', $isEdit
    ? json_encode((array)($editing->rules ?? []), JSON_UNESCAPED_UNICODE)
    : '{"role":"student","is_active":true}');
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
.tl-tbl  { width:100%; border-collapse:collapse; min-width:820px; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input, .wf-field select, .wf-field textarea { border:1px solid var(--u-line,#e2e8f0); border-radius:8px; padding:0 10px; height:36px; background:var(--u-card,#fff); color:var(--u-text,#0f172a); font-size:13px; outline:none; font-family:inherit; width:100%; box-sizing:border-box; }
.wf-field textarea { height:80px; padding:8px 10px; resize:vertical; }
.wf-field input:focus, .wf-field select:focus, .wf-field textarea:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }

.member-list { border:1px solid var(--u-line,#e2e8f0); border-radius:8px; max-height:140px; overflow-y:auto; background:var(--u-bg,#f8fafc); padding:6px 8px; }
.member-item { font-size:12px; color:var(--u-text,#0f172a); padding:4px 6px; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:6px; }
.member-item:hover { background:color-mix(in srgb,var(--u-brand,#1e40af) 8%,var(--u-card,#fff)); }
.member-item code { font-size:11px; color:var(--u-brand,#1e40af); background:color-mix(in srgb,var(--u-brand,#1e40af) 8%,var(--u-card,#fff)); padding:1px 5px; border-radius:4px; }
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
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ $stats['total'] ?? 0 }}</div>
            <div class="pl-lbl">Toplam Segment</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-ok,#16a34a);">{{ $stats['active'] ?? 0 }}</div>
            <div class="pl-lbl">Aktif</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ $stats['manual'] ?? 0 }}</div>
            <div class="pl-lbl">Manuel</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:#7c3aed;">{{ $stats['dynamic'] ?? 0 }}</div>
            <div class="pl-lbl">Dinamik</div>
        </div>
    </div>

    {{-- Ana Grid --}}
    <div style="display:grid;grid-template-columns:360px 1fr;gap:12px;align-items:start;">

        {{-- Sol: Form --}}
        <div style="display:grid;gap:12px;">

            <details class="card" {{ $isEdit ? 'open' : '' }}>
                <summary class="det-sum">
                    <h3>{{ $isEdit ? 'Segment Düzenle #'.$editing->id : '+ Yeni Segment' }}</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <form method="POST" action="{{ $action }}" style="display:grid;gap:8px;">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="wf-field">
                        <label>Segment Adı</label>
                        <input name="name" placeholder="Segment adı" value="{{ old('name', $editing->name ?? '') }}" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div class="wf-field">
                            <label>Tip</label>
                            <select name="type" required>
                                @foreach(($typeOptions ?? ['manual','dynamic']) as $tp)
                                <option value="{{ $tp }}" @selected(old('type', $editing->type ?? 'manual') === $tp)>{{ $tp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wf-field">
                            <label>Durum</label>
                            <select name="is_active">
                                <option value="1" @selected((string)old('is_active', isset($editing) ? (int)$editing->is_active : 1) === '1')>Aktif</option>
                                <option value="0" @selected((string)old('is_active', isset($editing) ? (int)$editing->is_active : 1) === '0')>Pasif</option>
                            </select>
                        </div>
                    </div>
                    <div class="wf-field">
                        <label>Açıklama (opsiyonel)</label>
                        <input name="description" placeholder="Segment açıklaması" value="{{ old('description', $editing->description ?? '') }}">
                    </div>
                    <div class="wf-field">
                        <label>Dinamik Kural (JSON)</label>
                        <textarea name="rules_text" placeholder='{"role":"student","is_active":true}'>{{ $rulesValue }}</textarea>
                    </div>
                    <div class="wf-field">
                        <label>Manuel Üye ID'leri (virgülle)</label>
                        <input name="member_user_ids" id="memberUserIds" placeholder="1,2,3,..." value="{{ $membersValue }}">
                    </div>
                    <div class="wf-field">
                        <label>Zoho Liste ID (opsiyonel)</label>
                        <input name="zoho_list_id" placeholder="zoho_list_id" value="{{ old('zoho_list_id', $editing->zoho_list_id ?? '') }}">
                    </div>
                    <div class="wf-field">
                        <label>Zoho Sync</label>
                        <select name="zoho_synced">
                            <option value="0" @selected((string)old('zoho_synced', isset($editing) ? (int)$editing->zoho_synced : 0) === '0')>Hayır</option>
                            <option value="1" @selected((string)old('zoho_synced', isset($editing) ? (int)$editing->zoho_synced : 0) === '1')>Evet</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:2px;">
                        <button type="submit" class="btn ok">{{ $isEdit ? 'Güncelle' : 'Segment Ekle' }}</button>
                        <a href="/mktg-admin/email/segments" class="btn alt">Temizle</a>
                    </div>
                </form>
            </details>

            {{-- Kullanıcı Listesi --}}
            <details class="card">
                <summary class="det-sum">
                    <h3>Kullanıcı Listesi</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <p style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin:0 0 8px;">Tıklayarak ID'yi otomatik ekle</p>
                <div class="member-list">
                    @forelse(($userOptions ?? []) as $u)
                    <div class="member-item" onclick="appendMemberId('{{ $u->id }}')">
                        <code>#{{ $u->id }}</code>
                        <span>{{ $u->name }}</span>
                        <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $u->role }}</span>
                        <span class="badge {{ $u->is_active ? 'ok' : 'danger' }}" style="font-size:var(--tx-xs);margin-left:auto;">{{ $u->is_active ? 'aktif' : 'pasif' }}</span>
                    </div>
                    @empty
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);padding:8px;">Kullanıcı bulunamadı.</div>
                    @endforelse
                </div>
            </details>

            {{-- Rehber --}}
            <details class="card">
                <summary class="det-sum">
                    <h3>Kullanım Rehberi</h3>
                    <span class="det-chev">▼</span>
                </summary>
                <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
                    <li><strong>Manuel:</strong> member_user_ids alanına ID'leri virgülle gir veya kullanıcı listesinden tıkla.</li>
                    <li><strong>Dinamik:</strong> JSON kural gir — <code style="background:var(--u-bg,#f3f4f6);padding:1px 4px;border-radius:3px;">{"role":"student","is_active":true}</code></li>
                    <li>Önizle ile segmentin gerçek üye sayısını ve listesini kontrol et.</li>
                    <li>Kampanyada kullanılan segment otomatik pasife çekilemez — önce kampanyadan kaldır.</li>
                </ol>
            </details>

        </div>

        {{-- Sağ: Liste --}}
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);flex-wrap:wrap;">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Segment Listesi</div>
                <form method="GET" action="/mktg-admin/email/segments" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ad / açıklama ara"
                        style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;min-width:140px;">
                    <select name="type" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                        <option value="all" @selected(($filters['type']??'all')==='all')>Tüm tipler</option>
                        <option value="manual"  @selected(($filters['type']??'all')==='manual')>manuel</option>
                        <option value="dynamic" @selected(($filters['type']??'all')==='dynamic')>dinamik</option>
                    </select>
                    <select name="status" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                        <option value="all"     @selected(($filters['status']??'all')==='all')>Tüm durumlar</option>
                        <option value="active"  @selected(($filters['status']??'all')==='active')>aktif</option>
                        <option value="passive" @selected(($filters['status']??'all')==='passive')>pasif</option>
                    </select>
                    <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                    <a href="/mktg-admin/email/segments" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;color:var(--u-muted,#64748b);">Temizle</a>
                </form>
            </div>

            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th style="width:40px;">ID</th>
                        <th>Segment</th>
                        <th style="width:80px;">Tip</th>
                        <th style="width:70px;">Durum</th>
                        <th style="width:70px;text-align:right;">Üye</th>
                        <th>Kural</th>
                        <th style="width:130px;">İşlem</th>
                    </tr></thead>
                    <tbody>
                        @forelse(($rows ?? []) as $row)
                        <tr>
                            <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);font-family:ui-monospace,monospace;">#{{ $row->id }}</td>
                            <td>
                                <strong>{{ $row->name }}</strong>
                                @if($row->description)
                                <br><span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->description }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $row->type === 'dynamic' ? 'info' : '' }}" style="font-size:var(--tx-xs);">
                                    {{ $row->type }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $row->is_active ? 'ok' : 'danger' }}" style="font-size:var(--tx-xs);">
                                    {{ $row->is_active ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td style="text-align:right;font-weight:600;">{{ (int)$row->estimated_size }}</td>
                            <td style="font-size:var(--tx-xs);">
                                @if($row->type === 'dynamic' && !empty($row->rules))
                                    @foreach((array)$row->rules as $k => $v)
                                    <span style="display:inline-block;background:color-mix(in srgb,var(--u-brand,#1e40af) 8%,var(--u-card,#fff));color:var(--u-brand,#1e40af);border-radius:4px;padding:1px 5px;font-size:var(--tx-xs);font-family:ui-monospace,monospace;margin:1px;">{{ $k }}:{{ is_bool($v) ? ($v?'true':'false') : $v }}</span>
                                    @endforeach
                                @elseif($row->type === 'manual')
                                    <span style="color:var(--u-muted,#64748b);">{{ count((array)($row->member_user_ids ?? [])) }} ID seçili</span>
                                @else
                                    <span style="color:var(--u-muted,#64748b);">—</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                    <a class="btn alt" href="/mktg-admin/email/segments?edit_id={{ $row->id }}" style="font-size:var(--tx-xs);padding:4px 8px;">Düzenle</a>
                                    <a class="btn" href="/mktg-admin/email/segments/{{ $row->id }}/preview" style="font-size:var(--tx-xs);padding:4px 8px;">Önizle</a>
                                    <form method="POST" action="/mktg-admin/email/segments/{{ $row->id }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn warn" style="font-size:var(--tx-xs);padding:4px 8px;" onclick="return confirm('Segment silinsin mi?')">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" style="text-align:center;padding:28px;color:var(--u-muted,#64748b);">Segment kaydı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;">{{ $rows->links() }}</div>
        </div>

    </div>

</div>

<script defer src="{{ Vite::asset('resources/js/csv-field.js') }}" defer></script>
<script defer src="{{ Vite::asset('resources/js/marketing-email-segments.js') }}" defer></script>

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — E-posta Segmentleri</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🎯 Segment Türleri</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>static:</strong> Manuel CSV yükleme veya sabit liste — değişmez</li>
                <li><strong>dynamic:</strong> Kural tabanlı — yeni uygun kayıtlar otomatik eklenir</li>
                <li>Kampanya göndermeden önce "Önizle" ile üye sayısını doğrula</li>
                <li>Çok geniş segment → düşük engagement; çok dar → düşük erişim</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📋 Kural Örnekleri</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Durum = "new" + kayıt tarihi son 30 gün → Yeni lead serisi</li>
                <li>Lead skoru 50+ → Warm lead kampanyası</li>
                <li>Ülke = Almanya + program = master → Hedefli içerik</li>
                <li>Son e-posta açılmadı (30+ gün) → Re-engagement serisi</li>
            </ul>
        </div>
    </div>
</details>
@endsection

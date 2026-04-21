@extends('marketing-admin.layouts.app')

@section('title', 'Ekip Yönetimi')
@section('page_subtitle', 'Ekip Yönetimi — marketing/sales kullanıcıları, roller ve yetki yönetimi')

@section('content')

@include('partials.manager-hero', [
    'label' => 'Ekip & Yetki',
    'title' => 'Ekip Yönetimi',
    'sub'   => 'Marketing ve sales ekibinin üyeleri, rolleri ve yetki grupları. Yeni üye ekle, rol ata, izin setlerini yönet.',
    'icon'  => '👥',
    'bg'    => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1400&q=80',
    'tone'  => 'indigo',
    'stats' => [],
])

<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }
.tl-tbl tr.edit-row td { background:var(--u-bg,#f8fafc); padding:14px 12px; border-bottom:1px solid var(--u-line,#e2e8f0); }

/* Avatar */
.av { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; flex-shrink:0; }

/* Role badge renkler */
.role-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:700; }
.role-marketing_admin  { background:color-mix(in srgb,var(--u-brand,#1e40af) 14%,transparent); color:var(--u-brand,#1e40af); }
.role-sales_admin      { background:color-mix(in srgb,#0891b2 14%,transparent); color:#0891b2; }
.role-marketing_staff  { background:color-mix(in srgb,var(--u-warn,#d97706) 14%,transparent); color:var(--u-warn,#d97706); }
.role-sales_staff      { background:color-mix(in srgb,#64748b 14%,transparent); color:#64748b; }

/* Invite form fields */
.tm-field { display:flex; flex-direction:column; gap:4px; }
.tm-field label { font-size:11px; font-weight:600; color:var(--u-muted,#64748b); }
.tm-field input, .tm-field select {
    width:100%; box-sizing:border-box; height:34px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.tm-field input:focus, .tm-field select:focus { border-color:var(--u-brand,#1e40af); }

/* Permission wrap */
.perm-wrap { border:1px solid var(--u-line,#e2e8f0); border-radius:8px; padding:8px; max-height:200px; overflow:auto; background:var(--u-card,#fff); }
.perm-group { margin-bottom:8px; }
.perm-group:last-child { margin-bottom:0; }
.perm-title { font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.perm-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:3px; }
.perm-item { display:inline-flex; gap:5px; align-items:center; font-size:11px; cursor:pointer; }
.perm-item input[type="checkbox"] { width:13px; height:13px; accent-color:var(--u-brand,#1e40af); }
</style>

@php
$roleColors = [
    'marketing_admin'  => '#1e40af',
    'sales_admin'      => '#0891b2',
    'marketing_staff'  => '#d97706',
    'sales_staff'      => '#64748b',
];
$roleLabels = [
    'marketing_admin'  => 'Mktg Admin',
    'sales_admin'      => 'Sales Admin',
    'marketing_staff'  => 'Mktg Staff',
    'sales_staff'      => 'Sales Staff',
];
@endphp

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

    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:12px;align-items:start;">

        {{-- SOL: Üye Tablosu --}}
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">
                    Ekip Üyeleri
                    <span class="badge info" style="margin-left:6px;">{{ count($teams ?? []) }}</span>
                </div>
            </div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th style="width:36px;"></th>
                        <th>Kullanıcı</th>
                        <th style="width:120px;">Rol</th>
                        <th>Yetkiler</th>
                        <th style="width:70px;text-align:center;">Durum</th>
                        <th style="width:110px;text-align:right;"></th>
                    </tr></thead>
                    <tbody>
                        @forelse(($teams ?? []) as $team)
                        @php
                            $user = $team->user;
                            $rawPerms = is_array($team->permissions) ? $team->permissions : [];
                            if (array_is_list($rawPerms)) {
                                $permCodes = array_values(array_filter(array_map('strval', $rawPerms)));
                            } else {
                                $permCodes = collect($rawPerms)->filter(fn ($v) => (bool) $v)->keys()->map(fn ($v) => (string) $v)->values()->all();
                            }
                            $initials = strtoupper(substr($user?->name ?? '?', 0, 1));
                            $avColor  = $roleColors[$team->role] ?? '#64748b';
                            $permShow = array_slice($permCodes, 0, 3);
                            $permMore = count($permCodes) - count($permShow);
                        @endphp
                        {{-- Özet satır --}}
                        <tr id="row-{{ $team->user_id }}">
                            <td>
                                <div class="av" style="background:{{ $avColor }};">{{ $initials }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:var(--tx-sm);">{{ $user?->name ?: 'Kullanıcı silinmiş' }}</div>
                                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $user?->email ?: '—' }}</div>
                            </td>
                            <td>
                                <span class="role-badge role-{{ $team->role }}">
                                    {{ $roleLabels[$team->role] ?? $team->role }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;flex-wrap:wrap;gap:3px;">
                                    @foreach($permShow as $p)
                                    <span class="badge info" style="font-size:var(--tx-xs);padding:1px 6px;">{{ $p }}</span>
                                    @endforeach
                                    @if($permMore > 0)
                                    <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);align-self:center;">+{{ $permMore }}</span>
                                    @endif
                                    @if(empty($permCodes))
                                    <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">yetki yok</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align:center;">
                                @if($user?->is_active ?? true)
                                <span class="badge ok" style="font-size:var(--tx-xs);">aktif</span>
                                @else
                                <span class="badge danger" style="font-size:var(--tx-xs);">pasif</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:4px;justify-content:flex-end;">
                                    <button onclick="toggleEdit('{{ $team->user_id }}')" id="edit-btn-{{ $team->user_id }}"
                                            class="btn alt" style="padding:3px 10px;font-size:var(--tx-xs);">Düzenle</button>
                                    <form method="POST" action="/mktg-admin/team/{{ $team->user_id }}" onsubmit="return confirmRemove(this)" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="btn warn" style="padding:3px 8px;font-size:var(--tx-xs);">Kaldır</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        {{-- Düzenleme satırı (gizli) --}}
                        <tr id="edit-{{ $team->user_id }}" style="display:none;" class="edit-row">
                            <td colspan="6">
                                <form method="POST" action="/mktg-admin/team/{{ $team->user_id }}/permissions">
                                    @csrf @method('PUT')
                                    <div style="display:grid;grid-template-columns:160px 1fr;gap:12px;align-items:start;">
                                        <div style="display:flex;flex-direction:column;gap:8px;">
                                            <div class="tm-field">
                                                <label>Rol</label>
                                                <select name="role" style="height:34px;">
                                                    @foreach(($roleOptions ?? []) as $roleCode)
                                                    <option value="{{ $roleCode }}" @selected($roleCode === $team->role)>{{ $roleLabels[$roleCode] ?? $roleCode }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                                                ID: <code style="font-size:var(--tx-xs);">{{ $team->user_id }}</code>
                                            </div>
                                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                                <button type="submit" class="btn ok" style="padding:5px 14px;font-size:var(--tx-xs);">Kaydet</button>
                                                <button type="button" onclick="toggleEdit('{{ $team->user_id }}')" class="btn alt" style="padding:5px 10px;font-size:var(--tx-xs);">İptal</button>
                                            </div>
                                            {{-- Pasif yap --}}
                                        </div>
                                        <div>
                                            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted,#64748b);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Yetkiler</div>
                                            <div class="perm-wrap">
                                                @foreach(($permissionCatalog ?? collect()) as $category => $items)
                                                <div class="perm-group">
                                                    <div class="perm-title">{{ $category }}</div>
                                                    <div class="perm-grid">
                                                        @foreach($items as $item)
                                                        @php $code = (string) $item['code']; @endphp
                                                        <label class="perm-item">
                                                            <input type="checkbox" name="permissions[]" value="{{ $code }}" @checked(in_array($code, $permCodes, true))>
                                                            <span title="{{ $item['description'] ?? '' }}">{{ $code }}</span>
                                                        </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Pasif yapma aksiyonu --}}
                                    @if($user?->is_active ?? true)
                                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--u-line,#e2e8f0);">
                                        <form method="POST" action="/mktg-admin/team/{{ $team->user_id }}" onsubmit="return confirmDeactivate()">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="btn warn" style="padding:4px 12px;font-size:var(--tx-xs);">Hesabı Pasif Yap & Kaldır</button>
                                        </form>
                                    </div>
                                    @endif
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--u-muted,#64748b);">Ekip kullanıcısı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SAĞ: Davet Formu --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                Yeni Üye Davet
            </div>
            <form method="POST" action="/mktg-admin/team/invite" style="display:flex;flex-direction:column;gap:8px;">
                @csrf
                <div class="tm-field">
                    <label>Ad Soyad *</label>
                    <input name="name" placeholder="Ad Soyad" value="{{ old('name') }}" required>
                </div>
                <div class="tm-field">
                    <label>E-posta *</label>
                    <input type="email" name="email" placeholder="ornek@domain.com" value="{{ old('email') }}" required>
                </div>
                <div class="tm-field">
                    <label>Rol *</label>
                    <select name="role" required id="inviteRole">
                        @foreach(($roleOptions ?? []) as $roleCode)
                        <option value="{{ $roleCode }}" @selected(old('role','marketing_staff') === $roleCode)>{{ $roleLabels[$roleCode] ?? $roleCode }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tm-field">
                    <label>Yetkiler <span style="font-weight:400;color:var(--u-muted,#64748b);">(boş → role varsayılanı)</span></label>
                    <div class="perm-wrap">
                        @foreach(($permissionCatalog ?? collect()) as $category => $items)
                        <div class="perm-group">
                            <div class="perm-title">{{ $category }}</div>
                            <div class="perm-grid">
                                @foreach($items as $item)
                                @php $code = (string) $item['code']; @endphp
                                <label class="perm-item">
                                    <input type="checkbox" name="permissions[]" value="{{ $code }}" @checked(in_array($code, old('permissions', []), true))>
                                    <span title="{{ $item['description'] ?? '' }}">{{ $code }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">Yeni kullanıcıysa şifre otomatik oluşturulur.</div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn ok" style="flex:1;">Davet Et</button>
                    <a href="/mktg-admin/team" class="btn alt">Yenile</a>
                </div>
            </form>
        </div>

    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Ekip Yönetimi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Ad, e-posta ve rol seçip <strong>Davet Et</strong> butonuyla yeni üye ekle. Yetkiler boş bırakılırsa role varsayılan yetkileri atanır.</li>
            <li>Üye satırında <strong>Düzenle</strong>'ye tıkla → satır içi form açılır, rol ve yetkiler güncellenebilir.</li>
            <li><strong>Kaldır:</strong> Ekip kaydını siler ve kullanıcıyı öğrenci rolüne çeker (hesap aktif kalır).</li>
            <li><strong>Hesabı Pasif Yap & Kaldır:</strong> Ekipten çıkarır + hesabı devre dışı bırakır (giriş yapamaz).</li>
            <li>Rol renkleri: <span style="color:#1e40af;font-weight:600;">Mktg Admin</span> · <span style="color:#0891b2;font-weight:600;">Sales Admin</span> · <span style="color:#d97706;font-weight:600;">Mktg Staff</span> · <span style="color:#64748b;font-weight:600;">Sales Staff</span></li>
        </ol>
    </details>

</div>

<script>
function toggleEdit(uid) {
    var row = document.getElementById('edit-' + uid);
    var btn = document.getElementById('edit-btn-' + uid);
    var shown = row.style.display !== 'none';
    // Close all others
    document.querySelectorAll('.edit-row').forEach(function(r) { r.style.display = 'none'; });
    document.querySelectorAll('[id^="edit-btn-"]').forEach(function(b) { b.textContent = 'Düzenle'; });
    if (!shown) {
        row.style.display = 'table-row';
        btn.textContent = 'Kapat';
    }
}
function confirmRemove(form) {
    return confirm('Bu kullanıcı ekipten çıkarılacak (rol öğrenciye düşer). Emin misiniz?');
}
function confirmDeactivate() {
    return confirm('Bu kullanıcı ekipten çıkarılacak VE hesabı pasif yapılacak. Emin misiniz?');
}
</script>
@endsection

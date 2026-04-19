@extends('manager.layouts.app')
@section('title', 'Rol Yönetimi')
@section('page_title', 'Rol Yönetimi')

@push('head')
<style>
.role-tree-card { background:var(--u-card);border:1.5px solid var(--u-line);border-radius:12px;padding:14px 16px;position:relative; }
.role-tree-card.highlight { border-color:#1e40af;background:#eff6ff; }
.role-chip { display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;border:1.5px solid var(--u-line);background:var(--u-bg);color:var(--u-text);transition:all .12s; }
.role-chip:hover { border-color:#1e40af;color:#1e40af; }
.role-chip.admin { border-color:#1e40af;background:#eff6ff;color:#1e40af; }
.role-chip.staff { border-color:#6366f1;background:#f5f3ff;color:#4f46e5; }
.role-chip.portal { border-color:#0891b2;background:#e0f2fe;color:#0369a1; }
.perm-tag { display:inline-block;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;background:var(--u-bg);border:1px solid var(--u-line);color:var(--u-muted);font-family:monospace; }
.perm-tag.has { background:#dcfce7;border-color:#bbf7d0;color:#15803d; }
.tpl-card { background:var(--u-card);border:1.5px solid var(--u-line);border-radius:10px;padding:12px 14px;transition:all .12s; }
.tpl-card:hover { border-color:#1e40af;transform:translateY(-1px); }
.user-row { display:flex;align-items:center;gap:10px;padding:8px 14px;border-bottom:1px solid var(--u-line); }
.user-row:last-child { border-bottom:none; }
.avatar { width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px;flex-shrink:0; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;gap:6px;align-items:center;margin-bottom:14px;font-size:11px;color:var(--u-muted);">
    <a href="/manager/system" style="color:#1e40af;text-decoration:none;font-weight:700;">Sistem Paneli</a>
    <span>›</span><span>Rol Yönetimi</span>
</div>

{{-- ─── Rol Hiyerarşisi ─── --}}
<section class="panel" style="padding:0;overflow:hidden;margin-bottom:14px;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
        <div style="font-weight:700;font-size:var(--tx-sm);">🏗 Rol Hiyerarşisi</div>
        <div style="font-size:11px;color:var(--u-muted);">{{ $userCountByRole->sum() }} iç kullanıcı</div>
    </div>
    <div style="padding:16px;display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
    @foreach($roleGroups as $group)
    @php
        $parentCount   = $userCountByRole[$group['parent']] ?? 0;
        $childrenCount = collect($group['children'])->sum(fn($r) => $userCountByRole[$r] ?? 0);
        $groupColor = match($group['key']) {
            'manager'    => '#1e40af',
            'system'     => '#0891b2',
            'operations' => '#7c3aed',
            'finance'    => '#16a34a',
            'marketing'  => '#d97706',
            'sales'      => '#dc2626',
            'advisor'    => '#6366f1',
            'portal'     => '#94a3b8',
            default      => '#6b7280',
        };
    @endphp
    <div class="role-tree-card" style="border-top:3px solid {{ $groupColor }};">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">{{ $group['title'] }}</div>

        {{-- Parent Rol --}}
        <a href="/manager/system/roles?filter={{ $group['parent'] }}"
           style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:{{ $groupColor }}18;border:1.5px solid {{ $groupColor }}40;border-radius:8px;text-decoration:none;margin-bottom:6px;">
            <span style="font-size:12px;font-weight:700;color:{{ $groupColor }};">{{ str_replace('_', ' ', $group['parent']) }}</span>
            <span style="font-size:14px;font-weight:900;color:{{ $groupColor }};">{{ $parentCount }}</span>
        </a>

        {{-- Children Roller --}}
        @foreach($group['children'] as $child)
        @php $childCount = $userCountByRole[$child] ?? 0; @endphp
        <a href="/manager/system/roles?filter={{ $child }}"
           style="display:flex;align-items:center;justify-content:space-between;padding:5px 10px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:7px;text-decoration:none;margin-bottom:4px;margin-left:12px;">
            <span style="font-size:11px;font-weight:600;color:var(--u-muted);">↳ {{ str_replace('_', ' ', $child) }}</span>
            <span style="font-size:12px;font-weight:800;color:var(--u-muted);">{{ $childCount }}</span>
        </a>
        @endforeach
    </div>
    @endforeach
    </div>
</section>

{{-- ─── Yeni Şablon Oluştur ─── --}}
@if(session('status'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#15803d;">{{ session('status') }}</div>
@endif
<section class="panel" style="margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0;" id="newTplToggle">
        <div style="font-weight:700;font-size:var(--tx-sm);">➕ Yeni Şablon Oluştur</div>
        <button type="button" id="newTplBtn" style="font-size:11px;padding:4px 12px;border:1.5px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-muted);cursor:pointer;font-weight:700;">Göster</button>
    </div>
    <div id="newTplForm" style="display:none;margin-top:14px;">
        <form method="POST" action="/manager/system/roles" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            @csrf
            <div style="flex:1;min-width:180px;">
                <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Şablon Adı</label>
                <input type="text" name="name" required placeholder="ör. Operasyon Staff — Genişletilmiş"
                       style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;">
            </div>
            <div style="min-width:180px;">
                <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Ana Rol</label>
                <select name="parent_role" required style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                    <option value="">— Rol seçin —</option>
                    @php $roleLabels = [
                        'manager'=>'Manager','senior'=>'Eğitim Danışmanı','mentor'=>'Mentor',
                        'system_admin'=>'Sistem Admin','system_staff'=>'Sistem Staff',
                        'operations_admin'=>'Operasyon Admin','operations_staff'=>'Operasyon Staff',
                        'finance_admin'=>'Finans Admin','finance_staff'=>'Finans Staff',
                        'marketing_admin'=>'Pazarlama Admin','marketing_staff'=>'Pazarlama Staff',
                        'sales_admin'=>'Satış Admin','sales_staff'=>'Satış Staff',
                    ]; @endphp
                    @foreach($roleLabels as $r => $lbl)
                    <option value="{{ $r }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn ok" style="padding:8px 20px;">Oluştur</button>
            </div>
        </form>
    </div>
</section>

{{-- ─── Rol Şablonları (Kompakt Liste) ─── --}}
@php
$deptGroups = [
    'manager'    => ['label'=>'Manager',    'color'=>'#1e40af', 'roles'=>['manager']],
    'system'     => ['label'=>'Sistem',     'color'=>'#0891b2', 'roles'=>['system_admin','system_staff']],
    'operations' => ['label'=>'Operasyon',  'color'=>'#7c3aed', 'roles'=>['operations_admin','operations_staff']],
    'finance'    => ['label'=>'Finans',     'color'=>'#16a34a', 'roles'=>['finance_admin','finance_staff']],
    'marketing'  => ['label'=>'Pazarlama',  'color'=>'#d97706', 'roles'=>['marketing_admin','marketing_staff']],
    'sales'      => ['label'=>'Satış',      'color'=>'#dc2626', 'roles'=>['sales_admin','sales_staff']],
    'advisory'   => ['label'=>'Danışmanlık','color'=>'#6366f1', 'roles'=>['senior','mentor']],
];
@endphp
<section class="panel" style="padding:0;overflow:hidden;margin-bottom:14px;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">📋 Rol Şablonları</div>

    @foreach($deptGroups as $deptKey => $dept)
    @php $deptTemplates = $templates->filter(fn($t) => in_array($t->parent_role, $dept['roles']))->sortByDesc('is_system'); @endphp
    @if($deptTemplates->isNotEmpty())
    {{-- Departman başlığı --}}
    <div style="padding:4px 16px;background:{{ $dept['color'] }}0d;border-top:1px solid var(--u-line);border-left:3px solid {{ $dept['color'] }};">
        <span style="font-size:10px;font-weight:800;color:{{ $dept['color'] }};text-transform:uppercase;letter-spacing:.06em;">{{ $dept['label'] }}</span>
    </div>
    @foreach($deptTemplates as $tpl)
    <a href="/manager/system/roles/{{ $tpl->id }}"
       style="display:flex;align-items:center;gap:14px;padding:11px 16px 11px 19px;border-bottom:1px solid var(--u-line);border-left:3px solid {{ $dept['color'] }};text-decoration:none;transition:background .1s;"
       onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
        {{-- İsim + badge --}}
        <div style="width:210px;flex-shrink:0;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text);">{{ $tpl->name }}</div>
            <div style="display:flex;gap:4px;margin-top:3px;">
                @if($tpl->is_system)
                <span style="font-size:10px;background:#0369a1;color:#fff;padding:1px 7px;border-radius:3px;font-weight:700;">SİSTEM</span>
                @else
                <span style="font-size:10px;background:#15803d;color:#fff;padding:1px 7px;border-radius:3px;font-weight:700;">ÖZEL</span>
                @endif
                @if(!$tpl->is_active)<span style="font-size:10px;background:#dc2626;color:#fff;padding:1px 7px;border-radius:3px;font-weight:700;">PASİF</span>@endif
            </div>
        </div>
        {{-- İzin etiketleri --}}
        <div style="flex:1;display:flex;flex-wrap:wrap;gap:4px;min-width:0;">
            @foreach($tpl->permissions->take(6) as $perm)
            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;font-family:monospace;background:#1e40af;color:#fff;">{{ $perm->code }}</span>
            @endforeach
            @if($tpl->permissions->count() > 6)
            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;background:var(--u-line);color:var(--u-muted);">+{{ $tpl->permissions->count() - 6 }} daha</span>
            @endif
            @if($tpl->permissions->isEmpty())
            <span style="font-size:11px;color:var(--u-muted);">İzin tanımlı değil</span>
            @endif
        </div>
        {{-- Kullanıcı sayısı --}}
        <div style="flex-shrink:0;text-align:right;min-width:60px;">
            <span style="font-size:15px;font-weight:800;color:{{ $tpl->active_user_count > 0 ? $dept['color'] : 'var(--u-muted)' }};">{{ $tpl->active_user_count }}</span>
            <div style="font-size:10px;color:var(--u-muted);">kullanıcı</div>
        </div>
        {{-- Detay ok --}}
        <div style="flex-shrink:0;font-size:13px;color:#1e40af;font-weight:700;">→</div>
    </a>
    @endforeach
    @endif
    @endforeach
</section>

{{-- ─── İzin Kataloğu ─── --}}
<section class="panel" style="padding:0;overflow:hidden;margin-bottom:14px;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">🔑 İzin Kataloğu</div>
    <div style="padding:14px 16px;display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
    @foreach($permissions as $category => $perms)
    <div>
        <div style="font-size:10px;font-weight:800;color:var(--u-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">{{ $category }}</div>
        <div style="display:flex;flex-direction:column;gap:4px;">
        @foreach($perms as $perm)
        <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:7px;">
            <code style="font-size:11px;font-weight:700;color:#1e40af;flex-shrink:0;">{{ $perm->code }}</code>
            <span style="font-size:10px;color:var(--u-muted);">{{ $perm->description }}</span>
            @if($perm->is_system)<span style="font-size:9px;background:#e0f2fe;color:#0369a1;padding:1px 5px;border-radius:3px;font-weight:700;margin-left:auto;flex-shrink:0;">SİSTEM</span>@endif
        </div>
        @endforeach
        </div>
    </div>
    @endforeach
    </div>
</section>

{{-- ─── Kullanıcı-Rol Tablosu ─── --}}
@php
    $filterRole = request('filter');
    $displayUsers = $filterRole ? $users->where('role', $filterRole) : $users;
@endphp
<section class="panel" style="padding:0;overflow:hidden;" id="role-table">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:var(--tx-sm);">👥 Kullanıcı — Rol Atamaları
            @if($filterRole) <span style="font-size:11px;font-weight:400;color:#1e40af;">({{ $filterRole }} filtresi)</span>@endif
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="/manager/system/roles#role-table" style="font-size:10px;padding:4px 10px;border:1.5px solid var(--u-line);border-radius:6px;background:{{ !$filterRole ? '#1e40af' : 'var(--u-card)' }};color:{{ !$filterRole ? '#fff' : 'var(--u-muted)' }};text-decoration:none;font-weight:700;">Tümü</a>
            @foreach($userCountByRole->keys() as $r)
            <a href="/manager/system/roles?filter={{ $r }}#role-table" style="font-size:10px;padding:4px 10px;border:1.5px solid var(--u-line);border-radius:6px;background:{{ $filterRole===$r ? '#1e40af' : 'var(--u-card)' }};color:{{ $filterRole===$r ? '#fff' : 'var(--u-muted)' }};text-decoration:none;font-weight:700;">{{ str_replace('_',' ',$r) }}</a>
            @endforeach
        </div>
    </div>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:var(--u-bg);">
                <th style="padding:8px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Kullanıcı</th>
                <th style="padding:8px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Temel Rol</th>
                <th style="padding:8px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Aktif Şablonlar</th>
                <th style="padding:8px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">İzin Sayısı</th>
                <th style="padding:8px 14px;"></th>
            </tr>
        </thead>
        <tbody>
        @foreach($displayUsers as $u)
        @php
            $activeAssignments = $u->roleAssignments->where('is_active', true);
            $permCount = count($u->effectivePermissionCodes());
        @endphp
        <tr style="border-bottom:1px solid var(--u-line);">
            <td style="padding:9px 14px;">
                <div style="font-weight:700;color:var(--u-text);">{{ $u->name ?: '—' }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $u->email }}</div>
            </td>
            <td style="padding:9px 14px;">
                <span style="background:#eff6ff;color:#1e40af;font-size:10px;font-weight:700;padding:3px 8px;border-radius:5px;font-family:monospace;">{{ $u->role }}</span>
            </td>
            <td style="padding:9px 14px;">
                @if($activeAssignments->isEmpty())
                    <span style="font-size:10px;color:var(--u-muted);">Varsayılan (rol tabanlı)</span>
                @else
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                @foreach($activeAssignments as $asgn)
                    <span style="background:#dcfce7;color:#15803d;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;">{{ $asgn->template?->name ?? '?' }}</span>
                @endforeach
                </div>
                @endif
            </td>
            <td style="padding:9px 14px;text-align:center;">
                <span style="font-size:14px;font-weight:800;color:{{ $permCount > 0 ? '#1e40af' : 'var(--u-muted)' }};">{{ $permCount }}</span>
            </td>
            <td style="padding:9px 14px;">
                <a href="/manager/system/roles/users/{{ $u->id }}"
                   style="display:inline-block;padding:4px 10px;font-size:11px;font-weight:600;color:#1e40af;border:1px solid rgba(30,64,175,.3);border-radius:6px;background:rgba(30,64,175,.05);text-decoration:none;">Profil →</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</section>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('newTplBtn')?.addEventListener('click', function() {
    var form = document.getElementById('newTplForm');
    if (form.style.display === 'none') {
        form.style.display = 'block';
        this.textContent = 'Gizle';
    } else {
        form.style.display = 'none';
        this.textContent = 'Göster';
    }
});
</script>
@endpush

@endsection

@extends('manager.layouts.app')

@section('title', 'Personel Yönetimi')
@section('page_title', 'Personel Yönetimi')

@push('head')
<style>
.mgr-kpi-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:700px){ .mgr-kpi-strip { grid-template-columns:1fr; } }
.mgr-kpi { background:var(--u-card); border:1px solid var(--u-line); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; }
.mgr-kpi-label { font-size:10px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mgr-kpi-val   { font-size:22px; font-weight:800; color:var(--u-text); line-height:1; }
.mgr-table { width:100%; border-collapse:collapse; font-size:12px; }
.mgr-table thead tr { background:var(--u-bg); }
.mgr-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.mgr-table tbody tr { border-bottom:1px solid var(--u-line); }
.mgr-table tbody tr:hover { background:rgba(30,64,175,.03); }
.mgr-table td { padding:9px 10px; vertical-align:middle; }
.layer-btn { padding:5px 14px; font-size:11px; font-weight:700; border:1.5px solid var(--u-line); border-radius:7px; background:var(--u-card); color:var(--u-muted); cursor:pointer; text-decoration:none; white-space:nowrap; transition:all .12s; }
.layer-btn.active, .layer-btn:hover { border-color:#1e40af; background:#1e40af; color:#fff; }
.layer-divider td { padding:6px 10px; background:var(--u-bg); font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--u-muted); border-bottom:1px solid var(--u-line); }
</style>
@endpush

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- KPI Strip --}}
<div class="mgr-kpi-strip">
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Toplam</div>
        <div class="mgr-kpi-val">{{ $kpis['total'] }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:#16a34a;">
        <div class="mgr-kpi-label">Aktif</div>
        <div class="mgr-kpi-val" style="color:#16a34a;">{{ $kpis['active'] }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:{{ $kpis['passive'] > 0 ? '#dc2626' : '#e2e8f0' }};">
        <div class="mgr-kpi-label">Pasif</div>
        <div class="mgr-kpi-val" style="{{ $kpis['passive'] > 0 ? 'color:#dc2626;' : '' }}">{{ $kpis['passive'] }}</div>
    </div>
</div>

{{-- Katman Sayıları --}}
@php
$layerMeta = [
    'manager' => ['icon'=>'👑','label'=>'Manager','color'=>'#7c3aed'],
    'admin'   => ['icon'=>'🔑','label'=>'Admin',  'color'=>'#1e40af'],
    'senior'  => ['icon'=>'👨‍💼','label'=>'Senior', 'color'=>'#0891b2'],
    'personel'=> ['icon'=>'👥','label'=>'Personel','color'=>'#16a34a'],
];
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px;">
    @foreach($layerMeta as $key => $meta)
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-left:3px solid {{ $meta['color'] }};border-radius:9px;padding:8px 12px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;">{{ $meta['icon'] }} {{ $meta['label'] }}</div>
        <div style="font-size:18px;font-weight:800;color:var(--u-text);">{{ $layerCounts[$key] ?? 0 }}</div>
    </div>
    @endforeach
</div>

{{-- Hiyerarşi Filtresi + Arama + Yeni Personel --}}
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
        <a href="/manager/staff?layer=hepsi"   class="layer-btn {{ $layerFilter === 'hepsi'   ? 'active' : '' }}">Hepsi</a>
        <a href="/manager/staff?layer=manager" class="layer-btn {{ $layerFilter === 'manager' ? 'active' : '' }}">👑 Manager</a>
        <a href="/manager/staff?layer=admin"   class="layer-btn {{ $layerFilter === 'admin'   ? 'active' : '' }}">🔑 Admin</a>
        <a href="/manager/staff?layer=senior"  class="layer-btn {{ $layerFilter === 'senior'  ? 'active' : '' }}">👨‍💼 Senior</a>
        <a href="/manager/staff?layer=personel"class="layer-btn {{ $layerFilter === 'personel'? 'active' : '' }}">👥 Personel</a>
        <form method="GET" action="/manager/staff" style="display:flex;gap:4px;margin-left:8px;">
            <input type="hidden" name="layer" value="{{ $layerFilter }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="İsim veya e-posta…"
                   style="padding:5px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);width:190px;">
            <button type="submit" class="btn alt" style="font-size:11px;padding:5px 12px;">Ara</button>
            @if($search)<a href="/manager/staff?layer={{ $layerFilter }}" style="font-size:11px;padding:5px 10px;border:1.5px solid var(--u-line);border-radius:7px;text-decoration:none;color:var(--u-muted);background:var(--u-bg);">✕</a>@endif
        </form>
    </div>
    <a href="/manager/staff/create" class="btn ok" style="font-size:12px;padding:6px 16px;">+ Yeni Personel</a>
</div>

{{-- Tablo --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;">
            {{ $staff->count() }} Kişi
        </span>
    </div>
    <div style="overflow-x:auto;">
        @if($staff->isEmpty())
        <div style="padding:40px;text-align:center;color:var(--u-muted);font-size:13px;">
            Bu katmanda personel bulunamadı.
        </div>
        @else
        @php
        $layerOrder  = [1=>'👑 Manager',2=>'🔑 Admin',3=>'👨‍💼 Senior',4=>'👥 Personel'];
        $layerColors = [1=>'#7c3aed',2=>'#1e40af',3=>'#0891b2',4=>'#16a34a'];
        $roleLayerMap = [
            'manager'=>1,'system_admin'=>2,'operations_admin'=>2,'finance_admin'=>2,
            'marketing_admin'=>2,'sales_admin'=>2,'senior'=>3,
            'system_staff'=>4,'operations_staff'=>4,'finance_staff'=>4,
            'marketing_staff'=>4,'sales_staff'=>4,
        ];
        $roleDeptLabel = [
            'manager'=>'—','senior'=>'—',
            'system_admin'=>'Sistem','system_staff'=>'Sistem',
            'operations_admin'=>'Operasyon','operations_staff'=>'Operasyon',
            'finance_admin'=>'Finans','finance_staff'=>'Finans',
            'marketing_admin'=>'Pazarlama','marketing_staff'=>'Pazarlama',
            'sales_admin'=>'Satış','sales_staff'=>'Satış',
        ];
        $currentLayer = null;
        @endphp
        {{-- Toplu İşlem Çubuğu --}}
        <div id="bulkBar" style="display:none;padding:10px 14px;background:#eff6ff;border-bottom:1px solid #bfdbfe;gap:10px;align-items:center;flex-wrap:wrap;">
            <span id="bulkCount" style="font-size:12px;font-weight:700;color:#1e40af;"></span>
            <form method="POST" action="/manager/staff/bulk" id="bulkForm" style="display:inline-flex;gap:8px;">
                @csrf
                <div id="bulkHiddenIds"></div>
                <input type="hidden" name="action" id="bulkAction" value="">
                <button type="button" class="btn ok" style="font-size:11px;padding:4px 12px;"
                        data-bulk-action="activate">Aktif Et</button>
                <button type="button" class="btn warn" style="font-size:11px;padding:4px 12px;"
                        data-bulk-action="deactivate">Pasif Yap</button>
            </form>
            <button type="button" id="bulkClear" style="font-size:11px;color:var(--u-muted);border:none;background:none;cursor:pointer;padding:0;">✕ Seçimi Temizle</button>
        </div>

        <table class="mgr-table">
            <thead>
                <tr>
                    <th style="width:32px;"><input type="checkbox" id="checkAll" style="cursor:pointer;width:13px;height:13px;"></th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Departman</th>
                    <th>Katman</th>
                    <th>Durum</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($staff as $s)
            @php
                $layer = $roleLayerMap[$s->role] ?? 9;
            @endphp
            @if($currentLayer !== $layer)
                @php $currentLayer = $layer; @endphp
                <tr class="layer-divider">
                    <td colspan="7" style="border-left:3px solid {{ $layerColors[$layer] ?? '#94a3b8' }};">
                        {{ $layerOrder[$layer] ?? 'Diğer' }}
                    </td>
                </tr>
            @endif
            <tr>
                <td><input type="checkbox" class="row-check" value="{{ $s->id }}" style="cursor:pointer;width:13px;height:13px;"></td>
                <td style="font-weight:700;color:var(--u-text);">{{ $s->name ?: '—' }}</td>
                <td style="color:var(--u-muted);font-size:11px;">{{ $s->email }}</td>
                <td>
                    @php $dept = $roleDeptLabel[$s->role] ?? '—'; @endphp
                    @if($dept !== '—')
                        <span class="badge info" style="font-size:10px;">{{ $dept }}</span>
                    @else
                        <span style="color:var(--u-muted);font-size:11px;">—</span>
                    @endif
                </td>
                <td>
                    @php
                        $lBadge = match($layer) {
                            1 => ['warn',   'Manager'],
                            2 => ['info',   'Admin'],
                            3 => ['',       'Senior'],
                            4 => ['',       'Personel'],
                            default => ['', '—'],
                        };
                    @endphp
                    <span class="badge {{ $lBadge[0] }}" style="font-size:10px;">{{ $lBadge[1] }}</span>
                </td>
                <td>
                    @if($s->is_active)
                        <span class="badge ok" style="font-size:10px;">Aktif</span>
                    @else
                        <span class="badge danger" style="font-size:10px;">Pasif</span>
                    @endif
                </td>
                <td style="text-align:right;">
                    <a href="/manager/hr/persons/{{ $s->id }}" style="display:inline-block;padding:4px 10px;font-size:11px;font-weight:600;color:#1e40af;border:1px solid rgba(30,64,175,.3);border-radius:6px;background:rgba(30,64,175,.05);text-decoration:none;">Kişi Kartı →</a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</section>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    var checkAll  = document.getElementById('checkAll');
    var bulkBar   = document.getElementById('bulkBar');
    var bulkCount = document.getElementById('bulkCount');
    var bulkHidden = document.getElementById('bulkHiddenIds');
    var bulkForm  = document.getElementById('bulkForm');
    var bulkAction = document.getElementById('bulkAction');

    function getChecked() {
        return Array.from(document.querySelectorAll('.row-check:checked'));
    }

    function updateBar() {
        var checked = getChecked();
        if (checked.length > 0) {
            bulkBar.style.display = 'flex';
            bulkCount.textContent = checked.length + ' personel seçildi';
            bulkHidden.innerHTML = '';
            checked.forEach(function(cb) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'user_ids[]';
                inp.value = cb.value;
                bulkHidden.appendChild(inp);
            });
        } else {
            bulkBar.style.display = 'none';
        }
    }

    checkAll?.addEventListener('change', function() {
        document.querySelectorAll('.row-check').forEach(function(cb) {
            cb.checked = checkAll.checked;
        });
        updateBar();
    });

    document.querySelectorAll('.row-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var all = document.querySelectorAll('.row-check');
            checkAll.checked = Array.from(all).every(function(c){ return c.checked; });
            checkAll.indeterminate = !checkAll.checked && getChecked().length > 0;
            updateBar();
        });
    });

    document.querySelectorAll('[data-bulk-action]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var action = this.getAttribute('data-bulk-action');
            var label  = action === 'activate' ? 'aktif' : 'pasif';
            if (!confirm(getChecked().length + ' personeli ' + label + ' yapmak istediğinize emin misiniz?')) return;
            bulkAction.value = action;
            bulkForm.submit();
        });
    });

    document.getElementById('bulkClear')?.addEventListener('click', function() {
        document.querySelectorAll('.row-check').forEach(function(cb){ cb.checked = false; });
        if (checkAll) { checkAll.checked = false; checkAll.indeterminate = false; }
        updateBar();
    });
})();
</script>
@endpush

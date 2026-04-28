@extends('manager.layouts.app')

@section('title', 'Sayfa Görünürlüğü')
@section('page_title', 'Sayfa Görünürlüğü Kontrol Paneli')

@push('head')
<style>
.pv-wrap { max-width:1400px; margin:0 auto; padding:0 0 32px; }
.pv-head {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    padding:20px 24px; margin-bottom:18px; box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.pv-head h2 { font-size:18px; font-weight:800; margin:0 0 4px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.pv-head h2 .badge { font-size:9.5px; font-weight:700; letter-spacing:.5px; color:#fff; background:#7c3aed; padding:2px 8px; border-radius:8px; }
.pv-head p { font-size:13px; color:#64748b; margin:0; line-height:1.5; }

.pv-flash {
    background:#dcfce7; border:1px solid #86efac; color:#166534;
    padding:10px 16px; border-radius:8px; margin-bottom:14px; font-size:13px;
}

.pv-card {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    margin-bottom:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.pv-group-head {
    padding:12px 18px; background:linear-gradient(90deg,#f8fafc 0%,#fff 100%);
    border-bottom:1px solid #e2e8f0; font-size:14px; font-weight:700; color:#1e293b;
    display:flex; align-items:center; gap:8px;
}
.pv-table-wrap { overflow-x:auto; }
.pv-table {
    width:100%; border-collapse:collapse; font-size:13px;
}
.pv-table thead th {
    text-align:center; padding:10px 8px; font-size:11px; font-weight:700;
    color:#475569; text-transform:uppercase; letter-spacing:.5px;
    background:#f8fafc; border-bottom:1px solid #e2e8f0;
    white-space:nowrap;
}
.pv-table thead th.role-staff { color:#7c3aed; background:#faf5ff; }
.pv-table thead th:first-child { text-align:left; padding-left:18px; min-width:200px; }
.pv-table tbody td {
    padding:10px 8px; text-align:center; border-bottom:1px solid #f1f5f9;
}
.pv-table tbody td:first-child {
    text-align:left; padding-left:18px; font-weight:600; color:#0f172a;
}
.pv-table tbody tr:hover { background:#f8fafc; }
.pv-table tbody td.cell-staff { background:#faf5ff; }

.pv-toggle {
    width:36px; height:20px; position:relative; display:inline-block; vertical-align:middle;
}
.pv-toggle input { opacity:0; width:0; height:0; }
.pv-toggle .slider {
    position:absolute; cursor:pointer; inset:0;
    background:#cbd5e1; border-radius:20px; transition:.2s;
}
.pv-toggle .slider:before {
    position:absolute; content:""; height:14px; width:14px; left:3px; bottom:3px;
    background:#fff; border-radius:50%; transition:.2s;
}
.pv-toggle input:checked + .slider { background:#16a34a; }
.pv-toggle input:checked + .slider:before { transform:translateX(16px); }
.pv-toggle input:disabled + .slider { background:#f1f5f9; cursor:not-allowed; }
.pv-toggle.na { opacity:.25; }

.pv-actions {
    position:sticky; bottom:0; background:#fff;
    padding:14px 24px; border-top:1px solid #e2e8f0;
    display:flex; justify-content:space-between; align-items:center; gap:10px;
    margin-top:18px; border-radius:0 0 14px 14px; box-shadow:0 -2px 8px rgba(0,0,0,.04);
    flex-wrap:wrap;
}
.pv-actions .info { font-size:12px; color:#64748b; }
.pv-actions .btn-save {
    padding:10px 22px; border:none; border-radius:8px; cursor:pointer;
    font-size:13px; font-weight:700; color:#fff;
    background:linear-gradient(135deg,#1e40af,#3b5fcc);
}
.pv-actions .btn-save:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(30,64,175,.25); }

.pv-tabs { display:flex; gap:4px; padding:8px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.pv-tab {
    padding:6px 14px; font-size:12px; font-weight:600; color:#475569;
    background:transparent; border:1px solid transparent; border-radius:6px;
    cursor:pointer;
}
.pv-tab.active { color:#1e40af; background:#fff; border-color:#dbeafe; }

.pv-staff-section { display:none; }
.pv-staff-section.show { display:block; }

@media (max-width:740px) {
    .pv-wrap { padding:0 12px 32px; }
    .pv-table thead th:first-child { min-width:150px; }
}
</style>
@endpush

@section('content')
<div class="pv-wrap">

    <div class="pv-head">
        <h2>🎛️ Sayfa Görünürlüğü <span class="badge">PREMIUM</span></h2>
        <p>Aşağıdaki matrisle, her rolün hangi sayfayı göreceğini kontrol et. <strong>Yeşil</strong> = sayfa görünür. <strong>Gri</strong> = sayfa gizli (rol kullanıcıları sidebar'da görmez, doğrudan URL'e gitse 404 alır).</p>
        <p style="margin-top:6px;">Bir rolde tanımlı olmayan sayfalar (rol o sayfayı kullanmıyor) <span style="opacity:.5;">soluk</span> gösterilir, değiştirilemez.</p>
    </div>

    @if(session('flash_success'))
        <div class="pv-flash">{{ session('flash_success') }}</div>
    @endif

    <form method="post" action="{{ route('manager.page-visibility.update') }}">
        @csrf

        <div class="pv-card">
            <div class="pv-tabs">
                <button type="button" class="pv-tab active" data-pv-tab="core">👥 Kullanıcı Rolleri</button>
                <button type="button" class="pv-tab" data-pv-tab="staff">🏢 Yönetici / Staff Rolleri</button>
            </div>

            {{-- Core Roller (Aday Öğrenci, Öğrenci, Bayi, Senior) --}}
            <div class="pv-section" data-pv-section="core">
                @php
                    $groups = collect($pages)->groupBy(fn ($p) => $p['group'] ?? 'Diğer');
                @endphp

                @foreach($groups as $groupName => $groupPages)
                    <div class="pv-group-head">📂 {{ $groupName }}</div>
                    <div class="pv-table-wrap">
                        <table class="pv-table">
                            <thead>
                                <tr>
                                    <th>Sayfa</th>
                                    @foreach($coreRoles as $roleCode => $roleLabel)
                                        <th>{{ $roleLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupPages as $pageKey => $page)
                                    <tr>
                                        <td>{{ $page['label'] }}</td>
                                        @foreach($coreRoles as $roleCode => $roleLabel)
                                            @php
                                                $applicable = in_array($roleCode, (array) ($page['roles'] ?? []), true);
                                                $checked    = $applicable ? ($visibility[$roleCode][$pageKey] ?? true) : false;
                                            @endphp
                                            <td>
                                                <label class="pv-toggle {{ $applicable ? '' : 'na' }}">
                                                    <input type="checkbox"
                                                           name="visible[{{ $roleCode }}][{{ $pageKey }}]"
                                                           value="1"
                                                           @checked($applicable && $checked)
                                                           @disabled(!$applicable)>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>

            {{-- Staff Roller (Manager altındaki personel) --}}
            <div class="pv-section pv-staff-section" data-pv-section="staff">
                <div class="pv-group-head" style="background:#faf5ff;color:#7c3aed;">
                    🏢 Manager altındaki staff'lar — ileride büyürse otomatik buradan yönetilir
                </div>
                <div class="pv-table-wrap">
                    <table class="pv-table">
                        <thead>
                            <tr>
                                <th>Sayfa</th>
                                @foreach($staffRoles as $roleCode => $roleLabel)
                                    <th class="role-staff">{{ $roleLabel }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pages as $pageKey => $page)
                                <tr>
                                    <td>{{ $page['label'] }} <span style="font-size:10.5px;color:#94a3b8;">({{ $page['group'] ?? 'Diğer' }})</span></td>
                                    @foreach($staffRoles as $roleCode => $roleLabel)
                                        @php
                                            $checked = $visibility[$roleCode][$pageKey] ?? true;
                                        @endphp
                                        <td class="cell-staff">
                                            <label class="pv-toggle">
                                                <input type="checkbox"
                                                       name="visible[{{ $roleCode }}][{{ $pageKey }}]"
                                                       value="1"
                                                       @checked($checked)>
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:14px 18px;background:#fefce8;border-top:1px solid #fde68a;font-size:11.5px;color:#854d0e;">
                    💡 Şu anda staff alt rolleri bağımsız sayfalara sahip değil; bu matrix gelecekte her staff rolü için özel sayfalar eklendiğinde otomatik aktif olur.
                </div>
            </div>
        </div>

        <div class="pv-actions">
            <div class="info">
                💾 Değişiklikler kaydedildikten sonra anında geçerli olur. Cache otomatik temizlenir.
            </div>
            <button type="submit" class="btn-save">💾 Değişiklikleri Kaydet</button>
        </div>
    </form>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    document.querySelectorAll('[data-pv-tab]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var key = this.dataset.pvTab;
            document.querySelectorAll('[data-pv-tab]').forEach(function(b){ b.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('[data-pv-section]').forEach(function(s){
                s.classList.toggle('show', s.dataset.pvSection === key);
                if (s.dataset.pvSection === 'core') s.style.display = (key === 'core') ? 'block' : 'none';
                else s.classList.toggle('show', key === 'staff');
            });
        });
    });
})();
</script>
@endsection

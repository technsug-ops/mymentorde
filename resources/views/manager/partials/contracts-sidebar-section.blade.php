{{--
    Sözleşme Yönetimi Sidebar — Manager (ADDON / SEPARABLE)

    Tüm koruma katmanları:
    - ModuleAccess::enabled('contracts_hub') gate
    - Her sub-link için Route::has() varlık kontrolü
    - try/catch tüm config/route erişimi
    - Hiçbir alt link yoksa section header da render olmaz

    Silmek için: bu dosyayı sil + business-contracts/contract-template/my-contracts
    route'larını kaldır. Manager sidebar diğer section'lar etkilenmez.
--}}
@php
    try {
        $_csShow = \App\Support\ModuleAccess::enabled('contracts_hub');
        $_csLinks = [];
        $_csParent = ''; // hangi sub-link aktif

        if ($_csShow) {
            $_csCandidates = [
                [
                    'route'   => 'manager.contract-template.index',
                    'url_fb'  => '/manager/contract-template',
                    'icon'    => '👤',
                    'label'   => 'Öğrenci',
                    'pattern' => 'manager/contract-template*',
                    'extra'   => null,
                ],
                [
                    'route'   => 'manager.business-contracts.index',
                    'url_fb'  => '/manager/business-contracts?type=staff',
                    'icon'    => '👥',
                    'label'   => 'Staff',
                    'pattern' => 'manager/business-contracts*',
                    'extra'   => 'staff',
                ],
                [
                    'route'   => 'manager.business-contracts.index',
                    'url_fb'  => '/manager/business-contracts?type=dealer',
                    'icon'    => '🤝',
                    'label'   => 'Dealer',
                    'pattern' => 'manager/business-contracts*',
                    'extra'   => 'dealer',
                ],
            ];
            foreach ($_csCandidates as $_c) {
                try {
                    if (\Illuminate\Support\Facades\Route::has($_c['route'])) {
                        $_c['url'] = $_c['extra']
                            ? route($_c['route'], ['type' => $_c['extra']])
                            : route($_c['route']);
                        $_csLinks[] = $_c;
                    } else {
                        // Route name yok ama URL hardcoded fallback — sub-system kısmi çalışabilir
                        $_c['url'] = $_c['url_fb'];
                        $_csLinks[] = $_c;
                    }
                } catch (\Throwable $_e) { /* skip */ }
            }
        }

        $_csIsOpen = request()->is('manager/contract-template*')
            || request()->is('manager/business-contracts*')
            || request()->is('my-contracts*');
    } catch (\Throwable $_e) {
        $_csShow = false;
        $_csLinks = [];
    }
@endphp

@if(! empty($_csLinks))
<div>
    <button type="button"
            id="sozlesme-btn"
            data-toggle="sozlesme"
            class="nav-link {{ $_csIsOpen ? 'active' : '' }}"
            style="display:flex;align-items:center;justify-content:space-between;width:100%;background:none;border:none;cursor:pointer;text-align:left;">
        <span><span class="nav-icon">📋</span> Sözleşme Yönetimi</span>
        <span id="sozlesme-caret" style="font-size:10px;transition:transform .2s;{{ $_csIsOpen ? 'transform:rotate(180deg)' : '' }}">▾</span>
    </button>
    <div id="sozlesme-sub" style="{{ $_csIsOpen ? '' : 'display:none;' }}padding-left:12px;">
        @foreach($_csLinks as $_link)
            @php
                // Sub-link active state: type query string'e göre
                $_isActive = false;
                try {
                    if ($_link['extra']) {
                        $_isActive = request()->is($_link['pattern']) && request()->get('type') === $_link['extra'];
                    } else {
                        $_isActive = request()->is($_link['pattern']);
                    }
                } catch (\Throwable $_e) { /* skip */ }
            @endphp
            <a href="{{ $_link['url'] }}"
               class="nav-link {{ $_isActive ? 'active' : '' }}"
               style="font-size:12px;padding:6px 12px;">
                <span class="nav-icon" style="font-size:14px;">{{ $_link['icon'] }}</span> {{ $_link['label'] }}
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- "Tüm Sözleşmeler" link — ayrı çünkü common.php'de tanımlı, /my-contracts route'u manager dışı --}}
@if(($_csShow ?? false))
    <a href="/my-contracts"
       class="nav-link {{ request()->is('my-contracts*') ? 'active' : '' }}">
        <span class="nav-icon">📄</span> Tüm Sözleşmeler
    </a>
@endif

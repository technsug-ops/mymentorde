{{--
    Bayi / Partner Yönetimi — Marketing Admin Sidebar (ADDON / SEPARABLE)

    Tüm koruma katmanları:
    - $isAdmin true olmalı (sadece marketing.admin yetkili)
    - ModuleAccess::enabled('dealer') — bayi modülü açık mı?
    - Route::has() her link için ayrı kontrol — eksik route varsa o link YOK
    - try/catch tüm config + module erişimi
    - Hiç link yoksa section header da görünmez

    Silmek için: bu dosyayı sil + routes/marketing-admin.php'den bayi block sil.
    Sidebar layout INTAK kalır.
--}}
@php
    try {
        $_byShow = ($isAdmin ?? false)
            && \App\Support\ModuleAccess::enabled('dealer');
        $_byLinks = [];
        if ($_byShow) {
            // Her link için Route::has() kontrolü — birini eksilt → o link YOK, diğerleri render
            $_byCandidates = [
                ['route' => 'mktg-admin.dealers.index',              'icon' => '🤝', 'label' => 'Bayiler',              'pattern' => 'mktg-admin/dealers*'],
                ['route' => 'mktg-admin.dealer-applications.index',  'icon' => '📋', 'label' => 'Bayi Başvuruları',     'pattern' => 'mktg-admin/dealer-applications*'],
                ['route' => 'mktg-admin.dealer-types.index',         'icon' => '🏷️', 'label' => 'Bayi Tipleri',          'pattern' => 'mktg-admin/dealer-types*'],
                ['route' => 'mktg-admin.dealer-tiers.index',         'icon' => '💎', 'label' => 'Komisyon Kademeleri',   'pattern' => 'mktg-admin/dealer-tiers*'],
            ];
            foreach ($_byCandidates as $_c) {
                if (\Illuminate\Support\Facades\Route::has($_c['route'])) {
                    try {
                        $_c['url'] = route($_c['route']);
                        $_byLinks[] = $_c;
                    } catch (\Throwable $_e) {
                        // Route exists ama generate fail → skip
                    }
                }
            }
        }
    } catch (\Throwable $_e) {
        $_byShow = false;
        $_byLinks = [];
    }
@endphp

@if(! empty($_byLinks))
<div class="nav-section">
    <div class="nav-section-label">Bayi / Partner Yönetimi</div>
    @foreach($_byLinks as $_link)
        <a href="{{ $_link['url'] }}"
           class="nav-link {{ request()->is($_link['pattern']) ? 'active' : '' }}">
            <span class="nav-icon">{{ $_link['icon'] }}</span> {{ $_link['label'] }}
        </a>
    @endforeach
</div>
@endif

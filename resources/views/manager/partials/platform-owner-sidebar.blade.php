{{--
    Platform Owner Sidebar Entries (ADDON / SEPARABLE)

    Sadece ROLE_PLATFORM_OWNER görür — Customer Manager kapsamında DEĞİL.

    Koruma katmanları:
    - auth()->user()?->role === ROLE_PLATFORM_OWNER
    - Her link için Route::has() (veya hardcoded URL fallback)
    - try/catch (auth çöktüğünde graceful skip)

    Silmek için: bu dosyayı sil. Manager sidebar etkilenmez.
--}}
@php
    try {
        $_poShow = auth()->user()?->role === \App\Models\User::ROLE_PLATFORM_OWNER;
    } catch (\Throwable $_e) {
        $_poShow = false;
    }
@endphp

@if($_poShow)
    {{-- Platform Owner için system admin linkleri --}}
    <a href="/manager/system"
       class="nav-link {{ request()->is('manager/system') ? 'active' : '' }}">
        <span class="nav-icon">🖥</span> Sistem Paneli
    </a>

    @if(\Illuminate\Support\Facades\Route::has('manager.companies.modules'))
    <a href="{{ route('manager.companies.modules') }}"
       class="nav-link {{ request()->is('manager/companies/modules*') ? 'active' : '' }}">
        <span class="nav-icon">🧩</span> SaaS Modül Yönetimi
    </a>
    @endif

    <a href="/manager/system/security"
       class="nav-link {{ request()->is('manager/system/security*') ? 'active' : '' }}">
        <span class="nav-icon">🛡</span> Güvenlik Paneli
    </a>
    <a href="/manager/system/roles"
       class="nav-link {{ request()->is('manager/system/roles*') ? 'active' : '' }}">
        <span class="nav-icon">🔑</span> Rol Yönetimi
    </a>
    <a href="/manager/system/ip-rules"
       class="nav-link {{ request()->is('manager/system/ip-rules*') ? 'active' : '' }}">
        <span class="nav-icon">🌐</span> IP Erişim Kuralları
    </a>
@endif

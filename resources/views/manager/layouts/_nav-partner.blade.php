{{-- Partner firma menüsü — sade takip penceresi.

     Bayi portalının şekli örnek alındı: aday takibi, öğrenci takibi, belge,
     destek, hesap. Yönetim ve analiz alanları YOK.

     Partnerlere yazılım satmıyoruz; öğrencilerini bize devredip süreci
     izliyorlar. Tam manager paneli (60+ sayfa: İK, finans, sistem, AI Labs,
     UniMatch, bayi ağı) onların işi değil.

     Menü gizlemek tek başına yeterli değil — adresler de RestrictPartnerPanel
     ile kapalı. --}}

@php
    $_pnCompany = app()->bound('current_company') ? app('current_company') : null;
    $_pnIs = fn (string $path): bool => request()->is($path) || request()->is($path . '/*');
@endphp

<nav class="sidebar-nav">
    <div class="nav-section">
        <div class="nav-section-label">Öğrencilerim</div>

        {{-- Giriş sayfası burası: manager dashboard'u MentorDE'nin yönetim
             ekranı ve kapalı alanlara bağlantı veriyor. Partner doğrudan
             kendi listesine düşer (bkz. RestrictPartnerPanel). --}}
        <a href="/manager/guests" class="nav-link {{ $_pnIs('manager/guests') ? 'active' : '' }}">
            <span class="nav-icon">📋</span> Adaylar
        </a>

        <a href="{{ route('manager.leads.create') }}" class="nav-link {{ $_pnIs('manager/leads/create') ? 'active' : '' }}">
            <span class="nav-icon">➕</span> Aday Ekle
        </a>

        <a href="/manager/students" class="nav-link {{ $_pnIs('manager/students') ? 'active' : '' }}">
            <span class="nav-icon">🎓</span> Öğrenciler
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-section-label">Belgeler</div>

        <a href="/manager/required-documents" class="nav-link {{ $_pnIs('manager/required-documents') ? 'active' : '' }}">
            <span class="nav-icon">📄</span> Belge Listesi
        </a>

        @module('doc_request')
        <a href="/manager/document-requests" class="nav-link {{ $_pnIs('manager/document-requests') ? 'active' : '' }}">
            <span class="nav-icon">📨</span> Belge Talepleri
        </a>
        @endmodule
    </div>

    <div class="nav-section">
        <div class="nav-section-label">İletişim</div>

        <a href="/manager/requests" class="nav-link {{ $_pnIs('manager/requests') ? 'active' : '' }}">
            <span class="nav-icon">💬</span> Destek Talepleri
        </a>

        <a href="/manager/bulletins" class="nav-link {{ $_pnIs('manager/bulletins') ? 'active' : '' }}">
            <span class="nav-icon">📢</span> Duyurular
        </a>
    </div>

    <div class="nav-section">
        <a href="{{ route('manager.account.edit') }}" class="nav-link {{ $_pnIs('manager/account') ? 'active' : '' }}">
            <span class="nav-icon">⚙️</span> Hesabım
        </a>

        <a href="/logout" class="nav-link">
            <span class="nav-icon">🚪</span> Çıkış Yap
        </a>
    </div>
</nav>

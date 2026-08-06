{{-- Partner firma menüsü — sade takip penceresi.

     Partnerlere yazılım satmıyoruz; öğrencilerini bize devredip süreci
     izliyorlar. Tam manager paneli (60+ sayfa: İK, finans, sistem, AI Labs,
     UniMatch, bayi ağı) onların işi değil.

     ⚠ MENÜYE MADDE EKLERKEN: hedefin partner bağlamında GERÇEKTEN açıldığını
     doğrula. İlk sürümde iki bağlantı kırıktı (biri olmayan rotaya gidiyordu,
     biri 500 veren bir sayfaya). PartnerMenuLinksTest artık her bağlantıyı
     gezip hata dönenleri raporluyor — yeni madde eklerken o test yeter. --}}

@php
    $_pnIs = fn (string $path): bool => request()->is($path) || request()->is($path . '/*');
@endphp

<nav class="sidebar-nav">
    <div class="nav-section">
        <div class="nav-section-label">Öğrencilerim</div>

        {{-- Giriş sayfası burası: manager dashboard'u MentorDE'nin yönetim
             ekranı ve kapalı alanlara bağlantı veriyor (bkz. RestrictPartnerPanel). --}}
        <a href="/manager/guests" class="nav-link {{ $_pnIs('manager/guests') ? 'active' : '' }}">
            <span class="nav-icon">📋</span> Aday Öğrenciler
        </a>

        <a href="/manager/students" class="nav-link {{ $_pnIs('manager/students') ? 'active' : '' }}">
            <span class="nav-icon">🎓</span> Öğrenciler
        </a>

        <a href="{{ route('manager.leads.create') }}" class="nav-link {{ $_pnIs('manager/leads/create') ? 'active' : '' }}">
            <span class="nav-icon">➕</span> Aday Ekle
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-section-label">İletişim</div>

        {{-- Öğrenci/adayla ve üst firmanın atadığı danışmanla yazışma. --}}
        <a href="/im" class="nav-link {{ $_pnIs('im') ? 'active' : '' }}">
            <span class="nav-icon">💬</span> Mesajlar
        </a>

        <a href="/manager/requests" class="nav-link {{ $_pnIs('manager/requests') ? 'active' : '' }}">
            <span class="nav-icon">🎫</span> Destek Talepleri
        </a>

        <a href="/manager/bulletins" class="nav-link {{ $_pnIs('manager/bulletins') ? 'active' : '' }}">
            <span class="nav-icon">📢</span> Duyurular
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-section-label">Belgeler</div>

        <a href="/manager/required-documents" class="nav-link {{ $_pnIs('manager/required-documents') ? 'active' : '' }}">
            <span class="nav-icon">📄</span> Belge Listesi
        </a>

        @module('doc_request')
        <a href="/manager/document-requests/analytics" class="nav-link {{ $_pnIs('manager/document-requests') ? 'active' : '' }}">
            <span class="nav-icon">📨</span> Belge Talepleri
        </a>
        @endmodule
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

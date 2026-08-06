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

        {{-- Operasyonu yürüten firmanın yaptığı süreç takibi — salt okunur. --}}
        <a href="/manager/process-info" class="nav-link {{ $_pnIs('manager/process-info') ? 'active' : '' }}">
            <span class="nav-icon">🧭</span> Süreç Bilgisi
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

    {{-- Belgeler: partnere ait iki ekran.

         ⚠ Buraya önce hazır sayfalar bağlanmıştı ve ikisi de yanlıştı:
         /manager/required-documents zorunlu belge TANIMLAMA ekranı
         (operasyon konfigürasyonu), /manager/document-requests/analytics
         ise dönüşüm hunisi ve hatırlatma etkinliği ölçen metrik panosu.
         Partnere lazım olan ikisi de değil: kimden ne istendi, ne geldi. --}}
    <div class="nav-section">
        <div class="nav-section-label">Belgeler</div>

        <a href="/manager/partner-documents" class="nav-link {{ request()->is('manager/partner-documents') ? 'active' : '' }}">
            <span class="nav-icon">📄</span> Belge Listesi
        </a>

        <a href="/manager/partner-documents/requests" class="nav-link {{ $_pnIs('manager/partner-documents/requests') ? 'active' : '' }}">
            <span class="nav-icon">📨</span> Belge Talepleri
        </a>

        {{-- Operasyonun bizden istedikleri. Bekleyen varsa sayıyla belirt —
             cevaplanmayan talep sürecin tamamını durdurur. --}}
        @php
            $_pnIncoming = \App\Models\PartnerInfoRequest::query()
                ->incomingFor((int) (\App\Support\TenantContext::writeId() ?? 0))
                ->where('status', \App\Models\PartnerInfoRequest::STATUS_OPEN)
                ->count();
        @endphp
        <a href="/manager/partner-requests/incoming" class="nav-link {{ $_pnIs('manager/partner-requests') ? 'active' : '' }}">
            <span class="nav-icon">📥</span> Bizden İstenenler
            @if($_pnIncoming > 0)
                <span style="margin-left:6px;background:#d97706;color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700;">{{ $_pnIncoming }}</span>
            @endif
        </a>
    </div>

    <div class="nav-section">
        <a href="{{ route('manager.account.edit') }}" class="nav-link {{ $_pnIs('manager/account') ? 'active' : '' }}">
            <span class="nav-icon">⚙️</span> Hesabım
        </a>
    </div>
</nav>

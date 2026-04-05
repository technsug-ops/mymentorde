<aside class="side">
    @php
        $mgrUser = auth()->user();
        $mgrInitials = strtoupper(substr(preg_replace('/\s+/', '', ($mgrUser?->name ?? 'MG')), 0, 2));
    @endphp
    <div class="avatar"><span>{{ $mgrInitials }}</span></div>
    <div class="brand">MentorDE Manager</div>
    <div class="meta">{{ $mgrUser?->email }}</div>
    <nav class="nav">
        <a class="{{ request()->is('manager/dashboard*') ? 'active' : '' }}" href="/manager/dashboard">Dashboard</a>

        @php
            $isVeri = request()->is('manager/guests*') || request()->is('manager/students*')
                   || request()->is('manager/seniors*') || request()->is('manager/dealers*')
                   || request()->is('manager/staff*') || request()->is('manager/commissions*')
                   || request()->is('manager/requests*') || request()->is('manager/university-requirements*');
        @endphp
        <div class="nav-group {{ $isVeri ? 'open has-active' : '' }}" id="ng-veri">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-veri">
                <span>Veri Yönetimi</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                <a class="{{ request()->is('manager/guests*') ? 'active' : '' }}" href="/manager/guests">Guest</a>
                <a class="{{ request()->is('manager/students*') ? 'active' : '' }}" href="/manager/students">Öğrenciler</a>
                <a class="{{ request()->is('manager/seniors*') ? 'active' : '' }}" href="/manager/seniors">Seniorlar</a>
                <a class="{{ request()->is('manager/staff*') ? 'active' : '' }}" href="/manager/staff">👥 Personel</a>
                <a class="{{ request()->is('manager/dealers*') ? 'active' : '' }}" href="/manager/dealers">Bayiler</a>
                <a class="{{ request()->is('manager/commissions*') ? 'active' : '' }}" href="/manager/commissions">Komisyonlar</a>
                <a class="{{ request()->is('manager/requests*') ? 'active' : '' }}" href="/manager/requests">Başvurular</a>
                <a class="{{ request()->is('manager/university-requirements*') ? 'active' : '' }}" href="/manager/university-requirements">🗺️ Üniversite Haritası</a>
            </div>
        </div>

        @php $isCommHub = request()->is('tasks*') || request()->is('tickets-center*') || request()->is('im*'); @endphp
        <div class="nav-group {{ $isCommHub ? 'open has-active' : '' }}" id="ng-comm">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-comm">
                <span>İletişim & Görevler</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                <a class="{{ request()->is('tasks*') ? 'active' : '' }}" href="/tasks">Görevler</a>
                <a class="{{ request()->is('tickets-center*') ? 'active' : '' }}" href="/tickets-center">Ticket Merkezi</a>
                <a class="{{ request()->is('im*') ? 'active' : '' }}" href="/im">💬 İletişim Merkezi</a>
            </div>
        </div>

        @php
            $isSozlesme = request()->is('manager/contract-template*')
                       || request()->is('manager/business-contracts*')
                       || request()->is('manager/contract-analytics*')
                       || request()->is('my-contracts*');
        @endphp
        <div class="nav-group {{ $isSozlesme ? 'open has-active' : '' }}" id="ng-sozlesme">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-sozlesme">
                <span>📋 Sözleşmeler</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                <a class="{{ request()->is('manager/contract-template*') ? 'active' : '' }}" href="/manager/contract-template">Öğrenci</a>
                <a class="{{ (request()->is('manager/business-contracts*') && request()->get('type') === 'staff') ? 'active' : '' }}" href="/manager/business-contracts?type=staff">Staff</a>
                <a class="{{ (request()->is('manager/business-contracts*') && request()->get('type') === 'dealer') ? 'active' : '' }}" href="/manager/business-contracts?type=dealer">Dealer</a>
                <a class="{{ request()->is('manager/contract-analytics*') ? 'active' : '' }}" href="/manager/contract-analytics">📊 Analitik</a>
                <a class="{{ request()->is('my-contracts*') ? 'active' : '' }}" href="/my-contracts">📄 İş Sözleşmem</a>
            </div>
        </div>

        @php
            $isSistem = request()->is('manager/notification-stats*') || request()->is('manager/gdpr-dashboard*')
                     || request()->is('manager/theme*') || request()->is('config*');
        @endphp
        <div class="nav-group {{ $isSistem ? 'open has-active' : '' }}" id="ng-sistem">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-sistem">
                <span>Sistem</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                <a class="{{ request()->is('manager/notification-stats*') ? 'active' : '' }}" href="/manager/notification-stats">🔔 Bildirim İstatistik</a>
                <a class="{{ request()->is('manager/gdpr-dashboard*') ? 'active' : '' }}" href="/manager/gdpr-dashboard">🔒 GDPR Paneli</a>
                <a class="{{ request()->is('manager/theme*') ? 'active' : '' }}" href="/manager/theme">Tema Yönetimi</a>
                <a class="{{ request()->is('config*') ? 'active' : '' }}" href="/config">Sistem Ayarları</a>
            </div>
        </div>
    </nav>
</aside>

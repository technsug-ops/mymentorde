<aside class="side">
    @php
        $mktgUser     = auth()->user();
        $mktgRole     = (string) ($mktgUser?->role ?? '');
        $mktgInitials = strtoupper(substr(preg_replace('/\s+/', '', ($mktgUser?->name ?? 'MA')), 0, 2));

        // Role-based mode enforcement
        $salesOnlyRoles     = ['sales_admin', 'sales_staff'];
        $marketingOnlyRoles = ['marketing_staff'];

        if (in_array($mktgRole, $salesOnlyRoles, true)) {
            $panelMode = 'sales';
            $canToggle = false;
        } elseif (in_array($mktgRole, $marketingOnlyRoles, true)) {
            $panelMode = 'marketing';
            $canToggle = false;
        } else {
            $panelMode = session('mktg_panel_mode', 'marketing');
            $canToggle = true;
        }

        // Permission flags — v3.0 §2.7 matrix
        $isMktgAdmin    = in_array($mktgRole, ['marketing_admin', 'manager', 'system_admin'], true);
        $isSalesAdmin   = $mktgRole === 'sales_admin';
        $isMktgStaff    = $mktgRole === 'marketing_staff';
        $isSalesStaff   = $mktgRole === 'sales_staff';
        $isAdmin        = $isMktgAdmin || $isSalesAdmin;
        $isStaff        = $isMktgStaff || $isSalesStaff;

        // Granular permissions (v3.0 §2.7)
        $canSeeTracking     = $isMktgAdmin;                                 // [A]
        $canSeeAttribution  = $isMktgAdmin || $isSalesAdmin || $isSalesStaff; // [A] sales_staff read-only
        $canSeeScoring      = $isMktgAdmin || $isSalesAdmin || $isSalesStaff; // [A] sales_staff read-only
        $canSeeScoringCfg   = $isMktgAdmin || $isSalesAdmin;               // [SC] config sadece admin
        $canSeeLeadSources  = $isMktgAdmin || $isSalesAdmin || $isSalesStaff; // [A] sales_staff read-only
        $canSeeDealers      = $isMktgAdmin || $isSalesAdmin;               // [A]
        $canSeeBudget       = $isMktgAdmin;                                 // [A]
        $canSeeKpi          = $isMktgAdmin || $isSalesAdmin;               // [A]
        $canSeeReports      = $isMktgAdmin || $isSalesAdmin;               // [A]
        $canSeeTeam         = $isAdmin;                                     // [A]
        $canSeeSettings     = $isAdmin;                                     // [A]
        $canSeeIntegrations = $isMktgAdmin;                                 // [A]
        // Staff see their own: campaigns, content, email, events, workflows, abtests, tasks
        $canSeeCampaigns    = $isMktgAdmin || $isMktgStaff;
        $canSeeContent      = $isMktgAdmin || $isMktgStaff;
        $canSeeEmail        = $isMktgAdmin || $isMktgStaff;
        $canSeeSocial       = $isMktgAdmin || $isMktgStaff;
        $canSeeEvents       = $isMktgAdmin || $isMktgStaff;
        $canSeeWorkflows    = $isMktgAdmin;
        $canSeeABTests      = $isMktgAdmin || $isMktgStaff;
        // Sales pipeline: admin=full, sales_staff=own leads only
        $canSeePipeline     = $isMktgAdmin || $isSalesAdmin || $isSalesStaff;

        $maSidebarBrand = config('brand.name', 'MentorDE');
        $brandLabel = $panelMode === 'sales' ? $maSidebarBrand . ' Sales' : $maSidebarBrand . ' Marketing';
        $roleLabel  = $isAdmin ? 'Admin' : 'Staff';
    @endphp

    <div class="avatar"><span>{{ $mktgInitials }}</span></div>
    <div class="brand">{{ $brandLabel }}</div>
    <div class="meta">
        {{ $mktgUser?->name }}<br>{{ $mktgUser?->email }}
        <br><span class="badge {{ $isAdmin ? 'ok' : 'info' }}" style="margin-top:4px;">{{ $roleLabel }}</span>
    </div>

    {{-- Mode toggle (sadece toggle yetkisi olan roller) --}}
    @if($canToggle)
    <div style="display:flex;gap:6px;margin:10px 0 4px;">
        <a href="/mktg-admin/switch-mode/marketing"
           style="flex:1;text-align:center;padding:5px 0;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;
                  {{ $panelMode === 'marketing' ? 'background:var(--u-brand);color:#fff;' : 'background:var(--u-card);color:var(--u-muted);border:1px solid var(--u-line);' }}">
            Pazarlama
        </a>
        <a href="/mktg-admin/switch-mode/sales"
           style="flex:1;text-align:center;padding:5px 0;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;
                  {{ $panelMode === 'sales' ? 'background:var(--u-ok,#16a34a);color:#fff;' : 'background:var(--u-card);color:var(--u-muted);border:1px solid var(--u-line);' }}">
            Satış
        </a>
    </div>
    @endif

    <nav class="nav">
        <a class="{{ request()->is('mktg-admin/dashboard') ? 'active' : '' }}" href="/mktg-admin/dashboard">
            {{ $panelMode === 'sales' ? 'Sales Dashboard' : 'Marketing Dashboard' }}
        </a>
        @can('dam.view')
        <a class="{{ request()->is('mktg-admin/digital-assets*') ? 'active' : '' }}" href="{{ route('marketing-admin.dam.index') }}">
            📁 Dijital Varlıklar
        </a>
        @endcan
        @php $isComHub = request()->is('mktg-admin/tasks*') || request()->is('im*'); @endphp
        <div class="nav-group {{ $isComHub ? 'open has-active' : '' }}" id="ng-com-hub">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-com-hub">
                <span>💬 İletişim Merkezi</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                <a class="{{ request()->is('im*') ? 'active' : '' }}" href="/im">İletişim</a>
                <a class="{{ request()->is('mktg-admin/tasks*') ? 'active' : '' }}" href="/mktg-admin/tasks">Görevlerim</a>
            </div>
        </div>

        @if($panelMode === 'marketing')
        {{-- ─── PAZARLAMA MENÜSÜ — v3.0 §2.7 ─── --}}
        @php
            $isMktgIcerik  = request()->is('mktg-admin/campaigns*','mktg-admin/content*','mktg-admin/email*','mktg-admin/social*','mktg-admin/tracking-links*','mktg-admin/events*','mktg-admin/workflows*','mktg-admin/abtests*');
            $isMktgAnaliz  = request()->is('mktg-admin/attribution*','mktg-admin/kpi*','mktg-admin/reports*','mktg-admin/budget*');
            $isMktgYonetim = request()->is('mktg-admin/integrations*','mktg-admin/team*','mktg-admin/settings*');
            $isMktgHesap   = request()->is('mktg-admin/notifications*','mktg-admin/profile*','my-contracts*');
        @endphp

        {{-- ── İçerik & Kampanya ── --}}
        <div class="nav-group {{ $isMktgIcerik ? 'open has-active' : '' }}" id="ng-mktg-icerik">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-mktg-icerik">
                <span>İçerik & Kampanya</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                @if($canSeeCampaigns)
                <a class="{{ request()->is('mktg-admin/campaigns*') ? 'active' : '' }}" href="/mktg-admin/campaigns">Kampanyalar</a>
                @endif
                @if($canSeeContent)
                <a class="{{ request()->is('mktg-admin/content*') ? 'active' : '' }}" href="/mktg-admin/content">CMS İçerik</a>
                @endif
                @if($canSeeEmail)
                <a class="{{ request()->is('mktg-admin/email*') ? 'active' : '' }}" href="/mktg-admin/email/templates">E-posta</a>
                @endif
                @if($canSeeSocial)
                <a class="{{ request()->is('mktg-admin/social*') ? 'active' : '' }}" href="/mktg-admin/social/metrics">Sosyal Medya</a>
                @endif
                @if($canSeeTracking)
                <a class="{{ request()->is('mktg-admin/tracking-links*') ? 'active' : '' }}" href="/mktg-admin/tracking-links">Tracking Linkler</a>
                @endif
                @if($canSeeEvents)
                <a class="{{ request()->is('mktg-admin/events*') ? 'active' : '' }}" href="/mktg-admin/events">Etkinlikler</a>
                @endif
                @if($canSeeWorkflows)
                <a class="{{ request()->is('mktg-admin/workflows*') ? 'active' : '' }}" href="/mktg-admin/workflows">Otomasyon</a>
                @endif
                @if($canSeeABTests)
                <a class="{{ request()->is('mktg-admin/abtests*') ? 'active' : '' }}" href="/mktg-admin/abtests">A/B Testler</a>
                @endif
            </div>
        </div>

        {{-- ── Analiz & Raporlar ── --}}
        <div class="nav-group {{ $isMktgAnaliz ? 'open has-active' : '' }}" id="ng-mktg-analiz">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-mktg-analiz">
                <span>Analiz & Raporlar</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                @if($canSeeAttribution)
                <a class="{{ request()->is('mktg-admin/attribution*') ? 'active' : '' }}" href="/mktg-admin/attribution">Attribution</a>
                @endif
                @if($canSeeKpi)
                <a class="{{ request()->is('mktg-admin/kpi*') ? 'active' : '' }}" href="/mktg-admin/kpi">KPI & Raporlar</a>
                @endif
                @if($canSeeReports)
                <a class="{{ request()->is('mktg-admin/reports*') ? 'active' : '' }}" href="/mktg-admin/reports/scheduled">Zamanlanmış Raporlar</a>
                @endif
                @if($canSeeBudget)
                <a class="{{ request()->is('mktg-admin/budget*') ? 'active' : '' }}" href="/mktg-admin/budget">Bütçe</a>
                @endif
            </div>
        </div>

        {{-- ── Yönetim ── --}}
        @if($canSeeIntegrations || $canSeeTeam || $canSeeSettings)
        <div class="nav-group {{ $isMktgYonetim ? 'open has-active' : '' }}" id="ng-mktg-yonetim">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-mktg-yonetim">
                <span>Yönetim</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                @if($canSeeIntegrations)
                <a class="{{ request()->is('mktg-admin/integrations*') ? 'active' : '' }}" href="/mktg-admin/integrations">Entegrasyonlar</a>
                @endif
                @if($canSeeTeam)
                <a class="{{ request()->is('mktg-admin/team*') ? 'active' : '' }}" href="/mktg-admin/team">Ekip</a>
                @endif
                @if($canSeeSettings)
                <a class="{{ request()->is('mktg-admin/settings*') ? 'active' : '' }}" href="/mktg-admin/settings">Ayarlar</a>
                @endif
            </div>
        </div>
        @endif

        {{-- ── Hesap ── --}}
        <div class="nav-group {{ $isMktgHesap ? 'open has-active' : '' }}" id="ng-mktg-hesap">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-mktg-hesap">
                <span>Hesap</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                <a class="{{ request()->is('mktg-admin/notifications*') ? 'active' : '' }}" href="/mktg-admin/notifications">Bildirimler</a>
                <a class="{{ request()->is('my-contracts*') ? 'active' : '' }}" href="/my-contracts">📄 Sözleşmelerim</a>
                <a class="{{ request()->is('mktg-admin/profile*') ? 'active' : '' }}" href="/mktg-admin/profile">Profil</a>
            </div>
        </div>

        @else
        {{-- ─── SATIŞ MENÜSÜ — v3.0 §2.7 ─── --}}
        @php
            $isSalesSurec  = request()->is('mktg-admin/pipeline*','mktg-admin/scoring','mktg-admin/scoring/leaderboard*','mktg-admin/scoring/history*','mktg-admin/attribution*','mktg-admin/lead-sources*','mktg-admin/dealers*');
            $isSalesAnaliz = request()->is('mktg-admin/kpi*','mktg-admin/reports*');
            $isSalesYonetim= request()->is('mktg-admin/scoring/config*','mktg-admin/team*','mktg-admin/settings*');
            $isSalesHesap  = request()->is('mktg-admin/notifications*','mktg-admin/profile*','my-contracts*');
        @endphp

        {{-- ── Satış Süreci ── --}}
        <div class="nav-group {{ $isSalesSurec ? 'open has-active' : '' }}" id="ng-sales-surec">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-sales-surec">
                <span>Satış Süreci</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                @if($canSeePipeline)
                <a class="{{ request()->is('mktg-admin/pipeline*') ? 'active' : '' }}" href="/mktg-admin/pipeline">Sales Pipeline</a>
                @endif
                @if($canSeeScoring)
                <a class="{{ request()->is('mktg-admin/scoring') || request()->is('mktg-admin/scoring/leaderboard*') || request()->is('mktg-admin/scoring/history*') ? 'active' : '' }}" href="/mktg-admin/scoring">Lead Scoring</a>
                @endif
                @if($canSeeAttribution)
                <a class="{{ request()->is('mktg-admin/attribution*') ? 'active' : '' }}" href="/mktg-admin/attribution">Attribution</a>
                @endif
                @if($canSeeLeadSources)
                <a class="{{ request()->is('mktg-admin/lead-sources*') ? 'active' : '' }}" href="/mktg-admin/lead-sources">Lead Kaynakları</a>
                @endif
                @if($canSeeDealers)
                <a class="{{ request()->is('mktg-admin/dealers*') ? 'active' : '' }}" href="/mktg-admin/dealers">Bayi İlişkileri</a>
                @endif
            </div>
        </div>

        {{-- ── Analiz & Raporlar ── --}}
        <div class="nav-group {{ $isSalesAnaliz ? 'open has-active' : '' }}" id="ng-sales-analiz">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-sales-analiz">
                <span>Analiz & Raporlar</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                @if($canSeeKpi)
                <a class="{{ request()->is('mktg-admin/kpi*') ? 'active' : '' }}" href="/mktg-admin/kpi">KPI & Raporlar</a>
                @endif
                @if($canSeeReports)
                <a class="{{ request()->is('mktg-admin/reports*') ? 'active' : '' }}" href="/mktg-admin/reports/scheduled">Zamanlanmış Raporlar</a>
                @endif
            </div>
        </div>

        {{-- ── Yönetim ── --}}
        @if($canSeeScoringCfg || $canSeeTeam || $canSeeSettings)
        <div class="nav-group {{ $isSalesYonetim ? 'open has-active' : '' }}" id="ng-sales-yonetim">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-sales-yonetim">
                <span>Yönetim</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                @if($canSeeScoringCfg)
                <a class="{{ request()->is('mktg-admin/scoring/config*') ? 'active' : '' }}" href="/mktg-admin/scoring/config">Scoring Yapılandırma</a>
                @endif
                @if($canSeeTeam)
                <a class="{{ request()->is('mktg-admin/team*') ? 'active' : '' }}" href="/mktg-admin/team">Ekip</a>
                @endif
                @if($canSeeSettings)
                <a class="{{ request()->is('mktg-admin/settings*') ? 'active' : '' }}" href="/mktg-admin/settings">Ayarlar</a>
                @endif
            </div>
        </div>
        @endif

        {{-- ── Hesap ── --}}
        <div class="nav-group {{ $isSalesHesap ? 'open has-active' : '' }}" id="ng-sales-hesap">
            <button class="nav-group-btn" type="button" data-toggle-group="ng-sales-hesap">
                <span>Hesap</span>
                <span class="nav-caret">▾</span>
            </button>
            <div class="nav-sub">
                <a class="{{ request()->is('mktg-admin/notifications*') ? 'active' : '' }}" href="/mktg-admin/notifications">Bildirimler</a>
                <a class="{{ request()->is('my-contracts*') ? 'active' : '' }}" href="/my-contracts">📄 Sözleşmelerim</a>
                <a class="{{ request()->is('mktg-admin/profile*') ? 'active' : '' }}" href="/mktg-admin/profile">Profil</a>
            </div>
        </div>
        @endif
    </nav>
</aside>

<div class="topbar">
    @php
        $navbarPanelMode = session('mktg_panel_mode', 'marketing');
        $navbarRole = (string) (auth()->user()?->role ?? '');
        if (in_array($navbarRole, ['sales_admin','sales_staff'], true)) { $navbarPanelMode = 'sales'; }
        if ($navbarRole === 'marketing_staff') { $navbarPanelMode = 'marketing'; }
    @endphp
    <div>
        <strong>{{ $pageTitle ?? ($navbarPanelMode === 'sales' ? 'Sales Panel' : 'Marketing Admin') }}</strong>
        <span class="badge {{ $navbarPanelMode === 'sales' ? 'ok' : 'info' }}" style="min-width:68px;text-align:center;display:inline-flex;align-items:center;justify-content:center;">
            {{ $navbarPanelMode === 'sales' ? 'Satış' : 'Pazarlama' }}
        </span>
        <span class="badge" style="min-width:68px;text-align:center;display:inline-flex;align-items:center;justify-content:center;">v4.6.1</span>
        @if(isset($currentCompany) && $currentCompany)
            <span class="badge" style="min-width:68px;text-align:center;display:inline-flex;align-items:center;justify-content:center;">Company: {{ $currentCompany->name }}</span>
        @endif
    </div>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <label for="mktgCompanySwitch" class="muted" style="font-size:12px;">Aktif Firma</label>
        <select id="mktgCompanySwitch" style="min-width:220px;">
            <option value="">Yükleniyor...</option>
        </select>
        <button type="button" id="mktgCompanyReload" class="btn primary">Yenile</button>
        <span id="mktgCompanyStatus" class="muted" style="font-size:12px; max-width:280px;"></span>
        <a class="btn" href="/logout">Çıkış</a>
    </div>
</div>

<script defer src="{{ Vite::asset('resources/js/mktg-company-switch.js') }}" defer></script>

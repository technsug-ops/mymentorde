{{--
    Dashboard Tab Bar
    Kullanım: @include('partials.dashboard-tabs', ['activeTab' => 'dashboard', 'dashboardUrl' => '/senior/dashboard'])
--}}
<div id="dash-tab-bar" style="
    display: flex; gap: 0; margin-bottom: 20px;
    border-bottom: 2px solid var(--border, #e2e8f0);
">
    <button id="tab-dashboard"
        onclick="switchDashTab('dashboard')"
        style="
            padding: 10px 20px; font-size: 13px; font-weight: 700;
            background: none; border: none; border-bottom: 3px solid transparent;
            margin-bottom: -2px; cursor: pointer;
            color: var(--muted, #64748b); transition: all .15s;
        ">
        📊 Genel Bakış
    </button>
    <button id="tab-bulletins"
        onclick="switchDashTab('bulletins')"
        style="
            padding: 10px 20px; font-size: 13px; font-weight: 700;
            background: none; border: none; border-bottom: 3px solid transparent;
            margin-bottom: -2px; cursor: pointer;
            color: var(--muted, #64748b); transition: all .15s;
            display: flex; align-items: center; gap: 6px;
        ">
        📢 Duyurular
        @if(($bulletinUnread ?? 0) > 0)
        <span style="
            background: #dc2626; color: #fff;
            font-size: 10px; font-weight: 800;
            border-radius: 999px; padding: 1px 6px;
            line-height: 16px; min-width: 18px; text-align: center;
        ">{{ $bulletinUnread }}</span>
        @endif
    </button>
</div>

{{-- Pano yükleme alanı --}}
<div id="panel-bulletins" style="display:none;"></div>

<script>
(function(){
    var _loaded = false;
    var _active = 'dashboard';

    // Sayfa yüklendiğinde URL hash'e göre tab seç
    if (window.location.hash === '#duyurular') {
        document.addEventListener('DOMContentLoaded', function(){ switchDashTab('bulletins'); });
    }

    window.switchDashTab = function(tab) {
        _active = tab;
        // Dashboard içeriği
        var dashContent = document.getElementById('dash-main-content');
        var bltPanel    = document.getElementById('panel-bulletins');
        var tabDash     = document.getElementById('tab-dashboard');
        var tabBlt      = document.getElementById('tab-bulletins');

        var activeStyle = 'border-bottom-color: var(--c-accent, #1e40af); color: var(--c-accent, #1e40af);';
        var inactStyle  = 'border-bottom-color: transparent; color: var(--muted, #64748b);';

        if (tab === 'bulletins') {
            if (dashContent) dashContent.style.display = 'none';
            if (bltPanel)    bltPanel.style.display    = 'block';
            tabDash.style.cssText += inactStyle;
            tabBlt.style.cssText  += activeStyle;
            window.location.hash = 'duyurular';
            if (!_loaded) {
                bltPanel.innerHTML = '<div style="text-align:center;padding:40px;color:var(--muted,#64748b);">Yükleniyor...</div>';
                fetch('/bulletins/partial', { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(function(html) {
                        bltPanel.innerHTML = html;
                        _loaded = true;
                    });
            }
        } else {
            if (dashContent) dashContent.style.display = 'block';
            if (bltPanel)    bltPanel.style.display    = 'none';
            tabDash.style.cssText += activeStyle;
            tabBlt.style.cssText  += inactStyle;
            window.location.hash = '';
        }
    };

    // İlk render: aktif tab stilini ayarla
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('tab-dashboard').style.cssText +=
            'border-bottom-color: var(--c-accent, #1e40af); color: var(--c-accent, #1e40af);';
    });
})();
</script>

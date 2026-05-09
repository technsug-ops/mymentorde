{{--
    Tema Modu Guard — Manager onayına göre dark/minimalist toggle butonlarını
    gizler ve mevcut localStorage tercihlerini sıfırlar.

    Kullanım: <head> veya body sonunda layout'a @include('partials.theme-mode-guard')

    Default: ikisi de izinli — bu durumda hiçbir script yazılmaz (overhead yok).
--}}
@if(!($themeDarkAllowed ?? true) || !($themeMinimalistAllowed ?? true))
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var darkAllowed = @json($themeDarkAllowed ?? true);
    var minAllowed  = @json($themeMinimalistAllowed ?? true);

    // 1) Manager kapatmışsa mevcut localStorage tercihini sıfırla, html attribute/class temizle
    if (!darkAllowed) {
        try { localStorage.removeItem('mentorde_dark'); } catch(e){}
        document.documentElement.removeAttribute('data-theme');
    }
    if (!minAllowed) {
        try { localStorage.removeItem('mentorde_design'); } catch(e){}
        document.documentElement.classList.remove('jm-minimalist');
    }

    // 2) DOM hazır olduğunda toggle butonlarını gizle (CSP-safe, addEventListener)
    function hideButtons(){
        if (!darkAllowed) {
            ['dm-btn', 'theme-toggle'].forEach(function(id){
                var el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
        }
        if (!minAllowed) {
            var d = document.getElementById('design-btn');
            if (d) d.style.display = 'none';
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideButtons);
    } else {
        hideButtons();
    }
})();
</script>
@endif

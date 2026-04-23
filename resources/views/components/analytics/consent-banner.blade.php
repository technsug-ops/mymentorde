{{--
  GDPR Consent Banner — Analytics opt-in/opt-out
  Cookie: analytics_consent = "true" | "false" | unset
  Kabul edilmedikçe PostHog init edilmez.
--}}
@php
    $__consent = request()->cookie('analytics_consent');
    $__showBanner = $__consent === null;
@endphp

@if($__showBanner)
<div id="analytics-consent-banner" role="dialog" aria-live="polite" style="
    position: fixed;
    bottom: 16px;
    left: 16px;
    right: 16px;
    max-width: 520px;
    margin-inline: auto;
    background: var(--surface, #ffffff);
    color: var(--text, #0f172a);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    padding: 16px 18px;
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
    z-index: 9999;
    font-size: 14px;
    line-height: 1.5;
">
    <div style="margin-bottom: 10px;">
        <strong style="display:block; margin-bottom: 6px;">🍪 Analiz çerezleri</strong>
        <span style="color: var(--muted, #64748b);">
            Deneyimini iyileştirmek için anonim kullanım analitiği topluyoruz (PostHog).
            Kişisel veri toplanmaz, tercihin istediğinde değiştirilebilir.
        </span>
    </div>
    <div style="display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap;">
        <button type="button" id="analytics-consent-reject" style="
            background: transparent;
            color: var(--muted, #64748b);
            border: 1px solid var(--border, #e2e8f0);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
        ">Reddet</button>
        <button type="button" id="analytics-consent-accept" style="
            background: var(--c-accent, #2563eb);
            color: white;
            border: 1px solid var(--c-accent, #2563eb);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        ">Kabul et</button>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    function setConsent(value) {
        var d = new Date();
        d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
        document.cookie = 'analytics_consent=' + value
            + '; expires=' + d.toUTCString()
            + '; path=/; SameSite=Lax';
        var banner = document.getElementById('analytics-consent-banner');
        if (banner) banner.remove();
        if (value === 'true') {
            // Sayfayı reload et ki PostHog snippet'i yüklensin
            window.location.reload();
        }
    }
    document.getElementById('analytics-consent-accept')?.addEventListener('click', function(){ setConsent('true'); });
    document.getElementById('analytics-consent-reject')?.addEventListener('click', function(){ setConsent('false'); });
})();
</script>
@endif

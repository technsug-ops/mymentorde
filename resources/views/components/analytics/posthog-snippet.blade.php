{{--
  PostHog JS Snippet — frontend event capture + session replay
  Sadece consent=true ise yüklenir (GDPR).
  Identify: login'deyse user_id'yle identify edilir, değilse anonymous.

  Super properties: portal, company_id, environment — her event'e otomatik eklenir.
--}}
@php
    $__consent = request()->cookie('analytics_consent');
    $__posthogKey = config('services.posthog.api_key');
    $__posthogHost = config('services.posthog.host', 'https://eu.posthog.com');
    $__posthogEnabled = config('services.posthog.enabled', true) && !empty($__posthogKey);
@endphp

@if($__consent === 'true' && $__posthogEnabled)
<script nonce="{{ $cspNonce ?? '' }}">
!function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init me ms ay Ht Rt getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug getPageViewId captureTraceFeedback captureTraceMetric".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);

posthog.init('{{ $__posthogKey }}', {
    api_host: '{{ $__posthogHost }}',
    capture_pageview: true,
    capture_pageleave: true,
    person_profiles: 'identified_only',
    disable_session_recording: false,
    autocapture: {
        css_selector_allowlist: ['[data-track]', '[data-ph]']
    },
    loaded: function(ph) {
        // Super properties — her event'e otomatik eklenir
        ph.register({
            portal: '{{ $portal ?? (auth()->check() ? (auth()->user()->role ?? "authenticated") : "public") }}',
            app_version: '{{ config('app.version', '1.0.0') }}',
            environment: '{{ app()->environment() }}',
            @auth
            company_id: {{ auth()->user()->company_id ?? 'null' }},
            @endauth
        });

        @auth
        // Login'deyse user'ı identify et
        ph.identify('{{ auth()->id() }}', {
            role: '{{ auth()->user()->role ?? "" }}',
            @if(!empty(auth()->user()->email))
            email_domain: '{{ explode("@", auth()->user()->email)[1] ?? "" }}',
            email_hash: '{{ hash("sha256", auth()->user()->email) }}',
            @endif
            @if(auth()->user()->company_id)
            company_id: {{ auth()->user()->company_id }},
            @endif
        });
        @endauth
    }
});

// CTA / data-track button handler
document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-track]');
    if (!el) return;
    var eventName = el.getAttribute('data-track');
    if (!eventName) return;

    var props = {};
    for (var i = 0; i < el.attributes.length; i++) {
        var a = el.attributes[i];
        if (a.name.indexOf('data-ph-') === 0) {
            props[a.name.substring(8).replace(/-/g, '_')] = a.value;
        }
    }
    if (el.textContent) {
        props.element_text = el.textContent.trim().substring(0, 100);
    }

    if (window.posthog) window.posthog.capture(eventName, props);
});
</script>
@endif

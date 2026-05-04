@props(['theme' => 'auto', 'size' => 'normal', 'action' => null])
{{--
    Cloudflare Turnstile widget — public form'lar icin bot/spam koruma.

    Kullanim:
        <x-turnstile />
        <x-turnstile theme="dark" />
        <x-turnstile size="invisible" action="lead_capture" />

    Backend dogrulama: $request->validate(['cf_turnstile_response' => ['required', new \App\Rules\TurnstileToken()]])
    Form'da otomatik <input name="cf_turnstile_response"> render edilir.

    site_key boşsa (env eksik) widget render edilmez — formlar normal calisir.
--}}
@php
    $__siteKey = (string) config('services.turnstile.site_key', '');
    $__enabled = (bool) config('services.turnstile.enabled', false);
@endphp
@if($__enabled && $__siteKey !== '')
    <div class="cf-turnstile-wrap" style="margin: 12px 0;">
        <div class="cf-turnstile"
             data-sitekey="{{ $__siteKey }}"
             data-theme="{{ $theme }}"
             data-size="{{ $size }}"
             data-callback="onTurnstileSuccess_{{ $size }}"
             data-error-callback="onTurnstileError_{{ $size }}"
             @if($action) data-action="{{ $action }}" @endif></div>
        <input type="hidden" name="cf_turnstile_response" value="">
        <noscript>
            <div style="padding:10px 14px;background:#fef3c7;border:1px solid #fbbf24;border-radius:8px;color:#92400e;font-size:12.5px;">
                ⚠ Bu form için JavaScript gerekli — tarayıcı ayarlarınızı kontrol edin.
            </div>
        </noscript>
    </div>

    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <script nonce="{{ $cspNonce ?? '' }}">
        (function(){
            // Token'i form içindeki hidden input'a bas
            window.onTurnstileSuccess_normal = window.onTurnstileSuccess_invisible = function(token) {
                document.querySelectorAll('input[name="cf_turnstile_response"]').forEach(function(inp){
                    inp.value = token;
                });
            };
            window.onTurnstileError_normal = window.onTurnstileError_invisible = function() {
                console.warn('Turnstile error');
            };
        })();
        </script>
    @endonce
@endif

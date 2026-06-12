{{--
    Real-time notification toast bootstrap partial.
    Tum portal layout'larina (manager/senior/guest/student/platform) dahil edilir.

    Yaptiklari:
      1. window.userId, window.userRole, window.companyId set eder
         (notifications.js bunlari kullanir)
      2. <audio> preload (sessiz autoplay block edilmeyecek sekilde)
      3. Toast stack container'ini DOM'a eklemez — JS lazy olusturur
      4. Echo + notifications.js Vite bundle'larini yukler

    CSP-safe: tum inline script'ler $cspNonce ile imzali.
--}}
@auth
    @php
        $__authUser  = auth()->user();
        $__userRole  = (string) ($__authUser->role ?? '');
        $__companyId = (int) ($__authUser->company_id ?? 0);
    @endphp

    {{-- Toast stack: notifications.js lazy ekliyor; placeholder gerekmiyor --}}

    {{-- Sessiz ses preload — kullanici sayfa ile etkilesime gectiginde Audio() calabilir --}}
    <audio id="mentorde-notif-audio" src="/sounds/notif.mp3" preload="auto" style="display:none;"></audio>

    <script nonce="{{ $cspNonce ?? '' }}">
        window.userId    = {{ (int) $__authUser->id }};
        window.userRole  = @json($__userRole);
        window.companyId = {{ $__companyId }};
        // PUSHER_DEBUG flag dev'de konsola state degisimi loglar
        window.__PUSHER_DEBUG = {{ app()->environment('local') ? 'true' : 'false' }};
    </script>

    {{-- Vite bundle: laravel-echo + pusher-js + toast UI logic --}}
    @vite(['resources/js/app.js'])
@endauth

{{--
    Application Guides — Button Bar Partial (ADDON / SEPARABLE)

    Tüm parça try/catch + Route::has() + @includeIf ile korumalı:
    - config/application_guides.php silinse → boş array fallback → loop boş
    - ApplicationGuideController silinse → Route::has() false → buton yok
    - Bu partial silinse → çağıran @includeIf sessizce skip eder
    - ModuleAccess::enabled('application_guides') false → tüm bar gizli

    Parametreler:
      - $guestId    (int)   guest application id
      - $studentId  (?string) varsa student id (öğrenci sayfası için)
--}}
@php
    try {
        if (! \App\Support\ModuleAccess::enabled('application_guides')) {
            return;
        }
        $_agGuides = (array) config('application_guides', []);
        $_agRoute  = ($studentId ?? null)
            ? 'manager.student.application-guide.show'
            : 'manager.application-guide.show';
        $_agParam0 = ($studentId ?? null) ?: ($guestId ?? null);
    } catch (\Throwable $_agEx) {
        $_agGuides = [];
    }
@endphp

@if(! empty($_agGuides) && \Illuminate\Support\Facades\Route::has($_agRoute) && $_agParam0)
    @foreach($_agGuides as $_agSlug => $_agData)
        @php
            try {
                $_agMeta = (array) ($_agData['meta'] ?? []);
                $_agIcon = (string) ($_agMeta['icon'] ?? '📄');
                $_agTitle = (string) ($_agMeta['title'] ?? $_agSlug);
                // Compact label — "APS Başvuru Rehberi" yerine "APS" gibi kısaltma
                $_agShort = strtok($_agTitle, ' ');
                $_agColor = (string) ($_agMeta['color'] ?? '#475569');
                $_agColor2 = (string) ($_agMeta['color2'] ?? $_agColor);
                $_agUrl = route($_agRoute, [$_agParam0, $_agSlug]);
            } catch (\Throwable $_agEx) {
                continue;
            }
        @endphp
        <a href="{{ $_agUrl }}"
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:linear-gradient(135deg,{{ $_agColor }},{{ $_agColor2 }}); color:#fff; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none;">
            {{ $_agIcon }} {{ $_agShort }} →
        </a>
    @endforeach
@endif

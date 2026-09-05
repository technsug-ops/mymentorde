{{--
    Yazılım sağlayıcı künyesi — "Powered by techNSUG".

    ── NEDEN AYRI PARTIAL ──────────────────────────────────────────────
    Bu satır 26 public sayfada geçiyor (13 partner şablonu + MentorDE'nin
    kendi sayfaları). Metni dosyalara dağıtmak, sağlayıcı adı bir gün
    değiştiğinde 26 yerde arama yapmak demekti. Ad ve adres tek kaynaktan
    okunur: config('brand.vendor').

    ⚠ Kiracı markası DEĞİL. config('brand.name') şirkete göre değişir
    (MentorDE / YourGermanUni / partner firma); burası yazılımı yapan
    taraftır ve kiracıya göre değişmez.

    ⚠ White-label sınırı ÇAĞIRANDA. Partner şablonlarında bu partial
    `$showBadge` koşulunun İÇİNDE duruyor: bayi "rozeti kapat" dediğinde
    hiçbir üçüncü taraf markası basılmaz — söz verilen tam white-label
    bozulmamalı.

    Renk taşımaz (`color:inherit`), koyu ve açık footer'larda aynı çalışır.
--}}
@php
    $vendorName = trim((string) config('brand.vendor.name', ''));
    $vendorUrl  = trim((string) config('brand.vendor.url', ''));
@endphp
@if($vendorName !== '')
    <span class="vendor-credit">Powered by @if($vendorUrl !== '')<a href="{{ $vendorUrl }}" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;text-underline-offset:2px;">{{ $vendorName }}</a>@else{{ $vendorName }}@endif</span>
@endif

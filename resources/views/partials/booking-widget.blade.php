{{--
    Booking embed widget — guest/student dashboard'larında "Bir uzmanla görüş" CTA.
    Kullanım: @include('partials.booking-widget')

    Auto-detects portal:
      - auth user role 'student' → student.booking.directory
      - auth user role 'guest'   → guest.booking.directory
      - diğer → render etmez (hidden)
--}}

@php
    $__bw_user = auth()->user();
    $__bw_role = $__bw_user?->role ?? null;
    $__bw_route = $__bw_role === 'student'
        ? 'student.booking.directory'
        : ($__bw_role === 'guest' ? 'guest.booking.directory' : null);

    // Sadece login + uygun rol VE booking module aktif olduğunda render et
    $__bw_show = $__bw_route !== null && function_exists('feature_enabled')
        ? true
        : $__bw_route !== null;

    $__bw_preview = collect();
    if ($__bw_show) {
        try {
            $__bw_settings = \App\Models\SeniorBookingSetting::query()
                ->withoutGlobalScopes()
                ->where('is_active', true)
                ->whereNotNull('public_slug')
                ->orderBy('updated_at', 'desc')
                ->take(3)
                ->get(['senior_user_id', 'public_slug', 'display_name']);

            $__bw_seniorIds = $__bw_settings->pluck('senior_user_id')->all();
            $__bw_seniors = \App\Models\User::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $__bw_seniorIds)
                ->get(['id', 'name', 'photo_url'])
                ->keyBy('id');

            $__bw_preview = $__bw_settings->map(function ($s) use ($__bw_seniors) {
                $u = $__bw_seniors->get($s->senior_user_id);
                return [
                    'name'      => $u?->name ?? $s->display_name ?? 'Danışman',
                    'photo_url' => $u?->photo_url,
                    'slug'      => $s->public_slug,
                ];
            });
        } catch (\Throwable $e) {
            // Widget asla parent sayfayı kırmamalı — sessizce gizle
            $__bw_show = false;
        }
    }
@endphp

@if($__bw_show && $__bw_preview->isNotEmpty())
    <div style="background:linear-gradient(135deg,#7e58bf 0%,#1e40af 100%);border-radius:16px;padding:22px;color:#fff;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:18px;box-shadow:0 6px 18px rgba(126,88,191,.25);">

        <div style="flex:1;min-width:240px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:11.5px;text-transform:uppercase;letter-spacing:.08em;opacity:.85;font-weight:700;">
                <x-icon name="calendar" size="14" aria-label="Randevu" />
                Uzmanla Görüş
            </div>
            <h3 style="margin:0 0 6px;font-size:18px;font-weight:800;line-height:1.3;">
                Sorularını bir uzmana sor
            </h3>
            <p style="margin:0;font-size:13px;opacity:.92;line-height:1.5;max-width:380px;">
                Online randevu al, başvuru/vize/üniversite konularında birebir görüşme yap.
            </p>
        </div>

        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <div style="display:flex;align-items:center;">
                @foreach($__bw_preview as $i => $p)
                    @if($p['photo_url'])
                        <img src="{{ $p['photo_url'] }}" alt="{{ $p['name'] }}"
                             title="{{ $p['name'] }}"
                             style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2.5px solid #fff;margin-left:{{ $i === 0 ? '0' : '-10px' }};box-shadow:0 2px 4px rgba(0,0,0,.15);">
                    @else
                        <div title="{{ $p['name'] }}"
                             style="width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.25);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;border:2.5px solid #fff;margin-left:{{ $i === 0 ? '0' : '-10px' }};box-shadow:0 2px 4px rgba(0,0,0,.15);">
                            {{ strtoupper(mb_substr($p['name'], 0, 1)) }}
                        </div>
                    @endif
                @endforeach
            </div>
            <a href="{{ route($__bw_route) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#fff;color:#1e40af;border-radius:10px;font-size:13.5px;font-weight:800;text-decoration:none;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,.12);">
                Tümünü Gör
                <x-icon name="chevron-right" size="14" aria-label="Devam" />
            </a>
        </div>

    </div>
@endif

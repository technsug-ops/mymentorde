{{--
  Manager portal Option B hero — parametrik.

  Kullanım:
    @include('partials.manager-hero', [
        'label' => 'Aday Öğrenci Yönetimi',
        'title' => 'Aday Öğrenciler',
        'sub'   => 'Tüm lead akışı, atamalar ve dönüşüm durumu bir arada.',
        'icon'  => '👥',
        'bg'    => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=1400&q=80',
        'tone'  => 'blue',  // blue|purple|green|amber|red|slate|indigo|teal
        'stats' => [
            ['icon' => '📊', 'text' => '142 toplam'],
            ['icon' => '✅', 'text' => '28 dönüşen'],
        ],
    ])
--}}
@php
    $mhTones = [
        'blue'   => ['start' => 'rgba(12,74,110,.93)',  'end' => 'rgba(14,165,233,.82)',  'fallback' => '#0c4a6e'],
        'purple' => ['start' => 'rgba(76,29,149,.92)',  'end' => 'rgba(124,58,237,.85)',  'fallback' => '#4c1d95'],
        'green'  => ['start' => 'rgba(6,78,59,.93)',    'end' => 'rgba(5,150,105,.82)',   'fallback' => '#064e3b'],
        'amber'  => ['start' => 'rgba(124,45,18,.93)',  'end' => 'rgba(234,88,12,.82)',   'fallback' => '#7c2d12'],
        'red'    => ['start' => 'rgba(127,29,29,.93)',  'end' => 'rgba(220,38,38,.82)',   'fallback' => '#7f1d1d'],
        'slate'  => ['start' => 'rgba(30,41,59,.95)',   'end' => 'rgba(71,85,105,.88)',   'fallback' => '#1e293b'],
        'indigo' => ['start' => 'rgba(49,46,129,.92)',  'end' => 'rgba(99,102,241,.85)',  'fallback' => '#312e81'],
        'teal'   => ['start' => 'rgba(19,78,74,.93)',   'end' => 'rgba(20,184,166,.82)',  'fallback' => '#134e4a'],
        'rose'   => ['start' => 'rgba(131,24,67,.93)',  'end' => 'rgba(219,39,119,.82)',  'fallback' => '#831843'],
    ];
    $mh = $mhTones[$tone ?? 'blue'] ?? $mhTones['blue'];
    $mhBg = $bg ?? '';
@endphp
<style>
.mgr-hero {
    color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:{{ $mh['fallback'] }}@if($mhBg !== '') url('{{ $mhBg }}') center/cover@endif;
}
.mgr-hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, {{ $mh['start'] }} 0%, {{ $mh['end'] }} 100%);
}
.mgr-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.mgr-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.mgr-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.85; }
.mgr-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.mgr-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.mgr-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:600px; }
.mgr-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.mgr-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.mgr-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
@media (max-width:640px){
    .mgr-hero-body { gap:14px; padding:18px; align-items:flex-start; }
    .mgr-hero-title { font-size:20px; }
    .mgr-hero-sub { font-size:12px; }
    .mgr-hero-icon { font-size:36px; }
}
</style>
<div class="mgr-hero">
    <div class="mgr-hero-body">
        <div class="mgr-hero-main">
            <div class="mgr-hero-label"><span class="mgr-hero-marker"></span>{{ $label ?? '' }}</div>
            <h1 class="mgr-hero-title">{{ $title ?? '' }}</h1>
            @if(!empty($sub))<div class="mgr-hero-sub">{{ $sub }}</div>@endif
            @if(!empty($stats))
            <div class="mgr-hero-stats">
                @foreach($stats as $st)
                    <span class="mgr-hero-stat">{{ $st['icon'] ?? '' }} {{ $st['text'] ?? '' }}</span>
                @endforeach
            </div>
            @endif
        </div>
        <div class="mgr-hero-icon">{{ $icon ?? '' }}</div>
    </div>
</div>

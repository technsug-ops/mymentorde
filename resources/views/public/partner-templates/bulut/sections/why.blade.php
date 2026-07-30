{{-- ═══ NEDEN BİZ + EKİP + ROZET ═══ --}}
<div class="wrap sec">
    <div class="sec-head">
        <span class="pill-lbl">Neden biz</span>
        <h2 class="h2">Berrak sürecin arkasındaki sistem</h2>
    </div>
    <div class="grid" style="--min:230px;--cols:{{ $cols(count($whyUs)) }};margin-bottom:{{ (!empty($team) || $showBadge) ? '16px' : '0' }};">
        @foreach($whyUs as $w)
            <div class="glass">
                <span class="svc-ic" style="width:46px;height:46px;border-radius:15px;font-size:23px;margin-bottom:14px;">{!! $icon($w['icon']) !!}</span>
                <h3 style="font:700 16.5px/1.3 var(--font);margin:0 0 7px;">{{ $w['title'] }}</h3>
                <p style="font-size:13.5px;line-height:1.6;color:var(--muted);margin:0;">{{ $w['desc'] }}</p>
            </div>
        @endforeach
    </div>

    @if(!empty($team) || $showBadge)
        <div class="grid" style="--min:250px;--cols:{{ $cols(count($team) + ($showBadge ? 1 : 0)) }};">
            @foreach($team as $m)
                <div class="glass member">
                    <span class="avatar">
                        @if(($m['photo'] ?? '') !== '')
                            <img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">
                        @else
                            {{ mb_strtoupper(mb_substr($m['name'], 0, 1)) }}
                        @endif
                    </span>
                    <div class="who">
                        <div>
                            <b>{{ $m['name'] }}</b>
                            @if(($m['title'] ?? '') !== '')<span>{{ $m['title'] }}</span>@endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Rozet kapalıysa MentorDE adı sayfada HİÇ geçmez (tam white-label) --}}
            @if($showBadge)
                <div class="trust">
                    <span class="trust-ic">{!! $icon('shield') !!}</span>
                    <div>
                        <b>{{ config('brand.name', 'MentorDE') }} Yetkili Partneri</b>
                        <p>Başvuru, vize ve yerleşim süreçleriniz resmi partner ağı ve dijital altyapı üzerinden yürütülür.</p>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

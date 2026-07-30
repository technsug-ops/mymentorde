{{-- ═══ NEDEN BİZ + EKİP + ROZET ═══ --}}
<div class="wrap sec">
    <div class="sec-head">
        <span class="lbl">Neden biz</span>
        <h2 class="h2">Kurumsal titizlik, tek sorumlu</h2>
    </div>
    <div class="grid" style="--min:230px;--cols:{{ $cols(count($whyUs)) }};margin-bottom:{{ (!empty($team) || $showBadge) ? '18px' : '0' }};">
        @foreach($whyUs as $w)
            <div class="card">
                <span class="card-ic">{!! $icon($w['icon']) !!}</span>
                <h3>{{ $w['title'] }}</h3>
                <p>{{ $w['desc'] }}</p>
            </div>
        @endforeach
    </div>

    @if(!empty($team) || $showBadge)
        <div class="grid" style="--min:250px;--cols:{{ $cols(count($team) + ($showBadge ? 1 : 0)) }};">
            @foreach($team as $m)
                <div class="member">
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

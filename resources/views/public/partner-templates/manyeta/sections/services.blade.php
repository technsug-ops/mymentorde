{{-- ═══ HİZMETLER — numaralı SATIR listesi (bu şablonun imzası) ═══ --}}
<div id="hizmetler" class="wrap sec">
    <div class="sec-head" style="max-width:560px;margin-bottom:12px;">
        <span class="lbl">Hizmetler</span>
        <h2 class="h2">Neler yapıyoruz?</h2>
    </div>
    <div class="rows">
        @foreach($services as $i => $s)
            <div class="row-item">
                <div class="row-no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</div>
                <div>
                    <h3>{{ $s['title'] }}</h3>
                    <p>{{ $s['desc'] }}</p>
                </div>
                @if(!empty($s['items']))
                    <div class="row-tags">
                        @foreach($s['items'] as $item)<span><i></i>{{ $item }}</span>@endforeach
                    </div>
                @else
                    <div></div>
                @endif
            </div>
        @endforeach
    </div>
</div>

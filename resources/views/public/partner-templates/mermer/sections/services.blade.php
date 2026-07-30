{{-- ═══ HİZMETLER — sticky başlık + numaralı satırlar (her zaman dolu) ═══ --}}
<div id="hizmetler" class="wrap sec">
    <div class="split">
        <div class="split-head">
            <span class="lbl">Hizmetler</span>
            <h2 class="h2">Her ayrıntı, tek çatı altında</h2>
            <p style="font-size:14.5px;line-height:1.65;color:var(--muted);margin:0;">Başvurudan yerleşime uzanan hizmet alanları; hepsi tek plan, tek sorumlu, tek takvim ile yürür.</p>
        </div>
        <div>
            @foreach($services as $i => $s)
                <div class="num-row">
                    <div class="no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</div>
                    <div>
                        <h3>{{ $s['title'] }}</h3>
                        <p>{{ $s['desc'] }}</p>
                        @if(!empty($s['items']))
                            <div class="items">
                                @foreach($s['items'] as $item)<span><i>—</i>{{ $item }}</span>@endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ═══ YORUMLAR — yalnız partnerin girdiği GERÇEK yorumlar ═══
    İlk yorum koyu bantta kullanıldıysa burada tekrar edilmez. --}}
@php $rest = array_slice($testimonials, 1); @endphp
@if(!empty($rest))
<div class="wrap sec">
    <div class="sec-head" style="max-width:560px;margin-bottom:36px;">
        <span class="lbl">Öğrenci yorumları</span>
        <h2 class="h2">Başarı hikâyeleriyle büyüyoruz</h2>
    </div>
    <div class="grid" style="--gap:18px;--cols:{{ $cols(count($rest)) }};">
        @foreach($rest as $t)
            <figure class="card quote" style="margin:0;">
                <p>“{{ $t['text'] }}”</p>
                @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                    <figcaption class="who">
                        <span class="avatar">{{ mb_strtoupper(mb_substr($t['name'] !== '' ? $t['name'] : $siteName, 0, 1)) }}</span>
                        <div>
                            @if(($t['name'] ?? '') !== '')<b>{{ $t['name'] }}</b>@endif
                            @if(($t['school'] ?? '') !== '')<span>{{ $t['school'] }}</span>@endif
                        </div>
                    </figcaption>
                @endif
            </figure>
        @endforeach
    </div>
</div>
@endif

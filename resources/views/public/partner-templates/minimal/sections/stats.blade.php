{{-- ═══ İSTATİSTİKLER ═══
     Sadece partnerin girdiği sayılar — boşsa bölüm hiç basılmaz. --}}
@if(!empty($stats))
<section class="sec sec-top">
    <div class="wrap">
        <div class="stat-cols" style="--n:{{ max(1, min(count($stats), 2)) }};">
            @foreach($stats as $st)
                <div class="stat-row"><span class="sv serif">{{ $st['value'] }}</span><span class="sl">{{ $st['label'] }}</span></div>
            @endforeach
        </div>
    </div>
</section>
@endif

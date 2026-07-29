{{-- ═══ İSTATİSTİK BANDI (boşsa hiç basma — uydurma rakam yok) ═══ --}}
@if(!empty($stats))
<section class="wrap">
    <div>
        @foreach($stats as $st)
            <div><b>{{ $st['value'] }}</b> <span>{{ $st['label'] }}</span></div>
        @endforeach
    </div>
</section>
@endif

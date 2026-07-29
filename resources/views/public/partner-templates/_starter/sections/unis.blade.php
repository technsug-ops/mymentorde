{{-- ═══ ÜNİVERSİTE ŞERİDİ (boşsa hiç basma — uydurma üniversite yazma) ═══ --}}
@if(!empty($universities))
<section class="wrap">
    <span>Öğrencilerimizin yerleştiği üniversiteler</span>
    @foreach($universities as $u)<span>{{ $u }}</span>@endforeach
</section>
@endif

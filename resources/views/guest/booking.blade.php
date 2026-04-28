@extends('guest.layouts.app')

@section('title', 'Randevu Al')

@push('head')
<style>
.gb-wrap { max-width:980px; margin:0 auto; padding:0 0 32px; }
.gb-head { background:linear-gradient(135deg,#1e3a8a,#1d4ed8); border-radius:14px; padding:22px 26px; color:#fff; margin-bottom:18px; box-shadow:0 4px 14px rgba(30,58,138,.15); }
.gb-head h1 { font-size:20px; font-weight:800; margin:0 0 6px; }
.gb-head p  { font-size:13.5px; margin:0; color:rgba(255,255,255,.92); line-height:1.55; }
.gb-head .gb-senior { display:inline-flex; align-items:center; gap:6px; padding:4px 10px 4px 8px; background:rgba(255,255,255,.16); border-radius:999px; font-size:12px; font-weight:700; margin-top:8px; }
.gb-frame-wrap { position:relative; background:#fff; border:1px solid var(--u-line,#e2e8f0); border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.gb-frame { width:100%; min-height:780px; border:0; display:block; }
.gb-info { display:flex; gap:10px; align-items:flex-start; margin-top:14px; padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; }
.gb-info .ico { font-size:18px; flex-shrink:0; }
.gb-info .txt { font-size:12.5px; color:#475569; line-height:1.55; }
.gb-info strong { color:#1e3a8a; }
@media(max-width:740px){
    .gb-frame { min-height:680px; }
}
</style>
@endpush

@section('content')
<div class="gb-wrap">
    <div class="gb-head">
        <h1>📅 Randevu Al</h1>
        <p>Danışmanının takviminden uygun bir saat seç. Bilgilerin sistemden otomatik gelir, tekrar girmen gerekmez.</p>
        @if(!empty($seniorName))
            <span class="gb-senior">👤 Danışman: {{ $seniorName }}</span>
        @endif
    </div>

    <div class="gb-frame-wrap">
        <iframe class="gb-frame"
                src="{{ $bookingEmbedUrl }}"
                title="Randevu takvimi"
                loading="eager"
                referrerpolicy="same-origin"></iframe>
    </div>

    <div class="gb-info">
        <span class="ico">💡</span>
        <span class="txt">
            <strong>Otomatik onay:</strong> Saat seçip onayladığında randevu danışmanına anlık gönderilir.
            E-posta ile takvim daveti alırsın. İptal etmen gerekirse aynı sayfadan yapabilirsin.
        </span>
    </div>
</div>
@endsection

@extends('student.layouts.app')
@section('title', 'Vize & Sperrkonto Rehberi')
@section('page_title', 'Vize & Sperrkonto Rehberi')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist .card[style*="gradient"] {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border: 1px solid rgba(0,0,0,.10) !important;
}
.jm-minimalist .card[style*="gradient"] [style*="opacity"] { color: var(--u-muted, #666) !important; opacity: 1 !important; }
.vg-step { display:flex; gap:16px; padding:14px 0; }
.vg-step:not(:last-child) { border-bottom:1px solid var(--u-line,#e2e8f0); }
.vg-step-num { width:36px; height:36px; border-radius:50%; color:#fff; display:flex; align-items:center; justify-content:center; font-size:var(--tx-sm); font-weight:800; flex-shrink:0; }
.vg-step-line { width:2px; flex:1; background:var(--u-line,#e2e8f0); margin:4px auto; min-height:18px; }
.vg-doc-row { display:flex; align-items:flex-start; gap:10px; padding:7px 0; border-bottom:1px solid var(--u-line,#f3f4f6); font-size:var(--tx-sm); }
.vg-note-row { display:flex; gap:12px; padding:9px 0; border-bottom:1px solid var(--u-line,#f3f4f6); }
.vg-faq-item { padding:10px 0; border-bottom:1px solid var(--u-line,#e2e8f0); }
</style>
@endpush

@section('content')
@php $rate = $eurTryRate ?? null; @endphp

{{-- Hero --}}
<div class="card" style="background:linear-gradient(to right,#0e7490,#0891b2);color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">Almanya Öğrenci Vizesi & Bloke Hesap</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">🛂 Vize & Sperrkonto Rehberi</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:580px;line-height:1.6;">
            Almanya öğrenci vizesi başvurusu, Sperrkonto (bloke hesap) açılışı ve gerekli belgeler — adım adım rehber.
        </div>
        @if($rate)
        <div style="margin-top:12px;display:inline-block;background:rgba(255,255,255,.15);border-radius:8px;padding:6px 14px;font-size:var(--tx-xs);font-weight:700;">
            1 EUR = {{ number_format($rate, 2) }} TRY &nbsp;·&nbsp; Sperrkonto ≈ ₺ {{ number_format(11208 * $rate, 0, ',', '.') }}
        </div>
        @endif
    </div>
</div>

{{-- Adım Adım Süreç --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Vize Başvuru Süreci — Adım Adım</div>
@php
$steps = [
    ['no'=>'1','title'=>'Kabul Mektubunu Al','desc'=>'Üniversiteden kesin kabul (Zulassungsbescheid) alınmalıdır. uni-assist üzerinden başvurduysan sonuç 6–8 hafta sürebilir.','icon'=>'🎓','color'=>'#7c3aed','badge'=>'Başlangıç'],
    ['no'=>'2','title'=>'APS Sertifikası Al','desc'=>'Türkiye\'den başvuranlar için çoğu üniversite APS sertifikası ister. Ankara Alman Büyükelçiliği\'nden alınır, 4–8 hafta sürer.','icon'=>'📜','color'=>'#0891b2','badge'=>'TR\'ye özgü'],
    ['no'=>'3','title'=>'Sperrkonto Aç','desc'=>'Fintiba, Coracle veya Deutsche Bank gibi onaylı sağlayıcılarda bloke hesap aç. Yıllık €11.208 yatırılmalıdır (€934 × 12 ay).','icon'=>'🏦','color'=>'#059669','badge'=>'Zorunlu'],
    ['no'=>'4','title'=>'Belgeleri Hazırla','desc'=>'Pasaport, biyometrik fotoğraf, kabul mektubu, dil belgesi, Sperrkonto belgesi, sağlık sigortası, diploma çevirileri.','icon'=>'📋','color'=>'#d97706','badge'=>'Kritik'],
    ['no'=>'5','title'=>'Konsolosluğa Randevu Al','desc'=>'Türkiye\'deki Alman Büyükelçiliği veya Başkonsolosluğundan online randevu alınır. Bekleme 4–16 hafta olabilir — erken al!','icon'=>'📅','color'=>'#dc2626','badge'=>'Erken Alın'],
    ['no'=>'6','title'=>'Vize Görüşmesi','desc'=>'Randevu günü tüm belgelerle konsolosluğa git. Görüşme 15–30 dakika sürer. Eksik belge olmamasına dikkat et.','icon'=>'🏛','color'=>'#475569','badge'=>'Görüşme'],
    ['no'=>'7','title'=>'Vize Onayı & Seyahat','desc'=>'Vize genellikle 4–8 haftada sonuçlanır. Onaylandıktan sonra Almanya\'ya gidebilirsin.','icon'=>'✈️','color'=>'#7c3aed','badge'=>'Son Adım'],
];
$stepsLeft  = array_slice($steps, 0, 4);
$stepsRight = array_slice($steps, 4);
@endphp
<div class="col2" style="margin-bottom:24px;">

    <div class="card">
        <div class="card-body" style="padding:20px 24px;">
            @foreach($stepsLeft as $step)
            <div class="vg-step">
                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                    <div class="vg-step-num" style="background:{{ $step['color'] }};">{{ $step['no'] }}</div>
                    @if(!$loop->last)<div class="vg-step-line"></div>@endif
                </div>
                <div style="padding-top:6px;flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                        <span style="font-weight:700;font-size:var(--tx-sm);">{{ $step['icon'] }} {{ $step['title'] }}</span>
                        <span class="badge info" style="font-size:var(--tx-xs);">{{ $step['badge'] }}</span>
                    </div>
                    <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">{{ $step['desc'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:20px 24px;">
            @foreach($stepsRight as $step)
            <div class="vg-step">
                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                    <div class="vg-step-num" style="background:{{ $step['color'] }};">{{ $step['no'] }}</div>
                    @if(!$loop->last)<div class="vg-step-line"></div>@endif
                </div>
                <div style="padding-top:6px;flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                        <span style="font-weight:700;font-size:var(--tx-sm);">{{ $step['icon'] }} {{ $step['title'] }}</span>
                        <span class="badge info" style="font-size:var(--tx-xs);">{{ $step['badge'] }}</span>
                    </div>
                    <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">{{ $step['desc'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>


{{-- Belgeler + Notlar --}}
<div class="col2" style="margin-bottom:24px;">

    <div class="card">
        <div class="card-body" style="padding:20px 24px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📋 Gerekli Belgeler</div>
            @foreach([
                ['Geçerli pasaport (6+ ay geçerliliği)',           'ok'],
                ['Biyometrik fotoğraf (Alman standardı)',           'ok'],
                ['Doldurulmuş vize başvuru formu',                  'ok'],
                ['Üniversite kabul mektubu (Zulassungsbescheid)',   'ok'],
                ['Sperrkonto bloke belgesi (€11.208)',              'ok'],
                ['Sağlık sigortası belgesi',                        'ok'],
                ['Dil belgesi (Almanca B2 / İngilizce C1)',         'ok'],
                ['Lise diploması + noter onaylı Almanca çeviri',    'ok'],
                ['Transkript + noter onaylı Almanca çeviri',        'ok'],
                ['APS sertifikası (TR\'den başvuranlar)',           'info'],
                ['Motivasyon mektubu (bazı konsolosl.)',            'warn'],
            ] as [$doc, $db])
            <div class="vg-doc-row">
                <span class="badge {{ $db }}" style="font-size:var(--tx-xs);flex-shrink:0;min-width:18px;text-align:center;">
                    {{ $db === 'ok' ? '✓' : ($db === 'info' ? 'i' : '?') }}
                </span>
                <span>{{ $doc }}</span>
            </div>
            @endforeach
            <div style="margin-top:10px;padding:8px 12px;background:var(--u-bg,#f8fafc);border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                <strong>✓</strong> Zorunlu &nbsp;·&nbsp; <strong>i</strong> Koşullu &nbsp;·&nbsp; <strong>?</strong> Konsolosluğa göre
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:20px 24px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">💡 Önemli Notlar</div>
            @foreach([
                ['💰','Sperrkonto tutarı','2024: yıllık €11.208 (aylık €934). Her yıl Ocak\'ta güncellenir.'],
                ['⏰','Randevu bekleme','Türkiye\'de 4–16 hafta bekleme olabilir. En erken tarihi al.'],
                ['📋','Vize geçerliliği','İlk vize 3 ay verilir. Almanya\'da Ausländerbehörde\'de uzatılır.'],
                ['🏧','Para çekme','Almanya\'ya geldikten sonra Sperrkonto\'dan aylık €934 çekilir.'],
                ['🏥','Sigorta','Vize ve üniversite kaydı için zorunlu. TK, AOK, DAK önerilir.'],
                ['🎓','APS','Türkiye\'den başvuranların büyük çoğunluğu için gereklidir.'],
            ] as [$ni, $nl, $nv])
            <div class="vg-note-row">
                <span style="font-size:var(--tx-xl);flex-shrink:0;line-height:1.5;">{{ $ni }}</span>
                <div>
                    <div style="font-size:var(--tx-sm);font-weight:700;margin-bottom:2px;">{{ $nl }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $nv }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- SSS --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="padding:20px 24px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:16px;">❓ Sık Sorulan Sorular</div>
        <div class="col2" style="margin-bottom:0;">
            @foreach([
                ['Sperrkonto olmadan vize alınır mı?','Hayır. Almanya öğrenci vizesi için Sperrkonto zorunludur. Nakit veya banka ekstresi kabul edilmez.'],
                ['Vize reddedilirse ne olur?','Ret gerekçesini öğrenip eksik belgeyi tamamlayarak yeniden başvurabilirsin. Danışmanınla süreci yönet.'],
                ['Hangi konsolosluğa başvurmalıyım?','İkamet adresine göre belirlenir. Türkiye\'de İstanbul, Ankara ve İzmir konsolosluğu bulunmaktadır.'],
                ['Para yatırmak ne kadar sürer?','Banka havalesinden sonra 3–7 iş günü. Bloke belgesi 1–3 gün ek süre alır.'],
                ['Dil belgesi şart mı?','Çoğu program için evet. Almanca program: B2/DSH/TestDaF; İngilizce: IELTS 6.0 veya TOEFL 80+.'],
                ['Öğrenci vizesiyle çalışabilir miyim?','Evet. Yılda 120 tam gün (240 yarım gün) çalışabilirsin. Ayrıca izin gerekmez.'],
            ] as [$fq, $fa])
            <div class="vg-faq-item">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:4px;">{{ $fq }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.6;">{{ $fa }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- CTA --}}
<div class="card" style="background:linear-gradient(to right,#0e7490,#0891b2);color:#fff;margin-bottom:16px;">
    <div class="card-body" style="padding:20px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
        <div>
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:4px;">Danışmanınla Konuş</div>
            <div style="font-size:var(--tx-sm);opacity:.85;">Vize veya Sperrkonto konusunda takıldığın bir nokta mı var? Danışmanın sana özel rehberlik sağlar.</div>
        </div>
        <a href="{{ route('student.messages') }}" class="btn" style="background:#fff;color:#0891b2;font-weight:700;flex-shrink:0;">
            Danışmana Yaz →
        </a>
    </div>
</div>

<div style="text-align:center;padding:8px 0;">
    <a href="/student/dashboard" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard</a>
    &nbsp;·&nbsp;
    <a href="{{ route('student.info.university-guide') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Üniversite Rehberi →</a>
</div>

@endsection

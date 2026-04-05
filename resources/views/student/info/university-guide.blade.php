@extends('student.layouts.app')
@section('title', 'Almanya Üniversite Rehberi')
@section('page_title', 'Almanya Üniversite Rehberi')
@push('head')
<style>
.col3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:20px; }
.col2 { display:grid; grid-template-columns:1fr 1fr;     gap:16px; margin-bottom:20px; }
@media(max-width:800px){ .col3{ grid-template-columns:1fr 1fr; } }
@media(max-width:600px){ .col3,.col2{ grid-template-columns:1fr; } }
</style>
@endpush
@section('content')

<div class="card" style="background:linear-gradient(to right,#2563eb,#0891b2);color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">Almanya'da Yükseköğretim</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">🎓 Üniversite Rehberi</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:560px;line-height:1.6;">
            Almanya'da 400+ yükseköğretim kurumu bulunmaktadır. Devlet üniversitelerinde eğitim büyük ölçüde <strong>ücretsizdir</strong>.
        </div>
    </div>
</div>

<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Üniversite Türleri</div>
<div class="col3">
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">🏛</div>
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:6px;">Universität (TU/Uni)</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:12px;">Teorik ve araştırma odaklı. Tıp, Hukuk, Doğa Bilimleri için ideal.</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <span class="badge ok" style="display:inline-block;width:fit-content;">TU München</span>
                <span class="badge ok" style="display:inline-block;width:fit-content;">Humboldt Uni Berlin</span>
                <span class="badge ok" style="display:inline-block;width:fit-content;">Uni Heidelberg</span>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">⚙️</div>
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:6px;">Fachhochschule (FH/HAW)</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:12px;">Uygulamalı bilimler. Mühendislik, İşletme, Tasarım için güçlü seçenek.</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <span class="badge info" style="display:inline-block;width:fit-content;">HAW Hamburg</span>
                <span class="badge info" style="display:inline-block;width:fit-content;">FH Aachen</span>
                <span class="badge info" style="display:inline-block;width:fit-content;">HS München</span>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">🎨</div>
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:6px;">Kunsthochschule / Musikhochschule</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:12px;">Sanat, Mimarlık ve Müzik alanlarında uzmanlaşmış kurumlar.</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <span class="badge warn" style="display:inline-block;width:fit-content;">UdK Berlin</span>
                <span class="badge warn" style="display:inline-block;width:fit-content;">HfG Offenbach</span>
                <span class="badge warn" style="display:inline-block;width:fit-content;">Bauhaus-Uni Weimar</span>
            </div>
        </div>
    </div>
</div>

<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Başvuru Portalları</div>
<div class="col3">
    <div class="card">
        <div class="card-body" style="padding:18px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:8px;color:var(--u-brand,#2563eb);">uni-assist e.V.</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:10px;">Uluslararası öğrenci belgelerini doğrulayan merkezi platform. Başvuru ücreti: ~75 EUR.</div>
            <span class="badge danger" style="font-size:var(--tx-xs);">Zorunlu (çoğu üniversite)</span>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:18px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:8px;color:var(--u-brand,#2563eb);">hochschulstart.de</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:10px;">Tıp, Eczacılık, Diş Hekimliği gibi kısıtlı bölümler için merkezi kontenjan dağıtım sistemi.</div>
            <span class="badge warn" style="font-size:var(--tx-xs);">Belirli bölümler için</span>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:18px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:8px;color:var(--u-brand,#2563eb);">Direkt Başvuru</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:10px;">Bazı üniversiteler kendi online portalleri üzerinden başvuruyu kabul eder.</div>
            <span class="badge ok" style="font-size:var(--tx-xs);">Ücretsiz</span>
        </div>
    </div>
</div>

<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Popüler Üniversite Şehirleri</div>
<div class="col2">
@php
$cities = [
    ['name'=>'Berlin','emoji'=>'🐻','desc'=>'TU Berlin, FU Berlin, HU Berlin. Canlı öğrenci hayatı, görece uygun kira.','tag'=>'Büyük Şehir','badge'=>'info'],
    ['name'=>'München','emoji'=>'🏔','desc'=>'TU München (Top 50). BMW, Siemens merkezi. Pahalı ama güçlü kariyer fırsatları.','tag'=>'Prestijli','badge'=>'ok'],
    ['name'=>'Hamburg','emoji'=>'⚓','desc'=>'HAW Hamburg, Uni Hamburg. Lojistik ve ticaret güçlü.','tag'=>'Liman Şehri','badge'=>'info'],
    ['name'=>'Frankfurt','emoji'=>'🏦','desc'=>'Goethe Uni. Bankacılık, finans ve ekonomi için ideal.','tag'=>'Finans','badge'=>'warn'],
    ['name'=>'Köln','emoji'=>'⛪','desc'=>'Uygun fiyatlı yaşam. Büyük üniversite, çok sayıda FH.','tag'=>'Uygun Fiyat','badge'=>'ok'],
    ['name'=>'Stuttgart','emoji'=>'🚗','desc'=>'Mercedes, Porsche, Bosch merkezi. Mühendislik için ideal.','tag'=>'Mühendislik','badge'=>'warn'],
];
@endphp
@foreach($cities as $c)
<div class="card" style="margin-bottom:0;">
    <div class="card-body" style="padding:16px 18px;display:flex;align-items:flex-start;gap:14px;">
        <div style="font-size:var(--tx-2xl);line-height:1;flex-shrink:0;">{{ $c['emoji'] }}</div>
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <span style="font-weight:700;font-size:var(--tx-sm);">{{ $c['name'] }}</span>
                <span class="badge {{ $c['badge'] }}" style="font-size:var(--tx-xs);">{{ $c['tag'] }}</span>
            </div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $c['desc'] }}</div>
        </div>
    </div>
</div>
@endforeach
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📅 Başvuru Takvimi (Kış Dönemi)</div>
        <div class="col2" style="margin-bottom:0;">
            <div>
                @foreach([['Oca–Mar','Belgeleri hazırla'],['Mar–May','uni-assist başvurusu'],['May–Haz','Kabul mektuplarını bekle'],['Tem–Ağu','Vize başvurusu'],['Eyl','Konut ara, Sperrkonto aç'],['Eki','Kayıt & oryantasyon']] as [$mon,$desc])
                <div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                    <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-brand,#2563eb);min-width:60px;">{{ $mon }}</span>
                    <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $desc }}</span>
                </div>
                @endforeach
            </div>
            <div>
                <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:10px;color:var(--u-ok,#16a34a);">Yaz Dönemi (SS) — Nisan</div>
                @foreach([['Tem–Eyl','Belgeleri hazırla'],['Eyl–Eki','uni-assist başvurusu'],['Eki–Kas','Kabul mektuplarını bekle'],['Ara–Oca','Vize başvurusu'],['Şub–Mar','Konut ara, Sperrkonto aç'],['Nis','Kayıt & oryantasyon']] as [$mon,$desc])
                <div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                    <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-ok,#16a34a);min-width:60px;">{{ $mon }}</span>
                    <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $desc }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div style="text-align:center;padding:8px 0;">
    <a href="{{ '/student/dashboard' }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard'a Dön</a>
</div>

@endsection

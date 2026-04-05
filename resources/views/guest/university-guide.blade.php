@extends('guest.layouts.app')
@section('title', 'Almanya Üniversite Rehberi')
@section('page_title', 'Almanya Üniversite Rehberi')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist .card[style*="gradient"] {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border: 1px solid rgba(0,0,0,.10) !important;
}
.jm-minimalist .card[style*="gradient"] [style*="opacity"] { color: var(--u-muted, #666) !important; opacity: 1 !important; }
</style>
@endpush

@section('content')

{{-- Başlık --}}
<div class="card" style="background:linear-gradient(135deg,#2563eb,#0891b2);color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">Almanya'da Yükseköğretim</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">🎓 Üniversite Rehberi</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:560px;line-height:1.6;">
            Almanya'da 400+ yükseköğretim kurumu bulunmaktadır. Devlet üniversitelerinde eğitim büyük ölçüde <strong>ücretsizdir</strong>.
            Doğru üniversite türünü ve şehrini seçmek başarınızın temelini oluşturur.
        </div>
    </div>
</div>

{{-- Üniversite Türleri --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Üniversite Türleri</div>
<div class="col3" style="margin-bottom:24px;">

    <a href="{{ route('guest.discover', ['cat' => 'uni-content']) }}" class="card" style="text-decoration:none;color:inherit;display:block;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">🏛</div>
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:6px;">Universität (TU/Uni)</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:12px;">
                Teorik ve araştırma odaklı. Tıp, Hukuk, Doğa Bilimleri için ideal.
                Almanya'nın en prestijli kurumları bu kategoridedir.
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <span class="badge ok" style="display:inline-block;width:fit-content;">TU München</span>
                <span class="badge ok" style="display:inline-block;width:fit-content;">Humboldt Uni Berlin</span>
                <span class="badge ok" style="display:inline-block;width:fit-content;">Uni Heidelberg</span>
            </div>
            <div style="margin-top:12px;font-size:.78rem;color:var(--u-brand,#2563eb);font-weight:600;">İçerikleri Keşfet →</div>
        </div>
    </a>

    <a href="{{ route('guest.discover', ['cat' => 'uni-content']) }}" class="card" style="text-decoration:none;color:inherit;display:block;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">⚙️</div>
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:6px;">Fachhochschule (FH/HAW)</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:12px;">
                Uygulamalı bilimler. Mühendislik, İşletme, Tasarım için güçlü seçenek.
                İş dünyasına entegrasyon hızlıdır, staj zorunlu.
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <span class="badge info" style="display:inline-block;width:fit-content;">HAW Hamburg</span>
                <span class="badge info" style="display:inline-block;width:fit-content;">FH Aachen</span>
                <span class="badge info" style="display:inline-block;width:fit-content;">HS München</span>
            </div>
            <div style="margin-top:12px;font-size:.78rem;color:var(--u-brand,#2563eb);font-weight:600;">İçerikleri Keşfet →</div>
        </div>
    </a>

    <a href="{{ route('guest.discover', ['cat' => 'uni-content']) }}" class="card" style="text-decoration:none;color:inherit;display:block;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">🎨</div>
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:6px;">Kunsthochschule / Musikhochschule</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:12px;">
                Sanat, Mimarlık ve Müzik alanlarında uzmanlaşmış kurumlar.
                Giriş genellikle portfolyo veya yetenek sınavı gerektirir.
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <span class="badge warn" style="display:inline-block;width:fit-content;">UdK Berlin</span>
                <span class="badge warn" style="display:inline-block;width:fit-content;">HfG Offenbach</span>
                <span class="badge warn" style="display:inline-block;width:fit-content;">Bauhaus-Uni Weimar</span>
            </div>
            <div style="margin-top:12px;font-size:.78rem;color:var(--u-brand,#2563eb);font-weight:600;">İçerikleri Keşfet →</div>
        </div>
    </a>

</div>

{{-- Başvuru Portalları --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Başvuru Portalları</div>
<div class="col3" style="margin-bottom:24px;">

    <div class="card">
        <div class="card-body" style="padding:18px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:8px;color:var(--u-brand,#2563eb);">uni-assist e.V.</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:10px;">
                Uluslararası öğrenci belgelerini doğrulayan merkezi platform. Çoğu Alman üniversitesi bu portalı kullanır.
                Başvuru ücreti: ~75 EUR.
            </div>
            <span class="badge danger" style="font-size:var(--tx-xs);">Zorunlu (çoğu üniversite)</span>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:18px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:8px;color:var(--u-brand,#2563eb);">hochschulstart.de</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:10px;">
                Tıp, Eczacılık, Diş Hekimliği gibi kısıtlı bölümler için merkezi kontenjan dağıtım sistemi (Numerus Clausus).
            </div>
            <span class="badge warn" style="font-size:var(--tx-xs);">Belirli bölümler için</span>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:18px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:8px;color:var(--u-brand,#2563eb);">Direkt Başvuru</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;margin-bottom:10px;">
                Bazı üniversiteler kendi online portalleri üzerinden başvuruyu kabul eder.
                Her üniversitenin web sitesini kontrol edin.
            </div>
            <span class="badge ok" style="font-size:var(--tx-xs);">Ücretsiz</span>
        </div>
    </div>

</div>

{{-- Şehir Rehberi --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Popüler Üniversite Şehirleri</div>
<div class="col2" style="margin-bottom:24px;">

    @php
    $cities = [
        ['name'=>'Berlin','emoji'=>'🐻','desc'=>'Almanya\'nın başkenti. TU Berlin, FU Berlin, HU Berlin. Canlı öğrenci hayatı, görece uygun kira. Uluslararası öğrenci topluluğu çok güçlü.','tag'=>'Büyük Şehir','badge'=>'info'],
        ['name'=>'München','emoji'=>'🏔','desc'=>'TU München (Dünya sıralaması Top 50). BMW, Siemens merkezi. Yüksek yaşam maliyeti ama güçlü kariyer fırsatları. Kira ~€800-1200.','tag'=>'Prestijli','badge'=>'ok'],
        ['name'=>'Hamburg','emoji'=>'⚓','desc'=>'Avrupa\'nın en büyük limanı. HAW Hamburg, Uni Hamburg. Lojistik, ticaret ve medya güçlü. Kozmopolit şehir atmosferi.','tag'=>'Liman Şehri','badge'=>'info'],
        ['name'=>'Frankfurt','emoji'=>'🏦','desc'=>'Avrupa\'nın finans merkezi. Goethe Uni. Bankacılık, finans ve ekonomi için ideal. Frankfurt Havalimanı uluslararası erişim sağlar.','tag'=>'Finans','badge'=>'warn'],
        ['name'=>'Köln','emoji'=>'⛪','desc'=>'Uygun fiyatlı yaşam (~€600-900 kira). Büyük üniversite, çok sayıda FH. Medya, sanat ve sosyal bilimler güçlü.','tag'=>'Uygun Fiyat','badge'=>'ok'],
        ['name'=>'Stuttgart','emoji'=>'🚗','desc'=>'Mercedes, Porsche, Bosch merkezi. Uni Stuttgart + DHBW. Mühendislik ve otomotiv sektöründe iş bulmak çok kolay.','tag'=>'Mühendislik','badge'=>'warn'],
        ['name'=>'Düsseldorf','emoji'=>'🎭','desc'=>'Moda, tasarım ve reklam merkezi. HSD & HHU Düsseldorf. 400+ Japon şirketi, uluslararası iş ortamı eşsiz.','tag'=>'Tasarım','badge'=>'info'],
        ['name'=>'Dresden','emoji'=>'🏰','desc'=>'TU Dresden Exzellenzuniversität kalitesi. Yaşam maliyeti çok uygun (kira €350-600). Silicon Saxony — yarı iletken kariyer merkezi.','tag'=>'Uygun+Güçlü','badge'=>'ok'],
        ['name'=>'Hannover','emoji'=>'🌿','desc'=>'Leibniz Uni Hannover. Volkswagen & Continental Ar-Ge merkezi. Uygun kira (~€400-650), sakin öğrenci şehri. Hannover Fuarı dünya devi.','tag'=>'Teknoloji','badge'=>'pending'],
        ['name'=>'Nürnberg','emoji'=>'🏛','desc'=>'FAU Erlangen-Nürnberg (Bavyera\'nın köklü üniversitesi). Siemens & Adidas merkezi. Münih\'e alternatif, daha uygun yaşam maliyeti.','tag'=>'Köklü','badge'=>'warn'],
    ];
    @endphp

    @php
    $citySlugMap = [
        'Berlin'     => 'berlin',
        'München'    => 'munich',
        'Hamburg'    => 'hamburg',
        'Frankfurt'  => 'frankfurt',
        'Köln'       => 'cologne',
        'Stuttgart'  => 'stuttgart',
        'Düsseldorf' => 'dusseldorf',
        'Dresden'    => 'dresden',
        'Hannover'   => 'hannover',
        'Nürnberg'   => 'nurnberg',
    ];
    @endphp
    @foreach($cities as $c)
    @php $slug = $citySlugMap[$c['name']] ?? null; @endphp
    <div class="card" style="margin-bottom:0;transition:box-shadow .2s,transform .2s;"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div class="card-body" style="padding:16px 18px;display:flex;align-items:flex-start;gap:14px;">
            <div style="font-size:30px;line-height:1;flex-shrink:0;">{{ $c['emoji'] }}</div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span style="font-weight:700;font-size:var(--tx-sm);">{{ $c['name'] }}</span>
                    <span class="badge {{ $c['badge'] }}" style="font-size:var(--tx-xs);">{{ $c['tag'] }}</span>
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;margin-bottom:8px;">{{ $c['desc'] }}</div>
                @if($slug)
                <a href="{{ route('guest.city-detail', $slug) }}" style="font-size:var(--tx-xs);font-weight:700;color:var(--u-brand,#2563eb);text-decoration:none;">
                    Detaylı Rehber →
                </a>
                @endif
            </div>
        </div>
    </div>
    @endforeach

</div>

{{-- Başvuru Takvimi --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📅 Başvuru Takvimi</div>
<div class="col2" style="margin-bottom:20px;">

    <div class="card" style="overflow:hidden;">
        <div style="background:linear-gradient(135deg,#2563eb,#0891b2);padding:14px 20px;">
            <div style="color:#fff;font-weight:700;font-size:var(--tx-base);">❄️ Kış Dönemi (WS)</div>
            <div style="color:rgba(255,255,255,.8);font-size:var(--tx-xs);margin-top:2px;">Ekim başlangıç — başvuru takvimi</div>
        </div>
        <div class="card-body" style="padding:0;">
            @foreach([
                ['1','Ocak – Mart','Belgeleri hazırla','Apostil, Almanca çeviri, transkript'],
                ['2','Mart – Mayıs','uni-assist başvurusu','Belgeleri yükle, başvuru ücretini öde'],
                ['3','Mayıs – Haziran','Kabul mektuplarını bekle','Sonuç e-posta ile gelir'],
                ['4','Temmuz – Ağustos','Vize başvurusu','Sperrkonto + belgeler hazır olmalı'],
                ['5','Eylül','Konut & Sperrkonto','Yurt veya WG ara, hesabı aç'],
                ['6','Ekim','Kayıt & Oryantasyon','Üniversite kaydı, Anmeldung'],
            ] as [$n,$mon,$title,$sub])
            <div style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid var(--u-line,#f1f5f9);">
                <div style="width:24px;height:24px;border-radius:50%;background:#2563eb;color:#fff;font-size:var(--tx-xs);font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $n }}</div>
                <div style="min-width:130px;flex-shrink:0;">
                    <span style="display:inline-block;background:#2563eb;color:#fff;font-size:var(--tx-xs);font-weight:700;padding:2px 8px;border-radius:4px;white-space:nowrap;">{{ $mon }}</span>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">{{ $title }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $sub }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card" style="overflow:hidden;">
        <div style="background:linear-gradient(135deg,#059669,#16a34a);padding:14px 20px;">
            <div style="color:#fff;font-weight:700;font-size:var(--tx-base);">🌸 Yaz Dönemi (SS)</div>
            <div style="color:rgba(255,255,255,.8);font-size:var(--tx-xs);margin-top:2px;">Nisan başlangıç — başvuru takvimi</div>
        </div>
        <div class="card-body" style="padding:0;">
            @foreach([
                ['1','Temmuz – Eylül','Belgeleri hazırla','Apostil, Almanca çeviri, transkript'],
                ['2','Eylül – Ekim','uni-assist başvurusu','Belgeleri yükle, başvuru ücretini öde'],
                ['3','Ekim – Kasım','Kabul mektuplarını bekle','Sonuç e-posta ile gelir'],
                ['4','Aralık – Ocak','Vize başvurusu','Sperrkonto + belgeler hazır olmalı'],
                ['5','Şubat – Mart','Konut & Sperrkonto','Yurt veya WG ara, hesabı aç'],
                ['6','Nisan','Kayıt & Oryantasyon','Üniversite kaydı, Anmeldung'],
            ] as [$n,$mon,$title,$sub])
            <div style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid var(--u-line,#f1f5f9);">
                <div style="width:24px;height:24px;border-radius:50%;background:#16a34a;color:#fff;font-size:var(--tx-xs);font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $n }}</div>
                <div style="min-width:130px;flex-shrink:0;">
                    <span style="display:inline-block;background:#16a34a;color:#fff;font-size:var(--tx-xs);font-weight:700;padding:2px 8px;border-radius:4px;white-space:nowrap;">{{ $mon }}</span>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">{{ $title }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $sub }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Başvuru İpuçları --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:16px;">🏆 Başarılı Başvuru İçin 10 Altın Kural</div>
        <div class="col2">
            @foreach([
                ['1','Dil sertifikanızı erken alın','TestDaF veya DSH sınavlarına 6-12 ay öncesinden hazırlanın. IELTS de birçok İngilizce program için geçerlidir.'],
                ['2','Notlarınızı apostil ile onaylatın','Türkiye\'deki notlarınız için Dışişleri Bakanlığı apostili ve Almanca yeminli tercüme şarttır.'],
                ['3','uni-assist hesabı açın','Başvuru sürecinin çoğu bu platform üzerinden yürüyor. Belgelerinizi dijital olarak yükleyin.'],
                ['4','Motivasyon mektubuna özen gösterin','"Neden bu program, neden bu üniversite?" sorusunu özgün ve ikna edici yanıtlayın.'],
                ['5','Sperrkonto\'yu zamanında açın','Vize için ~11.208 EUR bloke hesap zorunlu. DKB, Fintiba veya Expatrio ile açabilirsiniz.'],
                ['6','En az 3 üniversiteye başvurun','Tek üniversite stratejisi riskli. Prestij, konum ve kabul oranını dengeleyin.'],
                ['7','APS Sertifikası alın','Çoğu Alman üniversitesi Türk başvuruculardan APS sertifikası istiyor. Ankara Büyükelçiliği\'nden alınır.'],
                ['8','Konut aramaya erken başlayın','Kabul mektubunu alır almaz yurt (Studentenwerk) veya WG (paylaşık ev) aramaya başlayın.'],
                ['9','Öğrenci vizenizi zamanında alın','Vize randevusu için 2-4 ay bekleme süresi olabilir. Konsolosluk ile erken iletişime geçin.'],
                ['10','Danışmanınızla düzenli iletişim kurun','Her aşamada danışmanınızı bilgilendirin — doğru yönlendirilmek için zaman kaybetmeyin.'],
            ] as [$num,$title,$desc])
            <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div style="width:28px;height:28px;border-radius:50%;background:var(--u-brand,#2563eb);color:#fff;font-size:var(--tx-sm);font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $num }}</div>
                <div>
                    <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);margin-bottom:2px;">{{ $title }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $desc }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div style="text-align:center;padding:8px 0;">
    <a href="{{ route('guest.dashboard') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard'a Dön</a>
    &nbsp;·&nbsp;
    <a href="{{ route('guest.cost-calculator') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Maliyet Hesapla →</a>
</div>

@endsection

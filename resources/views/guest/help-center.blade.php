@extends('guest.layouts.app')

@section('title', 'SSS & Yardım Merkezi')
@section('page_title', 'SSS & Yardım Merkezi')

@push('head')
<style>
.hc-cats { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.hc-search { display:flex; gap:8px; margin-bottom:16px; }
.hc-search input { flex:1; padding:9px 12px; border:1px solid var(--u-line,#e5e7eb); border-radius:8px; font-size:13px; }
.hc-group-title { font-size:13px; font-weight:700; color:#374151; margin:16px 0 8px; text-transform:uppercase; letter-spacing:.4px; }
.hc-faq { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:12px; margin-bottom:8px; overflow:hidden; }
.hc-faq-q { padding:14px 16px; font-size:13px; font-weight:600; color:#111827; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
.hc-faq-q:hover { background:#f9fafb; }
.hc-faq-a { padding:0 16px; font-size:13px; color:#4b5563; line-height:1.6; display:none; border-top:1px solid var(--u-line,#e5e7eb); }
.hc-faq-a.open { display:block; padding:12px 16px; }
.hc-caret { font-size:12px; color:#9ca3af; transition:transform .2s; }
.hc-caret.open { transform:rotate(180deg); }
.hc-not-found { background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:24px; text-align:center; margin-top:20px; }
.hc-empty { text-align:center; padding:40px 0; color:#9ca3af; font-size:14px; }
</style>
@endpush

@section('content')

{{-- Arama --}}
<form method="GET" action="{{ route('guest.help-center') }}" class="hc-search">
    <input type="text" name="q" value="{{ $search }}" placeholder="Sorunuzu yazın: belge, vize, sözleşme...">
    @if($activeCategory !== 'all')
        <input type="hidden" name="category" value="{{ $activeCategory }}">
    @endif
    <button type="submit" class="btn ok">Ara</button>
    @if($search || $activeCategory !== 'all')
        <a href="{{ route('guest.help-center') }}" class="btn">Temizle</a>
    @endif
</form>

{{-- Kategori Filtreleri --}}
<div class="hc-cats">
    <a href="{{ route('guest.help-center') }}" class="badge {{ $activeCategory === 'all' ? 'ok' : 'info' }}" style="text-decoration:none;cursor:pointer;">🔍 Tümü</a>
    @foreach($categories as $key => $cat)
        <a href="{{ route('guest.help-center') }}?category={{ $key }}{{ $search ? '&q='.urlencode($search) : '' }}"
           class="badge {{ $activeCategory === $key ? 'ok' : 'info' }}" style="text-decoration:none;cursor:pointer;">
            {{ $cat['icon'] }} {{ $cat['label'] }}
        </a>
    @endforeach
</div>

{{-- SSS Listesi --}}
@if($faqs->isEmpty())
    @if($search)
    <div class="hc-empty">
        <div style="font-size:32px;margin-bottom:8px;">🔍</div>
        <div>"<strong>{{ $search }}</strong>" için sonuç bulunamadı.</div>
    </div>
    @else
    {{-- ── Statik SSS İçeriği (veritabanı boşken gösterilir) ── --}}
    @php
    $staticFaqs = [
        'application' => [
            ['S: Başvuru süreci nasıl işliyor?', 'A: Kayıt olduktan sonra profilinizi tamamlayın, belgelerinizi yükleyin ve danışmanınızla iletişime geçin. Danışmanınız üniversite önerisi, başvuru portali ve takvim konusunda sizi yönlendirecek.'],
            ['S: uni-assist nedir ve zorunlu mu?', 'A: uni-assist, Alman üniversiteleri adına uluslararası öğrenci başvurularını değerlendiren merkezi bir kuruluştur. Başvurmak istediğiniz üniversitenin bunu gerektirip gerektirmediğini kontrol edin — çoğu devlet üniversitesi istemektedir. Başvuru ücreti yaklaşık 75 EUR\'dur.'],
            ['S: APS sertifikası nedir?', 'A: Alman makamları tarafından Türk başvuruculardan istenen akademik değerlendirme sertifikasıdır. Ankara\'daki Alman Büyükelçiliği\'nden alınır. Başvuru için transkript, diploma ve dil belgelerinizi hazırlayın. Onay süreci 4-8 hafta sürebilir.'],
            ['S: Kaç üniversiteye başvurmalıyım?', 'A: En az 3-5 üniversiteye başvurmanız önerilir. Prestijli, orta ve daha erişilebilir üniversiteleri dengeli seçin. Tek üniversiteye bağımlı olmak riskinizi artırır.'],
        ],
        'documents' => [
            ['S: Hangi belgeler gerekli?', 'A: Temel belgeler: Pasaport + onaylı tercümesi, Lise/Üniversite diploması + apostil + Almanca tercümesi, Akademik transkript + Almanca tercümesi, Dil sertifikası (TestDaF/DSH/IELTS), Motivasyon mektubu, APS sertifikası (Türk başvurucular için), CV (Europass formatı önerilir).'],
            ['S: Apostil nedir?', 'A: Apostil, belgenin yurt dışında geçerli olması için Dışişleri Bakanlığı tarafından verilen resmi onay damgasıdır. Diploma ve transkriptleriniz için gereklidir. Türkiye\'de il nüfus müdürlükleri veya Dışişleri Bakanlığı il müdürlüklerinden alınabilir.'],
            ['S: Belgelerimi nerede tercüme ettirmeliyim?', 'A: Almanya\'da geçerli olması için belgeleriniz yeminli Almanca tercüman tarafından tercüme edilmeli ve onaylanmalıdır. Türkiye\'deki yeminli tercüme büroları veya noter kanalıyla alabilirsiniz.'],
            ['S: Belge yüklerken hangi format kullanmalıyım?', 'A: PDF formatı tercih edilir. Dosya boyutu 5 MB\'ı geçmemeli. Tarama kalitesi en az 300 DPI olmalı, tüm sayfa net ve okunabilir görünmeli. Belge tüm sayfaları tek dosyada birleştirilmiş olmalıdır.'],
        ],
        'visa' => [
            ['S: Öğrenci vizesi nasıl alınır?', 'A: Üniversiteden kabul mektubunu aldıktan sonra Türkiye\'deki Alman konsolosluğuna vize başvurusu yapılır. Gerekli belgeler: kabul mektubu, Sperrkonto kanıtı (~11.208 EUR), seyahat sağlık sigortası, uçak bileti rezervasyonu, biyometrik fotoğraf ve pasaport. Randevu için 2-4 ay önceden başvurun.'],
            ['S: Sperrkonto nedir ve nasıl açılır?', 'A: Sperrkonto (bloke hesap), Almanya\'da yaşam masraflarını karşılayabileceğinizi kanıtlamak için gereken ve her ay belirli miktarda çekim yapabileceğiniz banka hesabıdır. Minimum miktar: 11.208 EUR/yıl. DKB, Fintiba, Expatrio veya Deutsche Bank üzerinden açılabilir. Online süreç 1-2 hafta sürer.'],
            ['S: Vize reddi durumunda ne yapabilirim?', 'A: Red gerekçesini öğrenin ve eksik belgelerinizi tamamlayın. Yeniden başvurabilirsiniz. Danışmanınız bu süreçte size destek sağlayacaktır.'],
        ],
        'contract' => [
            ['S: Sözleşmemi nerede görebilirim?', 'A: Sol menüden "Sözleşme" bölümüne tıklayarak sözleşmenizi görüntüleyebilir ve indirebilirsiniz.'],
            ['S: Ödeme planım nedir?', 'A: Ödeme bilgileriniz için danışmanınızla iletişime geçin veya sözleşme bölümünüzü inceleyin. Hizmet ücretiniz seçtiğiniz pakete göre belirlenir.'],
        ],
        'living' => [
            ['S: Almanya\'da yaşam maliyeti nedir?', 'A: Şehre göre değişir: Berlin/Hamburg/Köln 800-1200 EUR/ay, München 1200-1800 EUR/ay. Temel giderler: kira %50-60, yemek %20, ulaşım %10, diğer %10-20. Öğrenci mensa\'ları ve Semesterticket (toplu taşıma) tasarruf sağlar.'],
            ['S: Part-time çalışabilir miyim?', 'A: Evet. AB dışı öğrenciler yılda 120 tam gün veya 240 yarım gün çalışabilir. Çalışma izni genellikle öğrenci vizesiyle birlikte gelir.'],
            ['S: Sağlık sigortası zorunlu mu?', 'A: Evet, üniversiteye kayıt için zorunludur. 30 yaş altındaki öğrenciler için TK, AOK, Barmer gibi devlet sigorta şirketleri tercih edilir (~€110-120/ay). Özel sigorta seçimi Almanca bilgisi gerektirebilir.'],
        ],
        'university' => [
            ['S: Hangi üniversite bana uygun?', 'A: Bu, hedef bölümünüze, dil seviyenize ve kariyer hedeflerinize göre değişir. Danışmanınız akademik profilinizi değerlendirerek size özel üniversite listesi hazırlayacak.'],
            ['S: Almanca bilmesem de başvurabilir miyim?', 'A: Evet! Pek çok üniversitede İngilizce master programları mevcuttur. Lisans için Almanca genellikle zorunludur (B2-C1 seviyesi). Almanca öğrenerek bachelor programlarına da başvurabilirsiniz.'],
            ['S: NC (Numerus Clausus) nedir?', 'A: NC, kısıtlı kapasiteli bölümlere girişte kullanılan not ortalaması sınırıdır. Tıp, Eczacılık, Diş Hekimliği gibi bölümler genellikle yüksek NC gerektirir. FH programları genellikle daha erişilebilirdir.'],
        ],
    ];
    @endphp

    @foreach($staticFaqs as $catKey => $items)
    @php $catInfo = $categories[$catKey] ?? null; @endphp
    <div class="hc-group-title">{{ $catInfo ? ($catInfo['icon'] . ' ' . $catInfo['label']) : ucfirst($catKey) }}</div>
    @foreach($items as [$q, $a])
    <div class="hc-faq">
        <div class="hc-faq-q" onclick="toggleFaq(this)">
            <span>{{ ltrim($q, 'S: ') }}</span>
            <span class="hc-caret">▼</span>
        </div>
        <div class="hc-faq-a">{{ ltrim($a, 'A: ') }}</div>
    </div>
    @endforeach
    @endforeach
    @endif
@else
    @if($grouped->count() > 1 && $activeCategory === 'all' && !$search)
        @foreach($grouped as $catKey => $items)
        @php $catInfo = $categories[$catKey] ?? null; @endphp
        <div class="hc-group-title">{{ $catInfo ? ($catInfo['icon'] . ' ' . $catInfo['label']) : ucfirst($catKey) }}</div>
        @foreach($items as $faq)
        <div class="hc-faq">
            <div class="hc-faq-q" onclick="toggleFaq(this)">
                <span>{{ $faq->title_tr }}</span>
                <span class="hc-caret">▼</span>
            </div>
            <div class="hc-faq-a">{{ $faq->content_tr }}</div>
        </div>
        @endforeach
        @endforeach
    @else
        @foreach($faqs as $faq)
        <div class="hc-faq">
            <div class="hc-faq-q" onclick="toggleFaq(this)">
                <span>{{ $faq->title_tr }}</span>
                <span class="hc-caret">▼</span>
            </div>
            <div class="hc-faq-a">{{ $faq->content_tr }}</div>
        </div>
        @endforeach
    @endif
@endif

{{-- "Cevabı Bulamadım" CTA --}}
<div class="hc-not-found" style="margin-top:24px;">
    <div style="font-size:var(--tx-2xl);margin-bottom:8px;">📨</div>
    <div style="font-size:var(--tx-base);font-weight:700;color:#111827;margin-bottom:6px;">Aradığınız cevabı bulamadınız mı?</div>
    <div style="font-size:var(--tx-sm);color:#6b7280;margin-bottom:14px;">Destek ekibimize bilet oluşturun — en kısa sürede yanıt vereceğiz.</div>
    <a href="{{ route('guest.tickets') }}?{{ $search ? 'subject='.urlencode($search).'&' : '' }}department=auto"
       class="btn ok">
        📨 Destek Talebi Oluştur
    </a>
</div>

@push('scripts')
<script>
function toggleFaq(el) {
    const answer = el.nextElementSibling;
    const caret  = el.querySelector('.hc-caret');
    answer.classList.toggle('open');
    caret.classList.toggle('open');
}
</script>
@endpush
@endsection

@extends('guest.layouts.app')
@section('title', 'Belge Hazırlama Rehberi')
@section('page_title', 'Belge Hazırlama Rehberi')

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

{{-- Hero --}}
<div class="card" style="background:linear-gradient(to right,var(--theme-hero-from-guest),var(--theme-hero-to-guest));color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">Almanya Üniversite Başvurusu</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">📋 Belge Hazırlama Rehberi</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:580px;line-height:1.6;">
            Almanya\'da üniversite başvurusunda belgeler her şeydir. Eksiksiz ve doğru hazırlanmış belgeler başvurunu hızlandırır, kabul şansını doğrudan etkiler.
        </div>
    </div>
</div>

{{-- Neden Önemli --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Neden Bu Kadar Önemli?</div>
<div class="col3" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">⏱️</div>
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:6px;">Süreç Hızlanır</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">
                Eksiksiz başvurular 2–4 haftada değerlendirilir. Eksik belgeli başvurular aylarca askıda kalabilir ya da doğrudan reddedilir.
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">✅</div>
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:6px;">Kabul Şansı Artar</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">
                Motivasyon mektubu ve CV kalitesi, akademik notlardan bağımsız olarak komisyon kararını doğrudan etkiler.
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:10px;">🛂</div>
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:6px;">Vize Kolaylaşır</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">
                Konsolosluk başvuru paketini bütünlük açısından değerlendirir. Eksik veya tutarsız belgeler vize reddine yol açar.
            </div>
        </div>
    </div>
</div>

{{-- En Sık Yapılan Hatalar --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">En Sık Yapılan Hatalar</div>
<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="padding:20px 24px;">
        <div class="col2" style="margin-bottom:0;">
            @foreach([
                ['Apostil eksikliği','Türkiye\'den alınan diplomalar için Dışişleri Bakanlığı apostili zorunludur. Notere gitmek yetmez.','danger'],
                ['Eski tarihli belgeler','Bazı üniversiteler son 6 ay içinde düzenlenmiş belge ister. Tarihlere dikkat et.','warn'],
                ['Makine çevirisi kullanımı','Yeminli tercüman olmadan yapılan çeviriler kabul edilmez. Mutlaka resmi tercüman kullan.','danger'],
                ['Genel motivasyon mektubu','Her üniversiteye aynı metni gönderme. Komisyon bunu hemen fark eder — özgün yaz.','warn'],
                ['CV format uyumsuzluğu','Almanya\'da Europass veya sade format tercih edilir. Renkli tasarım CV\'ler olumsuz karşılanır.','info'],
                ['Eksik imza veya mühür','Resmi belgelerde okul müdürü imzası veya kurumsal mühür olmadan belge geçersiz sayılabilir.','danger'],
            ] as [$title, $desc, $badge])
            <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <span class="badge {{ $badge }}" style="font-size:var(--tx-xs);flex-shrink:0;margin-top:2px;">!</span>
                <div>
                    <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:2px;">{{ $title }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $desc }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Belge Türleri --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Belge Türleri ve Dikkat Edilecekler</div>
<div class="col2" style="margin-bottom:24px;">

    <div class="card">
        <div class="card-body" style="padding:20px 24px;">
            <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-brand,#2563eb);margin-bottom:14px;">📝 Motivasyon Mektubu</div>
            @foreach([
                'Neden bu program, neden bu üniversite — somut yanıtla',
                'Akademik geçmişin ile programın bağlantısını kur',
                'Gelecek hedeflerini kısa ve net anlat (1–1,5 sayfa)',
                'Almanca veya İngilizce — programa göre dil seç',
                'Ana dili konuşan birine okutmadan gönderme',
            ] as $item)
            <div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid var(--u-line,#f3f4f6);font-size:var(--tx-sm);">
                <span style="color:#7c3aed;font-weight:700;flex-shrink:0;">→</span>
                <span>{{ $item }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:20px 24px;">
            <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-brand,#2563eb);margin-bottom:14px;">👤 Özgeçmiş (Lebenslauf)</div>
            @foreach([
                'Europass veya sade tablo formatı kullan',
                'Fotoğraf ekle — Almanya\'da hâlâ beklenir',
                'Kronolojik sıra: en yeni deneyim en üstte',
                'Dil seviyeleri CEFR skalasıyla belirt (B2, C1...)',
                'Gönüllülük ve hobiler ekle — komisyon dikkat eder',
            ] as $item)
            <div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid var(--u-line,#f3f4f6);font-size:var(--tx-sm);">
                <span style="color:#7c3aed;font-weight:700;flex-shrink:0;">→</span>
                <span>{{ $item }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:20px 24px;">
            <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-brand,#2563eb);margin-bottom:14px;">🎓 Diploma & Transkript</div>
            @foreach([
                'Lise diplomanı apostil ile onaylat (Dışişleri Bakanlığı)',
                'Yeminli tercüman ile Almancaya çevirt',
                'Not döküm belgesi (transkript) de aynı işlemden geçmeli',
                'Türkiye\'den başvuruyorsan APS sertifikası da gerekli',
                'Aslını değil onaylı fotokopisini gönder — aslını sakla',
            ] as $item)
            <div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid var(--u-line,#f3f4f6);font-size:var(--tx-sm);">
                <span style="color:#7c3aed;font-weight:700;flex-shrink:0;">→</span>
                <span>{{ $item }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:20px 24px;">
            <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-brand,#2563eb);margin-bottom:14px;">🗣 Dil Belgesi</div>
            @foreach([
                'Almanca program: DSH, TestDaF veya Goethe C1/B2',
                'İngilizce program: IELTS 6.0+ veya TOEFL iBT 80+',
                'Sınav tarihini iyi planla — sonuç 4–6 hafta alır',
                'Bazı üniversiteler kendi dil sınavını kabul eder',
                'Geçerlilik süresi: çoğu sınav için 2 yıl',
            ] as $item)
            <div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid var(--u-line,#f3f4f6);font-size:var(--tx-sm);">
                <span style="color:#7c3aed;font-weight:700;flex-shrink:0;">→</span>
                <span>{{ $item }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Hazırlık Takvimi --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="padding:20px 24px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:16px;">🗓 Belge Hazırlık Takvimi</div>
        <div class="col2" style="margin-bottom:0;">
            @foreach([
                ['6+ ay önce','Dil sınavına kaydol ve hazırlanmaya başla. TestDaF / IELTS için erken kayıt şart.','warn'],
                ['4–5 ay önce','Apostil işlemlerini başlat. Yeminli tercüman bul. APS başvurusunu yap.','warn'],
                ['3 ay önce','Motivasyon mektubunu yaz, danışmanına göster, revize et.','info'],
                ['2 ay önce','CV\'yi tamamla. Varsa referans mektuplarını topla.','info'],
                ['1 ay önce','Tüm belgeleri kontrol listesiyle denetle. Eksik varsa hemen tamamla.','ok'],
                ['Başvuru öncesi','Belge setinin dijital kopyasını sakla. Orijinalleri koru.','ok'],
            ] as [$zaman, $desc, $badge])
            <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:flex-start;">
                <span class="badge {{ $badge }}" style="font-size:var(--tx-xs);flex-shrink:0;white-space:nowrap;margin-top:1px;">{{ $zaman }}</span>
                <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.5;">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- CTA --}}
<div class="card" style="background:linear-gradient(to right,var(--theme-hero-from-guest),var(--theme-hero-to-guest));color:#fff;margin-bottom:8px;">
    <div class="card-body" style="padding:20px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
        <div>
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:4px;">Belgelerini Yüklemeye Hazır mısın?</div>
            <div style="font-size:var(--tx-sm);opacity:.85;">Danışmanın belgelerini inceleyecek ve eksik olanlar için seni yönlendirecek.</div>
        </div>
        <a href="{{ route('guest.registration.documents') }}" class="btn" style="background:#fff;color:#7c3aed;font-weight:700;flex-shrink:0;">
            Belgelerime Git →
        </a>
    </div>
</div>

<div style="text-align:center;padding:12px 0;">
    <a href="{{ route('guest.dashboard') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard</a>
    &nbsp;·&nbsp;
    <a href="{{ route('guest.university-guide') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Üniversite Rehberi →</a>
</div>

@endsection

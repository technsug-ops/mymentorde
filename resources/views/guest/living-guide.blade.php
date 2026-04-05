@extends('guest.layouts.app')
@section('title', 'Almanya\'da Yaşam Rehberi')
@section('page_title', 'Almanya\'da Yaşam Rehberi')

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
@php
    $cityList = $cities ?? [];
    $rate     = $eurTryRate ?? null;
@endphp

{{-- Başlık --}}
<div class="card" style="background:linear-gradient(to right,var(--theme-hero-from-guest),var(--theme-hero-to-guest));color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">Almanya'da Öğrenci Hayatı</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">🏙 Yaşam Rehberi</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:560px;line-height:1.6;">
            Konut, ulaşım, sigorta, banka hesabı — Almanya'ya gelmeden önce bilmen gereken her şey.
        </div>
        @if($rate)
        <div style="margin-top:12px;display:inline-block;background:rgba(255,255,255,.15);border-radius:8px;padding:6px 14px;font-size:var(--tx-xs);font-weight:700;">
            1 EUR = {{ number_format($rate, 2) }} TRY · bugün
        </div>
        @endif
        <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:8px;">
            <a href="{{ route('guest.discover') }}" style="display:inline-flex;align-items:center;gap:5px;padding:7px 16px;border-radius:20px;background:rgba(255,255,255,.92);color:#b91c1c;font-size:var(--tx-xs);font-weight:700;text-decoration:none;border:none;box-shadow:0 2px 6px rgba(0,0,0,.18);transition:all .15s;" onmouseover="this.style.background='#fff';this.style.boxShadow='0 3px 10px rgba(0,0,0,.25)'" onmouseout="this.style.background='rgba(255,255,255,.92)';this.style.boxShadow='0 2px 6px rgba(0,0,0,.18)'">🧭 Tüm İçerikler</a>
            <a href="{{ route('guest.discover', ['cat'=>'city-content']) }}" style="display:inline-flex;align-items:center;gap:5px;padding:7px 16px;border-radius:20px;background:rgba(255,255,255,.92);color:#b91c1c;font-size:var(--tx-xs);font-weight:700;text-decoration:none;border:none;box-shadow:0 2px 6px rgba(0,0,0,.18);transition:all .15s;" onmouseover="this.style.background='#fff';this.style.boxShadow='0 3px 10px rgba(0,0,0,.25)'" onmouseout="this.style.background='rgba(255,255,255,.92)';this.style.boxShadow='0 2px 6px rgba(0,0,0,.18)'">🏙 Şehir Rehberleri</a>
            <a href="{{ route('guest.discover', ['cat'=>'tips-tricks']) }}" style="display:inline-flex;align-items:center;gap:5px;padding:7px 16px;border-radius:20px;background:rgba(255,255,255,.92);color:#b91c1c;font-size:var(--tx-xs);font-weight:700;text-decoration:none;border:none;box-shadow:0 2px 6px rgba(0,0,0,.18);transition:all .15s;" onmouseover="this.style.background='#fff';this.style.boxShadow='0 3px 10px rgba(0,0,0,.25)'" onmouseout="this.style.background='rgba(255,255,255,.92)';this.style.boxShadow='0 2px 6px rgba(0,0,0,.18)'">💡 Pratik İpuçları</a>
            <a href="{{ route('guest.discover', ['cat'=>'careers']) }}" style="display:inline-flex;align-items:center;gap:5px;padding:7px 16px;border-radius:20px;background:rgba(255,255,255,.92);color:#b91c1c;font-size:var(--tx-xs);font-weight:700;text-decoration:none;border:none;box-shadow:0 2px 6px rgba(0,0,0,.18);transition:all .15s;" onmouseover="this.style.background='#fff';this.style.boxShadow='0 3px 10px rgba(0,0,0,.25)'" onmouseout="this.style.background='rgba(255,255,255,.92)';this.style.boxShadow='0 2px 6px rgba(0,0,0,.18)'">💼 Kariyer</a>
            <a href="{{ route('guest.discover', ['cat'=>'student-life']) }}" style="display:inline-flex;align-items:center;gap:5px;padding:7px 16px;border-radius:20px;background:rgba(255,255,255,.92);color:#b91c1c;font-size:var(--tx-xs);font-weight:700;text-decoration:none;border:none;box-shadow:0 2px 6px rgba(0,0,0,.18);transition:all .15s;" onmouseover="this.style.background='#fff';this.style.boxShadow='0 3px 10px rgba(0,0,0,.25)'" onmouseout="this.style.background='rgba(255,255,255,.92)';this.style.boxShadow='0 2px 6px rgba(0,0,0,.18)'">🎓 Öğrenci Hayatı</a>
        </div>
    </div>
</div>

{{-- Şehir Maliyet Tablosu --}}
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Şehir Bazında Aylık Maliyet</div>
<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <thead>
                <tr style="background:var(--u-bg,#f8fafc);">
                    <th style="padding:12px 16px;text-align:left;font-weight:700;border-bottom:1px solid var(--u-line,#e2e8f0);">Şehir</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:700;border-bottom:1px solid var(--u-line,#e2e8f0);">Kira</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:700;border-bottom:1px solid var(--u-line,#e2e8f0);">Gıda</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:700;border-bottom:1px solid var(--u-line,#e2e8f0);">Ulaşım</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:700;border-bottom:1px solid var(--u-line,#e2e8f0);">Diğer</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:700;border-bottom:1px solid var(--u-line,#e2e8f0);color:var(--u-brand,#2563eb);">Toplam/ay</th>
                    @if($rate)
                    <th style="padding:12px 16px;text-align:right;font-weight:700;border-bottom:1px solid var(--u-line,#e2e8f0);">TRY</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($cityList as $key => $c)
                @php
                    $monthly = ($c['rent_avg']??0) + ($c['food_avg']??0) + ($c['transport_avg']??0) + ($c['misc_avg']??0) + 110;
                    $isExpensive = $monthly > 1100;
                @endphp
                <tr style="border-bottom:1px solid var(--u-line,#e2e8f0);">
                    <td style="padding:12px 16px;font-weight:600;">
                        {{ $c['label'] ?? $key }}
                        @if($isExpensive)<span class="badge warn" style="font-size:var(--tx-xs);margin-left:6px;">Pahalı</span>@endif
                    </td>
                    <td style="padding:12px 16px;text-align:right;">€ {{ number_format($c['rent_avg']??0, 0, ',', '.') }}</td>
                    <td style="padding:12px 16px;text-align:right;">€ {{ number_format($c['food_avg']??0, 0, ',', '.') }}</td>
                    <td style="padding:12px 16px;text-align:right;">€ {{ number_format($c['transport_avg']??0, 0, ',', '.') }}</td>
                    <td style="padding:12px 16px;text-align:right;">€ {{ number_format(($c['misc_avg']??0) + 110, 0, ',', '.') }}</td>
                    <td style="padding:12px 16px;text-align:right;font-weight:800;color:var(--u-brand,#2563eb);">€ {{ number_format($monthly, 0, ',', '.') }}</td>
                    @if($rate)
                    <td style="padding:12px 16px;text-align:right;color:var(--u-muted,#64748b);font-size:var(--tx-xs);">₺ {{ number_format($monthly * $rate, 0, ',', '.') }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:10px 16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
            * Kira: WG/yurt ortalaması | Sağlık sigortası (€110/ay) dahil | Kaynak: Studentenwerk 2025-2026
        </div>
    </div>
</div>

{{-- Konut --}}
<div class="col2" style="margin-bottom:24px;">

    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">🏠 Konut Seçenekleri</div>
            @foreach([
                ['Studentenwohnheim (Yurt)','En ucuz seçenek. Studentenwerk listelerine yazılın — bekleme listesi uzun olabilir.','€150–400/ay','ok'],
                ['WG (Paylaşımlı Ev)','En popüler seçenek. WG-Gesucht.de ve Immobilienscout24 kullanın.','€300–600/ay','info'],
                ['Tek Kişilik Daire','En pahalı seçenek. Refah düzeyi yüksek öğrenciler için uygun.','€600–1200/ay','warn'],
            ] as [$title,$desc,$price,$badge])
            <div style="padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                    <span style="font-weight:600;font-size:var(--tx-sm);">{{ $title }}</span>
                    <span class="badge {{ $badge }}" style="font-size:var(--tx-xs);">{{ $price }}</span>
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $desc }}</div>
            </div>
            @endforeach
            <div style="margin-top:12px;padding:10px 12px;background:rgba(37,99,235,.06);border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                💡 <strong>İpucu:</strong> Kabul mektubu gelir gelmez konut aramaya başlayın. Popüler şehirlerde konut bulmak 1-3 ay sürebilir.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📋 Almanya'ya Gelince Yapılacaklar</div>
            @foreach([
                ['1','Anmeldung (İkamet Tescili)','Gelişten itibaren 2 hafta içinde zorunlu. Einwohnermeldeamt\'a gidin.'],
                ['2','Banka Hesabı','DKB, N26 veya Deutsche Bank — öğrenci hesabı ücretsiz.'],
                ['3','Sağlık Sigortası','TK, AOK, Barmer. €110-130/ay. Üniversite kaydı için zorunlu.'],
                ['4','Üniversite Kaydı','Kabul + sigorta belgesi + Anmeldung ile kayıt tamamlanır.'],
                ['5','Öğrenci Semesterticket','Üniversite katkı payıyla birlikte toplu taşıma hakkı.'],
                ['6','Sperrkonto Serbest Bırakma','Vizeyle geldikten sonra aylık ~€934 çekme hakkı başlar.'],
            ] as [$num,$title,$desc])
            <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div style="width:24px;height:24px;border-radius:50%;background:var(--u-brand,#2563eb);color:#fff;font-size:var(--tx-xs);font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $num }}</div>
                <div>
                    <div style="font-weight:600;font-size:var(--tx-sm);">{{ $title }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $desc }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Günlük Yaşam İpuçları --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">💡 Tasarruf İpuçları</div>
        <div class="col3">
            @foreach([
                ['🛒','Gıda','ALDI, LIDL, REWE — öğrencilerin favorisi. Market alışverişiyle yemek pişirirseniz aylık €150-200\'e düşürebilirsiniz.'],
                ['🚲','Ulaşım','Semesterticket ile toplu taşıma ücretsiz veya indirimli. Bisiklet de yaygın ve ucuz ulaşım aracı.'],
                ['💊','Sağlık','Devlet sigortasıyla (TK/AOK) tüm temel sağlık hizmetleri kapsanır. Ek sigorta gerekmez.'],
                ['📱','Telefon','Aldi Talk, Congstar gibi ön ödemeli hatlar €10-15/ay. Öğrenci kontratları da mevcut.'],
                ['☕','Sosyal Hayat','Üniversite kantinleri (Mensa) çok ucuz — öğle yemeği €2-4. Kütüphaneler ücretsiz.'],
                ['💰','Çalışma','Öğrenci vizesiyle yılda 120 tam gün (240 yarım gün) çalışabilirsiniz. Minijob yaygın.'],
            ] as [$icon,$title,$desc])
            <div style="padding:12px;background:var(--u-bg,#f8fafc);border-radius:10px;border:1px solid var(--u-line,#e2e8f0);">
                <div style="font-size:var(--tx-xl);margin-bottom:6px;">{{ $icon }}</div>
                <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:4px;">{{ $title }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div style="text-align:center;padding:8px 0;">
    <a href="{{ route('guest.cost-calculator') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Kişisel Maliyet Hesapla →</a>
    &nbsp;·&nbsp;
    <a href="{{ route('guest.dashboard') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard</a>
</div>

@endsection

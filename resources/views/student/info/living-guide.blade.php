@extends('student.layouts.app')
@section('title', 'Almanya\'da Yaşam Rehberi')
@section('page_title', 'Almanya\'da Yaşam Rehberi')
@push('head')
<style>
.col3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:20px; }
.col2 { display:grid; grid-template-columns:1fr 1fr;     gap:16px; margin-bottom:20px; }
@media(max-width:800px){ .col3{ grid-template-columns:1fr 1fr; } }
@media(max-width:600px){ .col3,.col2{ grid-template-columns:1fr; } }
</style>
@endpush
@section('content')

<div class="card" style="background:linear-gradient(to right,var(--theme-hero-from-student,#4c1d95),var(--theme-hero-to-student,#7c3aed));color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">Almanya'da Öğrenci Hayatı</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">🏙 Yaşam Rehberi</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:560px;line-height:1.6;">
            Konut, ulaşım, sigorta, banka hesabı — Almanya'ya gelmeden önce bilmen gereken her şey.
        </div>
        <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            @if(isset($eurTryRate) && $eurTryRate)
            <span style="background:rgba(255,255,255,.15);border-radius:8px;padding:6px 14px;font-size:var(--tx-xs);font-weight:700;">
                1 EUR = {{ number_format($eurTryRate, 2) }} TRY · bugün
            </span>
            @endif
            <a href="{{ route('student.discover') }}" style="background:rgba(255,255,255,.18);color:#fff;text-decoration:none;border-radius:20px;padding:6px 14px;font-size:var(--tx-xs);font-weight:600;border:1px solid rgba(255,255,255,.3);transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.28)'" onmouseout="this.style.background='rgba(255,255,255,.18)'">🧭 Tüm İçerikler</a>
            <a href="{{ route('student.discover', ['cat'=>'city-content']) }}" style="background:rgba(255,255,255,.18);color:#fff;text-decoration:none;border-radius:20px;padding:6px 14px;font-size:var(--tx-xs);font-weight:600;border:1px solid rgba(255,255,255,.3);transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.28)'" onmouseout="this.style.background='rgba(255,255,255,.18)'">🏙 Şehir Rehberleri</a>
            <a href="{{ route('student.discover', ['cat'=>'tips-tricks']) }}" style="background:rgba(255,255,255,.18);color:#fff;text-decoration:none;border-radius:20px;padding:6px 14px;font-size:var(--tx-xs);font-weight:600;border:1px solid rgba(255,255,255,.3);transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.28)'" onmouseout="this.style.background='rgba(255,255,255,.18)'">💡 Pratik İpuçları</a>
            <a href="{{ route('student.discover', ['cat'=>'careers']) }}" style="background:rgba(255,255,255,.18);color:#fff;text-decoration:none;border-radius:20px;padding:6px 14px;font-size:var(--tx-xs);font-weight:600;border:1px solid rgba(255,255,255,.3);transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.28)'" onmouseout="this.style.background='rgba(255,255,255,.18)'">💼 Kariyer</a>
            <a href="{{ route('student.discover', ['cat'=>'student-life']) }}" style="background:rgba(255,255,255,.18);color:#fff;text-decoration:none;border-radius:20px;padding:6px 14px;font-size:var(--tx-xs);font-weight:600;border:1px solid rgba(255,255,255,.3);transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.28)'" onmouseout="this.style.background='rgba(255,255,255,.18)'">🎓 Öğrenci Hayatı</a>
        </div>
    </div>
</div>

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
                    @if(isset($eurTryRate) && $eurTryRate)
                    <th style="padding:12px 16px;text-align:right;font-weight:700;border-bottom:1px solid var(--u-line,#e2e8f0);color:var(--u-muted,#64748b);">≈ TRY</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @php
                $costCities = config('cost_calculator.cities', []);
                @endphp
                @foreach($costCities as $key => $c)
                @php
                    $monthly = ($c['rent_avg']??0)+($c['food_avg']??0)+($c['transport_avg']??0)+($c['misc_avg']??0)+110;
                    $isExpensive = $monthly > 1100;
                @endphp
                <tr style="border-bottom:1px solid var(--u-line,#e2e8f0);">
                    <td style="padding:12px 16px;font-weight:600;">
                        {{ $c['label']??$key }}
                        @if($isExpensive)
                        <span class="badge warn" style="font-size:10px;margin-left:6px;vertical-align:middle;">Pahalı</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;text-align:right;">€ {{ number_format($c['rent_avg']??0,0,',','.') }}</td>
                    <td style="padding:12px 16px;text-align:right;">€ {{ number_format($c['food_avg']??0,0,',','.') }}</td>
                    <td style="padding:12px 16px;text-align:right;">€ {{ number_format($c['transport_avg']??0,0,',','.') }}</td>
                    <td style="padding:12px 16px;text-align:right;">€ {{ number_format(($c['misc_avg']??0)+110,0,',','.') }}</td>
                    <td style="padding:12px 16px;text-align:right;font-weight:800;color:var(--u-brand,#2563eb);">€ {{ number_format($monthly,0,',','.') }}</td>
                    @if(isset($eurTryRate) && $eurTryRate)
                    <td style="padding:12px 16px;text-align:right;color:var(--u-muted,#64748b);font-size:var(--tx-xs);">₺ {{ number_format($monthly * $eurTryRate, 0, ',', '.') }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:10px 16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">* Sağlık sigortası (€110/ay) dahil | Kaynak: Studentenwerk 2025-2026</div>
    </div>
</div>

<div class="col2" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">🏠 Konut Seçenekleri</div>
            @foreach([
                ['Studentenwohnheim (Yurt)','En ucuz seçenek. Bekleme listesi uzun olabilir.','€150–400/ay','ok'],
                ['WG (Paylaşımlı Ev)','En popüler seçenek. WG-Gesucht.de kullanın.','€300–600/ay','info'],
                ['Tek Kişilik Daire','En pahalı seçenek.','€600–1200/ay','warn'],
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
                💡 Kabul mektubu gelir gelmez konut aramaya başlayın. Popüler şehirlerde 1-3 ay sürebilir.
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📋 Almanya'ya Gelince Yapılacaklar</div>
            @foreach([
                ['1','Anmeldung (İkamet Tescili)','Gelişten itibaren 2 hafta içinde zorunlu.'],
                ['2','Banka Hesabı','DKB, N26 veya Deutsche Bank — öğrenci hesabı ücretsiz.'],
                ['3','Sağlık Sigortası','TK, AOK, Barmer. €110-130/ay.'],
                ['4','Üniversite Kaydı','Kabul + sigorta belgesi + Anmeldung.'],
                ['5','Semesterticket','Toplu taşıma hakkı.'],
                ['6','Sperrkonto Serbest Bırakma','Aylık ~€934 çekme hakkı başlar.'],
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

<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">💡 Tasarruf İpuçları</div>
        <div class="col3" style="margin-bottom:0;">
            @foreach([
                ['🛒','Gıda','ALDI, LIDL, REWE — öğrencilerin favorisi. Market alışverişiyle €150-200/ay\'a düşürebilirsiniz.'],
                ['🚲','Ulaşım','Semesterticket ile toplu taşıma ücretsiz/indirimli. Bisiklet de yaygın.'],
                ['💊','Sağlık','Devlet sigortasıyla (TK/AOK) tüm temel hizmetler kapsanır.'],
                ['📱','Telefon','Aldi Talk, Congstar €10-15/ay. Öğrenci kontratları da mevcut.'],
                ['☕','Sosyal Hayat','Mensa öğle yemeği €2-4. Kütüphaneler ücretsiz.'],
                ['💰','Çalışma','Yılda 120 tam gün çalışabilirsiniz. Minijob yaygın.'],
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
    <a href="{{ '/student/dashboard' }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard'a Dön</a>
</div>

@endsection

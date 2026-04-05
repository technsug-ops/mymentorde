@extends('guest.layouts.app')
@section('title', ($city['name'] ?? 'Şehir') . ' — Almanya\'da Yaşam')
@section('page_title', ($city['name'] ?? '') . ' Rehberi')

@push('head')
<script nonce="{{ $cspNonce ?? '' }}">if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist [style*="gradient"] {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border: 1px solid rgba(0,0,0,.10) !important;
}
.cms-card-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,.1);
}
</style>
@endpush

@section('content')
@php
    $c = $city ?? [];
    $costLabels = [1=>'Çok Uygun', 2=>'Uygun', 3=>'Orta', 4=>'Pahalı', 5=>'Çok Pahalı'];
    $costBadge  = [1=>'ok', 2=>'ok', 3=>'info', 4=>'warn', 5=>'danger'];
    $idx = $c['cost_index'] ?? 3;
    $collarIcon = ['beyaz yaka'=>'👔', 'mavi yaka'=>'🔧', 'her ikisi'=>'⚡'];
@endphp

{{-- Hero --}}
<div class="card" style="background:{{ $c['hero_color'] ?? 'var(--u-brand)' }};color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px;">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div style="font-size:48px;line-height:1;">{{ $c['emoji'] ?? '🏙' }}</div>
            <div style="flex:1;min-width:200px;">
                <div style="font-size:var(--tx-xs);opacity:.8;margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px;">{{ $c['state'] ?? '' }} Eyaleti</div>
                <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:4px;">{{ $c['name'] ?? '' }}</div>
                <div style="font-size:var(--tx-sm);opacity:.85;">{{ $c['tagline'] ?? '' }}</div>
            </div>

            {{-- Video Thumbnail --}}
            @php $heroVid = $c['hero_video_id'] ?? ''; @endphp
            @if($heroVid)
            <button data-vid-open="{{ $heroVid }}"
                    style="position:relative;width:380px;height:214px;border-radius:12px;overflow:hidden;border:3px solid rgba(255,255,255,.4);cursor:pointer;padding:0;flex-shrink:0;background:#000;">
                <img src="https://img.youtube.com/vi/{{ $heroVid }}/mqdefault.jpg"
                     alt="{{ $c['name'] }} video"
                     style="width:100%;height:100%;object-fit:cover;opacity:.85;">
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;">
                    <div style="width:52px;height:52px;background:rgba(255,0,0,.9);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.4);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#fff"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <span style="font-size:.72rem;font-weight:700;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.8);">▶ Videoyu İzle</span>
                </div>
            </button>
            @endif

            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                <span class="badge" style="background:rgba(255,255,255,.2);color:#fff;font-size:var(--tx-xs);">👥 {{ $c['population'] ?? '' }}</span>
                <span class="badge" style="background:rgba(255,255,255,.2);color:#fff;font-size:var(--tx-xs);">🎓 {{ $c['student_pop'] ?? '' }} öğrenci</span>
                <span class="badge" style="background:rgba(255,255,255,.2);color:#fff;font-size:var(--tx-xs);">💶 {{ $costLabels[$idx] }}</span>
            </div>
        </div>
        @if(!empty($c['overview']))
        <div style="margin-top:16px;font-size:var(--tx-sm);opacity:.9;line-height:1.6;max-width:700px;border-top:1px solid rgba(255,255,255,.2);padding-top:14px;">
            {{ $c['overview'] }}
        </div>
        @endif
    </div>
</div>

{{-- Şehir Navigasyonu --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
    @foreach($allCities ?? [] as $key => $ac)
    <a href="{{ route('guest.city-detail', $key) }}"
       style="padding:6px 14px;border-radius:20px;font-size:var(--tx-xs);font-weight:600;text-decoration:none;
              {{ $key === ($c['slug'] ?? '') ? 'background:var(--u-brand,#2563eb);color:#fff;' : 'background:var(--u-card);color:var(--u-text);border:1px solid var(--u-line);' }}">
        {{ $ac['emoji'] ?? '' }} {{ $ac['name'] }}
    </a>
    @endforeach
</div>

<div class="col2" style="margin-bottom:20px;">

    {{-- Konum & Ulaşım --}}
    @if(!empty($c['location']))
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">📍 Konum & Ulaşım</div>
            @if(!empty($c['location']['region']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">BÖLGE</div>
                <div style="font-size:var(--tx-sm);">{{ $c['location']['region'] }}</div>
            </div>
            @endif
            @if(!empty($c['location']['airport']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">HAVALİMANI</div>
                <div style="font-size:var(--tx-sm);">✈ {{ $c['location']['airport'] }}</div>
            </div>
            @endif
            @if(!empty($c['location']['train_hubs']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:6px;">TREN BAĞLANTILARI</div>
                @foreach($c['location']['train_hubs'] as $train)
                <div style="font-size:var(--tx-xs);margin-bottom:3px;">🚄 {{ $train }}</div>
                @endforeach
            </div>
            @endif
            @if(!empty($c['location']['city_transport']))
            <div style="padding:8px 0;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">ŞEHİR İÇİ ULAŞIM</div>
                <div style="font-size:var(--tx-xs);">🚇 {{ $c['location']['city_transport'] }}</div>
            </div>
            @endif
            @if(!empty($c['location']['geography']))
            <div style="margin-top:8px;padding:8px 10px;background:var(--u-bg,#f8fafc);border-radius:6px;font-size:var(--tx-xs);color:var(--u-muted);">
                {{ $c['location']['geography'] }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Kültür --}}
    @if(!empty($c['culture']))
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">🎭 Şehir Kültürü</div>
            @if(!empty($c['culture']['personality']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">KARAKTERİ</div>
                <div style="font-size:var(--tx-sm);font-style:italic;">{{ $c['culture']['personality'] }}</div>
            </div>
            @endif
            @if(!empty($c['culture']['notable_for']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:6px;">TANINDIĞI ÖZELLİKLER</div>
                <div style="display:flex;flex-wrap:wrap;gap:5px;">
                    @foreach($c['culture']['notable_for'] as $n)
                    <span class="badge info" style="font-size:var(--tx-xs);">{{ $n }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            @if(!empty($c['culture']['student_life']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">ÖĞRENCİ HAYATI</div>
                <div style="font-size:var(--tx-xs);line-height:1.5;">{{ $c['culture']['student_life'] }}</div>
            </div>
            @endif
            @if(!empty($c['culture']['turkish_community']))
            <div style="padding:8px 0;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">🇹🇷 TÜRK TOPLULUĞU</div>
                <div style="font-size:var(--tx-xs);line-height:1.5;">{{ $c['culture']['turkish_community'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

{{-- Üniversiteler --}}
@if(!empty($c['universities']))
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">🏛 Üniversiteler</div>
<div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
    @foreach($c['universities'] as $uni)
    <div class="card">
        <div class="card-body" style="padding:0;">
            <div style="padding:16px 18px;border-bottom:1px solid var(--u-line);">
                <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:4px;">{{ $uni['name'] }}</div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <span class="badge info" style="font-size:var(--tx-xs);">{{ $uni['type'] ?? '' }}</span>
                            @if(!empty($uni['founded']))
                            <span class="badge" style="font-size:var(--tx-xs);">Est. {{ $uni['founded'] }}</span>
                            @endif
                            @if(!empty($uni['students']))
                            <span class="badge" style="font-size:var(--tx-xs);">👥 {{ number_format($uni['students']) }} öğrenci</span>
                            @endif
                            @if(!empty($uni['english_programs']))
                            <span class="badge ok" style="font-size:var(--tx-xs);">🌍 İngilizce program var</span>
                            @endif
                        </div>
                    </div>
                    @if(!empty($uni['qs_ranking']))
                    <div style="text-align:center;padding:8px 14px;background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:10px;color:#fff;">
                        <div style="font-size:var(--tx-lg);font-weight:800;">#{{ $uni['qs_ranking'] }}</div>
                        <div style="font-size:var(--tx-xs);opacity:.8;">QS Dünya</div>
                    </div>
                    @endif
                </div>
            </div>
            <div style="padding:12px 18px;display:flex;gap:16px;flex-wrap:wrap;">
                @if(!empty($uni['strengths']))
                <div style="flex:1;min-width:180px;">
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:6px;">GÜÇLÜ PROGRAMLAR</div>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($uni['strengths'] as $s)
                        <span class="badge ok" style="font-size:var(--tx-xs);">{{ $s }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($uni['note']))
                <div style="flex:1;min-width:180px;">
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">NOT</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-text);line-height:1.5;">{{ $uni['note'] }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="col2" style="margin-bottom:20px;">

    {{-- Yaşam Maliyeti --}}
    @if(!empty($c['cost_of_living']))
    @php $cost = $c['cost_of_living']; @endphp
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div style="font-weight:700;font-size:var(--tx-sm);">💶 Yaşam Maliyeti</div>
                <span class="badge {{ $costBadge[$idx] ?? 'info' }}">{{ $costLabels[$idx] ?? '' }}</span>
            </div>
            {{-- Pahalılık çubuğu --}}
            <div style="margin-bottom:14px;">
                <div style="display:flex;gap:4px;">
                    @for($i=1;$i<=5;$i++)
                    <div style="flex:1;height:8px;border-radius:4px;background:{{ $i<=$idx ? ($idx>=4?'#dc2626':($idx>=3?'#d97706':'#16a34a')) : 'var(--u-line)' }};"></div>
                    @endfor
                </div>
            </div>
            @if(!empty($cost['rent']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">KİRA</div>
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);margin-bottom:2px;">
                    <span>WG/Oda</span><strong>{{ $cost['rent']['wg_room'] ?? '' }}</strong>
                </div>
                @if(!empty($cost['rent']['studentenwohnheim']))
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);">
                    <span>Öğrenci Yurdu</span><strong style="color:var(--u-ok,#16a34a);">{{ $cost['rent']['studentenwohnheim'] }}</strong>
                </div>
                @endif
            </div>
            @endif
            @if(!empty($cost['food']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">YEMEK</div>
                @if(!empty($cost['food']['mensa_lunch']))
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);">
                    <span>Mensa öğle</span><strong>{{ $cost['food']['mensa_lunch'] }}</strong>
                </div>
                @endif
                @if(!empty($cost['food']['grocery_monthly']))
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);">
                    <span>Market (aylık)</span><strong>{{ $cost['food']['grocery_monthly'] }}</strong>
                </div>
                @endif
            </div>
            @endif
            @if(!empty($cost['monthly_total_estimate']))
            <div style="margin-top:12px;padding:10px 12px;background:rgba(37,99,235,.06);border-radius:8px;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">TOPLAM TAHMİN (tipik öğrenci)</div>
                <div style="font-size:var(--tx-xl);font-weight:800;color:var(--u-brand,#2563eb);">{{ $cost['monthly_total_estimate'] }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Sağlık sigortası dahil, yurt hariç</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Artılar / Eksiler --}}
    @if(!empty($c['pros_cons']))
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">⚖️ Artılar & Eksiler</div>
            @if(!empty($c['pros_cons']['pros']))
            <div style="margin-bottom:12px;">
                <div style="font-size:var(--tx-xs);color:var(--u-ok,#16a34a);font-weight:700;margin-bottom:6px;">✓ ARTILARI</div>
                @foreach($c['pros_cons']['pros'] as $p)
                <div style="font-size:var(--tx-xs);padding:4px 0;border-bottom:1px solid var(--u-line);display:flex;gap:6px;align-items:flex-start;">
                    <span style="color:var(--u-ok,#16a34a);flex-shrink:0;">+</span><span>{{ $p }}</span>
                </div>
                @endforeach
            </div>
            @endif
            @if(!empty($c['pros_cons']['cons']))
            <div>
                <div style="font-size:var(--tx-xs);color:var(--u-danger,#dc2626);font-weight:700;margin-bottom:6px;">✗ EKSİLERİ</div>
                @foreach($c['pros_cons']['cons'] as $con)
                <div style="font-size:var(--tx-xs);padding:4px 0;border-bottom:1px solid var(--u-line);display:flex;gap:6px;align-items:flex-start;">
                    <span style="color:var(--u-danger,#dc2626);flex-shrink:0;">−</span><span>{{ $con }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

{{-- İş Piyasası --}}
@if(!empty($c['job_market']))
@php $jm = $c['job_market']; @endphp
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">💼 İş Piyasası & Kariyer</div>
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:20px;">
        @if(!empty($jm['overview']))
        <div style="font-size:var(--tx-sm);color:var(--u-text);margin-bottom:14px;line-height:1.6;padding:12px;background:rgba(37,99,235,.05);border-radius:8px;border-left:3px solid var(--u-brand,#2563eb);">
            {{ $jm['overview'] }}
        </div>
        @endif
        <div class="col3" style="margin-bottom:0;">
            @if(!empty($jm['avg_salary']))
            <div style="text-align:center;padding:14px;background:var(--u-bg,#f8fafc);border-radius:10px;border:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xl);font-weight:800;color:var(--u-ok,#16a34a);">{{ $jm['avg_salary'] }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Ortalama Maaş</div>
            </div>
            @endif
            @if(!empty($jm['unemployment']))
            <div style="text-align:center;padding:14px;background:var(--u-bg,#f8fafc);border-radius:10px;border:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xl);font-weight:800;color:var(--u-brand,#2563eb);">{{ $jm['unemployment'] }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">İşsizlik Oranı</div>
            </div>
            @endif
            @if(!empty($jm['student_jobs']))
            <div style="text-align:center;padding:14px;background:var(--u-bg,#f8fafc);border-radius:10px;border:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">ÖĞRENCİ İŞLERİ</div>
                <div style="font-size:var(--tx-xs);color:var(--u-text);">{{ $jm['student_jobs'] }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@if(!empty($jm['dominant_sectors']))
<div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
    @foreach($jm['dominant_sectors'] as $sector)
    <div class="card">
        <div class="card-body" style="padding:16px 18px;">
            <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <span style="font-size:var(--tx-lg);">{{ $collarIcon[$sector['collar'] ?? 'beyaz yaka'] ?? '💼' }}</span>
                        <span style="font-weight:700;font-size:var(--tx-sm);">{{ $sector['name'] }}</span>
                        <span class="badge info" style="font-size:var(--tx-xs);">{{ $sector['collar'] ?? '' }}</span>
                    </div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.5;margin-bottom:8px;">{{ $sector['description'] ?? '' }}</div>
                    @if(!empty($sector['companies']))
                    <div style="display:flex;flex-wrap:wrap;gap:5px;">
                        @foreach($sector['companies'] as $co)
                        <span style="font-size:var(--tx-xs);padding:2px 8px;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line);border-radius:12px;color:var(--u-text);">{{ $co }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                {{-- Yoğunluk barı --}}
                @if(!empty($sector['intensity']))
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px;min-width:60px;">
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);">YOĞUNLUK</div>
                    <div style="display:flex;flex-direction:column;gap:3px;">
                        @for($i=5;$i>=1;$i--)
                        <div style="width:40px;height:6px;border-radius:3px;background:{{ $i<=$sector['intensity'] ? '#2563eb' : 'var(--u-line)' }};"></div>
                        @endfor
                    </div>
                    <div style="font-size:var(--tx-sm);font-weight:800;color:var(--u-brand,#2563eb);">{{ $sector['intensity'] }}/5</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endif

{{-- Gezilecek Yerler --}}
@if(!empty($c['attractions']))
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📸 Gezilecek Yerler</div>
<div class="col3" style="margin-bottom:20px;">
    @foreach($c['attractions'] as $att)
    <div class="card" style="margin-bottom:0;">
        <div class="card-body" style="padding:14px 16px;display:flex;align-items:center;gap:12px;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:2px;">{{ $att['name'] }}</div>
                <div style="display:flex;gap:6px;align-items:center;margin-top:4px;">
                    <span class="badge" style="font-size:var(--tx-xs);">{{ $att['type'] ?? '' }}</span>
                    <span class="badge ok" style="font-size:var(--tx-xs);">{{ $att['price'] ?? '' }}</span>
                </div>
                @if(!empty($att['note']))
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;line-height:1.4;">{{ $att['note'] }}</div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Öğrenci İpuçları --}}
@if(!empty($c['student_tips']))
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">💡 Öğrenci İpuçları</div>
        <div class="col2">
            @foreach($c['student_tips'] as $tip => $desc)
            <div style="padding:10px 12px;background:var(--u-bg,#f8fafc);border-radius:8px;border:1px solid var(--u-line);">
                <div style="font-weight:600;font-size:var(--tx-xs);margin-bottom:3px;color:var(--u-brand,#2563eb);">{{ $tip }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.4;">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Etkinlikler --}}
@if(!empty($c['culture']['events']))
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">🎪 Önemli Etkinlikler</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($c['culture']['events'] as $event => $desc)
            <div style="display:flex;gap:12px;align-items:center;padding:8px;background:var(--u-bg,#f8fafc);border-radius:8px;">
                <span style="font-weight:700;font-size:var(--tx-xs);color:var(--u-brand,#2563eb);min-width:160px;">{{ $event }}</span>
                <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $desc }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- 📹 Video İçerikleri --}}
@php
    $videos = collect($c['videos'] ?? [])->filter(fn($v) => !empty($v['youtube_id']));
    $categoryLabels = ['şehir' => '🏙 Şehir Hayatı', 'üniversite' => '🏛 Üniversite', 'yaşam' => '🏠 Yaşam', 'kariyer' => '💼 Kariyer', 'genel' => '📌 Genel'];
    $categoryColors = ['şehir' => '#2563eb', 'üniversite' => '#7c3aed', 'yaşam' => '#16a34a', 'kariyer' => '#d97706', 'genel' => '#64748b'];
@endphp
@if($videos->isNotEmpty())
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📹 Video İçerikleri</div>

{{-- Kategori filtre butonları --}}
@php $videoCategories = $videos->pluck('category')->unique()->values(); @endphp
@if($videoCategories->count() > 1)
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;" id="vid-filters">
    <button data-cat="all"
            style="padding:5px 14px;border-radius:20px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;border:2px solid var(--u-brand,#2563eb);background:var(--u-brand,#2563eb);color:#fff;">
        Tümü ({{ $videos->count() }})
    </button>
    @foreach($videoCategories as $vcat)
    <button data-cat="{{ $vcat }}"
            style="padding:5px 14px;border-radius:20px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;border:2px solid {{ $categoryColors[$vcat] ?? '#64748b' }};background:var(--u-card);color:{{ $categoryColors[$vcat] ?? '#64748b' }};">
        {{ $categoryLabels[$vcat] ?? $vcat }} ({{ $videos->where('category', $vcat)->count() }})
    </button>
    @endforeach
</div>
@endif

<div class="col2" style="margin-bottom:24px;" id="vid-grid">
    @foreach($videos as $vid)
    @php
        $embedUrl = 'https://www.youtube.com/embed/' . htmlspecialchars($vid['youtube_id'], ENT_QUOTES, 'UTF-8') . '?rel=0&modestbranding=1';
        $thumbUrl = 'https://img.youtube.com/vi/' . htmlspecialchars($vid['youtube_id'], ENT_QUOTES, 'UTF-8') . '/hqdefault.jpg';
        $vcat = $vid['category'] ?? 'genel';
        $vcolor = $categoryColors[$vcat] ?? '#64748b';
    @endphp
    <div class="card vid-card" data-cat="{{ $vcat }}" style="margin-bottom:0;overflow:hidden;">
        {{-- Thumbnail tıklama ile embed aç --}}
        <div style="position:relative;padding-bottom:56.25%;height:0;background:#0f0f0f;cursor:pointer;"
             data-vid-play="{{ $embedUrl }}"
             title="{{ $vid['title'] ?? '' }}">
            <img src="{{ $thumbUrl }}" alt="{{ $vid['title'] ?? '' }}"
                 loading="lazy"
                 style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;opacity:.85;">
            {{-- Play butonu --}}
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                        width:56px;height:56px;background:rgba(255,0,0,.85);border-radius:50%;
                        display:flex;align-items:center;justify-content:center;pointer-events:none;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><polygon points="8,5 19,12 8,19"/></svg>
            </div>
            @if(!empty($vid['duration']))
            <div style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.75);color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:4px;">
                {{ $vid['duration'] }}
            </div>
            @endif
            {{-- Kategori badge --}}
            <div style="position:absolute;top:8px;left:8px;background:{{ $vcolor }};color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:12px;">
                {{ $categoryLabels[$vcat] ?? $vcat }}
            </div>
        </div>
        <div style="padding:12px 14px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:4px;line-height:1.3;">{{ $vid['title'] ?? '' }}</div>
            @if(!empty($vid['description']))
            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.4;">{{ $vid['description'] }}</div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@endif

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    // Video oynat: data-vid-play veya data-vid-open
    function playVideoEl(el, embedUrl) {
        var iframe = document.createElement('iframe');
        iframe.src = embedUrl + '&autoplay=1';
        iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;';
        iframe.allow = 'accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share';
        iframe.allowFullscreen = true;
        el.style.cursor = 'default';
        el.innerHTML = '';
        el.appendChild(iframe);
    }

    // Kategori filtresi
    function filterVideos(cat) {
        document.querySelectorAll('#vid-filters button').forEach(function (btn) {
            var isSel = btn.dataset.cat === cat;
            btn.style.background = isSel ? (btn.style.borderColor || 'var(--u-brand,#2563eb)') : 'var(--u-card)';
            btn.style.color      = isSel ? '#fff' : (btn.style.borderColor || 'var(--u-brand,#2563eb)');
        });
        document.querySelectorAll('.vid-card').forEach(function (card) {
            card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
        });
    }

    // Delegation: video grid click
    document.addEventListener('click', function (e) {
        // data-vid-play (grid kartlar)
        var playEl = e.target.closest('[data-vid-play]');
        if (playEl) { playVideoEl(playEl, playEl.dataset.vidPlay); return; }

        // data-vid-open (hero butonu)
        var openEl = e.target.closest('[data-vid-open]');
        if (openEl) {
            var vid = openEl.dataset.vidOpen;
            var embedUrl = 'https://www.youtube.com/embed/' + vid + '?rel=0&modestbranding=1';
            playVideoEl(openEl, embedUrl);
            return;
        }

        // data-cat (filtre butonları)
        var catBtn = e.target.closest('#vid-filters button[data-cat]');
        if (catBtn) { filterVideos(catBtn.dataset.cat); return; }
    });
}());
</script>

{{-- CMS İçerikleri (tag eşleme) --}}
@php
use App\Models\Marketing\CmsContent;
$citySlugForCms = $city['slug'] ?? array_search($city, config('germany_cities', []));
// Try to use the route slug
$routeSlug = request()->route('slug') ?? $citySlugForCms;
$cityContents = CmsContent::where('status', 'published')
    ->whereJsonContains('tags', $routeSlug)
    ->whereIn('type', ['blog', 'experience', 'career_guide', 'tip'])
    ->where(fn($q) => $q->where('target_audience', 'all')->orWhere('target_audience', 'guests'))
    ->orderByDesc('published_at')
    ->limit(4)->get();
@endphp

@if($cityContents->isNotEmpty())
<div style="margin-top:32px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div style="font-size:1rem;font-weight:700;color:var(--u-text,#1a1a1a);">📚 Bu Şehir Hakkında İçerikler</div>
        <a href="{{ route('guest.discover', ['cat' => 'city-content']) }}" style="font-size:.82rem;color:var(--u-brand,#2563eb);text-decoration:none;font-weight:600;">Tümünü Gör →</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
        @foreach($cityContents as $cms)
        @php
        $typeIcons = ['blog'=>'📝','video_feature'=>'▶️','podcast'=>'🎙','presentation'=>'📊','experience'=>'💬','career_guide'=>'🗺','tip'=>'💡'];
        $typeLabels = ['blog'=>'Blog','video_feature'=>'Video','podcast'=>'Podcast','presentation'=>'Sunum','experience'=>'Deneyim','career_guide'=>'Kariyer','tip'=>'İpucu'];
        $gradients = ['student-life'=>'linear-gradient(to right,#0d2748,#1f6fd9)','culture-fun'=>'linear-gradient(to right,#2e1660,#6b3fa0)','careers'=>'linear-gradient(to right,#0a2e18,#166534)','tips-tricks'=>'linear-gradient(to right,#0a2e3e,#1e607a)','city-content'=>'linear-gradient(to right,#072840,#0e6fa0)','uni-content'=>'linear-gradient(to right,#0f1d5a,#2a3fa8)','success-stories'=>'linear-gradient(to right,#0d1e52,#1a3a8a)'];
        @endphp
        <a href="{{ route('guest.content-detail', $cms->slug) }}" class="cms-card-link" style="display:flex;gap:12px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:10px;overflow:hidden;text-decoration:none;color:inherit;transition:transform .15s,box-shadow .15s;">
            <div style="width:72px;flex-shrink:0;background:{{ $gradients[$cms->category] ?? 'linear-gradient(to right,#0d2748,#1f6fd9)' }};display:flex;align-items:center;justify-content:center;font-size:1.8rem;">{{ $typeIcons[$cms->type] ?? '📄' }}</div>
            <div style="padding:10px 12px;flex:1;">
                <div style="font-size:.78rem;color:var(--u-muted,#888);margin-bottom:3px;">{{ $typeLabels[$cms->type] ?? $cms->type }}</div>
                <div style="font-size:.88rem;font-weight:600;color:var(--u-text,#1a1a1a);line-height:1.35;">{{ Str::limit($cms->title_tr, 65) }}</div>
                @if($cms->summary_tr)
                <div style="font-size:.78rem;color:var(--u-muted,#888);margin-top:3px;">{{ Str::limit($cms->summary_tr, 70) }}</div>
                @endif
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Navigasyon --}}
<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;flex-wrap:wrap;gap:8px;margin-top:20px;">
    <a href="{{ route('guest.university-guide') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Üniversite Rehberi</a>
    <a href="{{ route('guest.cost-calculator') }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Maliyet Hesapla →</a>
</div>

{{-- Video Modal --}}
<div id="city-vid-modal"
     style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.85);align-items:center;justify-content:center;"
     onclick="if(event.target===this)cityVidClose()">
    <div style="position:relative;width:min(900px,92vw);">
        <div style="position:relative;padding-bottom:56.25%;height:0;">
            <iframe id="city-vid-iframe" src="" allow="autoplay;accelerometer;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen
                    style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;border-radius:12px;"></iframe>
        </div>
        <button onclick="cityVidClose()"
                style="position:absolute;top:-14px;right:-14px;background:#fff;border:none;color:#111;border-radius:50%;width:36px;height:36px;font-size:18px;cursor:pointer;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.3);">✕</button>
    </div>
</div>
<script>
function cityVidOpen(id){
    var modal = document.getElementById('city-vid-modal');
    document.getElementById('city-vid-iframe').src = 'https://www.youtube.com/embed/' + id + '?autoplay=1&rel=0';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function cityVidClose(){
    document.getElementById('city-vid-modal').style.display = 'none';
    document.getElementById('city-vid-iframe').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') cityVidClose(); });
</script>

@endsection

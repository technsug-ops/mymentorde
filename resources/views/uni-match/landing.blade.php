@extends('uni-match.layout')

@section('title', config('brand.name') . ' UniMatch — Sana özel Almanya programı bul')

@section('content')
@if(! empty($resume))
<div style="max-width:600px;margin:0 auto 24px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;padding:18px 22px;border-left:4px solid #d97706;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <div style="font-size:28px;flex-shrink:0;">⏸️</div>
    <div style="flex:1;min-width:180px;">
        <div style="font-size:14.5px;font-weight:700;color:#78350f;margin-bottom:2px;">Wizard'da yarıda kalmıştın</div>
        <div style="font-size:12.5px;color:#92400e;">{{ $resume['step'] }}/19 adım · %{{ $resume['progress_pct'] }} tamamlandı{{ $resume['started_at'] ? ' · ' . $resume['started_at'] : '' }}</div>
    </div>
    <a href="{{ route('uni-match.step', ['n' => $resume['step']]) }}"
       style="background:#92400e;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;">
        Devam et →
    </a>
</div>
@endif

<div class="sb-hero">
    <span class="sb-hero-badge">✨ AKILLI EŞLEŞTIRME</span>
    <h1 class="sb-hero-title">Almanya'da sana en uygun programı bul</h1>
    <p class="sb-hero-subtitle">5 dakikalık akıllı sihirbazımız 13.000+ Almanya programı arasından profil ve hedeflerine en uygun olanları sıralar.</p>

    <div style="display:flex;gap:14px;justify-content:center;align-items:center;flex-wrap:wrap;">
        <a href="{{ route('uni-match.start') }}" class="sb-btn sb-btn-primary sb-hero-cta"
           data-track="cta_clicked" data-ph-cta-name="unimatch_landing_start">
            Hadi başlayalım
            <span style="font-size: 18px;">→</span>
        </a>
        @if(! empty($popularPrograms))
        <a href="#popular" class="sb-hero-secondary"
           data-track="cta_clicked" data-ph-cta-name="unimatch_landing_browse_popular"
           style="display:inline-flex;align-items:center;gap:6px;padding:14px 22px;border:1.5px solid #d8d2e8;background:#fff;color:#7e58bf;border-radius:10px;text-decoration:none;font-size:14px;font-weight:600;transition:all .15s;">
            👀 Önce popüler programlara bak
            <span style="font-size:14px;">↓</span>
        </a>
        @endif
    </div>

    <div class="sb-hero-meta">Ücretsiz · Login gerekmiyor · İstediğin zaman bırakabilirsin</div>

    <div class="sb-stats">
        <div class="sb-stat">
            <div class="sb-stat-num">13K+</div>
            <div class="sb-stat-label">Almanya Programı</div>
        </div>
        <div class="sb-stat">
            <div class="sb-stat-num">331</div>
            <div class="sb-stat-label">Üniversite</div>
        </div>
        <div class="sb-stat">
            <div class="sb-stat-num">5dk</div>
            <div class="sb-stat-label">Tahmini Süre</div>
        </div>
    </div>
</div>

<div style="max-width: 600px; margin: 60px auto; padding: 28px; background: #fff; border-radius: 16px; box-shadow: 0 4px 16px rgba(126, 88, 191, 0.06);">
    <h2 style="font-size: 18px; color: #7e58bf; margin-bottom: 16px;">Bu sihirbaz nasıl çalışıyor?</h2>
    <ol style="list-style: none; padding: 0; counter-reset: step-counter;">
        <li style="position: relative; padding-left: 36px; margin-bottom: 14px; counter-increment: step-counter; font-size: 14px; color: #1a1a1a;">
            <span style="position: absolute; left: 0; top: 0; background: rgba(126, 88, 191, 0.12); color: #7e58bf; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</span>
            Hedeflerini ve profilini soran <strong>kısa sorulara</strong> cevap ver
        </li>
        <li style="position: relative; padding-left: 36px; margin-bottom: 14px; counter-increment: step-counter; font-size: 14px; color: #1a1a1a;">
            <span style="position: absolute; left: 0; top: 0; background: rgba(126, 88, 191, 0.12); color: #7e58bf; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</span>
            Akıllı motorumuz cevaplarını <strong>13.000+ programla eşleştirir</strong>
        </li>
        <li style="position: relative; padding-left: 36px; margin-bottom: 14px; counter-increment: step-counter; font-size: 14px; color: #1a1a1a;">
            <span style="position: absolute; left: 0; top: 0; background: rgba(126, 88, 191, 0.12); color: #7e58bf; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">3</span>
            Sana en uygun <strong>10 programı sıralar</strong> ve neden uyduğunu açıklar
        </li>
        <li style="position: relative; padding-left: 36px; counter-increment: step-counter; font-size: 14px; color: #1a1a1a;">
            <span style="position: absolute; left: 0; top: 0; background: rgba(126, 88, 191, 0.12); color: #7e58bf; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">4</span>
            Hazır olduğunda <strong>tek tıkla {{ config('brand.name') }}'ye kayıt</strong> ol — danışmanın yönlendirsin
        </li>
    </ol>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     POPÜLER PROGRAMLAR — Coursera tarzı 3-kolon vitrin
     Sosyal proof + SEO + sihirbaza girmeden de "ne var burada?" cevabı
═══════════════════════════════════════════════════════════════════ --}}
@if(! empty($popularPrograms))
<section id="popular" class="popular-section" aria-label="Popüler Almanya programları">
    <div class="popular-inner">
        <div class="popular-head">
            <span style="display:inline-block;background:#7e58bf;color:#fff;padding:5px 14px;border-radius:999px;font-size:11.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">⭐ Vitrin</span>
            <h2 class="popular-title">Almanya'da en çok tercih edilen programlar</h2>
            <p class="popular-subtitle">Türk öğrencilerin yoğun olarak başvurduğu alanlardan örnekler — daha fazlasını sihirbaz sıralasın</p>
        </div>

        <div class="popular-grid">
            @foreach($popularPrograms as $key => $col)
                <div class="popular-col" style="--stagger:{{ $loop->index * 90 }}ms;">
                    <div class="popular-col-head">
                        <span class="popular-col-icon">
                            {!! \App\Support\LucideIcons::renderOrEmoji($col['icon'] ?? null, 22) !!}
                        </span>
                        <div>
                            <div class="popular-col-title">{{ $col['title'] }} <span aria-hidden="true">→</span></div>
                            <div class="popular-col-desc">{{ $col['desc'] }}</div>
                        </div>
                    </div>

                    @forelse($col['programs'] as $p)
                        <a href="{{ route('uni-match.start') }}?utm_source=landing&utm_medium=popular_grid&utm_content={{ $key }}_{{ $p['id'] }}"
                           class="popular-card"
                           data-track="popular_card_clicked"
                           data-ph-program-id="{{ $p['id'] }}"
                           data-ph-category="{{ $key }}">
                            <div class="popular-card-uni">
                                <span class="popular-card-uni-dot" aria-hidden="true"></span>
                                {{ \Illuminate\Support\Str::limit($p['uni'], 32) }}
                            </div>
                            <div class="popular-card-name">{{ \Illuminate\Support\Str::limit($p['name'], 70) }}</div>
                            <div class="popular-card-meta">
                                <span class="popular-chip">{{ $p['degree'] }}</span>
                                @if($p['is_free'])
                                    <span class="popular-chip popular-chip-free">★ Devlet</span>
                                @else
                                    <span class="popular-chip">{{ $p['tuition'] }}€/sem</span>
                                @endif
                                @if(! empty($p['location']))
                                    <span class="popular-chip popular-chip-loc">{{ \Illuminate\Support\Str::limit($p['location'], 18) }}</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="popular-card popular-card-empty">Yakında eklenecek</div>
                    @endforelse
                </div>
            @endforeach
        </div>

        <div style="text-align:center; margin-top:36px;">
            <a href="{{ route('uni-match.start') }}?utm_source=landing&utm_medium=popular_cta"
               class="sb-btn sb-btn-primary sb-hero-cta"
               data-track="cta_clicked"
               data-ph-cta-name="popular_explore">
                Sana özel programları sıralayalım
                <span style="font-size:18px;">→</span>
            </a>
        </div>
    </div>
</section>

<style nonce="{{ $cspNonce ?? '' }}">
.popular-section {
    margin: 64px calc(-1 * (50vw - 50%)) 24px;
    padding: 56px 20px;
    /* Daha belirgin vitrin hissi — kullanici scroll'da goz attiginda 'devamini gor' diyebilsin */
    background: linear-gradient(180deg, #ede9fe 0%, #f4f2ee 100%);
    border-top: 2px solid rgba(126, 88, 191, 0.18);
    border-bottom: 2px solid rgba(126, 88, 191, 0.18);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
}
.sb-hero-secondary:hover { border-color: #7e58bf !important; background: rgba(126,88,191,.04) !important; }
.popular-inner { max-width: 1100px; margin: 0 auto; }
.popular-head { text-align: center; margin-bottom: 36px; }
.popular-title {
    font-size: 28px; font-weight: 700; color: #1a1a1a;
    letter-spacing: -.5px; line-height: 1.2; margin-bottom: 8px;
}
.popular-subtitle { font-size: 14.5px; color: #6b5894; max-width: 540px; margin: 0 auto; }

.popular-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
@media (max-width: 880px) { .popular-grid { grid-template-columns: 1fr; } }

.popular-col {
    background: #f3edff;
    border-radius: 14px;
    padding: 20px 16px;
    display: flex; flex-direction: column; gap: 10px;
}
.popular-col-head {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 8px 6px 14px; border-bottom: 1px solid rgba(126, 88, 191, 0.12);
    margin-bottom: 4px;
}
.popular-col-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: #fff; box-shadow: 0 2px 8px rgba(126, 88, 191, .12);
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.popular-col-icon .lucide-icon { width: 22px; height: 22px; color: #7e58bf; }
.popular-col-title {
    font-size: 16px; font-weight: 700; color: #1a1a1a; line-height: 1.2;
}
.popular-col-title span { color: #7e58bf; transition: transform .25s cubic-bezier(.4,0,.2,1); display: inline-block; margin-left: 4px; }
.popular-col-desc { font-size: 12px; color: #6b5894; margin-top: 2px; }

.popular-card {
    display: block;
    background: #fff;
    border-radius: 10px;
    padding: 14px 14px;
    text-decoration: none;
    color: inherit;
    border: 1px solid transparent;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: transform .25s cubic-bezier(.4,0,.2,1),
                box-shadow .25s cubic-bezier(.4,0,.2,1),
                border-color .2s cubic-bezier(.4,0,.2,1);
}
.popular-card:hover {
    transform: translateY(-3px) scale(1.01);
    box-shadow: 0 12px 28px rgba(126, 88, 191, .18);
    border-color: rgba(126, 88, 191, 0.25);
}
.popular-card-empty {
    text-align: center; color: #8a7baf; font-size: 12.5px;
    padding: 20px; border: 2px dashed #d4c5e8; background: transparent;
    cursor: default;
}
.popular-card-empty:hover { transform: none; box-shadow: none; }

.popular-card-uni {
    font-size: 11.5px; color: #6b5894; font-weight: 600;
    letter-spacing: .2px; margin-bottom: 6px;
    display: inline-flex; align-items: center; gap: 6px;
}
.popular-card-uni-dot {
    width: 16px; height: 16px; border-radius: 4px;
    background: linear-gradient(135deg, #7e58bf, #a07ed9);
    display: inline-block; flex-shrink: 0;
}
.popular-card-name {
    font-size: 13.5px; font-weight: 700; color: #1a1a1a;
    line-height: 1.35; margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.popular-card-meta { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
.popular-chip {
    font-size: 10.5px; font-weight: 600;
    background: rgba(126, 88, 191, 0.10); color: #7e58bf;
    padding: 3px 8px; border-radius: 999px;
}
.popular-chip-free { background: rgba(22, 163, 74, 0.12); color: #15803d; }
.popular-chip-loc { background: #f4f2ee; color: #6b5894; }

@media (prefers-reduced-motion: no-preference) {
    .popular-col {
        animation: pop-col-in .55s cubic-bezier(.4,0,.2,1) both;
        animation-delay: var(--stagger, 0ms);
    }
    @keyframes pop-col-in {
        0%   { opacity: 0; transform: translateY(18px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    .popular-col:hover .popular-col-title span { transform: translateX(4px); }
}
</style>
@endif
@endsection

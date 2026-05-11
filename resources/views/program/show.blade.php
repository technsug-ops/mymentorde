@extends('uni-match.layout')

@section('title', $program->course_name . ' — ' . $program->university_name_cached)

@php
    // ── Üniversite görseli — TR-kaynaklı Almanya üni fotoğraf havuzu ──
    // Öncelik: DB image_path > keyword match (Heidelberg/Leipzig/LMU/Freiburg/Berlin)
    //          > generic Almanya/üniversite fallback (hash-based deterministic)
    $uniName = $program->university_name_cached ?? '';
    $uniLow  = mb_strtolower($uniName, 'UTF-8');

    $heroImage = null;

    // 1) DB image_path varsa onu kullan
    if (! empty($program->university?->image_path)) {
        $heroImage = str_starts_with($program->university->image_path, 'http')
            ? $program->university->image_path
            : asset($program->university->image_path);
    }

    // 2) Keyword bazlı spesifik eşleşme — local self-hosted (DSGVO uyum)
    if (! $heroImage) {
        $specific = [
            'heidelberg'                  => '/img/uni-watermarks/heidelberg.jpg',
            'leipzig'                     => '/img/uni-watermarks/leipzig.jpg',
            'lmu'                         => '/img/uni-watermarks/lmu.jpg',
            'ludwig-maximilian'           => '/img/uni-watermarks/lmu.jpg',
            'ludwig maximilian'           => '/img/uni-watermarks/lmu.jpg',
            'münchen'                     => '/img/uni-watermarks/lmu.jpg',
            'munich'                      => '/img/uni-watermarks/lmu.jpg',
            'freiburg'                    => '/img/uni-watermarks/freiburg.jpg',
            'freie universität berlin'    => '/img/uni-watermarks/free-berlin.jpg',
            'free university of berlin'   => '/img/uni-watermarks/free-berlin.jpg',
            'berlin international'        => '/img/uni-watermarks/berlin-int.jpg',
            'schiller'                    => '/img/uni-watermarks/schiller.jpg',
        ];
        foreach ($specific as $kw => $url) {
            if ($uniLow !== '' && str_contains($uniLow, $kw)) {
                $heroImage = $url;
                break;
            }
        }
    }

    // 3) Generic fallback — hash mod havuz boyutu (aynı uni hep aynı foto)
    if (! $heroImage) {
        $generalPool = [
            '/img/uni-watermarks/generic-1.jpg',
            '/img/uni-watermarks/generic-2.jpg',
            '/img/uni-watermarks/generic-3.png',
            '/img/uni-watermarks/generic-4.png',
            '/img/uni-watermarks/generic-5.jpg',
        ];
        $idx = abs(crc32($uniName)) % count($generalPool);
        $heroImage = $generalPool[$idx];
    }
@endphp

@push('head')
<script nonce="{{ $cspNonce ?? '' }}">
    document.documentElement.classList.add('sb-detail-active');
</script>
<style nonce="{{ $cspNonce ?? '' }}">
/* Üniversiteye özgü filigran — body fixed full-page watermark */
body.sb-detail-mode::before {
    background-image: url('{{ $heroImage }}');
}
</style>
@endpush

@section('back-link')
    <a href="javascript:history.back()" class="sb-back">← Geri</a>
@endsection

@section('content')
<div class="sb-card" style="padding: 32px 28px; margin-bottom: 18px;">
    {{-- Header --}}
    <div style="display: flex; gap: 18px; align-items: flex-start; margin-bottom: 18px;">
        <div style="width: 64px; height: 64px; flex-shrink: 0; background: linear-gradient(135deg, #7e58bf, #a07ed9); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 26px; font-weight: 700; letter-spacing: -0.5px;">
            {{ mb_strtoupper(mb_substr($program->university_name_cached ?? '?', 0, 2)) }}
        </div>
        <div style="flex: 1;">
            <div style="font-size: 13.5px; color: #6b5894; font-weight: 600; margin-bottom: 4px;">
                {{ $program->university_name_cached }}
                @if($program->location)
                    <span style="opacity: 0.6;"> · {{ $program->location }}</span>
                @endif
            </div>
            <h1 style="font-size: 28px; font-weight: 700; color: #1a1a1a; line-height: 1.2; letter-spacing: -0.5px; margin-bottom: 8px;">
                {{ $program->course_name }}
            </h1>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @if($program->degree_specification)
                    <span style="background: rgba(126, 88, 191, 0.12); color: #7e58bf; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600;">{{ $program->degree_specification }}</span>
                @endif
                @foreach((array) $program->languages_raw as $lang)
                    <span style="background: #ede9fe; color: #6d28d9; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600;">{{ $lang }}</span>
                @endforeach
                @if($program->is_manually_curated)
                    <span style="background: rgba(22,163,74,0.12); color: #15803d; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600;">✓ Manuel onay</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Quality + Source --}}
    <div style="display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid #f0ecf6; font-size: 12.5px; color: #6b5894; flex-wrap: wrap;">
        <span><strong style="color: #7e58bf;">Veri Kalitesi:</strong> {{ $program->quality_score }}/100</span>
        @foreach($sources as $src)
            <span><strong style="color: #7e58bf;">Kaynak:</strong> {{ $src->source }} (son güncelleme {{ $src->last_synced_at?->diffForHumans() ?? '?' }})</span>
        @endforeach
    </div>
</div>

{{-- Üniversite tanıtım videosu — varsa embed, yoksa header'daki body filigrani gosterilir --}}
@php $videoEmbedUrl = $program->university?->video_embed_url; @endphp
@if($videoEmbedUrl)
<div class="sb-card" style="margin-bottom: 14px; padding: 18px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
        <span style="font-size:20px;">🎬</span>
        <h2 style="font-size: 16px; color: #7e58bf; font-weight: 700; margin: 0;">
            Üniversite Tanıtım Videosu
        </h2>
        @if($program->university?->video_caption)
            <span style="font-size:12px;color:#6b5894;font-style:italic;">— {{ $program->university->video_caption }}</span>
        @endif
    </div>
    <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:10px;background:#000;">
        <iframe src="{{ $videoEmbedUrl }}"
                style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
                loading="lazy"
                title="{{ $program->university->name }} tanıtım videosu"></iframe>
    </div>
</div>
@endif

{{-- Translation banner — eğer TR yoksa --}}
@php
    $hasOriginalText = ! empty($program->description) || ! empty($program->qualification_requirements);
    $hasTranslation = ! empty($program->description_tr) || ! empty($program->qualification_requirements_tr);
@endphp
@if($hasOriginalText && ! $hasTranslation)
<div class="sb-card" id="translateBanner" style="margin-bottom: 14px; background: linear-gradient(135deg, rgba(126, 88, 191, 0.06), rgba(167, 126, 217, 0.03)); display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
    <div style="font-size: 32px;">🇹🇷</div>
    <div style="flex: 1; min-width: 220px;">
        <div style="font-size: 15px; font-weight: 700; color: #1a1a1a;">Bu programın detayları İngilizce</div>
        <div style="font-size: 13px; color: #6b5894; margin-top: 2px;">Tek tıkla MentorDE Lab seninle Türkçeye çevirsin (~3-5 saniye).</div>
    </div>
    <button type="button" id="translateBtn" class="sb-btn sb-btn-primary" style="padding: 10px 20px; font-size: 13.5px;">
        Türkçeye Çevir
        <span style="font-size: 16px;">→</span>
    </button>
</div>
@endif

{{-- Description --}}
@if($program->description)
<div class="sb-card" style="margin-bottom: 14px;" data-section="description">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px; flex-wrap: wrap;">
        <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin: 0;">📋 Program Hakkında</h2>
        @if($program->description_tr)
            <div style="display: inline-flex; gap: 4px; background: var(--u-bg, #f4f2ee); border-radius: 8px; padding: 3px;" data-lang-toggle>
                <button type="button" class="sb-lang-tab active" data-lang="tr" data-target="description-tr-text" style="padding: 4px 12px; border: none; background: #7e58bf; color: #fff; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">🇹🇷 TR</button>
                <button type="button" class="sb-lang-tab" data-lang="en" data-target="description-tr-text" style="padding: 4px 12px; border: none; background: transparent; color: #6b5894; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">🇬🇧 EN</button>
            </div>
        @endif
    </div>
    <div data-text-tr style="font-size: 14px; color: #1a1a1a; line-height: 1.7; white-space: pre-line; {{ $program->description_tr ? '' : 'display:none;' }}">{{ $program->description_tr }}</div>
    <div data-text-en style="font-size: 14px; color: #1a1a1a; line-height: 1.7; white-space: pre-line; {{ $program->description_tr ? 'display:none;' : '' }}">{{ $program->description }}</div>
</div>
@endif

{{-- Hızlı Bilgi Grid --}}
<div class="sb-card" style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin-bottom: 16px;">📊 Hızlı Bilgi</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">

        @if($program->duration_semesters)
            <div>
                <div style="font-size: 11px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Süre</div>
                <div style="font-size: 16px; color: #1a1a1a; font-weight: 600;">{{ $program->duration_semesters }} sömestr</div>
            </div>
        @endif

        @if($program->language)
            <div>
                <div style="font-size: 11px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Eğitim Dili</div>
                <div style="font-size: 16px; color: #1a1a1a; font-weight: 600;">
                    @switch($program->language)
                        @case('de') Almanca @break
                        @case('en') İngilizce @break
                        @case('both') Almanca + İngilizce @break
                        @default {{ $program->language }}
                    @endswitch
                </div>
            </div>
        @endif

        <div>
            <div style="font-size: 11px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Sömestr Ücreti</div>
            <div style="font-size: 16px; font-weight: 600;">
                @if($program->tuition_eur_per_semester === null)
                    <span style="color: #94a3b8;">Belirtilmemiş</span>
                @elseif($program->tuition_eur_per_semester === 0)
                    <span style="color: #15803d;">Ücretsiz ✓</span>
                @else
                    <span style="color: #1a1a1a;">{{ $program->tuition_eur_per_semester }} €</span>
                @endif
            </div>
        </div>

        @if($program->cost_per_semester_eur)
            <div>
                <div style="font-size: 11px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Yaşam Gideri (Tahmini)</div>
                <div style="font-size: 16px; color: #1a1a1a; font-weight: 600;">{{ $program->cost_per_semester_eur }} €/sömestr</div>
            </div>
        @endif

        @if($program->application_fee_eur)
            <div>
                <div style="font-size: 11px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Başvuru Ücreti (non-EU)</div>
                <div style="font-size: 16px; color: #1a1a1a; font-weight: 600;">{{ $program->application_fee_eur }} €</div>
            </div>
        @endif

        @if($program->admission_type)
            <div>
                <div style="font-size: 11px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Kabul Türü</div>
                <div style="font-size: 14.5px; color: #1a1a1a; font-weight: 600;">{{ $program->admission_type }}</div>
            </div>
        @endif

        @if($program->nc_value)
            <div>
                <div style="font-size: 11px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">NC (Numerus Clausus)</div>
                <div style="font-size: 14.5px; color: #1a1a1a; font-weight: 600;">{{ $program->nc_value }}</div>
            </div>
        @endif
    </div>
</div>

{{-- Başvuru Tarihleri --}}
@if($program->application_deadline_summer || $program->application_deadline_winter)
<div class="sb-card" style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin-bottom: 16px;">📅 Başvuru Tarihleri</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
        @if($program->application_deadline_summer)
            <div style="background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(245,158,11,0.02)); border: 1px solid rgba(245,158,11,0.25); border-radius: 12px; padding: 16px;">
                <div style="font-size: 11px; color: #b45309; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">☀ Yaz Sömestri</div>
                <div style="font-size: 18px; color: #1a1a1a; font-weight: 700;">{{ $program->application_deadline_summer->format('d.m.Y') }}</div>
                <div style="font-size: 11.5px; color: #6b5894; margin-top: 4px;">son başvuru</div>
            </div>
        @endif
        @if($program->application_deadline_winter)
            <div style="background: linear-gradient(135deg, rgba(59,130,246,0.08), rgba(59,130,246,0.02)); border: 1px solid rgba(59,130,246,0.25); border-radius: 12px; padding: 16px;">
                <div style="font-size: 11px; color: #1d4ed8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">❄ Kış Sömestri</div>
                <div style="font-size: 18px; color: #1a1a1a; font-weight: 700;">{{ $program->application_deadline_winter->format('d.m.Y') }}</div>
                <div style="font-size: 11.5px; color: #6b5894; margin-top: 4px;">son başvuru</div>
            </div>
        @endif
    </div>
</div>
@endif

{{-- Giriş Koşulları --}}
@if($program->qualification_requirements)
<div class="sb-card" style="margin-bottom: 14px;" data-section="qualification">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px; flex-wrap: wrap;">
        <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin: 0;">✅ Giriş / Kabul Koşulları</h2>
        @if($program->qualification_requirements_tr)
            <div style="display: inline-flex; gap: 4px; background: var(--u-bg, #f4f2ee); border-radius: 8px; padding: 3px;" data-lang-toggle>
                <button type="button" class="sb-lang-tab active" data-lang="tr" style="padding: 4px 12px; border: none; background: #7e58bf; color: #fff; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">🇹🇷 TR</button>
                <button type="button" class="sb-lang-tab" data-lang="en" style="padding: 4px 12px; border: none; background: transparent; color: #6b5894; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">🇬🇧 EN</button>
            </div>
        @endif
    </div>
    <div data-text-tr style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line; {{ $program->qualification_requirements_tr ? '' : 'display:none;' }}">{{ $program->qualification_requirements_tr }}</div>
    <div data-text-en style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line; {{ $program->qualification_requirements_tr ? 'display:none;' : '' }}">{{ $program->qualification_requirements }}</div>
</div>
@endif

{{-- Dil Gereksinimleri --}}
@if($program->language_requirements)
<div class="sb-card" style="margin-bottom: 14px;" data-section="language_req">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px; flex-wrap: wrap;">
        <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin: 0;">🗣 Dil Gereksinimleri</h2>
        @if($program->language_requirements_tr)
            <div style="display: inline-flex; gap: 4px; background: var(--u-bg, #f4f2ee); border-radius: 8px; padding: 3px;" data-lang-toggle>
                <button type="button" class="sb-lang-tab active" data-lang="tr" style="padding: 4px 12px; border: none; background: #7e58bf; color: #fff; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">🇹🇷 TR</button>
                <button type="button" class="sb-lang-tab" data-lang="en" style="padding: 4px 12px; border: none; background: transparent; color: #6b5894; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">🇬🇧 EN</button>
            </div>
        @endif
    </div>
    <div data-text-tr style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line; {{ $program->language_requirements_tr ? '' : 'display:none;' }}">{{ $program->language_requirements_tr }}</div>
    <div data-text-en style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line; {{ $program->language_requirements_tr ? 'display:none;' : '' }}">{{ $program->language_requirements }}</div>
</div>
@endif

{{-- Gereken Belgeler --}}
@if($program->required_documents)
<div class="sb-card" style="margin-bottom: 14px;" data-section="docs">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px; flex-wrap: wrap;">
        <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin: 0;">📎 Başvuru için Gereken Belgeler</h2>
        @if($program->required_documents_tr)
            <div style="display: inline-flex; gap: 4px; background: var(--u-bg, #f4f2ee); border-radius: 8px; padding: 3px;" data-lang-toggle>
                <button type="button" class="sb-lang-tab active" data-lang="tr" style="padding: 4px 12px; border: none; background: #7e58bf; color: #fff; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">🇹🇷 TR</button>
                <button type="button" class="sb-lang-tab" data-lang="en" style="padding: 4px 12px; border: none; background: transparent; color: #6b5894; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">🇬🇧 EN</button>
            </div>
        @endif
    </div>
    <div data-text-tr style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line; {{ $program->required_documents_tr ? '' : 'display:none;' }}">{{ $program->required_documents_tr }}</div>
    <div data-text-en style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line; {{ $program->required_documents_tr ? 'display:none;' : '' }}">{{ $program->required_documents }}</div>
</div>
@endif

{{-- Alanlar / Konular --}}
@if(! empty($program->study_fields) || ! empty($program->subjects))
<div class="sb-card" style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin-bottom: 12px;">🎓 Akademik Alanlar</h2>

    @if(! empty($program->study_fields))
        <div style="margin-bottom: 12px;">
            <div style="font-size: 12px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Akademik Alan</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach((array) $program->study_fields as $field)
                    <span style="background: #f4f2ee; color: #1a1a1a; padding: 5px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600;">{{ $field }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @if(! empty($program->subjects))
        <div>
            <div style="font-size: 12px; color: #6b5894; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Konular / Spesifik Alanlar</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach((array) $program->subjects as $sub)
                    <span style="background: #ede9fe; color: #6d28d9; padding: 5px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600;">{{ $sub }}</span>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endif

{{-- Üniversite bilgi (varsa) --}}
@if($program->university)
<div class="sb-card" style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin-bottom: 12px;">🏛 Üniversite</h2>
    <div style="font-size: 16px; color: #1a1a1a; font-weight: 700; margin-bottom: 8px;">{{ $program->university->name }}</div>
    <div style="display: flex; flex-wrap: wrap; gap: 12px; font-size: 13px; color: #6b5894; margin-bottom: 12px;">
        @if($program->university->city) <span>📍 {{ $program->university->city }}</span> @endif
        @if($program->university->state) <span>🗺 {{ $program->university->state }}</span> @endif
        @if($program->university->type) <span>🏛 {{ $program->university->type }}</span> @endif
        @if($program->university->is_public !== null)
            <span>{{ $program->university->is_public ? '🏛 Devlet üniversitesi' : '💼 Özel üniversite' }}</span>
        @endif
    </div>

    {{-- Başvuru tipi rozeti: uni-assist üye mi? --}}
    @if($program->university->is_uni_assist_member)
        <div style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 10px; border-left: 4px solid #d97706;">
            <span style="font-size: 18px; line-height: 1.2;">📨</span>
            <div style="font-size: 12.5px; color: #78350f; line-height: 1.5;">
                <strong style="color: #92400e;">uni-assist üzerinden başvuru</strong> — bu üniversite için VPD (Vorprüfungsdokumentation) gerekli, başvuru ücreti ~75 € ilk + 30 € her ek başvuru. Belge listesi: apostille + yeminli tercüme zorunlu, ~14 belge.
            </div>
        </div>
    @else
        <div style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 10px; border-left: 4px solid #059669;">
            <span style="font-size: 18px; line-height: 1.2;">✅</span>
            <div style="font-size: 12.5px; color: #064e3b; line-height: 1.5;">
                <strong style="color: #065f46;">Direkt başvuru</strong> — üniversite kendi başvuru portalini kullanıyor. Belge listesi daha kısa, uni-assist'e ek ücret yok. Üniversitenin başvuru sayfasından detay kontrol edilmeli.
            </div>
        </div>
    @endif
</div>
@endif

{{-- CTA: MentorDE'ye yönlendir — bu spesifik program için danışmana yönlendirme --}}
<div class="sb-card" style="text-align: center; background: linear-gradient(135deg, rgba(126, 88, 191, 0.06), rgba(167, 126, 217, 0.03));">
    <div style="font-size: 36px; margin-bottom: 8px;">🎯</div>
    <h2 class="sb-title" style="font-size: 22px;">Bu programa başvurmaya hazır mısın?</h2>
    <p class="sb-subtitle" style="font-size: 14px;">
        Danışmanın bu programı senin profilinle birlikte değerlendirip yol haritası hazırlasın.
    </p>
    <a href="{{ route('apply.create') }}?interested_program_id={{ $program->id }}&interested_program={{ urlencode($program->course_name ?? '') }}"
       class="sb-btn sb-btn-primary" style="padding: 14px 32px; font-size: 15px; font-weight: 700;">
        Danışmana Sor
        <span style="font-size: 18px;">→</span>
    </a>
    <div style="margin-top: 12px; font-size: 12px; color: #8a7baf;">
        Profilini hızlıca tanı, kalan süreci birlikte planlayalım — ücretsiz ilk görüşme.
    </div>
</div>

@push('scripts')
<script>
(function(){
    // ── Türkçeye Çevir butonu ──
    var btn = document.getElementById('translateBtn');
    var banner = document.getElementById('translateBanner');
    if (btn) {
        btn.addEventListener('click', function(){
            btn.disabled = true;
            btn.innerHTML = '<span class="sb-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite;margin-right:8px;"></span>Çeviriliyor...';

            fetch('{{ route("program.translate", $program) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
            .then(function(r){ return r.json().then(function(j){ return {ok: r.ok, body: j}; }); })
            .then(function(res){
                if (! res.ok || ! res.body.success) {
                    btn.disabled = false;
                    btn.textContent = '⚠ Çeviri başarısız — tekrar dene';
                    console.error('translate error', res.body);
                    return;
                }
                // Başarı → sayfayı reload (DB güncel TR alanlarla yeniden render)
                btn.innerHTML = '✓ Çevrildi! Yenileniyor...';
                setTimeout(function(){ location.reload(); }, 600);
            })
            .catch(function(e){
                btn.disabled = false;
                btn.textContent = '⚠ Bağlantı hatası — tekrar dene';
            });
        });
    }

    // ── TR/EN dil toggle ──
    document.querySelectorAll('[data-lang-toggle]').forEach(function(group){
        var section = group.closest('[data-section]');
        if (! section) return;
        var trEl = section.querySelector('[data-text-tr]');
        var enEl = section.querySelector('[data-text-en]');

        group.querySelectorAll('.sb-lang-tab').forEach(function(tab){
            tab.addEventListener('click', function(){
                var lang = tab.getAttribute('data-lang');
                group.querySelectorAll('.sb-lang-tab').forEach(function(t){
                    t.classList.toggle('active', t === tab);
                    t.style.background = (t === tab) ? '#7e58bf' : 'transparent';
                    t.style.color = (t === tab) ? '#fff' : '#6b5894';
                });
                if (trEl && enEl) {
                    trEl.style.display = lang === 'tr' ? '' : 'none';
                    enEl.style.display = lang === 'en' ? '' : 'none';
                }
            });
        });
    });
})();
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }

/* Internal-only blok */
.pi-internal { max-width: 880px; margin: 30px auto 0; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 12px; padding: 18px 20px; font-family: ui-monospace, monospace; font-size: 12px; line-height: 1.6; }
.pi-internal-title { font-size: 13px; font-weight: 800; color: #92400e; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 12px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
.pi-internal-grid { display: grid; grid-template-columns: 160px 1fr; gap: 6px 14px; }
.pi-internal-grid dt { color: #92400e; font-weight: 700; }
.pi-internal-grid dd { color: #1e293b; word-break: break-word; margin: 0; }
.pi-internal-sources { margin-top: 12px; padding-top: 12px; border-top: 1px dashed #fbbf24; }
.pi-internal-sources a { color: #5b2e91; text-decoration: underline; }
</style>
@endpush

{{-- Internal-only blok — sadece manager/senior/mentor/admin_staff/operations_* görür --}}
@php
    $internalRoles = ['manager', 'senior', 'mentor', 'admin_staff', 'operations_admin', 'operations_staff', 'system_admin', 'system_staff', 'marketing_admin'];
    $isInternal = auth()->check() && in_array(auth()->user()->role, $internalRoles, true);
@endphp
@if($isInternal)
    <div class="pi-internal">
        <div class="pi-internal-title">🔐 Internal Bilgi (sadece staff görür)</div>
        <dl class="pi-internal-grid">
            <dt>Program UUID</dt><dd>{{ $program->id }}</dd>
            <dt>Quality Score</dt><dd>{{ $program->quality_score }}/100 @if($program->is_manually_curated) <span style="background:#16a34a;color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;">✓ Manuel curated</span>@endif</dd>
            <dt>Active</dt><dd>{{ $program->is_active ? '✓ Aktif' : '✗ Pasif' }}</dd>
            <dt>University ID</dt><dd>{{ $program->university_id ?: '—' }} @if($program->university?->is_uni_assist_member) <span style="background:#3b82f6;color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;">uni-assist üye</span>@endif</dd>
            <dt>uni-assist ID</dt><dd>{{ $program->university?->uni_assist_id ?: '—' }}</dd>
            <dt>Languages (raw)</dt><dd>{{ is_array($program->languages_raw) ? implode(', ', $program->languages_raw) : '—' }}</dd>
            <dt>Study fields</dt><dd>{{ is_array($program->study_fields) ? implode(', ', $program->study_fields) : '—' }}</dd>
            <dt>Subjects</dt><dd>{{ is_array($program->subjects) ? implode(', ', $program->subjects) : '—' }}</dd>
            <dt>Description (TR)</dt><dd>{{ $program->description_tr ? '✓ var (' . mb_strlen($program->description_tr) . ' karakter)' : '✗ yok' }}</dd>
            <dt>Created</dt><dd>{{ $program->created_at?->format('d.m.Y H:i') }}</dd>
            <dt>Updated</dt><dd>{{ $program->updated_at?->format('d.m.Y H:i') }}</dd>
        </dl>
        @if($sources->isNotEmpty())
            <div class="pi-internal-sources">
                <strong style="color:#92400e;">Source Links ({{ $sources->count() }}):</strong>
                <ul style="margin:6px 0 0 18px; padding:0;">
                    @foreach($sources as $s)
                        <li>
                            <strong>{{ $s->source }}</strong>
                            (ext_id: {{ $s->external_id ?? '—' }})
                            @if($s->source_url) — <a href="{{ $s->source_url }}" target="_blank" rel="noopener">{{ Str::limit($s->source_url, 60) }}</a>@endif
                            @if($s->is_primary) <span style="background:#16a34a;color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;">PRIMARY</span>@endif
                            <span style="color:#64748b; font-size:11px;">son sync: {{ $s->last_synced_at?->format('d.m.Y') ?? '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div style="margin-top:12px; padding-top:10px; border-top:1px dashed #fbbf24; display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('program-search') }}" style="padding:6px 12px; background:#5b2e91; color:#fff; text-decoration:none; border-radius:6px; font-size:11.5px; font-weight:600;">← Program Aramaya Dön</a>
            @if($program->university && $program->university->id)
                <a href="{{ route('program-search', ['q' => $program->university_name_cached]) }}" style="padding:6px 12px; background:#fff; color:#5b2e91; text-decoration:none; border-radius:6px; font-size:11.5px; font-weight:600; border:1px solid #5b2e91;">🏛 Aynı üniversiteden diğer programlar</a>
            @endif
        </div>
    </div>
@endif

@endsection

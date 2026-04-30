@extends('study-buddy.layout')

@section('title', $program->course_name . ' — ' . $program->university_name_cached)

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

{{-- Description --}}
@if($program->description)
<div class="sb-card" style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin-bottom: 12px;">📋 Program Hakkında</h2>
    <div style="font-size: 14px; color: #1a1a1a; line-height: 1.7; white-space: pre-line;">{{ $program->description }}</div>
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
<div class="sb-card" style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin-bottom: 12px;">✅ Giriş / Kabul Koşulları</h2>
    <div style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line;">{{ $program->qualification_requirements }}</div>
</div>
@endif

{{-- Dil Gereksinimleri --}}
@if($program->language_requirements)
<div class="sb-card" style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin-bottom: 12px;">🗣 Dil Gereksinimleri</h2>
    <div style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line;">{{ $program->language_requirements }}</div>
</div>
@endif

{{-- Gereken Belgeler --}}
@if($program->required_documents)
<div class="sb-card" style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; color: #7e58bf; font-weight: 700; margin-bottom: 12px;">📎 Başvuru için Gereken Belgeler</h2>
    <div style="font-size: 13.5px; color: #1a1a1a; line-height: 1.7; white-space: pre-line;">{{ $program->required_documents }}</div>
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
    <div style="display: flex; flex-wrap: wrap; gap: 12px; font-size: 13px; color: #6b5894;">
        @if($program->university->city) <span>📍 {{ $program->university->city }}</span> @endif
        @if($program->university->state) <span>🗺 {{ $program->university->state }}</span> @endif
        @if($program->university->type) <span>🏛 {{ $program->university->type }}</span> @endif
        @if($program->university->is_public !== null)
            <span>{{ $program->university->is_public ? '🏛 Devlet üniversitesi' : '💼 Özel üniversite' }}</span>
        @endif
    </div>
</div>
@endif

{{-- CTA: MentorDE'ye yönlendir --}}
<div class="sb-card" style="text-align: center; background: linear-gradient(135deg, rgba(126, 88, 191, 0.06), rgba(167, 126, 217, 0.03));">
    <div style="font-size: 36px; margin-bottom: 8px;">🎯</div>
    <h2 class="sb-title" style="font-size: 22px;">Bu programa başvurmaya hazır mısın?</h2>
    <p class="sb-subtitle" style="font-size: 14px;">Danışmanın bu programı senin profilinle birlikte değerlendirip yol haritası hazırlasın.</p>
    <a href="/study-buddy/start" class="sb-btn sb-btn-primary" style="padding: 14px 32px; font-size: 15px; font-weight: 700;">
        Sihirbazla Başla
        <span style="font-size: 18px;">→</span>
    </a>
</div>
@endsection

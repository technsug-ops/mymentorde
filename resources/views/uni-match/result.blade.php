@extends('uni-match.layout')

@section('og_title', 'UniMatch ile sana özel ' . count($recommendations) . ' Almanya programı seçtim')
@section('og_description', 'MentorDE UniMatch sihirbazı, 13.000+ program arasından profil ve hedeflerime en uygun olanları sıraladı. Sen de dene → /uni-match')

@section('title', 'Sana özel program önerileri — UniMatch')

@section('content')
<div class="sb-progress-wrap">
    <div class="sb-progress-meta">
        <span>✓ Tamamlandı</span>
        <span>%100</span>
    </div>
    <div class="sb-progress-bar">
        <div class="sb-progress-fill" style="width: 100%;"></div>
    </div>
</div>

<div class="sb-card" style="text-align: center; margin-bottom: 16px;">
    <div style="font-size: 48px; margin-bottom: 8px;">🎯</div>
    <h1 class="sb-title">Senin için {{ count($recommendations) }} program seçtik</h1>
    <p class="sb-subtitle">Cevaplarına göre 13.000+ program arasından en uyumlu olanları sıraladık.</p>
</div>

@if(count($recommendations) === 0)
    <div class="sb-card" style="text-align: center;">
        <p style="color: #6b5894; font-size: 14px;">Cevaplarına tam uyan program bulunamadı. Filtreleri biraz genişletmek için cevaplarını tekrar gözden geçirelim.</p>
        <div style="margin-top: 20px;">
            <a href="{{ route('uni-match.start') }}" class="sb-btn sb-btn-primary">Yeniden Başla</a>
        </div>
    </div>
@else
    @foreach($recommendations as $i => $rec)
        <div class="sb-card" style="margin-bottom: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <div style="display: inline-block; background: rgba(126, 88, 191, 0.12); color: #7e58bf; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; margin-bottom: 8px; letter-spacing: .3px;">
                        #{{ $i + 1 }} ÖNERİ
                    </div>
                    <h2 style="font-size: 19px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; line-height: 1.3;">
                        {{ $rec['course_name'] ?? '?' }}
                    </h2>
                    <div style="font-size: 13.5px; color: #6b5894; margin-bottom: 8px;">
                        {{ $rec['university_name'] ?? '?' }}
                        @if(! empty($rec['location'])) · {{ $rec['location'] }} @endif
                    </div>
                </div>
                <div style="text-align: center; flex-shrink: 0;">
                    <div style="font-size: 28px; font-weight: 700; color: #7e58bf; line-height: 1;">{{ $rec['match_score'] }}</div>
                    <div style="font-size: 10px; color: #8a7baf; margin-top: 2px;">/100 MATCH</div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                @if(! empty($rec['degree_specification']))
                    <span style="background: #f4f2ee; color: #1a1a1a; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;">{{ $rec['degree_specification'] }}</span>
                @endif
                @foreach(($rec['languages_raw'] ?? []) as $lang)
                    <span style="background: #ede9fe; color: #6d28d9; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;">{{ $lang }}</span>
                @endforeach
                @if(($rec['tuition_eur'] ?? null) !== null)
                    @if((int) $rec['tuition_eur'] === 0)
                        <span style="background: rgba(22,163,74,0.12); color: #15803d; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;">✓ Ücretsiz</span>
                    @else
                        <span style="background: #fef9c3; color: #854d0e; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;">{{ (int) $rec['tuition_eur'] }} €/sömestr</span>
                    @endif
                @endif
                @if(! empty($rec['duration_semesters']))
                    <span style="background: #f4f2ee; color: #1a1a1a; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;">{{ $rec['duration_semesters'] }} sömestr</span>
                @endif
                @if(! empty($rec['is_uni_assist_member']))
                    <span title="uni-assist üzerinden başvuru — VPD + apostille gerekli, ~14 belge"
                          style="background: rgba(217,119,6,0.12); color: #92400e; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;">📨 uni-assist başvuru</span>
                @else
                    <span title="Üniversite kendi portali — daha az belge"
                          style="background: rgba(5,150,105,0.12); color: #065f46; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;">✅ Direkt başvuru</span>
                @endif
            </div>

            @if(! empty($rec['reasons']))
                <div style="font-size: 12.5px; color: #6b5894; line-height: 1.7; padding-top: 10px; border-top: 1px solid #f0ecf6;">
                    @foreach($rec['reasons'] as $reason)
                        <div>· {{ $reason }}</div>
                    @endforeach
                </div>
            @endif

            <div style="margin-top: 12px; text-align: right;">
                <a href="{{ route('program.show', ['program' => $rec['program_id']]) }}" target="_blank"
                   style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: rgba(126, 88, 191, 0.08); color: #7e58bf; border-radius: 8px; font-size: 12.5px; font-weight: 600; text-decoration: none;">
                    Detayları gör
                    <span style="font-size: 14px;">→</span>
                </a>
            </div>
        </div>
    @endforeach

    {{-- PDF indirme bandı --}}
    <div style="margin: 20px 0; padding: 14px 18px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 10px; border-left: 4px solid #d97706; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
        <div style="font-size: 26px;">📄</div>
        <div style="flex: 1; min-width: 200px;">
            <div style="font-size: 14px; font-weight: 700; color: #78350f;">Sonuçlarımı PDF olarak indir</div>
            <div style="font-size: 12px; color: #92400e; margin-top: 2px;">Tüm 10 program + profilin + neden uyduğu — paylaşıma hazır PDF</div>
        </div>
        <a href="{{ route('uni-match.result.pdf') }}"
           style="background: #92400e; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px;">
            PDF İndir →
        </a>
    </div>

    <div class="sb-card" style="margin-top: 24px; text-align: center; background: linear-gradient(135deg, rgba(126, 88, 191, 0.06), rgba(167, 126, 217, 0.03));">
        <div style="font-size: 32px; margin-bottom: 8px;">🚀</div>
        <h2 class="sb-title">Hadi adım atalım</h2>
        <p class="sb-subtitle">MentorDE'ye kayıt ol, danışmanın bu programlardan hangisinin sana en uygun olduğunu birlikte değerlendirin. Cevapların form'a otomatik aktarılacak — sadece kalan bilgileri tamamlarsın.</p>
        <form method="POST" action="{{ route('uni-match.convert') }}">
            @csrf
            <button type="submit" class="sb-btn sb-btn-primary" style="padding: 16px 36px; font-size: 16px; font-weight: 700;">
                Şimdi Kayıt Ol & Danışmanla Görüş
                <span style="font-size: 18px;">→</span>
            </button>
        </form>
        <div style="margin-top: 14px; font-size: 12px; color: #8a7baf;">
            Wizard cevapların kaydedildi — istediğin zaman bu sayfaya geri dönebilirsin.
        </div>
    </div>
@endif
@endsection

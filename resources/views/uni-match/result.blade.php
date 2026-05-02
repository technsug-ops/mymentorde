@extends('uni-match.layout')

@section('og_title', 'UniMatch ile sana özel ' . count($recommendations) . ' Almanya programı seçtim')
@section('og_description', 'MentorDE UniMatch sihirbazı, 13.000+ program arasından profil ve hedeflerime en uygun olanları sıraladı. Sen de dene → /uni-match')

@push('scripts')
<style>
.fav-btn:hover { color:#a07ed9 !important; transform:scale(1.15); }
.fav-btn.is-fav { color:#f59e0b !important; }
.fav-btn.is-fav:hover { color:#d97706 !important; }
.fav-toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#1a1a1a; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; z-index:9999; box-shadow:0 8px 24px rgba(0,0,0,.25); opacity:0; transition:opacity .3s; pointer-events:none; }
.fav-toast.show { opacity:1; }
.fav-toast.error { background:#dc2626; }
</style>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var toast = function(msg, isErr){
        var el = document.createElement('div');
        el.className = 'fav-toast' + (isErr ? ' error' : '');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function(){ el.classList.add('show'); }, 50);
        setTimeout(function(){ el.classList.remove('show'); setTimeout(function(){ el.remove(); }, 300); }, 2400);
    };

    document.querySelectorAll('[data-favorite-toggle]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var pid = btn.dataset.programId;
            fetch('{{ route("uni-match.favorite.toggle") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ program_id: pid })
            }).then(function(r){ return r.json().then(function(d){ return { ok: r.ok, data: d }; }); })
            .then(function(res){
                if (! res.ok) {
                    toast(res.data.message || 'Favorilere eklenemedi', true);
                    return;
                }
                if (res.data.action === 'added') {
                    btn.classList.add('is-fav');
                    toast('⭐ Favorilere eklendi (' + res.data.count + '/3)');
                } else {
                    btn.classList.remove('is-fav');
                    toast('✓ Favorilerden kaldırıldı');
                }
            }).catch(function(){ toast('Bir hata oldu, tekrar dene', true); });
        });
    });

    // Filter buttons
    var favSet = new Set({!! json_encode((array) ($response->favorite_program_ids ?? [])) !!});
    document.querySelectorAll('[data-filter]').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.querySelectorAll('[data-filter]').forEach(function(b){
                b.style.background = '#fff'; b.style.color = '#6b5894';
                b.classList.remove('active');
            });
            btn.style.background = '#7e58bf'; btn.style.color = '#fff';
            btn.classList.add('active');

            var f = btn.dataset.filter;
            var visibleCount = 0;
            document.querySelectorAll('.sb-rec-card').forEach(function(card){
                var show = true;
                if (f === 'uni-assist') show = card.dataset.sourceType === 'uni-assist';
                else if (f === 'direkt') show = card.dataset.sourceType === 'direkt';
                else if (f === 'favorite') show = favSet.has(card.dataset.programId);
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            if (visibleCount === 0 && f === 'favorite') {
                toast('Henüz favorin yok — bir programa yıldız ekle', true);
                document.querySelector('[data-filter="all"]').click();
            }
        });
    });
})();
</script>
@endpush

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
    {{-- Filter / Sort kontrolleri --}}
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:8px 0 16px;padding:10px 14px;background:#fff;border-radius:10px;border:1px solid #ede5f7;">
        <span style="font-size:12px;color:#6b5894;font-weight:600;">FİLTRE:</span>
        <button type="button" data-filter="all" class="sb-filter active"
                style="padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid #d4c5e8;background:#7e58bf;color:#fff;cursor:pointer;">Tümü ({{ count($recommendations) }})</button>
        <button type="button" data-filter="uni-assist" class="sb-filter"
                style="padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid #d4c5e8;background:#fff;color:#6b5894;cursor:pointer;">📨 uni-assist</button>
        <button type="button" data-filter="direkt" class="sb-filter"
                style="padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid #d4c5e8;background:#fff;color:#6b5894;cursor:pointer;">✅ Direkt</button>
        <button type="button" data-filter="favorite" class="sb-filter"
                style="padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid #d4c5e8;background:#fff;color:#6b5894;cursor:pointer;">⭐ Favorilerim</button>
    </div>

    @foreach($recommendations as $i => $rec)
        <div class="sb-card sb-rec-card"
             data-source-type="{{ ! empty($rec['is_uni_assist_member']) ? 'uni-assist' : 'direkt' }}"
             data-program-id="{{ $rec['program_id'] }}"
             style="margin-bottom: 14px;">
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
                <div style="text-align: center; flex-shrink: 0; display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <button type="button"
                            data-favorite-toggle
                            data-program-id="{{ $rec['program_id'] }}"
                            class="fav-btn {{ in_array($rec['program_id'], (array) ($response->favorite_program_ids ?? []), true) ? 'is-fav' : '' }}"
                            title="Favorile (max 3)"
                            style="background:none;border:none;cursor:pointer;font-size:22px;line-height:1;padding:0;color:#d4c5e8;transition:transform .15s,color .15s;">★</button>
                    <div>
                        <div style="font-size: 28px; font-weight: 700; color: #7e58bf; line-height: 1;">{{ $rec['match_score'] }}</div>
                        <div style="font-size: 10px; color: #8a7baf; margin-top: 2px;">/100 MATCH</div>
                    </div>
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

    {{-- Sosyal proof (son 7 gün) --}}
    @if(($socialProof ?? 0) >= 5)
    <div style="text-align:center;margin:20px 0;padding:12px 18px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;">
        <div style="font-size:13px;color:#166534;font-weight:600;">
            <span style="display:inline-block;width:8px;height:8px;background:#16a34a;border-radius:50%;animation:pulse 1.5s infinite;margin-right:6px;vertical-align:middle;"></span>
            Son 7 günde <strong style="color:#15803d;">{{ number_format($socialProof) }}</strong> öğrenci UniMatch'ı tamamladı
        </div>
    </div>
    <style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
    @endif

    {{-- Sosyal paylaşım --}}
    @php
        $shareText = "🎯 MentorDE UniMatch sihirbazı bana özel " . count($recommendations) . " Almanya programı seçti! Sen de dene:";
        $shareUrl = url('/uni-match');
    @endphp
    <div style="margin: 20px 0; padding: 14px 18px; background: #f9f6fc; border-radius: 10px; border-left: 4px solid #7e58bf;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
            <div style="flex:1;min-width:200px;">
                <div style="font-size: 14px; font-weight: 700; color: #6b5894;">📢 Bunu paylaş</div>
                <div style="font-size: 12px; color: #8a7baf; margin-top: 2px;">Almanya'ya gitmek isteyen arkadaşların da denemeli</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="https://wa.me/?text={{ urlencode($shareText . ' ' . $shareUrl) }}"
                   target="_blank" rel="noopener"
                   style="background:#25d366;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    💬 WhatsApp
                </a>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($shareText) }}&url={{ urlencode($shareUrl) }}"
                   target="_blank" rel="noopener"
                   style="background:#000;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    𝕏 Twitter
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"
                   target="_blank" rel="noopener"
                   style="background:#0a66c2;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    💼 LinkedIn
                </a>
                <a href="mailto:?subject={{ urlencode('UniMatch — Almanya programı bul') }}&body={{ urlencode($shareText . ' ' . $shareUrl) }}"
                   style="background:#7e58bf;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    ✉️ E-posta
                </a>
            </div>
        </div>
    </div>

    {{-- PDF indirme bandı --}}
    @php $favCount = count((array) ($response->favorite_program_ids ?? [])); @endphp
    <div style="margin: 20px 0; padding: 14px 18px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 10px; border-left: 4px solid #d97706;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div style="font-size: 26px;">📄</div>
            <div style="flex: 1; min-width: 200px;">
                <div style="font-size: 14px; font-weight: 700; color: #78350f;">Sonuçlarımı PDF olarak indir</div>
                <div style="font-size: 12px; color: #92400e; margin-top: 2px;">Tüm {{ count($recommendations) }} program + profilin — paylaşıma hazır</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('uni-match.result.pdf') }}"
                   style="background: #92400e; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px;">
                    Tümünü İndir →
                </a>
                @if($favCount > 0)
                <a href="{{ route('uni-match.result.pdf') }}?favorites=1"
                   style="background: #f59e0b; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px;">
                    ⭐ {{ $favCount }} Favorimi İndir
                </a>
                @endif
            </div>
        </div>
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

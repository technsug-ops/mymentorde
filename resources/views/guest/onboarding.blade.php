@extends('guest.layouts.app')

@section('title', 'Hoş Geldiniz — Başlangıç Rehberi')
@section('page_title', 'Başlangıç Rehberi')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.ob-wrap { max-width: 680px; margin: 0 auto; }
.ob-progress-bar { height: 6px; background: var(--u-line); border-radius: 999px; overflow: hidden; margin-bottom: 24px; }
.ob-progress-fill { height: 100%; background: linear-gradient(90deg, var(--u-brand), #60a5fa); border-radius: 999px; transition: width .5s ease; }
.ob-steps { display: flex; gap: 0; margin-bottom: 28px; border-bottom: 1px solid var(--u-line); padding-bottom: 16px; overflow-x: auto; }
.ob-step { flex: 1; min-width: 80px; display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 0 4px; position: relative; }
.ob-step:not(:last-child)::after { content: ''; position: absolute; top: 15px; left: calc(50% + 16px); width: calc(100% - 32px); height: 2px; background: var(--u-line); }
.ob-step.done:not(:last-child)::after { background: var(--u-ok); }
.ob-step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 15px; border: 2px solid var(--u-line); background: var(--u-card); flex-shrink: 0; }
.ob-step.done .ob-step-dot { background: var(--u-ok); border-color: var(--u-ok); color: #fff; }
.ob-step.active .ob-step-dot { border-color: var(--u-brand); background: #eff6ff; color: var(--u-brand); animation: obpulse 2s infinite; }
.ob-step-label { font-size: 10px; color: var(--u-muted); text-align: center; line-height: 1.3; }
.ob-step.active .ob-step-label { color: var(--u-brand); font-weight: 700; }
.ob-step.done .ob-step-label { color: var(--u-ok); }
@keyframes obpulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.1)} }

.ob-card { background: var(--u-card); border: 1px solid var(--u-line); border-radius: 16px; padding: 28px; margin-bottom: 16px; }
.ob-card-icon { font-size: 48px; text-align: center; margin-bottom: 12px; }
.ob-card-title { font-size: 22px; font-weight: 800; color: var(--u-text); text-align: center; margin-bottom: 8px; }
.ob-card-desc { font-size: 14px; color: var(--u-muted); text-align: center; line-height: 1.7; margin-bottom: 24px; }
.ob-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
.ob-skip { background: none; border: 1px solid var(--u-line); color: var(--u-muted); border-radius: 8px; padding: 10px 20px; font-size: 13px; cursor: pointer; font-family: inherit; }
.ob-skip:hover { background: var(--u-bg); }

/* ── Minimalist overrides ── */
.jm-minimalist .ob-progress-fill { background: var(--u-brand, #111) !important; }
.jm-minimalist .ob-step.active .ob-step-dot { animation: none !important; }
</style>
@endpush

@section('content')
<div class="ob-wrap">

    {{-- Progress --}}
    <div class="ob-progress-bar">
        <div class="ob-progress-fill" id="obProgressFill"
             style="width:{{ $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0 }}%"></div>
    </div>

    {{-- Step dots --}}
    <div class="ob-steps">
        @foreach($stepOrder as $code)
            @php
                $s = $steps[$code] ?? null;
                $isDone   = $s && $s->completed_at;
                $isSkipped= $s && $s->skipped_at;
                $isActive = ($code === $currentStep);
                $cls = $isDone || $isSkipped ? 'done' : ($isActive ? 'active' : '');
            @endphp
            <div class="ob-step {{ $cls }}">
                <div class="ob-step-dot">
                    @if($isDone || $isSkipped) ✓
                    @else {{ $stepIcons[$code] ?? '⬜' }}
                    @endif
                </div>
                <div class="ob-step-label">{{ $stepLabels[$code] ?? $code }}</div>
            </div>
        @endforeach
    </div>

    {{-- All done celebration --}}
    @if(!$currentStep && $completedSteps >= $totalSteps && $totalSteps > 0)
    <div class="ob-card" style="text-align:center;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-color:#86efac;">
        <div style="font-size:64px;margin-bottom:12px;animation:obpulse 1.5s 3;">🎉</div>
        <div class="ob-card-title" style="color:#15803d;">Tebrikler! Hazırsınız!</div>
        <div style="font-size:var(--tx-sm);color:#166534;margin-bottom:20px;line-height:1.7;">
            Tüm başlangıç adımlarını tamamladınız.<br>
            Danışmanınız en kısa sürede sizinle iletişime geçecek.
        </div>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:20px;">
            <a href="{{ route('guest.dashboard') }}" class="btn ok">Dashboard'a Dön →</a>
            <a href="{{ route('guest.messages') }}" class="btn alt">💬 Mesaj Gönder</a>
        </div>
        <div style="background:#fff;border-radius:10px;padding:12px 16px;display:inline-flex;gap:24px;flex-wrap:wrap;justify-content:center;">
            @foreach([['📄','Belgeler',route('guest.registration.documents')],['📅','Randevu',route('guest.appointments')],['🎓','Rehber',route('guest.university-guide')]] as [$ic,$lbl,$href])
            <a href="{{ $href }}" style="display:flex;flex-direction:column;align-items:center;gap:4px;text-decoration:none;color:var(--u-text);">
                <span style="font-size:var(--tx-xl);">{{ $ic }}</span>
                <span style="font-size:var(--tx-xs);font-weight:600;">{{ $lbl }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Current step card --}}
    @if($currentStep)
    <div class="ob-card" id="obCard">

        {{-- WELCOME --}}
        @if($currentStep === 'welcome')
        <div class="ob-card-icon">🎉</div>
        <div class="ob-card-title">{{ config('brand.name', 'MentorDE') }}'ye Hoş Geldiniz!</div>
        <div class="ob-card-desc">
            Almanya'da eğitim yolculuğunuz burada başlıyor.<br>
            Sistemi tanımanıza yardımcı olacak birkaç hızlı adım var.<br>
            <strong>Sadece 5 dakikanızı alacak!</strong>
        </div>
        <div style="background:#f8fafd;border-radius:12px;padding:16px;margin-bottom:24px;">
            <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);margin-bottom:10px;">Almanya'da eğitimin 5 avantajı:</div>
            <ol style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:6px;">
                <li style="font-size:var(--tx-sm);color:var(--u-muted);">Dünya genelinde tanınan kaliteli üniversiteler</li>
                <li style="font-size:var(--tx-sm);color:var(--u-muted);">Büyük çoğunluğu ücretsiz veya düşük ücretli eğitim</li>
                <li style="font-size:var(--tx-sm);color:var(--u-muted);">Mezuniyet sonrası 18 aylık iş arama vizesi</li>
                <li style="font-size:var(--tx-sm);color:var(--u-muted);">Çalışma izniyle part-time iş imkânı</li>
                <li style="font-size:var(--tx-sm);color:var(--u-muted);">Avrupa'nın kalbinde yaşam ve seyahat özgürlüğü</li>
            </ol>
        </div>
        <div class="ob-actions">
            <button class="btn ok" onclick="obComplete('welcome')">Anladım, Devam Et →</button>
            <button class="ob-skip" onclick="obSkip('welcome')">Atla</button>
        </div>

        {{-- PROFILE --}}
        @elseif($currentStep === 'profile')
        <div class="ob-card-icon">👤</div>
        <div class="ob-card-title">Profilini Tamamla</div>
        <div class="ob-card-desc">
            Danışmanınızın sizi daha iyi tanıması için temel bilgilerinizi doldurun.<br>
            Profil bilgileri başvuru sürecinizi hızlandırır.
        </div>
        <div style="background:#f8fafd;border-radius:12px;padding:14px 16px;margin-bottom:20px;display:flex;flex-direction:column;gap:8px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-text);margin-bottom:2px;">💡 Hangi bilgiler önemli?</div>
            @foreach([
                ['🎓','Hedef bölüm ve üniversite tercihleriniz'],
                ['📅','Almanya\'ya gitmek istediğiniz dönem (WS/SS)'],
                ['🌐','Almanca / İngilizce dil seviyeniz'],
                ['🏠','Yurt dışı deneyiminiz varsa ekleyin'],
            ] as [$ic,$tip])
            <div style="font-size:var(--tx-xs);color:var(--u-muted);display:flex;gap:8px;align-items:flex-start;">
                <span style="flex-shrink:0;">{{ $ic }}</span><span>{{ $tip }}</span>
            </div>
            @endforeach
        </div>
        <div class="ob-actions">
            <a href="{{ route('guest.profile') }}" class="btn ok" onclick="obComplete('profile')">Profile Git →</a>
            <button class="ob-skip" onclick="obSkip('profile')">Daha Sonra</button>
        </div>

        {{-- MEET SENIOR --}}
        @elseif($currentStep === 'meet_senior')
        <div class="ob-card-icon">🤝</div>
        <div class="ob-card-title">Danışmanını Tanı</div>
        <div class="ob-card-desc">
            @if($assignedSenior)
                <strong>{{ $assignedSenior->name }}</strong> sizin danışmanınız.<br>
                İlk mesajınızı göndererek süreci başlatın!
            @else
                Kısa süre içinde bir danışman size atanacak.<br>
                Mesaj kutunuzu takip edin.
            @endif
        </div>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 16px;margin-bottom:20px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:#166534;margin-bottom:8px;">📬 İlk mesajda ne sorasınız?</div>
            @foreach([
                'Hangi üniversiteler profil için uygun?',
                'Dil sertifikam (IELTS/TestDaF) yoksa ne yapmalıyım?',
                'Başvuru takvimi ve öncelikli adımlar neler?',
            ] as $q)
            <div style="font-size:var(--tx-xs);color:#166534;padding:4px 0;border-bottom:1px solid #dcfce7;display:flex;gap:6px;">
                <span style="flex-shrink:0;">→</span><em>"{{ $q }}"</em>
            </div>
            @endforeach
        </div>
        <div class="ob-actions">
            <a href="{{ route('guest.messages') }}" class="btn ok" onclick="obComplete('meet_senior')">Mesaj Gönder →</a>
            <button class="ob-skip" onclick="obSkip('meet_senior')">Daha Sonra</button>
        </div>

        {{-- FIRST DOCS --}}
        @elseif($currentStep === 'first_docs')
        <div class="ob-card-icon">📄</div>
        <div class="ob-card-title">İlk Belgeni Yükle</div>
        <div class="ob-card-desc">
            Başlamak için yalnızca iki belge yeterli:<br>
            <strong>Pasaport</strong> ve <strong>Kimlik Kartı</strong>.<br>
            Diğer belgeler daha sonra eklenebilir.
        </div>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;margin-bottom:20px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:#92400e;margin-bottom:8px;">⚠️ Belge yüklerken dikkat</div>
            @foreach([
                ['✓','Net, okunabilir tarama veya fotoğraf kullanın'],
                ['✓','PDF veya JPG formatı tercih edilir'],
                ['✓','Pasaport biyometri sayfasının tamamı görünmeli'],
                ['✗','Bulanık veya kırpılmış görseller reddedilebilir'],
            ] as [$ic,$tip])
            <div style="font-size:var(--tx-xs);color:#92400e;display:flex;gap:8px;margin-bottom:4px;">
                <span style="flex-shrink:0;font-weight:700;">{{ $ic }}</span><span>{{ $tip }}</span>
            </div>
            @endforeach
        </div>
        <div class="ob-actions">
            <a href="{{ route('guest.registration.documents') }}" class="btn ok" onclick="obComplete('first_docs')">Belgelere Git →</a>
            <button class="ob-skip" onclick="obSkip('first_docs')">Daha Sonra</button>
        </div>

        {{-- EXPLORE --}}
        @elseif($currentStep === 'explore')
        <div class="ob-card-icon">📦</div>
        <div class="ob-card-title">Paketleri İncele</div>
        <div class="ob-card-desc">
            Hedeflerinize uygun paketi seçin.<br>
            Basic, Plus veya Premium — hangisi size uygun?
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:20px;">
            @foreach([
                ['Basic','Temel danışmanlık ve belge takibi','info'],
                ['Plus','Üniversite önerileri + CV desteği','ok'],
                ['Premium','Tam kapsamlı süreç yönetimi','warn'],
            ] as [$name,$desc,$badge])
            <div style="border:1px solid var(--u-line);border-radius:10px;padding:10px;text-align:center;">
                <span class="badge {{ $badge }}" style="display:block;margin:0 auto 6px;">{{ $name }}</span>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.4;">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
        <div class="ob-actions">
            <a href="{{ route('guest.services') }}" class="btn ok" onclick="obComplete('explore')">Paketlere Git →</a>
            <button class="ob-skip" onclick="obSkip('explore')">Atla</button>
        </div>
        @endif

    </div>
    @endif

    <div style="text-align:center;margin-top:8px;">
        <a href="{{ route('guest.dashboard') }}" style="font-size:var(--tx-xs);color:var(--u-muted);">
            Sonra tamamlayacağım — Dashboard'a git →
        </a>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function obPost(url, cb) {
        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.ok) {
                if (d.done >= d.total) {
                    window.location.href = '{{ route("guest.dashboard") }}';
                } else {
                    window.location.href = '{{ route("guest.onboarding") }}';
                }
            }
        }).catch(function () { if (cb) cb(); });
    }

    window.obComplete = function (code) {
        obPost('/guest/onboarding/' + code + '/complete');
    };

    window.obSkip = function (code) {
        obPost('/guest/onboarding/' + code + '/skip');
    };
}());
</script>
@endpush
@endsection

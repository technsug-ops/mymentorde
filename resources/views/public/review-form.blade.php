<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Görüşmeni Değerlendir · MentörDE</title>
    @include('partials.favicon')
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">

    <style nonce="{{ $cspNonce ?? '' }}">
        :root {
            --primary:#7e58bf;
            --primary-dark:#6c47a8;
            --primary-deep:#5a3a8d;
            --primary-soft:#efe9fb;
            --text:#1a1325;
            --muted:#6b6377;
            --line:#e3dcec;
            --bg:#faf9f5;
            --gold:#f5b400;
            --gold-soft:#fff6dd;
            --success-bg:#e8f5ed;
            --success-text:#2d8b55;
            --error-bg:#fee2e2;
            --error-text:#991b1b;
            --font-base:"Space Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing:border-box; }
        html, body { margin:0; padding:0; }
        body {
            font-family:var(--font-base); color:var(--text);
            background:linear-gradient(160deg, #f7f3ff 0%, #faf9f5 55%, #f1edfb 100%);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding:30px 20px;
        }
        .rf-card {
            background:#fff; border-radius:18px; border:1px solid var(--line);
            box-shadow:0 10px 40px rgba(126,88,191,.12);
            max-width:560px; width:100%; padding:36px;
        }
        .rf-logo { font-size:22px; font-weight:700; color:var(--primary); text-align:center; margin-bottom:6px; letter-spacing:-.5px; }
        .rf-logo span { color:var(--primary-deep); font-style:italic; }
        .rf-h1 { font-size:24px; font-weight:700; margin:0 0 6px; text-align:center; letter-spacing:-.02em; }
        .rf-sub { color:var(--muted); font-size:14px; text-align:center; margin:0 0 22px; }

        .rf-booking-box {
            background:var(--primary-soft); border-radius:12px;
            padding:14px 18px; margin-bottom:24px; display:flex; align-items:center; gap:14px;
        }
        .rf-bb-avatar {
            width:48px; height:48px; border-radius:50%;
            background:linear-gradient(135deg, var(--primary), var(--primary-deep));
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-weight:700; font-size:18px; flex-shrink:0;
        }
        .rf-bb-name { font-weight:600; font-size:14.5px; color:var(--text); }
        .rf-bb-meta { font-size:12.5px; color:var(--muted); margin-top:2px; }

        /* Star rating selector */
        .rf-group { margin-bottom:18px; }
        .rf-label { display:block; font-weight:600; font-size:13.5px; color:var(--text); margin-bottom:8px; }
        .rf-stars {
            display:inline-flex; gap:6px; padding:14px 18px;
            background:var(--gold-soft); border-radius:12px; border:1px solid #f6e3a3;
        }
        .rf-stars button {
            width:42px; height:42px; border:0; background:transparent; cursor:pointer;
            color:#d4cfba; transition:.15s; display:flex; align-items:center; justify-content:center;
            padding:0;
        }
        .rf-stars button.active,
        .rf-stars button:hover { color:var(--gold); transform:scale(1.1); }
        .rf-stars svg { width:32px; height:32px; }
        .rf-rating-text {
            margin-left:14px; display:inline-block; vertical-align:middle;
            font-weight:600; color:var(--text); font-size:14px;
        }

        .rf-input, .rf-textarea {
            width:100%; padding:12px 14px; border:1.5px solid var(--line);
            border-radius:10px; font-family:inherit; font-size:14px; color:var(--text);
            background:#fff; transition:.2s; resize:vertical;
        }
        .rf-input:focus, .rf-textarea:focus {
            outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-soft);
        }
        .rf-textarea { min-height:120px; }
        .rf-hint { color:var(--muted); font-size:12px; margin-top:4px; }

        .rf-btn {
            display:flex; align-items:center; justify-content:center; gap:8px;
            width:100%; background:var(--primary); color:#fff;
            padding:14px 22px; border-radius:10px; font-weight:600; font-size:15px;
            border:0; cursor:pointer; transition:.2s; margin-top:6px;
        }
        .rf-btn:hover { background:var(--primary-dark); }
        .rf-btn:disabled { opacity:.5; cursor:not-allowed; }

        .rf-err {
            background:var(--error-bg); color:var(--error-text);
            padding:12px 14px; border-radius:8px; font-size:13.5px;
            margin-bottom:16px;
        }
        .rf-success {
            background:var(--success-bg); color:var(--success-text);
            padding:24px; border-radius:12px; text-align:center;
        }
        .rf-success-icon {
            width:64px; height:64px; border-radius:50%;
            background:#fff; color:var(--success-text);
            display:inline-flex; align-items:center; justify-content:center;
            margin-bottom:14px; font-size:32px; font-weight:700;
        }
        .rf-state-h { font-size:22px; font-weight:700; margin:0 0 8px; }
        .rf-state-p { margin:0 0 18px; font-size:14.5px; line-height:1.6; }
        .rf-state-link {
            display:inline-block; background:#fff; color:var(--primary);
            padding:10px 20px; border-radius:8px; font-weight:600; font-size:13.5px;
        }
        .rf-state-link:hover { background:var(--primary-soft); text-decoration:none; }

        .rf-footer { margin-top:24px; text-align:center; color:var(--muted); font-size:12px; }
    </style>
</head>
<body>

<div class="rf-card">
    <div class="rf-logo">mentör<span>DE</span></div>

    @php
        $state = $state ?? null;
        $whenStr = optional($booking->starts_at)->format('d.m.Y H:i');
        $seniorName = $setting?->display_name ?: ($senior?->name ?? 'Mentörde Uzmanı');
    @endphp

    @if($state === 'success')
        <div class="rf-success">
            <div class="rf-success-icon">✓</div>
            <h1 class="rf-state-h">Teşekkürler!</h1>
            <p class="rf-state-p">Değerlendirmen kaydedildi. Geri bildiriminin diğer adaylara faydası büyük.</p>
            @if($profileUrl)
                <a href="{{ $profileUrl }}" class="rf-state-link">{{ $seniorName }} Profilini Gör</a>
            @endif
        </div>
        <div class="rf-footer">© {{ date('Y') }} MentörDE · @include('partials.vendor-credit')</div>

    @elseif($state === 'not_eligible')
        <h1 class="rf-h1">Görüşme bulunamadı</h1>
        <p class="rf-sub">Bu randevu henüz tamamlanmamış veya iptal edilmiş. Yorumun ancak görüşme tamamlandıktan sonra yazılabilir.</p>
        <div class="rf-footer">© {{ date('Y') }} MentörDE · @include('partials.vendor-credit')</div>

    @else
        <h1 class="rf-h1">Görüşmeni Değerlendir</h1>
        <p class="rf-sub">Geri bildirimin {{ $seniorName }}'in ve diğer adayların faydasına.</p>

        <div class="rf-booking-box">
            <div class="rf-bb-avatar" aria-hidden="true">
                @php
                    $parts = preg_split('/\s+/u', trim($seniorName)) ?: [];
                    $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr(end($parts) ?: '', 0, 1));
                @endphp
                {{ $initials ?: 'M' }}
            </div>
            <div>
                <div class="rf-bb-name">{{ $seniorName }}</div>
                <div class="rf-bb-meta">{{ $whenStr }} · {{ $setting?->slot_duration ?? 30 }} dakika</div>
            </div>
        </div>

        @if($errors->any())
            <div class="rf-err">
                @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ $submitUrl }}" id="rf-form">
            @csrf

            <div class="rf-group">
                <label class="rf-label">Puanın (1–5) <span style="color:#c00">*</span></label>
                <div class="rf-stars" role="radiogroup" aria-label="Yıldız puanı">
                    @php $current = old('rating', $existing?->rating ?? 0); @endphp
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" data-val="{{ $i }}"
                                class="rf-star-btn {{ (int) $current >= $i ? 'active' : '' }}"
                                aria-label="{{ $i }} yıldız" role="radio"
                                aria-checked="{{ (int) $current === $i ? 'true' : 'false' }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </button>
                    @endfor
                    <span class="rf-rating-text" id="rf-rating-text">
                        {{ $current ? $current . ' / 5' : 'Yıldız seç' }}
                    </span>
                </div>
                <input type="hidden" name="rating" id="rf-rating-input" value="{{ $current }}" required>
            </div>

            <div class="rf-group">
                <label class="rf-label" for="rf-title">Kısa başlık (opsiyonel)</label>
                <input type="text" id="rf-title" name="title" class="rf-input" maxlength="150"
                       value="{{ old('title', $existing?->title) }}"
                       placeholder="Örn. Çok yardımcı oldu, tavsiye ederim">
            </div>

            <div class="rf-group">
                <label class="rf-label" for="rf-body">Yorumun (opsiyonel)</label>
                <textarea id="rf-body" name="body" class="rf-textarea" maxlength="2000"
                          placeholder="Görüşmen nasıl geçti? Hangi konularda yardımcı oldu?">{{ old('body', $existing?->body) }}</textarea>
                <div class="rf-hint">En fazla 2000 karakter</div>
            </div>

            <button type="submit" class="rf-btn" id="rf-submit">
                {{ $existing ? 'Değerlendirmemi Güncelle' : 'Değerlendirmemi Gönder' }}
            </button>
        </form>

        <div class="rf-footer">
            Bu link {{ $seniorName }} ile yaptığın {{ $whenStr }} görüşmen içindir.<br>
            © {{ date('Y') }} MentörDE
        </div>
    @endif
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var buttons = document.querySelectorAll('.rf-star-btn');
    var input   = document.getElementById('rf-rating-input');
    var textEl  = document.getElementById('rf-rating-text');
    if(!buttons.length || !input){ return; }

    function applyValue(val){
        input.value = String(val);
        buttons.forEach(function(b){
            var v = parseInt(b.getAttribute('data-val'), 10);
            b.classList.toggle('active', v <= val);
            b.setAttribute('aria-checked', v === val ? 'true' : 'false');
        });
        if(textEl){ textEl.textContent = val + ' / 5'; }
    }

    buttons.forEach(function(btn){
        btn.addEventListener('click', function(){
            applyValue(parseInt(btn.getAttribute('data-val'), 10));
        });
        btn.addEventListener('mouseenter', function(){
            var v = parseInt(btn.getAttribute('data-val'), 10);
            buttons.forEach(function(b){
                var bv = parseInt(b.getAttribute('data-val'), 10);
                b.classList.toggle('active', bv <= v);
            });
        });
    });
    var wrap = document.querySelector('.rf-stars');
    if(wrap){
        wrap.addEventListener('mouseleave', function(){
            var v = parseInt(input.value, 10) || 0;
            applyValue(v);
        });
    }

    var form = document.getElementById('rf-form');
    if(form){
        form.addEventListener('submit', function(ev){
            var v = parseInt(input.value, 10) || 0;
            if(v < 1 || v > 5){
                ev.preventDefault();
                alert('Lütfen bir yıldız puanı seç.');
            }
        });
    }
})();
</script>

</body>
</html>

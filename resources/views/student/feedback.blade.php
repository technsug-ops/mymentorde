@extends('student.layouts.app')
@section('title', 'Geri Bildirim')
@section('page_title', 'Geri Bildirim')

@push('head')
<style>
/* ── fb-* Feedback ── */
.fb-header {
    display: flex; align-items: center; gap: 14px;
    background: linear-gradient(to right, #6d28d9, #7c3aed);
    border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; color: #fff;
}
.fb-header-icon  { font-size: 24px; }
.fb-header-title { font-size: 16px; font-weight: 800; }
.fb-header-sub   { font-size: 12px; opacity: .75; }

.fb-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 18px 20px; margin-bottom: 14px;
}
.fb-card-title {
    font-size: 12px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .6px; color: var(--u-muted);
    padding-bottom: 12px; margin-bottom: 16px;
    border-bottom: 1px solid var(--u-line);
    display: flex; align-items: center; gap: 8px;
}
.fb-card-title::before {
    content: ''; display: inline-block; width: 3px; height: 14px;
    background: #7c3aed; border-radius: 2px; flex-shrink: 0;
}

/* Type selector tabs */
.fb-type-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
.fb-type-tab {
    padding: 7px 14px; border-radius: 8px; border: 1px solid var(--u-line);
    background: var(--u-bg); font-size: 12px; font-weight: 600; cursor: pointer;
    color: var(--u-muted); transition: all .15s;
}
.fb-type-tab.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

/* Stars */
.fb-stars { display: flex; gap: 6px; margin: 8px 0 14px; }
.fb-star {
    font-size: 30px; cursor: pointer; color: var(--u-line);
    transition: color .15s, transform .1s;
    line-height: 1;
}
.fb-star:hover { transform: scale(1.15); }
.fb-star.lit { color: #f59e0b; }

/* Star label */
.fb-star-label { font-size: 12px; font-weight: 700; color: var(--u-muted); margin-bottom: 10px; }

/* NPS */
.fb-nps-row { display: flex; gap: 5px; flex-wrap: wrap; margin: 10px 0 6px; }
.fb-nps-btn {
    width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--u-line);
    background: var(--u-bg); cursor: pointer; font-size: 12px; font-weight: 700;
    color: var(--u-muted); transition: all .15s; display: flex;
    align-items: center; justify-content: center;
}
.fb-nps-btn:hover { border-color: #7c3aed; color: #7c3aed; }
.fb-nps-btn.selected { background: #7c3aed; border-color: #7c3aed; color: #fff; }
.fb-nps-btn.det { background: #fef2f2; border-color: #fca5a5; color: #dc2626; }
.fb-nps-btn.pas { background: #fefce8; border-color: #fde68a; color: #ca8a04; }
.fb-nps-btn.pro { background: #f0fdf4; border-color: #86efac; color: #16a34a; }
.fb-nps-labels { display: flex; justify-content: space-between; font-size: 10px; color: var(--u-muted); margin-bottom: 10px; }

/* Field */
.fb-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 12px; }
.fb-field label { font-size: 12px; font-weight: 700; color: var(--u-muted); }
.fb-field select,
.fb-field textarea {
    padding: 9px 12px; border: 1px solid var(--u-line); border-radius: 8px;
    font-size: 13px; color: var(--u-text); background: var(--u-bg);
    outline: none; width: 100%; box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s; resize: vertical;
}
.fb-field select:focus,
.fb-field textarea:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.12); }

/* Submit btn */
.fb-submit {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-radius: 9px;
    background: #7c3aed; color: #fff; font-size: 13px; font-weight: 700;
    border: none; cursor: pointer; transition: background .15s; width: 100%;
    justify-content: center; margin-top: 4px;
}
.fb-submit:hover { background: #6d28d9; }

/* History items */
.fb-hist-empty { text-align: center; padding: 30px 0; color: var(--u-muted); font-size: 13px; }
.fb-hist-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 11px 14px; border-radius: 10px; margin-bottom: 6px;
    border: 1px solid var(--u-line); background: var(--u-bg);
}
.fb-hist-icon {
    width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 16px;
    background: rgba(124,58,237,.08);
}
.fb-hist-stars { color: #f59e0b; font-size: 13px; }
.fb-hist-date  { font-size: 11px; color: var(--u-muted); white-space: nowrap; }

/* Sentiment meter */
.fb-meter {
    display: flex; gap: 8px; margin: 12px 0;
}
.fb-meter-face {
    flex: 1; padding: 10px 6px; border-radius: 10px; border: 2px solid var(--u-line);
    text-align: center; cursor: pointer; transition: all .15s; background: var(--u-bg);
}
.fb-meter-face .face-emoji { font-size: 22px; display: block; }
.fb-meter-face .face-lbl   { font-size: 10px; font-weight: 700; color: var(--u-muted); margin-top: 4px; display: block; }
.fb-meter-face.active-1 { border-color: #dc2626; background: #fef2f2; }
.fb-meter-face.active-2 { border-color: #d97706; background: #fffbeb; }
.fb-meter-face.active-3 { border-color: #ca8a04; background: #fefce8; }
.fb-meter-face.active-4 { border-color: #16a34a; background: #f0fdf4; }
.fb-meter-face.active-5 { border-color: #15803d; background: #dcfce7; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="fb-header">
    <div class="fb-header-icon">💬</div>
    <div>
        <div class="fb-header-title">Geri Bildirim</div>
        <div class="fb-header-sub">Deneyimlerinizi paylaşarak süreci birlikte iyileştirelim</div>
    </div>
</div>

@if(session('success'))
<div class="badge ok" style="display:block;padding:10px 16px;border-radius:10px;margin-bottom:14px;font-size:var(--tx-sm);">
    ✓ {{ session('success') }}
</div>
@endif

{{-- Satır 1: Form (sol) | NPS (sağ) --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;margin-bottom:16px;">

    {{-- ── Form ── --}}
    <div class="fb-card" style="margin-bottom:0;">
        <div class="fb-card-title">⭐ Değerlendirme Formu</div>

        <form method="POST" action="{{ route('student.feedback.store') }}">
            @csrf
            <input type="hidden" name="rating" id="star-input" value="0">
            <input type="hidden" name="feedback_type" id="type-input" value="general">

            {{-- Type tabs --}}
            <div class="fb-type-tabs">
                @php
                    $types = [
                        'general' => ['💬', 'Genel'],
                        'process' => ['📋', 'Süreç'],
                        'senior'  => ['👤', 'Danışman'],
                        'portal'  => ['🖥️', 'Portal'],
                    ];
                @endphp
                @foreach($types as $val => [$ico, $lbl])
                <button type="button" class="fb-type-tab {{ $val==='general' ? 'active' : '' }}"
                        data-val="{{ $val }}" onclick="setType(this)">{{ $ico }} {{ $lbl }}</button>
                @endforeach
            </div>

            {{-- Süreç adımı --}}
            <div class="fb-field">
                <label>Süreç Adımı <span style="font-weight:400;opacity:.7;">(opsiyonel)</span></label>
                <select name="process_step">
                    <option value="">— Seçiniz —</option>
                    @foreach($stepLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Emoji meter --}}
            <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);margin-bottom:6px;">Memnuniyetiniz</div>
            <div class="fb-meter" id="meter-row">
                @foreach([1=>'😞',2=>'😕',3=>'😐',4=>'😊',5=>'😄'] as $v => $em)
                <div class="fb-meter-face" data-val="{{ $v }}" onclick="setMeter({{ $v }})">
                    <span class="face-emoji">{{ $em }}</span>
                    <span class="face-lbl">{{ ['','Kötü','Fena','Orta','İyi','Harika'][$v] }}</span>
                </div>
                @endforeach
            </div>

            {{-- Stars --}}
            <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);margin-bottom:4px;margin-top:10px;">Puan (1–5)</div>
            <div class="fb-stars" id="star-row">
                @for($i = 1; $i <= 5; $i++)
                <span class="fb-star" data-val="{{ $i }}" onclick="setStar({{ $i }})">★</span>
                @endfor
            </div>
            <div class="fb-star-label" id="star-lbl" style="min-height:16px;"></div>

            {{-- Comment --}}
            <div class="fb-field" style="margin-top:6px;">
                <label>Yorumunuz <span style="font-weight:400;opacity:.7;">(opsiyonel)</span></label>
                <textarea name="comment" rows="3" placeholder="Deneyimlerinizi paylaşın..."></textarea>
            </div>

            <button type="submit" class="fb-submit">📨 Gönder</button>
        </form>
    </div>

    {{-- ── NPS ── --}}
    <div class="fb-card" style="margin-bottom:0;">
        <div class="fb-card-title">📊 Bizi Tavsiye Eder misiniz?</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:10px;">
            Arkadaşlarınıza {{ config('brand.name', 'MentorDE') }}'yi önerme olasılığınız nedir?<br>
            <span style="font-weight:700;">0</span> = Kesinlikle hayır &nbsp;·&nbsp; <span style="font-weight:700;">10</span> = Kesinlikle evet
        </div>

        <div class="fb-nps-row" id="nps-row">
            @for($i = 0; $i <= 10; $i++)
            @php $cls = $i<=6 ? 'det' : ($i<=8 ? 'pas' : 'pro'); @endphp
            <button type="button" class="fb-nps-btn {{ $cls }}" data-val="{{ $i }}" onclick="setNps({{ $i }})">{{ $i }}</button>
            @endfor
        </div>
        <div class="fb-nps-labels">
            <span>🔴 Eleştirmen (0–6)</span>
            <span>🟡 Pasif (7–8)</span>
            <span>🟢 Destekçi (9–10)</span>
        </div>

        <div class="fb-field" style="margin:10px 0;">
            <label>Kısa yorum</label>
            <textarea id="nps-comment" rows="3" placeholder="İsterseniz açıklayın..."></textarea>
        </div>

        <button class="fb-submit" onclick="submitNps()">📊 NPS Gönder</button>
        <div id="nps-msg" style="display:none;margin-top:10px;padding:10px 14px;
             background:#f0fdf4;border:1px solid #86efac;border-radius:8px;
             color:#16a34a;font-size:13px;font-weight:700;">
            ✓ Teşekkürler! Görüşünüz kaydedildi.
        </div>

        {{-- Bilgi --}}
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--u-line);
                    display:flex;flex-direction:column;gap:8px;">
            <div style="font-size:var(--tx-xs);font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--u-muted);margin-bottom:2px;">
                💡 Bilgi
            </div>
            @foreach([
                ['✅','Geri bildirimleriniz danışmanınıza iletilir ve süreç iyileştirmelerinde kullanılır.'],
                ['🔒','Değerlendirmeleriniz gizlidir, adınız paylaşılmadan analiz edilir.'],
                ['📊','NPS skorunuz ' . config('brand.name', 'MentorDE') . ' hizmet kalitesini ölçmemize yardımcı olur.'],
            ] as [$ico,$txt])
            <div style="display:flex;gap:8px;align-items:flex-start;">
                <span style="font-size:var(--tx-sm);flex-shrink:0;">{{ $ico }}</span>
                <span style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.5;">{{ $txt }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Satır 2: Özet + Geçmiş (tam genişlik) --}}
@php
    $avgRating = $existing->whereNotNull('rating')->avg('rating');
    $avgNps    = $existing->whereNotNull('nps_score')->avg('nps_score');
    $total     = $existing->count();
@endphp

@if($existing->isNotEmpty())
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;">

    {{-- Özet --}}
    <div class="fb-card" style="margin-bottom:0;">
        <div class="fb-card-title">📈 Özet</div>
        <div class="grid3" style="gap:10px;">
            <div style="text-align:center;padding:14px 8px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;">
                <div style="font-size:var(--tx-2xl);font-weight:800;color:#7c3aed;">{{ $total }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">Değerlendirme</div>
            </div>
            <div style="text-align:center;padding:14px 8px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;">
                <div style="font-size:var(--tx-2xl);font-weight:800;color:#f59e0b;">
                    {{ $avgRating ? number_format($avgRating, 1) : '—' }}
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">Ort. Puan /5</div>
            </div>
            <div style="text-align:center;padding:14px 8px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;">
                <div style="font-size:var(--tx-2xl);font-weight:800;color:#16a34a;">
                    {{ $avgNps !== null ? number_format($avgNps, 1) : '—' }}
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">Ort. NPS /10</div>
            </div>
        </div>
    </div>

    {{-- Geçmiş --}}
    <div class="fb-card" style="margin-bottom:0;">
        <div class="fb-card-title">🕒 Geçmiş Değerlendirmelerim</div>
        @foreach($existing as $fb)
        @php
            $typeIcons  = ['general'=>'💬','process'=>'📋','senior'=>'👤','portal'=>'🖥️','nps'=>'📊'];
            $typeLabels = ['general'=>'Genel','process'=>'Süreç','senior'=>'Danışman','portal'=>'Portal','nps'=>'NPS'];
        @endphp
        <div class="fb-hist-item">
            <div class="fb-hist-icon">{{ $typeIcons[$fb->feedback_type] ?? '💬' }}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">
                    {{ $typeLabels[$fb->feedback_type] ?? $fb->feedback_type }}
                </div>
                @if($fb->rating)
                <div class="fb-hist-stars">
                    @for($i=1;$i<=5;$i++){{ $i<=$fb->rating ? '★' : '☆' }}@endfor
                    <span style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:600;"> {{ $fb->rating }}/5</span>
                </div>
                @endif
                @if($fb->nps_score !== null)
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">NPS: <strong>{{ $fb->nps_score }}</strong>/10</div>
                @endif
                @if($fb->comment)
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:3px;line-height:1.4;">
                    {{ Str::limit($fb->comment, 90) }}
                </div>
                @endif
            </div>
            <div class="fb-hist-date">{{ optional($fb->created_at)->format('d.m.Y') }}</div>
        </div>
        @endforeach
    </div>

</div>
@else
<div class="fb-card" style="text-align:center;padding:30px 20px;">
    <div style="font-size:40px;margin-bottom:10px;">💬</div>
    <div style="font-size:var(--tx-base);font-weight:700;margin-bottom:6px;">Henüz geri bildirim yok</div>
    <div style="font-size:var(--tx-sm);color:var(--u-muted);">İlk değerlendirmenizi yukarıdaki formdan gönderin.</div>
</div>
@endif

@push('scripts')
<script>
var selectedStar = 0, selectedNps = null;

// Star labels
var starLabels = ['', 'Çok Kötü 😞', 'Kötü 😕', 'Orta 😐', 'İyi 😊', 'Mükemmel 😄'];

function setStar(val) {
    selectedStar = val;
    document.getElementById('star-input').value = val;
    document.querySelectorAll('#star-row .fb-star').forEach((el, i) => {
        el.classList.toggle('lit', i < val);
    });
    var lbl = document.getElementById('star-lbl');
    lbl.textContent = starLabels[val] || '';
    lbl.style.color = val >= 4 ? '#16a34a' : val === 3 ? '#ca8a04' : '#dc2626';

    // Sync emoji meter
    setMeter(val);
}

function setMeter(val) {
    document.querySelectorAll('#meter-row .fb-meter-face').forEach(el => {
        el.className = 'fb-meter-face' + (parseInt(el.dataset.val) === val ? ' active-' + val : '');
    });
    // If triggered from meter, also set star
    if (selectedStar !== val) setStar(val);
}

function setType(btn) {
    document.querySelectorAll('.fb-type-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('type-input').value = btn.dataset.val;
}

function setNps(val) {
    selectedNps = val;
    document.querySelectorAll('#nps-row .fb-nps-btn').forEach(el => {
        var v = parseInt(el.dataset.val);
        var base = v <= 6 ? 'det' : v <= 8 ? 'pas' : 'pro';
        el.className = 'fb-nps-btn ' + base + (v === val ? ' selected' : '');
    });
}

async function submitNps() {
    if (selectedNps === null) { alert('Lütfen bir puan seçin.'); return; }
    var comment = document.getElementById('nps-comment').value;
    var res = await fetch('{{ route("student.nps.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ nps_score: selectedNps, comment })
    });
    if (res.ok) {
        document.getElementById('nps-msg').style.display = 'block';
        document.querySelectorAll('#nps-row .fb-nps-btn').forEach(el => el.disabled = true);
        document.getElementById('nps-comment').disabled = true;
    }
}

// Init: reset stars to unselected
window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#star-row .fb-star').forEach(el => el.classList.remove('lit'));
});
</script>
@endpush
@endsection

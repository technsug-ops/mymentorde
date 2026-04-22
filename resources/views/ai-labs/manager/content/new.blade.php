@extends('manager.layouts.app')
@section('title', $template['name'] . ' — İçerik Üretici')
@section('page_title', $template['icon'] . ' ' . $template['name'])

@section('content')
<style>
.alcn-wrap { max-width:800px; margin:20px auto; padding:0 16px; }
.alcn-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.alcn-card h2 { margin:0 0 6px; font-size:17px; color:#0f172a; }
.alcn-desc { font-size:13px; color:#64748b; line-height:1.55; margin-bottom:16px; background:#faf7ff; border:1px solid #ede9fe; border-radius:10px; padding:12px 14px; }
.alcn-msg-warn { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
.alcn-field { margin-bottom:14px; }
.alcn-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
.alcn-field label .required { color:#dc2626; }
.alcn-field input, .alcn-field select, .alcn-field textarea {
    width:100%; padding:9px 11px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:13px; background:#fff; box-sizing:border-box; font-family:inherit;
}
.alcn-field textarea { min-height:80px; resize:vertical; }
.alcn-field .help { font-size:11px; color:#64748b; display:block; margin-top:4px; }
.alcn-btn { padding:10px 20px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
.alcn-btn-primary { background:#5b2e91; color:#fff; }
.alcn-btn-primary:hover { background:#4a2578; }
.alcn-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; text-decoration:none; display:inline-block; }
.alcn-loading { display:none; align-items:center; gap:10px; color:#64748b; font-size:13px; }
.alcn-loading.active { display:flex; }

/* SEO Keyword suggester */
.alcn-kw-suggest {
    margin-top:-10px; margin-bottom:14px; padding:14px; background:#faf7ff;
    border:1px solid #ede9fe; border-radius:10px;
}
.alcn-kw-suggest-head { display:flex; align-items:center; gap:10px; justify-content:space-between; flex-wrap:wrap; margin-bottom:10px; }
.alcn-kw-suggest-head h4 { margin:0; font-size:13px; color:#5b2e91; font-weight:700; }
.alcn-kw-btn {
    background:#5b2e91; color:#fff; border:none; border-radius:8px; padding:6px 12px;
    font-size:12px; font-weight:700; cursor:pointer;
}
.alcn-kw-btn:disabled { opacity:.5; cursor:not-allowed; }
.alcn-kw-results { display:none; }
.alcn-kw-results.active { display:block; }
.alcn-kw-group { margin-top:10px; }
.alcn-kw-group-title { font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
.alcn-kw-chips { display:flex; flex-wrap:wrap; gap:6px; }
.alcn-kw-chip {
    background:#fff; border:1px solid #cbd5e1; border-radius:16px; padding:5px 12px;
    font-size:12px; color:#334155; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:6px;
}
.alcn-kw-chip:hover { border-color:#5b2e91; background:#faf7ff; color:#5b2e91; }
.alcn-kw-chip.added { background:#5b2e91; color:#fff; border-color:#5b2e91; }
.alcn-kw-chip.added::before { content:"✓ "; }
.alcn-kw-chip[data-intent="informational"] { }
.alcn-kw-chip[data-intent="transactional"] { border-color:#16a34a; }
.alcn-kw-chip[data-intent="transactional"]:hover { background:#dcfce7; color:#166534; }
.alcn-kw-intent-badge {
    font-size:9px; padding:1px 5px; border-radius:8px; font-weight:700; text-transform:uppercase;
    background:#f1f5f9; color:#64748b;
}
.alcn-kw-chip[data-intent="transactional"] .alcn-kw-intent-badge { background:#dcfce7; color:#166534; }
.alcn-kw-chip[data-intent="navigational"] .alcn-kw-intent-badge { background:#fef3c7; color:#92400e; }
.alcn-kw-meta {
    background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px;
    font-size:12px; color:#334155; margin-top:10px; line-height:1.5;
}
.alcn-kw-meta strong { color:#5b2e91; display:block; font-size:10px; text-transform:uppercase; margin-bottom:4px; }
.alcn-kw-loading { color:#64748b; font-size:12px; font-style:italic; padding:10px; }
.alcn-kw-error { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:8px 12px; border-radius:6px; font-size:12px; }
</style>

<div class="alcn-wrap">
    @if (session('status'))
        <div class="alcn-msg-warn">{{ session('status') }}</div>
    @endif

    <div class="alcn-card">
        <a href="{{ url('/manager/ai-labs/content') }}" style="color:#5b2e91; font-size:12px; text-decoration:none;">← Tüm içerikler</a>
        <h2 style="margin-top:8px;">{{ $template['icon'] }} {{ $template['name'] }}</h2>

        <div class="alcn-desc">💡 {{ $template['description'] }}</div>

        <form method="POST" action="{{ url('/manager/ai-labs/content/generate/' . $templateCode) }}" id="gen-form">
            @csrf

            <div class="alcn-field">
                <label>İçerik Başlığı <span class="required">*</span></label>
                <input type="text" name="title" required maxlength="300" value="{{ old('title') }}" placeholder="Liste ekranında görünecek ad — örn: 'Halil için TU Berlin Motivation Letter'">
            </div>

            @foreach ($template['fields'] as $key => $field)
                <div class="alcn-field">
                    <label>
                        {{ $field['label'] }}
                        @if ($field['required'] ?? false) <span class="required">*</span> @endif
                    </label>

                    @php
                        $inputName = 'fields[' . $key . ']';
                        $old = old('fields.' . $key, '');
                        $type = $field['type'] ?? 'text';
                        $inputId = 'field-' . $key;
                    @endphp

                    @if ($type === 'textarea')
                        <textarea name="{{ $inputName }}" id="{{ $inputId }}" rows="{{ $field['rows'] ?? 4 }}" @if ($field['required'] ?? false) required @endif placeholder="{{ $field['placeholder'] ?? '' }}">{{ $old }}</textarea>
                    @elseif ($type === 'select')
                        <select name="{{ $inputName }}" id="{{ $inputId }}" @if ($field['required'] ?? false) required @endif>
                            <option value="">— Seç —</option>
                            @foreach ($field['options'] as $ov => $ol)
                                <option value="{{ $ov }}" {{ $old == $ov ? 'selected' : '' }}>{{ $ol }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $type }}" name="{{ $inputName }}" id="{{ $inputId }}" value="{{ $old }}" @if ($field['required'] ?? false) required @endif placeholder="{{ $field['placeholder'] ?? '' }}">
                    @endif

                    @if (!empty($field['help']))
                        <small class="help">{{ $field['help'] }}</small>
                    @endif
                </div>

                {{-- Blog post template — topic alanından sonra SEO keyword suggester --}}
                @if ($templateCode === 'blog_post' && $key === 'topic')
                    <div class="alcn-kw-suggest">
                        <div class="alcn-kw-suggest-head">
                            <h4>✨ AI ile SEO anahtar kelime önerisi al</h4>
                            <button type="button" class="alcn-kw-btn" id="kw-suggest-btn">Öner</button>
                        </div>
                        <p style="font-size:11.5px; color:#64748b; margin:0;">
                            Yukarıdaki konu + aşağıdaki hedef kitle/dil'e göre Gemini 3 kategoride öneri verir: <strong>primary</strong> (yüksek volume), <strong>secondary</strong> (destek), <strong>long-tail</strong> (niche). Chip'e tıklayarak Anahtar Kelimeler alanına ekle.
                        </p>
                        <div class="alcn-kw-results" id="kw-results"></div>
                    </div>
                @endif
            @endforeach

            <div style="display:flex; gap:10px; align-items:center; margin-top:20px;">
                <button type="submit" class="alcn-btn alcn-btn-primary" id="gen-btn">✨ İçerik Üret</button>
                <a href="{{ url('/manager/ai-labs/content') }}" class="alcn-btn alcn-btn-ghost">İptal</a>
                <div class="alcn-loading" id="loading">
                    <span>⏳ AI üretiyor... (10-30 saniye sürebilir)</span>
                </div>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('gen-form').addEventListener('submit', () => {
    document.getElementById('loading').classList.add('active');
    document.getElementById('gen-btn').disabled = true;
    document.getElementById('gen-btn').textContent = '⏳ Üretiliyor...';
});

@if ($templateCode === 'blog_post')
(function(){
    const btn = document.getElementById('kw-suggest-btn');
    const out = document.getElementById('kw-results');
    const topicInput = document.getElementById('field-topic');
    const audienceInput = document.getElementById('field-target_audience');
    const keywordsInput = document.getElementById('field-keywords');

    const token = () => document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    function esc(s) { return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    function addKeywordToInput(keyword) {
        const current = (keywordsInput.value || '').trim();
        const list = current.split(',').map(s => s.trim()).filter(Boolean);
        if (list.includes(keyword)) return false; // already there
        list.push(keyword);
        keywordsInput.value = list.join(', ');
        return true;
    }

    function renderGroup(title, items, intentHelp) {
        if (!items || !items.length) return '';
        const chips = items.map(k => {
            const kw = esc(k.keyword);
            return `<span class="alcn-kw-chip" data-kw="${kw}" data-intent="${esc(k.intent)}" title="${esc(k.reason || '')}">
                ${kw}
                <span class="alcn-kw-intent-badge">${esc(k.intent || 'info')}</span>
            </span>`;
        }).join('');
        return `<div class="alcn-kw-group">
            <div class="alcn-kw-group-title">${esc(title)}</div>
            <div class="alcn-kw-chips">${chips}</div>
        </div>`;
    }

    btn.addEventListener('click', async () => {
        const topic = (topicInput?.value || '').trim();
        if (topic.length < 3) {
            out.innerHTML = '<div class="alcn-kw-error">⚠️ Önce yukarıdaki "Konu / Başlık Fikri" alanını doldur.</div>';
            out.classList.add('active');
            return;
        }

        btn.disabled = true;
        btn.textContent = '⏳ Üretiyor...';
        out.innerHTML = '<div class="alcn-kw-loading">🤖 Gemini SEO analizi yapıyor... (~10 saniye)</div>';
        out.classList.add('active');

        try {
            const fd = new FormData();
            fd.append('topic', topic);
            fd.append('audience', audienceInput?.value || 'prospective_students');
            fd.append('language', 'tr');

            const res = await fetch('{{ url("/manager/ai-labs/content/suggest-keywords") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const data = await res.json();

            if (!data.ok) {
                out.innerHTML = '<div class="alcn-kw-error">❌ Hata: ' + esc(data.error || 'bilinmeyen') + '</div>';
                return;
            }

            let html = '';
            html += renderGroup('🎯 Primary (yüksek volume)', data.primary);
            html += renderGroup('🔗 Secondary (destekleyici)', data.secondary);
            html += renderGroup('🎣 Long-tail (niche, yüksek dönüşüm)', data.long_tail);

            if (data.meta_description) {
                html += `<div class="alcn-kw-meta">
                    <strong>💡 Önerilen Meta Description (155-160 kar.)</strong>
                    ${esc(data.meta_description)}
                </div>`;
            }

            html += '<p style="font-size:11px; color:#64748b; margin-top:10px;">💡 Chip\'e tıkla → aşağıdaki Anahtar Kelimeler alanına eklenir. Tekrar tıklayarak kaldır.</p>';

            out.innerHTML = html;

            // Chip click — toggle in keywords input
            out.querySelectorAll('.alcn-kw-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    const kw = chip.dataset.kw;
                    const list = (keywordsInput.value || '').split(',').map(s => s.trim()).filter(Boolean);
                    if (list.includes(kw)) {
                        // Remove
                        const filtered = list.filter(k => k !== kw);
                        keywordsInput.value = filtered.join(', ');
                        chip.classList.remove('added');
                    } else {
                        // Add
                        list.push(kw);
                        keywordsInput.value = list.join(', ');
                        chip.classList.add('added');
                    }
                });

                // Initial state: if already in input, mark as added
                const existing = (keywordsInput.value || '').split(',').map(s => s.trim());
                if (existing.includes(chip.dataset.kw)) chip.classList.add('added');
            });
        } catch (e) {
            out.innerHTML = '<div class="alcn-kw-error">❌ Ağ hatası: ' + esc(e.message) + '</div>';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Yeniden Öner';
        }
    });
})();
@endif
</script>
@endsection

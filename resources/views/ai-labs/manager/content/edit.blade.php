@extends('manager.layouts.app')
@section('title', $draft->title)
@section('page_title', ($template['icon'] ?? '📄') . ' ' . $draft->title)

@section('content')
<style>
.alce-wrap { max-width:1200px; margin:20px auto; padding:0 16px; }
.alce-grid { display:grid; grid-template-columns:1fr 320px; gap:18px; }
@media(max-width:900px){ .alce-grid { grid-template-columns:1fr; } }
.alce-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.alce-card h3 { margin:0 0 10px; font-size:14px; color:#334155; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.alce-msg-ok { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
.alce-field { margin-bottom:12px; }
.alce-field label { display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em; }
.alce-field input, .alce-field select, .alce-field textarea {
    width:100%; padding:9px 11px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:13px; background:#fff; box-sizing:border-box; font-family:inherit;
}
.alce-field textarea {
    min-height:500px; font-family:'Menlo','Monaco','Courier New',monospace; font-size:12.5px;
    line-height:1.6;
}
.alce-btn { padding:9px 16px; border:none; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.alce-btn-primary { background:#5b2e91; color:#fff; }
.alce-btn-primary:hover { background:#4a2578; }
.alce-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
.alce-btn-danger { background:#dc2626; color:#fff; }
.alce-stat-pill { background:#faf7ff; border:1px solid #ede9fe; border-radius:10px; padding:10px; text-align:center; font-size:11px; color:#5b2e91; margin-bottom:10px; }
.alce-stat-pill strong { font-size:18px; display:block; margin-bottom:2px; }
.alce-var-item { padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:11px; }
.alce-var-item:last-child { border-bottom:none; }
.alce-var-item strong { display:block; color:#64748b; font-size:10px; text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px; }
.alce-export-row { display:flex; gap:6px; flex-wrap:wrap; }
.alce-preview {
    background:#fafbfc; border:1px solid #f1f5f9; border-radius:8px; padding:18px 22px;
    max-height:700px; overflow-y:auto; font-size:13px; line-height:1.7;
}
.alce-preview h1, .alce-preview h2, .alce-preview h3 { color:#0f172a; margin-top:18px; margin-bottom:10px; }
.alce-preview h1 { font-size:22px; }
.alce-preview h2 { font-size:18px; border-bottom:1px solid #e2e8f0; padding-bottom:5px; }
.alce-preview h3 { font-size:15px; }
.alce-preview p { margin:8px 0; }
.alce-preview ul, .alce-preview ol { padding-left:24px; }
.alce-preview li { margin:4px 0; }
.alce-preview strong { color:#0f172a; }
.alce-preview code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:12px; }
.alce-tabs { display:flex; gap:4px; margin-bottom:14px; border-bottom:1px solid #e2e8f0; }
.alce-tab { padding:8px 14px; cursor:pointer; font-size:12px; font-weight:600; color:#64748b; border-bottom:2px solid transparent; }
.alce-tab.active { color:#5b2e91; border-bottom-color:#5b2e91; }
.alce-panel { display:none; }
.alce-panel.active { display:block; }
</style>

<div class="alce-wrap">
    @if (session('status'))
        <div class="alce-msg-ok">{{ session('status') }}</div>
    @endif

    <a href="{{ url('/manager/ai-labs/content') }}" style="color:#5b2e91; font-size:12px; text-decoration:none;">← Tüm içerikler</a>

    @if ($draft->template_code === 'faq' && $draft->status === 'published')
        <div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:10px; margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span style="font-size:18px;">🌐</span>
            <div style="flex:1;">
                <strong>Bu FAQ yayında —</strong> public SSS sayfasında görünüyor.
                <a href="{{ url('/sss') }}" target="_blank" style="color:#166534; text-decoration:underline; margin-left:4px;">/sss sayfasını aç →</a>
            </div>
            <button type="button" class="alce-btn alce-btn-ghost" id="copy-public-url" style="font-size:11px;">🔗 Link Kopyala</button>
        </div>
    @elseif ($draft->template_code === 'faq')
        <div style="background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:12px 16px; border-radius:10px; margin-top:10px; font-size:13px;">
            💡 Bu FAQ'ı public <code>/sss</code> sayfasında göstermek için aşağıdan <strong>durumu "🟢 Yayında"</strong> yap ve kaydet.
        </div>
    @endif

    <div class="alce-grid" style="margin-top:10px;">
        <div>
            <div class="alce-card">
                <form method="POST" action="{{ url('/manager/ai-labs/content/' . $draft->id) }}" id="draft-form">
                    @csrf
                    @method('PUT')

                    <div class="alce-field">
                        <label>Başlık</label>
                        <input type="text" name="title" value="{{ old('title', $draft->title) }}" required maxlength="300">
                    </div>

                    <div class="alce-tabs">
                        <div class="alce-tab active" data-tab="edit">✏️ Düzenle</div>
                        <div class="alce-tab" data-tab="preview">👁 Önizleme</div>
                    </div>

                    <div class="alce-panel active" data-panel="edit">
                        <textarea name="content" id="content-input" required>{{ old('content', $draft->content) }}</textarea>
                        <small style="font-size:11px; color:#64748b; display:block; margin-top:4px;">Markdown destekler. `# Başlık`, `**kalın**`, `- liste`, vs.</small>
                    </div>

                    <div class="alce-panel" data-panel="preview">
                        <div class="alce-preview" id="preview-out"></div>
                    </div>

                    <div style="display:flex; gap:10px; align-items:center; margin-top:16px; flex-wrap:wrap;">
                        <select name="status" style="padding:8px 11px; border:1px solid #cbd5e1; border-radius:8px; font-size:12px;">
                            <option value="draft" {{ $draft->status === 'draft' ? 'selected' : '' }}>📝 Taslak</option>
                            <option value="published" {{ $draft->status === 'published' ? 'selected' : '' }}>🟢 Yayında</option>
                            <option value="archived" {{ $draft->status === 'archived' ? 'selected' : '' }}>🗃 Arşiv</option>
                        </select>
                        <button type="submit" class="alce-btn alce-btn-primary">💾 Kaydet</button>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <div class="alce-card">
                <h3>📊 İstatistikler</h3>
                <div class="alce-stat-pill">
                    <strong>{{ number_format(($draft->tokens_input ?? 0) + ($draft->tokens_output ?? 0)) }}</strong>
                    token kullanıldı
                </div>
                <div style="font-size:11px; color:#64748b; line-height:1.6;">
                    • Girdi: {{ number_format($draft->tokens_input ?? 0) }}<br>
                    • Çıktı: {{ number_format($draft->tokens_output ?? 0) }}<br>
                    • Model: {{ $draft->model ?? '—' }}<br>
                    • Oluşturma: {{ $draft->created_at?->format('d.m.Y H:i') }}
                </div>
            </div>

            <div class="alce-card">
                <h3>📥 Dışa Aktar</h3>
                <div class="alce-export-row">
                    <a href="{{ url('/manager/ai-labs/content/' . $draft->id . '/export/pdf') }}" class="alce-btn alce-btn-ghost">📄 PDF</a>
                    <a href="{{ url('/manager/ai-labs/content/' . $draft->id . '/export/docx') }}" class="alce-btn alce-btn-ghost">📝 DOCX</a>
                    <a href="{{ url('/manager/ai-labs/content/' . $draft->id . '/export/md') }}" class="alce-btn alce-btn-ghost">⬇️ .md</a>
                </div>
            </div>

            @if (!empty($draft->variables))
                <div class="alce-card">
                    <h3>⚙️ Girdi Bilgileri</h3>
                    @foreach ($draft->variables as $k => $v)
                        @if (trim((string) $v) !== '')
                            <div class="alce-var-item">
                                <strong>{{ $k }}</strong>
                                <span>{{ \Illuminate\Support\Str::limit((string) $v, 120) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @if (!empty($draft->metadata))
                <div class="alce-card">
                    <h3>🏷 Meta</h3>
                    @foreach ($draft->metadata as $k => $v)
                        @if (!is_array($v))
                            <div class="alce-var-item">
                                <strong>{{ $k }}</strong>
                                <span>{{ \Illuminate\Support\Str::limit((string) $v, 120) }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if (!empty($draft->metadata['faqs']))
                        <div class="alce-var-item">
                            <strong>FAQ Sayısı</strong>
                            <span>{{ count($draft->metadata['faqs']) }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Tab switch
    const tabs = document.querySelectorAll('.alce-tab');
    const panels = document.querySelectorAll('.alce-panel');
    const input = document.getElementById('content-input');
    const out = document.getElementById('preview-out');

    function renderPreview() {
        const md = input.value || '';
        let html = md.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        // Strip YAML metadata block
        html = html.replace(/^---\s*[\s\S]+?---\s*/m, '');
        // Headings
        html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
        html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
        html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');
        // Bold / italic
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
        // Inline code
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        // Lists
        html = html.replace(/^[\-\*] (.+)$/gm, '<li>$1</li>');
        html = html.replace(/(<li>.*?<\/li>\n?)+/gs, '<ul>$&</ul>');
        // Paragraph breaks
        html = html.replace(/\n{2,}/g, '</p><p>');
        html = '<p>' + html + '</p>';
        html = html.replace(/<p>(\s*<(?:h[1-3]|ul))/g, '$1');
        html = html.replace(/(<\/(?:h[1-3]|ul)>)\s*<\/p>/g, '$1');
        out.innerHTML = html;
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const t = tab.dataset.tab;
            tabs.forEach(x => x.classList.toggle('active', x === tab));
            panels.forEach(p => p.classList.toggle('active', p.dataset.panel === t));
            if (t === 'preview') renderPreview();
        });
    });

    // Copy public FAQ URL
    const copyBtn = document.getElementById('copy-public-url');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const url = window.location.origin + '/sss';
            navigator.clipboard?.writeText(url).then(() => {
                const orig = copyBtn.textContent;
                copyBtn.textContent = '✅ Kopyalandı';
                setTimeout(() => copyBtn.textContent = orig, 2000);
            });
        });
    }
})();
</script>
@endsection

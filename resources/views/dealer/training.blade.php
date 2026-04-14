@extends('dealer.layouts.app')

@section('title', 'Eğitim Merkezi')
@section('page_title', 'Eğitim Merkezi')
@section('page_subtitle', 'Sertifikalı Dealer olma yolunda tüm materyaller')

@push('head')
<style>
/* Progress hero */
.tr-hero {
    background:linear-gradient(to right,#0891b2,#16a34a);
    border-radius:14px;
    padding:22px 28px;
    color:#fff;
    margin-bottom:20px;
    display:flex; align-items:center; gap:20px; flex-wrap:wrap;
}
.tr-hero-info { flex:1; }
.tr-hero-title { font-size:17px; font-weight:800; margin-bottom:4px; }
.tr-hero-sub   { font-size:13px; opacity:.85; }
.tr-hero-right { text-align:right; flex-shrink:0; }
.tr-hero-pct   { font-size:36px; font-weight:900; line-height:1; }
.tr-hero-pct-label { font-size:12px; opacity:.8; margin-top:2px; }

.tr-bar-wrap { background:rgba(255,255,255,.25); border-radius:999px; height:10px; margin-top:14px; overflow:hidden; }
.tr-bar-fill { height:100%; background:#fff; border-radius:999px; transition:width .5s ease; }

/* Certified badge */
.tr-certified {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.4);
    border-radius:999px; padding:6px 16px; font-size:13px; font-weight:700;
    margin-top:10px;
}

/* Category card */
.tr-cat-card {
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-radius:12px;
    overflow:hidden;
    margin-bottom:14px;
}
.tr-cat-head {
    padding:14px 20px;
    background:var(--bg,#f8fafc);
    border-bottom:1px solid var(--border,#e2e8f0);
    display:flex; align-items:center; justify-content:space-between;
    cursor:pointer; user-select:none;
    gap:8px;
}
.tr-cat-head:hover { background:#f0faf4; }
.tr-cat-title { font-size:14px; font-weight:700; color:var(--text,#0f172a); }
.tr-cat-meta  { display:flex; gap:8px; align-items:center; }
.tr-cat-progress { font-size:12px; color:var(--muted,#64748b); }
.tr-cat-toggle { font-size:11px; color:var(--muted,#64748b); flex-shrink:0; }

/* Article row */
.tr-article {
    padding:13px 20px;
    border-bottom:1px solid var(--border,#e2e8f0);
    display:flex; align-items:center; justify-content:space-between;
    gap:12px; transition:background .12s;
}
.tr-article:last-child { border-bottom:none; }
.tr-article:hover { background:var(--bg,#f8fafc); }
.tr-article.read  { background:rgba(22,163,74,.03); }

.tr-art-left { display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
.tr-art-icon {
    width:28px; height:28px; border-radius:6px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:13px;
}
.tr-art-icon.done   { background:rgba(22,163,74,.12); }
.tr-art-icon.undone { background:var(--bg,#f1f5f9); }

.tr-art-title {
    font-size:13px; font-weight:600; color:var(--text,#0f172a);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.tr-art-title.read { color:var(--muted,#64748b); text-decoration:line-through; }
.tr-cat-badge {
    font-size:10px; font-weight:700; padding:2px 7px; border-radius:999px;
    background:rgba(8,145,178,.1); color:#0e7490; flex-shrink:0;
}

.tr-read-btn {
    display:inline-flex; align-items:center; gap:4px;
    padding:6px 14px; border-radius:8px; font-size:12px; font-weight:700;
    background:#16a34a; color:#fff; border:none; cursor:pointer;
    white-space:nowrap; transition:opacity .15s;
}
.tr-read-btn:hover { opacity:.88; }
.tr-done-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:5px 12px; border-radius:8px; font-size:12px; font-weight:700;
    background:rgba(22,163,74,.1); color:#15803d; white-space:nowrap;
}

/* Skeleton (no articles) */
.tr-skeleton-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
@media(max-width:700px){ .tr-skeleton-grid { grid-template-columns:1fr; } }

.tr-skel-card {
    background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0);
    border-radius:12px; padding:20px;
}
.tr-skel-icon { font-size:24px; margin-bottom:10px; }
.tr-skel-title { font-size:14px; font-weight:700; margin-bottom:6px; }
.tr-skel-desc  { font-size:13px; color:var(--muted,#64748b); }

.tr-topic-card {
    background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0);
    border-radius:12px; overflow:hidden; margin-bottom:14px;
}
.tr-topic-head { padding:14px 20px; border-bottom:1px solid var(--border,#e2e8f0); }
.tr-topic-head h3 { margin:0; font-size:14px; font-weight:700; }
.tr-topic-item {
    padding:13px 20px; border-bottom:1px solid var(--border,#e2e8f0);
    display:flex; align-items:flex-start; gap:12px;
}
.tr-topic-item:last-child { border-bottom:none; }
.tr-topic-num { width:24px;height:24px;border-radius:6px;background:rgba(22,163,74,.1);color:#15803d;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px; }
.tr-topic-text strong { font-size:13px;font-weight:700; }
.tr-topic-text .tr-topic-desc { font-size:12px;color:var(--muted,#64748b);margin-top:2px; }

/* Guide */
.tr-guide { background:var(--bg,#f1f5f9); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:16px 20px; margin-top:4px; }
.tr-guide-title { font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:10px; }
.tr-guide ul { margin:0;padding-left:18px; }
.tr-guide li { font-size:13px;color:var(--muted,#64748b);margin-bottom:6px; }
</style>
@endpush

@section('content')

{{-- İlerleme Hero --}}
@if(!empty($trainingProgress))
<div class="tr-hero">
    <div class="tr-hero-info">
        <div class="tr-hero-title">Eğitim İlerlemeniz</div>
        <div class="tr-hero-sub">
            {{ $trainingProgress['read'] }} / {{ $trainingProgress['total'] }} materyal tamamlandı
        </div>
        <div class="tr-bar-wrap">
            <div class="tr-bar-fill" id="training-progress-bar"
                 style="width:{{ $trainingProgress['percent'] }}%;"></div>
        </div>
        @if($trainingProgress['certified'])
        <div class="tr-certified">🏆 Sertifikalı Dealer — Tüm materyaller tamamlandı!</div>
        @endif
    </div>
    <div class="tr-hero-right">
        <div class="tr-hero-pct">%{{ $trainingProgress['percent'] }}</div>
        <div class="tr-hero-pct-label">tamamlandı</div>
    </div>
</div>
@endif

{{-- Materyaller --}}
@if(!empty($articles) && $articles->isNotEmpty())
    @php $grouped = $articles->groupBy('category'); @endphp
    @foreach($grouped as $cat => $catArticles)
        @php
            $readCount  = $catArticles->filter(fn($a) => isset($readIds[$a->id]))->count();
            $totalCount = $catArticles->count();
            $catDone    = $readCount === $totalCount;
        @endphp
        <div class="tr-cat-card">
            <details open>
                <summary class="tr-cat-head">
                    <div class="tr-cat-title">
                        {{ $catDone ? '✅' : '📂' }} {{ ucfirst($cat ?: 'Genel') }}
                    </div>
                    <div class="tr-cat-meta">
                        <span class="tr-cat-progress">{{ $readCount }}/{{ $totalCount }}</span>
                        @if($catDone)
                            <span style="font-size:var(--tx-xs);color:#15803d;font-weight:700;">Tamamlandı</span>
                        @endif
                        <span class="tr-cat-toggle">▾</span>
                    </div>
                </summary>
                @foreach($catArticles as $article)
                    @php $isRead = isset($readIds[$article->id]); @endphp
                    <div class="tr-article {{ $isRead ? 'read' : '' }}" id="article-row-{{ $article->id }}">
                        <div class="tr-art-left">
                            <div class="tr-art-icon {{ $isRead ? 'done' : 'undone' }}">
                                {{ $isRead ? '✓' : '📄' }}
                            </div>
                            <div>
                                <div class="tr-art-title {{ $isRead ? 'read' : '' }}">
                                    {{ $article->title_tr ?: $article->title_en ?: 'Başlıksız' }}
                                </div>
                                @if($article->category)
                                    <span class="tr-cat-badge">{{ $article->category }}</span>
                                @endif
                            </div>
                        </div>
                        @if($isRead)
                            <span class="tr-done-badge">✓ Okundu</span>
                        @else
                            <button class="tr-read-btn" onclick="markArticleRead({{ $article->id }}, this)">
                                Okudum ✓
                            </button>
                        @endif
                    </div>
                @endforeach
            </details>
        </div>
    @endforeach

@else
{{-- İskelet: içerik yokken --}}
<div class="tr-skeleton-grid">
    <div class="tr-skel-card">
        <div class="tr-skel-icon">🚀</div>
        <div class="tr-skel-title">Onboarding</div>
        <div class="tr-skel-desc">{{ config('brand.name', 'MentorDE') }} süreç tanıtımı, lead kalite kriterleri.</div>
    </div>
    <div class="tr-skel-card">
        <div class="tr-skel-icon">🔗</div>
        <div class="tr-skel-title">Satış & Yönlendirme</div>
        <div class="tr-skel-desc">Referans link kullanımı, kampanya paylaşım örnekleri.</div>
    </div>
    <div class="tr-skel-card">
        <div class="tr-skel-icon">📋</div>
        <div class="tr-skel-title">Süreç Rehberi</div>
        <div class="tr-skel-desc">Aday Öğrenci → Öğrenci dönüşüm adımları ve komisyon milestone mantığı.</div>
    </div>
</div>

<div class="tr-topic-card">
    <div class="tr-topic-head"><h3>Yaklaşan Eğitim Konuları</h3></div>
    @foreach([
        ['Lead Kalitesi', 'Hangi profillerde dönüşüm daha yüksek, minimum bilgi standardı.'],
        ['Referans Link Kullanımı', 'UTM/ref kodlu paylaşım, kanal ayrımı ve takip mantığı.'],
        ['Paket ve Sözleşme Aşaması', 'Aday Öğrenci tarafında servis seçimi, sözleşme talebi ve onay akışları.'],
        ['Komisyon Mantığı', 'Milestone bazlı kazanç ve bekleyen ödeme yorumlama.'],
    ] as $i => $topic)
    <div class="tr-topic-item">
        <div class="tr-topic-num">{{ $i + 1 }}</div>
        <div class="tr-topic-text">
            <strong>{{ $topic[0] }}</strong>
            <div class="tr-topic-desc">{{ $topic[1] }}</div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="tr-guide">
    <div class="tr-guide-title">💡 Sertifika Programı</div>
    <ul>
        <li>Her materyali okuyup <strong>Okudum ✓</strong> işaretlediğinizde ilerleme çubuğunuz güncellenir.</li>
        <li>Tüm materyalleri tamamlayan dealer <strong>🏆 Sertifikalı Dealer</strong> rozetini kazanır.</li>
        <li>İçerikler manager/marketing tarafından KnowledgeBase üzerinden güncellenir.</li>
    </ul>
</div>

@endsection

@push('scripts')
<script>
function markArticleRead(articleId, btn) {
    fetch('/dealer/training/' + articleId + '/read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) return;
        const row = document.getElementById('article-row-' + articleId);
        if (row) {
            row.classList.add('read');
            const icon = row.querySelector('.tr-art-icon');
            if (icon) { icon.className = 'tr-art-icon done'; icon.textContent = '✓'; }
            const title = row.querySelector('.tr-art-title');
            if (title) title.classList.add('read');
            btn.outerHTML = '<span class="tr-done-badge">✓ Okundu</span>';
        }
        const bar = document.getElementById('training-progress-bar');
        if (bar) bar.style.width = data.percent + '%';
    })
    .catch(() => {});
}
</script>
@endpush

@extends('marketing-admin.layouts.app')
@section('title', 'AI Asistan Geçmişi')

@section('content')
<div class="page-header">
    <h1>AI Pazarlama Asistanı</h1>
    <p class="u-muted">İçerik üretimi, email konu önerileri ve kampanya analizi.</p>
</div>

<div class="grid2" style="gap:16px;margin-bottom:20px;">
    <div class="card">
        <div class="card-title">İçerik Draft Oluştur</div>
        <form id="draftForm">
            <div class="field"><label>Konu</label><input name="topic" type="text" placeholder="Blog konusu veya başlık..." required></div>
            <div class="field"><label>Kategori</label>
                <select name="category">
                    <option value="blog">Blog</option>
                    <option value="city_guide">Şehir Rehberi</option>
                    <option value="university_guide">Üniversite Rehberi</option>
                    <option value="success_story">Başarı Hikayesi</option>
                    <option value="faq">SSS</option>
                </select>
            </div>
            <div class="field"><label>Dil</label>
                <select name="language">
                    <option value="tr">Türkçe</option>
                    <option value="de">Almanca</option>
                    <option value="en">İngilizce</option>
                </select>
            </div>
            <div class="field"><label>Ton</label>
                <select name="tone">
                    <option value="professional">Profesyonel</option>
                    <option value="casual">Samimi</option>
                    <option value="inspiring">İlham Verici</option>
                </select>
            </div>
            <div class="field"><label>Hedef Kitle (opsiyonel)</label><input name="target_audience" type="text" placeholder="Örn: Bachelor adayları, 18-25 yaş"></div>
            <button type="submit" class="btn">Draft Oluştur</button>
        </form>
        <div id="draftResult" style="display:none;margin-top:12px;" class="card" style="background:var(--u-bg)">
            <div id="draftStatus"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Genel Soru Sor</div>
        <form id="askForm">
            <div class="field"><label>Soru</label>
                <textarea name="question" rows="4" placeholder="Bu ay hangi kanal en verimli? Hangi segmenti hedeflemeliyiz?..." required></textarea>
            </div>
            <div class="field"><label>Bağlam</label>
                <select name="context_type">
                    <option value="campaign">Kampanya</option>
                    <option value="segment">Segment</option>
                    <option value="content">İçerik</option>
                    <option value="email">Email</option>
                    <option value="social">Sosyal Medya</option>
                </select>
            </div>
            <button type="submit" class="btn">Sor</button>
        </form>
        <div id="askResult" style="display:none;margin-top:12px;padding:12px;background:var(--u-bg);border-radius:6px;border:1px solid var(--u-line);">
            <div id="askAnswer"></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-title">Konuşma Geçmişi</div>
    @if($history->isEmpty())
        <p class="u-muted">Henüz AI ile konuşma yapılmamış.</p>
    @else
        <div class="list">
            @foreach($history as $conv)
            <div class="item" style="flex-direction:column;align-items:flex-start;gap:6px;">
                <div style="display:flex;justify-content:space-between;width:100%;">
                    <span class="badge info">{{ $conv->context_type }}</span>
                    <span class="u-muted" style="font-size:var(--tx-xs);">{{ $conv->created_at->diffForHumans() }} · {{ $conv->tokens_used }} token</span>
                </div>
                <div style="font-weight:500;font-size:var(--tx-sm);">{{ Str::limit($conv->question, 120) }}</div>
                <div class="u-muted" style="font-size:var(--tx-xs);">{{ Str::limit($conv->answer, 200) }}</div>
            </div>
            @endforeach
        </div>
        {{ $history->links('partials.pagination') }}
    @endif
</div>

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — AI Pazarlama Asistanı</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">✍️ İçerik Üretimi</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>Blog:</strong> SEO uyumlu uzun-form içerik taslakları</li>
                <li><strong>E-posta Konusu:</strong> A/B test için birden fazla konu önerisi al</li>
                <li><strong>Kampanya Analizi:</strong> Mevcut kampanya verilerini yapıştır → AI yorum yap</li>
                <li>Üretilen içerik → CMS'e kopyala ve düzenle</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">💡 Kullanım İpuçları</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Geçmiş sekmesi önceki AI sorgularını saklar — tekrar kullan veya düzenle</li>
                <li>Konu ne kadar spesifik olursa içerik o kadar kaliteli olur</li>
                <li>AI yanıtını daima gözden geçir ve markaya uyarla</li>
                <li>Aynı konu için birden fazla varyant üret → en iyisini seç</li>
            </ul>
        </div>
    </div>
</details>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    async function postJson(url, body) {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        return r.json();
    }

    document.getElementById('draftForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const payload = Object.fromEntries(fd.entries());
        document.getElementById('draftStatus').textContent = 'Oluşturuluyor...';
        document.getElementById('draftResult').style.display = '';
        const res = await postJson('/mktg-admin/ai/generate-draft', payload);
        if (res.ok) {
            document.getElementById('draftStatus').innerHTML =
                '<span class="badge ok">Oluşturuldu</span> <a href="/mktg-admin/content/' + res.content_id + '/edit" class="btn alt" style="margin-left:8px;">CMS\'de Görüntüle</a>'
                + '<p style="margin-top:8px;font-size:13px;">' + (res.preview ?? '').replace(/\n/g,'<br>') + '...</p>';
        } else {
            document.getElementById('draftStatus').innerHTML = '<span class="badge danger">Hata: ' + (res.error ?? 'Bilinmeyen') + '</span>';
        }
    });

    document.getElementById('askForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const payload = Object.fromEntries(fd.entries());
        document.getElementById('askAnswer').textContent = 'Yanıt alınıyor...';
        document.getElementById('askResult').style.display = '';
        const res = await postJson('/mktg-admin/ai/ask', payload);
        document.getElementById('askAnswer').textContent = res.ok ? (res.answer ?? '') : ('Hata: ' + (res.error ?? ''));
    });
})();
</script>
@endpush

@extends('manager.layouts.app')
@section('title', ($aiLabsName ?? 'AI Labs') . ' — Ayarlar')
@section('page_title','⚙️ ' . ($aiLabsName ?? 'MentorDE AI Labs') . ' — Ayarlar')

@section('content')
<style>
.alss-wrap { max-width:900px; margin:20px auto; padding:0 16px; }
.alss-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.alss-card h2 { margin:0 0 6px; font-size:17px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.alss-card p.hint { margin:0 0 16px; font-size:12px; color:#64748b; line-height:1.65; }
.alss-msg-ok { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
.alss-field { margin-bottom:16px; }
.alss-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
.alss-field input, .alss-field select, .alss-field textarea {
    width:100%; padding:9px 11px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:13px; background:#fff; box-sizing:border-box; font-family:inherit;
}
.alss-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media(max-width:640px){ .alss-grid-2 { grid-template-columns:1fr; } }
.alss-btn { padding:10px 18px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
.alss-btn-primary { background:#5b2e91; color:#fff; }
.alss-btn-primary:hover { background:#4a2578; }
.alss-radio-group { display:flex; gap:10px; }
.alss-radio-group label {
    flex:1; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; cursor:pointer;
    background:#fff; transition:all .15s; margin-bottom:0;
}
.alss-radio-group label.active { border-color:#5b2e91; background:#faf7ff; }
.alss-radio-group input[type=radio] { display:none; }
.alss-radio-title { display:block; font-size:13px; font-weight:700; color:#0f172a; margin-bottom:2px; }
.alss-radio-desc { display:block; font-size:11px; color:#64748b; line-height:1.5; }
.alss-stat { background:#faf7ff; border:1px solid #ede9fe; border-radius:10px; padding:12px 16px; font-size:12px; color:#5b2e91; margin-bottom:14px; }
.alss-brand-info { background:#fef3c7; border:1px solid #fcd34d; border-radius:8px; padding:10px 14px; font-size:11px; color:#92400e; margin-bottom:10px; line-height:1.6; }
</style>

<div class="alss-wrap">

    @if (session('status'))
        <div class="alss-msg-ok">✅ {{ session('status') }}</div>
    @endif

    <div class="alss-stat">
        📚 Kaynak Havuzu: <strong>{{ $sourcesActive }}</strong> aktif / <strong>{{ $sourcesTotal }}</strong> toplam —
        <a href="{{ url('/manager/ai-labs/sources') }}" style="color:#5b2e91; text-decoration:underline;">Yönet →</a>
    </div>

    {{-- Gemini API bağlantısı + sync --}}
    <div class="alss-card" style="border-left:4px solid {{ $geminiConfigured ? '#16a34a' : '#dc2626' }};">
        <h2>🔑 Gemini API Bağlantısı</h2>
        <p class="hint">AI asistan ve içerik üretici için Google Gemini API key gerekli. <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color:#5b2e91;">Google AI Studio'dan ücretsiz al →</a></p>

        <div class="alss-field">
            <label>API Key {{ $geminiConfigured ? '(mevcut: ' . ($geminiKeyMasked ?: '.env üzerinden') . ')' : '— YOK' }}</label>
            <input type="password" name="gemini_api_key" form="ai-labs-settings-form" placeholder="{{ $geminiConfigured ? 'Değiştirmek istersen yeni key gir...' : 'AIza...' }}" autocomplete="off">
            <small style="font-size:11px; color:#64748b;">Boş bırakırsan mevcut key korunur. Bu değer marketing_admin_settings tablosunda saklanır.</small>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
            <button type="button" class="alss-btn alss-btn-primary" id="btn-test-connection" style="background:#16a34a;">🔌 Bağlantıyı Test Et</button>
            <form method="POST" action="{{ url('/manager/ai-labs/sync-now') }}" style="display:inline; margin:0;" onsubmit="return confirm('Tüm aktif PDF kaynaklar Gemini\'ye yüklenecek. Devam?');">
                @csrf
                <button type="submit" class="alss-btn alss-btn-primary">☁️ Kaynakları Şimdi Senkronize Et</button>
            </form>
        </div>
        <div id="test-result" style="margin-top:10px; font-size:13px;"></div>

        <div style="margin-top:14px; padding:10px 14px; background:#f8fafc; border-radius:8px; font-size:12px;">
            📊 PDF Sync Durumu:
            <strong style="color:#16a34a;">{{ $sourcesSynced }}</strong> senkronize,
            <strong style="color:{{ $sourcesPendingSync > 0 ? '#dc2626' : '#64748b' }};">{{ $sourcesPendingSync }}</strong> bekleyen
        </div>
    </div>

    {{-- Dış kaynak API key'leri --}}
    <div class="alss-card" style="border-left:4px solid #3b82f6;">
        <h2>🌐 Dış Kaynak Entegrasyonları</h2>
        <p class="hint">Wikipedia ve RSS ücretsiz, hemen çalışır. Web arama için Serper.dev API key gerekli.</p>

        <div class="alss-field">
            <label>Serper.dev API Key — Google Web Search (opsiyonel)</label>
            <input type="password" name="serper_api_key" form="ai-labs-settings-form"
                   placeholder="{{ $serperKeyMasked ? 'Mevcut: ' . $serperKeyMasked . ' — değiştirmek istersen yeni key gir...' : 'Boş bırakırsan Web Arama tabı kapalı kalır' }}"
                   autocomplete="off">
            <small style="font-size:11px; color:#64748b;">
                <a href="https://serper.dev" target="_blank" style="color:#5b2e91;">serper.dev</a>'den ücretsiz al —
                ilk 2500 sorgu ücretsiz, sonra $50/10K sorgu.
            </small>
        </div>
    </div>

    <form method="POST" action="{{ url('/manager/ai-labs/settings') }}" id="ai-labs-settings-form">
        @csrf
        @method('PUT')

        {{-- Marka --}}
        <div class="alss-card">
            <h2>🏷️ Marka Kimliği</h2>
            <p class="hint">Bu isim sistemin her yerinde (sidebar, AI asistan yanıtları, e-postalar) otomatik kullanılır.</p>

            <div class="alss-brand-info">
                ℹ️ İsmi değiştirdiğinde 5 dakika içinde sistem genelinde güncellenir (cache TTL).
                "MentorDE AI Labs" varsayılan — istersen "Akıllı Danışman", "Belge Asistanı" gibi isimler verebilirsin.
            </div>

            <div class="alss-grid-2">
                <div class="alss-field">
                    <label>Marka Adı *</label>
                    <input type="text" name="brand_name" value="{{ old('brand_name', $brandName) }}" required maxlength="80">
                </div>
                <div class="alss-field">
                    <label>Tagline (kısa açıklama)</label>
                    <input type="text" name="brand_tagline" value="{{ old('brand_tagline', $brandTagline) }}" maxlength="180" placeholder="Yurt dışı eğitim bilgi havuzu">
                </div>
            </div>
        </div>

        {{-- Yönetici Talimatları (persistent training prompts) --}}
        <div class="alss-card" style="border-left:4px solid #f59e0b;">
            <h2>⚡ Yönetici Talimatları <small style="font-size:11px; color:#94a3b8; font-weight:400;">(AI'ı eğitir — her cevapta uygulanır)</small></h2>
            <p class="hint">
                Buraya yazdıkların AI'ın <strong>her cevabında zorunlu kural</strong> olur.
                Kaynakları boşa harcamadan davranışını şekillendirmek için ideal.
            </p>

            <div class="alss-field">
                <textarea name="admin_instructions" rows="8" style="font-family:monospace; font-size:12px;" placeholder="Her satıra bir kural yaz. Örnek:

• Sperrkonto için önce Fintiba öner, olmazsa Expatrio. Deutsche Bank'ı en son sıradaki tercih olarak göster.
• Uni-Assist başvurusunda asla tahmini not hesabı yapma — öğrenciyi senior danışmana yönlendir.
• Öğrenci vize randevusu hakkında soru sorarsa mutlaka 'vfs.global' üzerinden başvurmasını belirt.
• Konaklama sorularında Studentenwerk (öğrenci yurdu) ilk öneri olsun, WG ikinci.
• Öğrenciyle samimi ama profesyonel konuş; argo kullanma.">{{ old('admin_instructions', $settings->admin_instructions ?? '') }}</textarea>
                <small style="font-size:11px; color:#64748b; display:block; margin-top:6px;">
                    💡 Maksimum 5000 karakter. Değişiklik yapınca tüm cache temizlenir (AI yeni kurallarla yanıtlar).
                    @if (!empty($settings->instructions_updated_at))
                        <br>Son güncelleme: {{ $settings->instructions_updated_at->diffForHumans() }}
                    @endif
                </small>
            </div>
        </div>

        {{-- Davranış modu --}}
        <div class="alss-card">
            <h2>🎯 Yanıt Davranışı</h2>
            <p class="hint">AI asistanın kaynaklarda olmayan konulara nasıl yaklaşacağını belirler.</p>

            <div class="alss-radio-group" id="mode-group">
                <label class="{{ $settings->default_mode === 'strict' ? 'active' : '' }}">
                    <input type="radio" name="default_mode" value="strict" {{ $settings->default_mode === 'strict' ? 'checked' : '' }}>
                    <span class="alss-radio-title">🔒 Strict</span>
                    <span class="alss-radio-desc">Sadece kaynaklardan yanıt. Havuzda yoksa "Bilmiyorum" der.</span>
                </label>
                <label class="{{ $settings->default_mode === 'hybrid' ? 'active' : '' }}">
                    <input type="radio" name="default_mode" value="hybrid" {{ $settings->default_mode === 'hybrid' ? 'checked' : '' }}>
                    <span class="alss-radio-title">🌐 Hybrid <small style="color:#5b2e91;">(önerilen)</small></span>
                    <span class="alss-radio-desc">Kaynaklar öncelikli. Havuz dışı yurt dışı eğitim sorusunda uyarı ile yanıtlar.</span>
                </label>
            </div>
        </div>

        {{-- Provider --}}
        <div class="alss-card">
            <h2>🤖 AI Sağlayıcı</h2>
            <p class="hint">Hangi AI modeli üzerinden yanıt üretilecek. Gemini önerilir (maliyet / performans en iyi).</p>

            <div class="alss-field">
                <label>Birincil Provider</label>
                <select name="primary_provider">
                    <option value="gemini" {{ $settings->primary_provider === 'gemini' ? 'selected' : '' }}>
                        Google Gemini 1.5 Flash (önerilen — ucuz, hızlı, uzun context)
                    </option>
                    <option value="claude" {{ $settings->primary_provider === 'claude' ? 'selected' : '' }}>
                        Anthropic Claude (alternatif)
                    </option>
                    <option value="openai" {{ $settings->primary_provider === 'openai' ? 'selected' : '' }}>
                        OpenAI GPT (alternatif)
                    </option>
                </select>
            </div>
        </div>

        {{-- Limitler --}}
        <div class="alss-card">
            <h2>📊 Kullanım Limitleri</h2>
            <p class="hint">Gold tier önerilen: öğrenci 50, aday 20 soru/gün. Premium: sınırsız için 0 yaz.</p>

            <div class="alss-grid-2">
                <div class="alss-field">
                    <label>Öğrenci — Günlük Soru Limiti</label>
                    <input type="number" name="daily_limit_student" min="0" max="1000" value="{{ old('daily_limit_student', $settings->daily_limit_student) }}" required>
                </div>
                <div class="alss-field">
                    <label>Aday — Günlük Soru Limiti</label>
                    <input type="number" name="daily_limit_guest" min="0" max="500" value="{{ old('daily_limit_guest', $settings->daily_limit_guest) }}" required>
                </div>
            </div>

            <div class="alss-field" style="margin-top:14px;">
                <label>
                    <input type="checkbox" name="content_generator_enabled" value="1" {{ $settings->content_generator_enabled ? 'checked' : '' }}>
                    İçerik Üretici (Phase 4) aktif olsun
                </label>
                <small style="color:#64748b; font-size:11px; display:block; margin-top:4px;">
                    Phase 4 tamamlanana kadar kapalı bırak. Aktif edildiğinde motivation letter / vize çağrı / Sperrkonto taslağı üretimi açılır.
                </small>
            </div>

            <div class="alss-field">
                <label>Aylık Doküman Üretim Limiti</label>
                <input type="number" name="monthly_doc_limit" min="0" max="500" value="{{ old('monthly_doc_limit', $settings->monthly_doc_limit) }}" required>
            </div>
        </div>

        <div style="text-align:right;">
            <button type="submit" class="alss-btn alss-btn-primary">Ayarları Kaydet</button>
        </div>
    </form>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    document.querySelectorAll('#mode-group label').forEach(lbl => {
        lbl.addEventListener('click', () => {
            document.querySelectorAll('#mode-group label').forEach(l => l.classList.remove('active'));
            lbl.classList.add('active');
        });
    });

    // Gemini bağlantı testi
    const btnTest = document.getElementById('btn-test-connection');
    const resultBox = document.getElementById('test-result');
    if (btnTest) {
        btnTest.addEventListener('click', async () => {
            resultBox.innerHTML = '⏳ Bağlantı test ediliyor...';
            btnTest.disabled = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content
                           || document.querySelector('input[name="_token"]')?.value;

                // Form'daki key'i al — henüz kaydedilmemiş olabilir
                const formKey = document.querySelector('input[name="gemini_api_key"]')?.value || '';
                const fd = new FormData();
                if (formKey) fd.append('gemini_api_key', formKey);

                const res = await fetch('{{ url('/manager/ai-labs/test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });
                const data = await res.json();
                if (data.ok) {
                    const scope = data.tested_with === 'form' ? ' (formdaki key — henüz kaydedilmedi)' : ' (kayıtlı key)';
                    let html = '<div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px;">✅ ' + data.message + scope + '</div>';
                    if (data.models && data.models.length) {
                        html += '<div style="font-size:11px; color:#64748b; margin-top:4px;">Örnek modeller: ' + data.models.join(', ') + '</div>';
                    }
                    resultBox.innerHTML = html;
                } else {
                    resultBox.innerHTML = '<div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:8px;">❌ ' + data.message + '</div>';
                }
            } catch (e) {
                resultBox.innerHTML = '<div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:8px;">❌ Ağ hatası: ' + e.message + '</div>';
            } finally {
                btnTest.disabled = false;
            }
        });
    }
})();
</script>
@endsection

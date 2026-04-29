@extends('manager.layouts.app')

@section('title', $mode === 'create' ? 'Yeni İndirim Kodu' : 'Kodu Düzenle')
@section('page_title', $mode === 'create' ? 'Yeni İndirim Kodu' : 'Kodu Düzenle: '.$code->code)

@push('head')
<style>
.dc-form { background: var(--u-card); border: 1px solid var(--u-line); border-radius: 10px;
    padding: 20px; max-width: 720px; }
.dc-form .dc-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 12px; }
.dc-form .dc-full { grid-column: 1 / -1; }
.dc-form label { display:block; font-size: 12px; font-weight: 600; color: var(--u-muted); margin-bottom: 4px; }
.dc-form input, .dc-form select, .dc-form textarea {
    width:100%; padding: 7px 10px; border: 1px solid var(--u-line); border-radius: 7px;
    background: var(--u-bg); color: var(--u-text); font-size: 13px; }
.dc-form input:focus, .dc-form select:focus { border-color: var(--u-brand); outline: none; }
.dc-hint { font-size: 11.5px; color: var(--u-muted); margin-top: 3px; }
.dc-actions { display: flex; gap: 10px; margin-top: 16px; }
.dc-btn { padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 7px;
    border: 1px solid var(--u-line); background: var(--u-bg); color: var(--u-text); cursor: pointer; }
.dc-btn.primary { background: var(--u-brand, #2563eb); color: white; border-color: var(--u-brand); }
.dc-checkbox { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
.dc-checkbox input { width: auto; }

.dc-section { margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--u-line); }
.dc-section h3 { font-size: 13px; font-weight: 700; color: var(--u-text); margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: .4px; }

.dc-templates { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 16px; }
@media(max-width:700px){ .dc-templates { grid-template-columns: repeat(2, 1fr); } }
.dc-tpl-card {
    cursor: pointer; border: 2px solid var(--u-line); border-radius: 8px;
    padding: 12px 8px; text-align: center; background: var(--u-bg);
    transition: all .15s; user-select: none;
}
.dc-tpl-card:hover { border-color: var(--u-brand); }
.dc-tpl-card input { display: none; }
.dc-tpl-card .dc-tpl-preview {
    height: 70px; border-radius: 6px; margin-bottom: 8px; display:flex;
    align-items:center; justify-content:center; font-weight:800; font-size:11px;
    color:white; letter-spacing:1px;
}
.dc-tpl-card .dc-tpl-name { font-size: 11.5px; font-weight: 700; color: var(--u-text); }
.dc-tpl-card .dc-tpl-mood { font-size: 10px; color: var(--u-muted); margin-top: 2px; }
.dc-tpl-card.selected { border-color: var(--u-brand); background: rgba(37,99,235,.05); }

.dc-tpl-1 .dc-tpl-preview { background: linear-gradient(135deg, #6d28d9 0%, #4f46e5 100%); }
.dc-tpl-2 .dc-tpl-preview { background: linear-gradient(135deg, #ec4899 0%, #f97316 100%); }
.dc-tpl-3 .dc-tpl-preview { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fbbf24; }
.dc-tpl-4 .dc-tpl-preview { background: linear-gradient(135deg, #c084fc 0%, #fde047 100%); color: #581c87; }
.dc-tpl-5 .dc-tpl-preview { background: linear-gradient(135deg, #dc2626 0%, #f97316 100%); }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @if($errors->any())
        <div style="background:rgba(220,38,38,.08);color:rgb(185,28,28);border:1px solid rgba(220,38,38,.3);padding:10px 14px;border-radius:10px;margin-bottom:14px;">
            @foreach($errors->all() as $e) ⚠ {{ $e }}<br> @endforeach
        </div>
    @endif

    <form class="dc-form" method="POST" action="{{ $mode === 'create' ? route('manager.discount-codes.store') : route('manager.discount-codes.update', $code) }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="dc-row">
            <div>
                <label>Kod *</label>
                <input type="text" name="code" required maxlength="64"
                       value="{{ old('code', $code->code) }}" placeholder="Örn: HOSGELDIN10" style="text-transform:uppercase;">
                <div class="dc-hint">Sadece harf/rakam/tire/altçizgi. Otomatik büyük harfe çevrilir.</div>
            </div>
            <div>
                <label>Açıklama</label>
                <input type="text" name="description" maxlength="255"
                       value="{{ old('description', $code->description) }}" placeholder="Manager için iç not (opsiyonel)">
            </div>
        </div>

        <div class="dc-row">
            <div>
                <label>İndirim Tipi *</label>
                <select name="discount_type" required>
                    <option value="percent" {{ old('discount_type', $code->discount_type) === 'percent' ? 'selected' : '' }}>Yüzde (%)</option>
                    <option value="fixed"   {{ old('discount_type', $code->discount_type) === 'fixed' ? 'selected' : '' }}>Sabit Tutar (EUR)</option>
                </select>
            </div>
            <div>
                <label>İndirim Değeri *</label>
                <input type="number" step="0.01" min="0" name="discount_value" required
                       value="{{ old('discount_value', $code->discount_value) }}" placeholder="örn: 10 (yüzde) veya 250 (EUR)">
                <div class="dc-hint">Yüzde için 0–100, sabit için EUR tutarı.</div>
            </div>
        </div>

        <div class="dc-row">
            <div>
                <label>Geçerlilik başlangıcı</label>
                <input type="date" name="valid_from"
                       value="{{ old('valid_from', $code->valid_from?->format('Y-m-d')) }}">
                <div class="dc-hint">Boş = bugünden itibaren.</div>
            </div>
            <div>
                <label>Son kullanma</label>
                <input type="date" name="valid_until"
                       value="{{ old('valid_until', $code->valid_until?->format('Y-m-d')) }}">
                <div class="dc-hint">Boş = sınırsız tarih.</div>
            </div>
        </div>

        <div class="dc-row">
            <div>
                <label>Toplam max kullanım</label>
                <input type="number" min="1" name="max_redemptions"
                       value="{{ old('max_redemptions', $code->max_redemptions) }}" placeholder="boş = sınırsız">
                <div class="dc-hint">Tüm adaylar dahil toplam kotası.</div>
            </div>
            <div>
                <label>Kişi başına max kullanım *</label>
                <input type="number" min="1" max="100" required name="max_per_user"
                       value="{{ old('max_per_user', $code->max_per_user ?: 1) }}">
                <div class="dc-hint">Aynı aday kaç kez kullanabilir.</div>
            </div>
        </div>

        <div class="dc-checkbox">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $code->is_active) ? 'checked' : '' }}>
            <label for="is_active" style="margin:0;">Aktif</label>
        </div>

        {{-- ── Paylaşım kartı (public landing /promo/{code}) ──────── --}}
        <div class="dc-section">
            <h3>🎨 Paylaşım Kartı Tasarımı</h3>
            <div style="font-size:11.5px;color:var(--u-muted);margin-bottom:12px;">
                Bu kupona özel public link otomatik üretilir: <code>/promo/{{ $code->code ?: 'KOD' }}</code> —
                seçtiğin template ve metinlerle güzel bir landing oluşur. Aday "Görsel İndir" ile PNG kaydedebilir.
            </div>

            {{-- AI önerisi butonu --}}
            <div id="aiSuggestBox" style="margin-bottom:14px; padding:10px 12px; background:linear-gradient(135deg,rgba(168,85,247,.08),rgba(59,130,246,.08)); border:1px solid rgba(168,85,247,.25); border-radius:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div style="font-size:18px;">✨</div>
                <div style="flex:1; min-width:200px;">
                    <div style="font-size:12.5px; font-weight:700; color:var(--u-text);">AI ile metinleri otomatik oluştur</div>
                    <div style="font-size:11px; color:var(--u-muted); margin-top:1px;">İndirim tutarı, template tonu ve geçerliliğine göre 4 alan birden dolar.</div>
                </div>
                <button type="button" id="aiSuggestBtn" class="dc-btn primary" style="white-space:nowrap;">
                    ✨ Önerileri Üret
                </button>
            </div>
            <div id="aiSuggestFeedback" style="font-size:12px; line-height:1.5; margin-bottom:12px;"></div>

            <label>Template seç</label>
            @php $selectedTpl = (int) old('template_id', $code->template_id ?: 1); @endphp
            <div class="dc-templates">
                @foreach([1=>['Classic','Sade · Profesyonel'], 2=>['Bold','Canlı · Genç'], 3=>['Premium','Lüks · Şık'], 4=>['Playful','Eğlenceli · Renkli'], 5=>['Urgency','Aciliyet · Limited']] as $tid => $info)
                    <label class="dc-tpl-card dc-tpl-{{ $tid }} {{ $selectedTpl === $tid ? 'selected' : '' }}">
                        <input type="radio" name="template_id" value="{{ $tid }}" {{ $selectedTpl === $tid ? 'checked' : '' }}>
                        <div class="dc-tpl-preview">{{ $info[0] }}</div>
                        <div class="dc-tpl-name">{{ $info[0] }}</div>
                        <div class="dc-tpl-mood">{{ $info[1] }}</div>
                    </label>
                @endforeach
            </div>

            <div class="dc-row">
                <div class="dc-full">
                    <label>Hero başlık (opsiyonel)</label>
                    <input type="text" name="landing_title" maxlength="255"
                           value="{{ old('landing_title', $code->landing_title) }}"
                           placeholder="Örn: 'Sana Özel Hoş Geldin Hediyesi 🎉' — boş bırakırsan default kullanılır">
                </div>
            </div>
            <div class="dc-row">
                <div class="dc-full">
                    <label>Alt başlık / açıklama (opsiyonel)</label>
                    <textarea name="landing_subtitle" maxlength="500" rows="2"
                              placeholder="Örn: 'Almanya yolculuğun başlasın — bu kupon ile hizmet paketinde özel indirim seni bekliyor.'"
                              style="width:100%;padding:7px 10px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);color:var(--u-text);font-size:13px;font-family:inherit;resize:vertical;">{{ old('landing_subtitle', $code->landing_subtitle) }}</textarea>
                </div>
            </div>
            <div class="dc-row">
                <div>
                    <label>CTA buton metni</label>
                    <input type="text" name="landing_cta_text" maxlength="120"
                           value="{{ old('landing_cta_text', $code->landing_cta_text) }}"
                           placeholder="Hemen Başvur">
                </div>
                <div>
                    <label>Disclaimer / küçük yazı</label>
                    <input type="text" name="landing_disclaimer" maxlength="1000"
                           value="{{ old('landing_disclaimer', $code->landing_disclaimer) }}"
                           placeholder="Kupon kullanım koşulları geçerlidir. Tek kişi tek kullanım.">
                </div>
            </div>
        </div>

        <div class="dc-actions">
            <button type="submit" class="dc-btn primary">{{ $mode === 'create' ? 'Oluştur' : 'Kaydet' }}</button>
            <a href="{{ route('manager.discount-codes.index') }}" class="dc-btn">İptal</a>
            @if($mode === 'edit')
                <a href="{{ route('promo.show', $code->code) }}" target="_blank" class="dc-btn" style="margin-left:auto;">
                    👁 Önizle (yeni sekme)
                </a>
            @endif
        </div>
    </form>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
    // Template card highlight
    document.querySelectorAll('.dc-tpl-card input[type=radio]').forEach(function(r){
        r.addEventListener('change', function(){
            document.querySelectorAll('.dc-tpl-card').forEach(c => c.classList.remove('selected'));
            this.closest('.dc-tpl-card').classList.add('selected');
        });
    });

    // AI ile öneri üret
    (function(){
        var btn = document.getElementById('aiSuggestBtn');
        var fb  = document.getElementById('aiSuggestFeedback');
        if (!btn) return;

        btn.addEventListener('click', function(){
            var form = btn.closest('form');
            if (!form) return;

            var fd = new FormData(form);
            var dv = parseFloat(fd.get('discount_value') || '0');
            if (!dv || dv <= 0) {
                fb.innerHTML = '<span style="color:rgb(180,83,9);">⚠ Önce indirim tipi + değerini gir, sonra AI öner.</span>';
                return;
            }

            var payload = {
                code:            fd.get('code') || '',
                description:     fd.get('description') || '',
                discount_type:   fd.get('discount_type') || 'percent',
                discount_value:  dv,
                template_id:     parseInt(fd.get('template_id') || '1', 10),
                valid_until:     fd.get('valid_until') || null,
            };

            var orig = btn.textContent;
            btn.textContent = '⏳ AI düşünüyor…'; btn.disabled = true;
            fb.innerHTML = '<span style="color:var(--u-muted);">İndirim + template tonuna göre 4 metin üretiliyor…</span>';

            fetch('{{ route('manager.discount-codes.ai-suggest') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
            .then(function(r){ return r.json().then(function(d){ return { status: r.status, data: d }; }); })
            .then(function(res){
                btn.textContent = orig; btn.disabled = false;
                if (res.data && res.data.ok) {
                    var fields = {
                        landing_title:      res.data.title,
                        landing_subtitle:   res.data.subtitle,
                        landing_cta_text:   res.data.cta,
                        landing_disclaimer: res.data.disclaimer,
                    };
                    Object.keys(fields).forEach(function(name){
                        var el = form.querySelector('[name="' + name + '"]');
                        if (el && fields[name]) {
                            el.value = fields[name];
                            // Hafif yanıp sön efekti
                            el.style.transition = 'background-color .3s';
                            el.style.backgroundColor = 'rgba(168,85,247,.15)';
                            setTimeout(function(){ el.style.backgroundColor = ''; }, 800);
                        }
                    });
                    var meta = res.data.provider ? ' (' + res.data.provider + ')' : '';
                    fb.innerHTML = '<span style="color:#15803d;">✓ 4 alan dolduruldu' + meta + '. İstersen elle düzenle veya tekrar üret.</span>';
                } else {
                    var err = (res.data && res.data.error) || ('HTTP ' + res.status);
                    fb.innerHTML = '<span style="color:rgb(185,28,28);">⚠ ' + err + '</span>';
                }
            })
            .catch(function(e){
                btn.textContent = orig; btn.disabled = false;
                fb.innerHTML = '<span style="color:rgb(185,28,28);">⚠ Network hatası: ' + e.message + '</span>';
            });
        });
    })();
    </script>
    @endpush
</div>
@endsection

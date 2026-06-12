@extends('platform.layouts.app')

@section('title', 'Yeni Duyuru — DGmarkt Platform')

@section('content')
    <div class="plat-page-header">
        <div>
            <h1 class="plat-page-title"><x-icon name="megaphone" size="22" /> Yeni Duyuru</h1>
            <p class="plat-page-sub">Markdown destekli icerik + hedef segment + opsiyonel zamanlama.</p>
        </div>
        <a href="{{ route('platform.broadcasts') }}" class="plat-btn plat-btn-ghost">
            <x-icon name="arrow-left" size="14" /> Geri
        </a>
    </div>

    <form method="POST" action="{{ route('platform.broadcasts.store') }}" id="broadcastForm">
        @csrf
        <input type="hidden" name="action" id="actionField" value="draft">

        <div class="plat-grid plat-grid-2" style="align-items:start;">
            {{-- SOL: Form --}}
            <div class="plat-card">
                <h3 class="plat-card-title"><x-icon name="pencil" size="14" /> Icerik</h3>

                <div class="plat-form-group">
                    <label class="plat-form-label">Baslik</label>
                    <input type="text" name="title" id="titleInput" maxlength="200" required class="plat-input"
                           value="{{ old('title') }}" placeholder="Ornek: Yeni AI Asistan modulu yayinda!">
                </div>

                <div class="plat-form-group">
                    <label class="plat-form-label">
                        Govde (Markdown destekli)
                        <span style="color:var(--plat-muted); font-weight:500; text-transform:none; margin-left:8px;">
                            **kalin** *italik* [link](https://...)
                        </span>
                    </label>
                    <textarea name="body" id="bodyInput" rows="9" required class="plat-textarea"
                              placeholder="Merhaba, ...">{{ old('body') }}</textarea>
                </div>

                <h3 class="plat-card-title" style="margin-top:20px;"><x-icon name="target" size="14" /> Hedefleme</h3>

                <div class="plat-form-group">
                    <label class="plat-form-label">Kanal</label>
                    <div style="display:flex; gap:14px; flex-wrap:wrap;">
                        @foreach (['both' => 'Email + In-App', 'email' => 'Yalniz Email', 'in_app' => 'Yalniz In-App'] as $val => $lbl)
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                                <input type="radio" name="channel" value="{{ $val }}" {{ old('channel', 'both') === $val ? 'checked' : '' }}>
                                {{ $lbl }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="plat-form-group">
                    <label class="plat-form-label">Hedef Segment</label>
                    <select name="target_segment" id="segmentSelect" class="plat-select">
                        @foreach (['all' => 'Tum musteriler', 'trial' => 'Trial (deneme)', 'paid' => 'Odeyen (basic+gold+premium)', 'specific' => 'Belirli company\'ler (manuel)'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('target_segment', 'all') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="plat-form-group" id="tierBox">
                    <label class="plat-form-label">Tier filtresi (opsiyonel — segment uzerine biner)</label>
                    <div style="display:flex; gap:14px; flex-wrap:wrap;">
                        @foreach ($tiers as $tier)
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                                <input type="checkbox" name="target_tiers[]" value="{{ $tier }}"
                                       {{ in_array($tier, old('target_tiers', []), true) ? 'checked' : '' }}>
                                <span class="plat-badge plat-badge-{{ $tier }}">{{ $tier }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="plat-form-group" id="companyBox" style="display:none;">
                    <label class="plat-form-label">Specific company'ler</label>
                    <select name="target_company_ids[]" multiple size="6" class="plat-select">
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}"
                                {{ in_array($c->id, old('target_company_ids', []), true) ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->subscription_tier }})
                            </option>
                        @endforeach
                    </select>
                    <small style="color:var(--plat-muted); font-size:11px;">Ctrl/Cmd ile coklu secim</small>
                </div>

                <h3 class="plat-card-title" style="margin-top:20px;"><x-icon name="external-link" size="14" /> CTA (opsiyonel)</h3>

                <div class="plat-grid plat-grid-2" style="gap:12px;">
                    <div class="plat-form-group">
                        <label class="plat-form-label">Buton metni</label>
                        <input type="text" name="cta_label" maxlength="100" class="plat-input"
                               value="{{ old('cta_label') }}" placeholder="Ornek: Detayini gor">
                    </div>
                    <div class="plat-form-group">
                        <label class="plat-form-label">Buton URL'i</label>
                        <input type="url" name="cta_url" maxlength="500" class="plat-input"
                               value="{{ old('cta_url') }}" placeholder="https://...">
                    </div>
                </div>

                <h3 class="plat-card-title" style="margin-top:20px;"><x-icon name="clock" size="14" /> Zamanlama</h3>

                <div class="plat-form-group">
                    <label class="plat-form-label">Gonderim zamani (bos = hemen)</label>
                    <input type="datetime-local" name="scheduled_for" id="scheduledInput"
                           class="plat-input" value="{{ old('scheduled_for') }}">
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; border-top:1px solid var(--plat-border); padding-top:16px;">
                    <button type="button" id="btnPreview" class="plat-btn plat-btn-ghost">
                        <x-icon name="eye" size="14" /> Onizle
                    </button>
                    <button type="button" id="btnDraft" class="plat-btn plat-btn-ghost">
                        <x-icon name="archive" size="14" /> Taslak Kaydet
                    </button>
                    <button type="button" id="btnSchedule" class="plat-btn plat-btn-ghost">
                        <x-icon name="calendar" size="14" /> Zamanla
                    </button>
                    <button type="button" id="btnSendNow" class="plat-btn plat-btn-primary">
                        <x-icon name="send" size="14" /> Simdi Gonder
                    </button>
                </div>
            </div>

            {{-- SAG: Live Preview --}}
            <div class="plat-card">
                <h3 class="plat-card-title"><x-icon name="eye" size="14" /> Onizleme</h3>
                <div id="previewBox" style="background:var(--plat-bg); border:1px solid var(--plat-border); border-radius:8px; padding:18px 20px; min-height:300px;">
                    <h2 id="previewTitle" style="color:var(--plat-accent-2); margin:0 0 12px; font-size:18px;">
                        Baslik buraya gelecek
                    </h2>
                    <div id="previewBody" style="font-size:13px; line-height:1.6; color:var(--plat-text);">
                        Icerik yazmaya basla, onizleme burada belirir...
                    </div>
                    <div id="previewCta" style="margin-top:20px;"></div>
                </div>

                <div style="margin-top:14px; font-size:12px; color:var(--plat-muted);">
                    <x-icon name="circle-alert" size="12" /> Bu onizleme yalnizca bilgi amaclidir. Email gonderiminde kucuk farkliliklar olabilir.
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script nonce="{{ $cspNonce ?? '' }}">
        (function(){
            const segSel    = document.getElementById('segmentSelect');
            const compBox   = document.getElementById('companyBox');
            const tierBox   = document.getElementById('tierBox');

            function syncSegmentBoxes(){
                if (segSel.value === 'specific') {
                    compBox.style.display = '';
                    tierBox.style.display = 'none';
                } else {
                    compBox.style.display = 'none';
                    tierBox.style.display = '';
                }
            }
            segSel.addEventListener('change', syncSegmentBoxes);
            syncSegmentBoxes();

            // Live preview
            const titleEl = document.getElementById('titleInput');
            const bodyEl  = document.getElementById('bodyInput');
            const pTitle  = document.getElementById('previewTitle');
            const pBody   = document.getElementById('previewBody');
            const pCta    = document.getElementById('previewCta');

            function mdRender(t){
                if (!t) return '';
                let s = t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
                s = s.replace(/\*(.+?)\*/g, '<em>$1</em>');
                s = s.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" style="color:var(--plat-accent-2);">$1</a>');
                return s.split(/\n{2,}/).map(p => '<p>'+p.replace(/\n/g,'<br>')+'</p>').join('');
            }

            function refreshPreview(){
                pTitle.textContent = titleEl.value || 'Baslik buraya gelecek';
                pBody.innerHTML    = mdRender(bodyEl.value) || 'Icerik yazmaya basla, onizleme burada belirir...';
                const ctaLabel = document.querySelector('[name="cta_label"]').value.trim();
                const ctaUrl   = document.querySelector('[name="cta_url"]').value.trim();
                if (ctaLabel && ctaUrl) {
                    pCta.innerHTML = '<a href="'+ctaUrl+'" target="_blank" style="display:inline-block;background:linear-gradient(135deg,var(--plat-accent),var(--plat-accent-2));color:#fff;padding:9px 20px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;">'+ctaLabel+'</a>';
                } else {
                    pCta.innerHTML = '';
                }
            }
            ['input','change'].forEach(ev => {
                titleEl.addEventListener(ev, refreshPreview);
                bodyEl.addEventListener(ev, refreshPreview);
                document.querySelector('[name="cta_label"]').addEventListener(ev, refreshPreview);
                document.querySelector('[name="cta_url"]').addEventListener(ev, refreshPreview);
            });
            refreshPreview();

            // Action butonlari
            const form     = document.getElementById('broadcastForm');
            const actField = document.getElementById('actionField');
            document.getElementById('btnDraft').addEventListener('click', function(){
                actField.value = 'draft';
                form.submit();
            });
            document.getElementById('btnSchedule').addEventListener('click', function(){
                const t = document.getElementById('scheduledInput').value;
                if (!t) {
                    alert('Once zamanlama tarihi sec.');
                    return;
                }
                actField.value = 'schedule';
                form.submit();
            });
            document.getElementById('btnSendNow').addEventListener('click', function(){
                if (!confirm('Tum hedef alicilara hemen gonderilecek. Emin misin?')) return;
                actField.value = 'send_now';
                form.submit();
            });
            document.getElementById('btnPreview').addEventListener('click', refreshPreview);
        })();
        </script>
    @endpush
@endsection

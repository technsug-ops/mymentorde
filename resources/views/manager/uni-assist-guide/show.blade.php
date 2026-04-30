@extends('manager.layouts.app')

@section('title', 'Uni-Assist Rehberi · '.$guest->first_name.' '.$guest->last_name)
@section('page_title', '🎓 Uni-Assist Başvuru Rehberi')
@section('page_subtitle', $guest->first_name.' '.$guest->last_name.' · '.($guest->email ?? '—').' — Mein Konto sekmelerini birebir görür, her alanı tek tıkla kopyalarsın')

@push('head')
<style>
/* Uni-Assist tema klonu — kırmızı accent (#c8102e), tek sütun form, sekmeli */
.ua-wrap { max-width:1100px; margin:0 auto; }
.ua-banner {
    background:linear-gradient(135deg,#fff,#fef2f4);
    border:1px solid #fecdd3; border-radius:12px;
    padding:14px 18px; margin-bottom:18px;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
}
.ua-banner-icon { font-size:32px; }
.ua-banner-title { font-weight:700; color:#7f1d1d; font-size:15px; }
.ua-banner-sub { font-size:12.5px; color:#9f1239; line-height:1.55; }
.ua-banner-link {
    margin-left:auto; padding:8px 14px;
    background:#c8102e; color:#fff; border-radius:8px; font-size:12px; font-weight:700;
    text-decoration:none;
}
.ua-banner-link:hover { background:#9f1239; }

/* Sekme bar */
.ua-tabs {
    display:flex; gap:0; border-bottom:2px solid var(--u-line);
    margin-bottom:24px; flex-wrap:wrap;
}
.ua-tab {
    padding:14px 22px; cursor:pointer; background:transparent; border:none;
    font-family:inherit; font-size:13px; font-weight:600; letter-spacing:.6px;
    color:var(--u-muted); text-transform:uppercase;
    border-bottom:3px solid transparent; margin-bottom:-2px;
}
.ua-tab.active {
    color:#c8102e; border-bottom-color:#c8102e;
}
.ua-tab small { display:block; font-size:9.5px; color:var(--u-muted); margin-top:3px; text-transform:none; letter-spacing:0; }
.ua-tab.active small { color:#9f1239; }
.ua-tab-warn { background:rgba(217,119,6,.15); color:rgb(180,83,9); border-radius:999px; padding:1px 7px; font-size:10px; font-weight:800; margin-left:6px; vertical-align:top; }

/* Tab panel */
.ua-panel { display:none; }
.ua-panel.active { display:block; }

/* Field card — Uni-Assist'in dikey form layoutu */
.ua-field {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:10px;
    padding:14px 18px; margin-bottom:10px; transition:border-color .15s;
}
.ua-field.missing { border-left:3px solid #d97706; background:rgba(217,119,6,.04); }
.ua-field.editable { border-left:3px solid #c8102e; }
.ua-field-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.ua-field-label {
    flex:1; min-width:240px;
}
.ua-field-label .label-de { font-size:13.5px; font-weight:700; color:var(--u-text); display:block; margin-bottom:2px; }
.ua-field-label .label-tr { font-size:11.5px; color:var(--u-muted); }
.ua-field-label .req { color:#c8102e; }
.ua-field-value {
    flex:1.4; min-width:300px;
    display:flex; align-items:center; gap:8px;
}
.ua-value-text {
    flex:1; padding:9px 14px; background:var(--u-bg); border:1px solid var(--u-line);
    border-radius:7px; font-family:monospace; font-size:13.5px; color:var(--u-text);
    word-break:break-word;
}
.ua-value-text.empty { color:#94a3b8; font-style:italic; font-family:inherit; }
.ua-copy-btn {
    padding:9px 14px; background:#c8102e; color:#fff; border:none; border-radius:7px;
    font-size:12px; font-weight:700; cursor:pointer; flex-shrink:0; min-width:90px;
    transition:background .15s;
}
.ua-copy-btn:hover { background:#9f1239; }
.ua-copy-btn:disabled { background:#cbd5e1; cursor:not-allowed; color:#475569; }
.ua-copy-btn.copied { background:#16a34a; }
.ua-field-hint {
    font-size:11px; color:var(--u-muted); margin-top:6px; line-height:1.5;
    background:var(--u-bg); padding:6px 10px; border-radius:5px;
    border-left:2px solid var(--u-line);
}
.ua-source-tag {
    display:inline-block; background:rgba(126,88,191,.1); color:#5a3a8d;
    padding:2px 8px; border-radius:999px; font-size:10px; font-weight:700;
    text-transform:uppercase; letter-spacing:.5px;
}

/* Editable input (BID/BAN için) */
.ua-meta-form { display:flex; gap:8px; align-items:center; }
.ua-meta-input {
    flex:1; padding:8px 12px; border:1px solid var(--u-line); border-radius:7px;
    font-family:monospace; font-size:13px; background:#fff; color:var(--u-text);
}
.ua-meta-input:focus { border-color:#c8102e; outline:none; box-shadow:0 0 0 3px rgba(200,16,46,.15); }
.ua-meta-save {
    padding:8px 14px; background:#c8102e; color:#fff; border:none; border-radius:7px;
    font-size:12px; font-weight:700; cursor:pointer;
}
.ua-meta-save:hover { background:#9f1239; }

/* Eksik alanlar modal/panel */
.ua-missing-panel {
    background:linear-gradient(135deg,rgba(217,119,6,.06),rgba(217,119,6,.02));
    border:1px solid rgba(217,119,6,.4); border-radius:12px;
    padding:18px 22px; margin-bottom:20px;
}
.ua-missing-panel h3 { margin:0 0 10px; color:rgb(120,53,15); font-size:14px; text-transform:uppercase; letter-spacing:.5px; }
.ua-missing-list { list-style:none; padding:0; margin:0 0 14px; }
.ua-missing-list li { padding:5px 0; font-size:13px; color:rgb(120,53,15); }
.ua-missing-list li::before { content:'⚠ '; color:#d97706; font-weight:800; }
.ua-missing-actions { display:flex; gap:10px; flex-wrap:wrap; }
.ua-missing-btn {
    padding:9px 16px; border:none; border-radius:8px; cursor:pointer;
    font-size:13px; font-weight:700; text-decoration:none;
}
.ua-missing-btn.mail { background:#d97706; color:#fff; }
.ua-missing-btn.mail:hover { background:rgb(180,83,9); }
.ua-missing-btn.doc-link { background:#5a3a8d; color:#fff; }
.ua-missing-btn.doc-link:hover { background:#4c2d75; }
.ua-missing-btn:disabled { background:#cbd5e1; cursor:not-allowed; color:#64748b; }

.ua-tab-meta { font-size:11.5px; color:var(--u-muted); margin-bottom:14px; }
</style>
@endpush

@section('content')
<div class="ua-wrap">

    @if(session('success'))<div style="background:rgba(22,163,74,.08); color:#15803d; border:1px solid rgba(22,163,74,.3); padding:10px 14px; border-radius:10px; margin-bottom:14px;">✅ {{ session('success') }}</div>@endif
    @if($errors->any())
        <div style="background:rgba(220,38,38,.08); color:rgb(185,28,28); border:1px solid rgba(220,38,38,.3); padding:10px 14px; border-radius:10px; margin-bottom:14px;">
            @foreach($errors->all() as $e) ⚠ {{ $e }}<br> @endforeach
        </div>
    @endif

    <div class="ua-banner">
        <div class="ua-banner-icon">🎓</div>
        <div>
            <div class="ua-banner-title">Uni-Assist "Mein Konto" — Sıralı Doldurma Rehberi</div>
            <div class="ua-banner-sub">Aşağıdaki 4 sekme Uni-Assist portal'ının birebir kopyasıdır. Her alanın yanındaki <strong>"Kopyala"</strong> butonu ile değer panoya kopyalanır → Uni-Assist'e yapıştır.</div>
        </div>
        <a href="https://my.uni-assist.de/registration" target="_blank" rel="noopener" class="ua-banner-link">Uni-Assist Aç →</a>
    </div>

    @if(count($missing) > 0)
        <div class="ua-missing-panel">
            <h3>⚠ {{ count($missing) }} eksik alan var — başvuruya başlamadan önce tamamla</h3>
            <ul class="ua-missing-list">
                @foreach($missing as $m)
                    <li><strong>{{ $m['label'] }}</strong> ({{ $m['label_tr'] }}) — <em>{{ $m['tab'] }}</em> sekmesinde</li>
                @endforeach
            </ul>
            <div class="ua-missing-actions">
                <button type="button" class="ua-missing-btn mail" onclick="document.getElementById('ua-mail-modal').showModal()">
                    📧 Öğrenciye Mail Gönder ({{ count($missing) }} alan)
                </button>
                @if($canRequestDocs)
                    {{-- Uni-Assist Rehberi student aşamasında — student belge talep route'u kullanılır --}}
                    @if($studentId)
                        <a class="ua-missing-btn doc-link" href="{{ route('manager.student.document-tokens.index', $studentId) }}">
                            🔗 Belge Talep Linki Oluştur (Premium)
                        </a>
                    @else
                        {{-- Legacy fallback: guest aşamasında çağrı (geriye uyumluluk) --}}
                        <a class="ua-missing-btn doc-link" href="{{ route('manager.guest.document-tokens.index', $guest) }}">
                            🔗 Belge Talep Linki Oluştur (Premium)
                        </a>
                    @endif
                @else
                    <button type="button" class="ua-missing-btn doc-link" disabled title="Bu yetki bayide aktif değil">
                        🔒 Belge Talep Linki (Premium)
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ═══ TAB BAR ═══ --}}
    <div class="ua-tabs">
        @foreach($tabs as $tabKey => $tab)
            @php
                $missingInTab = collect($tab['fields'])->where('missing', true)->where('required', true)->count();
            @endphp
            <button type="button" class="ua-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $tabKey }}">
                {{ $tab['title'] }}
                @if($missingInTab > 0)
                    <span class="ua-tab-warn">{{ $missingInTab }}</span>
                @endif
                <small>{{ $tab['title_tr'] }}</small>
            </button>
        @endforeach
    </div>

    {{-- ═══ PANELS ═══ --}}
    @php
        $schulOpts = \App\Services\UniAssistFieldMapperService::SCHULABSCHLUSS_OPTIONS;
        $gesOpts   = \App\Services\UniAssistFieldMapperService::GESCHLECHT_OPTIONS;
        $metaArr   = is_array($guest->application_meta) ? $guest->application_meta : [];
    @endphp

    @foreach($tabs as $tabKey => $tab)
        <div class="ua-panel {{ $loop->first ? 'active' : '' }}" data-panel="{{ $tabKey }}">
            <div class="ua-tab-meta">{{ count($tab['fields']) }} alan · Uni-Assist sekmesi: <strong>{{ $tab['title'] }}</strong></div>

            {{-- Persönliche Daten — Geschlecht override --}}
            @if($tabKey === 'persoenliche')
                <form method="POST" action="{{ route('manager.uni-assist-guide.save-meta', $guest) }}" style="margin-bottom:16px;">
                    @csrf
                    <div class="ua-field editable">
                        <div style="font-size:13px; font-weight:700; color:var(--u-text); margin-bottom:8px;">⚙ Cinsiyet (Uni-Assist'in tanıdığı 4 seçenek)</div>
                        <div style="display:grid; grid-template-columns:1fr auto; gap:10px; align-items:flex-end;">
                            <div>
                                <label style="font-size:11px; color:var(--u-muted); font-weight:600; display:block; margin-bottom:4px;">Geschlecht (öğrenci doldurmadıysa elle seç)</label>
                                <select name="meta[geschlecht_de]" class="ua-meta-input">
                                    <option value="">— Auto (öğrenci datası) —</option>
                                    @foreach($gesOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($metaArr['geschlecht_de'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="ua-meta-save">💾 Kaydet</button>
                        </div>
                    </div>
                </form>
            @endif

            {{-- Bildungshistorie — Schulabschluss tipi (8 Türk lisesi seçeneği) --}}
            @if($tabKey === 'bildungs')
                <form method="POST" action="{{ route('manager.uni-assist-guide.save-meta', $guest) }}" style="margin-bottom:16px;">
                    @csrf
                    <div class="ua-field editable">
                        <div style="font-size:13px; font-weight:700; color:var(--u-text); margin-bottom:8px;">⚙ Lise Diploma Türü (Uni-Assist 8 Türk lisesi tanır)</div>
                        <div style="font-size:11.5px; color:var(--u-muted); margin-bottom:10px;">
                            Default: <strong>12-jährige Sekundarschule</strong> (1997 sonrası doğum). Açık lise / İmam Hatip / Meslek Lisesi ise dropdown'dan seç.
                        </div>
                        <div style="display:grid; grid-template-columns:1fr auto; gap:10px; align-items:flex-end;">
                            <div>
                                <label style="font-size:11px; color:var(--u-muted); font-weight:600; display:block; margin-bottom:4px;">Schulabschluss Type</label>
                                <select name="meta[schulabschluss_type]" class="ua-meta-input">
                                    <option value="">— Auto-detect (doğum yılına göre) —</option>
                                    @foreach($schulOpts as $key => $label)
                                        <option value="{{ $key }}" {{ ($metaArr['schulabschluss_type'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="ua-meta-save">💾 Kaydet</button>
                        </div>
                    </div>
                </form>
            @endif

            {{-- BID/BAN editable form (sadece hochschulstart sekmesinde) --}}
            @if($tabKey === 'hochschulstart')
                <form method="POST" action="{{ route('manager.uni-assist-guide.save-meta', $guest) }}" style="margin-bottom:16px;">
                    @csrf
                    @php $meta = is_array($guest->application_meta) ? $guest->application_meta : []; @endphp
                    <div class="ua-field editable">
                        <div style="font-size:13px; font-weight:700; color:var(--u-text); margin-bottom:8px;">⚙ BID/BAN Manuel Kayıt (öğrenciden geldiyse buraya yaz)</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:10px;">
                            <div>
                                <label style="font-size:11px; color:var(--u-muted); font-weight:600; display:block; margin-bottom:4px;">BID (B + 12 hane)</label>
                                <input type="text" name="meta[hochschulstart_bid]" class="ua-meta-input" pattern="^B?\d{12}$" placeholder="B123456789012" value="{{ $meta['hochschulstart_bid'] ?? '' }}">
                            </div>
                            <div>
                                <label style="font-size:11px; color:var(--u-muted); font-weight:600; display:block; margin-bottom:4px;">BAN (6 hane)</label>
                                <input type="text" name="meta[hochschulstart_ban]" class="ua-meta-input" pattern="^\d{6}$" placeholder="123456" value="{{ $meta['hochschulstart_ban'] ?? '' }}">
                            </div>
                            <button type="submit" class="ua-meta-save" style="align-self:flex-end;">💾 Kaydet</button>
                        </div>
                    </div>
                </form>
            @endif

            {{-- KONTAKTDATEN için manager-girdi alanları (city, c/o, address_extra) --}}
            @if($tabKey === 'kontakt')
                <form method="POST" action="{{ route('manager.uni-assist-guide.save-meta', $guest) }}" style="margin-bottom:16px;">
                    @csrf
                    @php $meta = is_array($guest->application_meta) ? $guest->application_meta : []; @endphp
                    <div class="ua-field editable">
                        <div style="font-size:13px; font-weight:700; color:var(--u-text); margin-bottom:8px;">⚙ Manager Manuel Kayıt (sistemde olmayan alanlar)</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:10px;">
                            <div>
                                <label style="font-size:11px; color:var(--u-muted); font-weight:600; display:block; margin-bottom:4px;">Şehir/İlçe (home_city)</label>
                                <input type="text" name="meta[home_city]" class="ua-meta-input" placeholder="Etimesgut/Ankara" value="{{ $meta['home_city'] ?? '' }}">
                            </div>
                            <div>
                                <label style="font-size:11px; color:var(--u-muted); font-weight:600; display:block; margin-bottom:4px;">c/o (varsa)</label>
                                <input type="text" name="meta[co_address]" class="ua-meta-input" placeholder="c/o İsim" value="{{ $meta['co_address'] ?? '' }}">
                            </div>
                            <div>
                                <label style="font-size:11px; color:var(--u-muted); font-weight:600; display:block; margin-bottom:4px;">Apartman/Site</label>
                                <input type="text" name="meta[address_extra]" class="ua-meta-input" placeholder="Mavidogankaya Sitesi B5/36" value="{{ $meta['address_extra'] ?? '' }}">
                            </div>
                        </div>
                        <button type="submit" class="ua-meta-save">💾 Kaydet</button>
                    </div>
                </form>
            @endif

            @foreach($tab['fields'] as $f)
                <div class="ua-field {{ $f['missing'] ? 'missing' : '' }}">
                    <div class="ua-field-row">
                        <div class="ua-field-label">
                            <span class="label-de">{{ $f['label'] }} @if($f['required'])<span class="req">*</span>@endif</span>
                            <span class="label-tr">{{ $f['label_tr'] }}</span>
                            @if($f['format'])
                                <span class="ua-source-tag" style="margin-left:6px;">format: {{ $f['format'] }}</span>
                            @endif
                        </div>
                        <div class="ua-field-value">
                            @if($f['value'] !== null)
                                <span class="ua-value-text">{{ $f['value'] }}</span>
                                <button type="button" class="ua-copy-btn" data-copy="{{ $f['value'] }}">📋 Kopyala</button>
                            @else
                                <span class="ua-value-text empty">— Eksik (öğrenci doldurmamış)</span>
                                <button type="button" class="ua-copy-btn" disabled>—</button>
                            @endif
                        </div>
                    </div>
                    @if($f['hint'])
                        <div class="ua-field-hint">💡 {{ $f['hint'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</div>

{{-- Eksik alan mail modal --}}
<dialog id="ua-mail-modal" style="border:none; border-radius:14px; padding:0; max-width:540px; width:92%; box-shadow:0 24px 60px rgba(0,0,0,.3);">
    <form method="POST" action="{{ route('manager.uni-assist-guide.request-missing', $guest) }}" style="padding:24px;">
        @csrf
        <h2 style="margin:0 0 8px; font-size:20px;">📧 Eksik Alan Maili</h2>
        <p style="font-size:13px; color:var(--u-muted); margin:0 0 18px; line-height:1.5;">
            <strong>{{ $guest->first_name }} {{ $guest->last_name }}</strong> ({{ $guest->email }}) adresine eksik alanlarla ilgili mail gönderilecek.
        </p>
        <div style="background:#fef9e7; border:1px solid #fcd34d; border-radius:8px; padding:12px 14px; font-size:12px; color:#78350f; margin-bottom:14px; line-height:1.55;">
            <strong>📋 Mail içeriğindeki alanlar:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach($missing as $m)
                    <li><input type="checkbox" name="fields[]" value="{{ $m['label'] }} ({{ $m['label_tr'] }})" checked style="margin-right:6px;"> {{ $m['label_tr'] }}</li>
                @endforeach
            </ul>
        </div>
        <div style="margin-bottom:16px;">
            <label style="font-size:12px; font-weight:600; color:var(--u-text); display:block; margin-bottom:4px;">Ek not (opsiyonel)</label>
            <textarea name="note" rows="2" maxlength="1000" style="width:100%; padding:8px 12px; border:1px solid var(--u-line); border-radius:7px; font-family:inherit; font-size:13px;" placeholder="Öğrenciye iletmek istediğin özel bir not..."></textarea>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" onclick="document.getElementById('ua-mail-modal').close()" style="padding:9px 18px; background:var(--u-bg); border:1px solid var(--u-line); border-radius:7px; cursor:pointer; font-weight:600;">İptal</button>
            <button type="submit" style="padding:9px 18px; background:#d97706; color:#fff; border:none; border-radius:7px; cursor:pointer; font-weight:700;">📤 Mail Gönder</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Tab switch
document.querySelectorAll('.ua-tab').forEach(function(t){
    t.addEventListener('click', function(){
        var key = t.getAttribute('data-tab');
        document.querySelectorAll('.ua-tab').forEach(function(tt){ tt.classList.toggle('active', tt === t); });
        document.querySelectorAll('.ua-panel').forEach(function(p){ p.classList.toggle('active', p.getAttribute('data-panel') === key); });
    });
});

// Copy to clipboard
document.querySelectorAll('.ua-copy-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var val = btn.getAttribute('data-copy');
        if (!val) return;
        var done = function(){
            var orig = btn.textContent;
            btn.textContent = '✓ Kopyalandı!';
            btn.classList.add('copied');
            setTimeout(function(){ btn.textContent = orig; btn.classList.remove('copied'); }, 1300);
        };
        if (navigator.clipboard) {
            navigator.clipboard.writeText(val).then(done).catch(function(){
                // fallback
                var ta = document.createElement('textarea'); ta.value = val; document.body.appendChild(ta);
                ta.select(); document.execCommand('copy'); document.body.removeChild(ta); done();
            });
        } else {
            var ta = document.createElement('textarea'); ta.value = val; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy'); document.body.removeChild(ta); done();
        }
    });
});
</script>
@endpush

@endsection

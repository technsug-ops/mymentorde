@extends('manager.layouts.app')

@section('title', $guide['meta']['title'] . ' · ' . $guest->first_name . ' ' . $guest->last_name)
@section('page_title', $guide['meta']['icon'] . ' ' . $guide['meta']['title'])
@section('page_subtitle', $guest->first_name . ' ' . $guest->last_name . ' · ' . ($guest->email ?? '—'))

@push('head')
<style>
.ag-wrap { max-width:1100px; margin:0 auto; }
.ag-banner {
    background:linear-gradient(135deg,{{ $guide['meta']['bg_tint'] }},#fff);
    border:1px solid {{ $guide['meta']['border'] }};
    border-radius:12px; padding:16px 22px; margin-bottom:18px;
    display:flex; align-items:center; gap:16px; flex-wrap:wrap;
}
.ag-banner-icon { font-size:36px; }
.ag-banner-title { font-weight:700; color:{{ $guide['meta']['color'] }}; font-size:15px; }
.ag-banner-sub { font-size:13px; color:#475569; line-height:1.55; margin-top:4px; }

.ag-tabs { display:flex; gap:0; border-bottom:2px solid var(--u-line); margin-bottom:24px; flex-wrap:wrap; }
.ag-tab { padding:12px 18px; cursor:pointer; background:transparent; border:none;
    font-family:inherit; font-size:12.5px; font-weight:600; letter-spacing:.4px;
    color:var(--u-muted); text-transform:uppercase; border-bottom:3px solid transparent;
    margin-bottom:-2px; text-decoration:none; }
.ag-tab.active { color:{{ $guide['meta']['color'] }}; border-bottom-color:{{ $guide['meta']['color'] }}; }

.ag-grid { display:grid; grid-template-columns:1fr 320px; gap:18px; }
@media(max-width:900px){ .ag-grid { grid-template-columns:1fr; } }

.ag-card { background:var(--u-card,#fff); border:1px solid var(--u-line); border-radius:10px; padding:18px 20px; margin-bottom:14px; }
.ag-card h2 { margin:0 0 12px; font-size:14px; font-weight:700; color:{{ $guide['meta']['color'] }}; padding-bottom:8px; border-bottom:1px solid var(--u-line); }
.ag-step { margin-bottom:18px; }
.ag-step h3 { font-size:13px; font-weight:700; color:var(--u-text); margin:0 0 8px; }
.ag-step ul { margin:0; padding:0 0 0 18px; }
.ag-step li { font-size:12.5px; line-height:1.7; color:#334155; margin-bottom:4px; }

.ag-fields { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
@media(max-width:600px){ .ag-fields { grid-template-columns:1fr; } }
.ag-field { background:var(--u-bg,#f8fafc); border:1px solid var(--u-line); border-radius:8px; padding:10px 12px; position:relative; }
.ag-field-lbl { font-size:10.5px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.4px; margin-bottom:3px; }
.ag-field-val { font-size:13px; font-weight:600; color:var(--u-text); word-break:break-word; padding-right:36px; }
.ag-field-val.empty { color:#cbd5e1; font-style:italic; font-weight:400; }
.ag-field-desc { font-size:10.5px; color:var(--u-muted); margin-top:3px; }
.ag-field-copy { position:absolute; top:8px; right:8px; background:#fff; border:1px solid var(--u-line); border-radius:5px; padding:3px 7px; font-size:10.5px; cursor:pointer; color:{{ $guide['meta']['color'] }}; font-weight:600; transition:background .1s; }
.ag-field-copy:hover { background:{{ $guide['meta']['bg_tint'] }}; }
.ag-field-copy.copied { background:#dcfce7; color:#166534; border-color:#86efac; }

.ag-docs { display:flex; flex-direction:column; gap:6px; }
.ag-doc { display:flex; align-items:center; gap:8px; padding:7px 10px; background:var(--u-bg); border:1px solid var(--u-line); border-radius:6px; font-size:12.5px; }
.ag-doc.required::before { content:'🔴'; font-size:10px; }
.ag-doc:not(.required)::before { content:'⚪'; font-size:10px; }
.ag-doc-code { font-family:ui-monospace,monospace; font-size:10.5px; color:var(--u-muted); margin-left:auto; }

.ag-tips { display:flex; flex-direction:column; gap:8px; }
.ag-tip { background:#fffbeb; border-left:3px solid #f59e0b; padding:8px 12px; font-size:12px; color:#78350f; line-height:1.5; border-radius:0 6px 6px 0; }

.ag-links { display:flex; flex-direction:column; gap:6px; }
.ag-link { display:block; padding:9px 12px; background:{{ $guide['meta']['bg_tint'] }}; border:1px solid {{ $guide['meta']['border'] }}; border-radius:6px; color:{{ $guide['meta']['color'] }}; text-decoration:none; font-size:12.5px; font-weight:600; transition:background .1s; }
.ag-link:hover { background:#fff; }
</style>
@endpush

@section('content')
<div class="ag-wrap">

    {{-- Banner --}}
    <div class="ag-banner">
        <div class="ag-banner-icon">{{ $guide['meta']['icon'] }}</div>
        <div style="flex:1;">
            <div class="ag-banner-title">{{ $guide['meta']['title'] }}</div>
            <div class="ag-banner-sub">{!! $guide['meta']['subtitle'] !!}</div>
        </div>
    </div>

    {{-- Sekme bar: tüm guide'lara hızlı geçiş --}}
    <div class="ag-tabs">
        @foreach($allSlugs as $s)
            @php
                $sg = config("application_guides.{$s}");
                $url = $studentId
                    ? route('manager.student.application-guide.show', [$studentId, $s])
                    : route('manager.application-guide.show', [$guest->id, $s]);
            @endphp
            <a href="{{ $url }}" class="ag-tab {{ $s === $slug ? 'active' : '' }}">
                {{ $sg['meta']['icon'] }} {{ $sg['meta']['title'] }}
            </a>
        @endforeach
    </div>

    {{-- Intro --}}
    <div class="ag-card" style="background:{{ $guide['meta']['bg_tint'] }};border-color:{{ $guide['meta']['border'] }};">
        <div style="font-size:13.5px;color:#1f2937;line-height:1.6;">{!! \Illuminate\Support\Str::markdown($guide['intro']) !!}</div>
    </div>

    <div class="ag-grid">
        {{-- SOL: Adımlar + Alanlar --}}
        <div>
            {{-- Adım adım --}}
            <div class="ag-card">
                <h2>📋 Adım Adım Süreç</h2>
                @foreach($guide['sections'] as $idx => $section)
                    <div class="ag-step">
                        <h3>{{ $section['title'] }}</h3>
                        <ul>
                            @foreach($section['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            {{-- Aday bilgileri (kopyala-yapıştır) --}}
            <div class="ag-card">
                <h2>👤 Aday Bilgileri (form'a kopyala)</h2>
                <div class="ag-fields">
                    @foreach($fieldValues as $key => $f)
                        <div class="ag-field">
                            <div class="ag-field-lbl">{{ $f['label'] }}</div>
                            <div class="ag-field-val {{ $f['has'] ? '' : 'empty' }}">{{ $f['value'] }}</div>
                            @if($f['desc'])
                                <div class="ag-field-desc">{{ $f['desc'] }}</div>
                            @endif
                            @if($f['has'])
                                <button type="button" class="ag-field-copy" data-copy="{{ $f['value'] }}">Kopyala</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Önemli ipuçları --}}
            @if(!empty($guide['tips']))
            <div class="ag-card">
                <h2>💡 Önemli İpuçları</h2>
                <div class="ag-tips">
                    @foreach($guide['tips'] as $tip)
                        <div class="ag-tip">{{ $tip }}</div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- SAĞ: Gerekli belgeler + Linkler --}}
        <div>
            {{-- Gerekli belgeler --}}
            <div class="ag-card">
                <h2>📎 Gerekli Belgeler</h2>
                <div class="ag-docs">
                    @foreach($guide['documents'] as $doc)
                        <div class="ag-doc {{ $doc['required'] ? 'required' : '' }}">
                            <span>{{ $doc['name'] }}</span>
                            <span class="ag-doc-code">{{ $doc['code'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div style="font-size:10.5px;color:var(--u-muted);margin-top:8px;">🔴 zorunlu · ⚪ opsiyonel</div>
            </div>

            {{-- Dış linkler --}}
            <div class="ag-card">
                <h2>🔗 Resmi Linkler</h2>
                <div class="ag-links">
                    @foreach($guide['external_links'] as $link)
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="ag-link">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    document.querySelectorAll('[data-copy]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var v = btn.getAttribute('data-copy');
            var fb = function(){
                btn.classList.add('copied');
                var orig = btn.textContent;
                btn.textContent = '✓ Kopyalandı';
                setTimeout(function(){ btn.classList.remove('copied'); btn.textContent = orig; }, 1500);
            };
            if (navigator.clipboard) navigator.clipboard.writeText(v).then(fb);
            else {
                var ta = document.createElement('textarea'); ta.value = v; document.body.appendChild(ta);
                ta.select(); document.execCommand('copy'); document.body.removeChild(ta); fb();
            }
        });
    });
})();
</script>
@endsection

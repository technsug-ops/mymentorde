@extends('manager.layouts.app')

@section('title', 'Başvuru Türüne Göre Alanlar')

@section('content')

{{-- Tek işi olan ekran: hangi alan hangi başvuru türünde görünsün.
     Alan ekleme/silme/sıralama burada YOK — /config'de. --}}

<style>
    .fft-wrap { max-width: 1080px; }
    .fft-note { border:1px solid #e2e8f0; border-left:3px solid #2563eb; border-radius:8px;
                padding:14px 18px; margin-bottom:18px; font-size:13.5px; line-height:1.6; background:#fff; }
    .fft-bar { position:sticky; top:0; z-index:6; background:#f8fafc; border-bottom:1px solid #e2e8f0;
               padding:12px 0; margin-bottom:8px; display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .fft-bar input[type="search"] { flex:1 1 220px; padding:8px 12px; border:1px solid #cbd5e1;
               border-radius:8px; font:inherit; font-size:13.5px; }
    .fft-sec { margin-top:22px; }
    .fft-sec h3 { font-size:15px; font-weight:800; margin:0 0 8px; display:flex; align-items:baseline; gap:9px; }
    .fft-sec h3 code { font-size:11.5px; font-weight:500; color:#64748b; background:none; }
    .fft-table { width:100%; border-collapse:collapse; background:#fff;
                 border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
    .fft-table th { text-align:left; font-size:10.5px; font-weight:800; letter-spacing:.08em;
                 text-transform:uppercase; color:#64748b; padding:9px 12px; background:#f1f5f9;
                 border-bottom:1px solid #e2e8f0; white-space:nowrap; }
    .fft-table td { padding:8px 12px; border-bottom:1px solid #eef2f7; font-size:13.5px; vertical-align:top; }
    .fft-table tr:last-child td { border-bottom:0; }
    .fft-key { font-family:ui-monospace,Consolas,monospace; font-size:12px; color:#334155; white-space:nowrap; }
    .fft-chk { text-align:center; width:112px; }
    .fft-chk input { width:17px; height:17px; cursor:pointer; }
    .fft-badge { display:inline-block; padding:1px 7px; border-radius:999px; font-size:10.5px; font-weight:700; }
    .fft-l1 { background:#dcfce7; color:#166534; }
    .fft-req { background:#fef3c7; color:#92400e; }
    .fft-save { position:sticky; bottom:0; background:#f8fafc; border-top:1px solid #e2e8f0;
                padding:12px 0; margin-top:22px; display:flex; gap:12px; align-items:center; }
    .fft-hidden { display:none; }
</style>

<div class="fft-wrap">

    <h1 style="font-size:22px;font-weight:800;margin:0 0 6px;">Başvuru Türüne Göre Alanlar</h1>
    <p style="color:#64748b;font-size:13.5px;margin:0 0 16px;max-width:70ch;line-height:1.6;">
        Aday <code>/apply</code>'da başvuru tipini seçiyor; form buna göre daralıyor.
        Ayrı form yok — tek tanım, alan bazında etiket.
    </p>

    @if(session('status'))
        <div style="border:1px solid rgba(22,163,74,.4);background:rgba(22,163,74,.08);border-radius:9px;padding:11px 14px;margin-bottom:14px;font-size:13.5px;">
            {{ session('status') }}
        </div>
    @endif

    <div class="fft-note">
        <strong>Hiçbiri işaretli değilse alan her türde görünür.</strong>
        Yani yalnızca <em>ayrışan</em> alanları işaretlemeniz yeterli; ortak alanlara dokunmayın.
        Şu an {{ $fieldCount }} alandan <strong>{{ $taggedCount }}</strong> tanesi etiketli.
        @unless($ownsRows)
            <br><span style="color:#b45309;">⚠ Firmanızın kendi form satırları yok; fabrika şablonu gösteriliyor.
            Kaydettiğinizde merkezî şablon değişir.</span>
        @endunless
    </div>

    <form method="POST" action="{{ route('manager.form-field-types.update') }}">
        @csrf

        <div class="fft-bar">
            <input type="search" id="fftSearch" placeholder="Alan ara…" aria-label="Alan ara">
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;">
                <input type="checkbox" id="fftOnlyL1"> Sadece ilk form (Level 1)
            </label>
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;">
                <input type="checkbox" id="fftOnlyTagged"> Sadece etiketliler
            </label>
            <span id="fftCount" style="margin-left:auto;font-size:12.5px;color:#64748b;"></span>
        </div>

        @foreach($sections as $sectionKey => $rows)
            @php $first = $rows->first(); @endphp
            <div class="fft-sec" data-fft-section>
                <h3>
                    {{ $first->section_title ?: $sectionKey }}
                    <code>{{ $sectionKey }}</code>
                </h3>
                <table class="fft-table">
                    <thead>
                        <tr>
                            <th>Alan</th>
                            <th>Soru</th>
                            @foreach($types as $code => $label)
                                <th class="fft-chk">{{ \Illuminate\Support\Str::before($label, ' (') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $field)
                            @php
                                $tags = is_array($field->applicable_types) ? $field->applicable_types : [];
                                $isL1 = in_array((string) $field->field_key, $level1Keys, true);
                            @endphp
                            <tr data-fft-row
                                data-search="{{ mb_strtolower($field->field_key . ' ' . $field->label) }}"
                                data-l1="{{ $isL1 ? '1' : '0' }}"
                                data-tagged="{{ $tags ? '1' : '0' }}">
                                <td>
                                    <span class="fft-key">{{ $field->field_key }}</span>
                                    @if($isL1)
                                        <span class="fft-badge fft-l1" style="margin-left:5px;">L1</span>
                                    @endif
                                </td>
                                <td>
                                    {{ \Illuminate\Support\Str::of($field->label)->rtrim(' *') }}
                                    @if($field->is_required)
                                        <span class="fft-badge fft-req" style="margin-left:5px;">Zorunlu</span>
                                    @endif
                                </td>
                                @foreach($types as $code => $label)
                                    <td class="fft-chk">
                                        <input type="checkbox"
                                               name="types[{{ $field->id }}][]"
                                               value="{{ $code }}"
                                               aria-label="{{ $field->field_key }} — {{ $label }}"
                                               {{ in_array($code, $tags, true) ? 'checked' : '' }}>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="fft-save">
            <button type="submit" class="btn btn-primary">Kaydet</button>
            <span style="font-size:12.5px;color:#64748b;">
                Tüm bölümler tek seferde kaydedilir.
            </span>
        </div>
    </form>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    'use strict';

    var search     = document.getElementById('fftSearch');
    var onlyL1     = document.getElementById('fftOnlyL1');
    var onlyTagged = document.getElementById('fftOnlyTagged');
    var counter    = document.getElementById('fftCount');
    var rows       = Array.prototype.slice.call(document.querySelectorAll('[data-fft-row]'));
    var sections   = Array.prototype.slice.call(document.querySelectorAll('[data-fft-section]'));

    // ⚠ Süzgeç satırı GİZLİYOR, formdan çıkarmıyor. Kutular DOM'da kaldığı
    // için gizli bir alanın etiketi kaydederken silinmiyor.
    function apply() {
        var q = (search.value || '').toLocaleLowerCase('tr');
        var shown = 0;

        rows.forEach(function (row) {
            var ok = true;

            if (q && row.dataset.search.indexOf(q) === -1) { ok = false; }
            if (onlyL1.checked && row.dataset.l1 !== '1') { ok = false; }
            if (onlyTagged.checked && row.dataset.tagged !== '1') { ok = false; }

            row.classList.toggle('fft-hidden', !ok);
            if (ok) { shown++; }
        });

        // Tamamı gizlenen bölümün başlığı boş durmasın.
        sections.forEach(function (sec) {
            var visible = sec.querySelectorAll('[data-fft-row]:not(.fft-hidden)').length;
            sec.classList.toggle('fft-hidden', visible === 0);
        });

        counter.textContent = shown + ' / ' + rows.length + ' alan';
    }

    search.addEventListener('input', apply);
    onlyL1.addEventListener('change', apply);
    onlyTagged.addEventListener('change', apply);

    apply();
})();
</script>

@endsection

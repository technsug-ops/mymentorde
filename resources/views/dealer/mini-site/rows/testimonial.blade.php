@php
    $inp = 'width:100%;padding:9px 11px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:13px;';
    $box = 'border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:12px;margin-top:12px;background:var(--bg,#f8fafc);';
@endphp
<div style="{{ $box }}" data-row>
    @include('dealer.mini-site.rows._head', ['label' => 'Yorum'])
    <textarea name="site_testimonials[{{ $i }}][text]" rows="2" maxlength="600" placeholder="Yorum metni" style="{{ $inp }}resize:vertical;">{{ $row['text'] ?? '' }}</textarea>
    <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:8px;margin-top:8px;">
        <input type="text" name="site_testimonials[{{ $i }}][name]"   value="{{ $row['name'] ?? '' }}"   maxlength="80"  placeholder="Öğrenci adı (örn. Elif K.)" style="{{ $inp }}">
        <input type="text" name="site_testimonials[{{ $i }}][school]" value="{{ $row['school'] ?? '' }}" maxlength="120" placeholder="Üniversite / program (opsiyonel)" style="{{ $inp }}">
    </div>
</div>

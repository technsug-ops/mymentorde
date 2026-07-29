@php
    $inp = 'width:100%;padding:9px 11px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:13px;';
    $box = 'border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:12px;margin-top:12px;background:var(--bg,#f8fafc);';
@endphp
<div style="{{ $box }}" data-row>
    @include('dealer.mini-site.rows._head', ['label' => 'Soru'])
    <input type="text" name="site_faq[{{ $i }}][q]" value="{{ $row['q'] ?? '' }}" maxlength="200" placeholder="Soru" style="{{ $inp }}">
    <textarea name="site_faq[{{ $i }}][a]" rows="2" maxlength="1000" placeholder="Cevap" style="{{ $inp }}margin-top:8px;resize:vertical;">{{ $row['a'] ?? '' }}</textarea>
</div>

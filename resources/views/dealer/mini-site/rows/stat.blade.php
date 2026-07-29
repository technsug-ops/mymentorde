@php
    $inp = 'width:100%;padding:9px 11px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:13px;';
    $box = 'border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:12px;margin-top:10px;background:var(--bg,#f8fafc);';
@endphp
<div style="{{ $box }}" data-row>
    @include('dealer.mini-site.rows._head', ['label' => 'İstatistik'])
    <div style="display:grid;grid-template-columns:.7fr 1.3fr;gap:8px;">
        <input type="text" name="site_stats[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" maxlength="40" placeholder="500+" style="{{ $inp }}">
        <input type="text" name="site_stats[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" maxlength="60" placeholder="Etiket (örn. Mutlu Öğrenci)" style="{{ $inp }}">
    </div>
</div>

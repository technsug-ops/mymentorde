@php
    $inp = 'width:100%;padding:9px 11px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:13px;';
    $box = 'border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:12px;margin-top:10px;background:var(--bg,#f8fafc);';
@endphp
<div style="{{ $box }}" data-row>
    @include('dealer.mini-site.rows._head', ['label' => 'Ekip üyesi'])
    <div style="display:grid;grid-template-columns:1fr 1fr 1.4fr;gap:8px;">
        <input type="text" name="site_team[{{ $i }}][name]"  value="{{ $row['name'] ?? '' }}"  maxlength="80"  placeholder="İsim Soyisim" style="{{ $inp }}">
        <input type="text" name="site_team[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" maxlength="80"  placeholder="Ünvan" style="{{ $inp }}">
        <input type="url"  name="site_team[{{ $i }}][photo]" value="{{ $row['photo'] ?? '' }}" maxlength="500" placeholder="Fotoğraf URL (opsiyonel)" style="{{ $inp }}">
    </div>
</div>

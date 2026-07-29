@php
    $inp   = 'width:100%;padding:9px 11px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:13px;';
    $box   = 'border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:12px;margin-top:12px;background:var(--bg,#f8fafc);';
    $items = $row['items'] ?? '';
    if (is_array($items)) { $items = implode("\n", $items); }
@endphp
<div style="{{ $box }}" data-row>
    @include('dealer.mini-site.rows._head', ['label' => 'Paket'])
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <input type="text" name="site_packages[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" maxlength="60" placeholder="Paket adı — örn. Standart" style="{{ $inp }}">
        <input type="text" name="site_packages[{{ $i }}][tag]"  value="{{ $row['tag'] ?? '' }}"  maxlength="40" placeholder="Etiket — örn. En çok tercih edilen" style="{{ $inp }}">
    </div>
    <input type="text" name="site_packages[{{ $i }}][desc]" value="{{ $row['desc'] ?? '' }}" maxlength="400" placeholder="Kısa açıklama" style="{{ $inp }}margin-top:8px;">
    <textarea name="site_packages[{{ $i }}][items]" rows="3" maxlength="600" placeholder="Kapsam maddeleri — her satıra bir madde (max 6)" style="{{ $inp }}margin-top:8px;resize:vertical;">{{ $items }}</textarea>
    <label style="display:flex;align-items:center;gap:8px;margin-top:8px;font-size:12.5px;cursor:pointer;color:var(--muted,#64748b);">
        <input type="checkbox" name="site_packages[{{ $i }}][featured]" value="1" @checked(!empty($row['featured'])) style="width:16px;height:16px;cursor:pointer;">
        Bu paketi öne çıkar (vurgulu kart)
    </label>
</div>

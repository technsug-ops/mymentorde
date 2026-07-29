@php
    $inp   = 'width:100%;padding:9px 11px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:13px;';
    $box   = 'border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:12px;margin-top:12px;background:var(--bg,#f8fafc);';
    $items = $row['items'] ?? '';
    if (is_array($items)) { $items = implode("\n", $items); }
    $iconOptions = [
        'cap' => 'Mezuniyet / Üniversite', 'passport' => 'Vize / Pasaport', 'coins' => 'Finans / Ödeme',
        'home' => 'Konaklama / Ev', 'work' => 'Kariyer / İş', 'chart' => 'Takip / Rapor',
        'users' => 'Ekip / Danışman', 'clock' => 'Zaman / Süreç', 'default' => 'Genel (kalkan)',
    ];
@endphp
<div style="{{ $box }}" data-row>
    @include('dealer.mini-site.rows._head', ['label' => 'Hizmet'])
    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:8px;">
        <input type="text" name="site_services[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" maxlength="120" placeholder="Hizmet başlığı" style="{{ $inp }}">
        <select name="site_services[{{ $i }}][icon]" style="{{ $inp }}">
            @foreach($iconOptions as $k => $lbl)
                <option value="{{ $k }}" @selected(($row['icon'] ?? 'default') === $k)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <input type="text" name="site_services[{{ $i }}][desc]" value="{{ $row['desc'] ?? '' }}" maxlength="400" placeholder="Kısa açıklama" style="{{ $inp }}margin-top:8px;">
    <textarea name="site_services[{{ $i }}][items]" rows="3" maxlength="600" placeholder="Kapsam maddeleri — her satıra bir madde (opsiyonel, max 6)" style="{{ $inp }}margin-top:8px;resize:vertical;">{{ $items }}</textarea>
</div>

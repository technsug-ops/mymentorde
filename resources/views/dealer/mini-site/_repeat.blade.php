{{--
    Tekrarlanabilir kart grubu — ekle / sil / ↑ ↓ (sıra = dizideki sıra).

    Parametreler:
      $name      string  input adı öneki (örn. 'site_services')
      $title     string  grup başlığı
      $hint      string  açıklama (HTML izinli değil, düz metin)
      $rows      array   mevcut satırlar (old() veya DB)
      $rowView   string  satır markup partial'ı (dealer.mini-site.rows.*)
      $max       int     en fazla kart
      $addLabel  string  ekle butonu metni
      $min       int     başlangıçta gösterilecek en az boş satır (varsayılan 1)

    JS: edit.blade.php sonundaki tek nonce'lu blok tüm grupları yönetir ([data-repeat]).
--}}
@php
    $rows       = array_values(array_filter((array) ($rows ?? []), 'is_array'));
    $min        = $min ?? 1;
    $sectionBox = $sectionBox ?? 'border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px;margin:22px 0;background:var(--surface,#fff);';
    $btn        = 'border:1px solid var(--border,#cbd5e1);background:var(--surface,#fff);color:var(--muted,#64748b);'
                . 'border-radius:8px;width:28px;height:28px;line-height:1;cursor:pointer;font-size:13px;padding:0;';
    while (count($rows) < $min) {
        $rows[] = [];
    }
@endphp

<div style="{{ $sectionBox }}" data-repeat="{{ $name }}" data-max="{{ $max }}">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <div style="font-weight:600;font-size:14px;margin-bottom:4px;">{{ $title }}</div>
            <small style="color:var(--muted,#64748b);font-size:12px;">{{ $hint }}</small>
        </div>
        <button type="button" data-add
                style="background:var(--theme-accent-dealer,#1E3D6B);color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
            {{ $addLabel ?? '+ Kart ekle' }}
        </button>
    </div>

    <div data-rows>
        @foreach($rows as $i => $row)
            @include($rowView, ['i' => $i, 'row' => $row, 'btn' => $btn])
        @endforeach
    </div>

    {{-- Yeni kart iskeleti: JS klonlar, __I__ yerine sıra numarasını yazar --}}
    <template data-row-tpl>
        @include($rowView, ['i' => '__I__', 'row' => [], 'btn' => $btn])
    </template>
</div>

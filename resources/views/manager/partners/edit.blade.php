@extends('manager.layouts.app')

@section('page_title', $company->brand_name ?: $company->name)
@section('page_subtitle', 'Yetki kısıtları')

@section('content')

<div style="max-width:760px;">

    <a href="{{ route('manager.partners.index') }}"
       style="display:inline-block;font-size:13px;color:var(--u-muted,#64748b);text-decoration:none;margin-bottom:14px;">← Partner firmalar</a>

    <div style="padding:12px 14px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-left:3px solid var(--u-primary,#5b2e91);border-radius:10px;margin-bottom:16px;font-size:13px;line-height:1.6;">
        İşaretlenen yetki bu firmanın <strong>tüm kullanıcılarından</strong> alınır.
        Hiçbiri işaretli değilse firma rolünün verdiği her şeyi yapabilir.
        <br>Koyduğunuz kısıt bu firmanın <strong>altındaki firmaları da</strong> bağlar.
    </div>

    @if($inherited->isNotEmpty())
        <div style="padding:11px 14px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:10px;margin-bottom:16px;font-size:12.5px;line-height:1.6;">
            <strong>Üstten miras gelen kısıtlar:</strong>
            {{ $inherited->map(fn ($c) => \App\Support\PermissionCeiling::RESTRICTABLE[$c]['label'] ?? $c)->implode(', ') }}
            <br>Bunlar daha üstteki bir firmada tanımlı — buradan kaldırılamaz.
        </div>
    @endif

    <form method="POST" action="{{ route('manager.partners.update', $company->id) }}"
          style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:20px;">
        @csrf
        <input type="hidden" name="denied_permission_codes[]" value="">

        @foreach($groups as $groupName => $items)
            <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--u-muted,#64748b);margin:{{ $loop->first ? '0' : '20px' }} 0 10px;">
                {{ $groupName }}
            </div>

            @foreach($items as $code => $meta)
                @php $_isInherited = $inherited->contains($code); @endphp
                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e5e7eb);border-radius:9px;margin-bottom:7px;cursor:{{ $_isInherited ? 'not-allowed' : 'pointer' }};opacity:{{ $_isInherited ? '.6' : '1' }};">
                    <input type="checkbox" name="denied_permission_codes[]" value="{{ $code }}"
                           {{ $own->contains($code) || $_isInherited ? 'checked' : '' }}
                           {{ $_isInherited ? 'disabled' : '' }}
                           style="margin-top:3px;">
                    <span style="font-size:13px;line-height:1.5;">
                        <strong>{{ $meta['label'] }}</strong>
                        <span style="display:block;color:var(--u-muted,#64748b);font-size:12px;">{{ $meta['desc'] }}</span>
                    </span>
                </label>
            @endforeach
        @endforeach

        <button type="submit"
                style="margin-top:16px;height:40px;padding:0 22px;border-radius:10px;border:0;background:var(--u-primary,#5b2e91);color:#fff;font-size:14px;font-weight:600;cursor:pointer;">
            Kısıtları Kaydet
        </button>
    </form>
</div>

@endsection

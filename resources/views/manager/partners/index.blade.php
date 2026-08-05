@extends('manager.layouts.app')

@section('page_title', 'Partner Firmalar')
@section('page_subtitle', 'Altınızdaki firmaların yetki sınırlarını buradan belirlersiniz')

@section('content')

<div style="max-width:900px;">

    @if($children->isEmpty())
        <div style="padding:16px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;font-size:14px;line-height:1.7;">
            Altınızda kayıtlı firma yok.
            <br><span style="color:var(--u-muted,#64748b);font-size:13px;">
                Bir firmanın burada görünmesi için "üst firma" olarak sizin şirketiniz seçilmiş olmalı.
            </span>
        </div>
    @else
        <div style="padding:12px 14px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-left:3px solid var(--u-primary,#5b2e91);border-radius:10px;margin-bottom:16px;font-size:13px;line-height:1.6;">
            Rol yetkiyi <strong>verir</strong>, buradaki kısıtlar <strong>daraltır</strong>.
            Koyduğunuz kısıt firmanın altındaki firmaları da bağlar.
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($children as $child)
                @php $_denied = collect($child->denied_permission_codes ?? []); @endphp
                <a href="{{ route('manager.partners.edit', $child->id) }}"
                   style="display:flex;align-items:center;gap:14px;padding:14px 16px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;text-decoration:none;color:inherit;">
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:15px;">{{ $child->brand_name ?: $child->name }}</div>
                        <div style="font-size:12px;color:var(--u-muted,#64748b);margin-top:2px;">
                            #{{ $child->id }} · {{ $child->code }}
                            @unless($child->is_active) · <span style="color:#b45309;">askıda</span> @endunless
                        </div>
                    </div>
                    <div style="text-align:right;font-size:12px;color:var(--u-muted,#64748b);">
                        @if($_denied->isEmpty())
                            kısıt yok
                        @else
                            <strong style="color:var(--u-primary,#5b2e91);">{{ $_denied->count() }} kısıt</strong>
                        @endif
                    </div>
                    <span style="font-size:18px;color:var(--u-muted,#94a3b8);">›</span>
                </a>
            @endforeach
        </div>
    @endif
</div>

@endsection

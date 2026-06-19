@extends('dealer.layouts.app')

@section('title', 'Alt Bayilerim')
@section('page_title', 'Alt Bayilerim')
@section('page_subtitle', 'Bölge bayisi olarak oluşturduğun alt bayiler ve performansları')

@section('content')

@if(session('status'))
    <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;">
        {{ session('status') }}
    </div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
    <div style="color:var(--muted,#64748b);font-size:13px;">
        Toplam <strong>{{ $rows->count() }}</strong> alt bayi. Alt bayilerinin yönlendirdiği adaylar senin ağında görünür.
    </div>
    <a href="/dealer/sub-dealers/create" class="btn btn-primary"
       style="display:inline-flex;align-items:center;gap:6px;background:var(--theme-accent-dealer,#1E3D6B);color:#fff;padding:10px 18px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;">
        <x-icon name="plus" size="16" /> Yeni Alt Bayi
    </a>
</div>

@if($rows->isEmpty())
    <div style="text-align:center;padding:48px 20px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:14px;color:var(--muted,#64748b);">
        <x-icon name="git-branch" size="32" /><br><br>
        Henüz alt bayin yok. <a href="/dealer/sub-dealers/create" style="color:var(--theme-accent-dealer,#1E3D6B);font-weight:600;">İlk alt bayini oluştur</a>.
    </div>
@else
    <div style="overflow-x:auto;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:14px;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="text-align:left;color:var(--muted,#64748b);font-size:12px;text-transform:uppercase;">
                    <th style="padding:14px 16px;">Alt Bayi</th>
                    <th style="padding:14px 16px;">Kod</th>
                    <th style="padding:14px 16px;text-align:right;">Lead</th>
                    <th style="padding:14px 16px;text-align:right;">Dönüşüm</th>
                    <th style="padding:14px 16px;text-align:right;">Kazandırdı (€)</th>
                    <th style="padding:14px 16px;">Durum</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr style="border-top:1px solid var(--border,#e2e8f0);">
                        <td style="padding:14px 16px;">
                            <strong>{{ $r['dealer']->name }}</strong>
                            <div style="font-size:12px;color:var(--muted,#64748b);">{{ $r['dealer']->email }}</div>
                        </td>
                        <td style="padding:14px 16px;font-family:ui-monospace,monospace;">{{ $r['dealer']->code }}</td>
                        <td style="padding:14px 16px;text-align:right;">{{ $r['leads'] }}</td>
                        <td style="padding:14px 16px;text-align:right;">{{ $r['converted'] }}</td>
                        <td style="padding:14px 16px;text-align:right;">€{{ number_format($r['earned'], 2, ',', '.') }}</td>
                        <td style="padding:14px 16px;">
                            @if($r['dealer']->is_active)
                                <span style="background:#ecfdf5;color:#065f46;padding:3px 10px;border-radius:999px;font-size:12px;">Aktif</span>
                            @else
                                <span style="background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:999px;font-size:12px;">Pasif</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection

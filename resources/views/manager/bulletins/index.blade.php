@extends('manager.layouts.app')
@section('title', 'Duyuru Yönetimi')
@section('page_title', 'Duyuru Yönetimi')

@section('topbar-actions')
<a href="/manager/bulletins/create" class="btn ok" style="font-size:12px;padding:6px 16px;">+ Yeni Duyuru</a>
@endsection

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- Filtre --}}
<form method="GET" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;align-items:center;">
    @foreach([''=>'Tüm Kategoriler'] + \App\Models\CompanyBulletin::$categoryLabels as $val => $lbl)
    <a href="?category={{ $val }}"
       style="padding:5px 14px;border-radius:999px;font-size:12px;font-weight:600;text-decoration:none;
              {{ $category === $val ? 'background:#1e40af;color:#fff;' : 'background:var(--u-bg);color:var(--u-muted);border:1px solid var(--u-line);' }}">
        {{ $lbl }}
    </a>
    @endforeach
</form>

<section class="panel" style="padding:0;overflow:hidden;">
    @if($bulletins->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--u-muted);font-size:13px;">Henüz duyuru yok.</div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead><tr style="background:var(--u-bg);">
                <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Başlık</th>
                <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Kategori</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Pin</th>
                <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Yayın</th>
                <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Bitiş</th>
                <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Hedef</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Okuma</th>
                <th style="padding:10px 14px;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">İşlem</th>
            </tr></thead>
            <tbody>
            @foreach($bulletins as $b)
            @php
                $color    = \App\Models\CompanyBulletin::$categoryColors[$b->category] ?? '#1e40af';
                $catLabel = \App\Models\CompanyBulletin::$categoryLabels[$b->category] ?? $b->category;
                $expired  = $b->isExpired();
            @endphp
            <tr style="border-bottom:1px solid var(--u-line);{{ $expired ? 'opacity:.5;' : '' }}">
                <td style="padding:10px 14px;max-width:280px;">
                    <div style="font-weight:600;color:var(--u-text);">{{ $b->title }}</div>
                    <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">{{ Str::limit($b->body, 60) }}</div>
                </td>
                <td style="padding:10px 14px;">
                    <span style="background:{{ $color }}18;color:{{ $color }};border:1px solid {{ $color }}30;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:700;">{{ $catLabel }}</span>
                </td>
                <td style="padding:10px 14px;text-align:center;">{{ $b->is_pinned ? '📌' : '—' }}</td>
                <td style="padding:10px 14px;font-size:12px;white-space:nowrap;">{{ $b->published_at->format('d.m.Y H:i') }}</td>
                <td style="padding:10px 14px;font-size:12px;white-space:nowrap;color:{{ $expired ? '#dc2626' : 'var(--u-muted)' }};">
                    {{ $b->expires_at ? $b->expires_at->format('d.m.Y') : '—' }}
                    @if($expired) <span style="font-size:10px;">(süresi doldu)</span> @endif
                </td>
                <td style="padding:10px 14px;font-size:11px;color:var(--u-muted);">
                    @if(empty($b->target_roles) && empty($b->target_departments))
                        <span style="color:var(--u-ok);">Herkes</span>
                    @else
                        @if(!empty($b->target_roles))
                        <div>🎭 {{ implode(', ', array_map(fn($r) => match($r){
                            'manager'=>'Manager','senior'=>'Senior','marketing_admin'=>'Mkt.Admin',
                            'marketing_staff'=>'Mkt.Staff','sales_admin'=>'Sales Admin',
                            'sales_staff'=>'Sales Staff','finance_admin'=>'Fin.Admin',
                            'finance_staff'=>'Fin.Staff',default=>$r}, $b->target_roles)) }}</div>
                        @endif
                        @if(!empty($b->target_departments))
                        <div>🏢 {{ implode(', ', $b->target_departments) }}</div>
                        @endif
                    @endif
                </td>
                <td style="padding:10px 14px;text-align:center;font-weight:700;">{{ $b->reads_count }}</td>
                <td style="padding:10px 14px;">
                    <div style="display:flex;gap:4px;">
                        <a href="/manager/bulletins/{{ $b->id }}/analytics" class="btn alt" style="font-size:11px;padding:4px 10px;">📊</a>
                        <a href="/manager/bulletins/{{ $b->id }}/edit" class="btn alt" style="font-size:11px;padding:4px 10px;">Düzenle</a>
                        <form method="POST" action="/manager/bulletins/{{ $b->id }}" onsubmit="return confirm('Sil?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn warn" style="font-size:11px;padding:4px 10px;">Sil</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <div style="padding:10px 16px;">{{ $bulletins->links() }}</div>
    </div>
    @endif
</section>

@endsection

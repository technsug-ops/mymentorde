@extends('manager.layouts.app')

@section('title', 'Personel Detayı')
@section('page_title', 'Personel Detayı')

@section('content')

<div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;">
    <a href="/manager/staff" style="font-size:var(--tx-sm);color:#7c3aed;font-weight:700;text-decoration:none;">← Personel Listesi</a>
    <a href="/manager/hr/persons/{{ $user->id }}" class="btn ok" style="font-size:11px;padding:5px 14px;">🪪 HR Kartını Aç</a>
</div>

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:var(--tx-base);">Hesap Bilgileri</div>
        <div style="display:flex;gap:6px;">
            <a href="/manager/staff/{{ $user->id }}/edit" class="btn alt" style="font-size:11px;padding:4px 12px;">Düzenle</a>
            <form method="POST" action="/manager/staff/{{ $user->id }}/toggle" style="display:inline;">
                @csrf
                <button type="submit" class="btn {{ $user->is_active ? 'warn' : 'ok' }}" style="font-size:11px;padding:4px 12px;">
                    {{ $user->is_active ? 'Pasif Yap' : 'Aktif Et' }}
                </button>
            </form>
        </div>
    </div>

    <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
        <tr><td style="padding:6px 0;color:var(--u-muted);width:130px;">Ad Soyad</td><td><strong>{{ $user->name ?: '—' }}</strong></td></tr>
        <tr><td style="padding:6px 0;color:var(--u-muted);">E-posta</td><td>{{ $user->email }}</td></tr>
        <tr><td style="padding:6px 0;color:var(--u-muted);">Departman</td><td><span class="badge info">{{ $dept }}</span></td></tr>
        <tr><td style="padding:6px 0;color:var(--u-muted);">Tür</td>
            <td>
                @if($isAdmin)
                    <span class="badge warn">Yönetici</span>
                @else
                    <span class="badge">Personel</span>
                @endif
            </td>
        </tr>
        <tr><td style="padding:6px 0;color:var(--u-muted);">Rol (Sistem)</td><td style="font-size:11px;color:var(--u-muted);font-family:monospace;">{{ $user->role }}</td></tr>
        <tr><td style="padding:6px 0;color:var(--u-muted);">Durum</td>
            <td>
                @if($user->is_active)
                    <span class="badge ok">Aktif</span>
                @else
                    <span class="badge danger">Pasif</span>
                @endif
            </td>
        </tr>
        <tr><td style="padding:6px 0;color:var(--u-muted);">Kayıt Tarihi</td><td>{{ optional($user->created_at)->format('d.m.Y H:i') }}</td></tr>
    </table>

    <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--u-line);display:flex;gap:8px;flex-wrap:wrap;">
        <a href="/manager/hr/persons/{{ $user->id }}?tab=kpi" style="padding:6px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--u-line);border-radius:8px;text-decoration:none;color:var(--u-text);background:var(--u-bg);">📊 KPI Performansı</a>
        <a href="/manager/hr/persons/{{ $user->id }}?tab=contracts" style="padding:6px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--u-line);border-radius:8px;text-decoration:none;color:var(--u-text);background:var(--u-bg);">📄 Sözleşmeler</a>
        <a href="/manager/hr/persons/{{ $user->id }}?tab=leaves" style="padding:6px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--u-line);border-radius:8px;text-decoration:none;color:var(--u-text);background:var(--u-bg);">🌴 İzinler</a>
        <a href="/manager/hr/persons/{{ $user->id }}?tab=certs" style="padding:6px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--u-line);border-radius:8px;text-decoration:none;color:var(--u-text);background:var(--u-bg);">🎓 Sertifikalar</a>
    </div>
</div>

@endsection

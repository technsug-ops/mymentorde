@extends('manager.layouts.app')

@section('title', 'Çalışan Dizini')
@section('page_title', 'Çalışan Dizini')

@section('content')

{{-- Filtreler --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center;">
    <div style="display:flex;gap:4px;">
        @foreach([['all','Hepsi',$total['all']],['staff','Staff',$total['staff']],['senior','Eğitim Danışmanı',$total['senior']],['manager','Manager',$total['manager']]] as [$key,$label,$cnt])
        <a href="/manager/hr/persons?type={{ $key }}{{ $search ? '&q='.urlencode($search) : '' }}"
           style="padding:5px 12px;font-size:11px;font-weight:700;border-radius:7px;text-decoration:none;border:1.5px solid {{ $typeFilter===$key ? '#1e40af' : 'var(--u-line)' }};background:{{ $typeFilter===$key ? '#1e40af' : 'var(--u-card)' }};color:{{ $typeFilter===$key ? '#fff' : 'var(--u-muted)' }};">
            {{ $label }} <span style="opacity:.75;">({{ $cnt }})</span>
        </a>
        @endforeach
    </div>
    <form method="GET" action="/manager/hr/persons" style="display:flex;gap:6px;margin-left:auto;">
        <input type="hidden" name="type" value="{{ $typeFilter }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="İsim veya e-posta ara…"
               style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);width:200px;">
        <button type="submit" class="btn alt" style="font-size:11px;padding:6px 12px;">Ara</button>
    </form>
</div>

{{-- Tablo --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        @if($employees->isEmpty())
        <div style="padding:40px;text-align:center;color:var(--u-muted);font-size:13px;">Çalışan bulunamadı.</div>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:var(--u-bg);">
                    <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;">Ad Soyad</th>
                    <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;">Rol</th>
                    <th style="padding:8px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;">Durum</th>
                    <th style="padding:8px 12px;"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($employees as $emp)
            @php
                $roleLabels = [
                    'manager'=>'Manager','senior'=>'Eğitim Danışmanı',
                    'system_admin'=>'Sistem — Yönetici','system_staff'=>'Sistem — Personel',
                    'operations_admin'=>'Operasyon — Yönetici','operations_staff'=>'Operasyon — Personel',
                    'finance_admin'=>'Finans — Yönetici','finance_staff'=>'Finans — Personel',
                    'marketing_admin'=>'Pazarlama — Yönetici','marketing_staff'=>'Pazarlama — Personel',
                    'sales_admin'=>'Satış — Yönetici','sales_staff'=>'Satış — Personel',
                ];
                $roleBadge = match(true) {
                    $emp->role === 'manager' => 'warn',
                    $emp->role === 'senior'  => 'info',
                    str_ends_with($emp->role, '_admin') => '',
                    default => '',
                };
            @endphp
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:10px 12px;">
                    <div style="font-weight:700;color:var(--u-text);">{{ $emp->name ?: '—' }}</div>
                    <div style="font-size:10px;color:var(--u-muted);">{{ $emp->email }}</div>
                </td>
                <td style="padding:10px 12px;">
                    <span class="badge {{ $roleBadge }}" style="font-size:10px;">{{ $roleLabels[$emp->role] ?? $emp->role }}</span>
                </td>
                <td style="padding:10px 12px;text-align:center;">
                    <span class="badge {{ $emp->is_active ? 'ok' : 'danger' }}" style="font-size:10px;">{{ $emp->is_active ? 'Aktif' : 'Pasif' }}</span>
                </td>
                <td style="padding:10px 12px;text-align:right;">
                    <a href="/manager/hr/persons/{{ $emp->id }}"
                       style="display:inline-block;padding:4px 10px;font-size:11px;font-weight:600;color:#1e40af;border:1px solid rgba(30,64,175,.3);border-radius:6px;background:rgba(30,64,175,.05);text-decoration:none;">
                        Kişi Kartı →
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</section>

@endsection

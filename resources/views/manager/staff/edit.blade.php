@extends('manager.layouts.app')

@section('title', 'Personel Düzenle')
@section('page_title', 'Personel Düzenle')

@section('content')

<div style="margin-bottom:12px;">
    <a href="/manager/staff/{{ $user->id }}" style="font-size:var(--tx-sm);color:#7c3aed;font-weight:700;text-decoration:none;">← Geri</a>
</div>

<div style="max-width:560px;">
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:24px;">
    <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:20px;">Personel Bilgilerini Düzenle</div>

    @if($errors->any())
    <div style="margin-bottom:14px;padding:10px 14px;border-radius:8px;background:#fef2f2;color:#dc2626;font-size:12px;border:1px solid #fecaca;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="/manager/staff/{{ $user->id }}">
        @csrf
        @method('PUT')

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">Ad Soyad *</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                   style="width:100%;padding:9px 12px;border:2px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='var(--u-line)'">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">E-posta</label>
            <input type="email" value="{{ $user->email }}" disabled
                   style="width:100%;padding:9px 12px;border:2px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-muted);box-sizing:border-box;cursor:not-allowed;">
            <div style="font-size:11px;color:var(--u-muted);margin-top:3px;">E-posta değiştirilemez.</div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">Departman & Tür *</label>
            <select name="role" required
                    style="width:100%;padding:9px 12px;border:2px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='var(--u-line)'">
                @foreach($deptLabels as $deptKey => $deptName)
                <optgroup label="{{ $deptName }}">
                    @foreach($deptMap[$deptKey] as $roleVal)
                    <option value="{{ $roleVal }}" @selected(old('role', $user->role) === $roleVal)>
                        {{ $roleLabels[$roleVal] ?? $roleVal }}
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                       style="width:16px;height:16px;">
                <span style="font-size:13px;font-weight:600;color:var(--u-text);">Aktif</span>
            </label>
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn ok" style="padding:9px 20px;">Kaydet</button>
            <a href="/manager/staff/{{ $user->id }}" class="btn alt" style="padding:9px 16px;">İptal</a>
        </div>
    </form>
</div>
</div>

@endsection

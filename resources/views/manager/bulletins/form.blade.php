@extends('manager.layouts.app')
@section('title', $bulletin ? 'Duyuru Düzenle' : 'Yeni Duyuru')
@section('page_title', $bulletin ? 'Duyuru Düzenle' : 'Yeni Duyuru')

@section('content')
<div style="max-width:680px;">

@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#991b1b;margin-bottom:16px;font-size:13px;">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<section class="panel" style="padding:24px 28px;">
    <form method="POST" action="{{ $bulletin ? '/manager/bulletins/'.$bulletin->id : '/manager/bulletins' }}">
        @csrf
        @if($bulletin) @method('PUT') @endif

        <div style="display:flex;flex-direction:column;gap:16px;">

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Başlık *</label>
                <input type="text" name="title" value="{{ old('title', $bulletin?->title) }}" required maxlength="200"
                       style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:14px;background:var(--u-card);color:var(--u-text);">
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">İçerik *</label>
                <textarea name="body" required rows="7" maxlength="5000"
                          style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:14px;background:var(--u-card);color:var(--u-text);font-family:inherit;resize:vertical;">{{ old('body', $bulletin?->body) }}</textarea>
                <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Satır sonları korunur. Maks. 5000 karakter.</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Kategori *</label>
                    <select name="category" required
                            style="width:100%;padding:9px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);">
                        @foreach(\App\Models\CompanyBulletin::$categoryLabels as $val => $lbl)
                        <option value="{{ $val }}" @selected(old('category', $bulletin?->category) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Yayın Tarihi *</label>
                    <input type="datetime-local" name="published_at"
                           value="{{ old('published_at', $bulletin ? $bulletin->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                           required
                           style="width:100%;box-sizing:border-box;padding:9px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Bitiş Tarihi <span style="font-weight:400;">(opsiyonel)</span></label>
                    <input type="datetime-local" name="expires_at"
                           value="{{ old('expires_at', $bulletin?->expires_at?->format('Y-m-d\TH:i')) }}"
                           style="width:100%;box-sizing:border-box;padding:9px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);">
                    <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Boş bırakılırsa süresiz yayında kalır.</div>
                </div>
                <div style="display:flex;align-items:flex-end;padding-bottom:4px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--u-text);">
                        <input type="checkbox" name="is_pinned" value="1"
                               @checked(old('is_pinned', $bulletin?->is_pinned))
                               style="width:16px;height:16px;">
                        📌 Sayfada sabitle (pinned)
                    </label>
                </div>
            </div>

        </div>

        {{-- Hedef Kitle --}}
        <div style="margin-top:20px;padding-top:18px;border-top:1px solid var(--u-line);">
            <div style="font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">
                🎯 Hedef Kitle <span style="font-size:11px;font-weight:400;text-transform:none;">(Boş bırakılırsa tüm çalışanlara gösterilir)</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--u-muted);margin-bottom:6px;">Roller</label>
                    @php
                        $allRoles = [
                            'manager'          => 'Manager',
                            'senior'           => 'Eğitim Danışmanı',
                            'marketing_admin'  => 'Marketing Admin',
                            'marketing_staff'  => 'Marketing Staff',
                            'sales_admin'      => 'Sales Admin',
                            'sales_staff'      => 'Sales Staff',
                            'finance_admin'    => 'Finance Admin',
                            'finance_staff'    => 'Finance Staff',
                            'operations_admin' => 'Operations Admin',
                            'operations_staff' => 'Operations Staff',
                            'system_admin'     => 'System Admin',
                        ];
                        $selRoles = old('target_roles', $bulletin?->target_roles ?? []);
                    @endphp
                    <select name="target_roles[]" multiple size="6"
                            style="width:100%;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);padding:6px;">
                        @foreach($allRoles as $val => $lbl)
                        <option value="{{ $val }}" @selected(is_array($selRoles) && in_array($val, $selRoles, true))>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Ctrl+tıkla ile çoklu seçim yapabilirsiniz.</div>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--u-muted);margin-bottom:6px;">Departmanlar</label>
                    @php
                        $allDepts = [
                            'marketing'  => 'Pazarlama',
                            'sales'      => 'Satış',
                            'finance'    => 'Finans',
                            'operations' => 'Operasyon',
                            'hr'         => 'İnsan Kaynakları',
                            'it'         => 'Bilgi Teknolojileri',
                            'management' => 'Yönetim',
                        ];
                        $selDepts = old('target_departments', $bulletin?->target_departments ?? []);
                    @endphp
                    <select name="target_departments[]" multiple size="6"
                            style="width:100%;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);padding:6px;">
                        @foreach($allDepts as $val => $lbl)
                        <option value="{{ $val }}" @selected(is_array($selDepts) && in_array($val, $selDepts, true))>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Ctrl+tıkla ile çoklu seçim yapabilirsiniz.</div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:8px;margin-top:22px;padding-top:16px;border-top:1px solid var(--u-line);">
            <button type="submit" class="btn ok">{{ $bulletin ? 'Güncelle' : 'Yayınla' }}</button>
            <a href="/manager/bulletins" class="btn alt">İptal</a>
        </div>
    </form>
</section>

</div>
@endsection

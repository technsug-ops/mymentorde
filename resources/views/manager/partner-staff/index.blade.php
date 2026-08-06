@extends('manager.layouts.app')

@section('title', 'Kullanıcılar')
@section('page_title', 'Kullanıcılar')

@push('head')
<style>
.ps-note  { background:rgba(30,64,175,.05); border:1px solid rgba(30,64,175,.2); border-left:3px solid #1e40af; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:12px; line-height:1.55; }
.ps-err   { background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.25); border-left:3px solid #dc2626; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:12px; line-height:1.55; }
.ps-ok    { background:rgba(22,163,74,.06); border:1px solid rgba(22,163,74,.25); border-left:3px solid #16a34a; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:12px; line-height:1.55; }
.ps-quota { display:flex; align-items:baseline; gap:8px; margin-bottom:12px; }
.ps-quota-val { font-size:22px; font-weight:800; }
.ps-quota-lbl { font-size:11px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
.ps-table { width:100%; border-collapse:collapse; font-size:12px; }
.ps-table thead tr { background:var(--bg,#f8fafc); }
.ps-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.ps-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.ps-table td { padding:8px 10px; vertical-align:middle; }
.ps-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.ps-on  { background:rgba(22,163,74,.1); color:#15803d; }
.ps-off { background:rgba(100,116,139,.12); color:#475569; }
.ps-btn { padding:4px 10px; font-size:11px; font-weight:600; color:#1e40af; border:1px solid rgba(30,64,175,.3); border-radius:6px; background:rgba(30,64,175,.05); cursor:pointer; }
.ps-btn.warn { color:#b45309; border-color:rgba(217,119,6,.35); background:rgba(217,119,6,.06); }
.ps-label { display:block; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
.ps-muted { color:var(--muted,#64748b); }
</style>
@endpush

@section('content')

@if(session('status'))
    <div class="ps-ok">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="ps-err">{{ $errors->first() }}</div>
@endif

<div class="ps-note">
    Ekibinizdeki her kişiye <strong>kendi hesabını</strong> açın — tek hesabı paylaşmayın.
    Sistem kimin ne yaptığını e-posta bazında kaydeder; paylaşılan hesapta atama,
    onay ve talep geçmişi tek isme yığılır. Öğrenci ve adaylar bu listeye girmez.
</div>

<section class="panel" style="margin-bottom:12px;">
    <div class="ps-quota">
        <span class="ps-quota-val" style="{{ !$canAdd ? 'color:#b91c1c;' : '' }}">
            {{ $used }}@if($limit !== null) / {{ $limit }} @endif
        </span>
        <span class="ps-quota-lbl">
            @if($limit === null)
                kullanıcı · sınırsız
            @else
                kullanıcı · {{ config("subscription_tiers.{$tier}.label", $tier) }} paketi
            @endif
        </span>
    </div>

    @unless($canAdd)
        <div class="ps-err" style="margin-bottom:0;">
            Paketinizin kullanıcı sınırına ulaştınız. Yeni kişi eklemek için üst pakete geçmeniz gerekiyor.
        </div>
    @endunless
</section>

@if($canAdd)
<section class="panel" style="margin-bottom:12px;">
    <h2 style="font-size:14px;margin-bottom:10px;">Yeni Kullanıcı</h2>

    <form method="POST" action="{{ route('manager.partner-staff.store') }}"
          style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        @csrf
        <div style="display:flex;flex-direction:column;">
            <label class="ps-label">Ad Soyad</label>
            <input name="name" value="{{ old('name') }}" required style="width:200px;">
        </div>
        <div style="display:flex;flex-direction:column;">
            <label class="ps-label">E-posta</label>
            <input name="email" type="email" value="{{ old('email') }}" required style="width:250px;">
        </div>
        <div style="display:flex;flex-direction:column;">
            <label class="ps-label">Rol</label>
            <select name="role" required style="min-width:280px;">
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="font-size:13px;">Ekle</button>
    </form>

    <p class="ps-muted" style="font-size:11.5px;margin-top:10px;line-height:1.6;">
        Geçici şifre üretilir ve e-posta ile gönderilir; kullanıcı ilk girişte kendi şifresini belirler.
        Danışman rolü burada yoktur — danışmanı operasyonu yürüten firma atar.
    </p>
</section>
@endif

<section class="panel">
    <div style="overflow-x:auto;">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Eklendi</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $u)
                    <tr>
                        <td style="font-weight:600;">{{ $u->name ?: '—' }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $roles[$u->role] ?? $u->role }}</td>
                        <td>
                            <span class="ps-badge {{ $u->is_active ? 'ps-on' : 'ps-off' }}">
                                {{ $u->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="ps-muted">{{ $u->created_at?->format('d.m.Y') ?? '—' }}</td>
                        <td style="text-align:right;white-space:nowrap;">
                            <form method="POST" action="{{ route('manager.partner-staff.reset', $u->id) }}" style="display:inline;"
                                  onsubmit="return confirm('{{ $u->email }} için yeni geçici şifre üretilip mail gönderilsin mi?');">
                                @csrf
                                <button type="submit" class="ps-btn">Şifre sıfırla</button>
                            </form>

                            @if((int) $u->id !== (int) auth()->id())
                                <form method="POST" action="{{ route('manager.partner-staff.toggle', $u->id) }}" style="display:inline;"
                                      onsubmit="return confirm('{{ $u->is_active ? 'Bu kullanıcı giriş yapamayacak. Devam?' : 'Kullanıcı yeniden etkinleştirilsin mi?' }}');">
                                    @csrf
                                    <button type="submit" class="ps-btn warn">{{ $u->is_active ? 'Pasife al' : 'Etkinleştir' }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@endsection

@extends('manager.layouts.app')

@section('page_title', 'Aday Devri')
@section('page_subtitle', 'Yanlış firmaya düşen adayı doğru firmaya taşıyın')

@section('content')

<div style="max-width:680px;">

    <div style="padding:12px 14px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-left:3px solid var(--u-primary,#5b2e91);border-radius:10px;margin-bottom:18px;font-size:13px;line-height:1.6;">
        Firma başvuru linkini kullandıramadığında kayıt genel havuza düşer.
        Adayın <strong>numarasını</strong> girip hedef firmayı seçin.
        <br>Adayın portal hesabı, rıza kaydı, belgeleri ve tüm bağlı kayıtları
        <strong>birlikte taşınır</strong>.
    </div>

    @if(session('status'))
        <div style="padding:11px 14px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;margin-bottom:16px;font-size:13px;">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div style="padding:11px 14px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;margin-bottom:16px;font-size:13px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($companies->count() < 2)
        <div style="padding:16px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;font-size:14px;line-height:1.7;">
            Devir yapabilmek için <strong>en az iki firma</strong> görebilmeniz gerekir.
            <br><span style="color:var(--u-muted,#64748b);font-size:13px;">
                Şu an yalnızca kendi firmanızı görüyorsunuz.
            </span>
        </div>
    @else
        <form method="POST" action="{{ route('manager.leads.transfer') }}"
              style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:20px;">
            @csrf

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Aday Numarası *</label>
                <input type="number" name="application_id" value="{{ old('application_id') }}" required min="1"
                       placeholder="örn. 1423"
                       style="width:180px;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 10px;font-size:13px;">
                <small style="display:block;font-size:11px;color:var(--u-muted,#64748b);margin-top:4px;">
                    Aday listesinde kaydın yanında görünen numara.
                </small>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Hedef Firma *</label>
                <select name="company_id" required
                        style="width:100%;max-width:320px;height:38px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);padding:0 8px;font-size:13px;">
                    <option value="">Firma seçin…</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" @selected((int) old('company_id') === (int) $c->id)>
                            {{ $c->brand_name ?: $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    style="height:40px;padding:0 22px;border-radius:10px;border:0;background:var(--u-primary,#5b2e91);color:#fff;font-size:14px;font-weight:600;cursor:pointer;">
                Devret
            </button>
        </form>

        <p style="font-size:12.5px;color:var(--u-muted,#64748b);line-height:1.7;margin-top:14px;">
            Öğrenciye dönüşmüş kayıtlar buradan devredilemez — sözleşme, ödeme ve belge
            zinciri devreye girdiği için ayrı bir işlem gerekir.
        </p>
    @endif
</div>

@endsection

@extends('manager.layouts.app')

@section('title', 'Bordro Profilleri')
@section('page_title', 'HR — Bordro Profilleri')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

<div style="margin-bottom:16px;font-size:13px;color:var(--u-muted);">
    Her çalışan için brüt maaş, ödeme günü ve banka bilgilerini buradan yönetebilirsiniz.
    Mevcut aktif profili güncellemek yeni bir kayıt oluşturur (geçmiş korunur).
</div>

@if($employees->isEmpty())
<section class="panel" style="padding:40px;text-align:center;">
    <div style="font-size:14px;color:var(--u-muted);">Aktif çalışan bulunamadı.</div>
</section>
@else
<div style="display:flex;flex-direction:column;gap:12px;">
@foreach($employees as $emp)
@php $profile = $profiles->get($emp->id); @endphp
<section class="panel" style="padding:14px 18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:{{ $profile ? '10px' : '0' }};">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--u-brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0;">
                {{ mb_strtoupper(mb_substr($emp->name,0,1)) }}
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--u-text);">{{ $emp->name }}</div>
                <div style="font-size:11px;color:var(--u-muted);">{{ $emp->email }} &nbsp;·&nbsp; {{ $emp->role }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            @if($profile)
            <div style="text-align:right;">
                <div style="font-size:16px;font-weight:800;color:var(--u-text);">
                    {{ number_format($profile->gross_salary, 0, ',', '.') }}
                    <span style="font-size:12px;font-weight:600;color:var(--u-muted);">{{ $profile->currency }}</span>
                </div>
                <div style="font-size:10px;color:var(--u-muted);">Brüt · Her ayın {{ $profile->payment_day }}. günü</div>
            </div>
            @else
            <span style="font-size:11px;color:#dc2626;font-weight:600;background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:3px 10px;">Profil yok</span>
            @endif
            <button type="button" onclick="toggleForm('salary-{{ $emp->id }}')" class="btn {{ $profile ? '' : 'ok' }}" style="font-size:11px;padding:4px 12px;">
                {{ $profile ? '✏️ Güncelle' : '+ Ekle' }}
            </button>
        </div>
    </div>

    @if($profile)
    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:11px;color:var(--u-muted);padding-top:8px;border-top:1px solid var(--u-line);">
        @if($profile->bank_name)<span>🏦 {{ $profile->bank_name }}</span>@endif
        @if($profile->iban)<span>📋 IBAN: {{ Str::mask($profile->iban, '*', 4, strlen($profile->iban)-8) }}</span>@endif
        <span>📅 Geçerlilik: {{ $profile->valid_from->format('d.m.Y') }}</span>
        @if($profile->notes)<span>📝 {{ $profile->notes }}</span>@endif
    </div>
    @endif

    {{-- Ekleme/Güncelleme Formu --}}
    <div id="salary-{{ $emp->id }}" style="display:none;margin-top:14px;padding-top:12px;border-top:1px solid var(--u-line);">
        <form method="POST" action="/manager/hr/salary/{{ $emp->id }}">
            @csrf
            <div class="grid2" style="gap:10px;margin-bottom:12px;">
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Brüt Maaş *</label>
                    <input type="number" name="gross_salary" step="0.01" min="0" required
                           value="{{ $profile?->gross_salary }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Para Birimi *</label>
                    <select name="currency" required style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                        @foreach(['EUR'=>'EUR (€)','TRY'=>'TRY (₺)','USD'=>'USD ($)','GBP'=>'GBP (£)'] as $v => $l)
                        <option value="{{ $v }}" {{ ($profile?->currency ?? 'EUR')===$v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Ödeme Günü *</label>
                    <input type="number" name="payment_day" min="1" max="31" required
                           value="{{ $profile?->payment_day ?? 1 }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Geçerlilik Tarihi *</label>
                    <input type="date" name="valid_from" required
                           value="{{ $profile?->valid_from?->format('Y-m-d') ?? now()->format('Y-m-d') }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Banka</label>
                    <input type="text" name="bank_name" maxlength="100"
                           value="{{ $profile?->bank_name }}"
                           placeholder="ör. Deutsche Bank, Ziraat…"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">IBAN</label>
                    <input type="text" name="iban" maxlength="50"
                           value="{{ $profile?->iban }}"
                           placeholder="DE89 3704 0044 0532 0130 00"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Not</label>
                <input type="text" name="notes" maxlength="500"
                       value="{{ $profile?->notes }}"
                       style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <button type="submit" class="btn ok" style="font-size:12px;">Kaydet</button>
                <button type="button" onclick="toggleForm('salary-{{ $emp->id }}')" class="btn" style="font-size:12px;">İptal</button>
                @if($profile)
                <span style="font-size:11px;color:var(--u-muted);">Kaydetmek mevcut profili devre dışı bırakır ve yeni bir kayıt oluşturur.</span>
                @endif
            </div>
        </form>
    </div>
</section>
@endforeach
</div>
@endif

@endsection

@push('scripts')
<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush

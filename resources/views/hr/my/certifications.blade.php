@php
    $role = auth()->user()?->role;
    $hrLayout = in_array($role, ['senior','mentor'])
        ? 'senior.layouts.app'
        : ($role === 'manager' ? 'manager.layouts.app' : 'layouts.staff');
@endphp
@extends($hrLayout)

@section('title', 'Sertifikalarım')
@section('page_title', 'Sertifikalarım')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- Özet strip --}}
@php
    $active   = $certs->filter(fn($c) => !$c->isExpired() && !$c->isExpiringSoon());
    $expiring = $certs->filter(fn($c) => $c->isExpiringSoon());
    $expired  = $certs->filter(fn($c) => $c->isExpired());
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;">
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #16a34a;border-radius:10px;padding:12px 16px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#16a34a;">{{ $active->count() }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Geçerli</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #d97706;border-radius:10px;padding:12px 16px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:{{ $expiring->count() > 0 ? '#d97706' : 'var(--u-muted)' }};">{{ $expiring->count() }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Yakında Bitiyor</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $expired->count() > 0 ? '#dc2626' : 'var(--u-line)' }};border-radius:10px;padding:12px 16px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:{{ $expired->count() > 0 ? '#dc2626' : 'var(--u-muted)' }};">{{ $expired->count() }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Süresi Dolmuş</div>
    </div>
</div>

{{-- Yeni Sertifika Ekle --}}
<section class="panel" style="padding:16px 20px;margin-bottom:16px;">
    <div style="font-size:13px;font-weight:700;color:var(--u-text);margin-bottom:12px;">+ Yeni Sertifika Ekle</div>
    <form method="POST" action="/hr/my/certifications">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div style="grid-column:1/-1;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Sertifika Adı *</label>
                <input type="text" name="cert_name" required maxlength="200"
                       placeholder="ör. Google Ads Sertifikası, AWS Cloud Practitioner…"
                       style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Veren Kurum</label>
                <input type="text" name="issuer" maxlength="200"
                       placeholder="ör. Google, HubSpot, PMI…"
                       style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
            </div>
            <div></div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Verilme Tarihi *</label>
                <input type="date" name="issue_date" required
                       style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Son Geçerlilik</label>
                <input type="date" name="expiry_date"
                       style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">Süresiz sertifikaları boş bırakın.</div>
            </div>
            <div style="grid-column:1/-1;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Not</label>
                <input type="text" name="notes" maxlength="500"
                       placeholder="Sertifika kodu, credential ID…"
                       style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
            </div>
        </div>
        <button type="submit" class="btn ok" style="font-size:12px;padding:7px 20px;">Sertifika Ekle</button>
    </form>
</section>

{{-- Sertifika Listesi --}}
@if($certs->isEmpty())
<section class="panel" style="padding:40px;text-align:center;">
    <div style="font-size:32px;margin-bottom:8px;">🎓</div>
    <div style="font-size:14px;color:var(--u-muted);">Henüz sertifika eklenmemiş.</div>
    <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">Yukarıdaki formu kullanarak ilk sertifikanızı ekleyin.</div>
</section>
@else
<div style="display:flex;flex-direction:column;gap:8px;">
@foreach($certs as $cert)
@php
    $isExpired  = $cert->isExpired();
    $isSoon     = !$isExpired && $cert->isExpiringSoon();
    $cardBorder = $isExpired ? '#fca5a5' : ($isSoon ? '#fde68a' : 'var(--u-line)');
    $cardBg     = $isExpired ? '#fef2f2' : ($isSoon ? '#fffbeb' : 'var(--u-card)');
    $badge      = $isExpired ? ['danger','Süresi Dolmuş'] : ($isSoon ? ['warn','Yakında Bitiyor'] : ['ok','Geçerli']);
@endphp
<section style="background:{{ $cardBg }};border:1px solid {{ $cardBorder }};border-radius:10px;padding:14px 18px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                <span style="font-size:14px;font-weight:700;color:var(--u-text);">{{ $cert->cert_name }}</span>
                <span class="badge {{ $badge[0] }}" style="font-size:10px;">{{ $badge[1] }}</span>
            </div>
            <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:11px;color:var(--u-muted);">
                @if($cert->issuer)<span>🏛 {{ $cert->issuer }}</span>@endif
                <span>📅 Verilme: {{ $cert->issue_date->format('d.m.Y') }}</span>
                @if($cert->expiry_date)
                    <span style="color:{{ $isExpired ? '#dc2626' : ($isSoon ? '#d97706' : 'var(--u-muted)') }};font-weight:{{ ($isExpired||$isSoon) ? '700' : '400' }};">
                        ⏰ Bitiş: {{ $cert->expiry_date->format('d.m.Y') }}
                        @if($isSoon) ({{ $cert->expiry_date->diffForHumans() }})@endif
                        @if($isExpired) ({{ $cert->expiry_date->diffForHumans() }})@endif
                    </span>
                @else
                    <span>⏰ Süresiz</span>
                @endif
                @if($cert->notes)<span>📝 {{ $cert->notes }}</span>@endif
            </div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0;">
            <button type="button" onclick="toggleEdit('cert-{{ $cert->id }}')" class="btn" style="font-size:11px;padding:3px 10px;">✏️</button>
            <form method="POST" action="/hr/my/certifications/{{ $cert->id }}" onsubmit="return confirm('Bu sertifikayı silmek istediğinizden emin misiniz?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn warn" style="font-size:11px;padding:3px 10px;">🗑</button>
            </form>
        </div>
    </div>

    {{-- Düzenleme formu --}}
    <div id="cert-{{ $cert->id }}" style="display:none;margin-top:12px;padding-top:12px;border-top:1px solid {{ $cardBorder }};">
        <form method="POST" action="/hr/my/certifications/{{ $cert->id }}">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Sertifika Adı *</label>
                    <input type="text" name="cert_name" required value="{{ $cert->cert_name }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Veren Kurum</label>
                    <input type="text" name="issuer" value="{{ $cert->issuer }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div></div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Verilme Tarihi *</label>
                    <input type="date" name="issue_date" required value="{{ $cert->issue_date->format('Y-m-d') }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Son Geçerlilik</label>
                    <input type="date" name="expiry_date" value="{{ $cert->expiry_date?->format('Y-m-d') }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Not</label>
                    <input type="text" name="notes" value="{{ $cert->notes }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
            </div>
            <div style="display:flex;gap:6px;">
                <button type="submit" class="btn ok" style="font-size:11px;">Kaydet</button>
                <button type="button" onclick="toggleEdit('cert-{{ $cert->id }}')" class="btn" style="font-size:11px;">İptal</button>
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
function toggleEdit(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush

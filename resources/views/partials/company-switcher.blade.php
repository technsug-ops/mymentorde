{{-- Şirket bağlamı değiştirici.

     Yalnızca birden fazla şirket görebilen personelde çıkar; tek şirketli
     kullanıcı (firma yöneticisi, danışman) hiç görmez.

     NEDEN ÖNEMLİ: MentorDE partner firmaların süreçlerini yürütüyor. Personel
     MentorDE bağlamındayken partner öğrencisi için ticket/görev açarsa kayıt
     MentorDE'nin kutusuna düşer ve partner firma göremez. Buradan bağlam
     değişince yazma hedefi de partnere geçer. --}}
@php
    $_swUser = auth()->user();
    $_swIds = $_swUser?->visibleCompanyIds() ?? [];
    $_swCompanies = count($_swIds) > 1
        ? \App\Models\Company::query()
            ->whereIn('id', $_swIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'brand_name'])
        : collect();
    $_swCurrent = (int) (\App\Support\TenantContext::writeId() ?? 0);
@endphp

@if($_swCompanies->count() > 1)
<form method="POST" action="{{ route('company-context.switch') }}"
      data-company-switcher="1"
      style="display:flex;align-items:center;gap:6px;">
    @csrf
    <span title="Hangi firma adına çalışıyorsunuz" style="font-size:16px;line-height:1;">🏢</span>
    <select name="company_id"
            aria-label="Çalışılan firma"
            style="height:36px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);background:var(--u-card,#fff);color:inherit;font-size:13px;padding:0 8px;max-width:190px;">
        @foreach($_swCompanies as $_swC)
            <option value="{{ $_swC->id }}" {{ $_swCurrent === (int) $_swC->id ? 'selected' : '' }}>
                {{ $_swC->brand_name ?: $_swC->name }}
            </option>
        @endforeach
    </select>
    <noscript>
        <button type="submit" style="height:36px;border-radius:9px;border:1px solid var(--u-line,#e5e7eb);background:var(--u-card,#fff);font-size:12px;padding:0 10px;cursor:pointer;">Geç</button>
    </noscript>
</form>

{{-- CSP: inline onchange bloklanır, nonce'lu blok içinden bağlanıyoruz. --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var form = document.querySelector('[data-company-switcher]');
    if (!form) return;

    form.querySelector('select').addEventListener('change', function () {
        form.submit();
    });
})();
</script>
@endif

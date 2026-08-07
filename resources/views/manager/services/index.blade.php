@extends('manager.layouts.app')

@section('title', 'Hizmetler ve Fiyatlar')
@section('page_title', 'Hizmetler ve Fiyatlar')

@push('head')
<style>
.sc-note { background:rgba(30,64,175,.05); border:1px solid rgba(30,64,175,.2); border-left:3px solid #1e40af; border-radius:8px; padding:11px 14px; font-size:12px; margin-bottom:12px; line-height:1.6; }
.sc-warn { background:rgba(217,119,6,.06); border:1px solid rgba(217,119,6,.25); border-left:3px solid #d97706; border-radius:8px; padding:11px 14px; font-size:12px; margin-bottom:12px; line-height:1.6; }
.sc-err  { background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.25); border-left:3px solid #dc2626; border-radius:8px; padding:11px 14px; font-size:12px; margin-bottom:12px; line-height:1.6; }
.sc-ok   { background:rgba(22,163,74,.06); border:1px solid rgba(22,163,74,.25); border-left:3px solid #16a34a; border-radius:8px; padding:11px 14px; font-size:12px; margin-bottom:12px; line-height:1.6; }
.sc-card { border:1px solid var(--border,#e2e8f0); border-radius:10px; padding:14px; margin-bottom:10px; }
.sc-card.off { opacity:.62; border-style:dashed; }
.sc-head { display:flex; align-items:baseline; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
.sc-title { font-size:14px; font-weight:700; }
.sc-code { font-family:ui-monospace,monospace; font-size:11px; color:var(--muted,#64748b); }
.sc-price { font-size:18px; font-weight:800; font-variant-numeric:tabular-nums; }
.sc-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px; }
.sc-label { display:block; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
.sc-in { width:100%; padding:6px 9px; font-size:12px; border:1px solid var(--border,#e2e8f0); border-radius:6px; background:var(--card,#fff); color:inherit; }
.sc-in:focus-visible { outline:2px solid #1e40af; outline-offset:1px; }
.sc-btn { padding:5px 12px; font-size:11px; font-weight:600; color:#1e40af; border:1px solid rgba(30,64,175,.3); border-radius:6px; background:rgba(30,64,175,.05); cursor:pointer; }
.sc-btn.warn { color:#b45309; border-color:rgba(217,119,6,.35); background:rgba(217,119,6,.06); }
.sc-btn.solid { color:#fff; background:#1e40af; border-color:#1e40af; }
.sc-chk { display:flex; align-items:center; gap:6px; font-size:12px; padding:4px 0; }
.sc-actions { display:flex; gap:8px; align-items:center; margin-top:10px; flex-wrap:wrap; }
.sc-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; background:rgba(100,116,139,.12); color:#475569; }
.sc-table { width:100%; border-collapse:collapse; font-size:12px; }
.sc-table th { padding:7px 8px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
.sc-table td { padding:6px 8px; border-top:1px solid var(--border,#e2e8f0); vertical-align:middle; }
.sc-scroll { overflow-x:auto; }
</style>
@endpush

@section('content')

@if(session('status'))
    <div class="sc-ok">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="sc-err">{{ $errors->first() }}</div>
@endif

@unless($hasOwn)

    {{-- ── Miras hâli: firma henüz kendi kataloğunu tanımlamamış ── --}}
    <div class="sc-note">
        Şu anda
        <strong>{{ $inheritedFrom?->name ?? 'platform' }}</strong>
        tarafından tanımlanan paket ve fiyatları kullanıyorsunuz. Kendi
        fiyatlarınızı belirlemek isterseniz aşağıdaki listeyi kendinize
        kopyalayın — sonra hem fiyatı hem içeriği serbestçe düzenlersiniz.
        Kopyalamadığınız sürece üst firmadaki değişiklikler size de yansımaya devam eder.
    </div>

    <section class="panel" style="margin-bottom:12px;">
        <form method="POST" action="{{ route('manager.services.fork') }}">
            @csrf
            <button type="submit" class="sc-btn solid">Kendi kataloğumu oluştur</button>
        </form>
    </section>

    <section class="panel" style="margin-bottom:12px;">
        <h2 style="font-size:14px;margin-bottom:10px;">Şu an geçerli paketler</h2>
        <div class="sc-scroll">
            <table class="sc-table">
                <thead><tr><th>Paket</th><th>Kod</th><th style="text-align:right;">Fiyat</th></tr></thead>
                <tbody>
                @foreach($preview as $p)
                    <tr>
                        <td>{{ $p['title'] }}</td>
                        <td class="sc-code">{{ $p['code'] }}</td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums;">{{ $p['price'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2 style="font-size:14px;margin-bottom:10px;">Şu an geçerli ek hizmetler ({{ $previewExtras->count() }})</h2>
        <div class="sc-scroll">
            <table class="sc-table">
                <thead><tr><th>Hizmet</th><th>Kod</th><th style="text-align:right;">Fiyat</th></tr></thead>
                <tbody>
                @foreach($previewExtras as $e)
                    <tr>
                        <td>{{ $e['title'] }}</td>
                        <td class="sc-code">{{ $e['code'] }}</td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums;">{{ $e['price'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

@else

    {{-- ── Kendi kataloğu: düzenlenebilir ── --}}
    <div class="sc-note">
        Bu paketler ve fiyatlar <strong>{{ $company?->name }}</strong> firmasına ait.
        Adaylarınız burada tanımladığınız listeyi ve fiyatları görür.
        Sözleşme aşamasında tutar tek tek pazarlıkla değiştirilebilir; finans
        yalnızca sözleşmede sabitlenen tutarı sayar.
    </div>

    <div class="sc-warn">
        Fiyat değişikliği <strong>geçmişi bozmaz</strong>: seçim anında paketin adı ve fiyatı
        adayın kaydına kopyalanır, sabitlenmiş sözleşme tutarları da olduğu gibi kalır.
        Etkilenen tek grup, henüz sözleşme tutarı sabitlenmemiş adaylardır.
    </div>

    <section class="panel" style="margin-bottom:14px;">
        <div class="sc-head" style="margin-bottom:0;">
            <div>
                <div style="font-size:14px;font-weight:700;">Paketler ({{ $packages->count() }})</div>
                <div style="font-size:11px;color:var(--muted,#64748b);">Kod alanı geçmiş kayıtları bağlar — değiştirmeyin.</div>
            </div>
            <form method="POST" action="{{ route('manager.services.reset') }}"
                  data-confirm="Kendi kataloğunuz tamamen silinecek ve üst firmanın paket/fiyatları geçerli olacak. Emin misiniz?">
                @csrf
                <button type="submit" class="sc-btn warn">Mirasa dön</button>
            </form>
        </div>
    </section>

    @foreach($packages as $p)
        @php $inUse = in_array($p->code, $usedCodes, true); @endphp
        <section class="panel sc-card {{ $p->is_active ? '' : 'off' }}">
            <form method="POST" action="{{ route('manager.services.packages.update', $p->id) }}">
                @csrf
                @method('PATCH')

                <div class="sc-head">
                    <div>
                        <span class="sc-title">{{ $p->title }}</span>
                        <span class="sc-code">· {{ $p->code }}</span>
                        @unless($p->is_active)<span class="sc-badge">satışta değil</span>@endunless
                        @if($inUse)<span class="sc-badge">kullanımda</span>@endif
                    </div>
                    <span class="sc-price">{{ $p->price }}</span>
                </div>

                <input type="hidden" name="code" value="{{ $p->code }}">

                <div class="sc-grid">
                    <div style="grid-column:1/-1;">
                        <label class="sc-label" for="title-{{ $p->id }}">Paket adı</label>
                        <input class="sc-in" id="title-{{ $p->id }}" name="title" value="{{ $p->title }}" required maxlength="160">
                    </div>
                    <div>
                        <label class="sc-label" for="price-{{ $p->id }}">Fiyat</label>
                        <input class="sc-in" id="price-{{ $p->id }}" name="price_amount" type="number" step="0.01" min="0" value="{{ (float) $p->price_amount }}" required>
                    </div>
                    <div>
                        <label class="sc-label" for="cur-{{ $p->id }}">Para birimi</label>
                        <input class="sc-in" id="cur-{{ $p->id }}" name="currency" value="{{ $p->currency }}" maxlength="8">
                    </div>
                    <div>
                        <label class="sc-label" for="uni-{{ $p->id }}">Üniversite sayısı</label>
                        <input class="sc-in" id="uni-{{ $p->id }}" name="max_universities" type="number" min="0" value="{{ $p->max_universities }}">
                    </div>
                    <div>
                        <label class="sc-label" for="val-{{ $p->id }}">Geçerlilik (ay)</label>
                        <input class="sc-in" id="val-{{ $p->id }}" name="validity_months" type="number" min="0" value="{{ $p->validity_months }}">
                    </div>
                    <div>
                        <label class="sc-label" for="sup-{{ $p->id }}">Destek düzeyi</label>
                        <input class="sc-in" id="sup-{{ $p->id }}" name="support_level" value="{{ $p->support_level }}" maxlength="64">
                    </div>
                    <div>
                        <label class="sc-label" for="ord-{{ $p->id }}">Sıra</label>
                        <input class="sc-in" id="ord-{{ $p->id }}" name="sort_order" type="number" min="0" value="{{ (int) $p->sort_order }}">
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <label class="sc-label" for="inc-{{ $p->id }}">Kısa özet (kart üzerinde görünür)</label>
                    <input class="sc-in" id="inc-{{ $p->id }}" name="includes" value="{{ $p->includes }}" maxlength="500">
                </div>

                <div style="margin-top:10px;">
                    <label class="sc-label" for="feat-{{ $p->id }}">Paket içeriği — her satır bir madde</label>
                    <textarea class="sc-in" id="feat-{{ $p->id }}" name="features" rows="5">{{ implode("\n", is_array($p->features) ? $p->features : []) }}</textarea>
                </div>

                <div style="margin-top:10px;">
                    <span class="sc-label">Pakete dahil hizmet kategorileri</span>
                    <div class="sc-grid">
                        @foreach($categories as $cat)
                            <label class="sc-chk">
                                <input type="checkbox" name="included_categories[]" value="{{ $cat['key'] }}"
                                       @checked(in_array($cat['key'], is_array($p->included_categories) ? $p->included_categories : [], true))>
                                {{ $cat['title'] ?? $cat['key'] }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <span class="sc-label">Pakete dahil ek hizmetler (ayrıca ücretlendirilmez)</span>
                    <div class="sc-grid">
                        @foreach($extras as $e)
                            <label class="sc-chk">
                                <input type="checkbox" name="included_extras[]" value="{{ $e->code }}"
                                       @checked(in_array($e->code, is_array($p->included_extras) ? $p->included_extras : [], true))>
                                {{ $e->title }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="sc-actions">
                    <label class="sc-chk"><input type="checkbox" name="includes_visa" value="1" @checked($p->includes_visa)> Vize desteği dahil</label>
                    <label class="sc-chk"><input type="checkbox" name="includes_housing" value="1" @checked($p->includes_housing)> Konaklama desteği dahil</label>
                    <label class="sc-chk"><input type="checkbox" name="is_active" value="1" @checked($p->is_active)> Satışta</label>
                    <button type="submit" class="sc-btn solid">Kaydet</button>
                </div>
            </form>

            <form method="POST" action="{{ route('manager.services.packages.destroy', $p->id) }}" style="margin-top:8px;"
                  data-confirm="{{ $inUse
                        ? 'Bu paketi seçmiş adaylarınız var; kayıt silinmeyecek, yalnızca satıştan kaldırılacak. Devam edilsin mi?'
                        : 'Paket silinecek. Emin misiniz?' }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="sc-btn warn">{{ $inUse ? 'Satıştan kaldır' : 'Sil' }}</button>
            </form>
        </section>
    @endforeach

    <section class="panel" style="margin-bottom:14px;">
        <h2 style="font-size:14px;margin-bottom:10px;">Yeni paket</h2>
        <form method="POST" action="{{ route('manager.services.packages.store') }}">
            @csrf
            <div class="sc-grid">
                <div>
                    <label class="sc-label" for="np-code">Kod</label>
                    <input class="sc-in" id="np-code" name="code" placeholder="pkg_premium" required maxlength="64">
                </div>
                <div>
                    <label class="sc-label" for="np-title">Paket adı</label>
                    <input class="sc-in" id="np-title" name="title" required maxlength="160">
                </div>
                <div>
                    <label class="sc-label" for="np-price">Fiyat</label>
                    <input class="sc-in" id="np-price" name="price_amount" type="number" step="0.01" min="0" required>
                </div>
                <div>
                    <label class="sc-label" for="np-cur">Para birimi</label>
                    <input class="sc-in" id="np-cur" name="currency" value="EUR" maxlength="8">
                </div>
            </div>
            <div style="margin-top:10px;">
                <label class="sc-label" for="np-feat">Paket içeriği — her satır bir madde</label>
                <textarea class="sc-in" id="np-feat" name="features" rows="4"></textarea>
            </div>
            <div class="sc-actions">
                <label class="sc-chk"><input type="checkbox" name="is_active" value="1" checked> Satışta</label>
                <button type="submit" class="sc-btn solid">Paket ekle</button>
            </div>
        </form>
    </section>

    {{-- ── Ek hizmetler ── --}}
    <section class="panel" style="margin-bottom:14px;">
        <h2 style="font-size:14px;margin-bottom:10px;">Ek hizmetler ({{ $extras->count() }})</h2>
        <div class="sc-scroll">
            <table class="sc-table">
                <thead>
                    <tr><th>Hizmet</th><th>Kategori</th><th style="width:120px;">Fiyat</th><th style="width:90px;">Satışta</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($extras as $e)
                    <tr>
                        <td>
                            <form method="POST" action="{{ route('manager.services.extras.update', $e->id) }}" id="ext-{{ $e->id }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="code" value="{{ $e->code }}">
                                <input class="sc-in" name="title" value="{{ $e->title }}" required maxlength="160" aria-label="Hizmet adı">
                                <div class="sc-code">{{ $e->code }}</div>
                            </form>
                        </td>
                        <td>
                            <select class="sc-in" name="category" form="ext-{{ $e->id }}" aria-label="Kategori">
                                <option value="">—</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat['key'] }}" @selected($e->category === $cat['key'])>{{ $cat['title'] ?? $cat['key'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input class="sc-in" name="price_amount" form="ext-{{ $e->id }}" type="number" step="0.01" min="0"
                                   value="{{ (float) $e->price_amount }}" required aria-label="Fiyat">
                        </td>
                        <td>
                            <label class="sc-chk">
                                <input type="checkbox" name="is_active" value="1" form="ext-{{ $e->id }}" @checked($e->is_active)>
                            </label>
                        </td>
                        <td style="white-space:nowrap;">
                            <button type="submit" form="ext-{{ $e->id }}" class="sc-btn">Kaydet</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2 style="font-size:14px;margin-bottom:10px;">Yeni ek hizmet</h2>
        <form method="POST" action="{{ route('manager.services.extras.store') }}">
            @csrf
            <div class="sc-grid">
                <div>
                    <label class="sc-label" for="ne-code">Kod</label>
                    <input class="sc-in" id="ne-code" name="code" placeholder="ext_ceviri" required maxlength="64">
                </div>
                <div>
                    <label class="sc-label" for="ne-title">Hizmet adı</label>
                    <input class="sc-in" id="ne-title" name="title" required maxlength="160">
                </div>
                <div>
                    <label class="sc-label" for="ne-cat">Kategori</label>
                    <select class="sc-in" id="ne-cat" name="category">
                        <option value="">—</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat['key'] }}">{{ $cat['title'] ?? $cat['key'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sc-label" for="ne-price">Fiyat</label>
                    <input class="sc-in" id="ne-price" name="price_amount" type="number" step="0.01" min="0" required>
                </div>
            </div>
            <div class="sc-actions">
                <label class="sc-chk"><input type="checkbox" name="is_active" value="1" checked> Satışta</label>
                <button type="submit" class="sc-btn solid">Hizmet ekle</button>
            </div>
        </form>
    </section>

@endunless

{{-- Onay diyalogları: CSP nonce'lu blok — inline onsubmit CSP'de bloklanır. --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });
})();
</script>

@endsection

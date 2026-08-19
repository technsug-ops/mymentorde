@extends('dealer.layouts.app')

@section('title', 'Mini-Site')
@section('page_title', 'Mini-Site (White-Label)')
@section('page_subtitle', 'Kendi marka ve renginle bir tanıtım sayfası — ziyaretçiler senin kodunla başvurur')

@section('content')

@php $d = $dealer; @endphp

@if(session('status'))
    <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;">
        {{ session('status') }}
    </div>
@endif
@if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
@endif

{{-- Durum kartı --}}
<div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div style="font-size:14px;">
        <strong>Yayın Durumu:</strong>
        @if($d?->site_enabled)
            <span style="background:#ecfdf5;color:#065f46;padding:3px 10px;border-radius:999px;">Yayında</span>
        @else
            <span style="background:#fef9c3;color:#854d0e;padding:3px 10px;border-radius:999px;">Yayında değil (yönetici onayı bekliyor)</span>
        @endif
    </div>
    @if($d?->public_slug)
        <div style="font-size:13px;color:var(--muted,#64748b);">
            Adres: <a href="/p/{{ $d->public_slug }}?preview=1" target="_blank" style="color:var(--theme-accent-dealer,#1E3D6B);font-weight:600;">/p/{{ $d->public_slug }}</a>
            <span style="font-size:11px;">(önizleme)</span>
        </div>
    @endif
</div>

<form method="POST" action="/dealer/mini-site" enctype="multipart/form-data"
      style="max-width:640px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:14px;padding:24px;">
    @csrf

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Sayfa Adresi (slug)</label>
        <div style="display:flex;align-items:center;gap:4px;">
            <span style="color:var(--muted,#64748b);font-size:14px;">/p/</span>
            <input type="text" name="public_slug" value="{{ old('public_slug', $d?->public_slug) }}" maxlength="64"
                   placeholder="ornek-bayi" pattern="[a-z0-9\-]+"
                   style="flex:1;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;font-family:ui-monospace,monospace;">
        </div>
        <small style="color:var(--muted,#64748b);font-size:12px;">Sadece küçük harf, rakam ve tire. Benzersiz olmalı.</small>
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Logo (PNG/JPG/WEBP, max 2MB)</label>
        @if($d?->site_logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($d->site_logo_path) }}" alt="logo" style="height:48px;margin-bottom:8px;display:block;">
        @endif
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" style="font-size:13px;">
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Marka Rengi</label>
        <div style="display:flex;align-items:center;gap:10px;">
            <input type="color" name="site_accent_color" value="{{ old('site_accent_color', $d?->site_accent_color ?: '#7e58bf') }}"
                   style="width:60px;height:40px;border:1px solid var(--border,#cbd5e1);border-radius:8px;cursor:pointer;">
            <small style="color:var(--muted,#64748b);font-size:12px;">Sitenin tüm vurgu rengi bu olur — kendi kurumsal renginizi seçin.</small>
        </div>
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Hero Başlık</label>
        <input type="text" name="site_hero_title" value="{{ old('site_hero_title', $d?->site_hero_title) }}" maxlength="160"
               placeholder="Almanya'da Eğitim Hayalini Gerçekleştir"
               style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Hero Alt Metin</label>
        <textarea name="site_hero_subtitle" rows="2" maxlength="300"
                  style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">{{ old('site_hero_subtitle', $d?->site_hero_subtitle) }}</textarea>
    </div>

    <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Hakkımda / Tanıtım</label>
        <textarea name="site_about_text" rows="4" maxlength="4000"
                  style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">{{ old('site_about_text', $d?->site_about_text) }}</textarea>
    </div>

    <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Telefon</label>
            <input type="text" name="site_phone" value="{{ old('site_phone', $d?->site_phone) }}" maxlength="50"
                   style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
        </div>
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">WhatsApp</label>
            <input type="text" name="site_whatsapp" value="{{ old('site_whatsapp', $d?->site_whatsapp) }}" maxlength="50"
                   style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
        </div>
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Instagram</label>
            <input type="text" name="site_instagram" value="{{ old('site_instagram', $d?->site_instagram) }}" maxlength="100"
                   style="width:100%;padding:10px 12px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:14px;">
        </div>
    </div>

    @php
        // Kararın tek kaynağı model — bkz. Dealer::usesPartnerSite().
        $isOperationPartner = (bool) $d?->usesPartnerSite();
        $svcRows  = old('site_services',     $d?->site_services ?: []);
        $statRows = old('site_stats',        $d?->site_stats ?: []);
        $teamRows = old('site_team',         $d?->site_team ?: []);
        $tstRows  = old('site_testimonials', $d?->site_testimonials ?: []);
        $pkgRows  = old('site_packages',     $d?->site_packages ?: []);
        $faqRows  = old('site_faq',          $d?->site_faq ?: []);
        $uniList  = old('site_universities', $d?->site_universities ?: []);
        if (is_array($uniList)) { $uniList = implode("\n", $uniList); }
        $inp = 'width:100%;padding:9px 11px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:13px;';
        $sectionBox = 'border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px;margin:22px 0;background:var(--surface,#fff);';
        $templates  = \App\Support\PartnerTemplates::all();
        $currentTpl = old('site_template', $d?->site_template ?: \App\Support\PartnerTemplates::DEFAULT);
    @endphp

    @if($isOperationPartner)
        <div style="border-top:2px solid var(--border,#e2e8f0);margin:26px 0 4px;padding-top:6px;">
            <div style="font-size:12px;font-weight:700;color:var(--theme-accent-dealer,#1E3D6B);text-transform:uppercase;letter-spacing:.08em;">
                Kurumsal Site Bölümleri
            </div>
            <small style="color:var(--muted,#64748b);font-size:12px;">Bu alanları doldurdukça siteniz zenginleşir. Boş bıraktığınız kartlar sitede görünmez.</small>
        </div>

        {{-- Template seçici --}}
        <div style="{{ $sectionBox }}">
            <div style="font-weight:600;font-size:14px;margin-bottom:4px;">Site Şablonu (Tasarım)</div>
            <small style="color:var(--muted,#64748b);font-size:12px;">İçeriğiniz aynı kalır; sadece görünüm değişir. Seçtiğinizi kaydedin, önizleyin.</small>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-top:12px;">
                @foreach($templates as $key => $tpl)
                    @php $sel = $currentTpl === $key; @endphp
                    <label style="display:block;cursor:pointer;border:2px solid {{ $sel ? 'var(--theme-accent-dealer,#1E3D6B)' : 'var(--border,#e2e8f0)' }};border-radius:12px;padding:14px;background:{{ $sel ? 'color-mix(in srgb, var(--theme-accent-dealer,#1E3D6B) 6%, #fff)' : 'var(--bg,#f8fafc)' }};transition:all .15s;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <span style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:14px;">
                                <input type="radio" name="site_template" value="{{ $key }}" @checked($sel) style="accent-color:var(--theme-accent-dealer,#1E3D6B);">
                                {{ $tpl['name'] }}
                            </span>
                            @if($d?->public_slug)
                                <a href="/p/{{ $d->public_slug }}?preview=1&tpl={{ $key }}" target="_blank" rel="noopener"
                                   style="font-size:11px;color:var(--theme-accent-dealer,#1E3D6B);font-weight:600;white-space:nowrap;">Önizle ↗</a>
                            @endif
                        </div>
                        <div style="font-size:12px;color:var(--muted,#64748b);margin-top:8px;line-height:1.4;">{{ $tpl['desc'] }}</div>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- İletişim adresi --}}
        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;">Ofis / Adres</label>
            <input type="text" name="site_address" value="{{ old('site_address', $d?->site_address) }}" maxlength="300"
                   placeholder="Örn. Bağdat Cad. No:1, Kadıköy / İstanbul" style="{{ $inp }}">
        </div>

        {{-- Sayfa kurgusu: bölüm sırası + aç/kapa --}}
        @include('dealer.mini-site._sections')

        {{-- Hizmet kartları --}}
        @include('dealer.mini-site._repeat', [
            'name' => 'site_services', 'title' => 'Hizmet Kartları', 'rows' => $svcRows,
            'rowView' => 'dealer.mini-site.rows.service', 'max' => 12, 'addLabel' => '+ Hizmet ekle',
            'hint' => 'Sıra sitede aynen görünür. Hepsini boş bırakırsanız varsayılan 6 hizmet gösterilir.',
        ])

        {{-- İstatistikler --}}
        @include('dealer.mini-site._repeat', [
            'name' => 'site_stats', 'title' => 'İstatistikler', 'rows' => $statRows,
            'rowView' => 'dealer.mini-site.rows.stat', 'max' => 8, 'addLabel' => '+ İstatistik ekle',
            'hint' => 'Örn. "500+" / "Mutlu Öğrenci". Boşsa bu bölüm gizlenir; ilk 3 tanesi hero rozetlerinde de görünür.',
        ])

        {{-- Ekip --}}
        @include('dealer.mini-site._repeat', [
            'name' => 'site_team', 'title' => 'Ekip / Danışmanlar', 'rows' => $teamRows,
            'rowView' => 'dealer.mini-site.rows.team', 'max' => 12, 'addLabel' => '+ Kişi ekle',
            'hint' => 'Fotoğraf yoksa isim baş harfi gösterilir. Hepsi boşsa Ekip bölümü gizlenir.',
        ])

        {{-- Öğrenci yorumları --}}
        @include('dealer.mini-site._repeat', [
            'name' => 'site_testimonials', 'title' => 'Öğrenci Yorumları', 'rows' => $tstRows,
            'rowView' => 'dealer.mini-site.rows.testimonial', 'max' => 12, 'addLabel' => '+ Yorum ekle',
            'hint' => 'Yalnız GERÇEK yorumları girin ve öğrencinin onayını aldığınızdan emin olun. Boşsa yorum bölümü sitede hiç görünmez.',
        ])

        {{-- Destek paketleri --}}
        @include('dealer.mini-site._repeat', [
            'name' => 'site_packages', 'title' => 'Destek Paketleri', 'rows' => $pkgRows,
            'rowView' => 'dealer.mini-site.rows.package', 'max' => 6, 'addLabel' => '+ Paket ekle',
            'hint' => 'Kendi paketlerinizi yazın (fiyat zorunlu değil). Boş bırakırsanız paket bölümü hiç görünmez — varsayılan paket üretilmez.',
        ])
        <input type="text" name="site_package_note" value="{{ old('site_package_note', $d?->site_package_note) }}" maxlength="300"
               placeholder="Paketlerin altındaki açıklama (opsiyonel)" style="{{ $inp }}margin:-14px 0 22px;">

        {{-- S.S.S. --}}
        @include('dealer.mini-site._repeat', [
            'name' => 'site_faq', 'title' => 'Sıkça Sorulan Sorular', 'rows' => $faqRows,
            'rowView' => 'dealer.mini-site.rows.faq', 'max' => 10, 'addLabel' => '+ Soru ekle',
            'hint' => 'Boş bırakırsanız Almanya süreciyle ilgili genel 4 soru gösterilir. Soru ve cevap birlikte dolu olmalı.',
        ])

        {{-- Yerleşilen üniversiteler --}}
        <div style="{{ $sectionBox }}">
            <div style="font-weight:600;font-size:14px;margin-bottom:4px;">Öğrencilerinizin Yerleştiği Üniversiteler</div>
            <small style="color:var(--muted,#64748b);font-size:12px;">
                Her satıra bir üniversite adı (max 12). Yalnız <b>gerçekten</b> öğrenci yerleştirdiğiniz
                üniversiteleri yazın. Boşsa bu şerit sitenizde görünmez.
            </small>
            <textarea name="site_universities" rows="4" maxlength="600"
                      placeholder="TU München&#10;RWTH Aachen&#10;Uni Köln" style="{{ $inp }}margin-top:10px;resize:vertical;">{{ $uniList }}</textarea>
        </div>

        {{-- MentorDE rozeti aç/kapa --}}
        <div style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
            <input type="hidden" name="site_show_badge" value="0">
            <input type="checkbox" name="site_show_badge" value="1" id="show_badge"
                   @checked(old('site_show_badge', $d?->site_show_badge ?? true)) style="width:18px;height:18px;cursor:pointer;">
            <label for="show_badge" style="font-size:14px;cursor:pointer;">
                "{{ config('brand.name', 'MentorDE') }} Yetkili Partneri" güven rozetini göster
            </label>
        </div>
    @endif

    <button type="submit"
            style="background:var(--theme-accent-dealer,#1E3D6B);color:#fff;padding:11px 22px;border:none;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">
        Kaydet
    </button>
</form>

@endsection

@push('scripts')
{{-- Tekrarlanabilir kart grupları: ekle / sil / ↑ ↓. Sıra = input adlarındaki index sırası,
     bu yüzden her değişiklikten sonra satırlar yeniden numaralanır. CSP: nonce'lu blok. --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    document.querySelectorAll('[data-repeat]').forEach(function (group) {
        var prefix = group.dataset.repeat;
        var max    = parseInt(group.dataset.max || '12', 10);
        var fixed  = group.hasAttribute('data-fixed');   // bölüm listesi: ekle/sil yok, sadece sırala
        var list   = group.querySelector('[data-rows]');
        var tpl    = group.querySelector('[data-row-tpl]');
        var addBtn = group.querySelector('[data-add]');
        if (!list) { return; }

        function renumber() {
            Array.prototype.forEach.call(list.children, function (row, i) {
                row.querySelectorAll('[name]').forEach(function (el) {
                    el.name = el.name.replace(/\[(?:\d+|__I__)\]/, '[' + i + ']');
                });
                var num = row.querySelector('[data-num]');
                if (num) { num.textContent = String(i + 1); }
                var up = row.querySelector('[data-act="up"]');
                var dn = row.querySelector('[data-act="down"]');
                if (up) { up.disabled = (i === 0); up.style.opacity = (i === 0) ? '.35' : '1'; }
                if (dn) {
                    var last = (i === list.children.length - 1);
                    dn.disabled = last; dn.style.opacity = last ? '.35' : '1';
                }
            });
            if (addBtn) {
                var full = list.children.length >= max;
                addBtn.disabled = full;
                addBtn.style.opacity = full ? '.45' : '1';
                addBtn.style.cursor = full ? 'not-allowed' : 'pointer';
            }
        }

        group.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn || !group.contains(btn)) { return; }
            var act = btn.hasAttribute('data-add') ? 'add' : btn.dataset.act;
            if (!act) { return; }
            var row = btn.closest('[data-row]');

            if (act === 'add' && !fixed && tpl) {
                if (list.children.length >= max) { return; }
                var clone = tpl.content ? tpl.content.cloneNode(true) : null;
                if (!clone) { return; }
                list.appendChild(clone);
            } else if (act === 'del' && !fixed && row) {
                row.remove();
            } else if (act === 'up' && row && row.previousElementSibling) {
                list.insertBefore(row, row.previousElementSibling);
            } else if (act === 'down' && row && row.nextElementSibling) {
                list.insertBefore(row.nextElementSibling, row);
            } else {
                return;
            }
            e.preventDefault();
            renumber();
        });

        renumber();
    });
})();
</script>
@endpush

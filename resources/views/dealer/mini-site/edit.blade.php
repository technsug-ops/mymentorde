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
        $isOperationPartner = ($d?->dealer_type_code === 'b2b_partner')
            || ($d && $d->hasRole(\App\Models\Dealer::ROLE_B2B_PARTNER));
        $iconOptions = [
            'cap' => 'Mezuniyet / Üniversite', 'passport' => 'Vize / Pasaport',
            'coins' => 'Finans / Ödeme', 'home' => 'Konaklama / Ev', 'default' => 'Genel (kalkan)',
        ];
        $svcRows  = old('site_services', $d?->site_services ?: []);
        $statRows = old('site_stats',    $d?->site_stats ?: []);
        $teamRows = old('site_team',     $d?->site_team ?: []);
        $inp = 'width:100%;padding:9px 11px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:13px;';
        $sectionBox = 'border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px;margin:22px 0;background:var(--surface,#fff);';
        $templates  = \App\Support\PartnerTemplates::all();
        $currentTpl = old('site_template', $d?->site_template ?: \App\Support\PartnerTemplates::DEFAULT);
    @endphp

    @if($isOperationPartner)
        <div style="border-top:2px solid var(--border,#e2e8f0);margin:26px 0 4px;padding-top:6px;">
            <div style="font-size:12px;font-weight:700;color:var(--theme-accent-dealer,#1E3D6B);text-transform:uppercase;letter-spacing:.08em;">
                Operasyon Partner — Kurumsal Site Bölümleri
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

        {{-- Hizmet kartları (6 slot) --}}
        <div style="{{ $sectionBox }}">
            <div style="font-weight:600;font-size:14px;margin-bottom:4px;">Hizmet Kartları</div>
            <small style="color:var(--muted,#64748b);font-size:12px;">Boş bırakırsanız varsayılan 6 hizmet (kapsam maddeleriyle) gösterilir.</small>
            @for($i = 0; $i < 6; $i++)
                @php
                    $s = $svcRows[$i] ?? [];
                    $svcItems = $s['items'] ?? '';
                    if (is_array($svcItems)) { $svcItems = implode("\n", $svcItems); }
                @endphp
                <div style="border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:12px;margin-top:12px;background:var(--bg,#f8fafc);">
                    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:8px;">
                        <input type="text" name="site_services[{{ $i }}][title]" value="{{ $s['title'] ?? '' }}" maxlength="120" placeholder="Hizmet başlığı ({{ $i+1 }})" style="{{ $inp }}">
                        <select name="site_services[{{ $i }}][icon]" style="{{ $inp }}">
                            @foreach($iconOptions as $k => $lbl)
                                <option value="{{ $k }}" @selected(($s['icon'] ?? 'default') === $k)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="text" name="site_services[{{ $i }}][desc]" value="{{ $s['desc'] ?? '' }}" maxlength="400" placeholder="Kısa açıklama" style="{{ $inp }}margin-top:8px;">
                    <textarea name="site_services[{{ $i }}][items]" rows="3" maxlength="600" placeholder="Kapsam maddeleri — her satıra bir madde (opsiyonel, max 6)" style="{{ $inp }}margin-top:8px;resize:vertical;">{{ $svcItems }}</textarea>
                </div>
            @endfor
        </div>

        {{-- İstatistik rozetleri (4 slot) --}}
        <div style="{{ $sectionBox }}">
            <div style="font-weight:600;font-size:14px;margin-bottom:4px;">İstatistikler</div>
            <small style="color:var(--muted,#64748b);font-size:12px;">Örn. "500+" / "Mutlu Öğrenci". Boşsa bu bölüm gizlenir.</small>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px;">
                @for($i = 0; $i < 4; $i++)
                    @php $st = $statRows[$i] ?? []; @endphp
                    <div style="display:grid;grid-template-columns:.7fr 1.3fr;gap:6px;">
                        <input type="text" name="site_stats[{{ $i }}][value]" value="{{ $st['value'] ?? '' }}" maxlength="40" placeholder="500+" style="{{ $inp }}">
                        <input type="text" name="site_stats[{{ $i }}][label]" value="{{ $st['label'] ?? '' }}" maxlength="60" placeholder="Etiket" style="{{ $inp }}">
                    </div>
                @endfor
            </div>
        </div>

        {{-- Ekip kartları (6 slot) --}}
        <div style="{{ $sectionBox }}">
            <div style="font-weight:600;font-size:14px;margin-bottom:4px;">Ekip / Danışmanlar</div>
            <small style="color:var(--muted,#64748b);font-size:12px;">Fotoğraf yoksa isim baş harfi gösterilir. Tüm satırlar boşsa Ekip bölümü gizlenir.</small>
            @for($i = 0; $i < 6; $i++)
                @php $m = $teamRows[$i] ?? []; @endphp
                <div style="display:grid;grid-template-columns:1fr 1fr 1.4fr;gap:8px;margin-top:10px;">
                    <input type="text" name="site_team[{{ $i }}][name]"  value="{{ $m['name'] ?? '' }}"  maxlength="80"  placeholder="İsim Soyisim" style="{{ $inp }}">
                    <input type="text" name="site_team[{{ $i }}][title]" value="{{ $m['title'] ?? '' }}" maxlength="80"  placeholder="Ünvan" style="{{ $inp }}">
                    <input type="url"  name="site_team[{{ $i }}][photo]" value="{{ $m['photo'] ?? '' }}" maxlength="500" placeholder="Fotoğraf URL (opsiyonel)" style="{{ $inp }}">
                </div>
            @endfor
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

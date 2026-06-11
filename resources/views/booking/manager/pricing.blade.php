@extends('manager.layouts.app')
@section('title','Randevu Fiyat Yönetimi')
@section('page_title','Randevu Fiyat Yönetimi')

@section('content')
<style>
/* ═══════════════════ Pricing Cockpit v1 ═══════════════════ */
.pc-wrap { max-width:1200px; margin:18px auto 40px; padding:0 18px; }

/* Hero */
.pc-hero {
    background:linear-gradient(135deg,#7e58bf 0%,#5b3a99 100%);
    color:#fff; border-radius:16px; padding:22px 26px; margin-bottom:22px;
    display:flex; align-items:center; gap:18px;
}
.pc-hero-icon {
    width:54px; height:54px; border-radius:14px;
    background:rgba(255,255,255,.16); display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.pc-hero h1 { margin:0 0 4px; font-size:21px; font-weight:700; letter-spacing:-.01em; }
.pc-hero p  { margin:0; font-size:13px; opacity:.92; line-height:1.55; }

/* Layout: form sol, önizleme sağ */
.pc-grid { display:grid; grid-template-columns:1fr 340px; gap:22px; align-items:flex-start; }
@media (max-width:1024px){ .pc-grid { grid-template-columns:1fr; } }

/* Cards */
.pc-card {
    background:#fff; border:1px solid #e5e7eb; border-radius:14px;
    padding:22px; margin-bottom:18px; box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.pc-card-head { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.pc-card-head h2 { margin:0; font-size:16px; font-weight:700; color:#0f172a; }
.pc-card-head .pc-icon-pill {
    width:32px; height:32px; border-radius:10px; background:#f3eefa;
    color:#7e58bf; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.pc-card p.pc-hint { margin:0 0 18px; font-size:12.5px; color:#64748b; line-height:1.6; }

/* Status banners */
.pc-msg {
    display:flex; align-items:center; gap:10px;
    padding:12px 16px; border-radius:10px; font-size:13px; font-weight:500; margin-bottom:14px;
}
.pc-msg-ok    { background:#ecfdf5; border:1px solid #6ee7b7; color:#065f46; }
.pc-msg-warn  { background:#fffbeb; border:1px solid #fcd34d; color:#92400e; line-height:1.55; }

/* Free toggle hero */
.pc-free-toggle {
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);
    border:1px solid #fcd34d; border-radius:12px; padding:14px 18px; margin-bottom:18px;
    transition:opacity .2s;
}
.pc-free-toggle[data-on="1"] { background:linear-gradient(135deg,#d1fae5 0%,#a7f3d0 100%); border-color:#34d399; }
.pc-free-toggle .pc-label { font-weight:700; font-size:14px; color:#334155; display:flex; align-items:center; gap:10px; }
.pc-free-toggle .pc-sub   { font-size:11.5px; color:#475569; line-height:1.5; margin-top:4px; }

/* Switch */
.pc-switch { position:relative; display:inline-block; width:46px; height:26px; flex-shrink:0; }
.pc-switch input { opacity:0; width:0; height:0; }
.pc-switch-slider {
    position:absolute; cursor:pointer; inset:0;
    background:#cbd5e1; border-radius:13px; transition:.2s;
}
.pc-switch-slider::before {
    content:""; position:absolute; left:3px; top:3px; width:20px; height:20px;
    background:#fff; border-radius:50%; transition:.2s;
    box-shadow:0 1px 3px rgba(0,0,0,.18);
}
.pc-switch input:checked + .pc-switch-slider { background:#7e58bf; }
.pc-switch input:checked + .pc-switch-slider::before { transform:translateX(20px); }

/* Paket kartları */
.pc-pkg-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
@media (max-width:900px){ .pc-pkg-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:520px){ .pc-pkg-grid { grid-template-columns:1fr; } }

.pc-pkg {
    background:#fafafa; border:2px solid #e5e7eb; border-radius:12px; padding:14px;
    transition:all .15s;
}
.pc-pkg[data-on="1"] { background:#fff; border-color:#7e58bf; box-shadow:0 4px 12px rgba(126,88,191,.12); }
.pc-pkg-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.pc-pkg-dur  { font-size:20px; font-weight:800; color:#0f172a; letter-spacing:-.02em; }
.pc-pkg-dur small { font-size:11px; font-weight:600; color:#64748b; margin-left:2px; }
.pc-pkg-price-wrap { position:relative; }
.pc-pkg-price-wrap .pc-currency {
    position:absolute; left:10px; top:50%; transform:translateY(-50%);
    color:#64748b; font-size:13px; font-weight:600; pointer-events:none;
}
.pc-pkg-price {
    width:100%; padding:9px 10px 9px 26px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:14px; font-weight:600; color:#0f172a; background:#fff; box-sizing:border-box;
}
.pc-pkg-price:disabled { background:#f1f5f9; color:#94a3b8; cursor:not-allowed; }

/* Policy + Form grids */
.pc-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.pc-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
@media (max-width:640px){ .pc-grid-2,.pc-grid-3 { grid-template-columns:1fr; } }

.pc-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:6px; }
.pc-field select, .pc-field input[type=number], .pc-field textarea {
    width:100%; padding:9px 11px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:13.5px; background:#fff; box-sizing:border-box; font-family:inherit;
}
.pc-field textarea { min-height:90px; resize:vertical; line-height:1.55; }
.pc-field .pc-help { font-size:11px; color:#64748b; margin-top:4px; line-height:1.5; }

/* Inline toggle row */
.pc-inline-toggle {
    display:flex; align-items:center; justify-content:space-between; gap:14px;
    padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;
}
.pc-inline-toggle .pc-it-label { font-weight:600; font-size:13px; color:#0f172a; }
.pc-inline-toggle .pc-it-sub { font-size:11.5px; color:#64748b; margin-top:2px; }

/* Save bar */
.pc-savebar {
    position:sticky; bottom:14px; background:rgba(255,255,255,.97); backdrop-filter:blur(8px);
    border:1px solid #e5e7eb; border-radius:12px; padding:12px 16px;
    display:flex; align-items:center; justify-content:flex-end; gap:12px; margin-top:14px;
    box-shadow:0 4px 14px rgba(15,23,42,.08);
}
.pc-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border:none; border-radius:10px;
    font-size:13.5px; font-weight:700; cursor:pointer; font-family:inherit;
    transition:transform .12s, box-shadow .15s;
}
.pc-btn:hover { transform:translateY(-1px); }
.pc-btn-primary { background:#7e58bf; color:#fff; box-shadow:0 2px 6px rgba(126,88,191,.3); }
.pc-btn-primary:hover { box-shadow:0 4px 12px rgba(126,88,191,.4); }

/* Önizleme paneli */
.pc-preview {
    position:sticky; top:80px;
    background:#fff; border:1px solid #e5e7eb; border-radius:14px;
    padding:20px; box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.pc-preview h3 {
    margin:0 0 12px; font-size:13px; font-weight:700; color:#334155;
    text-transform:uppercase; letter-spacing:.06em; display:flex; align-items:center; gap:8px;
}
.pc-preview-row {
    display:flex; align-items:baseline; justify-content:space-between;
    padding:10px 0; border-bottom:1px dashed #e5e7eb;
}
.pc-preview-row:last-child { border-bottom:none; }
.pc-preview-row.pc-disabled { opacity:.4; }
.pc-preview-dur { font-size:14px; font-weight:600; color:#0f172a; }
.pc-preview-price { font-size:15px; font-weight:800; color:#7e58bf; letter-spacing:-.01em; }
.pc-preview-price.pc-free { color:#10b981; }
.pc-preview-empty {
    padding:18px 12px; text-align:center; font-size:12.5px; color:#94a3b8; line-height:1.6;
}
.pc-preview-foot {
    margin-top:14px; padding-top:14px; border-top:1px solid #e5e7eb;
    font-size:11.5px; color:#64748b; line-height:1.6; display:flex; gap:8px; align-items:flex-start;
}
.pc-preview-foot strong { color:#334155; }

/* Tax/payment/commission alt panel başlığı */
.pc-advanced-toggle {
    width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;
    padding:14px 18px; display:flex; align-items:center; justify-content:space-between;
    cursor:pointer; font-size:13px; font-weight:600; color:#334155; margin:24px 0 0;
    font-family:inherit;
}
.pc-advanced-toggle:hover { background:#f1f5f9; }
.pc-advanced-panel { margin-top:14px; display:none; }
.pc-advanced-panel[data-open="1"] { display:block; }

/* Legacy bmp- styles (tax/commission tables tek dosyada kalsın) */
.bmp-table { width:100%; border-collapse:collapse; font-size:13px; }
.bmp-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#64748b; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
.bmp-table td { padding:10px; border-bottom:1px solid #f1f5f9; }
.bmp-table input[type=number], .bmp-table input[type=text] { width:100%; padding:6px 8px; font-size:13px; border:1px solid #cbd5e1; border-radius:6px; }
.bmp-badge { display:inline-block; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.bmp-badge.green { background:#dcfce7; color:#166534; }
.bmp-badge.red { background:#fee2e2; color:#991b1b; }
.bmp-btn { padding:6px 12px; border:none; border-radius:7px; font-size:12px; font-weight:600; cursor:pointer; font-family:inherit; }
.bmp-btn-ghost  { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
.bmp-btn-danger { background:#dc2626; color:#fff; }
.bmp-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
@media(max-width:640px){ .bmp-grid-3 { grid-template-columns:1fr; } }
.bmp-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
.bmp-field input, .bmp-field select {
    width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; box-sizing:border-box;
}
</style>

<div class="pc-wrap">

    {{-- ═══════ HERO ═══════ --}}
    <div class="pc-hero">
        <div class="pc-hero-icon"><x-icon name="euro" size="28" /></div>
        <div>
            <h1>Randevu Fiyat Yönetimi</h1>
            <p>Hangi süreleri satacağınızı seçin, paket fiyatlarını belirleyin. Sözleşmeli kullanıcılar (öğrenci) her zaman ücretsizdir — bu ayarlar guest + public ziyaretçi içindir.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="pc-msg pc-msg-ok">
            <x-icon name="check" size="18" /> {{ session('status') }}
        </div>
    @endif

    {{-- Ödeme modülü uyarısı (CompanyPaymentSetting kapalıysa) --}}
    @if (!$paymentSetting->is_payment_enabled)
        <div class="pc-msg pc-msg-warn">
            <x-icon name="info" size="18" />
            <div>
                <strong>Ödeme modülü şu an KAPALI.</strong> Tutarlar burada tanımlanır ancak Stripe akışı devrede değil — her randevu otomatik "ücretsiz" path'ine düşer. Muhasebeci onayı ve Stripe anahtarları sonrası alttaki <em>Ödeme Ayarları</em> kartından aktive edebilirsiniz.
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('manager.booking-pricing.update') }}" id="pc-form">
        @csrf

        <div class="pc-grid">
            {{-- ═══════ SOL: AYAR KOLONU ═══════ --}}
            <div>
                {{-- 1. Ücretsiz mod toggle --}}
                <div class="pc-card">
                    <div class="pc-card-head">
                        <span class="pc-icon-pill"><x-icon name="sparkles" size="18" /></span>
                        <h2>Ücretsiz mod</h2>
                    </div>
                    <p class="pc-hint">Açıkken tüm guest/public randevular ücretsiz alınır ve paket fiyatları devre dışı kalır. Stripe entegrasyonu kuruluncaya kadar burayı açık tutmanız önerilir.</p>

                    <div class="pc-free-toggle" id="pc-free-toggle-wrap" data-on="{{ $pricing->is_free ? '1' : '0' }}">
                        <div>
                            <div class="pc-label">
                                <x-icon name="party-popper" size="20" />
                                <span>Tüm randevular ücretsiz</span>
                            </div>
                            <div class="pc-sub">Paket fiyatları yok sayılır, senior'lar randevu için ödeme almaz.</div>
                        </div>
                        <label class="pc-switch">
                            <input type="checkbox" name="is_free" id="pc-is-free" value="1" @checked($pricing->is_free)>
                            <span class="pc-switch-slider"></span>
                        </label>
                    </div>
                </div>

                {{-- 2. Paket fiyat kartları --}}
                <div class="pc-card" id="pc-packages-card">
                    <div class="pc-card-head">
                        <span class="pc-icon-pill"><x-icon name="clock" size="18" /></span>
                        <h2>Paket fiyatları</h2>
                    </div>
                    <p class="pc-hint">4 standart paketten istediklerinizi etkinleştirin. Senior'lar yalnızca <strong>etkin</strong> sürelerden randevu sunabilir. Fiyatlar KDV hariç net tutardır.</p>

                    @php
                        // V1 cockpit'te 4 ana paket: 15/30/60/90 dk
                        $cockpitDurations = [
                            ['dur' => 15, 'default' => 15, 'enabled' => false],
                            ['dur' => 30, 'default' => 30, 'enabled' => true ],
                            ['dur' => 60, 'default' => 50, 'enabled' => true ],
                            ['dur' => 90, 'default' => 70, 'enabled' => false],
                        ];
                        $rulesByDur = collect($pricing->pricing_rules ?: [])->keyBy('duration');
                    @endphp

                    <div class="pc-pkg-grid" id="pc-pkg-grid">
                        @foreach ($cockpitDurations as $i => $pkg)
                            @php
                                $existing = $rulesByDur->get($pkg['dur']);
                                $enabled  = $existing ? (bool)($existing['enabled'] ?? false) : (bool)$pkg['enabled'];
                                $price    = $existing ? (float)($existing['price_net'] ?? 0) : (float)$pkg['default'];
                            @endphp
                            <div class="pc-pkg" data-on="{{ $enabled ? '1' : '0' }}" data-pkg="{{ $pkg['dur'] }}">
                                <input type="hidden" name="rules[{{ $i }}][duration]" value="{{ $pkg['dur'] }}">

                                <div class="pc-pkg-head">
                                    <div class="pc-pkg-dur">{{ $pkg['dur'] }}<small>dk</small></div>
                                    <label class="pc-switch">
                                        <input type="checkbox"
                                               class="pc-pkg-enable"
                                               name="rules[{{ $i }}][enabled]"
                                               value="1"
                                               data-pkg="{{ $pkg['dur'] }}"
                                               @checked($enabled)>
                                        <span class="pc-switch-slider"></span>
                                    </label>
                                </div>

                                <div class="pc-pkg-price-wrap">
                                    <span class="pc-currency">{{ $pricing->currency === 'TRY' ? '₺' : ($pricing->currency === 'USD' ? '$' : ($pricing->currency === 'GBP' ? '£' : '€')) }}</span>
                                    <input type="number"
                                           class="pc-pkg-price"
                                           name="rules[{{ $i }}][price_net]"
                                           value="{{ number_format($price, 2, '.', '') }}"
                                           min="0" max="999" step="0.50"
                                           data-pkg="{{ $pkg['dur'] }}"
                                           {{ $enabled ? '' : 'disabled' }}>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- 3. Politika --}}
                <div class="pc-card">
                    <div class="pc-card-head">
                        <span class="pc-icon-pill"><x-icon name="sliders" size="18" /></span>
                        <h2>İptal &amp; randevu politikası</h2>
                    </div>
                    <p class="pc-hint">Misafirinin randevuyu ne kadar önceden iptal/değiştirebileceği ve ne kadar ileriye randevu alabileceği.</p>

                    <div class="pc-grid-3">
                        <div class="pc-field">
                            <label>İptal penceresi</label>
                            <select name="cancellation_window_hours">
                                @foreach ([0, 6, 12, 24, 48, 72, 168] as $h)
                                    <option value="{{ $h }}" @selected((int)$pricing->cancellation_window_hours === $h)>
                                        {{ $h === 0 ? 'İptal yok' : ($h >= 24 ? ($h/24).' gün' : $h.' saat') }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="pc-help">Randevu öncesi bu süreye kadar iptal mümkün.</div>
                        </div>

                        <div class="pc-field">
                            <label>Maks. ileriye randevu</label>
                            <input type="number"
                                   name="max_advance_booking_days"
                                   value="{{ $pricing->max_advance_booking_days ?? 60 }}"
                                   min="1" max="365">
                            <div class="pc-help">Bugünden kaç gün sonraya kadar booking açık (gün).</div>
                        </div>

                        <div class="pc-field">
                            <label>Para birimi</label>
                            <select name="currency" id="pc-currency">
                                @foreach (['EUR', 'USD', 'TRY', 'GBP'] as $c)
                                    <option value="{{ $c }}" @selected($pricing->currency === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                            <div class="pc-help">Tüm paket fiyatları bu cinstendir.</div>
                        </div>
                    </div>

                    <div style="margin-top:14px;">
                        <label class="pc-inline-toggle" for="pc-reschedule">
                            <div>
                                <div class="pc-it-label">Invitee yeniden tarihlendirebilsin</div>
                                <div class="pc-it-sub">Açıkken misafir randevuyu farklı bir slota taşıyabilir (iptal penceresi içinde).</div>
                            </div>
                            <span class="pc-switch">
                                <input type="checkbox" name="allow_invitee_reschedule" id="pc-reschedule" value="1" @checked($pricing->allow_invitee_reschedule ?? true)>
                                <span class="pc-switch-slider"></span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- 4. Booking şartları --}}
                <div class="pc-card">
                    <div class="pc-card-head">
                        <span class="pc-icon-pill"><x-icon name="file-text" size="18" /></span>
                        <h2>Booking şartları</h2>
                    </div>
                    <p class="pc-hint">Randevu onay sayfasının footer'ında gösterilecek metin. İptal politikası, iade koşulları, KVKK notu vb.</p>

                    <div class="pc-field">
                        <textarea name="booking_terms" maxlength="4000" placeholder="Örn: Randevular 24 saat öncesine kadar iptal edilebilir. İade işlemleri 5 iş günü içinde tamamlanır...">{{ $pricing->booking_terms }}</textarea>
                        <div class="pc-help">Maks 4000 karakter — Markdown desteklenmez, düz metin olarak gösterilir.</div>
                    </div>
                </div>

                {{-- Save bar --}}
                <div class="pc-savebar">
                    <button type="submit" class="pc-btn pc-btn-primary">
                        <x-icon name="check" size="18" /> Değişiklikleri Kaydet
                    </button>
                </div>
            </div>

            {{-- ═══════ SAĞ: ÖNİZLEME ═══════ --}}
            <aside>
                <div class="pc-preview" id="pc-preview">
                    <h3><x-icon name="eye" size="16" /> Önizleme</h3>

                    <div style="font-size:12px; color:#64748b; line-height:1.55; margin-bottom:12px;">
                        Şu an senior'larınız bu paketleri sunabilir:
                    </div>

                    <div id="pc-preview-rows">
                        {{-- JS dolduracak --}}
                    </div>

                    <div id="pc-preview-empty" class="pc-preview-empty" style="display:none;">
                        Aktif paket yok. En az bir paketi etkinleştirin.
                    </div>

                    <div class="pc-preview-foot">
                        <x-icon name="info" size="14" />
                        <div>
                            <strong>İptal:</strong> <span id="pc-preview-cancel"></span><br>
                            <strong>Maks ileri:</strong> <span id="pc-preview-max"></span> gün<br>
                            <strong>Yeniden tarihlendirme:</strong> <span id="pc-preview-rs"></span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    {{-- ═══════ GELİŞMİŞ AYARLAR (TAX + PAYMENT + COMMISSION) ═══════ --}}
    <button type="button" class="pc-advanced-toggle" id="pc-advanced-toggle">
        <span style="display:flex; align-items:center; gap:10px;">
            <x-icon name="cog" size="16" /> Gelişmiş muhasebe ayarları (KDV, ödeme, komisyon)
        </span>
        <x-icon name="chevron-right" size="16" />
    </button>

    <div class="pc-advanced-panel" id="pc-advanced-panel">

        {{-- ─── KDV KURALLARI ─── --}}
        <div class="pc-card" style="margin-top:14px;">
            <div class="pc-card-head">
                <span class="pc-icon-pill"><x-icon name="file-text" size="18" /></span>
                <h2>KDV Kuralları</h2>
            </div>
            <p class="pc-hint">Yüksek priority önce denenir. Eşleşen ilk aktif kural uygulanır (ülke + müşteri tipi).</p>

            <table class="bmp-table" style="margin-bottom:14px;">
                <thead><tr><th>Ad</th><th>Ülke</th><th>Müşteri</th><th>Oran</th><th>Kod</th><th>Öncelik</th><th>Durum</th><th></th></tr></thead>
                <tbody>
                @forelse ($taxRules as $r)
                    <tr>
                        <td style="font-weight:600;">{{ $r->rule_name }}</td>
                        <td>{{ $r->match_country_code ?? '—' }}</td>
                        <td>{{ $r->match_customer_type ?? 'hepsi' }}</td>
                        <td>%{{ number_format((float)$r->tax_rate_pct, 2, ',', '.') }}</td>
                        <td><code style="font-size:11px;color:#64748b;">{{ $r->tax_code }}</code></td>
                        <td>{{ $r->priority }}</td>
                        <td>
                            @if ($r->is_active)<span class="bmp-badge green">Aktif</span>
                            @else<span class="bmp-badge red">Pasif</span>@endif
                        </td>
                        <td style="display:flex; gap:6px;">
                            <form method="POST" action="{{ route('manager.booking-pricing.tax.toggle', $r) }}" style="display:inline;">@csrf
                                <button type="submit" class="bmp-btn bmp-btn-ghost">{{ $r->is_active ? 'Kapat' : 'Aç' }}</button>
                            </form>
                            <form method="POST" action="{{ route('manager.booking-pricing.tax.destroy', $r) }}" style="display:inline;" data-confirm="Sil?">@csrf @method('DELETE')
                                <button type="submit" class="bmp-btn bmp-btn-danger"><x-icon name="trash" size="12" /></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:18px;">KDV kuralı yok. Default = %0 (muaf).</td></tr>
                @endforelse
                </tbody>
            </table>

            <details>
                <summary style="cursor:pointer; font-weight:600; color:#7e58bf; font-size:13px;">
                    <x-icon name="plus" size="14" class="inline-icon" /> Yeni KDV Kuralı
                </summary>
                <form method="POST" action="{{ route('manager.booking-pricing.tax.store') }}" style="margin-top:12px; padding:14px; background:#fafafa; border-radius:10px;">
                    @csrf
                    <div class="bmp-grid-3" style="margin-bottom:10px;">
                        <div class="bmp-field"><label>Ad</label><input type="text" name="rule_name" required maxlength="120" placeholder="Örn: TR muafiyet"></div>
                        <div class="bmp-field"><label>Ülke kodu (2 harf)</label><input type="text" name="match_country_code" maxlength="2" placeholder="DE/TR/FR…"></div>
                        <div class="bmp-field"><label>Müşteri tipi</label>
                            <select name="match_customer_type">
                                <option value="">Hepsi</option><option value="b2c">B2C</option><option value="b2b">B2B</option>
                            </select>
                        </div>
                    </div>
                    <div class="bmp-grid-3" style="margin-bottom:10px;">
                        <div class="bmp-field"><label>KDV %</label><input type="number" step="0.01" name="tax_rate_pct" required min="0" max="100" value="0"></div>
                        <div class="bmp-field"><label>Kod</label>
                            <select name="tax_code">
                                <option value="exempt">exempt (muaf)</option>
                                <option value="standard">standard</option>
                                <option value="reduced">reduced</option>
                                <option value="reverse_charge">reverse_charge</option>
                            </select>
                        </div>
                        <div class="bmp-field"><label>Öncelik</label><input type="number" name="priority" required min="1" max="100" value="10"></div>
                    </div>
                    <div class="bmp-field" style="margin-bottom:10px;"><label>Fatura notu</label><input type="text" name="invoice_note" maxlength="500"></div>
                    <label style="display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                        <input type="checkbox" name="is_active" value="1"> <span>Aktif</span>
                    </label>
                    <button type="submit" class="pc-btn pc-btn-primary"><x-icon name="plus" size="14" /> Ekle</button>
                </form>
            </details>
        </div>

        {{-- ─── ÖDEME + PAYOUT ─── --}}
        <div class="pc-card">
            <div class="pc-card-head">
                <span class="pc-icon-pill"><x-icon name="wallet" size="18" /></span>
                <h2>Ödeme &amp; Payout</h2>
            </div>
            <p class="pc-hint"><strong>is_payment_enabled</strong> Stripe canlı ödeme alımı flag'idir. Muhasebeci onaylamadan true yapmayın.</p>

            <form method="POST" action="{{ route('manager.booking-pricing.payment.update') }}">
                @csrf

                <label class="pc-inline-toggle" style="margin-bottom:14px;">
                    <div>
                        <div class="pc-it-label">Stripe canlı ödeme alımı</div>
                        <div class="pc-it-sub">Açıkken booking'ler net + KDV ile Stripe'a yönlendirilir. Anahtarlar env'de olmalı.</div>
                    </div>
                    <span class="pc-switch">
                        <input type="checkbox" name="is_payment_enabled" value="1" @checked($paymentSetting->is_payment_enabled)>
                        <span class="pc-switch-slider"></span>
                    </span>
                </label>

                <div class="bmp-grid-3">
                    <div class="bmp-field"><label>Payout günü (ayın X'i)</label>
                        <input type="number" name="payout_day_of_month" min="1" max="28" value="{{ $paymentSetting->payout_day_of_month }}">
                    </div>
                    <div class="bmp-field"><label>Min. payout eşiği (EUR)</label>
                        <input type="number" step="0.01" name="payout_minimum_eur" min="0" max="10000"
                               value="{{ number_format($paymentSetting->payout_minimum_cents / 100, 2, '.', '') }}">
                    </div>
                    <div class="bmp-field"><label>On-demand payout</label>
                        <label style="display:flex; align-items:center; gap:6px; padding-top:6px; font-size:12px; color:#64748b;">
                            <input type="checkbox" name="allow_on_demand_payout" value="1" @checked($paymentSetting->allow_on_demand_payout)>
                            <span>Senior eşik geçince çekebilsin</span>
                        </label>
                    </div>
                    <div class="bmp-field"><label>Default komisyon %</label>
                        <input type="number" step="0.01" name="default_commission_pct" min="0" max="100"
                               value="{{ number_format((float)$paymentSetting->default_commission_pct, 2, '.', '') }}">
                    </div>
                    <div class="bmp-field"><label>İade penceresi (saat)</label>
                        <input type="number" name="refund_window_hours" min="0" max="168" value="{{ $paymentSetting->refund_window_hours }}">
                    </div>
                </div>

                <div style="margin-top:14px;">
                    <button type="submit" class="pc-btn pc-btn-primary"><x-icon name="check" size="14" /> Ödeme Ayarlarını Kaydet</button>
                </div>
            </form>
        </div>

        {{-- ─── KOMİSYON KURALLARI ─── --}}
        <div class="pc-card">
            <div class="pc-card-head">
                <span class="pc-icon-pill"><x-icon name="users" size="18" /></span>
                <h2>Komisyon Kuralları</h2>
            </div>
            <p class="pc-hint">Senior tier + hizmet türü matrix. Kural yok → default komisyon uygulanır.</p>

            <table class="bmp-table" style="margin-bottom:14px;">
                <thead><tr><th>Ad</th><th>Tier</th><th>Servis</th><th>Oran</th><th>Öncelik</th><th>Durum</th><th></th></tr></thead>
                <tbody>
                @forelse ($commissionRules as $r)
                    <tr>
                        <td style="font-weight:600;">{{ $r->rule_name }}</td>
                        <td>{{ $r->applies_to_tier ?? 'hepsi' }}</td>
                        <td>{{ $r->applies_to_service_type ?? 'hepsi' }}</td>
                        <td>%{{ number_format((float)$r->commission_pct, 2, ',', '.') }}</td>
                        <td>{{ $r->priority }}</td>
                        <td>@if ($r->is_active)<span class="bmp-badge green">Aktif</span>@else<span class="bmp-badge red">Pasif</span>@endif</td>
                        <td>
                            <form method="POST" action="{{ route('manager.booking-pricing.commission.destroy', $r) }}" data-confirm="Sil?" style="display:inline;">@csrf @method('DELETE')
                                <button type="submit" class="bmp-btn bmp-btn-danger"><x-icon name="trash" size="12" /></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:18px;">Komisyon kuralı yok — default uygulanıyor.</td></tr>
                @endforelse
                </tbody>
            </table>

            <details>
                <summary style="cursor:pointer; font-weight:600; color:#7e58bf; font-size:13px;">
                    <x-icon name="plus" size="14" /> Yeni Komisyon Kuralı
                </summary>
                <form method="POST" action="{{ route('manager.booking-pricing.commission.store') }}" style="margin-top:12px; padding:14px; background:#fafafa; border-radius:10px;">
                    @csrf
                    <div class="bmp-grid-3" style="margin-bottom:10px;">
                        <div class="bmp-field"><label>Ad</label><input type="text" name="rule_name" required maxlength="120" placeholder="Örn: Expert tier %15"></div>
                        <div class="bmp-field"><label>Tier (ops.)</label><input type="text" name="applies_to_tier" maxlength="32" placeholder="junior/mid/senior"></div>
                        <div class="bmp-field"><label>Servis türü (ops.)</label><input type="text" name="applies_to_service_type" maxlength="32"></div>
                    </div>
                    <div class="bmp-grid-3" style="margin-bottom:10px;">
                        <div class="bmp-field"><label>Komisyon %</label><input type="number" step="0.01" name="commission_pct" required min="0" max="100" value="20"></div>
                        <div class="bmp-field"><label>Öncelik</label><input type="number" name="priority" required min="1" max="100" value="10"></div>
                        <div class="bmp-field"><label>&nbsp;</label>
                            <label style="display:flex; align-items:center; gap:6px; padding-top:6px; font-size:12px;">
                                <input type="checkbox" name="is_active" value="1" checked> <span>Aktif</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="pc-btn pc-btn-primary"><x-icon name="plus" size="14" /> Ekle</button>
                </form>
            </details>
        </div>

    </div>{{-- /pc-advanced-panel --}}

</div>

{{-- ═══════ JS: CSP-safe nonce'lu blok ═══════ --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    'use strict';

    var $form       = document.getElementById('pc-form');
    var $isFree     = document.getElementById('pc-is-free');
    var $freeWrap   = document.getElementById('pc-free-toggle-wrap');
    var $pkgGrid    = document.getElementById('pc-pkg-grid');
    var $previewRow = document.getElementById('pc-preview-rows');
    var $previewEmp = document.getElementById('pc-preview-empty');
    var $previewCancel = document.getElementById('pc-preview-cancel');
    var $previewMax    = document.getElementById('pc-preview-max');
    var $previewRs     = document.getElementById('pc-preview-rs');
    var $currency      = document.getElementById('pc-currency');
    var $cancelSel     = $form.querySelector('select[name="cancellation_window_hours"]');
    var $maxAdv        = $form.querySelector('input[name="max_advance_booking_days"]');
    var $rs            = document.getElementById('pc-reschedule');

    function currencySymbol(c){
        if (c === 'TRY') return '₺';
        if (c === 'USD') return '$';
        if (c === 'GBP') return '£';
        return '€';
    }

    function syncPkgEnable(){
        var disabled = $isFree.checked;
        $freeWrap.setAttribute('data-on', disabled ? '1' : '0');

        $pkgGrid.querySelectorAll('.pc-pkg').forEach(function(card){
            var sw    = card.querySelector('.pc-pkg-enable');
            var price = card.querySelector('.pc-pkg-price');
            var on    = sw.checked && !disabled;
            card.setAttribute('data-on', on ? '1' : '0');
            sw.disabled = disabled;
            price.disabled = !on;
        });
        renderPreview();
    }

    function renderPreview(){
        // Currency simgesini kartlarda güncelle
        var sym = currencySymbol($currency.value);
        $pkgGrid.querySelectorAll('.pc-pkg .pc-currency').forEach(function(el){ el.textContent = sym; });

        // Preview row'ları yeniden çiz
        var rows = '';
        var anyEnabled = false;
        var isFree = $isFree.checked;

        $pkgGrid.querySelectorAll('.pc-pkg').forEach(function(card){
            var dur   = card.getAttribute('data-pkg');
            var sw    = card.querySelector('.pc-pkg-enable');
            var price = parseFloat(card.querySelector('.pc-pkg-price').value || '0');
            var on    = sw.checked && !isFree;

            if (sw.checked) {
                anyEnabled = true;
            }

            var label = dur + ' dk';
            var priceHtml;
            if (isFree) {
                priceHtml = '<span class="pc-preview-price pc-free">Ucretsiz</span>';
            } else if (!on) {
                priceHtml = '<span class="pc-preview-price" style="color:#94a3b8;">—</span>';
            } else {
                priceHtml = '<span class="pc-preview-price">' + sym + price.toFixed(2) + '</span>';
            }

            rows += '<div class="pc-preview-row' + (on || isFree ? '' : ' pc-disabled') + '">' +
                '<span class="pc-preview-dur">' + label + '</span>' +
                priceHtml +
                '</div>';
        });

        if (isFree) {
            // Ucretsiz modda hicbiri "kapali" gozukmesin, hepsi gosterilsin
            $previewEmp.style.display = 'none';
            $previewRow.innerHTML = rows;
        } else if (!anyEnabled) {
            $previewRow.innerHTML = '';
            $previewEmp.style.display = '';
        } else {
            $previewEmp.style.display = 'none';
            $previewRow.innerHTML = rows;
        }

        // Foot
        var ch = parseInt($cancelSel.value || '0', 10);
        $previewCancel.textContent = ch === 0
            ? 'Iptal yok'
            : (ch >= 24 ? (ch/24) + ' gun once' : ch + ' saat once');

        $previewMax.textContent = $maxAdv.value || '0';
        $previewRs.textContent  = $rs.checked ? 'acik' : 'kapali';
    }

    // ── Event listeners (CSP-safe) ──
    $isFree.addEventListener('change', syncPkgEnable);
    $currency.addEventListener('change', renderPreview);
    $cancelSel.addEventListener('change', renderPreview);
    $maxAdv.addEventListener('input', renderPreview);
    $rs.addEventListener('change', renderPreview);

    $pkgGrid.querySelectorAll('.pc-pkg-enable').forEach(function(sw){
        sw.addEventListener('change', function(){
            var card  = sw.closest('.pc-pkg');
            var price = card.querySelector('.pc-pkg-price');
            var on    = sw.checked && !$isFree.checked;
            card.setAttribute('data-on', on ? '1' : '0');
            price.disabled = !on;
            renderPreview();
        });
    });

    $pkgGrid.querySelectorAll('.pc-pkg-price').forEach(function(inp){
        inp.addEventListener('input', renderPreview);
    });

    // Advanced panel toggle
    var $advBtn   = document.getElementById('pc-advanced-toggle');
    var $advPanel = document.getElementById('pc-advanced-panel');
    if ($advBtn && $advPanel) {
        $advBtn.addEventListener('click', function(){
            var open = $advPanel.getAttribute('data-open') === '1';
            $advPanel.setAttribute('data-open', open ? '0' : '1');
            $advBtn.querySelector('svg:last-of-type')?.style.setProperty('transform', open ? '' : 'rotate(90deg)');
        });
    }

    // data-confirm form'lar (delete butonları)
    document.querySelectorAll('form[data-confirm]').forEach(function(f){
        f.addEventListener('submit', function(e){
            if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    // Init
    syncPkgEnable();
})();
</script>

@endsection

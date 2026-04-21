@extends('manager.layouts.app')
@section('title','Randevu Fiyatlandırması')
@section('page_title','💰 Randevu Fiyatlandırması')

@section('content')
<style>
.bmp-wrap { max-width:1100px; margin:20px auto; padding:0 16px; }
.bmp-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.bmp-card h2 { margin:0 0 6px; font-size:17px; color:#0f172a; }
.bmp-card p.hint { margin:0 0 16px; font-size:12px; color:#64748b; line-height:1.65; }
.bmp-msg-ok { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
.bmp-msg-warn { background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:10px 14px; border-radius:8px; font-size:12px; margin-bottom:12px; line-height:1.6; }
.bmp-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
.bmp-field input, .bmp-field select, .bmp-field textarea {
    width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:13px; background:#fff; box-sizing:border-box;
}
.bmp-btn { padding:10px 18px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
.bmp-btn-primary { background:#0f172a; color:#fff; }
.bmp-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
.bmp-btn-danger { background:#dc2626; color:#fff; }
.bmp-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.bmp-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
@media(max-width:640px){ .bmp-grid-2,.bmp-grid-3 { grid-template-columns:1fr; } }
.bmp-table { width:100%; border-collapse:collapse; font-size:13px; }
.bmp-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#64748b; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
.bmp-table td { padding:10px; border-bottom:1px solid #f1f5f9; }
.bmp-table input[type=number], .bmp-table input[type=text] { width:100%; padding:6px 8px; font-size:13px; border:1px solid #cbd5e1; border-radius:6px; }
.bmp-badge { display:inline-block; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.bmp-badge.green { background:#dcfce7; color:#166534; }
.bmp-badge.red { background:#fee2e2; color:#991b1b; }
.bmp-badge.blue { background:#dbeafe; color:#1e40af; }
.bmp-toggle-wrap { display:flex; align-items:center; gap:10px; background:#fff7ed; border:1px solid #fed7aa; padding:12px 14px; border-radius:8px; margin-bottom:14px; }
</style>

<div class="bmp-wrap">

    @if (session('status'))
        <div class="bmp-msg-ok">✅ {{ session('status') }}</div>
    @endif

    <div class="bmp-msg-warn">
        ⚠️ <strong>Ödeme modülü şu an KAPALI.</strong> Tutarlar ve KDV kuralları burada tanımlanır ama canlı ödeme alınmaz. Muhasebeci onayı sonrası aşağıdaki "Ödeme Ayarları" kartından aktif edilir. Stripe anahtarları eklenene kadar herhangi bir tutar için booking onayı otomatik "ücretsiz" path'ine düşer.
    </div>

    {{-- ═══════ 1. FİYATLANDIRMA TABLOSU ═══════ --}}
    <div class="bmp-card">
        <h2>💵 Fiyatlandırma (KDV Hariç Net)</h2>
        <p class="hint">
            Her süre için <strong>KDV hariç net</strong> tutar gir. Aktif sürelerin ayarlanmamışları senior'un seçeneklerinden kaldırılır.
            Sözleşmeli (student/signed guest) kullanıcılar <strong>her zaman ücretsiz</strong> randevu alır — bu tablo sadece guest-non-contracted + public için uygulanır.
        </p>

        <form method="POST" action="{{ route('manager.booking-pricing.update') }}">
            @csrf
            <div class="bmp-grid-3" style="margin-bottom:14px;">
                <div class="bmp-field">
                    <label>
                        <input type="checkbox" name="is_free" value="1" @checked($pricing->is_free) style="margin-right:4px;">
                        Tüm kullanıcılara ücretsiz
                    </label>
                </div>
                <div class="bmp-field">
                    <label>Para birimi</label>
                    <select name="currency">
                        @foreach (['EUR', 'USD', 'TRY', 'GBP'] as $c)
                            <option value="{{ $c }}" @selected($pricing->currency === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bmp-field">
                    <label>İptal penceresi (saat)</label>
                    <input type="number" name="cancellation_window_hours" value="{{ $pricing->cancellation_window_hours }}" min="0" max="168">
                </div>
            </div>

            <table class="bmp-table">
                <thead>
                    <tr><th>Süre</th><th>Net Fiyat</th><th>Aktif</th></tr>
                </thead>
                <tbody>
                    @php
                        $currentRules = $pricing->pricing_rules ?: $defaultRules;
                        $rulesByDur = collect($currentRules)->keyBy('duration');
                    @endphp
                    @foreach ($defaultRules as $i => $def)
                        @php $r = $rulesByDur->get($def['duration'], $def); @endphp
                        <tr>
                            <td style="font-weight:700;">{{ $def['duration'] }} dk</td>
                            <td>
                                <input type="hidden" name="rules[{{ $i }}][duration]" value="{{ $def['duration'] }}">
                                <input type="number" step="0.01" name="rules[{{ $i }}][price_net]" value="{{ number_format((float)($r['price_net'] ?? 0), 2, '.', '') }}" min="0" max="9999" style="max-width:140px;">
                            </td>
                            <td>
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                    <input type="checkbox" name="rules[{{ $i }}][enabled]" value="1" @checked((bool)($r['enabled'] ?? false))>
                                    <span>Seçilebilir</span>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:14px;">
                <button type="submit" class="bmp-btn bmp-btn-primary">💾 Fiyatları Kaydet</button>
            </div>
        </form>
    </div>

    {{-- ═══════ 2. KDV KURALLARI ═══════ --}}
    <div class="bmp-card">
        <h2>🧾 KDV Kuralları (ayarlanabilir)</h2>
        <p class="hint">
            Muhasebecinin onayladığı KDV kurallarını buraya ekle. <strong>Yüksek priority önce denenir.</strong> Eşleşen ilk aktif kural uygulanır.
            Müşteri yurt dışı ise muaf (%0), AB içi B2B ise reverse charge, Almanya içi B2C ise %19 vb.
        </p>

        <table class="bmp-table" style="margin-bottom:14px;">
            <thead>
                <tr><th>Ad</th><th>Ülke</th><th>Müşteri</th><th>Oran</th><th>Kod</th><th>Öncelik</th><th>Durum</th><th>İşlem</th></tr>
            </thead>
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
                            @if ($r->is_active)
                                <span class="bmp-badge green">Aktif</span>
                            @else
                                <span class="bmp-badge red">Pasif</span>
                            @endif
                        </td>
                        <td style="display:flex;gap:6px;">
                            <form method="POST" action="{{ route('manager.booking-pricing.tax.toggle', $r) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="bmp-btn bmp-btn-ghost" style="padding:5px 10px;font-size:11px;">{{ $r->is_active ? 'Kapat' : 'Aç' }}</button>
                            </form>
                            <form method="POST" action="{{ route('manager.booking-pricing.tax.destroy', $r) }}" style="display:inline;" onsubmit="return confirm('Sil?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bmp-btn bmp-btn-danger" style="padding:5px 10px;font-size:11px;">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:20px;">KDV kuralı yok. Default = %0 (muaf).</td></tr>
                @endforelse
            </tbody>
        </table>

        <details style="margin-top:10px;">
            <summary style="cursor:pointer;font-weight:600;color:#1e40af;font-size:13px;">➕ Yeni KDV Kuralı Ekle</summary>
            <form method="POST" action="{{ route('manager.booking-pricing.tax.store') }}" style="margin-top:12px;padding:14px;background:#f8fafc;border-radius:8px;">
                @csrf
                <div class="bmp-grid-3" style="margin-bottom:10px;">
                    <div class="bmp-field"><label>Ad</label><input type="text" name="rule_name" required maxlength="120" placeholder="Örn: TR müşteri muafiyeti"></div>
                    <div class="bmp-field"><label>Ülke kodu (2 harf)</label><input type="text" name="match_country_code" maxlength="2" placeholder="DE/TR/FR... boş=tümü"></div>
                    <div class="bmp-field"><label>Müşteri tipi</label>
                        <select name="match_customer_type">
                            <option value="">Hepsi</option>
                            <option value="b2c">B2C</option>
                            <option value="b2b">B2B</option>
                        </select>
                    </div>
                </div>
                <div class="bmp-grid-3" style="margin-bottom:10px;">
                    <div class="bmp-field"><label>KDV oranı %</label><input type="number" step="0.01" name="tax_rate_pct" required min="0" max="100" value="0"></div>
                    <div class="bmp-field"><label>Kod</label>
                        <select name="tax_code">
                            <option value="exempt">exempt (muaf)</option>
                            <option value="standard">standard</option>
                            <option value="reduced">reduced</option>
                            <option value="reverse_charge">reverse_charge</option>
                        </select>
                    </div>
                    <div class="bmp-field"><label>Öncelik (1-100)</label><input type="number" name="priority" required min="1" max="100" value="10"></div>
                </div>
                <div class="bmp-field" style="margin-bottom:10px;">
                    <label>Fatura altı açıklama (opsiyonel)</label>
                    <input type="text" name="invoice_note" maxlength="500" placeholder="Örn: Tax exempt — export">
                </div>
                <label style="display:flex;align-items:center;gap:6px;margin-bottom:10px;">
                    <input type="checkbox" name="is_active" value="1">
                    <span>Aktif</span>
                </label>
                <button type="submit" class="bmp-btn bmp-btn-primary">➕ Ekle</button>
            </form>
        </details>
    </div>

    {{-- ═══════ 3. ÖDEME + PAYOUT AYARLARI ═══════ --}}
    <div class="bmp-card">
        <h2>💳 Ödeme ve Payout Ayarları</h2>
        <p class="hint">
            <strong>is_payment_enabled</strong> flag'i Stripe ile canlı ödeme alıp almayacağını kontrol eder. Muhasebeci onaylamadan true yapmayın.
            Default komisyon: kural yakalamayan booking'ler için uygulanır.
        </p>

        <form method="POST" action="{{ route('manager.booking-pricing.payment.update') }}">
            @csrf
            <div class="bmp-toggle-wrap">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:700;">
                    <input type="checkbox" name="is_payment_enabled" value="1" @checked($paymentSetting->is_payment_enabled)>
                    <span>🟢 Stripe ile canlı ödeme alımı AÇIK</span>
                </label>
                <span style="font-size:11px;color:#92400e;">⚠️ Canlıya çıkmadan Stripe anahtarlarını env'e eklemeyi unutma.</span>
            </div>

            <div class="bmp-grid-3">
                <div class="bmp-field"><label>Payout günü (ayın X'i)</label><input type="number" name="payout_day_of_month" min="1" max="28" value="{{ $paymentSetting->payout_day_of_month }}"></div>
                <div class="bmp-field"><label>Min. payout eşik (EUR)</label><input type="number" step="0.01" name="payout_minimum_eur" min="0" max="10000" value="{{ number_format($paymentSetting->payout_minimum_cents / 100, 2, '.', '') }}"></div>
                <div class="bmp-field">
                    <label>On-demand payout</label>
                    <label style="display:flex;align-items:center;gap:6px;padding-top:6px;">
                        <input type="checkbox" name="allow_on_demand_payout" value="1" @checked($paymentSetting->allow_on_demand_payout)>
                        <span>Senior min eşiği geçince çekebilsin</span>
                    </label>
                </div>
                <div class="bmp-field"><label>Default komisyon %</label><input type="number" step="0.01" name="default_commission_pct" min="0" max="100" value="{{ number_format((float)$paymentSetting->default_commission_pct, 2, '.', '') }}"></div>
                <div class="bmp-field"><label>İade penceresi (saat)</label><input type="number" name="refund_window_hours" min="0" max="168" value="{{ $paymentSetting->refund_window_hours }}"></div>
            </div>

            <div style="margin-top:14px;">
                <button type="submit" class="bmp-btn bmp-btn-primary">💾 Ödeme Ayarlarını Kaydet</button>
            </div>
        </form>
    </div>

    {{-- ═══════ 4. KOMİSYON KURALLARI ═══════ --}}
    <div class="bmp-card">
        <h2>🤝 Komisyon Kuralları</h2>
        <p class="hint">
            Senior tier + hizmet türü matrix'i. Kural yok = default komisyon uygulanır.
            Yüksek priority önce denenir; eşleşen ilk aktif kural kullanılır.
        </p>

        <table class="bmp-table" style="margin-bottom:14px;">
            <thead>
                <tr><th>Ad</th><th>Tier</th><th>Servis Türü</th><th>Oran %</th><th>Öncelik</th><th>Durum</th><th>İşlem</th></tr>
            </thead>
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
                            <form method="POST" action="{{ route('manager.booking-pricing.commission.destroy', $r) }}" style="display:inline;" onsubmit="return confirm('Sil?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bmp-btn bmp-btn-danger" style="padding:5px 10px;font-size:11px;">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px;">Komisyon kuralı yok. Default komisyon uygulanıyor.</td></tr>
                @endforelse
            </tbody>
        </table>

        <details>
            <summary style="cursor:pointer;font-weight:600;color:#1e40af;font-size:13px;">➕ Yeni Komisyon Kuralı</summary>
            <form method="POST" action="{{ route('manager.booking-pricing.commission.store') }}" style="margin-top:12px;padding:14px;background:#f8fafc;border-radius:8px;">
                @csrf
                <div class="bmp-grid-3" style="margin-bottom:10px;">
                    <div class="bmp-field"><label>Ad</label><input type="text" name="rule_name" required maxlength="120" placeholder="Örn: Expert tier %15"></div>
                    <div class="bmp-field"><label>Tier (opsiyonel)</label><input type="text" name="applies_to_tier" maxlength="32" placeholder="junior/mid/senior/expert"></div>
                    <div class="bmp-field"><label>Servis türü (opsiyonel)</label><input type="text" name="applies_to_service_type" maxlength="32" placeholder="consultation/doc_review..."></div>
                </div>
                <div class="bmp-grid-3" style="margin-bottom:10px;">
                    <div class="bmp-field"><label>Komisyon %</label><input type="number" step="0.01" name="commission_pct" required min="0" max="100" value="20"></div>
                    <div class="bmp-field"><label>Öncelik</label><input type="number" name="priority" required min="1" max="100" value="10"></div>
                    <div class="bmp-field">
                        <label>&nbsp;</label>
                        <label style="display:flex;align-items:center;gap:6px;padding-top:6px;">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Aktif</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="bmp-btn bmp-btn-primary">➕ Ekle</button>
            </form>
        </details>
    </div>

</div>
@endsection

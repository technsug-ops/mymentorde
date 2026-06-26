@extends(\App\Support\PanelRouting::layout())

@section('title', 'Manager – Bayi Detay')
@section('page_title', 'Bayi Detay')

@push('head')
<style>
/* Shared detail layout */
.gd-panel { padding:14px 16px !important; margin-bottom:12px !important; }
.gd-panel h2 { font-size:13px !important; font-weight:700 !important; color:var(--u-text,#0f172a); margin:0 0 10px; padding-bottom:8px; border-bottom:1px solid var(--u-line,#e5e9f0); letter-spacing:.2px; }
.gd-panel h2 .muted { font-weight:400 !important; font-size:11px !important; color:var(--u-muted,#64748b); }

/* KPI tiles */
.gd-kpi-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin-bottom:12px; }
.gd-kpi { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:8px; padding:12px 14px; display:flex; flex-direction:column; gap:4px; }
.gd-kpi .lbl { font-size:11px; font-weight:600; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; }
.gd-kpi .val { font-size:22px; font-weight:700; color:var(--u-text,#0f172a); line-height:1.1; }
.gd-kpi.warn .val { color:#d97706; }
.gd-kpi.ok .val { color:#15803d; }
@media(max-width:900px){ .gd-kpi-row { grid-template-columns:repeat(2,1fr); } }

/* Dealer header */
.gd-dealer-head { padding:14px 16px !important; margin-bottom:12px !important; display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap; }
.gd-dealer-head .name { font-size:16px; font-weight:700; color:var(--u-text,#0f172a); }
.gd-dealer-head .code { font-size:12px; color:var(--u-muted,#64748b); font-family:monospace; margin-left:6px; }
.gd-dealer-head .meta { display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-top:6px; }

/* Data table */
.gd-list-table { width:100%; border-collapse:collapse; font-size:12px; }
.gd-list-table thead th { padding:8px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; background:var(--u-bg,#f5f7fa); border-bottom:1px solid var(--u-line,#e5e9f0); }
.gd-list-table tbody td { padding:8px 10px; border-bottom:1px solid var(--u-line,#e5e9f0); vertical-align:top; }
.gd-list-table tbody tr:last-child td { border-bottom:none; }
.gd-list-table tbody tr:hover { background:#f8fafc; }
.gd-list-table .gd-pri { font-weight:600; color:var(--u-text,#0f172a); }
.gd-list-table .gd-sub { font-size:11px; color:var(--u-muted,#64748b); }
.gd-list-table td.num { text-align:right; font-variant-numeric:tabular-nums; }
.gd-list-table th.num { text-align:right; }
.gd-list-table .btn { font-size:11px !important; padding:4px 10px !important; min-height:28px !important; }

/* Payout list */
.gd-payout-list { display:flex; flex-direction:column; }
.gd-payout-item { padding:10px 12px; border-bottom:1px solid var(--u-line,#e5e9f0); display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.gd-payout-item:last-child { border-bottom:none; }
.gd-payout-item:hover { background:#f8fafc; }
.gd-payout-info { flex:1; min-width:0; font-size:12px; }
.gd-payout-info strong { color:var(--u-text,#0f172a); }
.gd-payout-info .meta { font-size:11px; color:var(--u-muted,#64748b); margin-top:3px; }
.gd-payout-bank { font-size:11px; color:var(--u-muted,#64748b); text-align:right; }
</style>
@endpush

@section('content')

<div style="margin-bottom:10px;">
    <a class="btn" href="{{ \App\Support\PanelRouting::url('dealers', 'index') }}">← Bayi Listesi</a>
</div>

{{-- Bayi Başlık --}}
<section class="panel gd-dealer-head">
    <div>
        <div>
            <span class="name">{{ $dealer->name }}</span>
            <span class="code">{{ $dealer->code }}</span>
            @if($dealer->dealer_type_code)
                <span class="badge" style="margin-left:6px;font-size:10px;">{{ $dealer->dealer_type_code }}</span>
            @endif
        </div>
        <div class="meta">
            @if($dealer->is_active)
                <span class="badge ok">Aktif</span>
            @else
                <span class="badge">Pasif</span>
            @endif
        </div>
    </div>
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
        @module('doc_request')
            @can('doc_request.use')
                <button type="button" id="docReqOpenBtn_dealer"
                        style="padding:6px 14px;border:none;border-radius:6px;font-size:12px;font-weight:700;color:#fff;background:linear-gradient(135deg,#1e40af,#3b5fcc);cursor:pointer;display:inline-flex;align-items:center;gap:6px;" aria-label="Belge talep et">
                    <x-icon name="smartphone" size="13" /> Belge Talep Et
                </button>
            @endcan
        @endmodule
        <a class="btn" href="/manager/preview/dealer/{{ $dealer->code }}" target="_blank" style="font-size:12px;padding:6px 14px;">Dealer Önizleme</a>
    </div>
</section>

{{-- Bayi İletişim ve Başvuru Bilgileri --}}
<section class="panel gd-panel">
    <h2 style="display:flex;align-items:center;gap:8px;"><x-icon name="clipboard-list" size="18" /> Bayi Bilgileri</h2>
    <div class="grid2" style="margin-bottom:0;">
        <div>
            <div style="font-size:11px;color:var(--u-muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:3px;">İletişim</div>
            <div style="font-size:13px;line-height:1.7;">
                <strong>E-posta:</strong> {{ $dealer->email ?: '—' }}<br>
                <strong>Telefon:</strong> {{ $dealer->phone ?: '—' }}<br>
                <strong>WhatsApp:</strong> {{ $dealer->whatsapp ?: '—' }}<br>
                <strong>Bayi Tipi:</strong> {{ $dealerType?->name_tr ?? $dealer->dealer_type_code ?? '—' }}<br>
                <strong>Roller:</strong>
                @forelse($dealer->roleLabels() as $rl)
                    <span class="badge ok" style="font-size:10px;">{{ $rl }}</span>
                @empty
                    <span class="muted">—</span>
                @endforelse
                <br>
                <strong>Kayıt:</strong> {{ $dealer->created_at?->format('d.m.Y') ?? '—' }}<br>
                <strong>Bonus Durum:</strong>
                @php $bs = $dealer->signup_bonus_status ?? 'locked'; @endphp
                <span class="badge {{ $bs === 'unlocked' ? 'ok' : ($bs === 'pending' ? 'warn' : '') }}">
                    {{ match($bs) { 'locked'=>'Kilitli','pending'=>'Beklemede','unlocked'=>'Çekilebilir', default=>$bs } }}
                </span>
                @if($dealer->signup_bonus_amount)
                    ({{ number_format($dealer->signup_bonus_amount, 0) }} EUR)
                @endif
            </div>
        </div>
        @if($application)
        <div>
            <div style="font-size:11px;color:var(--u-muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:3px;">
                Başvuru Detayları
                <a href="{{ \App\Support\PanelRouting::url('dealer-applications', 'show', $application->id) }}" style="float:right;color:#1e40af;text-decoration:none;font-weight:600;text-transform:none;letter-spacing:0;">Tam Başvuruyu Aç →</a>
            </div>
            <div style="font-size:13px;line-height:1.7;">
                @if($application->company_name)<strong>Şirket:</strong> {{ $application->company_name }}<br>@endif
                @if($application->city || $application->country)<strong>Konum:</strong> {{ trim(($application->city ?? '') . ' / ' . ($application->country ?? ''), ' / ') }}<br>@endif
                @if($application->tax_number)<strong>Vergi No:</strong> {{ $application->tax_number }}<br>@endif
                @if($application->business_type)<strong>İş Türü:</strong> {{ $application->business_type }}<br>@endif
                @if($application->expected_monthly_volume)<strong>Beklenen Hacim:</strong> {{ $application->expected_monthly_volume }} lead/ay<br>@endif
                @if($application->heard_from)<strong>Nereden Duydu:</strong> {{ $application->heard_from }}<br>@endif
                @if($application->utm_source)<strong>UTM:</strong> {{ $application->utm_source }} / {{ $application->utm_medium ?? '-' }} / {{ $application->utm_campaign ?? '-' }}<br>@endif
                @if($application->motivation)
                    <strong>Motivasyon:</strong>
                    <div style="margin:4px 0 0 0;padding:6px 8px;background:#f8fafc;border-left:2px solid #1e40af;border-radius:3px;font-size:12px;color:#475569;">{{ \Illuminate\Support\Str::limit($application->motivation, 200) }}</div>
                @endif
            </div>
        </div>
        @else
        <div>
            <div style="font-size:11px;color:var(--u-muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:3px;">Başvuru Detayları</div>
            <div style="font-size:12px;color:var(--u-muted);padding:8px;background:#fef9e7;border-left:3px solid #f59e0b;border-radius:3px;">
                Bu bayi için orijinal başvuru kaydı bulunamadı (manuel oluşturulmuş veya eski sistemden).
            </div>
        </div>
        @endif
    </div>
</section>

{{-- Çalışma Rolleri — düzenlenebilir (lead-gen / freelance çoklu) --}}
<section class="panel gd-panel">
    <h2 style="display:flex;align-items:center;gap:8px;"><x-icon name="sliders" size="18" /> Çalışma Rolleri</h2>
    <p class="muted" style="font-size:12px;margin:0 0 10px;">
        Bayi birden çok modelde çalışabilir. <strong>Freelance</strong> seçili olunca panel izinleri (öğrenci detayı, mesajlaşma) açılır; lead komisyonu her lead'in türüne göre hesaplanır.
    </p>
    @php $dRoles = $dealer->rolesList(); @endphp
    <form method="POST" action="{{ route('manager.dealers.roles', $dealer->code) }}" style="display:grid;gap:8px;max-width:480px;">
        @csrf
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;">
            <input type="checkbox" name="roles[]" value="lead_generation" {{ in_array('lead_generation', $dRoles, true) ? 'checked' : '' }}>
            🤝 Lead Generation (Referral)
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;">
            <input type="checkbox" name="roles[]" value="freelance" {{ in_array('freelance', $dRoles, true) ? 'checked' : '' }}>
            🎯 Freelance Danışman
        </label>
        <div>
            <button type="submit" class="btn" style="background:#0891b2;color:#fff;">💾 Rolleri Kaydet</button>
        </div>
    </form>
</section>

{{-- Hiyerarşi (2 seviye: bölge → alt bayi) --}}
<section class="panel gd-panel">
    <h2 style="display:flex;align-items:center;gap:8px;"><x-icon name="git-branch" size="18" />
        Bayi Hiyerarşisi
        <span class="muted">{{ $dealer->parent_dealer_id ? '· Alt Bayi' : '· Bölge Bayisi' }}</span>
    </h2>

    @if($parentDealer)
        <div style="font-size:13px;margin-bottom:8px;">
            <strong>Üst Bayi (Bölge):</strong>
            <a href="/manager/dealers/{{ $parentDealer->code }}" style="color:#1e40af;text-decoration:none;font-weight:600;">
                {{ $parentDealer->name }} <span style="font-family:monospace;font-size:12px;">({{ $parentDealer->code }})</span>
            </a>
        </div>
    @endif

    @if($childDealers->isNotEmpty())
        <div style="font-size:11px;color:var(--u-muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:6px;">
            Alt Bayiler ({{ $childDealers->count() }})
        </div>
        <table class="gd-list-table">
            <thead><tr><th>Alt Bayi</th><th>Kod</th><th>E-posta</th><th>Durum</th></tr></thead>
            <tbody>
                @foreach($childDealers as $child)
                    <tr>
                        <td class="gd-pri">
                            <a href="/manager/dealers/{{ $child->code }}" style="color:#1e40af;text-decoration:none;">{{ $child->name }}</a>
                        </td>
                        <td style="font-family:monospace;">{{ $child->code }}</td>
                        <td class="gd-sub">{{ $child->email ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $child->is_active ? 'ok' : '' }}">{{ $child->is_active ? 'Aktif' : 'Pasif' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif(!$parentDealer)
        <div style="font-size:12px;color:var(--u-muted);margin-bottom:12px;">Bu bölge bayisinin henüz alt bayisi yok.</div>
    @endif

    @if(!$parentDealer)
        {{-- Override (üst pay) — yalnız bölge bayisi --}}
        <div style="margin-top:14px;padding-top:12px;border-top:1px dashed var(--u-line,#e5e9f0);">
            <div style="font-size:11px;color:var(--u-muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:8px;">
                Override (Üst Pay) — alt bayilerin getirisi üzerinden bu bölge bayisine ek komisyon
            </div>
            <form method="POST" action="/manager/dealers/{{ $dealer->code }}/override" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                @csrf
                <div>
                    <label style="display:block;font-size:11px;color:var(--u-muted);margin-bottom:3px;">Yöntem</label>
                    <select name="override_basis" style="padding:7px 10px;border:1px solid var(--u-line,#cbd5e1);border-radius:6px;font-size:13px;">
                        <option value="percent_of_sub" {{ ($dealer->override_basis ?? 'percent_of_sub') === 'percent_of_sub' ? 'selected' : '' }}>Alt bayi hak edişinin %'si</option>
                        <option value="fixed_eur" {{ ($dealer->override_basis ?? '') === 'fixed_eur' ? 'selected' : '' }}>Öğrenci başına sabit €</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;color:var(--u-muted);margin-bottom:3px;">% (percent_of_sub)</label>
                    <input type="number" step="0.01" min="0" max="100" name="override_rate_percent" value="{{ $dealer->override_rate_percent }}"
                           style="width:110px;padding:7px 10px;border:1px solid var(--u-line,#cbd5e1);border-radius:6px;font-size:13px;">
                </div>
                <div>
                    <label style="display:block;font-size:11px;color:var(--u-muted);margin-bottom:3px;">€ (fixed_eur)</label>
                    <input type="number" step="0.01" min="0" name="override_rate_eur" value="{{ $dealer->override_rate_eur }}"
                           style="width:110px;padding:7px 10px;border:1px solid var(--u-line,#cbd5e1);border-radius:6px;font-size:13px;">
                </div>
                <button type="submit" class="btn" style="font-size:12px;padding:8px 16px;background:#1e40af;color:#fff;border:none;border-radius:6px;cursor:pointer;">Kaydet</button>
            </form>
        </div>
    @endif

    {{-- Mini-site moderasyonu — tüm bayiler --}}
    <div style="margin-top:14px;padding-top:12px;border-top:1px dashed var(--u-line,#e5e9f0);">
        <div style="font-size:11px;color:var(--u-muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:8px;">
            Mini-Site (White-Label)
            @if($dealer->public_slug)
                · <a href="/p/{{ $dealer->public_slug }}?preview=1" target="_blank" style="color:#1e40af;text-transform:none;letter-spacing:0;">/p/{{ $dealer->public_slug }} (önizle)</a>
            @endif
        </div>
        <form method="POST" action="/manager/dealers/{{ $dealer->code }}/mini-site" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div>
                <label style="display:block;font-size:11px;color:var(--u-muted);margin-bottom:3px;">Slug (/p/...)</label>
                <input type="text" name="public_slug" value="{{ $dealer->public_slug }}" pattern="[a-z0-9\-]+" maxlength="64"
                       style="width:200px;padding:7px 10px;border:1px solid var(--u-line,#cbd5e1);border-radius:6px;font-size:13px;font-family:monospace;">
            </div>
            <label style="display:flex;gap:6px;align-items:center;font-size:13px;color:#475569;cursor:pointer;">
                <input type="checkbox" name="site_enabled" value="1" {{ $dealer->site_enabled ? 'checked' : '' }}>
                Yayında
            </label>
            <button type="submit" class="btn" style="font-size:12px;padding:8px 16px;background:#15803d;color:#fff;border:none;border-radius:6px;cursor:pointer;">Kaydet</button>
        </form>
    </div>
</section>

{{-- KPI Çubuğu --}}
<div class="gd-kpi-row">
    <div class="gd-kpi"><div class="lbl">Öğrenci</div><div class="val">{{ $revenueStats['students'] }}</div></div>
    <div class="gd-kpi"><div class="lbl">Toplam Lead</div><div class="val">{{ $leads->total() }}</div></div>
    <div class="gd-kpi ok"><div class="lbl">Kazanılan (EUR)</div><div class="val">{{ number_format($revenueStats['total_earned'], 2, ',', '.') }}</div></div>
    <div class="gd-kpi {{ $revenueStats['total_pending'] > 0 ? 'warn' : '' }}">
        <div class="lbl">Bekleyen (EUR)</div>
        <div class="val">{{ number_format($revenueStats['total_pending'], 2, ',', '.') }}</div>
    </div>
</div>

<div class="grid2">

    {{-- Gelir Detayı (student bazlı) --}}
    <section class="card gd-panel">
        <h2>Öğrenci Gelir Detayı</h2>
        @if($revenues->isEmpty())
            <div class="muted" style="padding:12px 0;font-size:12px;">Henüz gelir kaydı yok.</div>
        @else
            <div style="overflow-x:auto;">
                <table class="gd-list-table">
                    <thead>
                        <tr>
                            <th>Öğrenci ID</th>
                            <th class="num">Kazanılan</th>
                            <th class="num">Bekleyen</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenues as $rev)
                            <tr>
                                <td class="gd-pri">{{ $rev->student_id }}</td>
                                <td class="num">{{ $rev->total_earned > 0 ? number_format((float)$rev->total_earned, 2, ',', '.') : '–' }}</td>
                                <td class="num">
                                    @if($rev->total_pending > 0)
                                        <span style="color:#d97706;">{{ number_format((float)$rev->total_pending, 2, ',', '.') }}</span>
                                    @else <span class="gd-sub">–</span>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    <a class="btn" href="/manager/students/{{ urlencode($rev->student_id) }}">Detay</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Ödeme Talepleri --}}
    <section class="card gd-panel">
        <h2>Ödeme Talepleri</h2>
        @if($payouts->isEmpty())
            <div class="muted" style="padding:12px 0;font-size:12px;">Ödeme talebi yok.</div>
        @else
            <div class="gd-payout-list">
                @foreach($payouts as $p)
                    @php
                        $sc = match($p->status) { 'requested'=>'warn','approved'=>'info','paid'=>'ok','rejected'=>'danger',default=>'badge' };
                        $sl = match($p->status) { 'requested'=>'Talep Edildi','approved'=>'Onaylandı','paid'=>'Ödendi','rejected'=>'Reddedildi',default=>ucfirst((string)($p->status ?? '–')) };
                    @endphp
                    <div class="gd-payout-item">
                        <div class="gd-payout-info">
                            <strong>#{{ $p->id }}</strong>
                            <span class="badge {{ $sc }}" style="margin-left:4px;font-size:10px;">{{ $sl }}</span>
                            <div class="meta">
                                {{ number_format((float)($p->amount ?? 0), 2, ',', '.') }} {{ $p->currency ?: 'EUR' }}
                                · {{ optional($p->created_at)->format('d.m.Y') }}
                                @if($p->approved_by) · Onaylayan: {{ $p->approved_by }} @endif
                            </div>
                            @if($p->receipt_url)
                                <div class="meta"><a href="{{ $p->receipt_url }}" target="_blank">Dekont</a></div>
                            @endif
                            @if($p->rejection_reason)
                                <div class="meta">Red: {{ $p->rejection_reason }}</div>
                            @endif
                        </div>
                        @if($p->account)
                            <div class="gd-payout-bank">
                                {{ $p->account->bank_name ?? '' }}<br>
                                {{ $p->account->iban ?? '' }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if($payouts->hasPages())
            <div style="margin-top:12px;">{{ $payouts->links() }}</div>
            @endif
        @endif
    </section>

</div>

{{-- Son Leadler --}}
<section class="card gd-panel" style="margin-top:12px;">
    <h2>Leadler <span class="muted">{{ $leads->total() }} kayıt</span></h2>
    @if($leads->isEmpty())
        <div class="muted" style="padding:12px 0;font-size:12px;">Bu bayiye ait lead bulunamadı.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="gd-list-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Eğitim Danışmanı</th>
                        <th>Durum</th>
                        <th>Dönüşüm</th>
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                        @php
                            $bc = match($lead->lead_status) { 'new'=>'info','contacted'=>'warn','qualified'=>'badge','converted'=>'ok','lost'=>'danger',default=>'badge' };
                            $bl = match($lead->lead_status ?? '') { 'new'=>'Yeni','contacted'=>'İletişime Geçildi','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu',default=>($lead->lead_status ?: '–') };
                        @endphp
                        <tr>
                            <td class="gd-sub">#{{ $lead->id }}</td>
                            <td class="gd-pri">{{ $lead->first_name }} {{ $lead->last_name }}</td>
                            <td class="gd-sub">{{ $lead->email }}</td>
                            <td class="gd-sub">{{ $lead->assigned_senior_email ?: '–' }}</td>
                            <td><span class="badge {{ $bc }}">{{ $bl }}</span></td>
                            <td>
                                @if($lead->converted_to_student)
                                    <span class="badge ok">{{ $lead->converted_student_id }}</span>
                                @else
                                    <span class="gd-sub">–</span>
                                @endif
                            </td>
                            <td class="gd-sub">{{ optional($lead->created_at)->format('d.m.Y') }}</td>
                            <td style="text-align:right;">
                                <a class="btn" href="/manager/guests/{{ $lead->id }}">Detay</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
            <div style="margin-top:12px;">{{ $leads->links() }}</div>
        @endif
    @endif
</section>

{{-- UTM / Tracking Link Performansı --}}
<section class="panel gd-panel" style="margin-top:12px;">
    <h2>UTM & Tracking Link Performansı <span class="muted">{{ $utmStats['total_links'] }} link toplam</span></h2>

    {{-- KPI satırı --}}
    <div class="gd-kpi-row">
        <div class="gd-kpi"><div class="lbl">Aktif Link</div><div class="val">{{ $utmStats['active_links'] }}</div></div>
        <div class="gd-kpi"><div class="lbl">Toplam Tıklama</div><div class="val">{{ number_format($utmStats['total_clicks']) }}</div></div>
        <div class="gd-kpi"><div class="lbl">Lead</div><div class="val">{{ number_format($utmStats['total_leads']) }}</div></div>
        <div class="gd-kpi {{ $utmStats['total_converted'] > 0 ? 'ok' : '' }}"><div class="lbl">Dönüşüm</div><div class="val">{{ number_format($utmStats['total_converted']) }}</div></div>
    </div>

    @if($utmLinks->isEmpty())
        <p class="muted" style="font-size:12px;margin:0;">Bu bayiye ait tracking link bulunamadı.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="gd-list-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Başlık</th>
                        <th>UTM</th>
                        <th style="text-align:center;">Durum</th>
                        <th class="num">Tıklama</th>
                        <th class="num">Lead</th>
                        <th class="num">Dönüşüm</th>
                        <th class="num">CVR</th>
                        <th>Son Tıklama</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($utmLinks as $link)
                        @php
                            $ls = $leadStatsByCode->get($link->code);
                            $leadCount = (int) ($ls?->lead_count ?? 0);
                            $convertedCount = (int) ($ls?->converted_count ?? 0);
                            $cvr = $leadCount > 0 ? round($convertedCount / $leadCount * 100, 1) : null;
                            $statusBadge = match($link->status) { 'active' => 'ok', 'paused' => 'warn', default => 'danger' };
                            $statusLabel = match($link->status) { 'active' => 'Aktif', 'paused' => 'Durduruldu', default => 'Arşiv' };
                            $utmLabel = collect([$link->utm_source, $link->utm_medium, $link->utm_campaign])->filter()->implode(' / ');
                        @endphp
                        <tr>
                            <td style="font-family:monospace;letter-spacing:.5px;">{{ $link->code }}</td>
                            <td class="gd-pri" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $link->title }}">{{ $link->title }}</td>
                            <td class="gd-sub">{{ $utmLabel ?: '–' }}</td>
                            <td style="text-align:center;"><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                            <td class="num">{{ number_format($link->click_count ?? 0) }}</td>
                            <td class="num">{{ $leadCount > 0 ? number_format($leadCount) : '–' }}</td>
                            <td class="num">{{ $convertedCount > 0 ? number_format($convertedCount) : '–' }}</td>
                            <td class="num">
                                @if($cvr !== null)
                                    <span class="badge {{ $cvr >= 10 ? 'ok' : ($cvr >= 5 ? 'warn' : 'danger') }}">%{{ $cvr }}</span>
                                @else
                                    <span class="gd-sub">–</span>
                                @endif
                            </td>
                            <td class="gd-sub">{{ $link->last_clicked_at ? \Carbon\Carbon::parse($link->last_clicked_at)->format('d.m.Y') : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@module('doc_request')
    @can('doc_request.use')
        @include('partials.doc-request-modal', [
            'modalId'     => 'docReqModal_dealer',
            'btnId'       => 'docReqOpenBtn_dealer',
            'indexRoute'  => 'manager.dealer.document-tokens.index',
            'storeRoute'  => 'manager.dealer.document-tokens.store',
            'routeParam'  => $dealer->code,
            'targetLabel' => $dealer->name . ' (Bayi · ' . $dealer->code . ')',
            'sendIntro'   => "Merhaba, MentorDE'den bayilik dosyanız için belge talebimiz var. Lütfen aşağıdaki linke tıklayıp belgeyi yükleyin:",
        ])
    @endcan
@endmodule

@endsection

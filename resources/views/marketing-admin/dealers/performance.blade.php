@extends('marketing-admin.layouts.app')

@section('title', 'Bayi Performansı')
@section('page_subtitle', 'Bayi bazlı lead, dönüşüm ve kazanç performans analizi')

@section('content')
<style>
    .dp-page { display:grid; gap:12px; }
    .dp-header { display:flex; align-items:flex-start; gap:14px; flex-wrap:wrap; }
    .dp-avatar { width:56px; height:56px; border-radius:50%; background:var(--u-brand,#0a67d8); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:700; flex-shrink:0; }
    .dp-info { flex:1; min-width:0; }
    .dp-info h3 { margin:0 0 4px; font-size:18px; }
    .dp-info .sub { font-size:12px; color:var(--u-muted,#6b7c93); }
    .dp-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .dp-top { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:10px; }
    .dp-kpi .label { color: var(--u-muted,#6b7c93); font-size:12px; }
    .dp-kpi .val { color:#0a67d8; font-size:20px; font-weight:700; }
    .dp-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    .milestone-bar-wrap { background:#e8f0fb; border-radius:99px; height:10px; overflow:hidden; margin-top:6px; }
    .milestone-bar-fill { height:100%; border-radius:99px; background:#0a67d8; transition:width .3s; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .table-wrap { overflow-x:auto; border:1px solid var(--u-line,#d9e4f0); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:600px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--u-line,#d9e4f0); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
    .btn-sm { display:inline-block; border:0; border-radius:8px; padding:7px 12px; font-size:12px; font-weight:700; text-decoration:none; cursor:pointer; }
    .btn-primary { background:#0a67d8; color:#fff; }
    .btn-secondary { background:#eef4fb; color:#204d87; border:1px solid #d2deea; }
    .collapse-toggle { background:none; border:none; color:#0a67d8; font-size:12px; cursor:pointer; font-weight:700; padding:0; }
    .broadcast-form { display:none; margin-top:12px; padding:12px; background:#f5f9ff; border-radius:10px; border:1px solid #d2deea; }
    .broadcast-form.open { display:block; }
    .form-row { display:grid; grid-template-columns: 1fr 1fr 120px; gap:8px; align-items:end; margin-top:8px; }
    @media (max-width: 1200px) {
        .dp-top { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dp-grid { grid-template-columns: 1fr; }
        .form-row { grid-template-columns: 1fr; }
    }
</style>

<div class="dp-page">

    {{-- Header Card --}}
    <section class="card">
        <div class="dp-header">
            <div class="dp-avatar">{{ strtoupper(substr($dealer->name ?? $dealer->code, 0, 1)) }}</div>
            <div class="dp-info">
                <h3>{{ $dealer->name ?? $dealer->code }}</h3>
                <div class="sub">
                    Kod: <strong>{{ $dealer->code }}</strong>
                    &nbsp;|&nbsp;
                    Tip: <strong>{{ $dealer->dealer_type_code ?? '-' }}</strong>
                    &nbsp;|&nbsp;
                    @if($dealer->is_archived)
                        <span class="badge warn">Arsivlendi</span>
                    @elseif($dealer->is_active)
                        <span class="badge ok">Aktif</span>
                    @else
                        <span class="badge danger">Pasif</span>
                    @endif
                </div>
                <div class="sub" style="margin-top:4px;">
                    @if($dealer->email)
                        <span title="E-posta">✉ {{ $dealer->email }}</span>&nbsp;
                    @endif
                    @if($dealer->phone)
                        <span title="Telefon">☎ {{ $dealer->phone }}</span>&nbsp;
                    @endif
                    @if($dealer->whatsapp)
                        <span title="WhatsApp">💬 {{ $dealer->whatsapp }}</span>
                    @endif
                    @if(!$dealer->email && !$dealer->phone && !$dealer->whatsapp)
                        <span style="color:#b05c00;">İletişim bilgisi girilmemis</span>
                    @endif
                </div>
            </div>
            <div class="dp-actions">
                <a href="/mktg-admin/dealers" class="btn-sm btn-secondary">← Liste</a>
                <button type="button" class="btn-sm btn-primary" onclick="toggleBroadcast()">Mesaj Gönder</button>
                <a href="/mktg-admin/tracking-links" class="btn-sm btn-secondary">Tracking Links</a>
            </div>
        </div>

        {{-- Broadcast mini form --}}
        @php
            $dealerEmail    = $dealer->email ?? '';
            $dealerPhone    = $dealer->phone ?? '';
            $dealerWhatsapp = $dealer->whatsapp ?? '';
        @endphp
        <div class="broadcast-form" id="broadcastForm">
            <strong style="font-size:var(--tx-sm);">{{ $dealer->code }} — Duyuru Gönder</strong>
            <div id="channelHint" style="margin-top:4px;font-size:var(--tx-xs);color:#b05c00;display:none;"></div>
            <form method="POST" action="/mktg-admin/dealers/{{ $dealer->code }}/broadcast">
                @csrf
                <div class="form-row">
                    <div>
                        <label style="font-size:var(--tx-xs);display:block;margin-bottom:4px;">Kanal</label>
                        <select name="channel" id="broadcastChannel"
                            style="width:100%;padding:7px 8px;border-radius:8px;border:1px solid #d2deea;font-size:var(--tx-sm);"
                            onchange="updateChannelHint(this.value,'{{ $dealerEmail }}','{{ $dealerPhone }}','{{ $dealerWhatsapp }}')">
                            <option value="inApp">In-App</option>
                            <option value="email" @if(!$dealerEmail) disabled title="E-posta adresi girilmemis" @endif>
                                E-posta {{ $dealerEmail ? '('.$dealerEmail.')' : '(adres yok)' }}
                            </option>
                            <option value="whatsapp" @if(!$dealerWhatsapp && !$dealerPhone) disabled title="Telefon/WhatsApp girilmemis" @endif>
                                WhatsApp {{ $dealerWhatsapp ?: ($dealerPhone ?: '(numara yok)') }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:var(--tx-xs);display:block;margin-bottom:4px;">Konu</label>
                        <input type="text" name="subject" required maxlength="190" placeholder="Konu..." style="width:100%;padding:7px 8px;border-radius:8px;border:1px solid #d2deea;font-size:var(--tx-sm);box-sizing:border-box;">
                    </div>
                    <div>
                        <button type="submit" class="btn-sm btn-primary" style="width:100%;">Gönder</button>
                    </div>
                </div>
                <div style="margin-top:8px;">
                    <label style="font-size:var(--tx-xs);display:block;margin-bottom:4px;">Mesaj</label>
                    <textarea name="message" required maxlength="5000" rows="2" placeholder="Mesajinizi yazin..." style="width:100%;padding:7px 8px;border-radius:8px;border:1px solid #d2deea;font-size:var(--tx-sm);resize:vertical;box-sizing:border-box;"></textarea>
                </div>
            </form>
        </div>
    </section>

    {{-- 4 KPI Grid --}}
    <section class="dp-top">
        <article class="card dp-kpi">
            <div class="label">Atanan Öğrenci</div>
            <div class="val">{{ $summary['students_total'] ?? 0 }}</div>
            @if(($summary['students_active'] ?? 0) > 0)
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#6b7c93);margin-top:2px;">{{ $summary['students_active'] }} aktif</div>
            @endif
        </article>
        <article class="card dp-kpi">
            <div class="label">Bu Ay Kazanc</div>
            <div class="val">{{ number_format((float) ($summary['revenue_this_month'] ?? 0), 2, ',', '.') }} EUR</div>
        </article>
        <article class="card dp-kpi">
            <div class="label">Toplam Kazanc</div>
            <div class="val">{{ number_format((float) ($summary['revenue_earned'] ?? 0), 2, ',', '.') }} EUR</div>
            @if(($summary['revenue_pending'] ?? 0) > 0)
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#6b7c93);margin-top:2px;">+ {{ number_format((float)$summary['revenue_pending'], 2, ',', '.') }} bekleyen</div>
            @endif
        </article>
        <article class="card dp-kpi">
            <div class="label">Dönüşüm Orani</div>
            <div class="val">{{ number_format((float) ($summary['conversion_rate'] ?? 0), 1, '.', ',') }}%</div>
            @if(($summary['converted_total'] ?? 0) > 0)
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#6b7c93);margin-top:2px;">{{ $summary['converted_total'] }} converted lead</div>
            @endif
        </article>
    </section>

    {{-- Milestone Progress --}}
    @if($milestones->isNotEmpty() || ($summary['grand_total'] ?? 0) > 0)
    <section class="card">
        <h4 style="margin:0 0 8px;">Milestone Ozeti</h4>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
            <span style="font-size:var(--tx-sm);">Odeme ilerlemesi:
                <strong>{{ number_format((float)($summary['revenue_earned']??0),2,',','.') }} EUR</strong>
                / {{ number_format((float)($summary['grand_total']??0),2,',','.') }} EUR
            </span>
            <span class="badge {{ ($summary['paid_pct']??0) >= 80 ? 'ok' : (($summary['paid_pct']??0) >= 40 ? 'warn' : 'info') }}">%{{ $summary['paid_pct']??0 }}</span>
        </div>
        <div class="milestone-bar-wrap">
            <div class="milestone-bar-fill" style="width:{{ $summary['paid_pct']??0 }}%;"></div>
        </div>
        @if($milestones->isNotEmpty())
            <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($milestones as $m)
                    <span class="badge info" title="{{ $m->name_tr }}">
                        {{ Str::limit($m->name_tr ?? '-', 30) }}
                        @if($m->percentage)
                            &nbsp;{{ $m->percentage }}%
                        @elseif($m->fixed_amount)
                            &nbsp;{{ number_format($m->fixed_amount,0) }} {{ $m->fixed_currency }}
                        @endif
                    </span>
                @endforeach
            </div>
        @endif
    </section>
    @endif

    {{-- Tables Grid: Assignments + Revenues --}}
    <section class="dp-grid">
        <article class="card">
            <h4 style="margin:0 0 8px;">Öğrenci Atamalari</h4>
            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Student</th>
                        <th>Senior</th>
                        <th>Branch</th>
                        <th>Risk</th>
                        <th>Payment</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse(($assignments ?? []) as $row)
                        <tr>
                            <td>{{ $row->student_id }}</td>
                            <td>{{ $row->senior_email ?: '-' }}</td>
                            <td>{{ $row->branch ?: '-' }}</td>
                            <td>{{ $row->risk_level }}</td>
                            <td>{{ $row->payment_status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Atama verisi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">{{ $assignments->links() }}</div>
        </article>

        <article class="card">
            <h4 style="margin:0 0 8px;">Revenue Kayıtlari</h4>
            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Student</th>
                        <th>Tip</th>
                        <th>Earned</th>
                        <th>Pending</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse(($revenues ?? []) as $row)
                        <tr>
                            <td>{{ $row->student_id }}</td>
                            <td>{{ $row->dealer_type ?: '-' }}</td>
                            <td>{{ number_format((float) $row->total_earned, 2, ',', '.') }} EUR</td>
                            <td>{{ number_format((float) $row->total_pending, 2, ',', '.') }} EUR</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Revenue kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    {{-- Tables Grid: Tracking Links + Leads --}}
    <section class="dp-grid">
        <article class="card">
            <h4 style="margin:0 0 8px;">Tracking Linkler</h4>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr><th>Kod</th><th>Baslik</th><th>Status</th><th>Clicks</th><th>Son Tıklama</th></tr></thead>
                    <tbody>
                    @forelse(($links ?? []) as $row)
                        <tr>
                            <td>{{ $row->code }}</td>
                            <td>{{ $row->title ?: '-' }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ (int) $row->click_count }}</td>
                            <td>{{ $row->last_clicked_at ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Tracking kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card">
            <h4 style="margin:0 0 8px;">Lead Kaynaklari</h4>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr><th>Guest</th><th>UTM</th><th>Campaign</th><th>Converted</th><th>Tarih</th></tr></thead>
                    <tbody>
                    @forelse(($leads ?? []) as $row)
                        <tr>
                            <td>{{ $row->guest_id ?: '-' }}</td>
                            <td>{{ $row->utm_source ?: '-' }} / {{ $row->utm_medium ?: '-' }}</td>
                            <td>{{ $row->utm_campaign ?: '-' }}</td>
                            <td>{{ $row->funnel_converted ? 'evet' : 'hayir' }}</td>
                            <td>{{ $row->created_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Lead kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

</div>

<script>
function toggleBroadcast() {
    var form = document.getElementById('broadcastForm');
    form.classList.toggle('open');
    if (form.classList.contains('open')) {
        var sel = document.getElementById('broadcastChannel');
        if (sel) updateChannelHint(sel.value, sel.dataset.email || '', sel.dataset.phone || '', sel.dataset.wa || '');
    }
}

function updateChannelHint(channel, email, phone, whatsapp) {
    var hint = document.getElementById('channelHint');
    if (!hint) return;
    if (channel === 'email') {
        hint.style.display = email ? 'none' : 'block';
        hint.textContent = email ? '' : 'Uyarı: Bu bayi için e-posta adresi girilmemis. Mesaj kuyruga alinir ama teslim edilemeyebilir.';
    } else if (channel === 'whatsapp') {
        var num = whatsapp || phone;
        hint.style.display = num ? 'none' : 'block';
        hint.textContent = num ? '' : 'Uyarı: Bu bayi için WhatsApp/telefon numarasi girilmemis.';
    } else {
        hint.style.display = 'none';
        hint.textContent = '';
    }
}
</script>

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Bayi Performans Raporu</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 Performans Metrikleri</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>Lead Sayısı:</strong> Bayi referansıyla gelen toplam aday</li>
                <li><strong>Dönüşüm Oranı:</strong> Converted / Lead × 100 — bayi kalitesini gösterir</li>
                <li><strong>Toplam Komisyon:</strong> Tamamlanan kayıtlara göre hesaplanır</li>
                <li>En iyi bayi → daha fazla destek ve öncelikli teşvik</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📬 İletişim & Aksiyonlar</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Mesaj gönder → bayiye e-posta veya WhatsApp bildirimi</li>
                <li>Düşük performanslı bayiler için eğitim materyali paylaş</li>
                <li>Dönem filtresi → aylık/çeyreklik bayi sıralama karşılaştırması yap</li>
            </ul>
        </div>
    </div>
</details>
@endsection

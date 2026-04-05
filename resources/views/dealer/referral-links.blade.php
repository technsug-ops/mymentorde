@extends('dealer.layouts.app')

@section('title', 'Referans Linklerim')
@section('page_title', 'Referans Linklerim')
@section('page_subtitle', 'Referans kodu, QR, UTM kampanya linkleri ve kanal istatistikleri')

@push('head')
<style>
/* Stats strip */
.ref-kpi-strip { display:grid; grid-template-columns:repeat(7,1fr); gap:10px; margin-bottom:20px; }
@media(max-width:1100px){ .ref-kpi-strip { grid-template-columns:repeat(4,1fr); } }
@media(max-width:700px) { .ref-kpi-strip { grid-template-columns:1fr 1fr; } }

.ref-kpi {
    background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0);
    border-top:3px solid var(--border); border-radius:12px; padding:14px 16px;
}
.ref-kpi.c1 { border-top-color:#16a34a; }
.ref-kpi.c2 { border-top-color:#0891b2; }
.ref-kpi.c3 { border-top-color:#d97706; }
.ref-kpi.c4 { border-top-color:#7c3aed; }
.ref-kpi.c5 { border-top-color:#2563eb; }
.ref-kpi.c6 { border-top-color:#0891b2; }
.ref-kpi.c7 { border-top-color:#f59e0b; }
.ref-kpi-label  { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:5px; }
.ref-kpi-val    { font-size:22px;font-weight:900;color:var(--text,#0f172a);line-height:1; }
.ref-kpi-sub    { font-size:10px;color:var(--muted,#64748b);margin-top:3px; }

/* Card shell */
.ref-card { background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;overflow:hidden;margin-bottom:16px; }
.ref-card-head { padding:14px 20px;border-bottom:1px solid var(--border,#e2e8f0);display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap; }
.ref-card-head h3 { margin:0;font-size:14px;font-weight:700; }
.ref-card-body { padding:20px; }

/* Link card */
.ref-link-box {
    background:var(--bg,#f8fafc); border:1.5px solid var(--border,#e2e8f0);
    border-radius:10px; padding:14px 16px; margin-bottom:14px;
}
.ref-link-label { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:6px; }
.ref-code-big   { font-size:28px;font-weight:900;color:#16a34a;letter-spacing:.08em; }
.ref-link-url   { font-size:12px;font-family:monospace;word-break:break-all;color:var(--text,#0f172a);line-height:1.5; }

.ref-actions { display:flex;gap:8px;flex-wrap:wrap;margin-top:14px; }
.ref-btn-copy {
    display:inline-flex;align-items:center;gap:5px;padding:8px 18px;border-radius:8px;
    font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid #16a34a;
    background:#fff;color:#16a34a;transition:all .15s;
}
.ref-btn-copy:hover { background:#16a34a;color:#fff; }
.ref-btn-wa {
    display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:8px;
    font-size:13px;font-weight:600;text-decoration:none;
    background:#25d366;color:#fff;border:none;transition:opacity .15s;
}
.ref-btn-wa:hover { opacity:.88; }

/* QR */
.ref-qr-wrap { display:flex;flex-direction:column;align-items:flex-start;gap:12px; }
.ref-qr-img  { border:2px solid var(--border,#e2e8f0);border-radius:10px;display:block; }
.ref-qr-dl   { display:flex;gap:8px; }

/* UTM list */
.ref-utm-item {
    padding:16px 20px;border-bottom:1px solid var(--border,#e2e8f0);
    transition:background .12s;
}
.ref-utm-item:last-child { border-bottom:none; }
.ref-utm-item:hover { background:var(--bg,#f8fafc); }
.ref-utm-name    { font-size:14px;font-weight:700;color:var(--text,#0f172a);margin-bottom:3px; }
.ref-utm-campaign{ font-size:11px;color:var(--muted,#64748b);margin-bottom:6px; }
.ref-utm-url     { font-size:11px;font-family:monospace;word-break:break-all;color:var(--muted,#64748b);background:var(--bg,#f1f5f9);border-radius:6px;padding:6px 8px;margin-bottom:8px; }
.ref-utm-perf    { display:flex;gap:16px;flex-wrap:wrap;font-size:12px; }
.ref-utm-perf strong { color:var(--text,#0f172a); }
.ref-utm-actions { margin-top:10px;display:flex;gap:6px;flex-wrap:wrap; }

/* UTM form */
.ref-add-details summary {
    display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;
    padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;
    border:1.5px dashed #16a34a;color:#16a34a;transition:all .15s;
}
.ref-add-details summary:hover { background:rgba(22,163,74,.06); }
.ref-add-form { margin-top:16px;padding:20px;background:var(--bg,#f8fafc);border-radius:10px;border:1px solid var(--border,#e2e8f0); }
.ref-field { margin-bottom:14px; }
.ref-field label { display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:6px; }
.ref-field input { width:100%;box-sizing:border-box;border:1.5px solid var(--border,#e2e8f0);border-radius:8px;padding:10px 12px;font-size:13px;background:var(--surface,#fff);color:var(--text,#0f172a);transition:border-color .15s,box-shadow .15s; }
.ref-field input:focus { outline:none;border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.12); }
.ref-field .ref-err  { font-size:12px;color:var(--c-danger,#dc2626);margin-top:4px; }
.ref-field .ref-hint { font-size:11px;color:var(--muted,#64748b);margin-top:4px; }

/* Source breakdown */
.ref-src-item { padding:10px 20px;border-bottom:1px solid var(--border,#e2e8f0);display:flex;align-items:center;gap:10px; }
.ref-src-item:last-child { border-bottom:none; }
.ref-src-name { font-size:13px;font-weight:600;flex:1; }
.ref-src-bar  { flex:3;height:6px;background:var(--border,#e2e8f0);border-radius:999px;overflow:hidden; }
.ref-src-fill { height:100%;background:#16a34a;border-radius:999px; }
.ref-src-cnt  { font-size:13px;font-weight:700;color:var(--text,#0f172a);min-width:32px;text-align:right; }

/* Recent list */
.ref-recent-item { padding:13px 20px;border-bottom:1px solid var(--border,#e2e8f0);display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;transition:background .12s; }
.ref-recent-item:last-child { border-bottom:none; }
.ref-recent-item:hover { background:var(--bg,#f8fafc); }
.ref-recent-name { font-size:13px;font-weight:700; }
.ref-recent-meta { font-size:11px;color:var(--muted,#64748b);margin-top:3px;display:flex;gap:8px;flex-wrap:wrap; }

.ref-badge { display:inline-block;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700; }
.ref-badge.green  { background:rgba(22,163,74,.12);color:#15803d; }
.ref-badge.blue   { background:rgba(8,145,178,.12);color:#0e7490; }
.ref-badge.muted  { background:var(--bg,#f1f5f9);color:var(--muted,#64748b); }

.ref-empty { padding:36px 20px;text-align:center;color:var(--muted,#64748b);font-size:13px; }

/* Guide */
.ref-guide { background:var(--bg,#f1f5f9);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px 20px;margin-top:4px; }
.ref-guide-title { font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:10px; }
.ref-guide ul { margin:0;padding-left:18px; }
.ref-guide li { font-size:13px;color:var(--muted,#64748b);margin-bottom:6px; }
</style>
@endpush

@section('content')

@php
    $ds           = $dealerStats ?? [];
    $sourceBreakdown = $recent->groupBy(fn($r) => (string)($r->utm_source ?: $r->lead_source ?: 'unknown'))->map->count()->sortDesc();
    $todayCount   = $recent->filter(fn($r) => optional($r->created_at)?->isToday())->count();
    $weekCount    = $recent->filter(fn($r) => optional($r->created_at)?->gte(now()->subDays(7)))->count();
    $srcMax       = max(1, $sourceBreakdown->max());
@endphp

{{-- Birleşik KPI strip --}}
<div class="ref-kpi-strip">
    <div class="ref-kpi c1">
        <div class="ref-kpi-label">Toplam Lead</div>
        <div class="ref-kpi-val">{{ $ds['guest_total'] ?? 0 }}</div>
        <div class="ref-kpi-sub">tüm zamanlar</div>
    </div>
    <div class="ref-kpi c2">
        <div class="ref-kpi-label">Dönüşüm</div>
        <div class="ref-kpi-val">{{ $ds['converted_total'] ?? 0 }}</div>
        <div class="ref-kpi-sub">öğrenciye</div>
    </div>
    <div class="ref-kpi c3">
        <div class="ref-kpi-label">Dönüşüm Oranı</div>
        <div class="ref-kpi-val">%{{ $ds['conversion_rate'] ?? 0 }}</div>
    </div>
    <div class="ref-kpi c4">
        <div class="ref-kpi-label">Son 30 Gün</div>
        <div class="ref-kpi-val">{{ $stats['total'] }}</div>
        <div class="ref-kpi-sub">başvuru</div>
    </div>
    <div class="ref-kpi c5">
        <div class="ref-kpi-label">Son 7 Gün</div>
        <div class="ref-kpi-val">{{ $weekCount }}</div>
    </div>
    <div class="ref-kpi c6">
        <div class="ref-kpi-label">Bugün</div>
        <div class="ref-kpi-val">{{ $todayCount }}</div>
    </div>
    <div class="ref-kpi c7">
        <div class="ref-kpi-label">Link / Form</div>
        <div class="ref-kpi-val">{{ $stats['link_channel'] }}</div>
        <div class="ref-kpi-sub">form: {{ $stats['form_channel'] }}</div>
    </div>
</div>

<div class="grid2" style="align-items:start;">

{{-- Referans Kodu + QR --}}
<div>
    <div class="ref-card">
        <div class="ref-card-head">
            <h3>🔗 Referans Linkim</h3>
        </div>
        <div class="ref-card-body">
            <div class="ref-link-box">
                <div class="ref-link-label">Referans Kodu</div>
                <div class="ref-code-big">{{ $dealerCode ?: '—' }}</div>
            </div>
            @if($dealerLink)
            <div class="ref-link-box" style="margin-bottom:0;">
                <div class="ref-link-label">Ana Link</div>
                <div class="ref-link-url" id="dealer-ref-link">{{ $dealerLink }}</div>
            </div>
            @endif
            <div class="ref-actions">
                <button type="button" class="ref-btn-copy" id="btn-copy-main">📋 Kopyala</button>
                @if($dealerLink)
                <a class="ref-btn-wa" href="https://wa.me/?text={{ urlencode((string)($dealerLink ?? '')) }}" target="_blank">
                    💬 WhatsApp Paylaş
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="ref-card">
        <div class="ref-card-head">
            <h3>📱 QR Kod</h3>
            @if($dealerLink)
            <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">Baskı için SVG önerilir</span>
            @endif
        </div>
        <div class="ref-card-body">
            @if($dealerLink)
            <div class="ref-qr-wrap">
                <img class="ref-qr-img" id="qr-img"
                     src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode((string)$dealerLink) }}"
                     alt="QR Kod" width="160" height="160" loading="lazy">
                <div class="ref-qr-dl">
                    <a class="btn" download="qr-{{ $dealerCode }}.png"
                       href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode((string)$dealerLink) }}"
                       style="font-size:var(--tx-xs);padding:6px 14px;">PNG İndir</a>
                    <a class="btn" download="qr-{{ $dealerCode }}.svg"
                       href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=svg&data={{ urlencode((string)$dealerLink) }}"
                       style="font-size:var(--tx-xs);padding:6px 14px;">SVG İndir</a>
                </div>
            </div>
            @else
            <div class="ref-empty">QR kod için dealer kodu gereklidir.</div>
            @endif
        </div>
    </div>
</div>

{{-- UTM + Kaynak --}}
<div>
    {{-- Kaynak Dağılımı --}}
    <div class="ref-card">
        <div class="ref-card-head">
            <h3>📊 Kaynak Dağılımı <span style="font-size:var(--tx-xs);font-weight:400;color:var(--muted,#64748b);">son 30 başvuru</span></h3>
        </div>
        @forelse($sourceBreakdown as $source => $count)
        <div class="ref-src-item">
            <span class="ref-src-name">{{ $source }}</span>
            <div class="ref-src-bar">
                <div class="ref-src-fill" style="width:{{ round($count/$srcMax*100) }}%;"></div>
            </div>
            <span class="ref-src-cnt">{{ $count }}</span>
        </div>
        @empty
        <div class="ref-empty">Kaynak verisi yok.</div>
        @endforelse
    </div>

    {{-- Son Başvurular --}}
    <div class="ref-card">
        <div class="ref-card-head">
            <h3>⏱ Son Referanslı Başvurular</h3>
            @if($recent->isNotEmpty())
                <span class="ref-badge muted">{{ $recent->count() }} kayıt</span>
            @endif
        </div>
        @forelse($recent as $r)
        <div class="ref-recent-item">
            <div>
                <div class="ref-recent-name">#{{ $r->id }} {{ $r->first_name }} {{ $r->last_name }}</div>
                <div class="ref-recent-meta">
                    @if($r->utm_source)<span class="ref-badge blue">{{ $r->utm_source }}</span>@endif
                    @if($r->utm_campaign)<span class="ref-badge muted">{{ $r->utm_campaign }}</span>@endif
                    <span>{{ optional($r->created_at)->format('d.m.Y H:i') }}</span>
                </div>
            </div>
            <a class="btn" href="{{ route('dealer.leads.show', ['lead' => $r->id]) }}"
               style="font-size:var(--tx-xs);padding:5px 12px;flex-shrink:0;">Detay</a>
        </div>
        @empty
        <div class="ref-empty">Henüz referanslı başvuru yok.</div>
        @endforelse
    </div>
</div>

</div>{{-- /grid2 --}}

{{-- Kampanya Linkleri --}}
<div class="ref-card">
    <div class="ref-card-head">
        <h3>🎯 Kampanya Linkleri (UTM)</h3>
        @if($utmLinks->isNotEmpty())
            <span class="ref-badge muted">{{ $utmLinks->count() }} link</span>
        @endif
    </div>

    @forelse($utmLinks as $link)
        @php
            $utmUrl = ($dealerLink ?: '')
                .'&utm_source='.urlencode($link->utm_source)
                .'&utm_medium='.urlencode($link->utm_medium)
                .'&utm_campaign='.urlencode($link->utm_campaign);
            $perf = $utmPerf[$link->utm_campaign] ?? null;
        @endphp
        <div class="ref-utm-item">
            <div class="ref-utm-name">{{ $link->label }}</div>
            <div class="ref-utm-campaign">campaign: {{ $link->utm_campaign }} · source: {{ $link->utm_source }} · medium: {{ $link->utm_medium }}</div>
            <div class="ref-utm-url">{{ $utmUrl }}</div>
            @if($perf)
            <div class="ref-utm-perf">
                <span><strong>{{ $perf['leads_total'] }}</strong> lead</span>
                <span><strong>{{ $perf['leads_converted'] }}</strong> dönüşüm</span>
                <span><strong style="color:#16a34a;">%{{ $perf['conv_rate'] }}</strong> oran</span>
                @if($perf['last_lead_at'])<span style="color:var(--muted,#64748b);">son: {{ $perf['last_lead_at'] }}</span>@endif
            </div>
            @endif
            <div class="ref-utm-actions">
                <button type="button" class="ref-btn-copy ref-utm-copy" style="padding:6px 12px;font-size:var(--tx-xs);"
                        data-url="{{ $utmUrl }}">📋 Kopyala</button>
                <a class="ref-btn-wa" style="padding:6px 12px;font-size:var(--tx-xs);"
                   href="https://wa.me/?text={{ urlencode($utmUrl) }}" target="_blank">💬 Paylaş</a>
                <form method="POST" action="{{ route('dealer.referral.utm.delete', $link->id) }}"
                      class="ref-utm-del-form" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="padding:6px 12px;font-size:var(--tx-xs);background:rgba(220,38,38,.1);color:#b91c1c;border:1px solid rgba(220,38,38,.2);border-radius:6px;cursor:pointer;">🗑 Sil</button>
                </form>
            </div>
        </div>
    @empty
        <div class="ref-empty">Henüz kampanya linki oluşturulmadı.</div>
    @endforelse

    <div style="padding:16px 20px;border-top:1px solid var(--border,#e2e8f0);">
        <details class="ref-add-details">
            <summary>+ Yeni Kampanya Linki</summary>
            <form method="POST" action="{{ route('dealer.referral.utm.store') }}" class="ref-add-form">
                @csrf
                <div class="grid2" style="margin-bottom:0;">
                    <div class="ref-field">
                        <label>Link Adı *</label>
                        <input name="label" value="{{ old('label') }}" placeholder="Instagram Bio, WhatsApp Grup..." required>
                        @error('label')<div class="ref-err">{{ $message }}</div>@enderror
                    </div>
                    <div class="ref-field">
                        <label>UTM Campaign *</label>
                        <input name="utm_campaign" value="{{ old('utm_campaign') }}" placeholder="ig_bio_2026" required>
                        <div class="ref-hint">Sadece a-z, 0-9, _ ve -</div>
                        @error('utm_campaign')<div class="ref-err">{{ $message }}</div>@enderror
                    </div>
                    <div class="ref-field" style="margin-bottom:0;">
                        <label>UTM Source</label>
                        <input name="utm_source" value="{{ old('utm_source','dealer') }}" placeholder="dealer, instagram...">
                    </div>
                    <div class="ref-field" style="margin-bottom:0;">
                        <label>UTM Medium</label>
                        <input name="utm_medium" value="{{ old('utm_medium','referral') }}" placeholder="referral, social...">
                    </div>
                </div>
                <button class="btn btn-primary" style="margin-top:14px;">Oluştur</button>
            </form>
        </details>
    </div>
</div>

<div class="ref-guide">
    <div class="ref-guide-title">💡 Nasıl Kullanılır?</div>
    <ul>
        <li>Referans kodu ve link, dealer kanalından gelen başvuruları otomatik eşler.</li>
        <li>QR kodu baskı materyallerine, okul tanıtımlarına veya afişlere ekleyebilirsin. Baskı için SVG önerilir.</li>
        <li>UTM campaign alanı sadece küçük harf, rakam, _ ve - içerebilir.</li>
        <li>Her kampanya için ayrı UTM linki oluşturarak hangi kanalın daha çok lead getirdiğini takip et.</li>
    </ul>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    const mainLink = @json($dealerLink ?: '');

    // Ana kopyala butonu
    document.getElementById('btn-copy-main')?.addEventListener('click', function () {
        if (!mainLink) return;
        navigator.clipboard?.writeText(mainLink).then(() => {
            const orig = this.textContent;
            this.textContent = '✓ Kopyalandı';
            setTimeout(() => { this.textContent = orig; }, 1500);
        });
    });

    // UTM link kopyala butonları
    document.querySelectorAll('.ref-utm-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const url = this.dataset.url;
            if (!url) return;
            navigator.clipboard?.writeText(url).then(() => {
                const orig = this.textContent;
                this.textContent = '✓ Kopyalandı';
                setTimeout(() => { this.textContent = orig; }, 1500);
            });
        });
    });

    // UTM sil formları — confirm dialog
    document.querySelectorAll('.ref-utm-del-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Bu kampanya linkini silmek istiyor musun?')) {
                e.preventDefault();
            }
        });
    });
})();
</script>
@endpush

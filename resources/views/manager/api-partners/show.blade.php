@extends(\App\Support\PartnerRouting::layout())

@section('title', $partner->name . ' · Partner API · ' . config('brand.name', 'MentorDE'))
@section('page_title', '📡 ' . $partner->name)
@section('page_subtitle', 'Partner detayı, kullanım istatistikleri ve audit log')

@section('content')
<style>
.apc-show-wrap { max-width: 1280px; margin: 0 auto; }
.apc-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.apc-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 14px; }
@media (max-width: 800px) { .apc-grid-2, .apc-grid-4 { grid-template-columns: 1fr; } }
.apc-card { background: var(--u-card,#fff); border: 1px solid var(--u-line,#e2e8f0); border-radius: 10px; padding: 14px; }
.apc-card h3 { margin: 0 0 10px; font-size: 12px; font-weight: 800; color: var(--u-muted,#64748b); text-transform: uppercase; letter-spacing: 0.06em; }
.apc-stat { text-align: center; padding: 14px; }
.apc-stat-num { font-size: 26px; font-weight: 800; color: #5b2e91; line-height: 1; margin-bottom: 4px; }
.apc-stat-label { font-size: 11px; color: var(--u-muted,#64748b); }
.apc-key-display { background: linear-gradient(135deg,#fef3c7,#fde68a); border: 2px solid #d97706; border-radius: 10px; padding: 16px; margin-bottom: 18px; }
.apc-key-display strong { display:block;font-size:13px;color:#78350f;margin-bottom:8px; }
.apc-key-mono { font-family: ui-monospace, "Cascadia Code", Consolas, monospace; font-size: 14px; padding: 10px 14px; background: #fff; border: 1px solid #d97706; border-radius: 6px; word-break: break-all; }
.apc-key-warn { margin-top: 8px; font-size: 11.5px; color: #78350f; }
.apc-toolbar { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.apc-btn { padding: 8px 14px; background: #5b2e91; color: #fff; border: none; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
.apc-btn:hover { background: #4a2578; }
.apc-btn-ghost { padding: 8px 13px; background: transparent; color: var(--u-muted,#64748b); border: 1px solid var(--u-line,#cbd5e1); border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
.apc-btn-warn { padding: 8px 13px; background: rgba(245,158,11,.12); color: #b45309; border: 1px solid rgba(245,158,11,.4); border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; }
.apc-btn-danger { padding: 8px 13px; background: rgba(239,68,68,.1); color: #b91c1c; border: 1px solid rgba(239,68,68,.3); border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; }
table.apc-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
table.apc-table th { padding: 7px 10px; text-align: left; font-size: 10.5px; font-weight: 700; color: var(--u-muted,#64748b); text-transform: uppercase; border-bottom: 1px solid var(--u-line,#e2e8f0); }
table.apc-table td { padding: 7px 10px; border-bottom: 1px solid var(--u-line,#f1f5f9); font-family: ui-monospace, monospace; font-size: 11.5px; }
.apc-pill-code { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 10.5px; font-weight: 700; }
.apc-pill-code.s2 { background: rgba(16,185,129,.15); color: #047857; }
.apc-pill-code.s4 { background: rgba(245,158,11,.18); color: #b45309; }
.apc-pill-code.s5 { background: rgba(239,68,68,.15); color: #b91c1c; }
.apc-info-grid { display: grid; grid-template-columns: max-content 1fr; gap: 6px 16px; font-size: 12.5px; }
.apc-info-grid .lbl { color: var(--u-muted,#64748b); }
.apc-mono { font-family: ui-monospace, monospace; }
</style>

<div class="apc-show-wrap">

    @if(session('success'))
        <div style="margin-bottom:14px;padding:10px 14px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:8px;color:#047857;font-size:13px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    <a href="{{ \App\Support\PartnerRouting::url('index') }}" style="display:inline-block;margin-bottom:12px;color:#5b2e91;font-size:12.5px;text-decoration:none;">← Tüm partnerlar</a>

    {{-- Plaintext key — sadece flash session'da, sayfayı yenileyince kaybolur --}}
    @if($plaintextKey)
        <div class="apc-key-display">
            <strong>🔐 API Anahtarı (yalnız bu sefer gösterilir):</strong>
            <div class="apc-key-mono">{{ $plaintextKey }}</div>
            <div class="apc-key-warn">⚠️ Partner'a güvenli kanaldan ilet (ör. password manager veya şifreli e-posta). Sayfayı kapatınca tekrar gösteremeyiz — yalnız "rotate" ile yeni key üretilir.</div>
        </div>
    @endif

    {{-- Toolbar --}}
    <div class="apc-toolbar">
        <form method="POST" action="{{ \App\Support\PartnerRouting::url('rotate', $partner) }}" onsubmit="return confirm('Eski anahtar geçersiz olacak. Partner sitesinde key güncellemen gerekecek. Devam edilsin mi?')">
            @csrf
            <button type="submit" class="apc-btn-warn">🔄 Anahtarı Yenile (Rotate)</button>
        </form>
        <form method="POST" action="{{ \App\Support\PartnerRouting::url('toggle', $partner) }}">
            @csrf
            <button type="submit" class="apc-btn-ghost">
                @if($partner->is_active) ⏸ Devre Dışı Bırak @else ▶ Aktif Et @endif
            </button>
        </form>
        <form method="POST" action="{{ \App\Support\PartnerRouting::url('destroy', $partner) }}" onsubmit="return confirm('Partner kalıcı olarak silinecek. Audit log tarihçesi korunur. Emin misin?')">
            @csrf @method('DELETE')
            <button type="submit" class="apc-btn-danger">🗑 Sil</button>
        </form>
    </div>

    {{-- 4 stat box: bugün, son 24h hata, lead total, lead completed --}}
    <div class="apc-grid-4">
        <div class="apc-card apc-stat">
            <div class="apc-stat-num">{{ number_format($partner->total_requests) }}</div>
            <div class="apc-stat-label">Lifetime Request</div>
        </div>
        <div class="apc-card apc-stat">
            <div class="apc-stat-num">{{ number_format($last24h->total ?? 0) }}</div>
            <div class="apc-stat-label">Son 24 saat</div>
            @if(($last24h->errors ?? 0) > 0)
                <div style="margin-top:4px;font-size:11px;color:#b45309;">{{ $last24h->errors }} hata, {{ $last24h->rate_limited }} 429</div>
            @endif
        </div>
        <div class="apc-card apc-stat">
            <div class="apc-stat-num">{{ number_format($leads->total ?? 0) }}</div>
            <div class="apc-stat-label">Lead Ziyareti (UniMatch'e geldi)</div>
        </div>
        <div class="apc-card apc-stat">
            <div class="apc-stat-num" style="color:#047857;">{{ number_format($leads->completed ?? 0) }}</div>
            <div class="apc-stat-label">Wizard Tamamlandı</div>
        </div>
    </div>

    {{-- Partner info + endpoint dağılımı yan yana --}}
    <div class="apc-grid-2">
        <div class="apc-card">
            <h3>📋 Partner Bilgileri</h3>
            <div class="apc-info-grid">
                <span class="lbl">Slug:</span><span class="apc-mono">{{ $partner->slug }}</span>
                <span class="lbl">Anahtar:</span><span class="apc-mono">{{ $partner->api_key_prefix }}</span>
                <span class="lbl">Durum:</span><span>@if($partner->is_active) <span style="color:#047857;font-weight:700;">● Aktif</span> @else <span style="color:#b91c1c;font-weight:700;">● Devre dışı</span> @endif</span>
                <span class="lbl">Rate limit:</span><span>{{ number_format($partner->rate_limit_per_hour) }} / saat</span>
                <span class="lbl">İletişim:</span><span>{{ $partner->contact_email ?? '—' }}</span>
                <span class="lbl">Website:</span><span>@if($partner->website)<a href="{{ $partner->website }}" target="_blank" style="color:#5b2e91;">{{ $partner->website }}</a>@else — @endif</span>
                <span class="lbl">Oluşturuldu:</span><span>{{ $partner->created_at?->format('d M Y, H:i') }}</span>
                <span class="lbl">Son kullanım:</span><span>{{ $partner->last_used_at?->diffForHumans() ?? 'hiç' }}</span>
            </div>
            @if($partner->notes)
                <div style="margin-top:12px;padding:10px;background:var(--u-bg,#f8fafc);border-radius:7px;font-size:12px;color:var(--u-text);">
                    <strong>Not:</strong> {{ $partner->notes }}
                </div>
            @endif
        </div>

        <div class="apc-card">
            <h3>📊 Endpoint Dağılımı (son 30 gün)</h3>
            @if($endpoints->isEmpty())
                <div style="padding:20px 8px;font-size:12px;color:var(--u-muted,#94a3b8);text-align:center;">Bu partnerdan henüz request gelmedi.</div>
            @else
                <table class="apc-table">
                    <thead>
                        <tr><th>Endpoint</th><th>Request</th><th>Ort. ms</th></tr>
                    </thead>
                    <tbody>
                        @foreach($endpoints as $e)
                            <tr>
                                <td>{{ $e->endpoint }}</td>
                                <td>{{ number_format($e->c) }}</td>
                                <td>{{ (int) $e->avg_ms }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Son 50 request audit log --}}
    <div class="apc-card" style="margin-top:14px;">
        <h3>🕓 Son 50 Request</h3>
        @if($recentRequests->isEmpty())
            <div style="padding:20px 8px;font-size:12px;color:var(--u-muted,#94a3b8);text-align:center;">Audit log boş.</div>
        @else
            <table class="apc-table">
                <thead>
                    <tr><th>Zaman</th><th>Endpoint</th><th>Status</th><th>Latency</th><th>Sonuç</th><th>IP</th></tr>
                </thead>
                <tbody>
                    @foreach($recentRequests as $r)
                        <tr>
                            <td>{{ $r->created_at?->format('d M H:i:s') }}</td>
                            <td>{{ $r->endpoint }}</td>
                            <td><span class="apc-pill-code s{{ (int) ($r->response_code / 100) }}">{{ $r->response_code }}</span></td>
                            <td>{{ $r->response_time_ms }}ms</td>
                            <td>{{ $r->result_count ?? '—' }}</td>
                            <td>{{ $r->ip }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div style="margin-top:16px;padding:12px 16px;background:rgba(126,88,191,.05);border:1px solid rgba(126,88,191,.18);border-radius:10px;font-size:12px;color:var(--u-text);line-height:1.5;">
        <strong style="color:#5b2e91;">📘 Partner Entegrasyon Rehberi:</strong><br>
        <code class="apc-mono">curl -H "Authorization: Bearer mtde_live_…" {{ url('/api/v1/partner/programs') }}?per_page=10</code><br>
        Detaylı endpoint listesi: <code class="apc-mono">/programs</code>, <code class="apc-mono">/programs/{uuid}</code>, <code class="apc-mono">/universities</code>, <code class="apc-mono">/universities/{uuid}</code>, <code class="apc-mono">/states</code>, <code class="apc-mono">/study-fields</code>, <code class="apc-mono">/meta</code>.
    </div>
</div>
@endsection

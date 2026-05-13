@extends('manager.layouts.app')

@section('title', 'Partner API · ' . config('brand.name', 'MentorDE'))
@section('page_title', 'Partner API Yönetimi')
@section('page_subtitle', 'Kardeş sitelere açtığın API key\'leri, kullanım istatistikleri ve lead conversion')

@section('content')
<style>
.apc-wrap { max-width: 1280px; margin: 0 auto; }
.apc-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.apc-toolbar h2 { margin: 0; font-size: 16px; color: var(--u-text); }
.apc-btn { padding: 9px 16px; background: #5b2e91; color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
.apc-btn:hover { background: #4a2578; }
.apc-btn-ghost { background: transparent; color: var(--u-muted, #64748b); border: 1px solid var(--u-line, #cbd5e1); padding: 7px 13px; font-size: 12px; font-weight: 600; border-radius: 7px; text-decoration: none; cursor: pointer; }
.apc-btn-ghost:hover { border-color: #5b2e91; color: #5b2e91; }
.apc-card { background: var(--u-card, #fff); border: 1px solid var(--u-line, #e2e8f0); border-radius: 12px; padding: 0; overflow: hidden; }
table.apc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
table.apc-table th { background: var(--u-bg, #f8fafc); padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: var(--u-muted, #64748b); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--u-line, #e2e8f0); }
table.apc-table td { padding: 12px 14px; border-bottom: 1px solid var(--u-line, #f1f5f9); vertical-align: top; }
table.apc-table tr:last-child td { border-bottom: none; }
.apc-pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.apc-pill.active { background: rgba(16,185,129,.12); color: #047857; }
.apc-pill.disabled { background: rgba(239,68,68,.12); color: #b91c1c; }
.apc-empty { padding: 60px 20px; text-align: center; color: var(--u-muted, #64748b); }
.apc-empty-icon { font-size: 36px; margin-bottom: 10px; }
.apc-mono { font-family: ui-monospace, "Cascadia Code", Consolas, monospace; font-size: 12px; color: var(--u-muted, #64748b); }
.apc-stat-mini { display: inline-flex; gap: 6px; align-items: baseline; }
.apc-stat-mini strong { color: var(--u-text); font-weight: 700; }
</style>

<div class="apc-wrap">

    @if(session('success'))
        <div style="margin-bottom:14px;padding:10px 14px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:8px;color:#047857;font-size:13px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div class="apc-toolbar">
        <h2>📡 {{ $partners->count() }} partner kayıtlı</h2>
        <div style="flex:1"></div>
        <a href="{{ route('manager.api-partners.create') }}" class="apc-btn">+ Yeni Partner</a>
    </div>

    <div class="apc-card">
        @if($partners->isEmpty())
            <div class="apc-empty">
                <div class="apc-empty-icon">📡</div>
                <div style="font-weight:700;margin-bottom:6px;">Henüz partner yok</div>
                <div style="font-size:12.5px;">Kardeş site ile veri paylaşmak için yeni bir API anahtarı oluştur.</div>
                <div style="margin-top:14px;">
                    <a href="{{ route('manager.api-partners.create') }}" class="apc-btn">+ İlk partnerı ekle</a>
                </div>
            </div>
        @else
            <table class="apc-table">
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>Anahtar (masked)</th>
                        <th>Durum</th>
                        <th>Bugün</th>
                        <th>Son 7g</th>
                        <th>Toplam</th>
                        <th>Conversion</th>
                        <th>Limit / saat</th>
                        <th>Son kullanım</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partners as $p)
                        @php
                            $conv = $conversions[$p->slug] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <div style="font-weight:600;color:var(--u-text);"><a href="{{ route('manager.api-partners.show', $p) }}" style="color:inherit;text-decoration:none;">{{ $p->name }}</a></div>
                                <div class="apc-mono">{{ $p->slug }}</div>
                                @if($p->contact_email)
                                    <div style="font-size:11px;color:var(--u-muted,#94a3b8);margin-top:2px;">{{ $p->contact_email }}</div>
                                @endif
                            </td>
                            <td class="apc-mono">{{ $p->api_key_prefix }}</td>
                            <td>
                                @if($p->is_active)
                                    <span class="apc-pill active">● Aktif</span>
                                @else
                                    <span class="apc-pill disabled">● Devre dışı</span>
                                @endif
                            </td>
                            <td>{{ number_format($todayCounts[$p->id] ?? 0) }}</td>
                            <td>{{ number_format($weekCounts[$p->id] ?? 0) }}</td>
                            <td>{{ number_format($p->total_requests) }}</td>
                            <td>
                                @if($conv)
                                    <div class="apc-stat-mini"><strong>{{ $conv->total }}</strong> ziyaret</div>
                                    @if($conv->completed > 0)
                                        <div style="font-size:11px;color:#047857;">→ {{ $conv->completed }} tamamlandı</div>
                                    @endif
                                @else
                                    <span style="color:var(--u-muted,#94a3b8);">—</span>
                                @endif
                            </td>
                            <td>{{ number_format($p->rate_limit_per_hour) }}</td>
                            <td>
                                @if($p->last_used_at)
                                    <span title="{{ $p->last_used_at }}">{{ $p->last_used_at->diffForHumans() }}</span>
                                @else
                                    <span style="color:var(--u-muted,#94a3b8);">hiç</span>
                                @endif
                            </td>
                            <td><a href="{{ route('manager.api-partners.show', $p) }}" class="apc-btn-ghost">Detay →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div style="margin-top:14px;padding:14px;background:rgba(126,88,191,.05);border:1px solid rgba(126,88,191,.18);border-radius:10px;font-size:12.5px;color:var(--u-text);">
        <strong style="color:#5b2e91;">📘 API Erişimi:</strong>
        Partner sitesi <code class="apc-mono">Authorization: Bearer mtde_live_…</code> header'ı ile <code class="apc-mono">/api/v1/partner/programs</code> gibi endpoint'leri çağırır.
        Tüm yanıtlarda <code class="apc-mono">referral_url</code> field'ı var — partner kullanıcısı tıklayınca UniMatch wizard'ına lead olarak düşer (UTM tracking ile).
    </div>
</div>
@endsection

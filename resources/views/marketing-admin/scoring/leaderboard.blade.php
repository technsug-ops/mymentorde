@extends('marketing-admin.layouts.app')

@section('title', 'Scoring Liderlik Tablosu')
@section('page_subtitle', 'Liderlik Tablosu — en yüksek puanlı leadler ve tier dağılımı')

@section('topbar-actions')
<a class="btn {{ request()->is('mktg-admin/scoring') && !request()->is('mktg-admin/scoring/*') ? '' : 'alt' }}" href="/mktg-admin/scoring" style="font-size:var(--tx-xs);padding:6px 12px;">Genel Bakış</a>
<a class="btn {{ request()->is('mktg-admin/scoring/leaderboard') ? '' : 'alt' }}" href="/mktg-admin/scoring/leaderboard" style="font-size:var(--tx-xs);padding:6px 12px;">Liderlik Tablosu</a>
<a class="btn {{ request()->is('mktg-admin/scoring/config') ? '' : 'alt' }}" href="/mktg-admin/scoring/config" style="font-size:var(--tx-xs);padding:6px 12px;">Kural Yapılandırma</a>
@endsection

@section('content')
<style>
.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }

.score-bar-bg   { background:var(--u-line,#e2e8f0); border-radius:4px; height:5px; overflow:hidden; margin-top:4px; }
.score-bar-fill { height:100%; border-radius:4px; }
</style>

@php
$tierColors = [
    'champion'   => '#16a34a',
    'sales_ready'=> '#0891b2',
    'hot'        => '#d97706',
    'warm'       => '#7c3aed',
    'cold'       => '#64748b',
];
$tierBadges = [
    'champion'   => 'ok',
    'sales_ready'=> 'info',
    'hot'        => 'warn',
    'warm'       => 'info',
    'cold'       => 'pending',
];
$maxScore = $leads->max('lead_score') ?: 100;
@endphp

<div style="display:grid;gap:12px;">

    {{-- Tier Filtre Bar --}}
    <div class="card" style="padding:12px 16px;">
        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
            <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-right:4px;">Tier:</span>
            <a href="/mktg-admin/scoring/leaderboard"
               class="btn {{ !$tier ? '' : 'alt' }}" style="font-size:var(--tx-xs);padding:5px 12px;">Tümü</a>
            @foreach(['champion' => 'Champion', 'sales_ready' => 'Sales Ready', 'hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold'] as $t => $label)
            <a href="/mktg-admin/scoring/leaderboard?tier={{ $t }}"
               class="btn {{ $tier === $t ? '' : 'alt' }}" style="font-size:var(--tx-xs);padding:5px 12px;{{ $tier === $t ? 'background:'.($tierColors[$t] ?? 'var(--u-brand,#1e40af)').';border-color:transparent;' : '' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Tablo --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            En Yüksek Puanlı Leadler
            @if($tier)
            <span style="font-weight:400;text-transform:none;margin-left:6px;font-size:var(--tx-xs);">
                — <span style="color:{{ $tierColors[$tier] ?? 'var(--u-brand)' }};font-weight:600;">{{ $tierLabels[$tier] ?? $tier }}</span>
            </span>
            @endif
            <span style="font-weight:400;font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-left:8px;">{{ $leads->count() }} kayıt</span>
        </div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:40px;">#</th>
                    <th>Aday</th>
                    <th style="width:160px;">Puan</th>
                    <th style="width:110px;">Tier</th>
                    <th style="width:110px;">Sözleşme</th>
                    <th style="width:130px;">Son Güncelleme</th>
                </tr></thead>
                <tbody>
                    @forelse($leads as $i => $lead)
                    @php
                        $tierKey   = $lead->lead_score_tier ?? 'cold';
                        $barColor  = $tierColors[$tierKey] ?? 'var(--u-brand,#1e40af)';
                        $badgeType = $tierBadges[$tierKey] ?? 'info';
                        $barPct    = $maxScore > 0 ? min(100, round($lead->lead_score / $maxScore * 100)) : 0;
                        $rank      = $i + 1;
                        $rankStyle = $rank === 1 ? 'font-size:15px;' : ($rank === 2 ? 'font-size:13px;' : ($rank === 3 ? 'font-size:12px;' : ''));
                    @endphp
                    <tr>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);font-weight:700;{{ $rankStyle }}">
                            @if($rank === 1) 🥇
                            @elseif($rank === 2) 🥈
                            @elseif($rank === 3) 🥉
                            @else {{ $rank }}
                            @endif
                        </td>
                        <td>
                            <a href="/mktg-admin/scoring/{{ $lead->id }}/history"
                               style="font-weight:600;color:var(--u-text,#0f172a);text-decoration:none;">
                                {{ $lead->first_name }} {{ $lead->last_name }}
                            </a>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-weight:700;font-size:var(--tx-base);color:{{ $barColor }};min-width:36px;">{{ $lead->lead_score }}</span>
                                <div style="flex:1;">
                                    <div class="score-bar-bg">
                                        <div class="score-bar-fill" style="width:{{ $barPct }}%;background:{{ $barColor }};"></div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $badgeType }}">{{ $tierLabels[$tierKey] ?? $tierKey }}</span>
                        </td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                            {{ $lead->contract_status ?? '—' }}
                        </td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                            {{ optional($lead->lead_score_updated_at)->diffForHumans() ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">
                        Bu tier için kayıt yok.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Lead Skor Sıralaması</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🏆 Tier Sistemi</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>Hot (80+):</strong> Satış odaklı acil temas — öncelikli takip</li>
                <li><strong>Warm (50–79):</strong> Nitelikli lead — besleyici içerik gönder</li>
                <li><strong>Cold (0–49):</strong> Henüz hazır değil — otomatik drip serisi</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📈 Aksiyon Rehberi</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Hot leade 24 saat içinde temas et — yanıt oranı hızla düşer</li>
                <li>Warm leadi ısıtmak için vaka çalışması ve referans paylaş</li>
                <li>Cold leadler için bütçe harcama — önce scoring kurallarını optimize et</li>
            </ul>
        </div>
    </div>
</details>

</div>
@endsection

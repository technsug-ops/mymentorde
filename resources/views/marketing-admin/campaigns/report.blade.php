@extends('marketing-admin.layouts.app')

@section('content')
<section class="card" style="margin-bottom:12px;">
    <h3 style="margin:0 0 8px;">{{ $title ?? 'Kampanya Raporu' }}</h3>
    <p class="muted" style="margin:0 0 8px;">
        Kanal: {{ $campaign->channel }} |
        Durum: {{ $campaign->status }} |
        Butce: {{ number_format((float) ($campaign->budget ?? 0), 2, '.', ',') }} {{ $campaign->currency ?? 'EUR' }} |
        Harcama: {{ number_format((float) (($campaign->spent_amount ?? 0) > 0 ? $campaign->spent_amount : $campaign->budget), 2, '.', ',') }} {{ $campaign->currency ?? 'EUR' }}
    </p>
    <p class="muted" style="margin:0;">Eslestirme key: {{ implode(', ', $matchKeys ?? []) }}</p>
</section>

<section class="kpis" style="margin-bottom:12px;">
    <div class="card kpi"><div class="label">Lead</div><div class="val">{{ $summary['lead_count'] ?? 0 }}</div></div>
    <div class="card kpi"><div class="label">Verified</div><div class="val">{{ $summary['verified_count'] ?? 0 }}</div></div>
    <div class="card kpi"><div class="label">Converted</div><div class="val">{{ $summary['converted_count'] ?? 0 }}</div></div>
</section>

<section class="card">
    <h4 style="margin-top:0;">Gunluk Metrik</h4>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th style="text-align:left; border-bottom:1px solid #d2deea; padding:8px;">Gun</th>
                <th style="text-align:left; border-bottom:1px solid #d2deea; padding:8px;">Lead</th>
                <th style="text-align:left; border-bottom:1px solid #d2deea; padding:8px;">Verified</th>
                <th style="text-align:left; border-bottom:1px solid #d2deea; padding:8px;">Converted</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($daily ?? []) as $row)
                <tr>
                    <td style="border-bottom:1px solid #edf3f9; padding:8px;">{{ $row['day'] }}</td>
                    <td style="border-bottom:1px solid #edf3f9; padding:8px;">{{ $row['lead_count'] }}</td>
                    <td style="border-bottom:1px solid #edf3f9; padding:8px;">{{ $row['verified_count'] }}</td>
                    <td style="border-bottom:1px solid #edf3f9; padding:8px;">{{ $row['converted_count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="padding:8px;" class="muted">Gunluk veri yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="card" style="margin-top:12px;">
    <h4 style="margin:0 0 8px;">Kullanim Kilavuzu</h4>
    <ol class="muted" style="margin:0; padding-left:18px;">
        <li>Bu sayfa tek kampanyanin lead/verified/converted dagilimini verir.</li>
        <li>Gunluk metrik tablosu kampanya trendini tarih bazinda izlemeni saglar.</li>
        <li>Eslestirme key alanlari, campaign id olmadan gelen UTM kayitlarini yakalamak icindir.</li>
    </ol>
</section>

<details class="card" style="margin-top:12px;">
    <summary class="det-sum">
        <h3>📖 Kampanya Raporu — Kullanım Kılavuzu</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;">
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 12px;">Seçilen kampanyanın ayrıntılı performans raporu. Yöneticiye veya müşteriye sunmak için kullanılır.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 Temel Metrikler</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><strong>Lead:</strong> Kampanyadan gelen toplam aday sayısı</li>
                    <li><strong>Verified:</strong> Ön değerlendirmeyi geçen adaylar</li>
                    <li><strong>Converted:</strong> Gerçekten kayıt olan adaylar</li>
                    <li><strong>Dönüşüm Oranı:</strong> Converted / Lead × 100</li>
                    <li><strong>CPL:</strong> Harcama / Lead sayısı</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📈 Trend Analizi</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Günlük metrik tablosu kampanya trendini tarih bazında izler</li>
                    <li>CTR düşükse → reklam görseli/metni değiştir</li>
                    <li>CPL yüksekse → hedef kitle daralt</li>
                    <li>Eşleştirme key alanları → campaign_id olmadan gelen UTM kayıtları</li>
                </ul>
            </div>
        </div>
        <div style="margin-top:10px;padding:10px;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
            💡 Raporu yönetim sunumuna eklemek için Ctrl+P → PDF kaydet.
        </div>
    </div>
</details>
@endsection

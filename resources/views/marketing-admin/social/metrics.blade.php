@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/social/accounts">Hesaplar</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/social/posts">Postlar</a>
<a class="btn" style="font-size:var(--tx-xs);padding:6px 12px;background:var(--u-brand,#1e40af);color:#fff;border-color:transparent;" href="/mktg-admin/social/metrics">Metrikler</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/social/calendar">Takvim</a>
@endsection

@section('title', 'Sosyal Medya Metrikleri')
@section('page_subtitle', 'Sosyal Medya Metrikleri — platform bazlı aylık performans verileri')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }

/* Stats bar */
.sm-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.sm-stat  { flex:1; padding:10px 14px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.sm-stat:last-child { border-right:none; }
.sm-val   { font-size:18px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.sm-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }
@media(max-width:1100px){ .sm-stats { flex-wrap:wrap; } .sm-stat { flex:1 1 30%; border-bottom:1px solid var(--u-line,#e2e8f0); } }

/* Table */
.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:1000px; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }

/* Details guide */
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

/* Filter bar */
.fl-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.fl-bar input, .fl-bar select {
    height:34px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0);
    border-radius:8px; background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:12px; outline:none; appearance:auto;
}
.fl-bar input:focus, .fl-bar select:focus { border-color:var(--u-brand,#1e40af); }
</style>

<div style="display:grid;gap:12px;">

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Sosyal Medya Rehberi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 14px;line-height:1.6;">
            Sosyal medya modülü; hesap bağlama, post planlaması, metrik takibi ve takvim görünümünü tek çatı altında sunar.
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div style="display:flex;flex-direction:column;gap:8px;font-size:var(--tx-xs);line-height:1.5;">
                @foreach(['Hesap bağla — Hesaplar sekmesinde platformları (Instagram, Facebook, LinkedIn, TikTok) ekle ve API bağlantısını kur.','Post planla — Postlar sekmesinde içerik oluştur, platform seç ve yayın tarihi belirle.','Metrikleri izle — Dönem seçerek beğeni, takipçi artışı, etkileşim ve erişim verilerini platform bazında gör.','Aylık rapor — Aylık Detay linkiyle seçili dönemin detaylı büyüme raporunu görüntüle. Otomatik senkronizasyon her gün 07:00\'de çalışır.'] as $i => $step)
                <div style="display:flex;gap:8px;align-items:flex-start;">
                    <span style="background:var(--u-brand,#1e40af);color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:var(--tx-xs);font-weight:700;flex-shrink:0;">{{ $i+1 }}</span>
                    <span>{{ $step }}</span>
                </div>
                @endforeach
            </div>
            <div>
                <div style="font-size:var(--tx-xs);font-weight:600;margin-bottom:8px;">Sekme Özeti</div>
                <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;overflow:hidden;font-size:var(--tx-xs);">
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);"><span style="min-width:70px;font-weight:600;">Hesaplar</span><span style="color:var(--u-muted);">Bağlı sosyal medya platformları</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);"><span style="min-width:70px;font-weight:600;">Postlar</span><span style="color:var(--u-muted);">İçerik oluşturma ve planlama</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);"><span style="min-width:70px;font-weight:600;">Metrikler</span><span style="color:var(--u-muted);">Platform bazlı performans verileri</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;"><span style="min-width:70px;font-weight:600;">Takvim</span><span style="color:var(--u-muted);">Takvim görünümünde yayın planı</span></div>
                </div>
                <div style="margin-top:10px;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                    💡 Metrikler otomatik senkronize edilir. Bağlantı hatası varsa Entegrasyonlar menüsünden hesabı yeniden bağla.
                </div>
            </div>
        </div>
    </details>

    {{-- Dönem filtresi --}}
    <div class="card">
        <div class="fl-bar" style="justify-content:space-between;">
            <form method="GET" action="/mktg-admin/social/metrics" style="display:contents;">
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="month" name="period" value="{{ $period }}">
                    <button class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 16px;" type="submit">Dönemi Yükle</button>
                </div>
            </form>
            <a class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 16px;display:flex;align-items:center;" href="/mktg-admin/social/metrics/monthly/{{ $period }}">Aylık Detay →</a>
        </div>
    </div>

    {{-- KPI bar --}}
    <div class="sm-stats">
        <div class="sm-stat"><div class="sm-val">{{ number_format((int) ($summary['followers_end'] ?? 0), 0, ',', '.') }}</div><div class="sm-lbl">Followers</div></div>
        <div class="sm-stat"><div class="sm-val">{{ number_format((int) ($summary['followers_growth'] ?? 0), 0, ',', '.') }}</div><div class="sm-lbl">Büyüme</div></div>
        <div class="sm-stat"><div class="sm-val">{{ number_format((int) ($summary['total_posts'] ?? 0), 0, ',', '.') }}</div><div class="sm-lbl">Posts</div></div>
        <div class="sm-stat"><div class="sm-val">{{ number_format((int) ($summary['total_views'] ?? 0), 0, ',', '.') }}</div><div class="sm-lbl">Views</div></div>
        <div class="sm-stat"><div class="sm-val">{{ number_format((int) ($summary['total_engagement'] ?? 0), 0, ',', '.') }}</div><div class="sm-lbl">Engagement</div></div>
        <div class="sm-stat"><div class="sm-val">{{ number_format((int) ($summary['total_guest_registrations'] ?? 0), 0, ',', '.') }}</div><div class="sm-lbl">Aday Öğrenci Kayıt</div></div>
    </div>

    {{-- Tablo --}}
    <div class="card">
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Platform</th>
                    <th>Hesap</th>
                    <th>Followers</th>
                    <th>Growth %</th>
                    <th>Posts</th>
                    <th>Views</th>
                    <th>Likes</th>
                    <th>Comments</th>
                    <th>Shares</th>
                    <th>Avg Eng %</th>
                    <th>Clicks</th>
                    <th>Aday Öğrenci Kayıt</th>
                </tr></thead>
                <tbody>
                @forelse(($rows ?? []) as $row)
                    <tr>
                        <td><strong>{{ $row->platform }}</strong></td>
                        <td style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">{{ $row->account->account_name ?? '—' }}</td>
                        <td>{{ number_format((int) $row->followers_end, 0, ',', '.') }}</td>
                        <td>{{ number_format((float) $row->followers_growth_rate, 2, ',', '.') }}%</td>
                        <td>{{ $row->total_posts }}</td>
                        <td>{{ number_format((int) $row->total_views, 0, ',', '.') }}</td>
                        <td>{{ number_format((int) $row->total_likes, 0, ',', '.') }}</td>
                        <td>{{ number_format((int) $row->total_comments, 0, ',', '.') }}</td>
                        <td>{{ number_format((int) $row->total_shares, 0, ',', '.') }}</td>
                        <td>{{ number_format((float) $row->avg_engagement_rate, 2, ',', '.') }}%</td>
                        <td>{{ number_format((int) $row->total_click_through, 0, ',', '.') }}</td>
                        <td><strong>{{ number_format((int) $row->total_guest_registrations, 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="12" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Metrik verisi yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>


    {{-- Rehber --}}
    <details class="card" style="margin-top:0;">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Sosyal Medya Metrikler</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 Kolon Açıklamaları</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><strong>Takipçi Büyümesi %:</strong> Ay içi net artış oranı — negatifse içerik stratejisini gözden geçir</li>
                    <li><strong>Engagement %:</strong> (Beğeni+Yorum+Paylaşım) / Gösterim × 100 — %2+ iyi, %5+ mükemmel</li>
                    <li><strong>Click-Through:</strong> Link tıklamaları — kampanya trafiğini ölçer</li>
                    <li><strong>Aday Öğrenci Kayıt:</strong> Sosyal medyadan dönüşen başvuru sayısı</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📈 Nasıl Kullanılır</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Yıl/ay filtresi ile dönem karşılaştırması yap</li>
                    <li>En düşük engagement'lı platformda içerik formatını değiştir</li>
                    <li>Yüksek görüntülenme + düşük engagement → başlık/görsel sorunlu</li>
                    <li>Veriler Sosyal Hesaplar menüsünden girilen aylık metriklerdir</li>
                </ul>
            </div>
        </div>
    </details>

</div>
@endsection

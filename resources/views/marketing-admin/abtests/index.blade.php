@extends('marketing-admin.layouts.app')

@section('title', 'A/B Test Yönetimi')

@section('topbar-actions')
<button onclick="document.getElementById('ab-create-det').open=true;document.getElementById('ab-create-det').scrollIntoView({behavior:'smooth'})" class="btn ok" style="font-size:var(--tx-xs);padding:6px 14px;">+ Yeni Test</button>
@endsection

@section('page_subtitle', 'A/B Test Yönetimi — varyant karşılaştırma ve istatistiksel anlamlılık')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input, .wf-field select {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.wf-field input:focus, .wf-field select:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}
</style>

<div style="display:grid;gap:12px;">

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — A/B Testler</h3>
            <span class="det-chev">▼</span>
        </summary>
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 16px;line-height:1.6;">
            A/B testi, iki farklı versiyonu (A ve B) aynı anda farklı kullanıcı gruplarına göstererek hangisinin daha iyi performans gösterdiğini ölçer. Örneğin: <em>"Hangi email konusu daha çok açılıyor?"</em>
        </p>
        <div class="grid2" style="gap:16px;margin-bottom:16px;">
            <div>
                <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:10px;">⚙️ Adım Adım Kullanım</div>
                <div style="display:flex;flex-direction:column;gap:8px;font-size:var(--tx-xs);line-height:1.5;">
                    @foreach(['Testi oluştur — Ad, tür, metrik ve güven aralığını seç. "Yeni Test" butonuna tıkla.','Varyantları ekle — Test detay sayfasında A ve B versiyonlarını tanımla.','Testi başlat — "Çalıştır" butonuna tıkla. Sistem kullanıcıları A veya B grubuna atar.','Sonuçları izle — Min. örnek büyüklüğüne ulaşınca istatistiksel anlamlılık hesaplanır.','Kazananı uygula — Manuel ya da "Kazananı Otomatik Uygula" ile kazanan versiyon sisteme yansır.'] as $i => $step)
                    <div style="display:flex;gap:8px;align-items:flex-start;">
                        <span style="background:var(--u-brand,#1e40af);color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:var(--tx-xs);font-weight:700;flex-shrink:0;">{{ $i+1 }}</span>
                        <span>{{ $step }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:8px;">🧪 Test Türleri</div>
                    <div class="list" style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;">
                        <div class="item" style="font-size:var(--tx-xs);gap:8px;"><span class="badge info" style="min-width:110px;justify-content:center;">Email Konu</span><span>Hangi email başlığı daha çok açılıyor?</span></div>
                        <div class="item" style="font-size:var(--tx-xs);gap:8px;"><span class="badge info" style="min-width:110px;justify-content:center;">Email İçerik</span><span>Hangi email içeriği daha çok tıklanıyor?</span></div>
                        <div class="item" style="font-size:var(--tx-xs);gap:8px;"><span class="badge info" style="min-width:110px;justify-content:center;">Landing Page</span><span>Hangi sayfa tasarımı daha çok dönüşüm sağlıyor?</span></div>
                        <div class="item" style="font-size:var(--tx-xs);gap:8px;"><span class="badge info" style="min-width:110px;justify-content:center;">CMS Başlık</span><span>Hangi içerik başlığı daha çok okunuyor?</span></div>
                        <div class="item" style="font-size:var(--tx-xs);gap:8px;"><span class="badge info" style="min-width:110px;justify-content:center;">Workflow Bölünme</span><span>İki farklı otomasyon akışı karşılaştırma</span></div>
                        <div class="item" style="font-size:var(--tx-xs);gap:8px;"><span class="badge info" style="min-width:110px;justify-content:center;">Paket Gösterimi</span><span>Hangi paket sunum sırası daha çok tercih ediliyor?</span></div>
                    </div>
                </div>
                <div style="font-size:var(--tx-xs);line-height:1.6;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;color:var(--u-muted,#64748b);">
                    💡 <strong>"Kazananı Otomatik Uygula"</strong> seçeneği işaretlenirse istatistiksel anlamlılığa ulaşıldığında kazanan varyant otomatik aktif edilir ve marketing admin'e bildirim gönderilir.
                </div>
            </div>
        </div>
    </details>

    {{-- Create Form --}}
    <details class="card" id="ab-create-det">
        <summary class="det-sum">
            <h3>+ Yeni A/B Test</h3>
            <span class="det-chev">▼</span>
        </summary>
        <form method="POST" action="/mktg-admin/abtests" style="display:flex;flex-direction:column;gap:10px;margin-top:12px;">
            @csrf
            <div class="wf-field">
                <label>Test Adı *</label>
                <input type="text" name="name" required maxlength="255">
            </div>
            <div class="grid2">
                <div class="wf-field">
                    <label>Test Türü *</label>
                    <select name="test_type" required>
                        @foreach($typeLabels as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div class="wf-field">
                    <label>Birincil Metrik *</label>
                    <select name="primary_metric" required>
                        <option value="open_rate">Açılma Oranı</option>
                        <option value="click_rate">Tıklama Oranı</option>
                        <option value="conversion_rate">Dönüşüm Oranı</option>
                    </select>
                </div>
                <div class="wf-field">
                    <label>Min. Örnek Büyüklüğü</label>
                    <input type="number" name="min_sample_size" value="100" min="10">
                </div>
                <div class="wf-field">
                    <label>Güven Aralığı</label>
                    <select name="confidence_level">
                        <option value="0.90">%90</option>
                        <option value="0.95" selected>%95</option>
                        <option value="0.99">%99</option>
                    </select>
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:6px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);cursor:pointer;">
                <input type="checkbox" name="auto_winner" value="1">
                Kazananı Otomatik Uygula
            </label>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn ok">Oluştur</button>
                <button type="button" onclick="document.getElementById('ab-create-det').open=false" class="btn alt">İptal</button>
            </div>
        </form>
    </details>

    {{-- Test Listesi --}}
    <div class="card">
        <div class="list">
            <div class="item" style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);letter-spacing:.04em;text-transform:uppercase;">
                <span style="flex:3;">Test</span>
                <span style="width:110px;">Tür</span>
                <span style="width:80px;text-align:right;">Katılımcı</span>
                <span style="width:90px;text-align:right;">Durum</span>
                <span style="width:80px;text-align:right;">İşlem</span>
            </div>
            @forelse($tests as $test)
            <div class="item">
                <span style="flex:3;">
                    <a href="/mktg-admin/abtests/{{ $test->id }}" style="font-weight:600;color:var(--u-brand,#1e40af);text-decoration:none;">{{ $test->name }}</a>
                    @if($test->winner_variant)
                    <span class="badge ok" style="margin-left:6px;">Kazanan: {{ $test->winner_variant }}</span>
                    @endif
                    <br><small style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">{{ $metricLabels[$test->primary_metric] ?? $test->primary_metric }}</small>
                </span>
                <span style="width:110px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $typeLabels[$test->test_type] ?? $test->test_type }}</span>
                <span style="width:80px;text-align:right;">{{ $test->assignments_count ?? 0 }}</span>
                <span style="width:90px;text-align:right;">
                    <span class="badge {{ $statusColors[$test->status] ?? 'info' }}">{{ $statusLabels[$test->status] ?? $test->status }}</span>
                </span>
                <span style="width:80px;text-align:right;">
                    <a href="/mktg-admin/abtests/{{ $test->id }}" class="btn alt" style="padding:4px 10px;font-size:var(--tx-xs);">Detay</a>
                </span>
            </div>
            @empty
            <div class="item" style="color:var(--u-muted,#64748b);">Henüz A/B test yok.</div>
            @endforelse
        </div>
        <div style="margin-top:12px;">{{ $tests->links() }}</div>
    </div>

</div>
@endsection

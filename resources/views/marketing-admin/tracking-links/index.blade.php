@extends('marketing-admin.layouts.app')

@section('title', 'Tracking Linkler')
@section('page_subtitle', 'Tracking Link Envanteri — reklam linkleri, tıklama logları ve dönüşüm')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

/* Form grid */
.tf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.tf-col  { display:grid; gap:12px; }
@media(max-width:1100px){ .tf-grid { grid-template-columns:1fr; } }

/* Section boxes */
.tf-box { border:1px solid var(--u-line,#e2e8f0); border-radius:10px; padding:12px; background:color-mix(in srgb,var(--u-brand,#1e40af) 3%,var(--u-card,#fff)); }
.tf-box h4 { margin:0 0 10px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); }

/* Field (label + input) */
.tf-field { display:grid; gap:4px; }
.tf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.tf-field input, .tf-field select {
    width:100%; box-sizing:border-box; height:34px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.tf-field input:focus, .tf-field select:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}
.tf-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.tf-row + .tf-row { margin-top:10px; }

/* Code preview panel */
.tf-preview {
    border:1.5px solid var(--u-brand,#1e40af);
    border-radius:10px;
    background:color-mix(in srgb,var(--u-brand,#1e40af) 6%,var(--u-card,#fff));
    padding:12px 14px;
    min-width:240px;
}
.tf-preview .pv-lbl { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); margin-bottom:6px; }
.tf-preview .pv-code { font-family:ui-monospace,monospace; font-size:22px; font-weight:800; color:var(--u-brand,#1e40af); margin-bottom:4px; }
.tf-preview .pv-url  { font-family:ui-monospace,monospace; font-size:11px; color:var(--u-muted,#64748b); word-break:break-all; }

/* Filter bar */
.fl-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:10px; }
.fl-bar input, .fl-bar select {
    height:34px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0);
    border-radius:8px; background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:12px; outline:none; appearance:auto;
}
.fl-bar input:focus, .fl-bar select:focus { border-color:var(--u-brand,#1e40af); }

/* Table */
.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:900px; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }

/* Code pill */
.code-pill {
    display:inline-flex; align-items:center; padding:3px 8px; border-radius:999px;
    background:color-mix(in srgb,var(--u-brand,#1e40af) 8%,var(--u-card,#fff));
    border:1px solid color-mix(in srgb,var(--u-brand,#1e40af) 20%,var(--u-line,#e2e8f0));
    color:var(--u-brand,#1e40af); font-family:ui-monospace,monospace; font-size:12px; font-weight:700;
}

/* Inline status form */
.st-form { display:inline-flex; gap:5px; align-items:center; }
.st-form select {
    height:30px; padding:0 8px; border:1px solid var(--u-line,#e2e8f0);
    border-radius:6px; font-size:12px; background:var(--u-card,#fff); outline:none; appearance:auto;
}
.st-form select:focus { border-color:var(--u-brand,#1e40af); }

/* Share URL row */
.share-cell { display:flex; gap:6px; align-items:center; }
.share-cell input {
    flex:1; min-width:180px; height:30px; padding:0 8px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:6px;
    font-size:11px; font-family:ui-monospace,monospace;
    background:var(--u-bg,#f8fafc); color:var(--u-muted,#64748b); outline:none;
}

/* Alerts */
.flash   { border:1px solid var(--u-ok,#16a34a); background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,#fff); color:var(--u-ok,#16a34a); border-radius:10px; padding:10px 14px; font-size:13px; }
.err-box { border:1px solid var(--u-danger,#dc2626); background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,#fff); color:var(--u-danger,#dc2626); border-radius:10px; padding:10px 14px; font-size:13px; }
</style>

<div style="display:grid;gap:12px;">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="err-box">@foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach</div>
    @endif

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Tracking Links</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
            <span style="display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border:1px solid color-mix(in srgb,var(--u-brand,#1e40af) 25%,var(--u-line));background:color-mix(in srgb,var(--u-brand,#1e40af) 8%,var(--u-card,#fff));color:var(--u-brand,#1e40af);border-radius:999px;font-size:var(--tx-xs);font-weight:700;">
                Kod formatı: [kategori2][platform2][tip1][varyasyon2]
            </span>
        </div>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.6;">
            <li>Kod Kategoriyasyonu alanlarında kategori/platform/tip seç ve varyasyon no gir (boş bırakılırsa otomatik seçilir).</li>
            <li>Sağ üstteki önizlemeden üretilecek kodu kontrol et.</li>
            <li>Link Bilgisi, Kampanya ve UTM alanlarını doldurup <strong>Tracking Link Ekle</strong> ile kaydet.</li>
            <li>Alttaki listeden <strong>Dağıtım Linki</strong> alanını kopyalayıp reklamlarda kullan.</li>
            <li>Durum yönetimini active/paused/archived ile yap; performansı Tıklama/Lead/Converted kolonlarından izle.</li>
        </ol>
    </details>

    {{-- Form --}}
    <section class="card">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:14px;flex-wrap:wrap;">
            <div>
                <h3 style="margin:0 0 4px;font-size:var(--tx-base);font-weight:700;">Yeni Tracking Link</h3>
                <p style="margin:0;font-size:var(--tx-sm);color:var(--u-muted,#64748b);">Onaylı kreatif için kod üret, linki dağıt, geri dönüşümü otomatik izle.</p>
            </div>
            <div class="tf-preview">
                <div class="pv-lbl">Üretilecek Kod</div>
                <div class="pv-code" id="trackingCodePreviewText">—</div>
                <div class="pv-url"  id="shareLinkPreview">/go/—</div>
            </div>
        </div>

        <form method="POST" action="/mktg-admin/tracking-links" id="trackingLinkForm">
            @csrf
            <div class="tf-grid">
                <div class="tf-col">
                    <div class="tf-box">
                        <h4>Kod Kategoriyasyonu</h4>
                        <div class="tf-row">
                            <div class="tf-field">
                                <label>Kategori</label>
                                <select name="category_code" id="categoryCodeSelect" required>
                                    <option value="">Seçiniz</option>
                                    @foreach(($codeSchema ?? []) as $catCode => $catData)
                                        <option value="{{ $catCode }}" @selected(old('category_code') === $catCode)>{{ $catCode }} — {{ $catData['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tf-field">
                                <label>Platform</label>
                                <select name="platform_code" id="platformCodeSelect" required>
                                    <option value="">Seçiniz</option>
                                </select>
                            </div>
                        </div>
                        <div class="tf-row">
                            <div class="tf-field">
                                <label>Tip / Yerleşim</label>
                                <select name="placement_code" id="placementCodeSelect" required>
                                    <option value="">Seçiniz</option>
                                </select>
                            </div>
                            <div class="tf-field">
                                <label>Varyasyon No (01–99)</label>
                                <input name="variation_no" id="variationNoInput" type="number" min="1" max="99" value="{{ old('variation_no') }}" placeholder="Boş = otomatik">
                            </div>
                        </div>
                    </div>

                    <div class="tf-box">
                        <h4>Link Bilgisi</h4>
                        <div class="tf-field">
                            <label>Başlık</label>
                            <input name="title" placeholder="Örn: Instagram Reel Winter Creative A" value="{{ old('title') }}">
                        </div>
                        <div class="tf-row" style="margin-top:10px;">
                            <div class="tf-field">
                                <label>Hedef URL/Path</label>
                                <input name="destination_path" placeholder="/apply" value="{{ old('destination_path', '/apply') }}">
                            </div>
                            <div class="tf-field">
                                <label>Durum</label>
                                <select name="status">
                                    @foreach(['active' => 'active', 'paused' => 'paused', 'archived' => 'archived'] as $v => $label)
                                        <option value="{{ $v }}" @selected(old('status', 'active') === $v)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tf-col">
                    <div class="tf-box">
                        <h4>Kampanya ve Kaynak</h4>
                        <div class="tf-row">
                            <div class="tf-field">
                                <label>Kampanya</label>
                                <select name="campaign_id">
                                    <option value="">Seçiniz</option>
                                    @foreach(($campaigns ?? []) as $campaign)
                                        <option value="{{ $campaign->id }}" @selected((string) old('campaign_id') === (string) $campaign->id)>#{{ $campaign->id }} — {{ $campaign->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tf-field">
                                <label>Kampanya Kodu</label>
                                <input name="campaign_code" placeholder="de_winter_2026" value="{{ old('campaign_code') }}">
                            </div>
                        </div>
                        <div class="tf-row">
                            <div class="tf-field">
                                <label>Lead Source (code)</label>
                                <input name="source_code" placeholder="instagram" value="{{ old('source_code') }}">
                            </div>
                            <div class="tf-field">
                                <label>Dealer Code (opsiyonel)</label>
                                <input name="dealer_code" placeholder="REF-000001" value="{{ old('dealer_code') }}">
                            </div>
                        </div>
                    </div>

                    <div class="tf-box">
                        <h4>UTM Parametreleri</h4>
                        <div class="tf-row">
                            <div class="tf-field"><label>utm_source</label><input name="utm_source" placeholder="instagram" value="{{ old('utm_source') }}"></div>
                            <div class="tf-field"><label>utm_medium</label><input name="utm_medium" placeholder="paid_social" value="{{ old('utm_medium') }}"></div>
                        </div>
                        <div class="tf-row">
                            <div class="tf-field"><label>utm_campaign</label><input name="utm_campaign" placeholder="de_winter_2026" value="{{ old('utm_campaign') }}"></div>
                            <div class="tf-field"><label>utm_content</label><input name="utm_content" placeholder="story_a" value="{{ old('utm_content') }}"></div>
                        </div>
                        <div class="tf-row">
                            <div class="tf-field"><label>utm_term</label><input name="utm_term" placeholder="keyword (opsiyonel)" value="{{ old('utm_term') }}"></div>
                            <div class="tf-field"><label>Not</label><input name="notes" placeholder="kreatif açıklaması" value="{{ old('notes') }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:12px;margin-top:14px;flex-wrap:wrap;">
                <button type="submit" class="btn ok">Tracking Link Ekle</button>
                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">Kod çakışırsa sistem uyarır; varyasyon boşsa otomatik ilk boş no seçilir.</span>
            </div>
        </form>
    </section>

    {{-- Tablo --}}
    <section class="card">
        @php $filterState = $filters ?? ['q' => '', 'status' => 'all', 'campaign_id' => null]; @endphp

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
            <h3 style="margin:0;font-size:var(--tx-sm);font-weight:700;">Kayıtlı Reklam Linkleri</h3>
            <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);font-weight:600;">{{ number_format((int) ($links->total() ?? 0)) }} kayıt</span>
        </div>

        <form method="GET" action="/mktg-admin/tracking-links">
            <div class="fl-bar">
                <input name="q" value="{{ $filterState['q'] ?? '' }}" placeholder="Ara: kod, başlık, kampanya, source, dealer" style="flex:2;min-width:180px;">
                <select name="status">
                    <option value="all" @selected(($filterState['status'] ?? 'all') === 'all')>Tüm durumlar</option>
                    @foreach(['active', 'paused', 'archived'] as $statusOpt)
                        <option value="{{ $statusOpt }}" @selected(($filterState['status'] ?? 'all') === $statusOpt)>{{ $statusOpt }}</option>
                    @endforeach
                </select>
                <select name="campaign_id">
                    <option value="">Tüm kampanyalar</option>
                    @foreach(($campaigns ?? []) as $campaign)
                        <option value="{{ $campaign->id }}" @selected((int) ($filterState['campaign_id'] ?? 0) === (int) $campaign->id)>#{{ $campaign->id }} — {{ $campaign->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                <a href="/mktg-admin/tracking-links" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 14px;display:flex;align-items:center;">Temizle</a>
            </div>
        </form>

        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Code</th><th>Başlık</th><th>Durum</th><th>Tıklama</th><th>Lead</th><th>Converted</th><th>Dağıtım Linki</th>
                </tr></thead>
                <tbody>
                @forelse(($links ?? []) as $link)
                    @php
                        $summary  = $summaryByCode[$link->code] ?? ['lead_count' => 0, 'converted_count' => 0];
                        $shareUrl = url('/go/'.$link->code);
                        $stBadge  = match($link->status) { 'active' => 'ok', 'paused' => 'warn', 'archived' => 'pending', default => '' };
                    @endphp
                    <tr>
                        <td><span class="code-pill">{{ $link->code }}</span></td>
                        <td>
                            <strong>{{ $link->title }}</strong><br>
                            <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                                {{ $link->category_code ?: '—' }} · {{ $link->platform_code ?: '—' }} · {{ $link->placement_code ?: '—' }} · v{{ str_pad((string) ($link->variation_no ?? 0), 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/mktg-admin/tracking-links/{{ $link->id }}" class="st-form">
                                @csrf @method('PUT')
                                <input type="hidden" name="status_only" value="1">
                                <select name="status">
                                    @foreach(['active', 'paused', 'archived'] as $statusOpt)
                                        <option value="{{ $statusOpt }}" @selected($link->status === $statusOpt)>{{ $statusOpt }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn alt" style="height:30px;font-size:var(--tx-xs);padding:0 8px;">Kaydet</button>
                            </form>
                        </td>
                        <td style="font-weight:600;">{{ $link->click_count }}</td>
                        <td>{{ $summary['lead_count'] ?? 0 }}</td>
                        <td>{{ $summary['converted_count'] ?? 0 }}</td>
                        <td>
                            <div class="share-cell">
                                <input value="{{ $shareUrl }}" readonly onclick="this.select();">
                                <button type="button" class="btn ok" style="height:30px;font-size:var(--tx-xs);padding:0 10px;white-space:nowrap;" data-copy="{{ $shareUrl }}">Kopyala</button>
                                <form method="POST" action="/mktg-admin/tracking-links/{{ $link->id }}"
                                      onsubmit="return confirm('Bu tracking linki silmek istiyor musun? Bu işlem geri alınamaz.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn warn" style="height:30px;font-size:var(--tx-xs);padding:0 8px;white-space:nowrap;">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Kayıtlı tracking link yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">{{ $links->links() }}</div>
    </section>
</div>

<script>
window.__trackingLinks = {
    schema:       @json($codeSchema ?? []),
    goBase:       @json(url('/go')),
    oldPlatform:  @json(old('platform_code')),
    oldPlacement: @json(old('placement_code'))
};
</script>
@endsection

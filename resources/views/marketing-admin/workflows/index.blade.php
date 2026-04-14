@extends('marketing-admin.layouts.app')

@section('title', 'Otomasyon Workflows')

@section('topbar-actions')
<button onclick="document.getElementById('wf-create-det').open=true;document.getElementById('wf-create-det').scrollIntoView({behavior:'smooth'})" class="btn ok" style="font-size:var(--tx-xs);padding:6px 14px;">+ Yeni Workflow</button>
@endsection

@section('page_subtitle', 'Otomasyon Workflowları — tetikleyici bazlı otomatik iletişim dizileri')

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
.wf-field input, .wf-field select, .wf-field textarea {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.wf-field textarea { height:64px; padding:8px 10px; resize:vertical; }
.wf-field input:focus, .wf-field select:focus, .wf-field textarea:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}
</style>

<div style="display:grid;gap:12px;">

    {{-- Create Form --}}
    <details class="card" id="wf-create-det">
        <summary class="det-sum">
            <h3>+ Yeni Workflow</h3>
            <span class="det-chev">▼</span>
        </summary>
        <form method="POST" action="/mktg-admin/workflows" style="display:flex;flex-direction:column;gap:10px;">
            @csrf
            <div class="wf-field">
                <label>Workflow Adı *</label>
                <input type="text" name="name" required maxlength="255">
            </div>
            <div class="wf-field">
                <label>Açıklama</label>
                <textarea name="description" maxlength="1000"></textarea>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div class="wf-field" style="flex:1;min-width:180px;">
                    <label>Tetikleyici *</label>
                    <select name="trigger_type" required>
                        <option value="">Seçin…</option>
                        <option value="guest_created">Yeni Aday Öğrenci Kaydı</option>
                        <option value="score_tier_changed">Score Tier Değişimi</option>
                        <option value="score_reached">Belirli Puana Ulaşma</option>
                        <option value="status_changed">Durum Değişimi</option>
                        <option value="document_uploaded">Belge Yüklendi</option>
                        <option value="inactivity">Hareketsizlik</option>
                        <option value="form_completed">Form Tamamlandı</option>
                        <option value="package_selected">Paket Seçildi</option>
                        <option value="email_opened">Email Açıldı</option>
                        <option value="date_based">Tarih Bazlı</option>
                        <option value="manual">Manuel</option>
                    </select>
                </div>
                <label style="display:flex;align-items:center;gap:6px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);cursor:pointer;padding-bottom:6px;">
                    <input type="checkbox" name="is_recurring" value="1">
                    Tekrarlayan (aynı kişi tekrar girebilir)
                </label>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn ok">Oluştur</button>
                <button type="button" onclick="document.getElementById('wf-create-det').open=false" class="btn alt">İptal</button>
            </div>
        </form>
    </details>

    {{-- Workflow Listesi --}}
    <div class="card">
        <div class="list">
            <div class="item" style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);letter-spacing:.04em;text-transform:uppercase;">
                <span style="flex:3;">Workflow</span>
                <span style="width:110px;">Tetikleyici</span>
                <span style="width:70px;text-align:right;">Enrollment</span>
                <span style="width:60px;text-align:right;">Aktif</span>
                <span style="width:90px;text-align:right;">Durum</span>
                <span style="width:140px;text-align:right;">İşlemler</span>
            </div>
            @forelse($workflows as $wf)
            <div class="item">
                <span style="flex:3;">
                    <a href="/mktg-admin/workflows/{{ $wf->id }}/builder" style="font-weight:600;color:var(--u-brand,#1e40af);text-decoration:none;">{{ $wf->name }}</a>
                    @if($wf->description)
                    <br><small style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">{{ Str::limit($wf->description, 60) }}</small>
                    @endif
                </span>
                <span style="width:110px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $wf->trigger_type }}</span>
                <span style="width:70px;text-align:right;font-size:var(--tx-sm);">{{ $wf->enrollments_count ?? 0 }}</span>
                <span style="width:60px;text-align:right;font-size:var(--tx-sm);color:var(--u-ok,#16a34a);">{{ $wf->active_enrollments_count ?? 0 }}</span>
                <span style="width:90px;text-align:right;">
                    <span class="badge {{ $statusColors[$wf->status] ?? 'info' }}">{{ $statusLabels[$wf->status] ?? $wf->status }}</span>
                </span>
                <span style="width:140px;display:flex;gap:4px;justify-content:flex-end;">
                    <a href="/mktg-admin/workflows/{{ $wf->id }}/builder" class="btn alt" style="padding:4px 9px;font-size:var(--tx-xs);">Builder</a>
                    @if($wf->status === 'active')
                    <form method="POST" action="/mktg-admin/workflows/{{ $wf->id }}/pause" style="display:inline;">
                        @csrf @method('PUT')
                        <button class="btn warn" style="padding:4px 9px;font-size:var(--tx-xs);">Durdur</button>
                    </form>
                    @else
                    <form method="POST" action="/mktg-admin/workflows/{{ $wf->id }}/activate" style="display:inline;">
                        @csrf @method('PUT')
                        <button class="btn ok" style="padding:4px 9px;font-size:var(--tx-xs);">Aktifleştir</button>
                    </form>
                    @endif
                </span>
            </div>
            @empty
            <div class="item" style="color:var(--u-muted,#64748b);">Henüz workflow yok. "Yeni Workflow" ile başlayın.</div>
            @endforelse
        </div>
        <div style="margin-top:12px;">{{ $workflows->links() }}</div>
    </div>



<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Otomasyon Workflow'ları</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🔧 Workflow Oluşturma</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Yeni Workflow → Builder'da node ekle → kaydet → aktifleştir</li>
                <li>Tetikleyici: Yeni başvuru, durum değişimi, belge yükleme vb.</li>
                <li>Node tipleri: E-posta, bildirim, bekleme, koşul, skor, görev oluştur</li>
                <li>Aktif workflow durdurulmadan node sırası değiştirilemez</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📈 Önerilen Workflow'lar</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>Hoş Geldin Serisi:</strong> Yeni başvuru → 3 adım e-posta dizisi</li>
                <li><strong>Belge Hatırlatma:</strong> Eksik belge → 7 günde bir hatırlatma</li>
                <li><strong>Hot Lead Alarmı:</strong> Puan 80+ → satış ekibine görev oluştur</li>
                <li><strong>Onay Sonrası:</strong> Başvuru onaylandı → WhatsApp + e-posta</li>
            </ul>
        </div>
    </div>
</details>

</div>
@endsection

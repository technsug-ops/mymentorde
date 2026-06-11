@extends('manager.layouts.app')

@section('title', 'AVV Registry — Veri İşleme Sözleşmeleri')
@section('page_title', 'AVV Registry — Auftragsverarbeitungsverträge (DSGVO Art. 28)')

@push('head')
<style>
.avv-wrap { max-width:1280px; margin:0 auto; padding:0 0 32px; }
.avv-head { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:18px 22px; margin-bottom:14px; }
.avv-head h2 { font-size:17px; font-weight:800; margin:0 0 4px; }
.avv-head p { font-size:12.5px; color:#64748b; margin:0; line-height:1.5; }
.avv-actions { display:flex; gap:8px; margin-top:12px; }
.avv-btn { padding:8px 16px; border-radius:8px; font-size:12.5px; font-weight:700; text-decoration:none; cursor:pointer; border:none; display:inline-flex; align-items:center; gap:6px; }
.avv-btn.primary { background:linear-gradient(135deg,#1e40af,#3b5fcc); color:#fff; }

.avv-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:14px; }
.avv-stat { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; }
.avv-stat-val { font-size:24px; font-weight:800; line-height:1.1; }
.avv-stat-lbl { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.5px; font-weight:600; margin-top:3px; }
.avv-stat.danger { border-top:3px solid #dc2626; }
.avv-stat.warn { border-top:3px solid #d97706; }
.avv-stat.ok { border-top:3px solid #16a34a; }
@media (max-width:740px) { .avv-stats { grid-template-columns:1fr 1fr; } }

.avv-flash { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:13px; }

.avv-table-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:auto; }
table.avv-table { width:100%; border-collapse:collapse; font-size:13px; min-width:900px; }
.avv-table thead th { text-align:left; padding:12px 14px; font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.5px; background:#f8fafc; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.avv-table tbody td { padding:11px 14px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.avv-table tbody tr:hover { background:#f8fafc; }
.avv-table .provider { font-weight:700; color:#0f172a; }
.avv-table .provider a { color:#1e40af; text-decoration:none; }
.avv-table .provider a:hover { text-decoration:underline; }
.avv-table .pill { display:inline-block; font-size:10.5px; padding:2px 8px; border-radius:99px; font-weight:600; }
.avv-table .pill.active { background:#dcfce7; color:#166534; }
.avv-table .pill.pending { background:#fef3c7; color:#92400e; }
.avv-table .pill.expired { background:#fef2f2; color:#991b1b; }
.avv-table .pill.terminated { background:#f1f5f9; color:#64748b; }
.avv-table .expiry { font-size:11.5px; }
.avv-table .expiry.warn { color:#d97706; font-weight:700; }
.avv-table .expiry.danger { color:#dc2626; font-weight:700; }
.avv-table .actions { display:flex; gap:6px; justify-content:flex-end; }
.avv-table .actions a, .avv-table .actions button { padding:5px 11px; border-radius:6px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:11.5px; font-weight:600; text-decoration:none; cursor:pointer; }
.avv-table .actions a.pdf { background:#dbeafe; color:#1e40af; border-color:#bfdbfe; }
.avv-table .actions button.danger { color:#dc2626; border-color:#fecaca; }
.empty-state { background:#fff; border:1px dashed #cbd5e1; border-radius:14px; padding:48px 24px; text-align:center; color:#64748b; }
</style>
@endpush

@section('content')
@php($gdprActive = 'avv')
@include('manager._partials.gdpr-tabs')

<div class="avv-wrap">
    <div class="avv-head">
        <h2 style="display:inline-flex;align-items:center;gap:8px;"><x-icon name="handshake" size="20" aria-label="sozlesme" /> AVV Registry — Veri İşleme Sözleşmeleri</h2>
        <p>DSGVO Art. 28 zorunluluğu. Sizin için veri işleyen tüm 3. taraf sağlayıcılar (hosting, email, CRM, analytics, payment) ile imzalanan AVV/DPA sözleşmelerinin arşivi.</p>
        <div class="avv-actions">
            <a href="{{ route('manager.avv.create') }}" class="avv-btn primary">+ Yeni AVV Ekle</a>
        </div>
    </div>

    <div class="avv-stats">
        <div class="avv-stat"><div class="avv-stat-val">{{ $stats['total'] }}</div><div class="avv-stat-lbl">Toplam</div></div>
        <div class="avv-stat ok"><div class="avv-stat-val" style="color:#16a34a;">{{ $stats['active'] }}</div><div class="avv-stat-lbl">Aktif</div></div>
        <div class="avv-stat warn"><div class="avv-stat-val" style="color:#d97706;">{{ $stats['expiring'] }}</div><div class="avv-stat-lbl">60g İçinde Sona Eriyor</div></div>
        <div class="avv-stat danger"><div class="avv-stat-val" style="color:#dc2626;">{{ $stats['expired'] }}</div><div class="avv-stat-lbl">Süresi Geçmiş</div></div>
        <div class="avv-stat"><div class="avv-stat-val" style="color:#94a3b8;">{{ $stats['missing_pdf'] }}</div><div class="avv-stat-lbl">PDF Eksik</div></div>
    </div>

    @if(session('flash_success'))
        <div class="avv-flash">{{ session('flash_success') }}</div>
    @endif

    @if($agreements->isEmpty())
        <div class="empty-state">
            <h3 style="font-size:15px;font-weight:700;color:#475569;margin:0 0 6px;display:inline-flex;align-items:center;gap:6px;justify-content:center;"><x-icon name="folder-open" size="18" aria-label="bos klasor" /> AVV Kayıtlı Değil</h3>
            <p style="font-size:12.5px;margin:0 0 14px;">Tipik AVV gerektiren sağlayıcılar: Hosting (IONOS/AWS), Email (Brevo/Google), CRM (HubSpot), Payment (Stripe), Analytics (Google), Cloud (Drive/Dropbox)...</p>
            <a href="{{ route('manager.avv.create') }}" class="avv-btn primary">+ İlk AVV'yi Ekle</a>
        </div>
    @else
        <div class="avv-table-wrap">
            <table class="avv-table">
                <thead>
                    <tr>
                        <th>Sağlayıcı</th>
                        <th>İşlem Amacı</th>
                        <th>İmza / Bitiş</th>
                        <th>Konum</th>
                        <th>Durum</th>
                        <th>PDF</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agreements as $a)
                        <tr>
                            <td class="provider">
                                @if($a->provider_url)
                                    <a href="{{ $a->provider_url }}" target="_blank">{{ $a->provider_name }}</a>
                                @else
                                    {{ $a->provider_name }}
                                @endif
                                @if($a->contact_email)
                                    <div style="font-size:10.5px;color:#94a3b8;font-weight:500;">{{ $a->contact_email }}</div>
                                @endif
                            </td>
                            <td style="max-width:280px;font-size:12px;">{{ Str::limit($a->purpose_summary, 100) }}</td>
                            <td>
                                @if($a->signed_date)
                                    <div style="font-size:11.5px;">İmza: {{ $a->signed_date->format('d.m.Y') }}</div>
                                @endif
                                @if($a->expires_date)
                                    @php
                                        $cls = '';
                                        if ($a->isExpired()) $cls = 'danger';
                                        elseif ($a->isExpiringSoon()) $cls = 'warn';
                                    @endphp
                                    <div class="expiry {{ $cls }}">Bitiş: {{ $a->expires_date->format('d.m.Y') }}</div>
                                @endif
                            </td>
                            <td>
                                <span style="font-size:11.5px;">{{ $a->country ?: '—' }}</span>
                                @if(!$a->eu_based)
                                    <span class="pill expired" style="margin-left:4px;display:inline-flex;align-items:center;gap:3px;">EU Dışı <x-icon name="alert-triangle" size="11" aria-label="uyari" /></span>
                                @endif
                            </td>
                            <td>
                                <span class="pill {{ $a->status }}">{{ \App\Models\DataProcessingAgreement::STATUS_LABELS[$a->status] ?? $a->status }}</span>
                            </td>
                            <td>
                                @if($a->avv_pdf_path)
                                    <a href="{{ route('manager.avv.download', $a) }}" class="pdf" style="font-size:11.5px;color:#1e40af;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:4px;"><x-icon name="file-text" size="13" aria-label="pdf" /> İndir</a>
                                @else
                                    <span style="font-size:11px;color:#dc2626;font-weight:600;display:inline-flex;align-items:center;gap:3px;"><x-icon name="alert-triangle" size="12" aria-label="eksik" /> Eksik</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('manager.avv.edit', $a) }}" aria-label="duzenle"><x-icon name="pencil" size="13" /></a>
                                    <form method="post" action="{{ route('manager.avv.destroy', $a) }}" style="display:inline;margin:0;"
                                          onsubmit="return confirm('Bu AVV kaydını silmek istediğinizden emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="danger" aria-label="sil"><x-icon name="trash" size="13" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

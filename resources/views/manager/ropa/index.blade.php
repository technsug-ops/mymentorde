@extends('manager.layouts.app')

@section('title', 'ROPA — Veri İşleme Sicili')
@section('page_title', 'ROPA — Verarbeitungsverzeichnis (DSGVO Art. 30)')

@push('head')
<style>
.ropa-wrap { max-width:1280px; margin:0 auto; padding:0 0 32px; }
.ropa-head {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    padding:18px 22px; margin-bottom:14px; box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.ropa-head h2 { font-size:17px; font-weight:800; margin:0 0 4px; color:#0f172a; }
.ropa-head p { font-size:12.5px; color:#64748b; margin:0; line-height:1.5; }
.ropa-actions { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
.ropa-btn {
    padding:8px 16px; border-radius:8px; font-size:12.5px; font-weight:700;
    text-decoration:none; cursor:pointer; border:none; display:inline-flex; align-items:center; gap:6px;
}
.ropa-btn.primary { background:linear-gradient(135deg,#1e40af,#3b5fcc); color:#fff; }
.ropa-btn.alt { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }

.ropa-flash {
    background:#dcfce7; border:1px solid #86efac; color:#166534;
    padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:13px;
}

.ropa-table-wrap {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:auto;
    box-shadow:0 1px 3px rgba(0,0,0,.04);
}
table.ropa-table { width:100%; border-collapse:collapse; font-size:13px; min-width:900px; }
.ropa-table thead th {
    text-align:left; padding:12px 14px; font-size:11px; font-weight:700;
    color:#475569; text-transform:uppercase; letter-spacing:.5px;
    background:#f8fafc; border-bottom:1px solid #e2e8f0; white-space:nowrap;
}
.ropa-table tbody td { padding:11px 14px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.ropa-table tbody tr:hover { background:#f8fafc; }
.ropa-table tbody td.cell-name { font-weight:700; color:#0f172a; min-width:180px; }
.ropa-table .pill {
    display:inline-block; font-size:10.5px; padding:2px 8px; border-radius:99px;
    background:#f1f5f9; color:#475569; margin:1px;
}
.ropa-table .pill.legal { background:#ede9fe; color:#6d28d9; }
.ropa-table .pill.tc { background:#fee2e2; color:#991b1b; }
.ropa-table .actions { display:flex; gap:6px; justify-content:flex-end; }
.ropa-table .actions a, .ropa-table .actions button {
    padding:5px 11px; border-radius:6px; border:1px solid #cbd5e1; background:#fff;
    color:#475569; font-size:11.5px; font-weight:600; text-decoration:none; cursor:pointer;
}
.ropa-table .actions a:hover { background:#f1f5f9; color:#0f172a; }
.ropa-table .actions button.danger { color:#dc2626; border-color:#fecaca; }
.ropa-table .actions button.danger:hover { background:#fef2f2; }
.empty-state {
    background:#fff; border:1px dashed #cbd5e1; border-radius:14px;
    padding:48px 24px; text-align:center; color:#64748b;
}
.empty-state h3 { font-size:15px; font-weight:700; color:#475569; margin:0 0 6px; }
.empty-state p { font-size:12.5px; margin:0 0 14px; }
.tag-inactive { opacity:.55; }
</style>
@endpush

@section('content')
<div class="ropa-wrap">
    <div class="ropa-head">
        <h2>📋 ROPA — Veri İşleme Sicili</h2>
        <p>DSGVO Art. 30 zorunluluğu. Şirketinizdeki tüm kişisel veri işleme süreçlerinin yazılı kaydı. Denetim olduğunda <strong>ilk istenen belge</strong>.</p>
        <div class="ropa-actions">
            <a href="{{ route('manager.ropa.create') }}" class="ropa-btn primary">+ Yeni Aktivite</a>
            <a href="{{ route('manager.ropa.export-csv') }}" class="ropa-btn alt">⬇ CSV İndir</a>
        </div>
    </div>

    @if(session('flash_success'))
        <div class="ropa-flash">{{ session('flash_success') }}</div>
    @endif

    @if($activities->isEmpty())
        <div class="empty-state">
            <h3>📋 Henüz işlem aktivitesi tanımlanmamış</h3>
            <p>İlk işlem aktivitenizi ekleyerek başlayın. Tipik örnekler: Newsletter gönderimi, Faturalandırma, Çalışan yönetimi, Web analytics...</p>
            <a href="{{ route('manager.ropa.create') }}" class="ropa-btn primary">+ İlk Aktiviteyi Ekle</a>
        </div>
    @else
        <div class="ropa-table-wrap">
            <table class="ropa-table">
                <thead>
                    <tr>
                        <th>Aktivite</th>
                        <th>Amaç</th>
                        <th>Veri Kategorileri</th>
                        <th>Alıcılar</th>
                        <th>Hukuki Dayanak</th>
                        <th>Saklama</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $a)
                        <tr class="{{ $a->is_active ? '' : 'tag-inactive' }}">
                            <td class="cell-name">
                                {{ $a->name }}
                                @if($a->responsible)
                                    <div style="font-size:11px;color:#94a3b8;font-weight:500;margin-top:2px;">{{ $a->responsible }}</div>
                                @endif
                            </td>
                            <td style="max-width:280px;">{{ Str::limit($a->purpose, 120) }}</td>
                            <td>
                                @foreach((array) ($a->data_categories ?? []) as $c)
                                    <span class="pill">{{ $c }}</span>
                                @endforeach
                            </td>
                            <td>
                                @foreach((array) ($a->recipients ?? []) as $r)
                                    <span class="pill">{{ $r }}</span>
                                @endforeach
                                @if($a->third_country_transfer)
                                    <span class="pill tc">3. Ülke ⚠</span>
                                @endif
                            </td>
                            <td>
                                @if($a->legal_basis)
                                    <span class="pill legal">{{ \App\Models\ProcessingActivity::LEGAL_BASIS[$a->legal_basis] ?? $a->legal_basis }}</span>
                                @endif
                            </td>
                            <td>{{ $a->retention_days ? $a->retention_days . ' gün' : '—' }}</td>
                            <td>
                                @if($a->is_active)
                                    <span class="pill" style="background:#dcfce7;color:#166534;">Aktif</span>
                                @else
                                    <span class="pill" style="background:#fef2f2;color:#991b1b;">Pasif</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('manager.ropa.edit', $a) }}">✏️ Düzenle</a>
                                    <form method="post" action="{{ route('manager.ropa.destroy', $a) }}" style="display:inline;margin:0;"
                                          onsubmit="return confirm('Bu aktiviteyi silmek istediğinizden emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="danger">🗑️</button>
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

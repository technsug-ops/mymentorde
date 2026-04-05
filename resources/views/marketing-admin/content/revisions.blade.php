@extends('marketing-admin.layouts.app')

@section('title', 'İçerik Revizyonları')
@section('page_subtitle', 'İçerik sürüm geçmişi ve değişiklik takibi')

@section('content')
<style>
    .cr-page { display:grid; gap:12px; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:860px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
</style>

<div class="cr-page">
    <section class="card">
        <h3 style="margin:0 0 8px;">Revizyonlar: {{ $content->title_tr }}</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/content">İçerik</a>
            <a class="tab" href="/mktg-admin/content/{{ $content->id }}/stats">Stats</a>
            <a class="tab active" href="/mktg-admin/content/{{ $content->id }}/revisions">Revisions</a>
        </div>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Rev</th>
                    <th>Editor</th>
                    <th>Note</th>
                    <th>Snapshot</th>
                    <th>Tarih</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($revisions ?? []) as $row)
                    <tr>
                        <td>#{{ $row->revision_number }}</td>
                        <td>{{ $row->edited_by }}</td>
                        <td>{{ $row->change_note ?: '-' }}</td>
                        <td class="mono">{{ \Illuminate\Support\Str::limit(json_encode($row->snapshot_data, JSON_UNESCAPED_UNICODE), 140) }}</td>
                        <td>{{ $row->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Revizyon kaydi yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">{{ $revisions->links() }}</div>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — İçerik Revizyonları</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
            <li>Her kaydetme işlemi yeni bir revizyon satırı oluşturur — değişiklik geçmişi korunur</li>
            <li>Snapshot alanı, o revizyon anındaki özet içeriği saklar</li>
            <li>Hatalı güncelleme durumunda önceki revizyondan içeriği geri yükle</li>
            <li>Revizyon sayısı yüksekse içerik sık düzenleniyor — kalite kontrolüne al</li>
        </ul>
    </details>
</div>
@endsection


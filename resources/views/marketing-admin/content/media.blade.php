@extends('marketing-admin.layouts.app')

@section('title', 'Medya Kütüphanesi')
@section('page_subtitle', 'Görsel, video ve dosya varlık yönetimi')

@section('content')
<style>
    .md-page { display:grid; gap:12px; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .md-grid { display:grid; grid-template-columns: .85fr 1.15fr; gap:12px; }
    .row { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:8px; margin-bottom:8px; }
    .row-1 { display:grid; grid-template-columns: 1fr; gap:8px; margin-bottom:8px; }
    .row input, .row select, .row textarea, .row-1 input, .row-1 select, .row-1 textarea {
        border:1px solid var(--line); border-radius:8px; padding:8px 10px; font-size:13px; min-height:38px; width:100%;
    }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .toolbar form { display:inline-flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .toolbar input, .toolbar select { border:1px solid var(--line); border-radius:8px; padding:7px 10px; font-size:13px; min-width:130px; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:860px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
    .btn { border:0; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; }
    .btn-primary { background:#0a67d8; color:#fff; }
    .btn-muted { background:#eef4fb; color:#204d87; border:1px solid #d2deea; }
    .btn-danger { background:#c93a3a; color:#fff; }
    .flash { border:1px solid #bfe2ca; background:#edf9f0; color:#1f6d35; border-radius:10px; padding:10px 12px; font-size:13px; }
    .err-box { border:1px solid #f0c4c4; background:#fff2f2; color:#b12525; border-radius:10px; padding:10px 12px; font-size:13px; }
    @media (max-width: 1100px) { .md-grid { grid-template-columns: 1fr; } }
</style>

<div class="md-page">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="err-box">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <section class="card">
        <h3 style="margin:0 0 8px;">CMS Medya Kutuphanesi</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/content">İçerik</a>
            <a class="tab" href="/mktg-admin/categories">Kategoriler</a>
            <a class="tab active" href="/mktg-admin/media">Medya</a>
        </div>
    </section>

    <section class="md-grid">
        <article class="card">
            <h4 style="margin:0 0 8px;">Yeni Medya Kaydi</h4>
            <form method="POST" action="/mktg-admin/media/upload" enctype="multipart/form-data">
                @csrf
                <div class="row-1" style="margin-bottom:4px;">
                    <label style="font-size:var(--tx-xs);color:#4a6a8f;font-weight:600;">Dosya Yükle (Otomatik tespit)</label>
                    <input type="file" name="upload_file" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.csv">
                </div>
                <div style="border-top:1px solid #dce6f0;margin:10px 0;padding-top:10px;">
                    <div style="font-size:var(--tx-xs);color:#8fa5bc;font-weight:600;text-transform:uppercase;margin-bottom:8px;">VEYA Manuel URL Girisi</div>
                    <div class="row">
                        <input name="file_name" placeholder="dosya adi (ops. otomatik)" value="{{ old('file_name') }}">
                        <input name="file_url" placeholder="dosya url (yüklemede otomatik)" value="{{ old('file_url') }}">
                    </div>
                    <div class="row">
                        <select name="file_type">
                            <option value="">-- tip otomatik --</option>
                            @foreach(($typeOptions ?? []) as $tp)
                                <option value="{{ $tp }}" @selected(old('file_type') === $tp)>{{ $tp }}</option>
                            @endforeach
                        </select>
                        <input name="mime_type" placeholder="mime (yüklemede otomatik)" value="{{ old('mime_type') }}">
                    </div>
                    <div class="row">
                        <input type="number" min="1" name="file_size_bytes" placeholder="boyut byte (otomatik)" value="{{ old('file_size_bytes') }}">
                        <input name="thumbnail_url" placeholder="thumbnail url (gorsel yüklemede otomatik)" value="{{ old('thumbnail_url') }}">
                    </div>
                </div>
                <div class="row">
                    <input name="tags" placeholder="etiketler (virgullu)" value="{{ old('tags') }}">
                    <input name="used_in_content_ids" placeholder="içerik idleri (virgullu)" value="{{ old('used_in_content_ids') }}">
                </div>
                <div class="row-1">
                    <input name="alt_text" placeholder="alt text (ops.)" value="{{ old('alt_text') }}">
                </div>
                <button class="btn btn-primary" type="submit">Medya Ekle</button>
            </form>
        </article>

        <article class="card">
            <div class="toolbar">
                <form method="GET" action="/mktg-admin/media">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="dosya / alt text / url">
                    <select name="type">
                        <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>tum tipler</option>
                        @foreach(($typeOptions ?? []) as $tp)
                            <option value="{{ $tp }}" @selected(($filters['type'] ?? 'all') === $tp)>{{ $tp }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                    <a href="/mktg-admin/media" class="btn btn-muted">Temizle</a>
                </form>
            </div>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr><th>ID</th><th>Onizleme</th><th>Dosya</th><th>Tip</th><th>Boyut</th><th>Aksiyon</th></tr></thead>
                    <tbody>
                    @forelse(($rows ?? []) as $row)
                        <tr>
                            <td>#{{ $row->id }}</td>
                            <td style="width:58px;">
                                @if($row->thumbnail_url)
                                    <img src="{{ $row->thumbnail_url }}" alt="{{ $row->alt_text }}" loading="lazy" style="width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid #dce6f0;">
                                @elseif($row->file_type === 'image' && $row->file_url)
                                    <img src="{{ $row->file_url }}" alt="{{ $row->alt_text }}" loading="lazy" style="width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid #dce6f0;">
                                @else
                                    <span style="display:inline-flex;width:50px;height:50px;align-items:center;justify-content:center;background:#eef4fb;border-radius:6px;border:1px solid #dce6f0;font-size:var(--tx-xs);color:#4a6a8f;font-weight:700;">{{ strtoupper($row->file_type ?? '?') }}</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $row->file_name }}</strong><br>
                                <a href="{{ $row->file_url }}" target="_blank" style="font-size:var(--tx-xs);color:#3a6aad;word-break:break-all;">{{ Str::limit($row->file_url, 60) }}</a>
                                @if($row->alt_text)<br><span style="font-size:var(--tx-xs);color:#8fa5bc;">{{ $row->alt_text }}</span>@endif
                            </td>
                            <td>{{ $row->file_type }}<br><span class="muted">{{ $row->mime_type }}</span>
                                @if($row->width && $row->height)<br><span class="muted">{{ $row->width }}×{{ $row->height }}</span>@endif
                            </td>
                            <td>{{ $row->file_size_bytes >= 1024*1024
                                ? number_format($row->file_size_bytes/1024/1024, 1).' MB'
                                : number_format((int)($row->file_size_bytes/1024)).' KB' }}</td>
                            <td>
                                <form method="POST" action="/mktg-admin/media/{{ $row->id }}">@csrf @method('DELETE')<button class="btn btn-danger" type="submit" onclick="return confirm('Medya kaydi silinsin mi?')">Sil</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Medya kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">{{ $rows->links() }}</div>
        </article>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Medya Kütüphanesi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📁 Dosya Yükleme</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>JPG, PNG, GIF, WEBP, SVG, PDF, DOC, MP4 desteklenir (maks. 20 MB)</li>
                    <li>Görseller otomatik boyut bilgisi kaydeder</li>
                    <li>Harici CDN veya Firebase URL → Manuel URL girişiyle ekle</li>
                    <li>Silme → storage dosyası da temizlenir (yüklenen dosyalar için)</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🔗 Kullanım</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>İçerik düzenleyicisinden medyayı içeriğe bağla</li>
                    <li><code>used_in_content_ids</code> hangi içeriklerde kullanıldığını gösterir</li>
                    <li>Kullanılmayan medyaları periyodik temizle → depolama alanı kazanırsın</li>
                </ul>
            </div>
        </div>
    </details>
</div>
@endsection


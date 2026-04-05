@extends('marketing-admin.layouts.app')

@section('title', 'İçerik Kategorileri')
@section('page_subtitle', 'CMS içerik kategorileri ve taksonomi yönetimi')

@section('content')
<style>
    .cg-page { display:grid; gap:12px; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .cg-grid { display:grid; grid-template-columns: .9fr 1.1fr; gap:12px; }
    .row { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:8px; margin-bottom:8px; }
    .row-1 { display:grid; grid-template-columns: 1fr; gap:8px; margin-bottom:8px; }
    .row input, .row select, .row textarea, .row-1 input, .row-1 select, .row-1 textarea {
        border:1px solid var(--line); border-radius:8px; padding:8px 10px; font-size:13px; min-height:38px; width:100%;
    }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .toolbar form { display:inline-flex; gap:8px; align-items:center; }
    .toolbar input { border:1px solid var(--line); border-radius:8px; padding:7px 10px; font-size:13px; min-width:220px; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:760px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
    .btn { border:0; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; }
    .btn-primary { background:#0a67d8; color:#fff; }
    .btn-muted { background:#eef4fb; color:#204d87; border:1px solid #d2deea; }
    .btn-danger { background:#c93a3a; color:#fff; }
    .flash { border:1px solid #bfe2ca; background:#edf9f0; color:#1f6d35; border-radius:10px; padding:10px 12px; font-size:13px; }
    .err-box { border:1px solid #f0c4c4; background:#fff2f2; color:#b12525; border-radius:10px; padding:10px 12px; font-size:13px; }
    @media (max-width: 1100px) { .cg-grid { grid-template-columns: 1fr; } }
</style>

<div class="cg-page">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="err-box">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <section class="card">
        <h3 style="margin:0 0 8px;">CMS Kategorileri</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/content">İçerik</a>
            <a class="tab active" href="/mktg-admin/categories">Kategoriler</a>
            <a class="tab" href="/mktg-admin/media">Medya</a>
        </div>
    </section>

    <section class="cg-grid">
        <article class="card">
            @php
                $isEdit = !empty($editing);
                $action = $isEdit ? '/mktg-admin/categories/'.$editing->id : '/mktg-admin/categories';
            @endphp
            <h4 style="margin:0 0 8px;">{{ $isEdit ? 'Kategori Duzenle #'.$editing->id : 'Yeni Kategori' }}</h4>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="row">
                    <input name="code" placeholder="code" value="{{ old('code', $editing->code ?? '') }}" required>
                    <input name="name_tr" placeholder="ad TR" value="{{ old('name_tr', $editing->name_tr ?? '') }}" required>
                </div>
                <div class="row">
                    <input name="name_de" placeholder="ad DE (ops.)" value="{{ old('name_de', $editing->name_de ?? '') }}">
                    <input name="name_en" placeholder="ad EN (ops.)" value="{{ old('name_en', $editing->name_en ?? '') }}">
                </div>
                <div class="row">
                    <select name="parent_category_id">
                        <option value="">parent kategori (ops.)</option>
                        @foreach(($parentOptions ?? []) as $opt)
                            <option value="{{ $opt->id }}" @selected((string) old('parent_category_id', $editing->parent_category_id ?? '') === (string) $opt->id)>#{{ $opt->id }} {{ $opt->code }} - {{ $opt->name_tr }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $editing->sort_order ?? 0) }}" placeholder="sort">
                </div>
                <div class="row">
                    <input name="icon_url" placeholder="icon url (ops.)" value="{{ old('icon_url', $editing->icon_url ?? '') }}">
                    <select name="is_active">
                        <option value="1" @selected((string) old('is_active', isset($editing) ? (int) $editing->is_active : 1) === '1')>aktif</option>
                        <option value="0" @selected((string) old('is_active', isset($editing) ? (int) $editing->is_active : 1) === '0')>pasif</option>
                    </select>
                </div>
                <div class="row-1">
                    <textarea name="description_tr" placeholder="aciklama">{{ old('description_tr', $editing->description_tr ?? '') }}</textarea>
                </div>
                <div class="row-1">
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Kategori Güncelle' : 'Kategori Ekle' }}</button>
                    <a href="/mktg-admin/categories" class="btn btn-muted">Temizle</a>
                </div>
            </form>
        </article>

        <article class="card">
            <div class="toolbar">
                <form method="GET" action="/mktg-admin/categories">
                    <input name="q" value="{{ $q ?? '' }}" placeholder="code / ad ara">
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                    <a href="/mktg-admin/categories" class="btn btn-muted">Temizle</a>
                </form>
            </div>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr><th>ID</th><th>Kategori</th><th>Parent</th><th>Durum</th><th>Aksiyon</th></tr></thead>
                    <tbody>
                    @forelse(($rows ?? []) as $row)
                        <tr>
                            <td>#{{ $row->id }}</td>
                            <td>{{ $row->code }} - {{ $row->name_tr }}</td>
                            <td>{{ $row->parent_category_id ?: '-' }}</td>
                            <td>{{ $row->is_active ? 'aktif' : 'pasif' }}</td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a class="btn btn-muted" href="/mktg-admin/categories?edit_id={{ $row->id }}">Duzenle</a>
                                    <form method="POST" action="/mktg-admin/categories/{{ $row->id }}">@csrf @method('DELETE')<button class="btn btn-danger" type="submit" onclick="return confirm('Kategori silinsin mi?')">Sil</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Kategori kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">{{ $rows->links() }}</div>
        </article>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — İçerik Kategorileri</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🗂 Kategori Yapısı</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Kod kısa ve sabit tut: <code>blog</code>, <code>guide</code>, <code>faq</code>, <code>news</code></li>
                    <li>Üst kategori ile hiyerarşi kur (ana → alt)</li>
                    <li>Pasif kategori yeni içeriklerde görünmez, mevcut kayıtları bozmaz</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📋 İpuçları</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Fazla kategori içerik yönetimini karmaşıklaştırır — 5–8 ana kategori yeterli</li>
                    <li>Kategoriye içerik atamak için İçerik Yönetimi menüsünü kullan</li>
                    <li>SEO slug alanı doldurmak URL dostu adres oluşturur</li>
                </ul>
            </div>
        </div>
    </details>
</div>
@endsection


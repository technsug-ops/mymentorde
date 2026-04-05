@extends('marketing-admin.layouts.app')

@section('title', 'Sosyal Medya Gönderileri')
@section('page_subtitle', 'Sosyal medya gönderi yönetimi ve taslaklar')

@section('content')
<style>
    .sp-page { display:grid; gap:12px; }
    .sp-top { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:10px; }
    .sp-kpi .label { color: var(--muted); font-size:12px; }
    .sp-kpi .val { color:#0a67d8; font-size:22px; font-weight:700; }
    .sp-grid { display:grid; grid-template-columns: 1fr 1.2fr; gap:12px; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .row { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:8px; margin-bottom:8px; }
    .row input, .row select, .row textarea { border:1px solid var(--line); border-radius:8px; padding:8px 10px; font-size:13px; min-height:38px; width:100%; }
    .row textarea { min-height:84px; resize:vertical; }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .toolbar form { display:inline-flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .toolbar input, .toolbar select { border:1px solid var(--line); border-radius:8px; padding:7px 10px; font-size:13px; min-width:130px; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:1040px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
    .btn { border:0; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; }
    .btn-primary { background:#0a67d8; color:#fff; }
    .btn-muted { background:#eef4fb; color:#204d87; border:1px solid #d2deea; }
    .btn-danger { background:#c93a3a; color:#fff; }
    .flash { border:1px solid #bfe2ca; background:#edf9f0; color:#1f6d35; border-radius:10px; padding:10px 12px; font-size:13px; }
    .err-box { border:1px solid #f0c4c4; background:#fff2f2; color:#b12525; border-radius:10px; padding:10px 12px; font-size:13px; }
    @media (max-width: 1200px) { .sp-top { grid-template-columns: repeat(2, minmax(0, 1fr)); } .sp-grid { grid-template-columns: 1fr; } }
</style>

<div class="sp-page">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="err-box">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <section class="card">
        <h3 style="margin:0 0 8px;">Sosyal Gönderiler</h3>
        <div class="tabs">
            <a class="tab" href="/mktg-admin/social/accounts">Hesaplar</a>
            <a class="tab active" href="/mktg-admin/social/posts">Postlar</a>
            <a class="tab" href="/mktg-admin/social/metrics">Metrikler</a>
            <a class="tab" href="/mktg-admin/social/calendar">Takvim</a>
        </div>
    </section>

    <section class="sp-top">
        <article class="card sp-kpi"><div class="label">Toplam Post</div><div class="val">{{ $stats['total'] ?? 0 }}</div></article>
        <article class="card sp-kpi"><div class="label">Published</div><div class="val">{{ $stats['published'] ?? 0 }}</div></article>
        <article class="card sp-kpi"><div class="label">Scheduled</div><div class="val">{{ $stats['scheduled'] ?? 0 }}</div></article>
        <article class="card sp-kpi"><div class="label">Toplam Etkilesim</div><div class="val">{{ number_format((int) ($stats['engagement'] ?? 0), 0, ',', '.') }}</div></article>
    </section>

    <section class="sp-grid">
        <article class="card">
            @php
                $isEdit = !empty($editing);
                $action = $isEdit ? '/mktg-admin/social/posts/'.$editing->id : '/mktg-admin/social/posts';
                $mediaUrls = old('media_urls', $isEdit ? implode(',', (array) ($editing->media_urls ?? [])) : '');
                $tags = old('tags', $isEdit ? implode(',', (array) ($editing->tags ?? [])) : '');
            @endphp
            <h4 style="margin:0 0 8px;">{{ $isEdit ? 'Post Duzenle #'.$editing->id : 'Yeni Post' }}</h4>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="row">
                    <select name="account_id" required>
                        <option value="">hesap seçiniz</option>
                        @foreach(($accountOptions ?? []) as $acc)
                            <option value="{{ $acc->id }}" @selected((string) old('account_id', $editing->account_id ?? '') === (string) $acc->id)>{{ $acc->account_name }} ({{ $acc->platform }})</option>
                        @endforeach
                    </select>
                    <select name="platform">
                        @foreach(($platformOptions ?? []) as $pf)
                            <option value="{{ $pf }}" @selected(old('platform', $editing->platform ?? 'instagram') === $pf)>{{ $pf }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <select name="post_type">
                        @foreach(($postTypeOptions ?? []) as $tp)
                            <option value="{{ $tp }}" @selected(old('post_type', $editing->post_type ?? 'feed') === $tp)>{{ $tp }}</option>
                        @endforeach
                    </select>
                    <select name="status">
                        @foreach(($statusOptions ?? []) as $st)
                            <option value="{{ $st }}" @selected(old('status', $editing->status ?? 'draft') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <input name="post_url" placeholder="post url (ops.)" value="{{ old('post_url', $editing->post_url ?? '') }}">
                    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', !empty($editing->scheduled_at) ? \Illuminate\Support\Carbon::parse($editing->scheduled_at)->format('Y-m-d\TH:i') : '') }}">
                </div>
                <div class="row">
                    <input name="media_urls" placeholder="media url (virgullu)" value="{{ $mediaUrls }}">
                    <input name="tags" placeholder="etiket (virgullu)" value="{{ $tags }}">
                </div>
                <div class="row">
                    <select name="linked_campaign_id">
                        <option value="">campaign (ops.)</option>
                        @foreach(($campaignOptions ?? []) as $cmp)
                            <option value="{{ $cmp->id }}" @selected((string) old('linked_campaign_id', $editing->linked_campaign_id ?? '') === (string) $cmp->id)>#{{ $cmp->id }} {{ $cmp->name }}</option>
                        @endforeach
                    </select>
                    <select name="linked_content_id">
                        <option value="">cms content (ops.)</option>
                        @foreach(($contentOptions ?? []) as $cnt)
                            <option value="{{ $cnt->id }}" @selected((string) old('linked_content_id', $editing->linked_content_id ?? '') === (string) $cnt->id)>#{{ $cnt->id }} {{ \Illuminate\Support\Str::limit($cnt->title_tr, 40) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <input name="caption" placeholder="kisa aciklama/caption" value="{{ old('caption', $editing->caption ?? '') }}">
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Post Güncelle' : 'Post Ekle' }}</button>
                </div>
                <div class="row">
                    <a href="/mktg-admin/social/posts" class="btn btn-muted">Temizle</a>
                    <a href="/mktg-admin/social/calendar" class="btn btn-muted">Takvim</a>
                </div>
            </form>
        </article>

        <article class="card">
            <div class="toolbar">
                <h4 style="margin:0;">Post Listesi</h4>
                <form method="GET" action="/mktg-admin/social/posts">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="caption/url ara">
                    <select name="platform">
                        <option value="all" @selected(($filters['platform'] ?? 'all') === 'all')>Tüm platformlar</option>
                        @foreach(($platformOptions ?? []) as $pf)
                            <option value="{{ $pf }}" @selected(($filters['platform'] ?? 'all') === $pf)>{{ $pf }}</option>
                        @endforeach
                    </select>
                    <select name="status">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tüm durumlar</option>
                        @foreach(($statusOptions ?? []) as $st)
                            <option value="{{ $st }}" @selected(($filters['status'] ?? 'all') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" type="submit">Filtrele</button>
                    <a href="/mktg-admin/social/posts" class="btn btn-muted">Temizle</a>
                </form>
            </div>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr><th>ID</th><th>Post</th><th>Hesap</th><th>Status</th><th>Metrik</th><th>Aksiyon</th></tr></thead>
                    <tbody>
                    @forelse(($rows ?? []) as $row)
                        <tr>
                            <td>#{{ $row->id }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($row->caption ?: '-', 80) }}<br><span class="muted">{{ $row->post_type }} / {{ $row->platform }}</span></td>
                            <td>{{ $row->account->account_name ?? '-' }}</td>
                            @php $postStatusLbl = ['draft'=>'Taslak','scheduled'=>'Planlandı','published'=>'Yayınlandı'][$row->status] ?? ucfirst($row->status); @endphp
                            <td>{{ $postStatusLbl }}<br><span class="muted">{{ $row->scheduled_at ?: $row->published_at ?: '-' }}</span></td>
                            <td>v:{{ $row->metric_views }} | l:{{ $row->metric_likes }} | c:{{ $row->metric_comments }} | s:{{ $row->metric_shares }}</td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a class="btn btn-muted" href="/mktg-admin/social/posts?edit_id={{ $row->id }}">Duzenle</a>
                                    <form method="POST" action="/mktg-admin/social/posts/{{ $row->id }}/publish">@csrf @method('PUT')<button class="btn btn-primary" type="submit">Yayınla</button></form>
                                    <form method="POST" action="/mktg-admin/social/posts/{{ $row->id }}/metrics">@csrf @method('PUT')<input type="hidden" name="metric_likes" value="{{ (int) $row->metric_likes + 1 }}"><button class="btn btn-muted" type="submit">+Like</button></form>
                                    <form method="POST" action="/mktg-admin/social/posts/{{ $row->id }}">@csrf @method('DELETE')<button class="btn btn-danger" type="submit" onclick="return confirm('Post silinsin mi?')">Sil</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Sosyal post yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">{{ $rows->links() }}</div>
        </article>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Sosyal Medya Postları</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📝 Post Durumları</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><strong>draft:</strong> Taslak — henüz planlanmadı</li>
                    <li><strong>scheduled:</strong> Yayın tarihi atandı, bekliyor</li>
                    <li><strong>published:</strong> Yayında — metrik girişi yapılabilir</li>
                    <li><strong>cancelled:</strong> İptal edildi, raporlamaya dahil edilmez</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 Metrik Takibi</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Post yayınlandıktan sonra görüntülenme, beğeni, yorum, paylaşım gir</li>
                    <li>Kampanyaya bağlı postlar → Kampanya KPI hesabında kullanılır</li>
                    <li>İçeriğe bağlı postlar → CMS içerik performansında görünür</li>
                    <li>Takvim görünümü için Takvim sekmesini kullan</li>
                </ul>
            </div>
        </div>
    </details>
</div>
@endsection


@extends('marketing-admin.layouts.app')

@section('title', 'Sosyal Medya Hesapları')
@section('page_subtitle', 'Sosyal medya hesapları ve bağlantı yönetimi')

@section('content')
<style>
    .sa-page { display:grid; gap:12px; }
    .sa-top { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:10px; }
    .sa-kpi .label { color: var(--muted); font-size:12px; }
    .sa-kpi .val { color:#0a67d8; font-size:22px; font-weight:700; }
    .sa-grid { display:grid; grid-template-columns: 1fr 1.2fr; gap:12px; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .row { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:8px; margin-bottom:8px; }
    .row input, .row select { border:1px solid var(--line); border-radius:8px; padding:8px 10px; font-size:13px; min-height:38px; width:100%; }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .toolbar form { display:inline-flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .toolbar input, .toolbar select { border:1px solid var(--line); border-radius:8px; padding:7px 10px; font-size:13px; min-width:130px; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:940px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
    .btn { border:0; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; }
    .btn-primary { background:#0a67d8; color:#fff; }
    .btn-muted { background:#eef4fb; color:#204d87; border:1px solid #d2deea; }
    .btn-danger { background:#c93a3a; color:#fff; }
    .flash { border:1px solid #bfe2ca; background:#edf9f0; color:#1f6d35; border-radius:10px; padding:10px 12px; font-size:13px; }
    .err-box { border:1px solid #f0c4c4; background:#fff2f2; color:#b12525; border-radius:10px; padding:10px 12px; font-size:13px; }
    @media (max-width: 1200px) { .sa-top { grid-template-columns: repeat(2, minmax(0, 1fr)); } .sa-grid { grid-template-columns: 1fr; } }
</style>

<div class="sa-page">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="err-box">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <section class="card">
        <h3 style="margin:0 0 8px;">Sosyal Hesaplar</h3>
        <div class="tabs">
            <a class="tab active" href="/mktg-admin/social/accounts">Hesaplar</a>
            <a class="tab" href="/mktg-admin/social/posts">Postlar</a>
            <a class="tab" href="/mktg-admin/social/metrics">Metrikler</a>
            <a class="tab" href="/mktg-admin/social/calendar">Takvim</a>
        </div>
    </section>

    <section class="sa-top">
        <article class="card sa-kpi"><div class="label">Toplam Hesap</div><div class="val">{{ $stats['total'] ?? 0 }}</div></article>
        <article class="card sa-kpi"><div class="label">Aktif</div><div class="val">{{ $stats['active'] ?? 0 }}</div></article>
        <article class="card sa-kpi"><div class="label">API Bagli</div><div class="val">{{ $stats['connected'] ?? 0 }}</div></article>
        <article class="card sa-kpi"><div class="label">Toplam Takipci</div><div class="val">{{ number_format((int) ($stats['followers'] ?? 0), 0, ',', '.') }}</div></article>
    </section>

    <section class="sa-grid">
        <article class="card">
            @php
                $isEdit = !empty($editing);
                $action = $isEdit ? '/mktg-admin/social/accounts/'.$editing->id : '/mktg-admin/social/accounts';
            @endphp
            <h4 style="margin:0 0 8px;">{{ $isEdit ? 'Hesap Duzenle #'.$editing->id : 'Yeni Hesap' }}</h4>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="row">
                    <select name="platform">
                        @foreach(($platformOptions ?? []) as $pf)
                            <option value="{{ $pf }}" @selected(old('platform', $editing->platform ?? 'instagram') === $pf)>{{ $pf }}</option>
                        @endforeach
                    </select>
                    <input name="account_name" placeholder="hesap adi" value="{{ old('account_name', $editing->account_name ?? '') }}" required>
                </div>
                <div class="row">
                    <input name="account_url" placeholder="https://..." value="{{ old('account_url', $editing->account_url ?? '') }}" required>
                    <input name="profile_image_url" placeholder="profil gorseli url (ops.)" value="{{ old('profile_image_url', $editing->profile_image_url ?? '') }}">
                </div>
                <div class="row">
                    <input type="number" min="0" name="followers" placeholder="takipci" value="{{ old('followers', $editing->followers ?? 0) }}">
                    <input type="number" name="followers_growth_this_month" placeholder="aylik buyume" value="{{ old('followers_growth_this_month', $editing->followers_growth_this_month ?? 0) }}">
                </div>
                <div class="row">
                    <input type="number" min="0" name="total_posts" placeholder="toplam post" value="{{ old('total_posts', $editing->total_posts ?? 0) }}">
                    <select name="api_connected">
                        <option value="1" @selected((string) old('api_connected', isset($editing) ? (int) $editing->api_connected : 0) === '1')>api bagli</option>
                        <option value="0" @selected((string) old('api_connected', isset($editing) ? (int) $editing->api_connected : 0) === '0')>api bagli degil</option>
                    </select>
                </div>
                <div class="row">
                    <input name="api_access_token" placeholder="api token (ops.)" value="{{ old('api_access_token') }}">
                    <select name="is_active">
                        <option value="1" @selected((string) old('is_active', isset($editing) ? (int) $editing->is_active : 1) === '1')>durum: aktif</option>
                        <option value="0" @selected((string) old('is_active', isset($editing) ? (int) $editing->is_active : 1) === '0')>durum: pasif</option>
                    </select>
                </div>
                <div class="row">
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Hesap Güncelle' : 'Hesap Ekle' }}</button>
                    <a href="/mktg-admin/social/accounts" class="btn btn-muted">Temizle</a>
                </div>
            </form>
        </article>

        <article class="card">
            <div class="toolbar">
                <h4 style="margin:0;">Hesap Listesi</h4>
                <form method="GET" action="/mktg-admin/social/accounts">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="hesap/url ara">
                    <select name="platform">
                        <option value="all" @selected(($filters['platform'] ?? 'all') === 'all')>Tüm platformlar</option>
                        @foreach(($platformOptions ?? []) as $pf)
                            <option value="{{ $pf }}" @selected(($filters['platform'] ?? 'all') === $pf)>{{ $pf }}</option>
                        @endforeach
                    </select>
                    <select name="active">
                        <option value="all" @selected(($filters['active'] ?? 'all') === 'all')>Tüm durumlar</option>
                        <option value="active" @selected(($filters['active'] ?? 'all') === 'active')>aktif</option>
                        <option value="inactive" @selected(($filters['active'] ?? 'all') === 'inactive')>pasif</option>
                    </select>
                    <button class="btn btn-primary" type="submit">Filtrele</button>
                    <a href="/mktg-admin/social/accounts" class="btn btn-muted">Temizle</a>
                </form>
            </div>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr><th>ID</th><th>Hesap</th><th>Platform</th><th>Takipci</th><th>API</th><th>Aksiyon</th></tr></thead>
                    <tbody>
                    @forelse(($rows ?? []) as $row)
                        <tr>
                            <td>#{{ $row->id }}</td>
                            <td><strong>{{ $row->account_name }}</strong><br><span class="muted">{{ $row->account_url }}</span></td>
                            <td>{{ $row->platform }}</td>
                            <td>{{ number_format((int) $row->followers, 0, ',', '.') }}</td>
                            <td>{{ $row->api_connected ? 'Bağlı' : 'Kapalı' }}</td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a class="btn btn-muted" href="/mktg-admin/social/accounts?edit_id={{ $row->id }}">Duzenle</a>
                                    <form method="POST" action="/mktg-admin/social/accounts/{{ $row->id }}">@csrf @method('DELETE')<button class="btn btn-danger" type="submit" onclick="return confirm('Hesap silinsin mi?')">Sil</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Sosyal hesap yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">{{ $rows->links() }}</div>
        </article>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Sosyal Medya Hesapları</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🔗 Hesap Yönetimi</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Her platform için ayrı hesap tanımla (Instagram, Facebook, LinkedIn, YouTube…)</li>
                    <li><strong>Handle/username</strong> alanı → profil URL'si oluşturmak için kullanılır</li>
                    <li>Takipçi sayısı ve büyüme oranı → Metrikler sayfasında raporlanır</li>
                    <li>Hesap durumu <strong>active</strong> → post planlamada kullanılabilir</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📋 İş Akışı</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Yeni hesap ekle → Postlar menüsünden bu hesaba gönderi planla</li>
                    <li>Aylık metrikleri Metrikler menüsünden manuel gir</li>
                    <li>API bağlantısı aktifse ilerleyen versiyonda otomatik senkronize edilecek</li>
                    <li>Platform bazlı filtreyle sadece ilgili kanalı görüntüle</li>
                </ul>
            </div>
        </div>
    </details>
</div>
@endsection


@extends('marketing-admin.layouts.app')

@section('title', 'Etkinlik Kayıtları')
@section('page_subtitle', 'Etkinlik kayıt listesi ve katılımcı yönetimi')

@section('content')
<style>
    .rg-page { display:grid; gap:12px; }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; }
    .tab { border:1px solid #cbd9ea; border-radius:999px; padding:6px 10px; font-size:12px; color:#1f4b84; background:#eef4fb; text-decoration:none; font-weight:700; }
    .tab.active { background:#0a67d8; color:#fff; border-color:#0a67d8; }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; justify-content:space-between; align-items:center; }
    .toolbar form { display:inline-flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .toolbar input, .toolbar select { border:1px solid var(--line); border-radius:8px; padding:7px 10px; font-size:13px; min-width:140px; }
    .btn { border:0; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; }
    .btn-primary { background:#0a67d8; color:#fff; }
    .btn-muted { background:#eef4fb; color:#204d87; border:1px solid #d2deea; }
    .flash { border:1px solid #bfe2ca; background:#edf9f0; color:#1f6d35; border-radius:10px; padding:10px 12px; font-size:13px; }
    .table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
    .tbl { width:100%; border-collapse:collapse; min-width:1180px; }
    .tbl th { text-align:left; border-bottom:1px solid var(--line); background:#f5f9ff; color:#2b4d74; font-size:12px; text-transform:uppercase; padding:10px; }
    .tbl td { border-bottom:1px solid #edf3f9; padding:10px; font-size:13px; vertical-align:top; }
</style>

<div class="rg-page">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif

    <section class="card">
        <h3 style="margin:0 0 8px;">Etkinlik Kayıtlari: {{ $event->title_tr }}</h3>
        <div class="tabs">
            <a class="tab active" href="/mktg-admin/events/{{ $event->id }}/registrations">Kayıtlar</a>
            <a class="tab" href="/mktg-admin/events/{{ $event->id }}/report">Rapor</a>
            <a class="tab" href="/mktg-admin/events/{{ $event->id }}/survey-results">Anket</a>
            <a class="tab" href="/mktg-admin/events">Etkinlikler</a>
        </div>
    </section>

    <section class="card">
        <div class="toolbar">
            <form method="GET" action="/mktg-admin/events/{{ $event->id }}/registrations">
                <select name="status">
                    @php $regStatusLabels = ['attended'=>'Katıldı','no_show'=>'Gelmedi','cancelled'=>'İptal','registered'=>'Kayıtlı','waitlisted'=>'Beklemede']; @endphp
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tüm durumlar</option>
                    @foreach(($statusOptions ?? []) as $st)
                        <option value="{{ $st }}" @selected(($filters['status'] ?? 'all') === $st)>{{ $regStatusLabels[$st] ?? ucfirst($st) }}</option>
                    @endforeach
                </select>
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="email/isim/id ara">
                <button class="btn btn-primary" type="submit">Filtrele</button>
                <a class="btn btn-muted" href="/mktg-admin/events/{{ $event->id }}/registrations">Temizle</a>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>Email/Phone</th>
                    <th>Role/ID</th>
                    <th>Status</th>
                    <th>Survey</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($rows ?? []) as $row)
                    <tr>
                        <td>#{{ $row->id }}</td>
                        <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                        <td>{{ $row->email }}<br><span class="muted">{{ $row->phone ?: '-' }}</span></td>
                        <td>{{ $row->role ?: '-' }}<br><span class="muted">{{ $row->mentorde_id ?: '-' }}</span></td>
                        @php $regStatusLbl = ['attended'=>'Katıldı','no_show'=>'Gelmedi','cancelled'=>'İptal','registered'=>'Kayıtlı','waitlisted'=>'Beklemede'][$row->status] ?? ucfirst((string)($row->status ?? '–')); @endphp
                        <td>{{ $regStatusLbl }}</td>
                        <td>{{ $row->survey_completed ? 'Tamamlandı' : 'Yok' }} | puan: {{ $row->survey_score ?: '-' }}</td>
                        <td>
                            <form method="POST" action="/mktg-admin/events/{{ $event->id }}/registrations/{{ $row->id }}/status" style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                @csrf
                                @method('PUT')
                                <select name="status" style="min-width:140px;">
                                    @foreach(($statusOptions ?? []) as $st)
                                        <option value="{{ $st }}" @selected($row->status === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                                <input type="number" min="1" max="10" name="survey_score" value="{{ $row->survey_score ?: '' }}" placeholder="score">
                                <button class="btn btn-primary" type="submit">Kaydet</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">{{ $rows->links() }}</div>
    </section>

    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Etkinlik Kayıtları</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">👥 Katılımcı Yönetimi</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><strong>registered:</strong> Kayıt yapıldı, etkinliğe gelmedi (henüz)</li>
                    <li><strong>attended:</strong> Etkinliğe katıldı → devam oranına sayılır</li>
                    <li><strong>no_show:</strong> Gelmeyen → takip kampanyasına alınabilir</li>
                    <li>Anket skoru girilen kayıtlar Anket Sonuçları raporuna yansır</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 Raporlama</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Toplam kayıt / katılım oranı etkinlik başarısını ölçer</li>
                    <li>No-show oranı yüksekse hatırlatma e-postası planla</li>
                    <li>CSV Export → etkinlik sonrası takip e-posta listesi oluştur</li>
                </ul>
            </div>
        </div>
    </details>
</div>
@endsection


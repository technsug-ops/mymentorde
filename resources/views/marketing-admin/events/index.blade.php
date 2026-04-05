@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn" style="font-size:var(--tx-xs);padding:6px 12px;background:var(--u-brand,#1e40af);color:#fff;border-color:transparent;" href="/mktg-admin/events">Events</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/notifications">Notifications</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/campaigns">Campaigns</a>
@endsection

@section('title', 'Etkinlikler')
@section('page_subtitle', 'Etkinlik Yönetimi — webinar, fuar, eğitim ve kayıt takibi')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }

/* Stats bar */
.ev-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.ev-stat  { flex:1; padding:10px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.ev-stat:last-child { border-right:none; }
.ev-val   { font-size:20px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.ev-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

/* 2-col layout */
.ev-grid { display:grid; grid-template-columns:1fr 1.2fr; gap:12px; }
@media(max-width:1100px){ .ev-grid { grid-template-columns:1fr; } }

/* Form inputs */
.fm-row   { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px; }
.fm-row-1 { margin-bottom:8px; }
.fm-row input, .fm-row select, .fm-row textarea,
.fm-row-1 input, .fm-row-1 select, .fm-row-1 textarea {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.fm-row textarea, .fm-row-1 textarea { height:76px; padding:8px 10px; resize:vertical; }
.fm-row input:focus, .fm-row select:focus, .fm-row textarea:focus,
.fm-row-1 input:focus, .fm-row-1 select:focus, .fm-row-1 textarea:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}
.fm-row input[type=datetime-local] { padding:0 8px; }

/* Filter bar */
.fl-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:8px 0 4px; }
.fl-bar input, .fl-bar select {
    height:34px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0);
    border-radius:8px; background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:12px; outline:none; min-width:110px; appearance:auto;
}
.fl-bar input:focus, .fl-bar select:focus { border-color:var(--u-brand,#1e40af); }

/* Table */
.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; margin-top:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:900px; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }
.tl-acts { display:flex; gap:4px; flex-wrap:wrap; }

/* Details guide */
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

/* Alerts */
.flash   { border:1px solid var(--u-ok,#16a34a); background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,#fff); color:var(--u-ok,#16a34a); border-radius:10px; padding:10px 14px; font-size:13px; }
.err-box { border:1px solid var(--u-danger,#dc2626); background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,#fff); color:var(--u-danger,#dc2626); border-radius:10px; padding:10px 14px; font-size:13px; }
</style>

<div style="display:grid;gap:12px;">
    @if(session('status')) <div class="flash">{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="err-box">@foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach</div>
    @endif

    {{-- KPI bar --}}
    <div class="ev-stats">
        <div class="ev-stat"><div class="ev-val">{{ $stats['total'] ?? 0 }}</div><div class="ev-lbl">Toplam</div></div>
        <div class="ev-stat"><div class="ev-val">{{ $stats['published'] ?? 0 }}</div><div class="ev-lbl">Published</div></div>
        <div class="ev-stat"><div class="ev-val">{{ $stats['draft'] ?? 0 }}</div><div class="ev-lbl">Draft</div></div>
        <div class="ev-stat"><div class="ev-val">{{ $stats['upcoming'] ?? 0 }}</div><div class="ev-lbl">Upcoming</div></div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Etkinlikler</h3>
            <span class="det-chev">▼</span>
        </summary>
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 14px;line-height:1.6;">
            Webinar, bilgi günü, fuar veya eğitim gibi etkinlikleri oluşturun; katılımcı kayıtlarını yönetin ve performansı takip edin.
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div style="display:flex;flex-direction:column;gap:8px;font-size:var(--tx-xs);line-height:1.5;">
                @foreach(['Etkinlik oluştur — Başlık, tür, tarih, konum ve kontenjan belirle.','Yayınla — Draft olarak kaydet, hazırsa Published yap; kayıt formu otomatik aktif olur.','Katılımcı yönet — Kayıt listesini gör, katılım durumlarını güncelle.','Bildirim gönder — Notifications sekmesinden kayıtlı katılımcılara hatırlatma gönder.','Rapor al — Etkinlik sonrası katılım oranını ve anket sonuçlarını raporla.'] as $i => $step)
                <div style="display:flex;gap:8px;align-items:flex-start;">
                    <span style="background:var(--u-brand,#1e40af);color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:var(--tx-xs);font-weight:700;flex-shrink:0;">{{ $i+1 }}</span>
                    <span>{{ $step }}</span>
                </div>
                @endforeach
            </div>
            <div>
                <div style="font-size:var(--tx-xs);font-weight:600;margin-bottom:8px;">Etkinlik Durumları</div>
                <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;overflow:hidden;font-size:var(--tx-xs);">
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:center;"><span class="badge">Draft</span><span style="color:var(--u-muted);">Taslak — henüz yayınlanmadı</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:center;"><span class="badge ok">Published</span><span style="color:var(--u-muted);">Yayında — kayıtlar açık</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:center;"><span class="badge info">Completed</span><span style="color:var(--u-muted);">Tamamlandı — etkinlik bitti</span></div>
                    <div style="display:flex;gap:8px;padding:8px 10px;align-items:center;"><span class="badge danger">Cancelled</span><span style="color:var(--u-muted);">İptal edildi</span></div>
                </div>
                <div style="margin-top:10px;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                    💡 Kontenjan dolduğunda sistem otomatik olarak yeni kayıtları kapatır.
                </div>
            </div>
        </div>
    </details>

    {{-- 2-col: form + list --}}
    <div class="ev-grid">

        {{-- Form --}}
        @php
            $isEdit = !empty($editing);
            $action = $isEdit ? '/mktg-admin/events/'.$editing->id : '/mktg-admin/events';
            $targetTypesOld = old('target_student_types', $isEdit ? implode(',', (array) ($editing->target_student_types ?? [])) : '');
            $galleryOld     = old('gallery_urls',          $isEdit ? implode(',', (array) ($editing->gallery_urls          ?? [])) : '');
            $remindersOld   = old('reminders_json',        $isEdit ? json_encode((array) ($editing->reminders ?? []), JSON_UNESCAPED_UNICODE) : '[{"minutesBefore":1440,"channel":"email"},{"minutesBefore":60,"channel":"email"}]');
        @endphp
        <details class="card" {{ $isEdit ? 'open' : '' }}>
            <summary class="det-sum">
                <h3>{{ $isEdit ? '✏️ Etkinlik Düzenle #'.$editing->id : '+ Yeni Etkinlik' }}</h3>
                <span class="det-chev">▼</span>
            </summary>
            <form method="POST" action="{{ $action }}" style="margin-top:12px;">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="fm-row">
                    <input name="title_tr" placeholder="Başlık TR" value="{{ old('title_tr', $editing->title_tr ?? '') }}" required>
                    <select name="status">
                        @foreach(($statusOptions ?? []) as $st)
                            <option value="{{ $st }}" @selected(old('status', $editing->status ?? 'draft') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-row">
                    <select name="type">
                        @foreach(($typeOptions ?? []) as $tp)
                            <option value="{{ $tp }}" @selected(old('type', $editing->type ?? 'webinar') === $tp)>{{ $tp }}</option>
                        @endforeach
                    </select>
                    <select name="format">
                        @foreach(($formatOptions ?? []) as $fm)
                            <option value="{{ $fm }}" @selected(old('format', $editing->format ?? 'online') === $fm)>{{ $fm }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-row">
                    <input type="datetime-local" name="start_date" value="{{ old('start_date', !empty($editing->start_date) ? \Illuminate\Support\Carbon::parse($editing->start_date)->format('Y-m-d\TH:i') : '') }}" required>
                    <input type="datetime-local" name="end_date"   value="{{ old('end_date',   !empty($editing->end_date)   ? \Illuminate\Support\Carbon::parse($editing->end_date)->format('Y-m-d\TH:i')   : '') }}">
                </div>
                <div class="fm-row">
                    <input name="timezone" value="{{ old('timezone', $editing->timezone ?? 'Europe/Berlin') }}" placeholder="Timezone">
                    <input type="number" name="capacity" min="1" max="20000" value="{{ old('capacity', $editing->capacity ?? '') }}" placeholder="Kapasite">
                </div>
                <div class="fm-row">
                    <input name="venue_city" placeholder="Şehir (opsiyonel)" value="{{ old('venue_city', $editing->venue_city ?? '') }}">
                    <select name="linked_campaign_id">
                        <option value="">Bağlı Kampanya (opsiyonel)</option>
                        @foreach(($campaignOptions ?? []) as $cmp)
                            <option value="{{ $cmp->id }}" @selected((string) old('linked_campaign_id', $editing->linked_campaign_id ?? '') === (string) $cmp->id)>#{{ $cmp->id }} {{ $cmp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-row">
                    <input name="online_meeting_url" placeholder="Online meeting URL (opsiyonel)" value="{{ old('online_meeting_url', $editing->online_meeting_url ?? '') }}">
                </div>
                <div class="fm-row-1">
                    <textarea name="description_tr" placeholder="Açıklama TR" required>{{ old('description_tr', $editing->description_tr ?? '') }}</textarea>
                </div>

                {{-- Gelişmiş Ayarlar --}}
                <details style="margin-top:8px;" {{ $isEdit ? 'open' : '' }}>
                    <summary style="cursor:pointer;font-size:12px;font-weight:700;color:var(--u-muted,#64748b);padding:4px 0;list-style:none;display:flex;align-items:center;gap:6px;user-select:none;">
                        <span style="display:inline-block;transition:transform .2s;" class="adv-chev">▶</span> Gelişmiş Ayarlar
                    </summary>
                    <div style="margin-top:8px;display:grid;gap:8px;padding:10px;border:1px dashed var(--u-line,#e2e8f0);border-radius:8px;">
                        <div class="fm-row">
                            <input name="post_event_survey_url" placeholder="Survey URL (opsiyonel)" value="{{ old('post_event_survey_url', $editing->post_event_survey_url ?? '') }}">
                            <input name="target_student_types" placeholder="Hedef tipler (virgüllü)" value="{{ $targetTypesOld }}">
                        </div>
                        <div class="fm-row">
                            <input name="gallery_urls" placeholder="Galeri URL (virgüllü)" value="{{ $galleryOld }}">
                            <select name="waitlist_enabled">
                                <option value="0" @selected((string) old('waitlist_enabled', isset($editing) ? (int) $editing->waitlist_enabled : 0) === '0')>Waitlist: Hayır</option>
                                <option value="1" @selected((string) old('waitlist_enabled', isset($editing) ? (int) $editing->waitlist_enabled : 0) === '1')>Waitlist: Evet</option>
                            </select>
                        </div>
                        <div class="fm-row">
                            <select name="post_event_survey_enabled">
                                <option value="0" @selected((string) old('post_event_survey_enabled', isset($editing) ? (int) $editing->post_event_survey_enabled : 0) === '0')>Survey: Pasif</option>
                                <option value="1" @selected((string) old('post_event_survey_enabled', isset($editing) ? (int) $editing->post_event_survey_enabled : 0) === '1')>Survey: Aktif</option>
                            </select>
                            <div></div>
                        </div>
                        <div style="margin-bottom:0;">
                            <textarea name="reminders_json" placeholder='Hatırlatma JSON (örn: [{"minutesBefore":60,"channel":"email"}])' style="width:100%;box-sizing:border-box;height:64px;padding:8px 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:13px;outline:none;resize:vertical;">{{ $remindersOld }}</textarea>
                        </div>
                    </div>
                </details>
                <script>
                document.querySelectorAll('details').forEach(function(d) {
                    d.addEventListener('toggle', function() {
                        var chev = d.querySelector('.adv-chev');
                        if (chev) chev.style.transform = d.open ? 'rotate(90deg)' : 'rotate(0deg)';
                    });
                });
                </script>

                <div style="display:flex;gap:8px;margin-top:6px;">
                    <button type="submit" class="btn ok">{{ $isEdit ? 'Etkinlik Güncelle' : 'Etkinlik Ekle' }}</button>
                    <a href="/mktg-admin/events" class="btn alt">Temizle</a>
                </div>
            </form>
        </details>

        {{-- Liste --}}
        <article class="card" style="min-width:0;">
            <h3 style="margin:0 0 2px;font-size:var(--tx-sm);font-weight:700;">Etkinlik Listesi</h3>
            <form method="GET" action="/mktg-admin/events">
                <div class="fl-bar">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ara…" style="flex:1;min-width:100px;">
                    <select name="status">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tüm durumlar</option>
                        @foreach(($statusOptions ?? []) as $st)
                            <option value="{{ $st }}" @selected(($filters['status'] ?? 'all') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    <select name="type">
                        <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>Tüm tipler</option>
                        @foreach(($typeOptions ?? []) as $tp)
                            <option value="{{ $tp }}" @selected(($filters['type'] ?? 'all') === $tp)>{{ $tp }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                    <a href="/mktg-admin/events" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 14px;display:flex;align-items:center;">Temizle</a>
                </div>
            </form>

            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>ID</th><th>Etkinlik</th><th>Tip</th><th>Tarih</th><th>Durum</th><th>Kayıt</th><th>İşlem</th>
                    </tr></thead>
                    <tbody>
                    @forelse(($rows ?? []) as $row)
                        @php
                            $stBadge = match($row->status) {
                                'published' => 'ok',
                                'completed' => 'info',
                                'cancelled' => 'danger',
                                default     => '',
                            };
                            $stLabel = ['draft'=>'Taslak','published'=>'Yayında','cancelled'=>'İptal','completed'=>'Tamamlandı'][$row->status] ?? ucfirst($row->status);
                        @endphp
                        <tr>
                            <td style="color:var(--u-muted);font-size:var(--tx-xs);font-family:ui-monospace,monospace;">#{{ $row->id }}</td>
                            <td>
                                <strong>{{ $row->title_tr }}</strong><br>
                                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row->venue_city ?: '—' }}</span>
                            </td>
                            <td style="font-size:var(--tx-xs);">{{ $row->type }}<br><span style="color:var(--u-muted);">{{ $row->format }}</span></td>
                            <td style="font-size:var(--tx-xs);">{{ $row->start_date }}<br><span style="color:var(--u-muted);">{{ $row->end_date ?: '—' }}</span></td>
                            <td><span class="badge {{ $stBadge }}">{{ $stLabel }}</span></td>
                            <td style="font-size:var(--tx-xs);">{{ (int) $row->current_registrations }}<span style="color:var(--u-muted);">/{{ $row->capacity ?: '∞' }}</span></td>
                            <td>
                                <div class="tl-acts">
                                    <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" href="/mktg-admin/events?edit_id={{ $row->id }}">Düzenle</a>
                                    <form method="POST" action="/mktg-admin/events/{{ $row->id }}/publish" style="display:inline;">@csrf @method('PUT')<button class="btn ok" style="font-size:var(--tx-xs);padding:4px 8px;" type="submit">Yayınla</button></form>
                                    <form method="POST" action="/mktg-admin/events/{{ $row->id }}/cancel" style="display:inline;">@csrf @method('PUT')<button class="btn warn" style="font-size:var(--tx-xs);padding:4px 8px;" type="submit">İptal</button></form>
                                    <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" href="/mktg-admin/events/{{ $row->id }}/registrations">Kayıtlar</a>
                                    <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" href="/mktg-admin/events/{{ $row->id }}/report">Rapor</a>
                                    <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 8px;" href="/mktg-admin/events/{{ $row->id }}/survey-results">Anket</a>
                                    <form method="POST" action="/mktg-admin/events/{{ $row->id }}/send-reminder" style="display:inline;">@csrf<button class="btn" style="font-size:var(--tx-xs);padding:4px 8px;" type="submit">Hatırlat</button></form>
                                    <form method="POST" action="/mktg-admin/events/{{ $row->id }}" style="display:inline;">@csrf @method('DELETE')<button class="btn warn" style="font-size:var(--tx-xs);padding:4px 8px;" type="submit" onclick="return confirm('Etkinlik silinsin mi?')">Sil</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Etkinlik kaydı yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">{{ $rows->links() }}</div>
        </article>
    </div>
</div>
@endsection

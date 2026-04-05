@extends('senior.layouts.app')

@section('title', 'Öğrenci 360° — ' . ($guest ? trim($guest->first_name . ' ' . $guest->last_name) : $studentId))
@section('page_title', 'Öğrenci 360°')

@push('head')
<style>
.s360-hero { background:linear-gradient(to right,#3b1a6e,#7c3aed); border-radius:14px; padding:20px 24px; color:#fff; margin-bottom:16px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.s360-avatar { width:56px; height:56px; border-radius:50%; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:700; flex-shrink:0; }
.s360-meta { flex:1; min-width:160px; }
.s360-name { font-size:18px; font-weight:700; line-height:1.2; }
.s360-sub  { font-size:13px; color:rgba(255,255,255,.7); margin-top:4px; }
.s360-progress { background:rgba(255,255,255,.12); border-radius:10px; padding:12px 16px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.s360-pbar-wrap { flex:1; min-width:120px; }
.s360-pbar-track { height:8px; background:rgba(255,255,255,.25); border-radius:4px; overflow:hidden; margin-top:4px; }
.s360-pbar-fill  { height:8px; border-radius:4px; background:#4ade80; transition:width .4s; }

.s360-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:14px; border-bottom:1.5px solid var(--u-line,#e5e7eb); padding-bottom:6px; }
.s360-tab { padding:6px 14px; border-radius:8px 8px 0 0; font-size:13px; font-weight:600; cursor:pointer; border:none; background:transparent; color:#6b7280; transition:background .15s, color .15s; }
.s360-tab.active { background:#7c3aed; color:#fff; }
.s360-tab:hover:not(.active) { background:#f5f3ff; color:#6d28d9; }
.s360-pane { display:none; }
.s360-pane.active { display:block; }
</style>
@endpush

@section('content')
@php
    $initials = strtoupper(substr(str_replace('-','',preg_replace('/[^A-Za-z0-9]/','', $studentId)), 0, 2));
    $guestName = $guest ? trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')) : $studentId;
@endphp

{{-- Hero --}}
<div class="s360-hero">
    <div class="s360-avatar">{{ $initials }}</div>
    <div class="s360-meta">
        <div class="s360-name">{{ $guestName ?: $studentId }}</div>
        <div class="s360-sub">
            {{ $studentId }}
            @if($guest?->email) · {{ $guest->email }} @endif
            @if($assignment?->branch) · {{ $assignment->branch }} @endif
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
            @if($assignment?->risk_level)
                <span class="badge {{ in_array($assignment->risk_level, ['high','critical']) ? 'danger' : 'warn' }}">risk: {{ $assignment->risk_level }}</span>
            @endif
            @if($assignment?->payment_status)
                <span class="badge {{ $assignment->payment_status === 'paid' ? 'ok' : 'pending' }}">{{ $assignment->payment_status }}</span>
            @endif
            @if($assignment?->is_archived)
                <span class="badge danger">Arşiv</span>
            @else
                <span class="badge ok">Aktif</span>
            @endif
        </div>
    </div>
    <div class="s360-progress">
        <div class="s360-pbar-wrap">
            <div style="font-size:var(--tx-xs);color:rgba(255,255,255,.8);font-weight:600;">Genel İlerleme: {{ $progress['percent'] }}%</div>
            <div class="s360-pbar-track"><div class="s360-pbar-fill" style="width:{{ $progress['percent'] }}%"></div></div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @foreach($progress['steps'] as $step)
            <div style="text-align:center;font-size:var(--tx-xs);">
                <div style="width:28px;height:28px;border-radius:50%;border:2px solid {{ $step['done'] ? '#4ade80' : 'rgba(255,255,255,.3)' }};display:flex;align-items:center;justify-content:center;font-size:var(--tx-sm);margin:0 auto 2px;">{{ $step['done'] ? '✓' : '○' }}</div>
                <div style="color:rgba(255,255,255,.65);max-width:60px;line-height:1.2;">{{ $step['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<a class="btn" href="/senior/students" style="margin-bottom:14px;display:inline-block;">← Öğrenciler</a>

{{-- Tabs --}}
<div class="s360-tabs">
    <button class="s360-tab active" onclick="s360Tab('profil',this)">👤 Profil</button>
    <button class="s360-tab" onclick="s360Tab('belgeler',this)">📁 Belgeler ({{ $documents->count() }})</button>
    <button class="s360-tab" onclick="s360Tab('outcomes',this)">🔄 Süreç ({{ $outcomes->count() }})</button>
    <button class="s360-tab" onclick="s360Tab('randevular',this)">📅 Randevular ({{ $appointments->count() }})</button>
    <button class="s360-tab" onclick="s360Tab('tickets',this)">🎫 Tickets ({{ $tickets->count() }})</button>
    <button class="s360-tab" onclick="s360Tab('notlar',this)">📝 Notlar ({{ $notes->count() }})</button>
    <button class="s360-tab" onclick="s360Tab('universite',this)">🎓 Üniversite ({{ $uniApps->count() }})</button>
    <button class="s360-tab" onclick="s360Tab('gelen',this)">📬 Gelen Bel. ({{ $instDocs->count() }})</button>
    <button class="s360-tab" onclick="s360Tab('vize',this)">🛂 Vize</button>
    <button class="s360-tab" onclick="s360Tab('konut',this)">🏠 Konut</button>
</div>

{{-- Profil --}}
<div class="s360-pane active" id="pane-profil">
@if($guest)
<div class="grid2">
    <article class="panel">
        <h3 style="margin:0 0 12px;">Kişisel Bilgiler</h3>
        <div class="list">
            @foreach([
                ['Ad Soyad', trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? ''))],
                ['E-posta', $guest->email ?? '-'],
                ['Telefon', $guest->phone ?? '-'],
                ['Uyruk', $guest->nationality ?? '-'],
                ['Doğum Tarihi', $guest->birth_date ?? '-'],
                ['Pasaport No', $guest->passport_number ?? '-'],
            ] as [$lbl, $val])
            <div class="item" style="justify-content:space-between;">
                <span class="muted" style="font-size:var(--tx-xs);">{{ $lbl }}</span>
                <span style="font-size:var(--tx-sm);font-weight:600;">{{ $val }}</span>
            </div>
            @endforeach
        </div>
    </article>
    <article class="panel">
        <h3 style="margin:0 0 12px;">Başvuru Bilgileri</h3>
        <div class="list">
            @foreach([
                ['Sözleşme Durumu', $guest->contract_status ?? '-'],
                ['Başvuru Durumu', $guest->application_status ?? '-'],
                ['Atanan Danışman', $assignment?->senior_email ?? '-'],
                ['Bayi', $assignment?->dealer_id ?? '-'],
                ['Şube', $assignment?->branch ?? '-'],
                ['Kayıt Tarihi', $guest->created_at?->format('d.m.Y') ?? '-'],
            ] as [$lbl, $val])
            <div class="item" style="justify-content:space-between;">
                <span class="muted" style="font-size:var(--tx-xs);">{{ $lbl }}</span>
                <span style="font-size:var(--tx-sm);font-weight:600;">{{ $val }}</span>
            </div>
            @endforeach
        </div>
    </article>
</div>
@else
<div class="panel muted">Bu öğrenci için guest profili bulunamadı.</div>
@endif
</div>

{{-- Belgeler --}}
<div class="s360-pane" id="pane-belgeler">
<article class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h3 style="margin:0;">Belgeler</h3>
        <a class="btn" href="/senior/registration-documents?student={{ $studentId }}">Tüm Belgeler</a>
    </div>
    @forelse($documents as $doc)
    <div class="item" style="justify-content:space-between;flex-wrap:wrap;gap:4px;">
        <div>
            <span style="font-size:var(--tx-sm);font-weight:600;">{{ $doc->original_file_name }}</span>
            <div style="margin-top:3px;">
                <span class="badge {{ $doc->status === 'approved' ? 'ok' : ($doc->status === 'rejected' ? 'danger' : 'warn') }}">{{ $doc->status }}</span>
                @if($doc->category)<span class="badge info">{{ $doc->category->name ?? $doc->category_id }}</span>@endif
            </div>
        </div>
        <span class="muted" style="font-size:var(--tx-xs);">{{ $doc->created_at?->format('d.m.Y') }}</span>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">Belge kaydı yok.</div>
    @endforelse
</article>
</div>

{{-- Outcomes --}}
<div class="s360-pane" id="pane-outcomes">
<article class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h3 style="margin:0;">Süreç Çıktıları</h3>
        <a class="btn" href="/senior/process-tracking?student={{ $studentId }}">Süreç Takibi</a>
    </div>
    @forelse($outcomes as $o)
    <div class="item" style="justify-content:space-between;flex-wrap:wrap;gap:4px;">
        <div>
            <span class="badge info">{{ $o->process_step }}</span>
            <span class="badge">{{ $o->outcome_type }}</span>
            @if($o->details_tr)
            <div class="muted" style="font-size:var(--tx-xs);margin-top:3px;">{{ \Illuminate\Support\Str::limit((string) $o->details_tr, 80) }}</div>
            @endif
        </div>
        <span class="muted" style="font-size:var(--tx-xs);">{{ $o->created_at?->format('d.m.Y H:i') }}</span>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">Outcome kaydı yok.</div>
    @endforelse
</article>
</div>

{{-- Randevular --}}
<div class="s360-pane" id="pane-randevular">
<article class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h3 style="margin:0;">Randevular</h3>
        <a class="btn" href="/senior/appointments">Randevu Yönetimi</a>
    </div>
    @forelse($appointments as $apt)
    <div class="item" style="justify-content:space-between;flex-wrap:wrap;gap:4px;">
        <div>
            <span style="font-size:var(--tx-sm);font-weight:600;">{{ $apt->appointment_date?->format('d.m.Y H:i') }}</span>
            <div style="margin-top:3px;">
                <span class="badge">{{ $apt->type ?? 'randevu' }}</span>
                <span class="badge {{ $apt->status === 'confirmed' ? 'ok' : ($apt->status === 'cancelled' ? 'danger' : 'warn') }}">{{ $apt->status }}</span>
            </div>
            @if($apt->notes)<div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">{{ \Illuminate\Support\Str::limit((string)$apt->notes, 80) }}</div>@endif
        </div>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">Randevu kaydı yok.</div>
    @endforelse
</article>
</div>

{{-- Tickets --}}
<div class="s360-pane" id="pane-tickets">
<article class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h3 style="margin:0;">Destek Talepleri</h3>
        <a class="btn" href="/senior/tickets">Tüm Ticketlar</a>
    </div>
    @forelse($tickets as $t)
    <div class="item" style="justify-content:space-between;flex-wrap:wrap;gap:4px;">
        <div>
            <span style="font-size:var(--tx-sm);font-weight:600;">{{ \Illuminate\Support\Str::limit($t->subject, 60) }}</span>
            <div style="margin-top:3px;">
                <span class="badge {{ $t->status === 'open' ? 'warn' : ($t->status === 'resolved' ? 'ok' : '') }}">{{ $t->status }}</span>
                @if($t->priority)<span class="badge {{ $t->priority === 'urgent' ? 'danger' : 'info' }}">{{ $t->priority }}</span>@endif
                @if($t->department)<span class="badge">{{ $t->department }}</span>@endif
            </div>
        </div>
        <span class="muted" style="font-size:var(--tx-xs);">{{ $t->created_at?->format('d.m.Y') }}</span>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">Ticket kaydı yok.</div>
    @endforelse
</article>
</div>

{{-- Notlar --}}
<div class="s360-pane" id="pane-notlar">
<article class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h3 style="margin:0;">Gizli Notlar</h3>
        <a class="btn" href="/senior/notes?student={{ $studentId }}">Not Yönetimi</a>
    </div>
    @forelse($notes as $note)
    <div class="item" style="justify-content:space-between;flex-wrap:wrap;gap:4px;">
        <div style="flex:1;">
            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                <span class="badge">{{ $note->category }}</span>
                <span class="badge {{ $note->priority === 'high' ? 'danger' : ($note->priority === 'medium' ? 'warn' : '') }}">{{ $note->priority }}</span>
                @if($note->is_pinned)<span class="badge ok">📌 pinned</span>@endif
            </div>
            @if($note->content)
            <div class="muted" style="font-size:var(--tx-xs);margin-top:4px;">{{ \Illuminate\Support\Str::limit((string)$note->content, 120) }}</div>
            @endif
        </div>
        <span class="muted" style="font-size:var(--tx-xs);flex-shrink:0;">{{ $note->created_at?->format('d.m.Y') }}</span>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">Not kaydı yok.</div>
    @endforelse
</article>
</div>

{{-- Üniversite --}}
<div class="s360-pane" id="pane-universite">
<article class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h3 style="margin:0;">Üniversite Başvuruları</h3>
        <a class="btn" href="/senior/university-applications?student={{ $studentId }}">Yönet</a>
    </div>
    @forelse($uniApps as $ua)
    <div class="item" style="justify-content:space-between;flex-wrap:wrap;gap:4px;">
        <div>
            <span style="font-size:var(--tx-sm);font-weight:600;">{{ $ua->university_name ?? $ua->university_code }}</span>
            @if($ua->department_name)<span class="muted"> / {{ $ua->department_name }}</span>@endif
            <div style="margin-top:3px;display:flex;gap:4px;flex-wrap:wrap;">
                <span class="badge {{ in_array($ua->status, ['accepted','conditional_accepted']) ? 'ok' : ($ua->status === 'rejected' ? 'danger' : 'info') }}">{{ $ua->status }}</span>
                @if($ua->degree_type)<span class="badge">{{ $ua->degree_type }}</span>@endif
                @if($ua->deadline)<span class="muted" style="font-size:var(--tx-xs);">Son: {{ \Carbon\Carbon::parse($ua->deadline)->format('d.m.Y') }}</span>@endif
            </div>
        </div>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">Üniversite başvurusu yok.</div>
    @endforelse
</article>
</div>

{{-- Gelen Belgeler --}}
<div class="s360-pane" id="pane-gelen">
<article class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h3 style="margin:0;">Kurumsal Gelen Belgeler</h3>
        <a class="btn" href="/senior/institution-documents?student={{ $studentId }}">Yönet</a>
    </div>
    @forelse($instDocs as $doc)
    <div class="item" style="justify-content:space-between;flex-wrap:wrap;gap:4px;">
        <div>
            <span style="font-size:var(--tx-sm);font-weight:600;">{{ $doc->document_code ?? '-' }}</span>
            @if($doc->notes)<div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">{{ \Illuminate\Support\Str::limit((string)$doc->notes, 60) }}</div>@endif
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            @if($doc->is_visible_to_student)<span class="badge ok">Öğrenci görür</span>@else<span class="badge">Gizli</span>@endif
            <span class="muted" style="font-size:var(--tx-xs);">{{ $doc->created_at?->format('d.m.Y') }}</span>
        </div>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">Gelen belge kaydı yok.</div>
    @endforelse
</article>
</div>

{{-- Vize --}}
<div class="s360-pane" id="pane-vize">
<article class="panel">
    <h3 style="margin:0 0 14px;">🛂 Vize Başvurusu</h3>

    @if($visa)
    {{-- Mevcut kayıt: durum özeti --}}
    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--u-bg);border-radius:10px;margin-bottom:14px;">
        <div style="font-size:var(--tx-2xl);">{{ $visa->status === 'approved' ? '✅' : ($visa->status === 'rejected' ? '❌' : '🛂') }}</div>
        <div>
            <div style="font-weight:700;font-size:var(--tx-sm);">{{ \App\Models\StudentVisaApplication::VISA_TYPE_LABELS[$visa->visa_type] ?? $visa->visa_type }}</div>
            <div style="margin-top:3px;">
                <span class="badge {{ $visa->statusBadge() }}">{{ $visa->statusLabel() }}</span>
                @if($visa->consulate_city) <span class="muted" style="font-size:var(--tx-xs);">· {{ $visa->consulate_city }}</span>@endif
                @if($visa->appointment_date) <span class="muted" style="font-size:var(--tx-xs);">· Randevu: {{ $visa->appointment_date->format('d.m.Y') }}</span>@endif
            </div>
        </div>
    </div>

    {{-- Güncelle formu --}}
    <form method="POST" action="{{ route('senior.visa.update', $visa->id) }}">
        @csrf @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Durum</label>
                <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentVisaApplication::STATUS_LABELS as $v => $l)
                        <option value="{{ $v }}" {{ $visa->status === $v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Konsolosluk</label>
                <select name="consulate_city" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    <option value="">—</option>
                    @foreach(['İstanbul','Ankara','İzmir'] as $c)
                        <option value="{{ $c }}" {{ $visa->consulate_city === $c ? 'selected':'' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Randevu Tarihi</label>
                <input type="date" name="appointment_date" value="{{ $visa->appointment_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Karar Tarihi</label>
                <input type="date" name="decision_date" value="{{ $visa->decision_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Geçerli Başlangıç</label>
                <input type="date" name="valid_from" value="{{ $visa->valid_from?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Geçerli Bitiş</label>
                <input type="date" name="valid_until" value="{{ $visa->valid_until?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
        </div>
        <div style="margin-bottom:10px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:5px;">Sunulan Belgeler</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach(\App\Models\StudentVisaApplication::COMMON_DOCUMENTS as $key => $lbl)
                <label style="display:inline-flex;align-items:center;gap:4px;font-size:var(--tx-xs);cursor:pointer;">
                    <input type="checkbox" name="submitted_documents[]" value="{{ $key }}" {{ in_array($key, $visa->submitted_documents ?? []) ? 'checked':'' }}> {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>
        <textarea name="notes" rows="2" placeholder="Not..." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;margin-bottom:8px;">{{ $visa->notes }}</textarea>
        <div style="display:flex;align-items:center;gap:10px;">
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <input type="checkbox" name="is_visible_to_student" value="1" {{ $visa->is_visible_to_student ? 'checked':'' }}> Öğrenciye görünür
            </label>
            <button type="submit" style="background:#0891b2;color:#fff;border:none;border-radius:7px;padding:7px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Güncelle</button>
        </div>
    </form>

    @else
    {{-- Yeni kayıt formu --}}
    <form method="POST" action="{{ route('senior.visa.store') }}">
        @csrf
        <input type="hidden" name="student_id" value="{{ $studentId }}">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Vize Türü</label>
                <select name="visa_type" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentVisaApplication::VISA_TYPE_LABELS as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Durum</label>
                <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentVisaApplication::STATUS_LABELS as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Konsolosluk</label>
                <select name="consulate_city" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    <option value="">—</option>
                    @foreach(['İstanbul','Ankara','İzmir'] as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Randevu Tarihi</label>
                <input type="date" name="appointment_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
        </div>
        <textarea name="notes" rows="2" placeholder="Not..." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;margin-bottom:8px;"></textarea>
        <div style="display:flex;align-items:center;gap:10px;">
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <input type="checkbox" name="is_visible_to_student" value="1" checked> Öğrenciye görünür
            </label>
            <button type="submit" style="background:#0891b2;color:#fff;border:none;border-radius:7px;padding:7px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Vize Kaydı Ekle</button>
        </div>
    </form>
    @endif
</article>
</div>

{{-- Konut --}}
<div class="s360-pane" id="pane-konut">
<article class="panel">
    <h3 style="margin:0 0 14px;">🏠 Konut & Barınma</h3>

    @if($accommodation)
    {{-- Mevcut kayıt: durum özeti --}}
    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--u-bg);border-radius:10px;margin-bottom:14px;">
        <div style="font-size:var(--tx-2xl);">{{ $accommodation->booking_status === 'confirmed' ? '🏠' : ($accommodation->booking_status === 'searching' ? '🔍' : '🔑') }}</div>
        <div>
            <div style="font-weight:700;font-size:var(--tx-sm);">{{ \App\Models\StudentAccommodation::TYPE_LABELS[$accommodation->type] ?? $accommodation->type }}</div>
            <div style="margin-top:3px;">
                <span class="badge {{ $accommodation->statusBadge() }}">{{ $accommodation->statusLabel() }}</span>
                @if($accommodation->city) <span class="muted" style="font-size:var(--tx-xs);">· {{ $accommodation->city }}</span>@endif
                @if($accommodation->monthly_cost_eur) <span class="muted" style="font-size:var(--tx-xs);">· €{{ number_format($accommodation->monthly_cost_eur,0) }}/ay</span>@endif
            </div>
        </div>
    </div>

    {{-- Güncelle formu --}}
    <form method="POST" action="{{ route('senior.housing.update', $accommodation->id) }}">
        @csrf @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Durum</label>
                <select name="booking_status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentAccommodation::STATUS_LABELS as $v => $l)
                        <option value="{{ $v }}" {{ $accommodation->booking_status === $v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Konut Türü</label>
                <select name="type" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentAccommodation::TYPE_LABELS as $v => $l)
                        <option value="{{ $v }}" {{ $accommodation->type === $v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:span 2;">
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Adres</label>
                <input type="text" name="address" value="{{ $accommodation->address }}" placeholder="Straße 12, Wohnung 3" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Şehir</label>
                <input type="text" name="city" value="{{ $accommodation->city }}" placeholder="Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Aylık Kira (€)</label>
                <input type="number" name="monthly_cost_eur" value="{{ $accommodation->monthly_cost_eur }}" min="0" step="0.01" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Taşınma Tarihi</label>
                <input type="date" name="move_in_date" value="{{ $accommodation->move_in_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Ev Sahibi</label>
                <input type="text" name="landlord_name" value="{{ $accommodation->landlord_name }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Telefon</label>
                <input type="text" name="landlord_phone" value="{{ $accommodation->landlord_phone }}" placeholder="+49..." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
        </div>
        <textarea name="notes" rows="2" placeholder="Not..." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;margin-bottom:8px;">{{ $accommodation->notes }}</textarea>
        <div style="display:flex;align-items:center;gap:10px;">
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <input type="checkbox" name="utilities_included" value="1" {{ $accommodation->utilities_included ? 'checked':'' }}> Faturalar dahil
            </label>
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <input type="checkbox" name="is_visible_to_student" value="1" {{ $accommodation->is_visible_to_student ? 'checked':'' }}> Öğrenciye görünür
            </label>
            <button type="submit" style="background:#059669;color:#fff;border:none;border-radius:7px;padding:7px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Güncelle</button>
        </div>
    </form>

    @else
    {{-- Yeni kayıt formu --}}
    <form method="POST" action="{{ route('senior.housing.store') }}">
        @csrf
        <input type="hidden" name="student_id" value="{{ $studentId }}">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Konut Türü</label>
                <select name="type" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentAccommodation::TYPE_LABELS as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Durum</label>
                <select name="booking_status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentAccommodation::STATUS_LABELS as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:span 2;">
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Adres</label>
                <input type="text" name="address" placeholder="Straße 12, Wohnung 3" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Şehir</label>
                <input type="text" name="city" placeholder="Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Aylık Kira (€)</label>
                <input type="number" name="monthly_cost_eur" min="0" step="0.01" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Taşınma Tarihi</label>
                <input type="date" name="move_in_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <input type="checkbox" name="is_visible_to_student" value="1" checked> Öğrenciye görünür
            </label>
            <button type="submit" style="background:#059669;color:#fff;border:none;border-radius:7px;padding:7px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Konut Kaydı Ekle</button>
        </div>
    </form>
    @endif
</article>
</div>

@push('scripts')
<script>
function s360Tab(key, btn) {
    document.querySelectorAll('.s360-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.s360-tab').forEach(b => b.classList.remove('active'));
    const pane = document.getElementById('pane-' + key);
    if (pane) pane.classList.add('active');
    if (btn) btn.classList.add('active');
}
</script>
@endpush
@endsection

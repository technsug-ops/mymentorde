@extends('senior.layouts.app')

@section('title', 'Üniversite Başvuruları')
@section('page_title', 'Üniversite Başvuruları')

@push('head')
<style>
.ua-field label { display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:5px; text-transform:uppercase; letter-spacing:.04em; }
.ua-field select, .ua-field input[type=text] {
    display:block; width:100%; padding:8px 11px;
    border:2px solid #e2e8f0; border-radius:8px;
    background:#fff; color:#1e293b; font-size:13px; font-family:inherit;
    transition:border-color .15s;
}
.ua-field select:focus, .ua-field input:focus { outline:none; border-color:#7c3aed; }
.ua-filter-row { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.ua-filter-row .ua-field { min-width:160px; }
.ua-filter-row .ua-field.ua-q { min-width:220px; flex:1; }
</style>
@endpush

@section('content')

@if(session('status'))
    <div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- Filtreler --}}
<section class="panel" style="margin-bottom:16px;">
    <form method="GET" action="/senior/university-applications" class="ua-filter-row">
        <div class="ua-field">
            <label>Öğrenci</label>
            <select name="student_id">
                <option value="">Tüm Öğrenciler</option>
                @foreach($students as $s)
                    <option value="{{ $s->tracking_token }}" {{ request('student_id') === $s->tracking_token ? 'selected' : '' }}>
                        {{ $s->first_name }} {{ $s->last_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="ua-field">
            <label>Durum</label>
            <select name="status">
                <option value="">Tüm Durumlar</option>
                @foreach(\App\Models\StudentUniversityApplication::STATUS_LABELS as $val => $lbl)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="ua-field">
            <label>Derece</label>
            <select name="degree_type">
                <option value="">Tüm Dereceler</option>
                @foreach(\App\Models\StudentUniversityApplication::DEGREE_LABELS as $val => $lbl)
                    <option value="{{ $val }}" {{ request('degree_type') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="ua-field ua-q">
            <label>Üniversite / Bölüm / Şehir</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Ara...">
        </div>
        <div style="display:flex;gap:8px;padding-bottom:2px;">
            <button type="submit" class="btn">Filtrele</button>
            @if(request('student_id') || request('status') || request('degree_type') || request('q'))
                <a class="btn alt" href="/senior/university-applications">Temizle</a>
            @endif
        </div>
    </form>
</section>

{{-- Yeni Başvuru Ekle --}}
<details class="panel" style="margin-bottom:16px;">
    <summary style="cursor:pointer;font-weight:600;padding:4px 0;">+ Yeni Başvuru Ekle</summary>
    <form method="POST" action="/senior/university-applications" style="margin-top:14px;">
        @csrf
        <div class="grid2" style="gap:10px;">
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Öğrenci *</div>
                <select name="student_id" required style="width:100%;">
                    <option value="">Seçin...</option>
                    @foreach($students as $s)
                        <option value="{{ $s->tracking_token }}">{{ $s->first_name }} {{ $s->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Derece Türü *</div>
                <select name="degree_type" required style="width:100%;">
                    @foreach(\App\Models\StudentUniversityApplication::DEGREE_LABELS as $val => $lbl)
                        <option value="{{ $val }}" {{ $val === 'master' ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Üniversite Adı *</div>
                <input type="text" name="university_name" required placeholder="ör. Technische Universität Berlin" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Bölüm Adı *</div>
                <input type="text" name="department_name" required placeholder="ör. Maschinenbau (M.Sc.)" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Şehir</div>
                <input type="text" name="city" placeholder="ör. Berlin" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Eyalet</div>
                <input type="text" name="state" placeholder="ör. Bayern" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Dönem</div>
                <input type="text" name="semester" placeholder="WS2025/26" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Başvuru Portalı</div>
                <select name="application_portal" style="width:100%;">
                    <option value="">Seçin...</option>
                    @foreach(\App\Models\StudentUniversityApplication::PORTAL_LABELS as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Başvuru No. / Referans</div>
                <input type="text" name="application_number" placeholder="ör. UA-2025-XXXXXX" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Durum *</div>
                <select name="status" required style="width:100%;">
                    @foreach(\App\Models\StudentUniversityApplication::STATUS_LABELS as $val => $lbl)
                        <option value="{{ $val }}" {{ $val === 'planned' ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Öncelik (1=birinci tercih)</div>
                <input type="number" name="priority" value="1" min="1" max="99" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Son Başvuru Tarihi</div>
                <input type="date" name="deadline" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Gönderildiği Tarih</div>
                <input type="date" name="submitted_at" style="width:100%;">
            </div>
            <div>
                <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Sonuç Tarihi</div>
                <input type="date" name="result_at" style="width:100%;">
            </div>
        </div>
        <div style="margin-top:10px;">
            <div class="muted" style="font-size:var(--tx-xs);margin-bottom:3px;">Notlar</div>
            <textarea name="notes" rows="2" style="width:100%;" placeholder="İsteğe bağlı notlar..."></textarea>
        </div>
        <div style="margin-top:10px;">
            <button type="submit" class="btn ok">Kaydet</button>
        </div>
    </form>
</details>

{{-- Başvuru Listesi --}}
<section class="panel" style="margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h2 style="margin:0;">Başvurular ({{ $applications->total() }})</h2>
        <span class="muted" style="font-size:var(--tx-xs);">Öncelik sırasına göre</span>
    </div>

    @if($applications->isEmpty())
        <div class="muted" style="text-align:center;padding:20px 0;">Henüz başvuru kaydedilmedi.</div>
    @else
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:9px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;width:32px;">#</th>
                    <th style="padding:9px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Öğrenci</th>
                    <th style="padding:9px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Üniversite</th>
                    <th style="padding:9px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Bölüm · Derece</th>
                    <th style="padding:9px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Son Tarih</th>
                    <th style="padding:9px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Durum</th>
                    <th style="padding:9px 10px;text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
            @foreach($applications as $app)
                @php
                    $badgeClass  = \App\Models\StudentUniversityApplication::STATUS_BADGE[$app->status]  ?? 'info';
                    $statusLabel = \App\Models\StudentUniversityApplication::STATUS_LABELS[$app->status] ?? $app->status;
                    $degreeLabel = \App\Models\StudentUniversityApplication::DEGREE_LABELS[$app->degree_type] ?? $app->degree_type;
                    $hasReqMap   = false;
                    if ($app->university_code) {
                        $reqMap = \App\Models\UniversityRequirementMap::where('university_code', $app->university_code)
                            ->where(fn($q) => $q->where('department_code', $app->department_code)->orWhereNull('department_code'))
                            ->where('degree_type', $app->degree_type ?: 'master')
                            ->where(fn($q) => $q->where('semester', $app->semester ?: 'WS')->orWhere('semester','both'))
                            ->where('is_active', true)
                            ->orderByRaw("department_code IS NULL ASC")
                            ->first();
                        $hasReqMap   = !empty($reqMap);
                        $docCatalog  = config('university_application_documents.documents', []);
                    }
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;transition:background .1s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    {{-- # --}}
                    <td style="padding:10px 10px;white-space:nowrap;vertical-align:middle;">
                        <span style="font-size:11px;font-weight:700;background:#f3f7fe;border:1px solid #d6dfeb;border-radius:999px;padding:2px 7px;">#{{ $app->priority }}</span>
                    </td>
                    {{-- Öğrenci --}}
                    <td style="padding:10px 10px;vertical-align:middle;">
                        <div style="font-weight:600;font-size:13px;white-space:nowrap;">{{ $app->first_name }} {{ $app->last_name }}</div>
                        <div style="font-size:11px;color:#94a3b8;">{{ $app->student_id }}</div>
                    </td>
                    {{-- Üniversite --}}
                    <td style="padding:10px 10px;vertical-align:middle;">
                        <div style="font-weight:600;">{{ $app->university_name }}</div>
                        <div style="font-size:11px;color:#94a3b8;">
                            @if($app->city){{ $app->city }}@endif
                            @if($app->application_portal) · {{ \App\Models\StudentUniversityApplication::PORTAL_LABELS[$app->application_portal] ?? $app->application_portal }}@endif
                            @if($app->application_number) · Ref: {{ $app->application_number }}@endif
                        </div>
                    </td>
                    {{-- Bölüm · Derece --}}
                    <td style="padding:10px 10px;vertical-align:middle;">
                        <div style="font-size:13px;">{{ $app->department_name }}</div>
                        <div style="font-size:11px;color:#94a3b8;">{{ $degreeLabel }}@if($app->semester) · {{ $app->semester }}@endif</div>
                    </td>
                    {{-- Son Tarih --}}
                    <td style="padding:10px 10px;vertical-align:middle;white-space:nowrap;">
                        @if($app->deadline)
                            @php $dl = $app->deadline; $isNear = $dl->diffInDays(now(), false) >= -14 && $dl->isFuture(); $isPast = $dl->isPast(); @endphp
                            <span style="font-size:12px;font-weight:600;color:{{ $isPast ? '#dc2626' : ($isNear ? '#d97706' : '#334155') }};">
                                {{ $dl->format('d.m.Y') }}
                            </span>
                        @else
                            <span style="color:#cbd5e1;font-size:12px;">—</span>
                        @endif
                        @if($app->submitted_at)
                            <div style="font-size:11px;color:#94a3b8;">Gön: {{ $app->submitted_at->format('d.m.Y') }}</div>
                        @endif
                    </td>
                    {{-- Durum --}}
                    <td style="padding:10px 10px;vertical-align:middle;">
                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                        @if($hasReqMap)
                            <div style="margin-top:4px;">
                                <a href="#" onclick="event.preventDefault();document.getElementById('req-{{ $app->id }}').style.display=document.getElementById('req-{{ $app->id }}').style.display==='none'?'block':'none';"
                                   style="font-size:11px;color:#7c3aed;text-decoration:none;font-weight:600;">
                                    Belgeler ({{ count($reqMap->required_document_codes) }})
                                </a>
                            </div>
                        @endif
                        @if($app->notes)
                            <div style="font-size:11px;color:#94a3b8;font-style:italic;margin-top:2px;">{{ Str::limit($app->notes, 40) }}</div>
                        @endif
                    </td>
                    {{-- İşlemler --}}
                    <td style="padding:10px 10px;vertical-align:middle;text-align:right;white-space:nowrap;">
                        <div style="display:inline-flex;gap:4px;align-items:center;">
                            {{-- Öğrenci görünürlük --}}
                            <form method="POST" action="/senior/university-applications/{{ $app->id }}/visibility">
                                @csrf @method('POST')
                                <input type="hidden" name="target" value="student">
                                <input type="hidden" name="value" value="{{ $app->is_visible_to_student ? 0 : 1 }}">
                                <button type="submit" class="btn {{ $app->is_visible_to_student ? 'ok' : '' }}" style="font-size:11px;padding:3px 8px;"
                                        title="{{ $app->is_visible_to_student ? 'Öğrenciye görünür — kapat' : 'Öğrenciye göster' }}">
                                    {{ $app->is_visible_to_student ? 'Açık' : 'Kapalı' }}
                                </button>
                            </form>
                            {{-- Durum güncelle --}}
                            <form method="POST" action="/senior/university-applications/{{ $app->id }}" style="display:flex;gap:3px;align-items:center;">
                                @csrf @method('PUT')
                                <select name="status" style="font-size:11px;padding:3px 6px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#334155;">
                                    @foreach(\App\Models\StudentUniversityApplication::STATUS_LABELS as $val => $lbl)
                                        <option value="{{ $val }}" {{ $app->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn alt" style="font-size:11px;padding:3px 8px;">Kaydet</button>
                            </form>
                            {{-- Sil --}}
                            <form method="POST" action="/senior/university-applications/{{ $app->id }}"
                                  onsubmit="return confirm('Bu başvuruyu silmek istediğinize emin misiniz?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn warn" style="font-size:11px;padding:3px 8px;">Sil</button>
                            </form>
                        </div>
                    </td>
                </tr>
                {{-- Belge gereksinim satırı (gizli, toggle ile açılır) --}}
                @if($hasReqMap)
                <tr id="req-{{ $app->id }}" style="display:none;background:#f8fafd;">
                    <td colspan="7" style="padding:12px 16px;border-bottom:1px solid #e2e8f0;">
                        <div style="display:flex;gap:24px;flex-wrap:wrap;">
                            <div>
                                <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Zorunlu Belgeler</div>
                                <div style="display:flex;flex-direction:column;gap:3px;">
                                    @foreach($reqMap->required_document_codes as $code)
                                    <div style="display:flex;align-items:center;gap:6px;font-size:12px;">
                                        <span style="color:#16a34a;font-weight:700;">✓</span>
                                        <code style="background:#fff;border:1px solid #e5e7eb;padding:1px 5px;border-radius:3px;font-size:11px;">{{ $code }}</code>
                                        <span style="color:#475569;">{{ $docCatalog[$code]['label_tr'] ?? $code }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @if(!empty($reqMap->recommended_document_codes))
                            <div>
                                <div style="font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Tavsiye Edilen</div>
                                <div style="display:flex;flex-direction:column;gap:3px;">
                                    @foreach($reqMap->recommended_document_codes as $code)
                                    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#94a3b8;">
                                        <span>○</span>
                                        <code style="background:#fff;border:1px solid #e5e7eb;padding:1px 5px;border-radius:3px;font-size:11px;">{{ $code }}</code>
                                        <span>{{ $docCatalog[$code]['label_tr'] ?? $code }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @if($reqMap->language_requirement || $reqMap->notes)
                            <div style="font-size:12px;color:#64748b;">
                                @if($reqMap->language_requirement)<div>Dil: <strong>{{ $reqMap->language_requirement }}</strong></div>@endif
                                @if($reqMap->notes)<div style="font-style:italic;margin-top:4px;">{{ $reqMap->notes }}</div>@endif
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
            @endforeach
            </tbody>
        </table>
        </div>

        <div style="margin-top:12px;">
            {{ $applications->links() }}
        </div>
    @endif
</section>

@endsection

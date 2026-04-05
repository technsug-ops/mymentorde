@extends('senior.layouts.app')
@section('title', 'Başvuru & Süreç Takibi')
@section('page_title', 'Başvuru & Süreç Takibi')

@section('content')

@php
    $stepOptions = [
        'application_prep'  => 'Başvuru Hazırlık',
        'uni_assist'        => 'Uni-Assist',
        'visa_application'  => 'Vize Başvurusu',
        'language_course'   => 'Dil Kursu',
        'residence'         => 'İkamet',
        'official_services' => 'Resmi Hizmetler',
    ];
    $outcomeTypeOptions = [
        'acceptance'             => 'Kabul',
        'rejection'              => 'Red',
        'conditional_acceptance' => 'Koşullu Kabul',
        'correction_request'     => 'Düzeltme Talebi',
        'waitlist'               => 'Bekleme Listesi',
    ];
@endphp

@if(session('status'))
    <div style="padding:10px 16px;border-radius:8px;background:#16a34a;color:#fff;margin-bottom:14px;font-weight:600;font-size:var(--tx-sm);">✓ {{ session('status') }}</div>
@endif

{{-- Gradient header + student selector --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">🗂 Başvuru & Süreç Takibi</div>
    <div style="font-size:var(--tx-sm);opacity:.8;margin-bottom:16px;">Üniversite başvuruları, kurumdan gelen belgeler ve süreç kararları</div>
    <form method="GET" action="/senior/process-tracking" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <select name="student_id" id="pt-student-select"
                style="flex:1;min-width:240px;border:2px solid rgba(255,255,255,.3);border-radius:8px;padding:9px 12px;font-size:var(--tx-sm);font-weight:600;background:rgba(255,255,255,.15);color:#fff;cursor:pointer;backdrop-filter:blur(4px);">
            <option value="" style="color:#333;">— Öğrenci seçin —</option>
            @foreach($studentOptions as $opt)
                <option value="{{ $opt['id'] }}" style="color:#333;" {{ $filterSid === $opt['id'] ? 'selected' : '' }}>
                    {{ $opt['label'] }}
                </option>
            @endforeach
        </select>
        <button type="submit" style="background:rgba(255,255,255,.2);color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:8px;padding:9px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Yükle</button>
        @if($filterSid)
            <a href="/senior/process-tracking" style="color:rgba(255,255,255,.7);font-size:var(--tx-sm);padding:9px 14px;border:1px solid rgba(255,255,255,.3);border-radius:8px;text-decoration:none;">Temizle</a>
        @endif
    </form>
</div>

@if(!$filterSid)
{{-- Tüm belgeler görünümü --}}
@php
    $dCat    = $docFilters['dCat']    ?? '';
    $dStatus = $docFilters['dStatus'] ?? '';
    $dQ      = $docFilters['dQ']      ?? '';
    $dSid    = $docFilters['dSid']    ?? '';
@endphp

{{-- Belge filtresi --}}
<form method="GET" style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <select name="doc_student" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);min-width:160px;">
        <option value="">Tüm Öğrenciler</option>
        @foreach($studentOptions as $opt)
            <option value="{{ $opt['id'] }}" @selected($dSid === $opt['id'])>{{ $opt['label'] }}</option>
        @endforeach
    </select>
    <select name="category" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="">Tüm Kategoriler</option>
        @foreach($institutionCatalog as $catKey => $cat)
            <option value="{{ $catKey }}" @selected($dCat === $catKey)>{{ $cat['icon'] ?? '' }} {{ $cat['label_tr'] }}</option>
        @endforeach
    </select>
    <select name="status" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="">Tüm Durumlar</option>
        <option value="expected"        @selected($dStatus==='expected')>Bekleniyor</option>
        <option value="received"        @selected($dStatus==='received')>Alındı</option>
        <option value="action_required" @selected($dStatus==='action_required')>Aksiyon Gerekli</option>
        <option value="completed"       @selected($dStatus==='completed')>Tamamlandı</option>
    </select>
    <input type="text" name="q" value="{{ $dQ }}" placeholder="🔍  Belge adı, kurum, not..."
        style="flex:1;min-width:160px;border:1px solid var(--u-line);border-radius:7px;padding:8px 12px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
    <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:600;cursor:pointer;">Filtrele</button>
    <a href="/senior/process-tracking" style="color:var(--u-muted);font-size:var(--tx-sm);text-decoration:none;padding:8px 10px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);">Temizle</a>
</form>

{{-- Belge listesi --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
        <div style="font-weight:700;font-size:var(--tx-base);">📄 Tüm Kurumsal Belgeler</div>
        <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $allDocs->total() }} kayıt</span>
    </div>
    @forelse($allDocs as $rec)
    @php
        $catLabel    = $institutionCatalog[$rec->institution_category]['label_tr'] ?? $rec->institution_category;
        $catIcon     = $institutionCatalog[$rec->institution_category]['icon'] ?? '📄';
        $studentName = ($nameMap[(string)$rec->student_id] ?? null) ?: $rec->student_id;
        $smMap       = ['expected'=>['Bekleniyor','warn'],'received'=>['Alındı','ok'],'action_required'=>['Aksiyon','warn'],'completed'=>['Tamamlandı','ok'],'archived'=>['Arşiv','']];
        [$smLabel, $smBadge] = $smMap[$rec->status] ?? [$rec->status,'info'];
    @endphp
    <div style="padding:13px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;flex-wrap:wrap;">
                <span style="font-size:var(--tx-xs);font-weight:700;background:var(--u-bg);border:1px solid var(--u-line);border-radius:999px;padding:2px 8px;color:var(--u-muted);">{{ $catIcon }} {{ $catLabel }}</span>
                <span style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);">{{ $rec->document_type_label }}</span>
            </div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);">
                <a href="?student_id={{ $rec->student_id }}" style="color:#7c3aed;font-weight:600;text-decoration:none;">🎓 {{ $studentName }}</a>
                @if($rec->institution_name) · {{ $rec->institution_name }}@endif
                @if($rec->received_date) · {{ $rec->received_date->format('d.m.Y') }}@endif
            </div>
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;flex-wrap:wrap;">
            <span class="badge {{ $smBadge }}">{{ $smLabel }}</span>
            <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $rec->created_at->format('d.m.Y') }}</span>
            <form method="POST" action="/senior/institution-documents/{{ $rec->id }}/visibility">
                @csrf
                <input type="hidden" name="target" value="student">
                <input type="hidden" name="value" value="{{ $rec->is_visible_to_student ? 0 : 1 }}">
                <button type="submit" style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid {{ $rec->is_visible_to_student ? '#16a34a' : 'var(--u-line)' }};border-radius:6px;background:{{ $rec->is_visible_to_student ? '#f0fdf4' : 'var(--u-bg)' }};color:{{ $rec->is_visible_to_student ? '#16a34a' : 'var(--u-muted)' }};cursor:pointer;font-weight:600;">
                    Öğrenci: {{ $rec->is_visible_to_student ? '✓ Açık' : 'Kapalı' }}
                </button>
            </form>
            <form method="POST" action="/senior/institution-documents/{{ $rec->id }}" style="display:flex;gap:4px;">
                @csrf @method('PUT')
                <input type="hidden" name="institution_name" value="{{ $rec->institution_name }}">
                <input type="hidden" name="received_date" value="{{ $rec->received_date?->format('Y-m-d') }}">
                <input type="hidden" name="notes" value="{{ $rec->notes }}">
                <select name="status" style="font-size:var(--tx-xs);padding:3px 6px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);">
                    @foreach(['expected'=>'Bekleniyor','received'=>'Alındı','action_required'=>'Aksiyon','completed'=>'Tamamlandı'] as $v => $lbl)
                        <option value="{{ $v }}" @selected($rec->status === $v)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <button type="submit" style="font-size:var(--tx-xs);padding:4px 8px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);cursor:pointer;font-weight:600;">Güncelle</button>
            </form>
            <form method="POST" action="/senior/institution-documents/{{ $rec->id }}" onsubmit="return confirm('Silinsin mi?')">
                @csrf @method('DELETE')
                <button type="submit" style="font-size:var(--tx-xs);padding:4px 8px;border:1px solid #fca5a5;border-radius:6px;background:#fff5f5;color:#dc2626;cursor:pointer;font-weight:600;">Sil</button>
            </form>
        </div>
    </div>
    @empty
    <div style="padding:48px 20px;text-align:center;color:var(--u-muted);">
        <div style="font-size:40px;margin-bottom:10px;">📄</div>
        <div style="font-size:var(--tx-sm);font-weight:700;margin-bottom:4px;">Belge bulunamadı</div>
        <div style="font-size:var(--tx-sm);">Öğrenci seçerek o öğrencinin belgelerini ve süreç takibini görüntüleyin.</div>
    </div>
    @endforelse
    @if($allDocs->hasPages())
        <div style="padding:12px 18px;border-top:1px solid var(--u-line);">{{ $allDocs->links() }}</div>
    @endif
</div>

@else
@php
    $activeTabId = request('tab', 'hazirlik');
    $tabDefs = [
        ['id'=>'hazirlik', 'code'=>'application_prep',  'icon'=>'📋', 'label'=>'Başvuru Hazırlık'],
        ['id'=>'uni',      'code'=>'uni_assist',         'icon'=>'🏛', 'label'=>'Uni Assist'],
        ['id'=>'vize',     'code'=>'visa_application',   'icon'=>'🛂', 'label'=>'Vize'],
        ['id'=>'dil',      'code'=>'language_course',    'icon'=>'🗣', 'label'=>'Dil Kursu'],
        ['id'=>'ikamet',   'code'=>'residence',          'icon'=>'🏠', 'label'=>'İkamet'],
        ['id'=>'resmi',    'code'=>'official_services',  'icon'=>'🏛', 'label'=>'Resmi Hizmetler'],
    ];

    // ── Aşama ilerleme % hesabı ──────────────────────────────────────────────
    // Her aşama için sub-task tamamlama + aşamaya özel milestone'lar birlikte değerlendiriliyor.
    $stageExtras = [
        'application_prep' => [
            $guestApp !== null,
            $guestApp && in_array($guestApp->contract_status ?? '', ['signed','active']),
            $guestApp && ($guestApp->docs_ready ?? false),
            ($registrationDocs ?? collect())->where('status','approved')->isNotEmpty(),
        ],
        'uni_assist' => [
            $uniApplications->isNotEmpty(),
            $uniApplications->whereIn('status',['submitted','under_review','accepted','conditional_accepted','rejected'])->isNotEmpty(),
            $uniApplications->whereIn('status',['accepted','conditional_accepted'])->isNotEmpty(),
            $institutionDocs->isNotEmpty(),
        ],
        'visa_application' => [
            $ptVisa !== null,
            $ptVisa && in_array($ptVisa->status ?? '', ['submitted','in_review','approved']),
            $ptVisa && ($ptVisa->status ?? '') === 'approved',
        ],
        'language_course' => [
            ($languageCourses ?? collect())->isNotEmpty(),
            ($languageCourses ?? collect())->whereIn('certificate_status',['received','submitted'])->isNotEmpty(),
        ],
        'residence' => [
            $ptAccommodation !== null,
            $ptAccommodation && in_array($ptAccommodation->booking_status ?? '', ['booked','confirmed']),
            $ptAccommodation && ($ptAccommodation->booking_status ?? '') === 'confirmed',
        ],
        'official_services' => [],
    ];
    $stagePercents = [];
    foreach ($processDefinitions as $_def) {
        $_tasks    = $tasksByStep[$_def->id] ?? collect();
        $_taskDone = $_tasks->filter(fn($t) => $completedTaskIds->has($t->id))->count();
        $_extras   = $stageExtras[$_def->code] ?? [];
        $_total    = $_tasks->count() + count($_extras);
        $_done     = $_taskDone + count(array_filter($_extras));
        $stagePercents[$_def->code] = $_total > 0 ? (int) round($_done / $_total * 100) : 0;
    }
    unset($_def, $_tasks, $_taskDone, $_extras, $_total, $_done);
@endphp
{{-- Student header --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;display:flex;align-items:center;gap:12px;">
    <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:var(--tx-base);font-weight:800;flex-shrink:0;">{{ strtoupper(substr($filterSid,0,2)) }}</div>
    <div>
        <div style="font-weight:800;font-size:var(--tx-base);color:var(--u-text);">{{ $selectedStudent['label'] ?? $filterSid }}</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);">ID: {{ $filterSid }}</div>
    </div>
</div>

{{-- ── Unified Clickable Pipeline ─────────────────────────────────────────── --}}
<div style="display:flex;gap:6px;margin-bottom:14px;overflow-x:auto;padding-bottom:2px;">
    @foreach($tabDefs as $i => $tab)
    @php
        $pct     = $stagePercents[$tab['code']] ?? 0;
        $isDone  = $pct >= 100;
        $inProg  = $pct > 0 && !$isDone;
        $barCol  = $isDone ? '#16a34a' : ($inProg ? '#3b82f6' : 'var(--u-line)');
        $pctCol  = $isDone ? '#16a34a' : ($inProg ? '#1e40af' : 'var(--u-muted)');
        $cardBg  = $isDone ? '#f0fdf4' : ($inProg ? '#eff6ff' : 'var(--u-card)');
        $border  = $isDone ? '#86efac' : ($inProg ? '#bfdbfe' : 'var(--u-line)');
    @endphp
    <div id="pt-tab-{{ $tab['id'] }}" onclick="switchTab('{{ $tab['id'] }}')"
         style="flex:1;min-width:100px;background:{{ $cardBg }};border:2px solid {{ $border }};border-radius:10px;padding:10px 12px 10px;cursor:pointer;user-select:none;transition:all .15s;">
        {{-- Icon + % row --}}
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
            <span style="font-size:18px;line-height:1;">{{ $tab['icon'] }}</span>
            <span style="font-size:15px;font-weight:900;color:{{ $pctCol }};line-height:1;">
                @if($isDone) ✓ @else {{ $pct }}% @endif
            </span>
        </div>
        {{-- Label --}}
        <div style="font-size:11px;font-weight:700;color:var(--u-text);line-height:1.3;margin-bottom:8px;">{{ $tab['label'] }}</div>
        {{-- Progress bar --}}
        <div style="height:4px;background:var(--u-line);border-radius:999px;overflow:hidden;">
            <div style="height:100%;width:{{ $pct }}%;background:{{ $barCol }};border-radius:999px;transition:width .4s;"></div>
        </div>
    </div>
    @if($i < count($tabDefs) - 1)
    @php $connDone = ($stagePercents[$tab['code']] ?? 0) >= 100; @endphp
    <div style="display:flex;align-items:center;flex-shrink:0;padding-bottom:14px;gap:0;">
        <div style="width:22px;height:3px;background:{{ $connDone ? '#16a34a' : '#d1d5db' }};border-radius:2px 0 0 2px;"></div>
        <div style="width:0;height:0;border-top:6px solid transparent;border-bottom:6px solid transparent;border-left:10px solid {{ $connDone ? '#16a34a' : '#d1d5db' }};"></div>
    </div>
    @endif
    @endforeach
</div>

{{-- ══ TAB 2: UNI ASSIST ══ --}}
<div id="tab-uni">
@php $__defUni = $processDefinitions->firstWhere('code','uni_assist'); $__tasksUni = $__defUni ? ($tasksByStep[$__defUni->id] ?? collect()) : collect(); @endphp
@if($__tasksUni->isNotEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--u-muted);margin-bottom:10px;">Gorev Kontrol Listesi</div>
    @foreach($__tasksUni as $__task)
    @php $__done = isset($completedTaskIds[$__task->id]); @endphp
    <label style="display:flex;align-items:center;gap:8px;padding:7px 0;cursor:pointer;border-bottom:1px solid var(--u-line);">
        <input type="checkbox" class="pt-task-cb" data-task="{{ $__task->id }}" data-student="{{ $filterSid }}" {{ $__done ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer;">
        <span style="flex:1;font-size:var(--tx-sm);{{ $__done ? 'text-decoration:line-through;color:var(--u-muted)' : 'color:var(--u-text)' }}">{{ $__task->label_tr }}</span>
        @if($__task->is_required)<span style="font-size:10px;color:#d97706;font-weight:700;">Zorunlu</span>@endif
    </label>
    @endforeach
</div>
@endif
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;margin-bottom:14px;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:var(--tx-base);">🏛</span>
            <span style="font-weight:700;font-size:var(--tx-base);">Üniversite Başvuruları</span>
            <span class="badge info">{{ $uniApplications->count() }}</span>
        </div>
        <button style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;" onclick="toggleSection('uni-form')" type="button">+ Başvuru Ekle</button>
    </div>

    <div id="uni-form" style="display:none;padding:16px 18px;border-bottom:1px solid var(--u-line);background:var(--u-bg);">
        <form method="POST" action="/senior/university-applications">
            @csrf
            <input type="hidden" name="student_id" value="{{ $filterSid }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                @foreach([
                    ['name'=>'university_name','label'=>'Üniversite Adı *','type'=>'text','req'=>true,'ph'=>'ör. Technische Universität Berlin'],
                    ['name'=>'department_name','label'=>'Bölüm Adı *','type'=>'text','req'=>true,'ph'=>'ör. Maschinenbau (M.Sc.)'],
                ] as $f)
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">{{ $f['label'] }}</div>
                    <input type="{{ $f['type'] }}" name="{{ $f['name'] }}" {{ $f['req'] ? 'required' : '' }} placeholder="{{ $f['ph'] }}"
                           style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                @endforeach
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Derece *</div>
                    <select name="degree_type" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        @foreach(\App\Models\StudentUniversityApplication::DEGREE_LABELS as $val => $lbl)
                            <option value="{{ $val }}" {{ $val === 'master' ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Başvuru Portalı</div>
                    <select name="application_portal" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        <option value="">Seçin...</option>
                        @foreach(\App\Models\StudentUniversityApplication::PORTAL_LABELS as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Şehir</div>
                    <input type="text" name="city" placeholder="ör. Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Dönem</div>
                    <select name="semester" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        <option value="">— Seçin —</option>
                        <option value="WS2025/26">WS2025/26</option>
                        <option value="SS2026">SS2026</option>
                        <option value="WS2026/27">WS2026/27</option>
                        <option value="SS2027">SS2027</option>
                        <option value="WS2027/28">WS2027/28</option>
                        <option value="SS2028">SS2028</option>
                    </select>
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Son Başvuru Tarihi</div>
                    <input type="date" name="deadline" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Durum *</div>
                    <select name="status" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        @foreach(\App\Models\StudentUniversityApplication::STATUS_LABELS as $val => $lbl)
                            <option value="{{ $val }}" {{ $val === 'planned' ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Öncelik</div>
                    <input type="number" name="priority" value="{{ $uniApplications->count() + 1 }}" min="1" max="99" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Notlar</div>
                <textarea name="notes" rows="2" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Kaydet</button>
                <button type="button" onclick="toggleSection('uni-form')" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:8px 14px;font-size:var(--tx-sm);cursor:pointer;">İptal</button>
            </div>
        </form>
    </div>

    @if($uniApplications->isEmpty())
        <div style="padding:32px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Henüz başvuru eklenmedi.</div>
    @else
        @foreach($uniApplications as $app)
        @php
            $badgeClass  = \App\Models\StudentUniversityApplication::STATUS_BADGE[$app->status] ?? 'info';
            $statusLabel = \App\Models\StudentUniversityApplication::STATUS_LABELS[$app->status] ?? $app->status;
            $degreeLabel = \App\Models\StudentUniversityApplication::DEGREE_LABELS[$app->degree_type] ?? $app->degree_type;
        @endphp
        <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <span style="font-size:var(--tx-xs);font-weight:800;background:#eef2fd;border:1px solid #c9d5f0;border-radius:999px;padding:2px 8px;color:#3b5bdb;">#{{ $app->priority }}</span>
                        <span style="font-weight:800;font-size:var(--tx-sm);color:var(--u-text);">{{ $app->university_name }}</span>
                        @if($app->city)<span style="font-size:var(--tx-xs);color:var(--u-muted);">· {{ $app->city }}</span>@endif
                    </div>
                    <div style="font-size:var(--tx-sm);color:var(--u-text);">
                        {{ $app->department_name }}
                        <span style="color:var(--u-muted);">· {{ $degreeLabel }}</span>
                        @if($app->semester)<span style="color:var(--u-muted);"> · {{ $app->semester }}</span>@endif
                    </div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">
                        @if($app->application_portal){{ \App\Models\StudentUniversityApplication::PORTAL_LABELS[$app->application_portal] ?? '' }}@endif
                        @if($app->application_number) · Ref: {{ $app->application_number }}@endif
                        @if($app->deadline) · Son: {{ $app->deadline->format('d.m.Y') }}@endif
                        @if($app->submitted_at) · Gönderildi: {{ $app->submitted_at->format('d.m.Y') }}@endif
                        @if($app->result_at) · Sonuç: {{ $app->result_at->format('d.m.Y') }}@endif
                    </div>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                <form method="POST" action="/senior/university-applications/{{ $app->id }}" style="display:flex;gap:6px;align-items:center;">
                    @csrf @method('PUT')
                    <select name="status" style="font-size:var(--tx-xs);border:1px solid var(--u-line);border-radius:6px;padding:4px 8px;background:var(--u-bg);color:var(--u-text);">
                        @foreach(\App\Models\StudentUniversityApplication::STATUS_LABELS as $val => $lbl)
                            <option value="{{ $val }}" {{ $app->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <button type="submit" style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);cursor:pointer;font-weight:600;">Güncelle</button>
                </form>
                <form method="POST" action="/senior/university-applications/{{ $app->id }}/visibility" style="display:inline;">
                    @csrf
                    <input type="hidden" name="target" value="student">
                    <input type="hidden" name="value" value="{{ $app->is_visible_to_student ? 0 : 1 }}">
                    <button type="submit" style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid {{ $app->is_visible_to_student ? '#16a34a' : 'var(--u-line)' }};border-radius:6px;background:{{ $app->is_visible_to_student ? '#f0fdf4' : 'var(--u-bg)' }};color:{{ $app->is_visible_to_student ? '#16a34a' : 'var(--u-muted)' }};cursor:pointer;font-weight:600;">
                        Öğrenci: {{ $app->is_visible_to_student ? '✓ Açık' : 'Kapalı' }}
                    </button>
                </form>
                <form method="POST" action="/senior/university-applications/{{ $app->id }}" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid #fca5a5;border-radius:6px;background:#fff5f5;color:#dc2626;cursor:pointer;font-weight:600;">Sil</button>
                </form>
            </div>
        </div>
        @endforeach
    @endif
</div>

{{-- Kurumsal Belgeler (Üniversitelerden/Kurumlardan gelen) --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;margin-bottom:14px;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-weight:700;font-size:var(--tx-base);">Kurumdan Gelen Belgeler</span>
            <span class="badge info">{{ $institutionDocs->count() }}</span>
        </div>
        <button style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;" onclick="toggleSection('doc-form')" type="button">+ Belge Ekle</button>
    </div>

    <div id="doc-form" style="display:none;padding:16px 18px;border-bottom:1px solid var(--u-line);background:var(--u-bg);">
        <form method="POST" action="/senior/institution-documents" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="student_id" value="{{ $filterSid }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Kategori *</label>
                    <select name="institution_category" id="pt-cat-select" required onchange="ptFillDocTypes(this.value)"
                            style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        <option value="">Seçin...</option>
                        @foreach($institutionCatalog as $catKey => $cat)
                            <option value="{{ $catKey }}">{{ $cat['icon'] ?? '' }} {{ $cat['label_tr'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Belge Türü *</label>
                    <select name="document_type_code" id="pt-type-select" required onchange="ptSyncLabel(this)"
                            style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        <option value="">Önce kategori seçin</option>
                    </select>
                    <input type="hidden" name="document_type_label" id="pt-type-label">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Kurum Adı</label>
                    <input type="text" name="institution_name" placeholder="ör. TU Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Alındı Tarihi</label>
                    <input type="date" name="received_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Durum</label>
                    <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        <option value="received" selected>Alındı</option>
                        <option value="expected">Bekleniyor</option>
                        <option value="action_required">Aksiyon Gerekli</option>
                        <option value="completed">Tamamlandı</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Dosya (opsiyonel)</label>
                    <label for="pt-doc-file" style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid var(--u-line);border-radius:7px;cursor:pointer;font-size:var(--tx-xs);font-weight:600;background:var(--u-card);color:var(--u-text);">📎 Seç</label>
                    <input type="file" id="pt-doc-file" name="document_file" style="display:none;" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="document.getElementById('pt-doc-fname').textContent = this.files[0]?.name || ''">
                    <span id="pt-doc-fname" style="font-size:var(--tx-xs);color:var(--u-muted);display:block;margin-top:3px;"></span>
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Notlar</label>
                <textarea name="notes" rows="2" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Kaydet</button>
                <button type="button" onclick="toggleSection('doc-form')" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:8px 14px;font-size:var(--tx-sm);cursor:pointer;">İptal</button>
            </div>
        </form>
    </div>

    @if($institutionDocs->isEmpty())
        <div style="padding:24px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Henüz kurum belgesi eklenmedi.</div>
    @else
        @php $docGrouped = $institutionDocs->groupBy('institution_category'); @endphp
        @foreach($docGrouped as $catKey => $catDocs)
        @php $catInfo = $institutionCatalog[$catKey] ?? []; $catLabel = ($catInfo['icon'] ?? '') . ' ' . ($catInfo['label_tr'] ?? $catKey); @endphp
        <div style="padding:7px 18px;font-size:var(--tx-xs);font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--u-muted);background:var(--u-bg);border-bottom:1px solid var(--u-line);">{{ $catLabel }}</div>
        @foreach($catDocs as $doc)
        @php [$statusLabel,$statusBadge] = ['expected'=>['Bekleniyor','warn'],'received'=>['Alındı','ok'],'action_required'=>['Aksiyon','warn'],'completed'=>['Tamamlandı','ok']][$doc->status] ?? [$doc->status,'info']; @endphp
        <div style="padding:11px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:var(--tx-sm);">{{ $doc->document_type_label }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:1px;">@if($doc->institution_name){{ $doc->institution_name }}@endif @if($doc->received_date)· {{ $doc->received_date->format('d.m.Y') }}@endif</div>
            </div>
            <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;flex-shrink:0;">
                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                <form method="POST" action="/senior/institution-documents/{{ $doc->id }}" style="display:flex;gap:4px;align-items:center;">
                    @csrf @method('PUT')
                    <input type="hidden" name="institution_name" value="{{ $doc->institution_name }}">
                    <input type="hidden" name="received_date" value="{{ $doc->received_date?->format('Y-m-d') }}">
                    <input type="hidden" name="notes" value="{{ $doc->notes }}">
                    <select name="status" style="font-size:var(--tx-xs);padding:3px 6px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);">
                        @foreach(['expected'=>'Bekleniyor','received'=>'Alındı','action_required'=>'Aksiyon','completed'=>'Tamamlandı'] as $v => $lbl)
                            <option value="{{ $v }}" @selected($doc->status === $v)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <button type="submit" style="font-size:var(--tx-xs);padding:4px 8px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);cursor:pointer;font-weight:600;">Güncelle</button>
                </form>
                <form method="POST" action="/senior/institution-documents/{{ $doc->id }}" onsubmit="return confirm('Silinsin mi?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:var(--tx-xs);padding:4px 8px;border:1px solid #fca5a5;border-radius:6px;background:#fff5f5;color:#dc2626;cursor:pointer;font-weight:600;">Sil</button>
                </form>
            </div>
        </div>
        @endforeach
        @endforeach
    @endif
</div>

{{-- Üniversite Kararları --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-weight:700;font-size:var(--tx-base);">Süreç Kararları</span>
            <span class="badge info">{{ $outcomes->count() }}</span>
        </div>
        <button style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;" onclick="toggleSection('outcome-form')" type="button">+ Karar Ekle</button>
    </div>
    <div id="outcome-form" style="display:none;padding:16px 18px;border-bottom:1px solid var(--u-line);background:var(--u-bg);">
        <form method="POST" action="{{ route('senior.process-outcomes.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="student_id" value="{{ $filterSid }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Süreç Adımı *</label>
                    <select name="process_step" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        @foreach($stepOptions as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Sonuç Tipi *</label>
                    <select name="outcome_type" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        @foreach($outcomeTypeOptions as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Üniversite</label>
                    <input type="text" name="university" placeholder="TU Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Program</label>
                    <input type="text" name="program" placeholder="Informatik M.Sc." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Detay / Açıklama *</label>
                <textarea name="details_tr" required rows="3" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:10px;">
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Son Tarih</label>
                    <input type="date" name="deadline" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Belge (ops.)</label>
                    <label for="ptOutcomeFile" style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid var(--u-line);border-radius:7px;cursor:pointer;font-size:var(--tx-xs);font-weight:600;background:var(--u-bg);color:var(--u-text);">📎 Dosya Seç</label>
                    <input type="file" name="document_file" id="ptOutcomeFile" style="display:none;" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="document.getElementById('ptOutcomeFn').textContent = this.files[0]?.name || ''">
                    <span id="ptOutcomeFn" style="font-size:var(--tx-xs);color:var(--u-muted);display:block;margin-top:2px;"></span>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Kaydet</button>
                <button type="button" onclick="toggleSection('outcome-form')" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:8px 14px;font-size:var(--tx-sm);cursor:pointer;">İptal</button>
            </div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:8px;">Kaydedilince öğrenciye görünmez — "Öğrenciye Göster" ile yayınlayın.</div>
        </form>
    </div>
    @if($outcomes->isEmpty())
        <div style="padding:24px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Henüz süreç kararı eklenmedi.</div>
    @else
        @foreach($outcomes as $row)
        @php
            $stepLbl  = $stepOptions[$row->process_step] ?? $row->process_step;
            $typeLbl  = $outcomeTypeOptions[$row->outcome_type] ?? ucfirst($row->outcome_type);
            $isVisible= (bool) $row->is_visible_to_student;
            $typeBadge= match($row->outcome_type) { 'acceptance'=>'ok','rejection'=>'danger','conditional_acceptance'=>'warn',default=>'info' };
        @endphp
        <div style="padding:13px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;margin-bottom:5px;">
                    <span class="badge info">{{ $stepLbl }}</span>
                    <span class="badge {{ $typeBadge }}">{{ $typeLbl }}</span>
                    <span class="badge {{ $isVisible ? 'ok' : '' }}">{{ $isVisible ? 'Öğrenciye Açık' : 'Gizli' }}</span>
                </div>
                @if($row->university || $row->program)<div style="font-size:var(--tx-sm);font-weight:700;margin-bottom:2px;">{{ $row->university }}@if($row->university && $row->program) / @endif{{ $row->program }}</div>@endif
                <div style="font-size:var(--tx-sm);color:var(--u-text);">{{ $row->details_tr }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:3px;">
                    @if($row->deadline)Son: {{ \Carbon\Carbon::parse($row->deadline)->format('d.m.Y') }} · @endif{{ optional($row->created_at)->format('d.m.Y') }}
                </div>
            </div>
            <div style="flex-shrink:0;">
                @if(!$isVisible)
                    <form method="POST" action="{{ route('senior.process-outcomes.make-visible', $row->id) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Öğrenciye görünür yapılsın mı?')" style="background:#16a34a;color:#fff;border:none;border-radius:7px;padding:6px 12px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;">Öğrenciye Göster</button>
                    </form>
                @else
                    <span style="font-size:var(--tx-xs);color:#16a34a;font-weight:600;">✓ Yayınlandı</span>
                @endif
            </div>
        </div>
        @endforeach
    @endif
</div>

</div>{{-- /tab-uni --}}

{{-- ══ TAB 1: BAŞVURU HAZIRLIK ══ --}}
<div id="tab-hazirlik">
@php $__defHz = $processDefinitions->firstWhere('code','application_prep'); $__tasksHz = $__defHz ? ($tasksByStep[$__defHz->id] ?? collect()) : collect(); @endphp
@if($__tasksHz->isNotEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--u-muted);margin-bottom:10px;">Gorev Kontrol Listesi</div>
    @foreach($__tasksHz as $__task)
    @php $__done = isset($completedTaskIds[$__task->id]); @endphp
    <label style="display:flex;align-items:center;gap:8px;padding:7px 0;cursor:pointer;border-bottom:1px solid var(--u-line);">
        <input type="checkbox" class="pt-task-cb" data-task="{{ $__task->id }}" data-student="{{ $filterSid }}" {{ $__done ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer;">
        <span style="flex:1;font-size:var(--tx-sm);{{ $__done ? 'text-decoration:line-through;color:var(--u-muted)' : 'color:var(--u-text)' }}">{{ $__task->label_tr }}</span>
        @if($__task->is_required)<span style="font-size:10px;color:#d97706;font-weight:700;">Zorunlu</span>@endif
    </label>
    @endforeach
</div>
@endif
{{-- ── BAŞVURU FORMU DURUMU ── --}}
@if($guestApp)
@php
    $leadStatusLabels = ['new'=>'Yeni','contacted'=>'İletişimde','qualified'=>'Nitelikli','proposal'=>'Teklif','follow_up'=>'Takip','converted'=>'Dönüştürüldü','lost'=>'Kaybedildi'];
    $contractStatusLabels = ['none'=>'Sözleşme Yok','draft'=>'Taslak','sent'=>'Gönderildi','signed'=>'İmzalandı','active'=>'Aktif','terminated'=>'Sonlandırıldı'];
    $lsBadge = match($guestApp->lead_status ?? 'new') {'converted'=>'ok','lost'=>'danger','qualified','proposal'=>'info',default=>'warn'};
    $csBadge = match($guestApp->contract_status ?? 'none') {'signed','active'=>'ok','sent'=>'info','none'=>'',default=>'warn'};
@endphp
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;margin-bottom:14px;">
    <div style="padding:12px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:var(--tx-base);">Başvuru Formu</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <span class="badge {{ $lsBadge }}">{{ $leadStatusLabels[$guestApp->lead_status ?? 'new'] ?? $guestApp->lead_status }}</span>
            @if($guestApp->contract_status && $guestApp->contract_status !== 'none')
                <span class="badge {{ $csBadge }}">Sözleşme: {{ $contractStatusLabels[$guestApp->contract_status] ?? $guestApp->contract_status }}</span>
            @endif
            @if($guestApp->docs_ready)
                <span class="badge ok">Belgeler Hazır</span>
            @endif
        </div>
    </div>
    <div style="padding:14px 18px;">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;font-size:var(--tx-sm);">
            <div><span style="color:var(--u-muted);font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Ad Soyad</span><div style="margin-top:3px;font-weight:600;">{{ $guestApp->first_name }} {{ $guestApp->last_name }}</div></div>
            <div><span style="color:var(--u-muted);font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">E-posta</span><div style="margin-top:3px;">{{ $guestApp->email ?: '—' }}</div></div>
            <div><span style="color:var(--u-muted);font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Telefon</span><div style="margin-top:3px;">{{ $guestApp->phone ?: '—' }}</div></div>
            <div><span style="color:var(--u-muted);font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Başvuru Ülkesi</span><div style="margin-top:3px;">{{ $guestApp->application_country ?: '—' }}</div></div>
            <div><span style="color:var(--u-muted);font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Hedef Dönem</span><div style="margin-top:3px;">{{ $guestApp->target_term ?: '—' }}</div></div>
            <div><span style="color:var(--u-muted);font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Hedef Şehir</span><div style="margin-top:3px;">{{ $guestApp->target_city ?: '—' }}</div></div>
            @if($guestApp->language_level)
            <div><span style="color:var(--u-muted);font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Dil Seviyesi</span><div style="margin-top:3px;">{{ $guestApp->language_level }}</div></div>
            @endif
        </div>
        @if($guestApp->notes)
        <div style="margin-top:10px;padding:8px 12px;background:var(--u-bg);border-radius:7px;font-size:var(--tx-xs);color:var(--u-text);">{{ $guestApp->notes }}</div>
        @endif
        <div style="margin-top:10px;">
            <a href="/senior/guests/{{ $guestApp->id }}" style="font-size:var(--tx-xs);color:#7c3aed;font-weight:600;text-decoration:none;">Başvuru Detayı →</a>
        </div>
    </div>
</div>
@endif

{{-- ── KAYIT BELGELERİ ── --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;margin-bottom:14px;">
    <div style="padding:12px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-weight:700;font-size:var(--tx-base);">Kayıt Belgeleri</span>
            @if($registrationDocs->isNotEmpty())
            @php
                $approvedCount = $registrationDocs->where('status','approved')->count();
                $pendingCount  = $registrationDocs->whereIn('status',['pending','review'])->count();
                $rejectedCount = $registrationDocs->where('status','rejected')->count();
            @endphp
            <span class="badge ok">{{ $approvedCount }} Onaylı</span>
            @if($pendingCount > 0)<span class="badge warn">{{ $pendingCount }} Bekliyor</span>@endif
            @if($rejectedCount > 0)<span class="badge danger">{{ $rejectedCount }} Reddedildi</span>@endif
            @endif
        </div>
        <a href="/senior/registration-documents?student_id={{ $filterSid }}" style="font-size:var(--tx-xs);color:#7c3aed;font-weight:700;text-decoration:none;padding:6px 12px;border:1px solid #7c3aed;border-radius:7px;">Belge Onayları →</a>
    </div>
    @if($registrationDocs->isEmpty())
        <div style="padding:24px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Henüz belge yüklenmedi.</div>
    @else
        @php
            $docStatusMap = ['pending'=>['Bekliyor','warn'],'review'=>['İncelemede','info'],'approved'=>['Onaylandı','ok'],'rejected'=>['Reddedildi','danger']];
        @endphp
        @foreach($registrationDocs as $rd)
        @php [$rdLabel,$rdBadge] = $docStatusMap[$rd->status] ?? [$rd->status,'info']; @endphp
        <div style="padding:10px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;gap:8px;transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
            <div style="flex:1;min-width:0;">
                <div style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $rd->original_file_name }}</div>
                @if($rd->category)<div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:1px;">{{ $rd->category->name }}</div>@endif
                @if($rd->review_note)<div style="font-size:var(--tx-xs);color:#dc2626;margin-top:2px;">{{ $rd->review_note }}</div>@endif
            </div>
            <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
                <span class="badge {{ $rdBadge }}">{{ $rdLabel }}</span>
                <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $rd->created_at->format('d.m.Y') }}</span>
            </div>
        </div>
        @endforeach
    @endif
</div>

{{-- ── DOKÜMAN OLUŞTURUCU ── --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px 18px;margin-bottom:14px;display:flex;align-items:center;gap:14px;">
    <div style="width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">📝</div>
    <div style="flex:1;">
        <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);">Doküman Oluşturucu</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">CV, motivasyon mektubu ve diğer başvuru belgelerini oluşturun</div>
    </div>
    <a href="/senior/document-builder?student_id={{ $filterSid }}" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:8px 16px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap;">Oluşturucu Aç →</a>
</div>

{{-- ── SÜREÇTEKİ NOTLAR / TAMAMLANAN OTOMATİK GÖREVLER ── --}}
{{-- Tamamlanan Görevler --}}
@if($filterSid && $doneTasks->isNotEmpty())
@php
    $priorityLabel = ['low' => 'Düşük', 'normal' => 'Normal', 'high' => 'Yüksek', 'urgent' => 'Acil'];
    $deptLabel     = ['operations' => 'Operasyon', 'finance' => 'Finans', 'advisory' => 'Danışmanlık', 'marketing' => 'Pazarlama', 'system' => 'Sistem'];
@endphp
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:8px;">
        <span style="font-weight:700;font-size:var(--tx-base);">Otomatik Görev Geçmişi</span>
        <span class="badge ok">{{ $doneTasks->count() }}</span>
        <span style="font-size:var(--tx-xs);color:var(--u-muted);margin-left:auto;">Sistem tarafından tamamlanan görevler</span>
    </div>
    @foreach($doneTasks as $dt)
    @php
        $ptLabel = \App\Models\MarketingTask::PROCESS_TYPES[$dt->process_type] ?? $dt->process_type;
        $wsLabel = \App\Models\MarketingTask::WORKFLOW_STAGES[$dt->process_type][$dt->workflow_stage] ?? $dt->workflow_stage;
    @endphp
    <div style="padding:10px 18px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:10px;transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
        <span style="font-size:var(--tx-sm);flex-shrink:0;color:#16a34a;">✔</span>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:var(--tx-sm);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $dt->title }}</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;display:flex;gap:6px;flex-wrap:wrap;">
                @if($dt->process_type)<span class="badge info" style="font-size:var(--tx-xs);">{{ $ptLabel }}</span>@endif
                @if($dt->workflow_stage)<span>{{ $wsLabel }}</span>@endif
                <span>{{ $deptLabel[$dt->department] ?? $dt->department }}</span>
            </div>
        </div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);white-space:nowrap;">
            {{ $dt->completed_at ? \Carbon\Carbon::parse($dt->completed_at)->format('d.m.Y H:i') : '—' }}
        </div>
    </div>
    @endforeach
</div>
@endif

</div>{{-- /tab-hazirlik --}}

{{-- ══ TAB 3: VİZE ══ --}}
<div id="tab-vize">
@php $__defViz = $processDefinitions->firstWhere('code','visa_application'); $__tasksViz = $__defViz ? ($tasksByStep[$__defViz->id] ?? collect()) : collect(); @endphp
@if($__tasksViz->isNotEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--u-muted);margin-bottom:10px;">Gorev Kontrol Listesi</div>
    @foreach($__tasksViz as $__task)
    @php $__done = isset($completedTaskIds[$__task->id]); @endphp
    <label style="display:flex;align-items:center;gap:8px;padding:7px 0;cursor:pointer;border-bottom:1px solid var(--u-line);">
        <input type="checkbox" class="pt-task-cb" data-task="{{ $__task->id }}" data-student="{{ $filterSid }}" {{ $__done ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer;">
        <span style="flex:1;font-size:var(--tx-sm);{{ $__done ? 'text-decoration:line-through;color:var(--u-muted)' : 'color:var(--u-text)' }}">{{ $__task->label_tr }}</span>
        @if($__task->is_required)<span style="font-size:10px;color:#d97706;font-weight:700;">Zorunlu</span>@endif
    </label>
    @endforeach
</div>
@endif
<article class="panel">
    <h3 style="margin:0 0 14px;">🛂 Vize Başvurusu</h3>

    @if($ptVisa)
    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--u-bg);border-radius:10px;margin-bottom:14px;">
        <div style="font-size:var(--tx-2xl);">{{ $ptVisa->status === 'approved' ? '✅' : ($ptVisa->status === 'rejected' ? '❌' : '🛂') }}</div>
        <div>
            <div style="font-weight:700;font-size:var(--tx-sm);">{{ \App\Models\StudentVisaApplication::VISA_TYPE_LABELS[$ptVisa->visa_type] ?? $ptVisa->visa_type }}</div>
            <div style="margin-top:3px;">
                <span class="badge {{ $ptVisa->statusBadge() }}">{{ $ptVisa->statusLabel() }}</span>
                @if($ptVisa->consulate_city) <span class="muted" style="font-size:var(--tx-xs);">· {{ $ptVisa->consulate_city }}</span>@endif
                @if($ptVisa->appointment_date) <span class="muted" style="font-size:var(--tx-xs);">· Randevu: {{ $ptVisa->appointment_date->format('d.m.Y') }}</span>@endif
            </div>
        </div>
    </div>
    <form method="POST" action="{{ route('senior.visa.update', $ptVisa->id) }}">
        @csrf @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Durum</label>
                <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentVisaApplication::STATUS_LABELS as $v => $l)
                        <option value="{{ $v }}" {{ $ptVisa->status === $v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Konsolosluk</label>
                <select name="consulate_city" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    <option value="">—</option>
                    @foreach(\App\Models\StudentVisaApplication::CONSULATE_CITIES as $c)
                        <option value="{{ $c }}" {{ $ptVisa->consulate_city === $c ? 'selected':'' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Randevu Tarihi</label>
                <input type="date" name="appointment_date" value="{{ $ptVisa->appointment_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Karar Tarihi</label>
                <input type="date" name="decision_date" value="{{ $ptVisa->decision_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Geçerli Başlangıç</label>
                <input type="date" name="valid_from" value="{{ $ptVisa->valid_from?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Geçerli Bitiş</label>
                <input type="date" name="valid_until" value="{{ $ptVisa->valid_until?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
        </div>
        <div style="margin-bottom:10px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:5px;">Sunulan Belgeler</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach(\App\Models\StudentVisaApplication::COMMON_DOCUMENTS as $key => $lbl)
                <label style="display:inline-flex;align-items:center;gap:4px;font-size:var(--tx-xs);cursor:pointer;">
                    <input type="checkbox" name="submitted_documents[]" value="{{ $key }}" {{ in_array($key, $ptVisa->submitted_documents ?? []) ? 'checked':'' }}> {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>
        <textarea name="notes" rows="2" placeholder="Not..." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;margin-bottom:8px;">{{ $ptVisa->notes }}</textarea>
        <div style="display:flex;align-items:center;gap:10px;">
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <input type="checkbox" name="is_visible_to_student" value="1" {{ $ptVisa->is_visible_to_student ? 'checked':'' }}> Öğrenciye görünür
            </label>
            <button type="submit" style="background:#0891b2;color:#fff;border:none;border-radius:7px;padding:7px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Güncelle</button>
        </div>
    </form>
    @else
    <form method="POST" action="{{ route('senior.visa.store') }}">
        @csrf
        <input type="hidden" name="student_id" value="{{ $filterSid }}">
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
                    @foreach(\App\Models\StudentVisaApplication::CONSULATE_CITIES as $c)
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
</div>{{-- /tab-vize --}}

{{-- ══ TAB 4: DİL KURSU ══ --}}
<div id="tab-dil">
@php $__defDil = $processDefinitions->firstWhere('code','language_course'); $__tasksDil = $__defDil ? ($tasksByStep[$__defDil->id] ?? collect()) : collect(); @endphp
@if($__tasksDil->isNotEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--u-muted);margin-bottom:10px;">Gorev Kontrol Listesi</div>
    @foreach($__tasksDil as $__task)
    @php $__done = isset($completedTaskIds[$__task->id]); @endphp
    <label style="display:flex;align-items:center;gap:8px;padding:7px 0;cursor:pointer;border-bottom:1px solid var(--u-line);">
        <input type="checkbox" class="pt-task-cb" data-task="{{ $__task->id }}" data-student="{{ $filterSid }}" {{ $__done ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer;">
        <span style="flex:1;font-size:var(--tx-sm);{{ $__done ? 'text-decoration:line-through;color:var(--u-muted)' : 'color:var(--u-text)' }}">{{ $__task->label_tr }}</span>
        @if($__task->is_required)<span style="font-size:10px;color:#d97706;font-weight:700;">Zorunlu</span>@endif
    </label>
    @endforeach
</div>
@endif

{{-- Dil Kursları listesi --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;margin-bottom:14px;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:var(--tx-base);">🗣</span>
            <span style="font-weight:700;font-size:var(--tx-base);">Dil Kursları</span>
            @if(isset($languageCourses))<span class="badge info">{{ $languageCourses->count() }}</span>@endif
        </div>
        <button style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;" onclick="toggleSection('dil-form')" type="button">+ Kurs Ekle</button>
    </div>

    <div id="dil-form" style="display:none;padding:16px 18px;border-bottom:1px solid var(--u-line);background:var(--u-bg);">
        <form method="POST" action="/senior/language-courses">
            @csrf
            <input type="hidden" name="student_id" value="{{ $filterSid }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Okul Adı *</div>
                    <input type="text" name="school_name" required placeholder="ör. Goethe-Institut Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Şehir</div>
                    <input type="text" name="city" placeholder="ör. Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Kurs Tipi</div>
                    <select name="course_type" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        @foreach(\App\Models\StudentLanguageCourse::COURSE_TYPE_LABELS as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Hedef Seviye</div>
                    <input type="text" name="level_target" placeholder="ör. B2" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Başlangıç</div>
                    <input type="date" name="start_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Bitiş</div>
                    <input type="date" name="end_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Sertifika Durumu</div>
                    <select name="certificate_status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        @foreach(\App\Models\StudentLanguageCourse::CERT_STATUS_LABELS as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;align-items:center;gap:6px;padding-top:22px;">
                    <input type="checkbox" name="is_visible_to_student" value="1" id="dil-vis" checked style="width:16px;height:16px;">
                    <label for="dil-vis" style="font-size:var(--tx-sm);cursor:pointer;">Öğrenciye görünür</label>
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Notlar</div>
                <textarea name="notes" rows="2" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Kaydet</button>
                <button type="button" onclick="toggleSection('dil-form')" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:8px 14px;font-size:var(--tx-sm);cursor:pointer;">İptal</button>
            </div>
        </form>
    </div>

    @if(isset($languageCourses) && $languageCourses->isEmpty())
        <div style="padding:32px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Henüz dil kursu eklenmedi.</div>
    @elseif(isset($languageCourses))
        @foreach($languageCourses as $lc)
        @php
            $certBadge = \App\Models\StudentLanguageCourse::CERT_STATUS_BADGE[$lc->certificate_status] ?? 'info';
            $certLabel = \App\Models\StudentLanguageCourse::CERT_STATUS_LABELS[$lc->certificate_status] ?? $lc->certificate_status;
            $ctLabel   = \App\Models\StudentLanguageCourse::COURSE_TYPE_LABELS[$lc->course_type] ?? $lc->course_type;
        @endphp
        <div style="padding:13px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);">{{ $lc->school_name }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">
                    {{ $ctLabel }}
                    @if($lc->level_target) · Hedef: {{ $lc->level_target }}@endif
                    @if($lc->level_achieved) · Alınan: {{ $lc->level_achieved }}@endif
                    @if($lc->city) · {{ $lc->city }}@endif
                    @if($lc->start_date) · {{ $lc->start_date->format('d.m.Y') }}@endif
                    @if($lc->end_date) – {{ $lc->end_date->format('d.m.Y') }}@endif
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
                <span class="badge {{ $certBadge }}">{{ $certLabel }}</span>
                <form method="POST" action="/senior/language-courses/{{ $lc->id }}" onsubmit="return confirm('Silinsin mi?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:var(--tx-xs);padding:4px 8px;border:1px solid #fca5a5;border-radius:6px;background:#fff5f5;color:#dc2626;cursor:pointer;font-weight:600;">Sil</button>
                </form>
            </div>
        </div>
        @endforeach
    @endif
</div>
</div>{{-- /tab-dil --}}

{{-- ══ TAB 5: İKAMET ══ --}}
<div id="tab-ikamet">
@php $__defIk = $processDefinitions->firstWhere('code','residence'); $__tasksIk = $__defIk ? ($tasksByStep[$__defIk->id] ?? collect()) : collect(); @endphp
@if($__tasksIk->isNotEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--u-muted);margin-bottom:10px;">Gorev Kontrol Listesi</div>
    @foreach($__tasksIk as $__task)
    @php $__done = isset($completedTaskIds[$__task->id]); @endphp
    <label style="display:flex;align-items:center;gap:8px;padding:7px 0;cursor:pointer;border-bottom:1px solid var(--u-line);">
        <input type="checkbox" class="pt-task-cb" data-task="{{ $__task->id }}" data-student="{{ $filterSid }}" {{ $__done ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer;">
        <span style="flex:1;font-size:var(--tx-sm);{{ $__done ? 'text-decoration:line-through;color:var(--u-muted)' : 'color:var(--u-text)' }}">{{ $__task->label_tr }}</span>
        @if($__task->is_required)<span style="font-size:10px;color:#d97706;font-weight:700;">Zorunlu</span>@endif
    </label>
    @endforeach
</div>
@endif
<article class="panel">
    <h3 style="margin:0 0 14px;">🏠 Konut & Barınma</h3>

    @if($ptAccommodation)
    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--u-bg);border-radius:10px;margin-bottom:14px;">
        <div style="font-size:var(--tx-2xl);">{{ $ptAccommodation->booking_status === 'confirmed' ? '🏠' : ($ptAccommodation->booking_status === 'searching' ? '🔍' : '🔑') }}</div>
        <div>
            <div style="font-weight:700;font-size:var(--tx-sm);">{{ \App\Models\StudentAccommodation::TYPE_LABELS[$ptAccommodation->type] ?? $ptAccommodation->type }}</div>
            <div style="margin-top:3px;">
                <span class="badge {{ $ptAccommodation->statusBadge() }}">{{ $ptAccommodation->statusLabel() }}</span>
                @if($ptAccommodation->city) <span class="muted" style="font-size:var(--tx-xs);">· {{ $ptAccommodation->city }}</span>@endif
                @if($ptAccommodation->monthly_cost_eur) <span class="muted" style="font-size:var(--tx-xs);">· €{{ number_format($ptAccommodation->monthly_cost_eur,0) }}/ay</span>@endif
            </div>
        </div>
    </div>
    <form method="POST" action="{{ route('senior.housing.update', $ptAccommodation->id) }}">
        @csrf @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Durum</label>
                <select name="booking_status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentAccommodation::STATUS_LABELS as $v => $l)
                        <option value="{{ $v }}" {{ $ptAccommodation->booking_status === $v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Konut Türü</label>
                <select name="type" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                    @foreach(\App\Models\StudentAccommodation::TYPE_LABELS as $v => $l)
                        <option value="{{ $v }}" {{ $ptAccommodation->type === $v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:span 2;">
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Adres</label>
                <input type="text" name="address" value="{{ $ptAccommodation->address }}" placeholder="Straße 12, Wohnung 3" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Şehir</label>
                <input type="text" name="city" value="{{ $ptAccommodation->city }}" placeholder="Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Aylık Kira (€)</label>
                <input type="number" name="monthly_cost_eur" value="{{ $ptAccommodation->monthly_cost_eur }}" min="0" step="0.01" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Taşınma Tarihi</label>
                <input type="date" name="move_in_date" value="{{ $ptAccommodation->move_in_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Ev Sahibi</label>
                <input type="text" name="landlord_name" value="{{ $ptAccommodation->landlord_name }}" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Telefon</label>
                <input type="text" name="landlord_phone" value="{{ $ptAccommodation->landlord_phone }}" placeholder="+49..." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
            </div>
        </div>
        <textarea name="notes" rows="2" placeholder="Not..." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;margin-bottom:8px;">{{ $ptAccommodation->notes }}</textarea>
        <div style="display:flex;align-items:center;gap:10px;">
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <input type="checkbox" name="utilities_included" value="1" {{ $ptAccommodation->utilities_included ? 'checked':'' }}> Faturalar dahil
            </label>
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <input type="checkbox" name="is_visible_to_student" value="1" {{ $ptAccommodation->is_visible_to_student ? 'checked':'' }}> Öğrenciye görünür
            </label>
            <button type="submit" style="background:#059669;color:#fff;border:none;border-radius:7px;padding:7px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Güncelle</button>
        </div>
    </form>
    @else
    <form method="POST" action="{{ route('senior.housing.store') }}">
        @csrf
        <input type="hidden" name="student_id" value="{{ $filterSid }}">
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
</div>{{-- /tab-ikamet --}}

{{-- ══ TAB 6: RESMİ HİZMETLER ══ --}}
<div id="tab-resmi">
@php $__defRs = $processDefinitions->firstWhere('code','official_services'); $__tasksRs = $__defRs ? ($tasksByStep[$__defRs->id] ?? collect()) : collect(); @endphp
@if($__tasksRs->isNotEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--u-muted);margin-bottom:10px;">Gorev Kontrol Listesi</div>
    @foreach($__tasksRs as $__task)
    @php $__done = isset($completedTaskIds[$__task->id]); @endphp
    <label style="display:flex;align-items:center;gap:8px;padding:7px 0;cursor:pointer;border-bottom:1px solid var(--u-line);">
        <input type="checkbox" class="pt-task-cb" data-task="{{ $__task->id }}" data-student="{{ $filterSid }}" {{ $__done ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer;">
        <span style="flex:1;font-size:var(--tx-sm);{{ $__done ? 'text-decoration:line-through;color:var(--u-muted)' : 'color:var(--u-text)' }}">{{ $__task->label_tr }}</span>
        @if($__task->is_required)<span style="font-size:10px;color:#d97706;font-weight:700;">Zorunlu</span>@endif
    </label>
    @endforeach
</div>
@else
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:32px;text-align:center;color:var(--u-muted);">
    <div style="font-size:32px;margin-bottom:8px;">🏛</div>
    <div style="font-size:var(--tx-sm);font-weight:700;margin-bottom:4px;">Resmi Hizmetler</div>
    <div style="font-size:var(--tx-sm);">Bu aşama için Manager panelinden görev şablonları ekleyebilirsiniz.</div>
    <a href="/manager/process-step-tasks" style="display:inline-block;margin-top:10px;font-size:var(--tx-xs);color:#7c3aed;text-decoration:none;font-weight:600;">Manager Paneli →</a>
</div>
@endif
</div>{{-- /tab-resmi --}}

@endif {{-- /filterSid --}}

<script>
const _ptCatalog = @json($institutionCatalog ?? []);

document.getElementById('pt-student-select')?.addEventListener('change', function () {
    const val = this.value;
    if (val) {
        window.location.href = '/senior/process-tracking?student_id=' + encodeURIComponent(val);
    } else {
        window.location.href = '/senior/process-tracking';
    }
});

function toggleSection(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function switchTab(name) {
    ['hazirlik','uni','vize','dil','ikamet','resmi'].forEach(function(t) {
        const panel = document.getElementById('tab-' + t);
        const card  = document.getElementById('pt-tab-' + t);
        if (!panel || !card) return;
        const active = t === name;
        panel.style.display = active ? '' : 'none';
        // active card: purple border + subtle purple tint
        if (active) {
            card.style.borderColor = '#7c3aed';
            card.style.boxShadow   = '0 0 0 3px rgba(124,58,237,.12)';
        } else {
            card.style.borderColor = card.dataset.origBorder || 'var(--u-line)';
            card.style.boxShadow   = '';
        }
    });
    try { sessionStorage.setItem('pt-tab-{{ $filterSid }}', name); } catch(e) {}
}
// Cache original border colors
document.querySelectorAll('[id^="pt-tab-"]').forEach(function(c) {
    c.dataset.origBorder = c.style.borderColor;
});
(function(){
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    let t = urlTab || 'hazirlik';
    try { t = urlTab || sessionStorage.getItem('pt-tab-{{ $filterSid }}') || 'hazirlik'; } catch(e) {}
    switchTab(t);
})();

// AJAX sub-task toggle
document.addEventListener('change', function(e) {
    const cb = e.target;
    if (!cb.classList.contains('pt-task-cb')) return;
    const taskId   = cb.dataset.task;
    const studentId= cb.dataset.student;
    fetch('/senior/process-tasks/' + taskId + '/toggle', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content || ''},
        body: JSON.stringify({student_id: studentId})
    }).then(r => r.json()).then(data => {
        const label = cb.closest('label');
        const span  = label ? label.querySelector('span') : null;
        if (span) {
            span.style.textDecoration = data.completed ? 'line-through' : '';
            span.style.color = data.completed ? 'var(--u-muted)' : 'var(--u-text)';
        }
    }).catch(() => { cb.checked = !cb.checked; });
});

function ptFillDocTypes(catKey) {
    const sel   = document.getElementById('pt-type-select');
    const label = document.getElementById('pt-type-label');
    const docs  = _ptCatalog[catKey]?.documents ?? {};
    sel.innerHTML = '<option value="">Seçin...</option>';
    Object.entries(docs).forEach(([code, names]) => {
        const opt = document.createElement('option');
        opt.value = code;
        opt.textContent = code + ' — ' + (names.tr || names.de || '');
        opt.dataset.label = names.tr || names.de || code;
        sel.appendChild(opt);
    });
    label.value = '';
}

function ptSyncLabel(selectEl) {
    const lbl = document.getElementById('pt-type-label');
    const opt = selectEl.selectedOptions[0];
    lbl.value = opt?.dataset.label || selectEl.value;
}
</script>

@endsection

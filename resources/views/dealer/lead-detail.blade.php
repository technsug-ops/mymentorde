@extends('dealer.layouts.app')

@section('title', 'Lead Detay')
@section('page_title', 'Lead Detay')

@section('content')
    <section class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0 0 6px;">
                    #{{ $lead->id }} {{ $lead->first_name }} {{ $lead->last_name }}
                    @if(($lead->referral_type ?? '') === 'confirmed_referral')
                        <span style="font-size:12px;font-weight:700;padding:2px 10px;border-radius:20px;background:#dcfce7;color:#166534;vertical-align:middle;">Kesin Yönlendirme</span>
                    @elseif(($lead->referral_type ?? '') === 'recommendation')
                        <span style="font-size:12px;font-weight:700;padding:2px 10px;border-radius:20px;background:#fef9c3;color:#854d0e;vertical-align:middle;">Tavsiye</span>
                    @endif
                </h2>
                <div class="muted">Kayıt: {{ optional($lead->created_at)->format('d.m.Y H:i') }}</div>
                <div class="muted">email: {{ $lead->email }} | tel: {{ $lead->phone ?: '-' }}</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="btn" href="/dealer/leads">Listeye Dön</a>
                @if($lead->converted_student_id)
                    <a class="btn" target="_blank" href="/manager/preview/student/{{ urlencode($lead->converted_student_id) }}">Öğrenci Önizleme</a>
                @endif
            </div>
        </div>
    </section>

    @php
        $ldBadge = match($lead->lead_status ?? '') { 'new'=>'info','contacted'=>'warn','qualified'=>'badge','converted'=>'ok','lost'=>'danger',default=>'' };
        $ldLabel = match($lead->lead_status ?? '') { 'new'=>'Yeni','contacted'=>'İletişimde','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu',default=>($lead->lead_status ?: '–') };
        $csBadge = match($lead->contract_status ?? '') { 'not_requested'=>'badge','requested'=>'warn','sent'=>'info','signed'=>'info','approved'=>'ok','rejected'=>'danger',default=>'' };
        $csLabel = match($lead->contract_status ?? '') { 'not_requested'=>'Talep Edilmedi','requested'=>'Talep Edildi','sent'=>'Gönderildi','signed'=>'İmzalandı','approved'=>'Onaylandı','rejected'=>'Reddedildi',default=>($lead->contract_status ?: '–') };
    @endphp
    <div class="grid4">
        <div class="panel"><div class="muted">Lead Durumu</div><div style="margin-top:6px;"><span class="badge {{ $ldBadge }}">{{ $ldLabel }}</span></div></div>
        <div class="panel"><div class="muted">Paket</div><div class="kpi" style="font-size:var(--tx-xl);">{{ $lead->selected_package_code ?: '–' }}</div></div>
        <div class="panel"><div class="muted">Sözleşme</div><div style="margin-top:6px;"><span class="badge {{ $csBadge }}">{{ $csLabel }}</span></div></div>
        <div class="panel"><div class="muted">Dönüşen Öğrenci</div><div class="kpi" style="font-size:var(--tx-xl);">{{ $lead->converted_student_id ?: '–' }}</div></div>
    </div>

    {{-- 4 Aşama Milestone --}}
    @if(isset($canViewProcessDetails) && !$canViewProcessDetails)
    <section class="card" style="border:1.5px dashed #cbd5e1;">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;color:#64748b;">
            <span style="font-size:24px;">🔒</span>
            <div>
                <strong style="display:block;color:#374151;">Süreç Aşamaları — Freelance Danışman (T2) ile Açılır</strong>
                <span style="font-size:12px;color:#94a3b8;">Öğrencinin üniversite kabulü, vize ve süreç milestone'larını T2 yetkisiyle takip edebilirsin.</span>
            </div>
        </div>
    </section>
    @else
    <section class="card">
        <h2>Süreç Aşamaları (4 Ana Aşama)</h2>
        @php
            $leadStatus = (string) ($lead->lead_status ?? 'new');
            $isConverted = !empty($lead->converted_student_id);

            // Milestone tespiti: lead_status + commission verisi
            $milestoneKeys = ['DM-001','DM-002','DM-003','DM-004'];
            $milestoneLabels = [
                'DM-001' => ['icon' => '✅', 'name' => 'Kayıt Oldu',           'trigger' => 'guest_converted'],
                'DM-002' => ['icon' => '🎓', 'name' => 'Üniversite Kabulü Aldı','trigger' => 'university_accepted'],
                'DM-003' => ['icon' => '🛂', 'name' => 'Vize Kabulü Aldı',      'trigger' => 'visa_approved'],
                'DM-004' => ['icon' => '✅', 'name' => 'Süreç Tamamlandı',      'trigger' => 'process_completed'],
            ];

            $commissionProgress = [];
            if ($commissionRevenue && is_array($commissionRevenue->milestone_progress)) {
                $commissionProgress = $commissionRevenue->milestone_progress;
            }

            // DM-001: converted_student_id varsa tamamlanmış say
            $milestonesDone = [
                'DM-001' => $isConverted || !empty($commissionProgress['DM-001']),
                'DM-002' => !empty($commissionProgress['DM-002']),
                'DM-003' => !empty($commissionProgress['DM-003']),
                'DM-004' => !empty($commissionProgress['DM-004']),
            ];
            $doneCount = count(array_filter($milestonesDone));
            $progressPct = round(($doneCount / 4) * 100);
        @endphp

        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($milestoneKeys as $i => $key)
                @php
                    $ml = $milestoneLabels[$key];
                    $done = $milestonesDone[$key];
                    $isNext = !$done && ($i === 0 || $milestonesDone[$milestoneKeys[$i - 1]]);
                @endphp
                <div class="panel" style="display:flex;align-items:center;gap:12px;
                    @if($done) border-color:var(--u-ok);background:#f0faf4; @elseif($isNext) border-color:var(--u-warn); @else opacity:.5; @endif">
                    <div style="font-size:var(--tx-xl);width:28px;text-align:center;">
                        @if($done) ✅ @elseif($isNext) 🔄 @else ⬜ @endif
                    </div>
                    <div>
                        <strong>{{ $i + 1 }}. {{ $ml['name'] }}</strong>
                        <div class="muted" style="font-size:var(--tx-xs);">
                            @if($done) Tamamlandı @elseif($isNext) Bekleniyor @else Henüz erişilmedi @endif
                        </div>
                    </div>
                    @if($done && $commissionRevenue)
                        @php
                            $commAmt    = $commissionProgress[$key . '_amount'] ?? null;
                            $commStatus = $commissionProgress[$key . '_paid'] ?? false;
                        @endphp
                        <div style="margin-left:auto;text-align:right;">
                            @if($commAmt)
                                <span class="muted">{{ number_format((float) $commAmt, 2, ',', '.') }} EUR</span>
                                @if($commStatus)
                                    <span class="badge ok" style="margin-left:4px;">Ödendi</span>
                                @else
                                    <span class="badge warn" style="margin-left:4px;">Bekleyen</span>
                                @endif
                            @else
                                <span class="badge info">Komisyon hesaplanıyor</span>
                            @endif
                        </div>
                    @elseif($done && !$commissionRevenue)
                        <div style="margin-left:auto;">
                            <span class="badge info">Komisyon hesaplanıyor</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div style="margin-top:12px;">
            <div class="muted" style="margin-bottom:4px;">İlerleme: {{ $doneCount }}/4 aşama ({{ $progressPct }}%)</div>
            <div style="background:var(--u-line);border-radius:4px;height:8px;overflow:hidden;">
                <div style="background:var(--u-ok);height:100%;width:{{ $progressPct }}%;transition:width .3s;"></div>
            </div>
        </div>

    </section>
    @endif {{-- canViewProcessDetails --}}

    {{-- Lead Güncelleme Paneli --}}
    @if(session('status'))
        <div style="background:#dcfce7;border:1px solid #86efac;padding:10px 16px;border-radius:8px;font-size:13px;color:#15803d;margin-bottom:12px;">
            ✅ {{ session('status') }}
        </div>
    @endif
    <section class="panel" style="margin-bottom:14px;">
        <h2 style="margin-bottom:14px;">📋 Lead Durumu Güncelle</h2>
        <form method="POST" action="/dealer/leads/{{ $lead->id }}/qualification">
            @csrf
            <div class="grid3" style="margin-bottom:10px;">
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Lead Durumu</label>
                    <select name="lead_status" style="width:100%;height:34px;border-radius:7px;font-size:13px;padding:0 8px;">
                        @foreach(['new'=>'Yeni','contacted'=>'İletişimde','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu'] as $k=>$v)
                            <option value="{{ $k }}" @selected(($lead->lead_status??'new')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Nitelik Skoru</label>
                    <select name="qualification_status" style="width:100%;height:34px;border-radius:7px;font-size:13px;padding:0 8px;">
                        <option value="">— Seçilmedi —</option>
                        @foreach(['unqualified'=>'❄️ Niteliksiz','warm'=>'🌤 Ilık','hot'=>'🔥 Sıcak','qualified'=>'✅ Nitelikli'] as $k=>$v)
                            <option value="{{ $k }}" @selected(($lead->qualification_status??'')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">📅 Takip Tarihi</label>
                    <input type="date" name="follow_up_date"
                           value="{{ $lead->follow_up_date ? $lead->follow_up_date->format('Y-m-d') : '' }}"
                           style="width:100%;height:34px;border-radius:7px;font-size:13px;padding:0 8px;">
                </div>
            </div>
            <div class="grid2" id="lost-fields" style="margin-bottom:10px;{{ ($lead->lead_status??'')!=='lost' ? 'display:none;' : '' }}">
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Kayıp Nedeni</label>
                    <select name="lost_reason" style="width:100%;height:34px;border-radius:7px;font-size:13px;padding:0 8px;">
                        <option value="">— Seçin —</option>
                        @foreach(['no_response'=>'Yanıt Vermedi','chose_competitor'=>'Rakip Seçti','budget'=>'Bütçe Yetersiz','not_interested'=>'İlgisini Yitirdi','timing'=>'Zamanlama Uygun Değil','other'=>'Diğer'] as $k=>$v)
                            <option value="{{ $k }}" @selected(($lead->lost_reason??'')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Kayıp Notu</label>
                    <input type="text" name="lost_note" value="{{ $lead->lost_note ?? '' }}"
                           placeholder="İsteğe bağlı açıklama…"
                           style="width:100%;height:34px;border-radius:7px;font-size:13px;padding:0 8px;">
                </div>
            </div>
            <button type="submit" class="btn ok" style="padding:8px 20px;">💾 Güncelle</button>
        </form>
        <script>
        document.querySelector('[name="lead_status"]').addEventListener('change', function(){
            document.getElementById('lost-fields').style.display = this.value === 'lost' ? '' : 'none';
        });
        </script>
    </section>

    <div class="grid2">
        <section class="panel">
            <h2>Lead Özet</h2>
            <div class="list">
                <div class="item"><strong>Talep Tipi:</strong> {{ $lead->application_type ?: '-' }}</div>
                <div class="item"><strong>Ülke:</strong> {{ $lead->application_country ?: '-' }}</div>
                <div class="item"><strong>Kanal:</strong> {{ $lead->lead_source ?: '-' }}</div>
                <div class="item"><strong>Branch:</strong> {{ $lead->branch ?: '-' }}</div>
                <div class="item"><strong>Risk:</strong> {{ $lead->risk_level ?: '-' }} | <strong>Öncelik:</strong> {{ $lead->priority ?: '-' }}</div>
                <div class="item"><strong>Eğitim Danışmanı:</strong> {{ $lead->assigned_senior_email ?: '-' }}</div>
                <div class="item"><strong>UTM:</strong> {{ $lead->utm_source ?: '-' }} / {{ $lead->utm_medium ?: '-' }} / {{ $lead->utm_campaign ?: '-' }}</div>
                <div class="item"><strong>Not:</strong><br><span class="muted">{{ $lead->notes ?: '-' }}</span></div>
            </div>
        </section>

        <section class="panel">
            <h2>Otomasyon Durumu</h2>
            <div class="list">
                <div class="item"><strong>Ticket:</strong> {{ $tickets->count() }}</div>
                <div class="item"><strong>Task:</strong> {{ $tasks->count() }}</div>
                <div class="item"><strong>Event:</strong> {{ $events->count() }}</div>
                <div class="item"><strong>Bildirim Kuyruğu:</strong> {{ $notifications->count() }}</div>
            </div>
            <div class="panel muted" style="margin-top:8px;">
                Lead oluşturulduğunda operasyon ticketi + task tetiklenmesi beklenir. Bu panel dealer tarafında takip kolaylığı için audit özeti verir.
            </div>
        </section>
    </div>

    <div class="grid2">
        <section class="panel">
            <h2>Ticketlar ({{ $tickets->count() }})</h2>
            <div class="list">
                @forelse($tickets as $ticket)
                    <div class="item">
                        <strong>#{{ $ticket->id }} {{ $ticket->subject }}</strong>
                        @php
                            $tkSt  = match($ticket->status ?? '') { 'open'=>'Açık','in_progress'=>'İşlemde','closed'=>'Kapalı','resolved'=>'Çözüldü',default=>($ticket->status ?: '–') };
                            $tkPr  = match($ticket->priority ?? '') { 'low'=>'Düşük','normal'=>'Normal','high'=>'Yüksek',default=>($ticket->priority ?: '–') };
                        @endphp
                        <span class="muted"> | {{ $ticket->department ?: '–' }} | {{ $tkSt }} | {{ $tkPr }}</span><br>
                        <span class="muted">{{ optional($ticket->created_at)->format('Y-m-d H:i') }}</span>
                        @if($ticket->replies->isNotEmpty())
                            <div class="muted" style="margin-top:6px;">son yanıt: {{ Str::limit((string) optional($ticket->replies->last())->message, 100) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="muted" style="padding:10px 0;text-align:center;">Ticket yok.</div>
                @endforelse
            </div>
        </section>

        <section class="panel">
            <h2>Tasklar ({{ $tasks->count() }})</h2>
            <div class="list">
                @forelse($tasks as $task)
                    <div class="item">
                        <strong>#{{ $task->id }} {{ $task->title }}</strong>
                        @php
                            $tskSt = match($task->status ?? '') { 'open'=>'Açık','todo'=>'Yapılacak','in_progress'=>'Devam Ediyor','done'=>'Tamamlandı','completed'=>'Tamamlandı','closed'=>'Kapalı',default=>($task->status ?: '–') };
                            $tskPr = match($task->priority ?? '') { 'low'=>'Düşük','normal'=>'Normal','high'=>'Yüksek',default=>($task->priority ?: '–') };
                        @endphp
                        <span class="muted"> | {{ $task->department ?: '–' }} | {{ $tskSt }} | {{ $tskPr }}</span><br>
                        <span class="muted">kaynak: {{ $task->source_type }} / {{ $task->source_id }} | vade: {{ optional($task->due_date)->format('Y-m-d') ?: '–' }}</span>
                    </div>
                @empty
                    <div class="muted" style="padding:10px 0;text-align:center;">Task yok.</div>
                @endforelse
            </div>
        </section>
    </div>

    @if(!isset($canViewProcessDetails) || $canViewProcessDetails)
    <section class="card">
        <h2>Timeline</h2>
        <div class="list">
            @forelse($timeline as $row)
                <div class="item">
                    @php
                        $tlTypeMap = ['ticket'=>'Ticket','task'=>'Task','event'=>'Olay','note'=>'Not','document'=>'Belge','status'=>'Durum','convert'=>'Dönüşüm','assign'=>'Atama'];
                        $tlType = $tlTypeMap[strtolower((string)($row['type'] ?? ''))] ?? strtoupper((string)($row['type'] ?? '-'));
                    @endphp
                    <strong>[{{ $tlType }}] {{ $row['title'] ?? '-' }}</strong>
                    <span class="muted"> | {{ optional($row['when'] ?? null)->format('Y-m-d H:i:s') ?: '-' }}</span><br>
                    <span class="muted">{{ $row['meta'] ?? '-' }}</span>
                </div>
            @empty
                <div class="muted" style="padding:10px 0;text-align:center;">Timeline verisi yok.</div>
            @endforelse
        </div>
    </section>
    @endif {{-- canViewProcessDetails --}}

    {{-- Gelen Belgeler (sadece is_visible_to_dealer = true olanlar) --}}
    @if(isset($canViewDocuments) && !$canViewDocuments)
    <section class="card" style="border:1.5px dashed #cbd5e1;margin-top:10px;">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;color:#64748b;">
            <span style="font-size:24px;">🔒</span>
            <div>
                <strong style="display:block;color:#374151;">Belgeler — B2B Partner (T3) ile Açılır</strong>
                <span style="font-size:12px;color:#94a3b8;">Öğrenciye ait kurumsal belgeler ve üniversite başvuru dokümanları T3 yetkisiyle görüntülenebilir.</span>
            </div>
        </div>
    </section>
    @elseif(isset($institutionDocs) && $institutionDocs->isNotEmpty())
    <section class="card" style="margin-top:10px;">
        <h2>Gelen Belgeler</h2>
        <div class="muted" style="font-size:var(--tx-xs);margin-bottom:8px;">Danışman tarafından dealer görünürlüğüne açılan belgeler</div>
        <div class="list">
            @foreach($institutionDocs as $idoc)
                @php
                    $catInfo  = $institutionCatalog[$idoc->institution_category] ?? [];
                    $catLabel = $catInfo['label_tr'] ?? $idoc->institution_category;
                    $catIcon  = $catInfo['icon'] ?? '';
                    $statusMap = ['expected'=>'Bekleniyor','received'=>'Alındı','action_required'=>'Aksiyon','completed'=>'Tamamlandı','archived'=>'Arşiv'];
                    $statusLabel = $statusMap[$idoc->status] ?? $idoc->status;
                @endphp
                <div class="item">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                        <div>
                            <span style="font-size:var(--tx-xs);font-weight:700;background:#f3f7fe;border:1px solid #d6dfeb;border-radius:999px;padding:2px 7px;">
                                {{ $catIcon }} {{ $catLabel }}
                            </span>
                            <strong style="display:block;margin-top:4px;">{{ $idoc->document_type_label }}</strong>
                            @if($idoc->institution_name)
                                <span class="muted" style="font-size:var(--tx-xs);">{{ $idoc->institution_name }}</span>
                            @endif
                            @if($idoc->received_date)
                                <span class="muted" style="font-size:var(--tx-xs);"> · {{ $idoc->received_date }}</span>
                            @endif
                        </div>
                        <span class="badge {{ in_array($idoc->status, ['received','completed']) ? 'ok' : ($idoc->status === 'action_required' ? 'warn' : 'info') }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Üniversite Başvuruları (dealer görünürlüklü) --}}
    @if(isset($dealerUniApps) && $dealerUniApps->isNotEmpty())
    <section class="card" style="margin-top:10px;">
        <h2>🏛 Üniversite Başvuruları</h2>
        <div class="muted" style="font-size:var(--tx-xs);margin-bottom:8px;">Danışman tarafından sizinle paylaşılan başvuru bilgileri</div>
        <div class="list">
            @foreach($dealerUniApps as $ua)
            @php
                $uaBadge = \App\Models\StudentUniversityApplication::STATUS_BADGE[$ua->status] ?? 'info';
                $uaLabel = \App\Models\StudentUniversityApplication::STATUS_LABELS[$ua->status] ?? $ua->status;
                $uaDeg   = \App\Models\StudentUniversityApplication::DEGREE_LABELS[$ua->degree_type] ?? $ua->degree_type;
            @endphp
            <div class="item">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                    <div>
                        <strong>{{ $ua->university_name }}@if($ua->city) · <span style="font-weight:400;">{{ $ua->city }}</span>@endif</strong>
                        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">{{ $ua->department_name }} · {{ $uaDeg }}@if($ua->semester) · {{ $ua->semester }}@endif</div>
                        @if($ua->deadline)
                        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">Son Başvuru: {{ $ua->deadline->format('d.m.Y') }}@if($ua->result_at) · Sonuç: {{ $ua->result_at->format('d.m.Y') }}@endif</div>
                        @endif
                    </div>
                    <span class="badge {{ $uaBadge }}">{{ $uaLabel }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    @include('dealer._partials.usage-guide', [
        'items' => [
            '4 aşama görseli lead\'in komisyon mihenk taşlarını (Kayıt, Üniversite Kabulü, Vize, Tamamlandı) gösterir.',
            'Komisyon tutarları milestone_progress verisi varsa aşama bazlı görünür.',
            'Timeline alanından otomasyonların hangi sırayla tetiklendiğini izleyebilirsin.',
            'Dönüşen öğrenci varsa Öğrenci Önizleme butonundan manager önizleme ekranına geçilebilir.',
            '"Gelen Belgeler" bölümü, danışman tarafından sizinle paylaşılan kurumsal belgeleri gösterir.',
        ]
    ])
@endsection

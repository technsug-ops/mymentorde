{{-- Paylaşımlı aktivite/görüşme günlüğü — aday öğrenci + dönüşmüş öğrenci.
     $ctx: storeAction, updateRoute, archiveRoute, attachRoute, baseUrl, showArchived, archivedCount
     $notes: InternalNote koleksiyonu --}}
@php
    $ctx           = $ctx ?? ($activityCtx ?? []);
    $showArchived  = $ctx['showArchived'] ?? false;
    $archivedCount = $ctx['archivedCount'] ?? 0;
    $actIcons      = ['meeting'=>'📅','call'=>'📞','whatsapp'=>'💬','email'=>'✉️','note'=>'📝','document'=>'📎','general'=>'•'];
    $actTypes      = ['meeting'=>'📅 Görüşme','call'=>'📞 Telefon','whatsapp'=>'💬 WhatsApp','email'=>'✉️ E-posta','note'=>'📝 Not','general'=>'• Genel'];
    $prios         = ['low'=>'Düşük','medium'=>'Normal','high'=>'Yüksek'];

    // Bekleyen takipler: aktif kayıtlardan follow_up_date'i olanlar (tarihe göre).
    $today     = \Illuminate\Support\Carbon::today();
    $followups = collect($notes ?? [])
        ->filter(fn($n) => empty($n->archived_at) && !empty($n->follow_up_date))
        ->sortBy(fn($n) => $n->follow_up_date)
        ->values();
@endphp
<div class="panel" style="margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <h2 style="display:flex;align-items:center;gap:8px;margin:0;">📋 Aktivite &amp; Görüşme Günlüğü</h2>
        @if($showArchived)
            <a class="btn" href="{{ $ctx['baseUrl'] }}">← Aktif kayıtlar</a>
        @else
            <a class="btn" href="{{ $ctx['baseUrl'] }}?archived=1">🗄 Arşiv ({{ $archivedCount }})</a>
        @endif
    </div>

    @if(session('status'))<div class="badge ok" style="display:block;margin:10px 0;padding:8px 10px;">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="badge danger" style="display:block;margin:10px 0;padding:8px 10px;">{{ $errors->first() }}</div>@endif

    @unless($showArchived)
    {{-- Bekleyen Takipler — hatırlatma tarihi olan aktiviteler --}}
    @if($followups->isNotEmpty())
    <div style="margin:12px 0;border:1px solid #f0c9a0;border-radius:10px;padding:10px 12px;background:#fff8f0;">
        <div style="font-weight:700;font-size:var(--tx-sm);color:#b45309;margin-bottom:6px;">⏰ Bekleyen Takipler ({{ $followups->count() }})</div>
        <div style="display:grid;gap:6px;">
            @foreach($followups as $f)
                @php
                    $d        = $f->follow_up_date instanceof \Carbon\Carbon ? $f->follow_up_date : \Illuminate\Support\Carbon::parse($f->follow_up_date);
                    $overdue  = $d->lt($today);
                    $isToday  = $d->isSameDay($today);
                    $color    = $overdue ? '#dc2626' : ($isToday ? '#b45309' : '#475569');
                    $dayLabel = $overdue ? ('⚠ ' . $d->format('d.m.Y') . ' (gecikti)') : ($isToday ? ('● ' . $d->format('d.m.Y') . ' (bugün)') : $d->format('d.m.Y'));
                @endphp
                <a href="#act-{{ $f->id }}" style="display:flex;gap:8px;align-items:baseline;text-decoration:none;color:inherit;">
                    <span style="font-weight:700;font-size:var(--tx-xs);color:{{ $color }};white-space:nowrap;min-width:140px;">{{ $dayLabel }}</span>
                    <span style="font-size:var(--tx-xs);color:var(--u-text,#0f172a);">{{ \Illuminate\Support\Str::limit($f->next_step ?: $f->content, 90) }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Yeni aktivite ekle --}}
    <form method="POST" action="{{ $ctx['storeAction'] }}" enctype="multipart/form-data"
          style="display:grid;gap:8px;margin:12px 0 16px;border:1px solid var(--u-line,#e2e8f0);border-radius:10px;padding:12px;background:var(--u-bg,#f8fafc);">
        @csrf
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <select name="activity_type" style="flex:1;min-width:140px;">
                @foreach($actTypes as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <select name="priority" style="min-width:120px;">
                @foreach($prios as $k => $label)<option value="{{ $k }}" @selected($k==='medium')>{{ $label }}</option>@endforeach
            </select>
        </div>
        <textarea name="content" required rows="3" placeholder="Görüşme/aktivite raporu... (ne konuşuldu, sonuç)" style="width:100%;"></textarea>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <input type="text" name="next_step" maxlength="500" placeholder="➡️ Sonraki adım (ne yapılacak?)" style="flex:1;min-width:200px;">
            <label style="font-size:var(--tx-xs);color:var(--u-muted);display:flex;align-items:center;gap:6px;">⏰ Hatırlatma:
                <input type="date" name="follow_up_date">
            </label>
            <label style="font-size:var(--tx-xs);color:var(--u-muted);display:flex;align-items:center;gap:6px;cursor:pointer;" title="İşaretlersen öğrenci bu notu kendi panelinde görür">
                <input type="checkbox" name="visible_to_student" value="1"> 👁 Öğrenciye görünür
            </label>
        </div>
        <label style="font-size:var(--tx-xs);color:var(--u-muted);">📎 Görsel ekle (opsiyonel, en fazla 6 — JPG/PNG/WebP):
            <input type="file" name="images[]" accept="image/*" multiple>
        </label>
        <div><button type="submit" class="btn" style="background:#7e58bf;color:#fff;">Aktivite Kaydet</button></div>
    </form>
    @endunless

    {{-- Timeline --}}
    @forelse(($notes ?? []) as $note)
    <div id="act-{{ $note->id }}" style="border-left:3px solid {{ $note->archived_at ? '#94a3b8' : '#7e58bf' }};padding:8px 0 12px 12px;margin-bottom:8px;">
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <span>{{ $actIcons[$note->category] ?? '•' }}</span>
            <span class="badge">{{ $note->category }}</span>
            <span class="badge {{ $note->priority === 'high' ? 'danger' : ($note->priority === 'medium' ? 'warn' : '') }}">{{ $note->priority }}</span>
            @if($note->archived_at)<span class="badge">🗄 arşiv</span>@endif
            @if(!empty($note->is_visible_to_student))<span class="badge" style="background:#dcfce7;color:#15803d;">👁 öğrenciye görünür</span>@endif
            <span class="muted" style="font-size:var(--tx-xs);margin-left:auto;">{{ $note->created_at?->format('d.m.Y H:i') }}</span>
        </div>
        @if($note->content)
        <div style="font-size:var(--tx-sm);margin-top:6px;white-space:pre-wrap;">{{ $note->content }}</div>
        @endif
        @if(!empty($note->next_step) || !empty($note->follow_up_date))
        @php
            $fd = !empty($note->follow_up_date)
                ? ($note->follow_up_date instanceof \Carbon\Carbon ? $note->follow_up_date : \Illuminate\Support\Carbon::parse($note->follow_up_date))
                : null;
            $fOverdue = $fd && !$note->archived_at && $fd->lt($today);
        @endphp
        <div style="margin-top:6px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;font-size:var(--tx-xs);">
            @if(!empty($note->next_step))
                <span style="background:#f1edfa;border:1px solid #d8cdf0;border-radius:6px;padding:3px 8px;color:#5b3fa0;">➡️ {{ $note->next_step }}</span>
            @endif
            @if($fd)
                <span style="border-radius:6px;padding:3px 8px;font-weight:700;{{ $fOverdue ? 'background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;' : 'background:#fff8f0;border:1px solid #f0c9a0;color:#b45309;' }}">⏰ {{ $fd->format('d.m.Y') }}{{ $fOverdue ? ' (gecikti)' : '' }}</span>
            @endif
        </div>
        @endif
        @if(!empty($note->attachments) && is_array($note->attachments))
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
            @foreach($note->attachments as $i => $att)
                @if(!empty($att['path']))
                <a href="{{ route($ctx['attachRoute'], [$note->id, $i]) }}" target="_blank" rel="noopener">
                    <img src="{{ route($ctx['attachRoute'], [$note->id, $i]) }}" alt="{{ $att['name'] ?? 'görsel' }}"
                         style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid var(--u-line,#e2e8f0);">
                </a>
                @endif
            @endforeach
        </div>
        @endif
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px;">
            <span class="muted" style="font-size:var(--tx-xs);">{{ $note->created_by }}</span>
            <div style="margin-left:auto;display:flex;gap:6px;align-items:center;">
                @if($note->archived_at)
                    <form method="POST" action="{{ route($ctx['archiveRoute'], $note->id) }}" style="margin:0;">
                        @csrf <input type="hidden" name="unarchive" value="1">
                        <button class="btn" type="submit" style="padding:4px 10px;font-size:12px;">↩ Arşivden çıkar</button>
                    </form>
                @else
                    <details style="display:inline-block;">
                        <summary class="btn" style="padding:4px 10px;font-size:12px;cursor:pointer;list-style:none;">✎ Düzenle</summary>
                        <form method="POST" action="{{ route($ctx['updateRoute'], $note->id) }}"
                              style="display:grid;gap:6px;margin-top:6px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:8px;min-width:260px;">
                            @csrf
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <select name="activity_type" style="flex:1;min-width:120px;">
                                    @foreach($actTypes as $k => $label)<option value="{{ $k }}" @selected($note->category===$k)>{{ $label }}</option>@endforeach
                                </select>
                                <select name="priority">
                                    @foreach($prios as $k => $label)<option value="{{ $k }}" @selected($note->priority===$k)>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <textarea name="content" required rows="3" style="width:100%;">{{ $note->content }}</textarea>
                            <input type="text" name="next_step" maxlength="500" placeholder="➡️ Sonraki adım" value="{{ $note->next_step }}" style="width:100%;">
                            <label style="font-size:11px;color:var(--u-muted);display:flex;align-items:center;gap:6px;">⏰ Hatırlatma:
                                <input type="date" name="follow_up_date" value="{{ $note->follow_up_date ? ($note->follow_up_date instanceof \Carbon\Carbon ? $note->follow_up_date->format('Y-m-d') : \Illuminate\Support\Carbon::parse($note->follow_up_date)->format('Y-m-d')) : '' }}">
                            </label>
                            <label style="font-size:11px;color:var(--u-muted);display:flex;align-items:center;gap:6px;cursor:pointer;">
                                <input type="checkbox" name="visible_to_student" value="1" @checked(!empty($note->is_visible_to_student))> 👁 Öğrenciye görünür
                            </label>
                            <button class="btn" type="submit" style="background:#7e58bf;color:#fff;padding:4px 10px;font-size:12px;">Kaydet</button>
                        </form>
                    </details>
                    <form method="POST" action="{{ route($ctx['archiveRoute'], $note->id) }}" style="margin:0;" onsubmit="return confirm('Bu aktivite arşive alınsın mı? (silinmez, geri alınabilir)')">
                        @csrf
                        <button class="btn" type="submit" style="padding:4px 10px;font-size:12px;">🗄 Arşivle</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">{{ $showArchived ? 'Arşivde kayıt yok.' : 'Henüz aktivite kaydı yok. İlk görüşme notunu ekle.' }}</div>
    @endforelse
</div>

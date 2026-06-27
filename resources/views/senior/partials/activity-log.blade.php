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
        <textarea name="content" required rows="3" placeholder="Görüşme/aktivite raporu... (ne konuşuldu, sonuç, sonraki adım)" style="width:100%;"></textarea>
        <label style="font-size:var(--tx-xs);color:var(--u-muted);">📎 Görsel ekle (opsiyonel, en fazla 6 — JPG/PNG/WebP):
            <input type="file" name="images[]" accept="image/*" multiple>
        </label>
        <div><button type="submit" class="btn" style="background:#7e58bf;color:#fff;">Aktivite Kaydet</button></div>
    </form>
    @endunless

    {{-- Timeline --}}
    @forelse(($notes ?? []) as $note)
    <div style="border-left:3px solid {{ $note->archived_at ? '#94a3b8' : '#7e58bf' }};padding:8px 0 12px 12px;margin-bottom:8px;">
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <span>{{ $actIcons[$note->category] ?? '•' }}</span>
            <span class="badge">{{ $note->category }}</span>
            <span class="badge {{ $note->priority === 'high' ? 'danger' : ($note->priority === 'medium' ? 'warn' : '') }}">{{ $note->priority }}</span>
            @if($note->archived_at)<span class="badge">🗄 arşiv</span>@endif
            <span class="muted" style="font-size:var(--tx-xs);margin-left:auto;">{{ $note->created_at?->format('d.m.Y H:i') }}</span>
        </div>
        @if($note->content)
        <div style="font-size:var(--tx-sm);margin-top:6px;white-space:pre-wrap;">{{ $note->content }}</div>
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

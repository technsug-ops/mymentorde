{{-- ── Aktivite & Görüşme Günlüğü (rapor + görsel + timeline) — aday öğrenci ── --}}
<div class="panel" style="margin-bottom:16px;">
    <h2 style="display:flex;align-items:center;gap:8px;">📋 Aktivite &amp; Görüşme Günlüğü</h2>

    @if (session('status'))
        <div class="badge ok" style="display:block;margin-bottom:10px;padding:8px 10px;">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="badge danger" style="display:block;margin-bottom:10px;padding:8px 10px;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('senior.guest.activity.store', $guest->id) }}" enctype="multipart/form-data"
          style="display:grid;gap:8px;margin-bottom:16px;border:1px solid var(--u-line,#e2e8f0);border-radius:10px;padding:12px;background:var(--u-bg,#f8fafc);">
        @csrf
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <select name="activity_type" style="flex:1;min-width:140px;">
                <option value="meeting">📅 Görüşme</option>
                <option value="call">📞 Telefon</option>
                <option value="whatsapp">💬 WhatsApp</option>
                <option value="email">✉️ E-posta</option>
                <option value="note">📝 Not</option>
                <option value="general">• Genel</option>
            </select>
            <select name="priority" style="min-width:120px;">
                <option value="low">Düşük</option>
                <option value="medium" selected>Normal</option>
                <option value="high">Yüksek</option>
            </select>
        </div>
        <textarea name="content" required rows="3" placeholder="Görüşme/aktivite raporu... (ne konuşuldu, sonuç, sonraki adım)" style="width:100%;"></textarea>
        <label style="font-size:var(--tx-xs);color:var(--u-muted);">📎 Görsel ekle (opsiyonel, en fazla 6 — JPG/PNG/WebP):
            <input type="file" name="images[]" accept="image/*" multiple>
        </label>
        <div><button type="submit" class="btn" style="background:#7e58bf;color:#fff;">Aktivite Kaydet</button></div>
    </form>

    @php $actIcons = ['meeting'=>'📅','call'=>'📞','whatsapp'=>'💬','email'=>'✉️','note'=>'📝','document'=>'📎','general'=>'•']; @endphp
    @forelse(($notes ?? []) as $note)
    <div style="border-left:3px solid #7e58bf;padding:8px 0 12px 12px;margin-bottom:4px;">
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <span>{{ $actIcons[$note->category] ?? '•' }}</span>
            <span class="badge">{{ $note->category }}</span>
            <span class="badge {{ $note->priority === 'high' ? 'danger' : ($note->priority === 'medium' ? 'warn' : '') }}">{{ $note->priority }}</span>
            <span class="muted" style="font-size:var(--tx-xs);margin-left:auto;">{{ $note->created_at?->format('d.m.Y H:i') }}</span>
        </div>
        @if($note->content)
        <div style="font-size:var(--tx-sm);margin-top:6px;white-space:pre-wrap;">{{ $note->content }}</div>
        @endif
        @if(!empty($note->attachments) && is_array($note->attachments))
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
            @foreach($note->attachments as $i => $att)
                @if(!empty($att['path']))
                <a href="{{ route('senior.guest.activity.attachment', [$note->id, $i]) }}" target="_blank" rel="noopener">
                    <img src="{{ route('senior.guest.activity.attachment', [$note->id, $i]) }}" alt="{{ $att['name'] ?? 'görsel' }}"
                         style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid var(--u-line,#e2e8f0);">
                </a>
                @endif
            @endforeach
        </div>
        @endif
        <div class="muted" style="font-size:var(--tx-xs);margin-top:4px;">{{ $note->created_by }}</div>
    </div>
    @empty
    <div class="muted" style="padding:8px 0;">Henüz aktivite kaydı yok. İlk görüşme notunu ekle.</div>
    @endforelse
</div>

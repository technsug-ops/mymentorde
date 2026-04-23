{{--
  Lead Action Bar — guest veya student için hızlı aksiyon butonları.
  Props:
    - target: App\Models\GuestApplication veya App\Models\User (student)
    - type: 'guest' veya 'student'
--}}
@props(['target', 'type' => 'guest'])

@php
    $phone = $target->phone ?? '';
    $email = $target->email ?? '';
    $firstName = $target->first_name ?? (explode(' ', $target->name ?? '')[0] ?? '');
    $targetId = $target->id;

    // Phone numarasını temizle (WhatsApp linki için)
    $phoneClean = $phone ? preg_replace('/[^0-9+]/', '', $phone) : '';
    if ($phoneClean && !str_starts_with($phoneClean, '+')) {
        $phoneClean = '+' . $phoneClean;
    }

    // Varsayılan WhatsApp mesajı
    $defaultWhatsappText = rawurlencode("Merhaba {$firstName}, " . config('brand.name', 'MentorDE') . " ekibinden yazıyorum.");

    // Varsayılan email subject/body
    $defaultMailSubject = rawurlencode('Merhaba ' . $firstName);
    $defaultMailBody = rawurlencode("Merhaba {$firstName},\n\n");
@endphp

<style>
.lab-wrap { display:flex; gap:8px; flex-wrap:wrap; margin:10px 0 16px; }
.lab-btn {
    display:inline-flex; align-items:center; gap:6px; padding:8px 14px;
    border:1px solid #e2e8f0; border-radius:8px; background:#fff;
    color:#334155; font-size:12px; font-weight:600; text-decoration:none; cursor:pointer;
    transition:all .15s;
}
.lab-btn:hover { background:#f8fafc; border-color:#cbd5e1; transform:translateY(-1px); }
.lab-btn.primary { background:#5b2e91; color:#fff; border-color:#5b2e91; }
.lab-btn.primary:hover { background:#4c2478; }
.lab-btn.call    { border-color:#16a34a; color:#16a34a; }
.lab-btn.call:hover    { background:#dcfce7; }
.lab-btn.whatsapp { border-color:#22c55e; color:#16a34a; }
.lab-btn.whatsapp:hover { background:#dcfce7; }
.lab-btn.email   { border-color:#2563eb; color:#2563eb; }
.lab-btn.email:hover   { background:#dbeafe; }
.lab-btn.warn    { border-color:#f59e0b; color:#92400e; }
.lab-btn.warn:hover    { background:#fef3c7; }
.lab-btn[disabled] { opacity:.4; cursor:not-allowed; }

/* Modal */
.lab-modal { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; display:none; align-items:center; justify-content:center; padding:20px; }
.lab-modal.open { display:flex; }
.lab-modal-inner { background:#fff; border-radius:12px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; padding:24px; }
.lab-modal-inner h3 { margin:0 0 14px; font-size:16px; color:#0f172a; }
.lab-modal-inner .close-x { float:right; background:none; border:none; font-size:20px; cursor:pointer; color:#64748b; }
.lab-modal-inner label { display:block; margin:10px 0 4px; font-size:12px; color:#475569; font-weight:600; }
.lab-modal-inner input, .lab-modal-inner textarea, .lab-modal-inner select { width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; font-size:13px; font-family:inherit; }
.lab-modal-inner textarea { min-height:100px; resize:vertical; }
.lab-modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:16px; }
</style>

<div class="lab-wrap" data-lab-target-type="{{ $type }}" data-lab-target-id="{{ $targetId }}">
    @if ($phoneClean)
        <a href="tel:{{ $phoneClean }}" class="lab-btn call" data-lab-action="call">
            📞 Ara
        </a>
        <a href="https://wa.me/{{ ltrim($phoneClean, '+') }}?text={{ $defaultWhatsappText }}"
           target="_blank" rel="noopener"
           class="lab-btn whatsapp" data-lab-action="whatsapp">
            💬 WhatsApp
        </a>
    @else
        <button class="lab-btn call" disabled title="Telefon yok">📞 Ara</button>
        <button class="lab-btn whatsapp" disabled title="Telefon yok">💬 WhatsApp</button>
    @endif

    @if ($email)
        <a href="mailto:{{ $email }}?subject={{ $defaultMailSubject }}&body={{ $defaultMailBody }}"
           class="lab-btn email" data-lab-action="email">
            📧 Email
        </a>
    @endif

    <button type="button" class="lab-btn" data-lab-open="template-modal" data-lab-channel="whatsapp">
        📋 WhatsApp Şablonu
    </button>

    <button type="button" class="lab-btn" data-lab-open="template-modal" data-lab-channel="email">
        ✉️ Email Şablonu
    </button>

    @if ($type === 'guest')
        <button type="button" class="lab-btn primary" data-lab-open="senior-modal">
            👥 Senior Ata / Değiştir
        </button>
        <button type="button" class="lab-btn warn" data-lab-open="note-modal">
            ✏️ Hızlı Not
        </button>
    @else
        <button type="button" class="lab-btn warn" data-lab-open="template-modal" data-lab-channel="email">
            💰 Ödeme Hatırlat
        </button>
    @endif
</div>

{{-- Senior Ata Modal (guest only) --}}
@if ($type === 'guest')
<div class="lab-modal" id="senior-modal">
    <div class="lab-modal-inner">
        <button type="button" class="close-x" data-lab-close>×</button>
        <h3>👥 Senior Ata</h3>
        <form id="form-senior-assign">
            @csrf
            <label>Senior Email</label>
            <select name="senior_email" required>
                <option value="">— Seçiniz —</option>
                @php
                    $seniors = \App\Models\User::where('role', 'senior')
                        ->where('is_active', true)
                        ->orderBy('name')->get(['email', 'name']);
                @endphp
                @foreach ($seniors as $s)
                    <option value="{{ $s->email }}" {{ ($target->assigned_senior_email ?? '') === $s->email ? 'selected' : '' }}>
                        {{ $s->name }} ({{ $s->email }})
                    </option>
                @endforeach
            </select>
            <div class="lab-modal-actions">
                <button type="button" class="lab-btn" data-lab-close>İptal</button>
                <button type="submit" class="lab-btn primary">Ata</button>
            </div>
        </form>
    </div>
</div>

{{-- Hızlı Not Modal --}}
<div class="lab-modal" id="note-modal">
    <div class="lab-modal-inner">
        <button type="button" class="close-x" data-lab-close>×</button>
        <h3>✏️ Hızlı Not</h3>
        <form id="form-note">
            @csrf
            <label>Not içeriği</label>
            <textarea name="notes" placeholder="Dün aradım, geri dönmedi. Bu hafta WhatsApp ile tekrar dene." required>{{ $target->notes ?? '' }}</textarea>
            <div class="lab-modal-actions">
                <button type="button" class="lab-btn" data-lab-close>İptal</button>
                <button type="submit" class="lab-btn primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Template Modal --}}
<div class="lab-modal" id="template-modal">
    <div class="lab-modal-inner">
        <button type="button" class="close-x" data-lab-close>×</button>
        <h3 id="template-modal-title">📋 Şablon Seç</h3>
        <label>Şablon</label>
        <select id="template-select">
            <option value="">— Şablon seçiniz —</option>
        </select>

        <label>Subject (email için)</label>
        <input type="text" id="template-subject" readonly style="background:#f8fafc;">

        <label>Mesaj</label>
        <textarea id="template-body" readonly style="background:#f8fafc; min-height:180px;"></textarea>

        <div class="lab-modal-actions">
            <button type="button" class="lab-btn" data-lab-close>Kapat</button>
            <button type="button" class="lab-btn" id="template-copy">📋 Kopyala</button>
            <button type="button" class="lab-btn primary" id="template-send">📤 Gönder</button>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    const targetType = '{{ $type }}';
    const targetId = {{ $targetId }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ── Modal helpers ──
    function openModal(id) {
        document.getElementById(id)?.classList.add('open');
    }
    function closeModal(modal) {
        modal.classList.remove('open');
    }
    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-lab-open]');
        if (opener) {
            const modalId = opener.getAttribute('data-lab-open');
            openModal(modalId);
            if (modalId === 'template-modal') {
                const channel = opener.getAttribute('data-lab-channel') || 'whatsapp';
                loadTemplates(channel);
                document.getElementById('template-modal-title').textContent =
                    channel === 'email' ? '✉️ Email Şablonu' : '📋 WhatsApp Şablonu';
            }
        }
        const closer = e.target.closest('[data-lab-close]');
        if (closer) closeModal(closer.closest('.lab-modal'));
    });

    // ── Telefon / WhatsApp / Email butonları tıklanınca log ──
    document.querySelectorAll('[data-lab-action]').forEach(btn => {
        btn.addEventListener('click', () => {
            logAction(btn.getAttribute('data-lab-action'), {});
        });
    });

    function logAction(actionType, payload) {
        fetch(`/manager/actions/${targetType}/${targetId}/log`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(Object.assign({ action_type: actionType }, payload)),
        }).catch(() => {});
    }

    // ── Senior atama ──
    const seniorForm = document.getElementById('form-senior-assign');
    if (seniorForm) {
        seniorForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const senior = seniorForm.querySelector('[name=senior_email]').value;
            const res = await fetch(`/manager/actions/${targetType}/${targetId}/assign-senior`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ senior_email: senior }),
            });
            const data = await res.json();
            if (data.ok) {
                alert('Senior atandı.');
                location.reload();
            } else {
                alert(data.error || 'Hata oluştu.');
            }
        });
    }

    // ── Not kaydet ──
    const noteForm = document.getElementById('form-note');
    if (noteForm) {
        noteForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const notes = noteForm.querySelector('[name=notes]').value;
            const res = await fetch(`/manager/actions/${targetType}/${targetId}/update-notes`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ notes }),
            });
            const data = await res.json();
            if (data.ok) {
                alert('Not kaydedildi.');
                closeModal(noteForm.closest('.lab-modal'));
            } else {
                alert(data.error || 'Hata oluştu.');
            }
        });
    }

    // ── Template modal ──
    let currentTemplateChannel = 'whatsapp';
    async function loadTemplates(channel) {
        currentTemplateChannel = channel;
        const select = document.getElementById('template-select');
        select.innerHTML = '<option value="">— Yükleniyor… —</option>';
        document.getElementById('template-subject').value = '';
        document.getElementById('template-body').value = '';
        try {
            const res = await fetch(`/manager/actions/templates?channel=${channel}&target_type=${targetType}`);
            const data = await res.json();
            select.innerHTML = '<option value="">— Şablon seçiniz —</option>';
            (data.templates || []).forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                select.appendChild(opt);
            });
        } catch (err) {
            select.innerHTML = '<option value="">⚠️ Yüklenemedi</option>';
        }
    }

    document.getElementById('template-select')?.addEventListener('change', async (e) => {
        const id = e.target.value;
        if (!id) return;
        const res = await fetch(`/manager/actions/templates/${id}/render?target_type=${targetType}&target_id=${targetId}`);
        const data = await res.json();
        document.getElementById('template-subject').value = data.subject || '';
        document.getElementById('template-body').value = data.body || '';
    });

    document.getElementById('template-copy')?.addEventListener('click', () => {
        const body = document.getElementById('template-body').value;
        if (!body) return;
        navigator.clipboard.writeText(body).then(() => {
            alert('Kopyalandı.');
        });
    });

    document.getElementById('template-send')?.addEventListener('click', () => {
        const select = document.getElementById('template-select');
        const templateId = select.value;
        const body = document.getElementById('template-body').value;
        const subject = document.getElementById('template-subject').value;

        if (!templateId) { alert('Şablon seç.'); return; }

        if (currentTemplateChannel === 'whatsapp') {
            const phone = '{{ $phoneClean }}';
            if (!phone) { alert('Telefon yok.'); return; }
            const url = `https://wa.me/${phone.replace('+','')}?text=${encodeURIComponent(body)}`;
            logAction('whatsapp', { template_id: parseInt(templateId), channel: 'whatsapp', notes: body });
            window.open(url, '_blank', 'noopener');
        } else if (currentTemplateChannel === 'email') {
            const email = '{{ $email }}';
            if (!email) { alert('Email yok.'); return; }
            const url = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            logAction('email', { template_id: parseInt(templateId), channel: 'email', notes: subject });
            window.location.href = url;
        }
    });
})();
</script>

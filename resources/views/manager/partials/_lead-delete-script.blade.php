{{-- Lead delete (guest / student) delegated handler.
     Buton template:
       <button class="qa-del-btn archive" data-qa-delete-mode="archive"
               data-qa-delete-url="{{ route('manager.quick-admin.guest.delete', $g->id) }}"
               data-qa-delete-label="aday {{ $g->first_name }} {{ $g->last_name }}">🗑</button> --}}
<style>
.qa-del-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 28px; padding: 0; margin-left: 4px; border: 1px solid var(--u-line,#cbd5e1); background: var(--u-card,#fff); border-radius: 6px; font-size: 14px; cursor: pointer; transition: all .12s; vertical-align: middle; }
.qa-del-btn:hover { border-color: #d97706; background: rgba(245,158,11,.08); }
.qa-del-btn.force:hover { border-color: #dc2626; background: rgba(239,68,68,.08); }
.qa-del-btn:disabled { opacity: .5; cursor: wait; }
</style>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    document.addEventListener('click', async function(e){
        var btn = e.target.closest('[data-qa-delete-url]');
        if (!btn) return;
        var mode = btn.dataset.qaDeleteMode || 'archive';
        var label = btn.dataset.qaDeleteLabel || 'kayıt';
        var actionText = mode === 'force'
            ? '⚠️ KALICI OLARAK silinecek (DB\'den, geri alınamaz)'
            : 'arşivlenecek (deleted_at flag, geri alınabilir)';
        if (!window.confirm(label + ' ' + actionText + '.\n\nDevam etmek istiyor musun?')) return;

        btn.disabled = true; var origText = btn.textContent; btn.textContent = '…';
        try {
            var res = await fetch(btn.dataset.qaDeleteUrl + '?mode=' + mode, {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            });
            var json = await res.json();
            if (!res.ok || !json.ok) {
                alert(json.message || ('Hata: HTTP ' + res.status));
                btn.disabled = false; btn.textContent = origText;
                return;
            }
            var row = btn.closest('tr');
            if (row) {
                row.style.opacity = '0.25';
                row.style.transition = 'opacity .3s';
                setTimeout(function(){ row.remove(); }, 300);
            }
        } catch (err) {
            alert('Bağlantı hatası: ' + (err.message || 'bilinmeyen'));
            btn.disabled = false; btn.textContent = origText;
        }
    });
})();
</script>

{{-- Task iptal — iptal sebebi dropdown'lu. $taskId gerekli. --}}
<details style="display:inline-block;">
    <summary class="btn warn" style="cursor:pointer;list-style:none;font-size:12px;padding:4px 10px;">✗ İptal</summary>
    <form method="POST" action="/tasks/{{ $taskId }}/cancel"
          style="display:flex;gap:5px;align-items:center;margin-top:5px;flex-wrap:wrap;">
        @csrf
        <select name="cancel_reason" required style="font-size:12px;padding:4px 8px;border-radius:6px;border:1px solid var(--u-line);">
            <option value="">İptal sebebi seç...</option>
            <option value="customer_withdrew">Müşteri vazgeçti</option>
            <option value="duplicate">Mükerrer kayıt</option>
            <option value="wrong_task">Yanlış açıldı</option>
            <option value="process_changed">Süreç değişti</option>
            <option value="no_longer_needed">Artık gerekli değil</option>
            <option value="resolved_elsewhere">Başka yerde çözüldü</option>
            <option value="other">Diğer</option>
        </select>
        <button class="btn warn" type="submit" style="font-size:12px;padding:4px 10px;">İptal Et</button>
    </form>
</details>

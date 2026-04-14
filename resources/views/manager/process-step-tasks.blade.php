@extends('manager.layouts.app')

@section('title', 'Süreç Görev Şablonları')
@section('page_title', 'Süreç Görev Şablonları')

@section('content')

@if(session('status'))
    <div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

<div style="margin-bottom:16px;color:#64748b;font-size:13px;">
    Her süreç adımı için eğitim danışmanlarının öğrenci takibinde göreceği sub-task (görev) şablonlarını yönetin. Eğitim Danışmanı bu görevleri her öğrenci için ayrı ayrı tamamlandı olarak işaretleyebilir.
</div>

@foreach($definitions as $def)
<details class="panel" style="margin-bottom:12px;" {{ $loop->first ? 'open' : '' }}>
    <summary style="cursor:pointer;font-weight:700;font-size:14px;padding:2px 0;display:flex;align-items:center;gap:10px;">
        <span style="background:#e0e7ff;color:#3730a3;border-radius:6px;padding:2px 9px;font-size:11px;font-weight:700;">{{ $def->sort_order }}</span>
        {{ $def->name_tr }}
        <span style="font-size:11px;color:#94a3b8;font-weight:400;">{{ $def->stepTasks->where('is_active',true)->count() }} aktif görev</span>
    </summary>

    <div style="margin-top:14px;">
        {{-- Mevcut görevler --}}
        @if($def->stepTasks->isNotEmpty())
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:12px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:7px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">#</th>
                    <th style="padding:7px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Görev (TR)</th>
                    <th style="padding:7px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Görev (DE)</th>
                    <th style="padding:7px 10px;text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Zorunlu</th>
                    <th style="padding:7px 10px;text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Aktif</th>
                    <th style="padding:7px 10px;text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">İşlem</th>
                </tr>
            </thead>
            <tbody>
            @foreach($def->stepTasks->sortBy('sort_order') as $task)
            <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                <td style="padding:8px 10px;color:#94a3b8;font-size:12px;">{{ $task->sort_order }}</td>
                <td style="padding:8px 10px;">
                    <form method="POST" action="/manager/process-step-tasks/{{ $task->id }}" id="edit-form-{{ $task->id }}" style="display:none;gap:6px;align-items:center;">
                        @csrf @method('PUT')
                        <input type="text" name="label_tr" value="{{ $task->label_tr }}" style="flex:1;padding:4px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;" required>
                        <input type="hidden" name="label_de" id="edit-de-{{ $task->id }}" value="{{ $task->label_de }}">
                        <input type="hidden" name="sort_order" value="{{ $task->sort_order }}">
                        <input type="hidden" name="is_required" id="edit-req-{{ $task->id }}" value="{{ $task->is_required ? '1' : '0' }}">
                        <input type="hidden" name="is_active" id="edit-act-{{ $task->id }}" value="{{ $task->is_active ? '1' : '0' }}">
                    </form>
                    <span id="label-tr-{{ $task->id }}">{{ $task->label_tr }}</span>
                </td>
                <td style="padding:8px 10px;color:#64748b;font-size:12px;">
                    <span id="label-de-{{ $task->id }}">{{ $task->label_de ?: '—' }}</span>
                </td>
                <td style="padding:8px 10px;text-align:center;">
                    @if($task->is_required)
                        <span class="badge warn" style="font-size:10px;">Zorunlu</span>
                    @else
                        <span style="color:#cbd5e1;font-size:12px;">—</span>
                    @endif
                </td>
                <td style="padding:8px 10px;text-align:center;">
                    @if($task->is_active)
                        <span class="badge ok" style="font-size:10px;">Aktif</span>
                    @else
                        <span class="badge" style="font-size:10px;">Pasif</span>
                    @endif
                </td>
                <td style="padding:8px 10px;text-align:right;">
                    <div style="display:inline-flex;gap:4px;">
                        {{-- Inline düzenleme modalı --}}
                        <button type="button" onclick="openEditModal({{ $task->id }}, '{{ addslashes($task->label_tr) }}', '{{ addslashes($task->label_de ?? '') }}', {{ $task->sort_order }}, {{ $task->is_required ? 'true' : 'false' }}, {{ $task->is_active ? 'true' : 'false' }})"
                                class="btn alt" style="font-size:11px;padding:3px 8px;">Düzenle</button>
                        <form method="POST" action="/manager/process-step-tasks/{{ $task->id }}"
                              onsubmit="return confirm('Bu görevi silmek istediğinize emin misiniz?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn warn" style="font-size:11px;padding:3px 8px;">Sil</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
            <div style="color:#94a3b8;font-size:13px;margin-bottom:12px;padding:8px 0;">Henüz görev tanımlanmadı.</div>
        @endif

        {{-- Yeni görev ekleme formu --}}
        <details style="border:1px dashed #e2e8f0;border-radius:8px;padding:10px 14px;">
            <summary style="cursor:pointer;font-size:12px;font-weight:600;color:#7c3aed;">+ Yeni Görev Ekle</summary>
            <form method="POST" action="/manager/process-step-tasks" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
                @csrf
                <input type="hidden" name="process_definition_id" value="{{ $def->id }}">
                <div style="flex:2;min-width:180px;">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">Görev Adı (TR) *</label>
                    <input type="text" name="label_tr" required placeholder="ör. Transkript apostil yapıldı"
                           style="width:100%;padding:7px 10px;border:2px solid #e2e8f0;border-radius:7px;font-size:13px;background:#fff;color:#1e293b;">
                </div>
                <div style="flex:2;min-width:160px;">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">Görev Adı (DE)</label>
                    <input type="text" name="label_de" placeholder="ör. Transkript apostilliert"
                           style="width:100%;padding:7px 10px;border:2px solid #e2e8f0;border-radius:7px;font-size:13px;background:#fff;color:#1e293b;">
                </div>
                <div style="min-width:70px;">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">Sıra</label>
                    <input type="number" name="sort_order" value="0" min="0" max="999"
                           style="width:70px;padding:7px 10px;border:2px solid #e2e8f0;border-radius:7px;font-size:13px;background:#fff;color:#1e293b;">
                </div>
                <div style="display:flex;align-items:center;gap:6px;padding-bottom:2px;">
                    <input type="checkbox" name="is_required" value="1" id="req-{{ $def->id }}" style="width:16px;height:16px;">
                    <label for="req-{{ $def->id }}" style="font-size:13px;color:#475569;cursor:pointer;">Zorunlu</label>
                </div>
                <div style="padding-bottom:2px;">
                    <button type="submit" class="btn ok" style="padding:7px 16px;">Ekle</button>
                </div>
            </form>
        </details>
    </div>
</details>
@endforeach

{{-- Düzenleme Modal --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:24px;width:480px;max-width:90vw;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <div style="font-weight:700;font-size:15px;margin-bottom:16px;">Görevi Düzenle</div>
        <form method="POST" id="edit-modal-form">
            @csrf @method('PUT')
            <div style="margin-bottom:10px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">Görev Adı (TR) *</label>
                <input type="text" name="label_tr" id="modal-label-tr" required
                       style="width:100%;padding:8px 11px;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;color:#1e293b;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:10px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">Görev Adı (DE)</label>
                <input type="text" name="label_de" id="modal-label-de"
                       style="width:100%;padding:8px 11px;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;color:#1e293b;box-sizing:border-box;">
            </div>
            <div style="display:flex;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">Sıra</label>
                    <input type="number" name="sort_order" id="modal-sort" min="0" max="999"
                           style="width:80px;padding:8px 11px;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;color:#1e293b;">
                </div>
                <div style="display:flex;align-items:center;gap:6px;padding-top:20px;">
                    <input type="checkbox" name="is_required" id="modal-required" value="1" style="width:16px;height:16px;">
                    <label for="modal-required" style="font-size:13px;color:#475569;cursor:pointer;">Zorunlu</label>
                </div>
                <div style="display:flex;align-items:center;gap:6px;padding-top:20px;">
                    <input type="checkbox" name="is_active" id="modal-active" value="1" checked style="width:16px;height:16px;">
                    <label for="modal-active" style="font-size:13px;color:#475569;cursor:pointer;">Aktif</label>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn alt">İptal</button>
                <button type="submit" class="btn ok">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, labelTr, labelDe, sortOrder, isRequired, isActive) {
    document.getElementById('modal-label-tr').value = labelTr;
    document.getElementById('modal-label-de').value = labelDe;
    document.getElementById('modal-sort').value = sortOrder;
    document.getElementById('modal-required').checked = isRequired;
    document.getElementById('modal-active').checked = isActive;
    document.getElementById('edit-modal-form').action = '/manager/process-step-tasks/' + id;
    document.getElementById('edit-modal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

@endsection

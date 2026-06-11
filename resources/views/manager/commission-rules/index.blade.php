@extends('manager.layouts.app')

@section('title', 'Komisyon Kuralları')
@section('page_title', 'Komisyon Kuralları')

@push('head')
<style>
.cr-intro { background:rgba(30,64,175,.06); border:1px solid rgba(30,64,175,.18); border-radius:9px; padding:10px 16px; margin-bottom:14px; font-size:12px; color:var(--u-muted); line-height:1.6; }
.cr-table-wrap { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px; overflow:hidden; }
.cr-table { width:100%; border-collapse:collapse; font-size:13px; }
.cr-table th { background:var(--bg,#f1f5f9); padding:9px 14px; text-align:left; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); border-bottom:1px solid var(--border,#e2e8f0); }
.cr-table td { padding:10px 14px; border-bottom:1px solid var(--border,#e2e8f0); vertical-align:middle; }
.cr-table tr:last-child td { border-bottom:none; }
.cr-table tr:hover { background:rgba(30,64,175,.03); }
.cr-pill { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; }
.cr-pill.tier { background:rgba(124,58,237,.10); color:#6d28d9; }
.cr-pill.svc  { background:rgba(8,145,178,.10); color:#0e7490; }
.cr-pill.wild { background:rgba(100,116,139,.15); color:#475569; }
.cr-pill.pct  { background:rgba(22,163,74,.10); color:#15803d; font-weight:700; }
.cr-actions { display:flex; gap:6px; }
.cr-form-card { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px; padding:18px 20px; margin-top:18px; }
.cr-form-grid { display:grid; grid-template-columns:2fr 1fr 1fr 100px 100px 100px auto; gap:10px; align-items:end; }
@media (max-width:900px) { .cr-form-grid { grid-template-columns:1fr 1fr; } }
.cr-field-label { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); margin-bottom:4px; }
.cr-field-input { width:100%; padding:8px 10px; font-size:13px; border:1.5px solid var(--border,#cbd5e1); border-radius:6px; background:var(--surface,#fff); }
.cr-toggle { position:relative; display:inline-block; width:38px; height:22px; }
.cr-toggle input { opacity:0; width:0; height:0; }
.cr-toggle-slider { position:absolute; cursor:pointer; inset:0; background:#cbd5e1; border-radius:11px; transition:.2s; }
.cr-toggle-slider::before { position:absolute; content:""; height:16px; width:16px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
.cr-toggle input:checked + .cr-toggle-slider { background:#16a34a; }
.cr-toggle input:checked + .cr-toggle-slider::before { transform:translateX(16px); }
.cr-edit-row { display:none; background:rgba(30,64,175,.04); }
.cr-edit-row.open { display:table-row; }
.cr-priority-badge { font-family:'SF Mono','Monaco',monospace; font-size:11px; background:var(--bg,#f1f5f9); border:1px solid var(--border,#e2e8f0); padding:2px 8px; border-radius:5px; font-weight:600; color:var(--text,#0f172a); }
.cr-default-info { margin-top:10px; padding:8px 12px; background:rgba(217,119,6,.08); border-left:3px solid #d97706; border-radius:5px; font-size:12px; color:#b45309; }
.cr-empty { padding:40px 20px; text-align:center; color:var(--muted,#64748b); font-size:13px; }
</style>
@endpush

@section('content')

<div class="cr-intro">
    <strong style="color:var(--u-text);">Komisyon Kuralları:</strong>
    Senior'lara ödediğiniz hizmet bedellerinden platform payı (komisyon) yüzdesini tier (kıdem) ve hizmet türüne göre yönetin.
    Lookup sırası: 1) Tier + Hizmet eşleşmesi, 2) Sadece Tier, 3) Sadece Hizmet, 4) Genel (wildcard).
    Düşük <strong>priority</strong> = yüksek öncelik. Hiçbir kural eşleşmezse varsayılan <strong>%{{ number_format((float) $defaultPct, 0) }}</strong> uygulanır.
</div>

@if(session('success'))
<div style="background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.25);border-radius:8px;padding:10px 14px;margin-bottom:12px;color:#15803d;font-size:13px;">
    <x-icon name="check-circle" size="14" /> {{ session('success') }}
</div>
@endif

<div class="cr-table-wrap">
    <table class="cr-table">
        <thead>
            <tr>
                <th style="width:80px;">Öncelik</th>
                <th>Kural Adı</th>
                <th>Tier</th>
                <th>Hizmet Türü</th>
                <th style="width:100px;">Komisyon</th>
                <th style="width:90px;">Aktif</th>
                <th style="width:140px;text-align:right;">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rules as $rule)
                <tr>
                    <td><span class="cr-priority-badge">{{ $rule->priority }}</span></td>
                    <td><strong style="color:var(--text,#0f172a);">{{ $rule->rule_name }}</strong></td>
                    <td>
                        @if($rule->applies_to_tier)
                            <span class="cr-pill tier">{{ $tiers[$rule->applies_to_tier] ?? $rule->applies_to_tier }}</span>
                        @else
                            <span class="cr-pill wild">Tümü</span>
                        @endif
                    </td>
                    <td>
                        @if($rule->applies_to_service_type)
                            <span class="cr-pill svc">{{ $serviceTypes[$rule->applies_to_service_type] ?? $rule->applies_to_service_type }}</span>
                        @else
                            <span class="cr-pill wild">Tümü</span>
                        @endif
                    </td>
                    <td><span class="cr-pill pct">%{{ number_format((float) $rule->commission_pct, 2) }}</span></td>
                    <td>
                        @if($rule->is_active)
                            <span style="color:#16a34a;font-weight:600;font-size:12px;">Aktif</span>
                        @else
                            <span style="color:#94a3b8;font-size:12px;">Pasif</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <div class="cr-actions" style="justify-content:flex-end;">
                            <button type="button" class="btn alt cr-edit-btn" data-edit-id="{{ $rule->id }}"
                                    style="padding:5px 10px;font-size:11px;">
                                <x-icon name="pen-line" size="12" /> Düzenle
                            </button>
                            <form method="POST" action="{{ route('manager.commission-rules.destroy', $rule) }}"
                                  class="cr-delete-form" data-confirm="Bu kuralı silmek istediğinden emin misin?"
                                  style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn warn" style="padding:5px 10px;font-size:11px;">
                                    <x-icon name="trash-2" size="12" /> Sil
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <tr class="cr-edit-row" id="cr-edit-{{ $rule->id }}">
                    <td colspan="7">
                        <form method="POST" action="{{ route('manager.commission-rules.update', $rule) }}">
                            @csrf
                            @method('PUT')
                            <div class="cr-form-grid">
                                <div>
                                    <label class="cr-field-label">Kural Adı</label>
                                    <input type="text" name="rule_name" value="{{ old('rule_name', $rule->rule_name) }}" class="cr-field-input" required>
                                </div>
                                <div>
                                    <label class="cr-field-label">Tier</label>
                                    <select name="applies_to_tier" class="cr-field-input">
                                        <option value="">— Tümü —</option>
                                        @foreach($tiers as $key => $label)
                                            <option value="{{ $key }}" @selected($rule->applies_to_tier === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="cr-field-label">Hizmet Türü</label>
                                    <select name="applies_to_service_type" class="cr-field-input">
                                        <option value="">— Tümü —</option>
                                        @foreach($serviceTypes as $key => $label)
                                            <option value="{{ $key }}" @selected($rule->applies_to_service_type === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="cr-field-label">Komisyon %</label>
                                    <input type="number" step="0.01" min="0" max="100" name="commission_pct" value="{{ $rule->commission_pct }}" class="cr-field-input" required>
                                </div>
                                <div>
                                    <label class="cr-field-label">Öncelik</label>
                                    <input type="number" min="0" max="65535" name="priority" value="{{ $rule->priority }}" class="cr-field-input">
                                </div>
                                <div>
                                    <label class="cr-field-label">Aktif</label>
                                    <label class="cr-toggle">
                                        <input type="checkbox" name="is_active" value="1" @checked($rule->is_active)>
                                        <span class="cr-toggle-slider"></span>
                                    </label>
                                </div>
                                <div>
                                    <button type="submit" class="btn-primary btn">
                                        <x-icon name="check" size="14" /> Kaydet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="cr-empty">
                    <x-icon name="sliders" size="20" /><br>
                    Henüz kural yok. Aşağıdan ilk kuralı ekle. Varsayılan komisyon: <strong>%{{ number_format((float) $defaultPct, 0) }}</strong>.
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="cr-form-card">
    <h3 style="margin:0 0 12px;font-size:14px;font-weight:700;color:var(--text,#0f172a);">
        <x-icon name="plus" size="16" /> Yeni Komisyon Kuralı
    </h3>
    <form method="POST" action="{{ route('manager.commission-rules.store') }}">
        @csrf
        <div class="cr-form-grid">
            <div>
                <label class="cr-field-label">Kural Adı</label>
                <input type="text" name="rule_name" class="cr-field-input" placeholder="Örn. Junior - Danışma" value="{{ old('rule_name') }}" required>
            </div>
            <div>
                <label class="cr-field-label">Tier</label>
                <select name="applies_to_tier" class="cr-field-input">
                    <option value="">— Tümü —</option>
                    @foreach($tiers as $key => $label)
                        <option value="{{ $key }}" @selected(old('applies_to_tier') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="cr-field-label">Hizmet Türü</label>
                <select name="applies_to_service_type" class="cr-field-input">
                    <option value="">— Tümü —</option>
                    @foreach($serviceTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('applies_to_service_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="cr-field-label">Komisyon %</label>
                <input type="number" step="0.01" min="0" max="100" name="commission_pct" class="cr-field-input" placeholder="20.00" value="{{ old('commission_pct', '20.00') }}" required>
            </div>
            <div>
                <label class="cr-field-label">Öncelik</label>
                <input type="number" min="0" max="65535" name="priority" class="cr-field-input" placeholder="100" value="{{ old('priority', 100) }}">
            </div>
            <div>
                <label class="cr-field-label">Aktif</label>
                <label class="cr-toggle">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span class="cr-toggle-slider"></span>
                </label>
            </div>
            <div>
                <button type="submit" class="btn-primary btn">
                    <x-icon name="plus" size="14" /> Ekle
                </button>
            </div>
        </div>
    </form>
    <div class="cr-default-info">
        <x-icon name="info" size="12" /> Hiçbir kural eşleşmezse <strong>%{{ number_format((float) $defaultPct, 0) }}</strong> varsayılan komisyon uygulanır.
        Aktif olmayan kurallar lookup'ta yok sayılır.
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Edit row toggle
    document.querySelectorAll('.cr-edit-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = this.getAttribute('data-edit-id');
            var row = document.getElementById('cr-edit-' + id);
            if (row) {
                row.classList.toggle('open');
            }
        });
    });

    // Delete confirm
    document.querySelectorAll('.cr-delete-form').forEach(function(f){
        f.addEventListener('submit', function(e){
            var msg = this.getAttribute('data-confirm') || 'Silmek istediğine emin misin?';
            if (!confirm(msg)) { e.preventDefault(); }
        });
    });
})();
</script>

@endsection

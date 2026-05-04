@extends('manager.layouts.app')

@section('title', 'SaaS Modül Yönetimi')
@section('page_title', '🧩 SaaS Modül Yönetimi')
@section('page_subtitle', 'Her company için aktif modülleri seçin — değişiklik anında tüm portallarda etkili')

@push('head')
<style>
.cm-stats { display:grid; grid-template-columns:repeat(3, 1fr); gap:14px; margin-bottom:22px; }
.cm-stat  { background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:16px 18px; }
.cm-stat-num   { font-size:24px; font-weight:800; color:#7e58bf; line-height:1; }
.cm-stat-label { font-size:12px; color:#6b5894; font-weight:600; margin-top:4px; text-transform:uppercase; letter-spacing:.4px; }

.cm-company {
    background:#fff; border:1px solid #e5e5e5; border-radius:12px;
    padding:18px 20px; margin-bottom:14px;
}
.cm-company-head {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:16px; flex-wrap:wrap; gap:10px;
}
.cm-company-name { font-size:16px; font-weight:700; color:#1a1a1a; }
.cm-company-code { font-size:11px; color:#6b5894; font-family:ui-monospace,monospace; margin-left:8px; }
.cm-company-status {
    display:inline-block; padding:3px 10px; border-radius:999px;
    font-size:11px; font-weight:700;
}
.cm-company-status.active { background:#d1fae5; color:#065f46; }
.cm-company-status.inactive { background:#fee2e2; color:#991b1b; }

.cm-group { margin-bottom:14px; }
.cm-group-title {
    font-size:11px; font-weight:700; color:#6b5894; text-transform:uppercase;
    letter-spacing:.5px; margin-bottom:8px;
}
.cm-modules {
    display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
    gap:8px;
}
.cm-module {
    display:flex; align-items:flex-start; gap:8px;
    padding:10px 12px; background:#f8f5fc; border:1px solid #e8dffa;
    border-radius:8px; cursor:pointer; transition:background .15s, border-color .15s;
}
.cm-module:hover { background:#ede9fe; border-color:#c4b5fd; }
.cm-module.locked { background:#f3f4f6; border-color:#d1d5db; opacity:.7; cursor:not-allowed; }
.cm-module input[type="checkbox"] { margin-top:2px; flex-shrink:0; cursor:pointer; }
.cm-module input[type="checkbox"]:disabled { cursor:not-allowed; }
.cm-module-text { flex:1; }
.cm-module-label { font-size:13px; font-weight:600; color:#1a1a1a; }
.cm-module-desc  { font-size:11px; color:#6b7280; margin-top:2px; line-height:1.4; }

.cm-actions { display:flex; gap:10px; align-items:center; margin-top:14px; padding-top:14px; border-top:1px dashed #e5e5e5; }
.cm-save-btn {
    background:#7e58bf; color:#fff; border:0; padding:9px 22px;
    border-radius:8px; font-weight:700; font-size:13.5px; cursor:pointer;
}
.cm-save-btn:hover { background:#6c47a8; }
.cm-tier-presets { display:flex; gap:6px; }
.cm-tier-btn {
    background:#fff; border:1px solid #d4c5e8; color:#6b5894;
    padding:7px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;
}
.cm-tier-btn:hover { background:#f4f2ee; }
</style>
@endpush

@section('content')

<div class="cm-stats">
    <div class="cm-stat">
        <div class="cm-stat-num">{{ $companies->count() }}</div>
        <div class="cm-stat-label">Toplam Company</div>
    </div>
    <div class="cm-stat">
        <div class="cm-stat-num">{{ $companies->where('is_active', true)->count() }}</div>
        <div class="cm-stat-label">Aktif</div>
    </div>
    <div class="cm-stat">
        <div class="cm-stat-num">{{ count($moduleMeta) }}</div>
        <div class="cm-stat-label">Tanımlı Modül</div>
    </div>
</div>

@if(session('success'))
<div style="padding:12px 16px; background:#d1fae5; border-left:4px solid #059669; border-radius:8px; color:#064e3b; margin-bottom:16px; font-size:13.5px;">
    {{ session('success') }}
</div>
@endif

@foreach($companies as $company)
    @php $enabled = (array) ($company->enabled_modules ?? []); @endphp
    <form method="POST" action="{{ route('manager.companies.modules.update', $company) }}" class="cm-company" data-company-form>
        @csrf
        <div class="cm-company-head">
            <div>
                <span class="cm-company-name">{{ $company->name }}</span>
                <span class="cm-company-code">{{ $company->code }}</span>
            </div>
            <div>
                @if($company->is_active)
                    <span class="cm-company-status active">Aktif</span>
                @else
                    <span class="cm-company-status inactive">Pasif</span>
                @endif
            </div>
        </div>

        @foreach($moduleGroups as $groupCode => $groupLabel)
            @php $groupModules = collect($moduleMeta)->filter(fn($m) => ($m['group'] ?? '') === $groupCode); @endphp
            @if($groupModules->count() > 0)
                <div class="cm-group">
                    <div class="cm-group-title">{{ $groupLabel }}</div>
                    <div class="cm-modules">
                        @foreach($groupModules as $code => $meta)
                            @php
                                $isLocked = (bool) ($meta['locked'] ?? false);
                                $isEnabled = $isLocked || in_array($code, $enabled, true);
                            @endphp
                            <label class="cm-module {{ $isLocked ? 'locked' : '' }}">
                                <input type="checkbox" name="modules[]" value="{{ $code }}"
                                       @checked($isEnabled) @disabled($isLocked)>
                                <span class="cm-module-text">
                                    <span class="cm-module-label">{{ $meta['label'] }}</span>
                                    @if(!empty($meta['desc']))
                                        <span class="cm-module-desc">{{ $meta['desc'] }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        {{-- D11: doc_request aylik quota --}}
        @php
            $usage = method_exists($company, 'docRequestMonthlyUsage') ? $company->docRequestMonthlyUsage() : 0;
            $limit = $company->doc_request_monthly_limit;
        @endphp
        <div class="cm-group" style="margin-top:6px;">
            <div class="cm-group-title">📲 Belge Talep Linki Quota (aylık)</div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:13px;">
                <label style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#6b5894;font-weight:600;">Aylık limit:</span>
                    <input type="number" name="doc_request_monthly_limit" min="0" max="10000"
                           value="{{ $limit }}" placeholder="boş = sınırsız"
                           style="padding:6px 10px;border-radius:6px;border:1px solid #d4c5e8;width:130px;">
                </label>
                <span style="color:{{ $limit && $usage >= $limit ? '#dc2626' : '#6b7280' }};font-size:12px;">
                    Bu ay: <strong>{{ $usage }}</strong>{{ $limit ? ' / ' . $limit : ' (sınırsız)' }}
                </span>
                <span style="font-size:11px;color:#94a3b8;">Boş bırakılırsa Premium = sınırsız</span>
            </div>
        </div>

        <div class="cm-actions">
            <button type="submit" class="cm-save-btn">💾 Kaydet</button>
            <span class="cm-tier-presets">
                <button type="button" class="cm-tier-btn" data-preset="basic">Basic preset</button>
                <button type="button" class="cm-tier-btn" data-preset="gold">Gold preset</button>
                <button type="button" class="cm-tier-btn" data-preset="premium">Premium (hepsi)</button>
            </span>
        </div>
    </form>
@endforeach

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Tier preset: hizli secim
    var GOLD = ['booking','dam','content_hub','multi_provider_ai','doc_builder_ai','ai_labs'];
    var ALL_PREMIUM = @json(array_keys($moduleMeta));

    document.querySelectorAll('[data-company-form]').forEach(function(form){
        form.querySelectorAll('[data-preset]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var preset = this.dataset.preset;
                var keep = ['core'];
                if (preset === 'gold') keep = keep.concat(GOLD);
                else if (preset === 'premium') keep = ALL_PREMIUM;
                form.querySelectorAll('input[name="modules[]"]').forEach(function(cb){
                    if (cb.disabled) return; // core locked
                    cb.checked = keep.indexOf(cb.value) !== -1;
                });
            });
        });
    });
})();
</script>
@endpush

@endsection

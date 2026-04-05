@extends('manager.layouts.app')
@section('title', $template->name . ' — Şablon')
@section('page_title', $template->name)

@section('content')

@if(session('status'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#15803d;">{{ session('status') }}</div>
@endif

{{-- Breadcrumb --}}
<div style="display:flex;gap:6px;align-items:center;margin-bottom:14px;font-size:11px;color:var(--u-muted);">
    <a href="/manager/system" style="color:#1e40af;text-decoration:none;font-weight:700;">Sistem</a>
    <span>›</span>
    <a href="/manager/system/roles" style="color:#1e40af;text-decoration:none;font-weight:700;">Rol Yönetimi</a>
    <span>›</span><span>{{ $template->name }}</span>
</div>

{{-- Meta --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:16px 20px;margin-bottom:14px;display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
    <div>
        <div style="font-size:18px;font-weight:800;color:var(--u-text);">{{ $template->name }}</div>
        <div style="font-size:11px;color:var(--u-muted);font-family:monospace;">{{ $template->code }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <div style="text-align:center;padding:8px 14px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;">
            <div style="font-size:18px;font-weight:800;color:#1e40af;">{{ $assignments->count() }}</div>
            <div style="font-size:10px;color:var(--u-muted);">Aktif Kullanıcı</div>
        </div>
        <div style="text-align:center;padding:8px 14px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;">
            <div style="font-size:18px;font-weight:800;color:#7c3aed;">{{ $template->permissions->count() }}</div>
            <div style="font-size:10px;color:var(--u-muted);">İzin</div>
        </div>
        <div style="text-align:center;padding:8px 14px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;">
            <div style="font-size:14px;font-weight:800;color:var(--u-muted);">v{{ $template->version }}</div>
            <div style="font-size:10px;color:var(--u-muted);">Versiyon</div>
        </div>
    </div>
    <div style="margin-left:auto;display:flex;gap:6px;">
        @if($template->is_system)<span style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:800;padding:4px 10px;border-radius:6px;">SİSTEM</span>@endif
        <span style="background:{{ $template->is_active ? '#dcfce7' : '#fee2e2' }};color:{{ $template->is_active ? '#15803d' : '#dc2626' }};font-size:11px;font-weight:800;padding:4px 10px;border-radius:6px;">{{ $template->is_active ? 'AKTİF' : 'PASİF' }}</span>
    </div>
</div>

<div class="grid2" style="gap:14px;align-items:start;">

    {{-- Sol: Atanmış Kullanıcılar + Yeni Atama --}}
    <div style="display:flex;flex-direction:column;gap:12px;">

        {{-- Atanmış Kullanıcılar --}}
        <section class="panel" style="padding:0;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">👥 Atanmış Kullanıcılar ({{ $assignments->count() }})</div>
            @if($assignments->isEmpty())
            <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Bu şablona henüz kullanıcı atanmadı.</div>
            @else
            @foreach($assignments as $asgn)
            <div style="padding:10px 16px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:50%;background:#1e40af;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px;flex-shrink:0;">
                    {{ strtoupper(substr($asgn->user?->name ?? '?', 0, 1)) }}
                </div>
                <div style="flex:1;">
                    <div style="font-size:12px;font-weight:700;color:var(--u-text);">{{ $asgn->user?->name ?: '—' }}</div>
                    <div style="font-size:10px;color:var(--u-muted);">{{ $asgn->user?->role }} · {{ $asgn->assigned_at?->format('d.m.Y') }}</div>
                </div>
                <div style="display:flex;gap:6px;">
                    <a href="/manager/system/roles/users/{{ $asgn->user_id }}" style="font-size:10px;color:#1e40af;font-weight:700;text-decoration:none;padding:3px 8px;border:1px solid rgba(30,64,175,.3);border-radius:5px;">Profil</a>
                    <form method="POST" action="/manager/system/roles/assignments/{{ $asgn->id }}/revoke" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn warn" style="font-size:10px;padding:3px 8px;"
                                onclick="return confirm('Rol atamasını iptal etmek istediğinize emin misiniz?')">✕ İptal</button>
                    </form>
                </div>
            </div>
            @endforeach
            @endif
        </section>

        {{-- Kullanıcı Atama --}}
        @if($assignableUsers->isNotEmpty())
        <section class="panel">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">➕ Kullanıcı Ata</div>
            <form method="POST" action="/manager/system/roles/users/0/assign" id="assignForm">
                @csrf
                <input type="hidden" name="role_template_id" value="{{ $template->id }}">
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Kullanıcı Seç</label>
                    <select name="_user_id" id="assignUserSelect" required style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                        <option value="">— Kullanıcı seçin —</option>
                        @foreach($assignableUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn" style="width:100%;">Şablon Ata</button>
            </form>
        </section>
        @endif

    </div>

    {{-- Sağ: İzinler --}}
    <div style="display:flex;flex-direction:column;gap:12px;">
        <section class="panel" style="padding:0;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">🔑 Şablon İzinleri</div>

            @if(!$template->is_system)
            {{-- Düzenlenebilir form --}}
            <form method="POST" action="/manager/system/roles/{{ $template->id }}/permissions" style="padding:14px 16px;">
                @csrf
                <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px;">
                @foreach($allPermissions->groupBy('category') as $cat => $perms)
                <div style="margin-bottom:8px;">
                    <div style="font-size:10px;font-weight:800;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">{{ $cat }}</div>
                    @foreach($perms as $perm)
                    <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;transition:background .1s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
                        <input type="checkbox" name="permission_ids[]" value="{{ $perm->id }}"
                               {{ in_array($perm->id, $templatePermIds) ? 'checked' : '' }}
                               style="width:14px;height:14px;cursor:pointer;accent-color:#1e40af;">
                        <div>
                            <code style="font-size:11px;font-weight:700;color:#1e40af;">{{ $perm->code }}</code>
                            <span style="font-size:10px;color:var(--u-muted);margin-left:6px;">{{ $perm->description }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
                @endforeach
                </div>
                <button type="submit" class="btn ok" style="width:100%;">İzinleri Kaydet</button>
            </form>

            @else
            {{-- Sistem şablonu — sadece görüntüle --}}
            <div style="padding:8px 14px;background:#fef9c3;border-bottom:1px solid #fde68a;font-size:11px;color:#92400e;">
                ⚠ Sistem şablonları düzenlenemez.
            </div>
            <div style="padding:14px 16px;display:flex;flex-direction:column;gap:4px;">
            @if($template->permissions->isEmpty())
                <div style="color:var(--u-muted);font-size:12px;">İzin tanımlı değil.</div>
            @endif
            @foreach($template->permissions as $perm)
            <div style="display:flex;align-items:center;gap:8px;padding:7px 10px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:7px;">
                <span style="font-size:11px;font-weight:800;color:#15803d;">✓</span>
                <code style="font-size:11px;font-weight:700;color:#15803d;">{{ $perm->code }}</code>
                <span style="font-size:10px;color:#166534;">{{ $perm->description }}</span>
            </div>
            @endforeach
            </div>
            @endif
        </section>
    </div>

</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('assignForm')?.addEventListener('submit', function(e) {
    var sel = document.getElementById('assignUserSelect');
    var userId = sel?.value;
    if (!userId) { e.preventDefault(); return; }
    this.action = '/manager/system/roles/users/' + userId + '/assign';
});
</script>
@endpush

@endsection

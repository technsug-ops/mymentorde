@extends('manager.layouts.app')
@section('title', ($user->name ?: $user->email) . ' — Rol Profili')
@section('page_title', 'Kullanıcı Rol Profili')

@push('head')
<style>
.perm-pill { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:5px;font-size:10px;font-weight:700;font-family:monospace;background:var(--u-bg);border:1px solid var(--u-line);color:var(--u-text); }
.perm-pill.active { background:#dcfce7;border-color:#bbf7d0;color:#15803d; }
.perm-pill.default { background:#e0f2fe;border-color:#bae6fd;color:#0369a1; }
</style>
@endpush

@section('content')

@if(session('status'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#15803d;">{{ session('status') }}</div>
@endif

{{-- Breadcrumb --}}
<div style="display:flex;gap:6px;align-items:center;margin-bottom:14px;font-size:11px;color:var(--u-muted);">
    <a href="/manager/system" style="color:#1e40af;text-decoration:none;font-weight:700;">Sistem</a>
    <span>›</span>
    <a href="/manager/system/roles" style="color:#1e40af;text-decoration:none;font-weight:700;">Rol Yönetimi</a>
    <span>›</span><span>{{ $user->name ?: $user->email }}</span>
</div>

{{-- Kullanıcı Başlık --}}
<div style="background:linear-gradient(to right,#1e40af,#4f46e5);border-radius:14px;padding:18px 22px;margin-bottom:14px;color:#fff;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
    <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:20px;flex-shrink:0;">
        {{ strtoupper(substr($user->name ?: $user->email, 0, 1)) }}
    </div>
    <div style="flex:1;">
        <div style="font-size:18px;font-weight:800;">{{ $user->name ?: '—' }}</div>
        <div style="font-size:12px;opacity:.8;">{{ $user->email }}</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <div style="text-align:center;padding:8px 14px;background:rgba(255,255,255,.15);border-radius:8px;">
            <div style="font-size:16px;font-weight:800;">{{ $activeAssignments->count() }}</div>
            <div style="font-size:10px;opacity:.8;">Aktif Şablon</div>
        </div>
        <div style="text-align:center;padding:8px 14px;background:rgba(255,255,255,.15);border-radius:8px;">
            <div style="font-size:16px;font-weight:800;">{{ count($effectivePermissions) }}</div>
            <div style="font-size:10px;opacity:.8;">Etkin İzin</div>
        </div>
        <div style="text-align:center;padding:8px 14px;background:rgba(255,255,255,.15);border-radius:8px;">
            <div style="font-size:13px;font-weight:800;font-family:monospace;">{{ $user->role }}</div>
            <div style="font-size:10px;opacity:.8;">Temel Rol</div>
        </div>
    </div>
</div>

<div class="grid2" style="gap:14px;align-items:start;">

    {{-- Sol: Aktif Şablonlar + Şablon Atama --}}
    <div style="display:flex;flex-direction:column;gap:12px;">

        {{-- Aktif Şablon Atamaları --}}
        <section class="panel" style="padding:0;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">📋 Aktif Şablon Atamaları</div>
            @if($activeAssignments->isEmpty())
            <div style="padding:20px 16px;background:#fffbeb;border-bottom:1px solid var(--u-line);">
                <div style="font-size:12px;color:#92400e;font-weight:600;">⚠ Aktif şablon ataması yok.</div>
                <div style="font-size:11px;color:#92400e;margin-top:2px;">İzinler <strong>varsayılan rol kurallarından</strong> hesaplanıyor.</div>
            </div>
            @else
            @foreach($activeAssignments as $asgn)
            <div style="padding:11px 16px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:10px;">
                <div style="flex:1;">
                    <div style="font-size:13px;font-weight:700;color:var(--u-text);">{{ $asgn->template?->name ?? '?' }}</div>
                    <div style="font-size:10px;color:var(--u-muted);font-family:monospace;">{{ $asgn->template?->code }}</div>
                    <div style="font-size:10px;color:var(--u-muted);">Atandı: {{ $asgn->assigned_at?->format('d.m.Y H:i') }}</div>
                </div>
                <div style="text-align:right;">
                    @php $permCount = $asgn->template?->permissions->count() ?? 0; @endphp
                    <div style="font-size:12px;font-weight:700;color:#7c3aed;">{{ $permCount }} izin</div>
                    <form method="POST" action="/manager/system/roles/assignments/{{ $asgn->id }}/revoke" style="margin-top:4px;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn warn" style="font-size:10px;padding:3px 9px;"
                                onclick="return confirm('Şablon atamasını iptal et?')">✕ İptal</button>
                    </form>
                </div>
            </div>
            @endforeach
            @endif
        </section>

        {{-- Yeni Şablon Atama --}}
        @if($availableTemplates->isNotEmpty())
        <section class="panel">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">➕ Şablon Ata</div>
            <form method="POST" action="/manager/system/roles/users/{{ $user->id }}/assign">
                @csrf
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Şablon Seç</label>
                    <select name="role_template_id" required style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                        <option value="">— Şablon seçin —</option>
                        @foreach($availableTemplates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->permissions->count() }} izin)</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn" style="width:100%;">Şablon Ata</button>
            </form>
        </section>
        @endif

        {{-- Geçmiş Atamalar --}}
        @if($revokedAssignments->isNotEmpty())
        <section class="panel" style="padding:0;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">🗂 Geçmiş Atamalar</div>
            @foreach($revokedAssignments as $asgn)
            <div style="padding:8px 16px;border-bottom:1px solid var(--u-line);opacity:.6;">
                <div style="font-size:11px;font-weight:600;color:var(--u-text);">{{ $asgn->template?->name ?? '?' }}</div>
                <div style="font-size:10px;color:var(--u-muted);">
                    {{ $asgn->assigned_at?->format('d.m.Y') }} → {{ $asgn->revoked_at?->format('d.m.Y') }}
                </div>
            </div>
            @endforeach
        </section>
        @endif

    </div>

    {{-- Sağ: Etkin İzinler --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:var(--tx-sm);">🔑 Etkin İzinler</div>
            <div style="display:flex;gap:6px;align-items:center;font-size:10px;">
                <span style="background:#dcfce7;color:#15803d;padding:2px 7px;border-radius:4px;font-weight:700;">Şablondan</span>
                <span style="background:#e0f2fe;color:#0369a1;padding:2px 7px;border-radius:4px;font-weight:700;">Varsayılan</span>
            </div>
        </div>

        @php
            // Şablonlardan gelen izinler
            $fromTemplate = [];
            foreach ($activeAssignments as $asgn) {
                foreach ($asgn->template?->permissions ?? [] as $p) {
                    $fromTemplate[$p->code] = true;
                }
            }
        @endphp

        <div style="padding:14px 16px;">
        @foreach($allPermissions as $category => $perms)
        <div style="margin-bottom:14px;">
            <div style="font-size:10px;font-weight:800;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px;">{{ $category }}</div>
            <div style="display:flex;flex-wrap:wrap;gap:5px;">
            @foreach($perms as $perm)
            @php
                $hasIt     = in_array($perm->code, $effectivePermissions);
                $fromTpl   = isset($fromTemplate[$perm->code]);
                $pillClass = !$hasIt ? 'perm-pill' : ($fromTpl ? 'perm-pill active' : 'perm-pill default');
                $icon      = !$hasIt ? '✕' : ($fromTpl ? '✓' : '~');
            @endphp
            <span class="{{ $pillClass }}" title="{{ $perm->description }}">
                {{ $icon }} {{ $perm->code }}
            </span>
            @endforeach
            </div>
        </div>
        @endforeach

        @if(empty($effectivePermissions))
        <div style="text-align:center;color:var(--u-muted);font-size:12px;padding:16px;">Etkin izin bulunamadı.</div>
        @endif
        </div>

        <div style="padding:10px 16px;border-top:1px solid var(--u-line);background:var(--u-bg);">
            <div style="font-size:10px;color:var(--u-muted);">
                <span style="color:#15803d;font-weight:700;">✓</span> Şablon · <span style="color:#0369a1;font-weight:700;">~</span> Varsayılan (rol tabanlı) · <span style="color:var(--u-muted);">✕</span> Yok
            </div>
        </div>
    </section>

</div>

@endsection

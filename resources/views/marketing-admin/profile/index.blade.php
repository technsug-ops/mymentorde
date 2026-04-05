@extends('marketing-admin.layouts.app')

@section('title', 'Profilim')

@section('page_subtitle', 'Profil — hesap bilgileri ve yetki özeti')

@section('content')
<style>
.mprf-hero {
    background: linear-gradient(to right, #1e3a8a 0%, var(--u-brand,#1e40af) 100%);
    border-radius: 14px;
    padding: 28px 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    position: relative;
    overflow: hidden;
}
.mprf-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.mprf-avatar {
    width: 76px; height: 76px; border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(255,255,255,.35);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 24px;
    flex-shrink: 0; z-index: 1;
}
.mprf-hero-info { flex: 1; min-width: 180px; z-index: 1; }
.mprf-hero-name  { font-size: 20px; font-weight: 700; color: #fff; margin: 0 0 3px; }
.mprf-hero-email { font-size: 12px; color: rgba(255,255,255,.75); margin-bottom: 10px; }
.mprf-hero-tags  { display: flex; gap: 6px; flex-wrap: wrap; }
.mprf-tag {
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 999px;
    padding: 2px 10px; font-size: 12px; color: #fff; font-weight: 600;
}
.mprf-tag.active { background: rgba(134,239,172,.22); border-color: rgba(134,239,172,.4); }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; font-family:inherit;
}
.wf-field input:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }
.wf-field input:disabled { background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); color:var(--u-muted,#64748b); cursor:not-allowed; }

details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }
</style>

@php
    $u = $user ?? auth()->user();
    $initials = strtoupper(substr($u->name ?? 'MA', 0, 2));
    $isActive = (bool)($u->is_active ?? false);
    $rawPerms = is_array($team->permissions ?? null) ? $team->permissions : [];
    $permList = array_is_list($rawPerms)
        ? array_values(array_filter(array_map('strval', $rawPerms)))
        : collect($rawPerms)->filter(fn($v) => (bool)$v)->keys()->map(fn($v) => (string)$v)->values()->all();
@endphp

<div style="display:grid;gap:12px;">

    {{-- Hero --}}
    <div class="mprf-hero">
        <div class="mprf-avatar">{{ $initials }}</div>
        <div class="mprf-hero-info">
            <div class="mprf-hero-name">{{ $u->name ?? '—' }}</div>
            <div class="mprf-hero-email">{{ $u->email ?? '—' }}</div>
            <div class="mprf-hero-tags">
                <span class="mprf-tag" style="text-transform:capitalize;">{{ $u->role ?? '—' }}</span>
                @if($team->role ?? null)
                <span class="mprf-tag">{{ $team->role }}</span>
                @endif
                <span class="mprf-tag {{ $isActive ? 'active' : '' }}">{{ $isActive ? 'Aktif' : 'Pasif' }}</span>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('status'))
    <div style="border:1px solid var(--u-ok,#16a34a);background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,var(--u-card,#fff));color:var(--u-ok,#16a34a);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        {{ session('status') }}
    </div>
    @endif
    @if($errors->any())
    <div style="border:1px solid var(--u-danger,#dc2626);background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,var(--u-card,#fff));color:var(--u-danger,#dc2626);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
    @endif

    {{-- Hesap Güncelleme --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            Hesap Bilgileri
        </div>
        <form method="POST" action="/mktg-admin/profile">
            @csrf @method('PUT')
            <div class="grid2" style="gap:10px;margin-bottom:14px;">
                <div class="wf-field">
                    <label>Ad Soyad</label>
                    <input name="name" value="{{ old('name', $u->name ?? '') }}" required>
                </div>
                <div class="wf-field">
                    <label>E-posta</label>
                    <input value="{{ $u->email ?? '' }}" disabled>
                </div>
                <div class="wf-field">
                    <label>Rol</label>
                    <input value="{{ $u->role ?? '—' }}" disabled>
                </div>
                <div class="wf-field">
                    <label>Hesap Durumu</label>
                    <input value="{{ $isActive ? 'Aktif' : 'Pasif' }}" disabled>
                </div>
                <div class="wf-field">
                    <label>Yeni Şifre (opsiyonel)</label>
                    <input type="password" name="password" placeholder="Yeni şifre">
                </div>
                <div class="wf-field">
                    <label>Şifre Tekrar</label>
                    <input type="password" name="password_confirmation" placeholder="Tekrar">
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn ok">Profili Kaydet</button>
                <a href="/mktg-admin/profile" class="btn alt">Yenile</a>
            </div>
        </form>
    </div>

    {{-- Ekip & Yetkiler --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            Ekip & Yetki Özeti
        </div>
        <div style="margin-bottom:12px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted,#64748b);margin-bottom:4px;">Ekip Rolü</div>
            <div style="font-size:var(--tx-sm);font-weight:700;">{{ $team->role ?? '—' }}</div>
        </div>
        @if(count($permList) > 0)
        <div style="margin-bottom:14px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted,#64748b);margin-bottom:6px;">Ekip Permissionları</div>
            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                @foreach($permList as $p)
                <span class="badge info" style="font-size:var(--tx-xs);">{{ $p }}</span>
                @endforeach
            </div>
        </div>
        @endif
        @if(!empty($effectivePermissions ?? []))
        <div>
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted,#64748b);margin-bottom:6px;">Efektif Yetkiler (Rol + RBAC)</div>
            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                @foreach($effectivePermissions as $p)
                <span class="badge" style="font-size:var(--tx-xs);background:color-mix(in srgb,#7c3aed 12%,#fff);color:#5b21b6;border:1px solid color-mix(in srgb,#7c3aed 30%,#fff);">{{ $p }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Profil</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Sadece kendi adını ve şifreni bu ekrandan güncelle.</li>
            <li>Şifre alanlarını boş bırakırsan mevcut şifre değişmez.</li>
            <li>Efektif yetki listesi, rol + RBAC template sonucunu gösterir.</li>
            <li>Rol değişikliği gerekiyorsa Team ekranından yapılır.</li>
        </ol>
    </details>

    {{-- ── İş Sözleşmelerim ── --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            İş Sözleşmelerim
        </div>
        @if($contracts->isEmpty())
            <div style="text-align:center;padding:20px;color:var(--u-muted);font-size:13px;">Henüz size gönderilmiş bir sözleşme yok.</div>
        @else
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($contracts as $c)
                <div style="display:flex;align-items:center;gap:14px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;padding:12px 16px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:14px;font-weight:600;color:var(--u-text);margin-bottom:3px;">{{ $c->title }}</div>
                        <div style="font-size:12px;color:var(--u-muted);">
                            {{ $c->contract_no }}
                            @if($c->issued_at) &middot; Gönderilme: {{ \Carbon\Carbon::parse($c->issued_at)->format('d.m.Y') }} @endif
                            @if($c->approved_at) &middot; Onaylanma: {{ \Carbon\Carbon::parse($c->approved_at)->format('d.m.Y') }} @endif
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                        <span class="badge {{ $c->statusBadge() }}">{{ $c->statusLabel() }}</span>
                        @if($c->status === 'issued')
                            <span class="badge warn" style="font-size:11px;">⏳ İmza Bekliyor</span>
                        @endif
                        <a href="{{ route('my-contracts.show', $c) }}" class="btn" style="font-size:12px;padding:5px 12px;">Görüntüle</a>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── İzin Talepler ── --}}
    @php
        $lvTypeLabels = ['annual'=>'Yıllık İzin','sick'=>'Hastalık İzni','personal'=>'Kişisel İzin','maternity'=>'Doğum İzni','unpaid'=>'Ücretsiz İzin'];
        $lvStatusMap  = ['pending'=>['label'=>'Bekliyor','color'=>'#d97706'],'approved'=>['label'=>'Onaylandı','color'=>'#16a34a'],'rejected'=>['label'=>'Reddedildi','color'=>'#dc2626'],'cancelled'=>['label'=>'İptal','color'=>'#6b7280']];
    @endphp
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            İzin Talepler
        </div>

        {{-- Kota --}}
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div style="flex:1;min-width:100px;background:color-mix(in srgb,var(--u-brand,#1e40af) 8%,#fff);border:1px solid color-mix(in srgb,var(--u-brand,#1e40af) 20%,#fff);border-radius:10px;padding:12px 16px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:var(--u-brand,#1e40af);">{{ $quota }}</div>
                <div style="font-size:11px;color:var(--u-muted);font-weight:600;margin-top:2px;">Toplam Kota</div>
            </div>
            <div style="flex:1;min-width:100px;background:#fef9ec;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:#d97706;">{{ $used }}</div>
                <div style="font-size:11px;color:var(--u-muted);font-weight:600;margin-top:2px;">Kullanılan ({{ $leaveYear }})</div>
            </div>
            <div style="flex:1;min-width:100px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:#16a34a;">{{ $remaining }}</div>
                <div style="font-size:11px;color:var(--u-muted);font-weight:600;margin-top:2px;">Kalan Gün</div>
            </div>
        </div>

        {{-- Yeni talep formu --}}
        <div style="background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,#fff);border:1px solid color-mix(in srgb,var(--u-brand,#1e40af) 15%,#fff);border-radius:10px;padding:16px 18px;margin-bottom:18px;">
            <div style="font-size:12px;font-weight:700;color:var(--u-brand,#1e40af);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Yeni İzin Talebi</div>
            <form method="POST" action="{{ url('/hr/my/leaves') }}" enctype="multipart/form-data">
                @csrf
                <div class="grid2" style="gap:10px;margin-bottom:10px;">
                    <div class="wf-field">
                        <label>İzin Türü</label>
                        <select name="leave_type" style="height:36px;padding:0 10px;border:1px solid var(--u-line);border-radius:8px;background:var(--u-card);color:var(--u-text);font-size:13px;">
                            @foreach($lvTypeLabels as $val => $lbl)
                                <option value="{{ $val }}" @selected(old('leave_type') === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <div class="wf-field" style="flex:1;">
                            <label>Başlangıç</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="wf-field" style="flex:1;">
                            <label>Bitiş</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" min="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="wf-field">
                        <label>Açıklama <span style="font-weight:400;color:#9ca3af;">(opsiyonel)</span></label>
                        <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Ek bilgi...">
                    </div>
                    <div class="wf-field">
                        <label>Link <span style="font-weight:400;color:#9ca3af;">(opsiyonel)</span></label>
                        <input type="url" name="attachment_links[]" placeholder="https://">
                    </div>
                </div>
                <div class="wf-field" style="margin-bottom:12px;">
                    <label>Belge Ekle <span style="font-weight:400;color:#9ca3af;">(PDF, resim, Word — maks. 5MB)</span></label>
                    <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                           style="width:100%;padding:7px 10px;border:1.5px dashed color-mix(in srgb,var(--u-brand,#1e40af) 40%,#fff);border-radius:8px;font-size:12px;background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,#fff);color:var(--u-brand,#1e40af);cursor:pointer;">
                </div>
                <button type="submit" class="btn ok" style="font-size:13px;">Talep Gönder</button>
            </form>
        </div>

        {{-- Geçmiş --}}
        @if($leaves->isNotEmpty())
        <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Geçmiş Talepler</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($leaves as $leave)
            @php $st = $lvStatusMap[$leave->status] ?? ['label'=>$leave->status,'color'=>'#6b7280']; @endphp
            <div style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;padding:10px 14px;">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:140px;">
                        <div style="font-size:13px;font-weight:600;color:var(--u-text);">{{ $lvTypeLabels[$leave->leave_type] ?? $leave->leave_type }}</div>
                        <div style="font-size:12px;color:var(--u-muted);margin-top:2px;">
                            {{ \Carbon\Carbon::parse($leave->start_date)->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($leave->end_date)->format('d.m.Y') }}
                            <span style="margin-left:6px;color:var(--u-text);font-weight:600;">{{ $leave->days_count }} gün</span>
                        </div>
                    </div>
                    <span style="background:{{ $st['color'] }}18;color:{{ $st['color'] }};border:1px solid {{ $st['color'] }}40;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:700;">{{ $st['label'] }}</span>
                    @if($leave->status === 'pending')
                    <form method="POST" action="{{ url('/hr/my/leaves/' . $leave->id) }}" onsubmit="return confirm('İptal et?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:1px solid #e5e7eb;border-radius:7px;padding:4px 12px;font-size:12px;color:var(--u-muted);cursor:pointer;">İptal</button>
                    </form>
                    @endif
                </div>
                @if($leave->rejection_note)
                <div style="font-size:12px;color:#dc2626;background:#fef2f2;border-radius:7px;padding:6px 10px;margin-top:6px;">Red notu: {{ $leave->rejection_note }}</div>
                @endif
                @if($leave->attachments->isNotEmpty())
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
                    @foreach($leave->attachments as $att)
                        @if($att->type === 'file')
                        <a href="{{ route('hr.my.leave-attachment.download', $att) }}"
                           style="display:inline-flex;align-items:center;gap:4px;background:color-mix(in srgb,var(--u-brand,#1e40af) 8%,#fff);border:1px solid color-mix(in srgb,var(--u-brand,#1e40af) 25%,#fff);border-radius:6px;padding:3px 10px;font-size:11px;color:var(--u-brand,#1e40af);text-decoration:none;font-weight:600;">
                            📎 {{ $att->original_name }}
                        </a>
                        @else
                        <a href="{{ $att->url }}" target="_blank" rel="noopener"
                           style="display:inline-flex;align-items:center;gap:4px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:3px 10px;font-size:11px;color:#1d4ed8;text-decoration:none;font-weight:600;">
                            🔗 {{ parse_url($att->url, PHP_URL_HOST) ?: $att->url }}
                        </a>
                        @endif
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align:center;padding:20px;color:var(--u-muted);font-size:13px;">Henüz izin talebi bulunmuyor.</div>
        @endif
    </div>

</div>
@endsection

@extends('manager.layouts.app')
@section('page_title', 'Bayi Tip Yönetimi')

@section('content')
<div style="max-width:1100px;margin:0 auto;padding:20px;">

    @if(session('status'))
        <div style="padding:10px 16px;background:#e6f4ea;color:#1f6d35;border-radius:8px;margin-bottom:16px;font-size:13px;font-weight:600;">
            ✓ {{ session('status') }}
        </div>
    @endif

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
            <h1 style="font-size:20px;font-weight:700;margin:0;">Bayi Tip Yönetimi</h1>
            <p style="font-size:13px;color:var(--u-muted,#64748b);margin:4px 0 0;">Her tier'ın izinlerini buradan düzenleyin. Değişiklikler anında uygulanır.</p>
        </div>
        <a href="{{ route('manager.dealers') }}" class="btn alt" style="font-size:13px;">← Bayi Listesi</a>
    </div>

    <div style="display:grid;gap:16px;">
        @foreach($types as $type)
            @php
                $perms = $type->permissions ?? [];
                $tier = (int) ($perms['tier'] ?? 1);
                $dl = (string) ($perms['dashboardLevel'] ?? 'basic');
                $tierColors = [1 => '#0891b2', 2 => '#7c3aed', 3 => '#1e40af'];
                $count = $dealerCounts[$type->code] ?? 0;
            @endphp
            <form method="POST" action="{{ route('manager.dealer-types.update', $type->code) }}" class="card" style="padding:20px;">
                @csrf
                {{-- Header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="display:inline-block;padding:2px 10px;border-radius:5px;font-size:11px;font-weight:800;background:{{ $tierColors[$tier] ?? '#6b7280' }};color:#fff;">T{{ $tier }}</span>
                        <strong style="font-size:15px;">{{ $type->name_tr ?? $type->code }}</strong>
                        <span style="font-size:11px;color:var(--u-muted,#64748b);">{{ $type->code }}</span>
                    </div>
                    <span style="font-size:12px;color:var(--u-muted,#64748b);">{{ $count }} aktif bayi</span>
                </div>

                {{-- Core settings --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px;">
                    <label style="display:grid;gap:3px;font-size:12px;">
                        <span style="font-weight:600;">Görünen Ad (TR)</span>
                        <input type="text" name="name_tr" value="{{ $type->name_tr }}" required
                               style="padding:6px 10px;border:1px solid var(--u-line);border-radius:6px;font-size:12px;">
                    </label>
                    <label style="display:grid;gap:3px;font-size:12px;">
                        <span style="font-weight:600;">Tier Seviyesi</span>
                        <select name="tier" style="padding:6px 10px;border:1px solid var(--u-line);border-radius:6px;font-size:12px;">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ $tier === $i ? 'selected' : '' }}>T{{ $i }}</option>
                            @endfor
                        </select>
                    </label>
                    <label style="display:grid;gap:3px;font-size:12px;">
                        <span style="font-weight:600;">Dashboard Seviyesi</span>
                        <select name="dashboardLevel" style="padding:6px 10px;border:1px solid var(--u-line);border-radius:6px;font-size:12px;">
                            <option value="basic" {{ $dl === 'basic' ? 'selected' : '' }}>Basic</option>
                            <option value="standard" {{ $dl === 'standard' ? 'selected' : '' }}>Standard</option>
                            <option value="advanced" {{ $dl === 'advanced' ? 'selected' : '' }}>Advanced</option>
                        </select>
                    </label>
                </div>

                {{-- Permission toggles --}}
                <div style="border-top:1px solid var(--u-line);padding-top:12px;">
                    <div style="font-size:12px;font-weight:600;color:var(--u-muted,#64748b);margin-bottom:8px;">İzinler</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:6px;">
                        @foreach($permissionLabels as $key => $meta)
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;padding:4px 6px;border-radius:4px;cursor:pointer;"
                                   title="{{ $meta['desc'] }}">
                                <input type="checkbox" name="{{ $key }}" value="1" {{ !empty($perms[$key]) ? 'checked' : '' }}>
                                <span>{{ $meta['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Save --}}
                <div style="margin-top:14px;display:flex;gap:8px;align-items:center;">
                    <button type="submit" class="btn ok" style="font-size:12px;padding:6px 20px;">Kaydet</button>
                    <span style="font-size:11px;color:var(--u-muted,#64748b);">Değişiklikler anında tüm {{ $count }} bayiye yansır.</span>
                </div>
            </form>
        @endforeach
    </div>
</div>
@endsection

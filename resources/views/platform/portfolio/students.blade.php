@extends('platform.layouts.app')

@section('title', 'Tüm Öğrenciler — Platform')

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Tüm Öğrenciler</h1>
        <p class="plat-page-sub">Bütün şirketlerin öğrencileri tek listede — hangi firmaya ait olduğu kolonda</p>
    </div>
</div>

{{-- ŞİRKET BAZLI ÖZET --}}
<div class="plat-card" style="margin-bottom:18px;">
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        @php $grandTotal = array_sum($totals); @endphp
        <a href="{{ route('platform.students') }}"
           style="display:flex;flex-direction:column;gap:2px;padding:12px 18px;border-radius:10px;text-decoration:none;
                  border:1px solid {{ $filters['company'] === 0 ? '#2563eb' : '#e2e8f0' }};
                  background:{{ $filters['company'] === 0 ? '#eff6ff' : '#fff' }};">
            <span style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;">Tümü</span>
            <span style="font-size:22px;font-weight:800;color:#0f172a;">{{ $grandTotal }}</span>
        </a>
        @foreach($companies as $company)
            @php $count = $totals[$company->id] ?? 0; @endphp
            <a href="{{ route('platform.students', ['company' => $company->id]) }}"
               style="display:flex;flex-direction:column;gap:2px;padding:12px 18px;border-radius:10px;text-decoration:none;
                      border:1px solid {{ $filters['company'] === (int) $company->id ? '#2563eb' : '#e2e8f0' }};
                      background:{{ $filters['company'] === (int) $company->id ? '#eff6ff' : '#fff' }};">
                <span style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;">
                    {{ $company->brand_name ?: $company->name }}
                </span>
                <span style="font-size:22px;font-weight:800;color:#0f172a;">{{ $count }}</span>
            </a>
        @endforeach
    </div>
</div>

{{-- FİLTRE --}}
<div class="plat-card" style="margin-bottom:18px;">
    <form method="GET" action="{{ route('platform.students') }}"
          style="display:grid;grid-template-columns:2fr 1fr auto;gap:12px;align-items:end;">
        <div>
            <label class="plat-form-label">Arama</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="plat-input" placeholder="Ad, e-posta veya öğrenci no...">
        </div>
        <div>
            <label class="plat-form-label">Şirket</label>
            <select name="company" class="plat-select">
                <option value="0">Hepsi</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ $filters['company'] === (int) $company->id ? 'selected' : '' }}>
                        {{ $company->brand_name ?: $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="plat-btn plat-btn-primary">Filtrele</button>
    </form>
</div>

{{-- LİSTE --}}
<div class="plat-card">
    @if($rows->isEmpty())
        <p style="margin:0;color:#64748b;">Bu filtreye uyan öğrenci yok.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="plat-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Şirket</th>
                        <th>Öğrenci</th>
                        <th>E-posta</th>
                        <th>Öğrenci No</th>
                        <th>Durum</th>
                        <th>Kayıt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>
                                <span style="display:inline-block;padding:3px 9px;border-radius:20px;background:#f1f5f9;
                                             font-size:11px;font-weight:700;color:#334155;">
                                    {{ $companyNames[(int) $row->company_id] ?? '—' }}
                                </span>
                            </td>
                            <td style="font-weight:600;">{{ $row->name }}</td>
                            <td style="color:#475569;">{{ $row->email }}</td>
                            <td style="color:#64748b;font-size:13px;">{{ $row->student_id ?: '—' }}</td>
                            <td>
                                <span style="color:{{ $row->is_active ? '#16a34a' : '#dc2626' }};font-weight:600;font-size:13px;">
                                    {{ $row->is_active ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td style="color:#64748b;font-size:13px;">
                                {{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('d.m.Y') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">{{ $rows->links() }}</div>
    @endif
</div>

@endsection

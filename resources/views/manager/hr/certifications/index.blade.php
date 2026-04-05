@extends('manager.layouts.app')

@section('title', 'Sertifikalar')
@section('page_title', 'Sertifikalar')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- Uyarı chip'leri --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;align-items:center;">
    @foreach([['','Tümü',$certs->count(),'var(--u-line)','var(--u-card)'],['expired','Süresi Doldu',$expiredCount,'#dc2626','#fef2f2'],['soon','Yakında Bitiyor',$soonCount,'#d97706','#fffbeb'],['active','Aktif',null,'#16a34a','#f0fdf4']] as [$v,$l,$cnt,$bc,$bg])
    <a href="/manager/hr/certifications?status={{ $v }}{{ $userFilter ? '&user_id='.$userFilter : '' }}"
       style="padding:5px 12px;font-size:11px;font-weight:700;border-radius:7px;text-decoration:none;border:1.5px solid {{ $statusFilter===$v ? $bc : 'var(--u-line)' }};background:{{ $statusFilter===$v ? $bg : 'var(--u-card)' }};color:{{ $statusFilter===$v ? $bc : 'var(--u-muted)' }};">
        {{ $l }}@if($cnt !== null) ({{ $cnt }})@endif
    </a>
    @endforeach

    <form method="GET" action="/manager/hr/certifications" style="display:flex;gap:6px;margin-left:auto;">
        <input type="hidden" name="status" value="{{ $statusFilter }}">
        <select name="user_id" style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
            <option value="">Tüm Çalışanlar</option>
            @foreach($employees as $emp)
            <option value="{{ $emp->id }}" {{ $userFilter==(string)$emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn alt" style="font-size:11px;padding:6px 12px;">Filtrele</button>
    </form>
</div>

{{-- Sertifika Tablosu --}}
<section class="panel" style="padding:0;overflow:hidden;margin-bottom:12px;">
    <div style="overflow-x:auto;">
        @if($certs->isEmpty())
        <div style="padding:40px;text-align:center;color:var(--u-muted);font-size:13px;">Sertifika bulunamadı.</div>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:var(--u-bg);">
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Çalışan</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Sertifika</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Veren Kurum</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Alım Tarihi</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Son Geçerlilik</th>
                <th style="padding:8px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Durum</th>
                <th style="padding:8px 12px;"></th>
            </tr></thead>
            <tbody>
            @foreach($certs as $c)
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:8px 12px;">
                    <a href="/manager/hr/persons/{{ $c->user_id }}?tab=certs" style="font-weight:600;color:var(--u-text);text-decoration:none;">{{ $c->user?->name ?: '—' }}</a>
                </td>
                <td style="padding:8px 12px;font-weight:600;color:var(--u-text);">{{ $c->cert_name }}</td>
                <td style="padding:8px 12px;color:var(--u-muted);">{{ $c->issuer ?: '—' }}</td>
                <td style="padding:8px 12px;white-space:nowrap;">{{ $c->issue_date->format('d.m.Y') }}</td>
                <td style="padding:8px 12px;white-space:nowrap;">
                    @if($c->expiry_date)
                        <span style="color:{{ $c->isExpired() ? '#dc2626' : ($c->isExpiringSoon() ? '#d97706' : 'var(--u-text)') }};font-weight:{{ $c->isExpired()||$c->isExpiringSoon() ? '700' : '400' }};">
                            {{ $c->expiry_date->format('d.m.Y') }}
                        </span>
                    @else
                        <span style="color:var(--u-muted);">—</span>
                    @endif
                </td>
                <td style="padding:8px 12px;text-align:center;">
                    <span class="badge {{ $c->statusBadge() }}" style="font-size:10px;">{{ $c->statusLabel() }}</span>
                </td>
                <td style="padding:8px 12px;">
                    <div style="display:flex;gap:4px;">
                        <button type="button" onclick="showEdit({{ $c->id }})"
                                style="padding:3px 9px;font-size:10px;font-weight:600;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-muted);cursor:pointer;">
                            Düzenle
                        </button>
                        <form method="POST" action="/manager/hr/certifications/{{ $c->id }}" onsubmit="return confirm('Silmek istediğinizden emin misiniz?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="padding:3px 9px;font-size:10px;font-weight:600;border:1px solid #fca5a5;border-radius:6px;background:#fef2f2;color:#dc2626;cursor:pointer;">Sil</button>
                        </form>
                    </div>
                </td>
            </tr>
            {{-- Edit satırı --}}
            <tr id="edit-row-{{ $c->id }}" style="display:none;background:var(--u-bg);">
                <td colspan="7" style="padding:10px 16px;">
                    <form method="POST" action="/manager/hr/certifications/{{ $c->id }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
                        @csrf @method('PUT')
                        <div>
                            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);margin-bottom:3px;">Sertifika Adı</label>
                            <input type="text" name="cert_name" value="{{ $c->cert_name }}" required style="padding:5px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-card);color:var(--u-text);width:200px;">
                        </div>
                        <div>
                            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);margin-bottom:3px;">Veren Kurum</label>
                            <input type="text" name="issuer" value="{{ $c->issuer }}" style="padding:5px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-card);color:var(--u-text);width:160px;">
                        </div>
                        <div>
                            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);margin-bottom:3px;">Alım</label>
                            <input type="date" name="issue_date" value="{{ $c->issue_date->format('Y-m-d') }}" required style="padding:5px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-card);color:var(--u-text);">
                        </div>
                        <div>
                            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);margin-bottom:3px;">Son Geçerlilik</label>
                            <input type="date" name="expiry_date" value="{{ optional($c->expiry_date)->format('Y-m-d') }}" style="padding:5px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-card);color:var(--u-text);">
                        </div>
                        <button type="submit" class="btn ok" style="padding:6px 14px;font-size:12px;">Kaydet</button>
                        <button type="button" onclick="document.getElementById('edit-row-{{ $c->id }}').style.display='none'" class="btn" style="padding:6px 14px;font-size:12px;">İptal</button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</section>

{{-- Yeni Sertifika Ekle --}}
<section class="panel" style="padding:14px 18px;">
    <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">+ Yeni Sertifika Ekle</div>
    <form method="POST" action="/manager/hr/certifications" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        @csrf
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Çalışan</label>
            <select name="user_id" required style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);min-width:180px;">
                <option value="">Seç…</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ $userFilter==(string)$emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Sertifika Adı</label>
            <input type="text" name="cert_name" required placeholder="ör. IELTS, B2 Almanca…"
                   style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);width:200px;">
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Veren Kurum</label>
            <input type="text" name="issuer" style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);width:150px;">
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Alım Tarihi</label>
            <input type="date" name="issue_date" required style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Son Geçerlilik</label>
            <input type="date" name="expiry_date" style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
        </div>
        <button type="submit" class="btn ok" style="padding:7px 18px;font-size:12px;">Ekle</button>
    </form>
</section>

@endsection

@push('scripts')
<script>
function showEdit(id) {
    var row = document.getElementById('edit-row-' + id);
    if (row) row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>
@endpush

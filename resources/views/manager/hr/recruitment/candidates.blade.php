@extends('manager.layouts.app')

@section('title', 'Adaylar')
@section('page_title', 'İşe Alım — Adaylar')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- ─── Pipeline Özet Chips ─────────────────────────────────────────────────── --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
@foreach(\App\Models\Hr\HrCandidate::$statusLabels as $s => $l)
@php $cnt = $pipeline[$s] ?? 0; @endphp
<a href="/manager/hr/recruitment/candidates?status={{ $s }}{{ $postingId ? '&posting_id='.$postingId : '' }}"
   style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;
          background:{{ $status===$s ? 'var(--u-brand)' : 'var(--u-bg)' }};
          color:{{ $status===$s ? '#fff' : 'var(--u-text)' }};
          border:1.5px solid {{ $status===$s ? 'var(--u-brand)' : 'var(--u-line)' }};">
    {{ $l }}
    <span style="background:{{ $status===$s ? 'rgba(255,255,255,.25)' : 'var(--u-line)' }};border-radius:999px;padding:1px 7px;font-size:11px;">{{ $cnt }}</span>
</a>
@endforeach
@if($status !== '')
<a href="/manager/hr/recruitment/candidates{{ $postingId ? '?posting_id='.$postingId : '' }}" style="display:inline-flex;align-items:center;padding:6px 12px;border-radius:20px;font-size:11px;color:var(--u-muted);border:1.5px solid var(--u-line);text-decoration:none;">✕ Temizle</a>
@endif
</div>

{{-- ─── Filtre + Aksiyon Bar ────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
    <form method="GET" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <select name="posting_id" style="padding:5px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
            <option value="">Tüm İlanlar</option>
            @foreach($postings as $p)
            <option value="{{ $p->id }}" {{ $postingId==(string)$p->id ? 'selected' : '' }}>{{ $p->title }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn alt" style="font-size:11px;padding:5px 12px;">Filtrele</button>
    </form>
    <div style="display:flex;gap:6px;">
        <a href="/manager/hr/recruitment" class="btn" style="font-size:11px;">← İlanlar</a>
        <button type="button" onclick="document.getElementById('new-candidate-panel').style.display='block';this.style.display='none'" class="btn ok" style="font-size:12px;">+ Yeni Aday</button>
    </div>
</div>

{{-- ─── Yeni Aday Formu ─────────────────────────────────────────────────────── --}}
<section id="new-candidate-panel" style="display:none;margin-bottom:20px;">
    <div style="font-size:14px;font-weight:700;color:var(--u-text);margin-bottom:10px;">Yeni Aday Ekle</div>
    <section class="panel" style="padding:18px 20px;">
        <form method="POST" action="/manager/hr/recruitment/candidates" enctype="multipart/form-data">
            @csrf
            <div class="grid2" style="gap:10px;margin-bottom:12px;">
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Ad *</label>
                    <input type="text" name="first_name" required maxlength="80"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Soyad *</label>
                    <input type="text" name="last_name" required maxlength="80"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">E-posta</label>
                    <input type="email" name="email" maxlength="200"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Telefon</label>
                    <input type="text" name="phone" maxlength="30"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">İş İlanı</label>
                    <select name="job_posting_id" style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                        <option value="">— Bağlantısız —</option>
                        @foreach($postings as $p)
                        <option value="{{ $p->id }}" {{ $postingId==(string)$p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Kaynak *</label>
                    <select name="source" required style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                        @foreach(\App\Models\Hr\HrCandidate::$sourceLabels as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Atanan Kişi</label>
                    <select name="assigned_to" style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                        <option value="">— Seç —</option>
                        @foreach($team as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">CV (PDF/DOC)</label>
                    <label for="cv-upload" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;color:var(--u-muted);cursor:pointer;background:var(--u-bg);">📎 Dosya Seç</label>
                    <input type="file" id="cv-upload" name="cv" accept=".pdf,.doc,.docx" style="display:none;">
                    <span id="cv-name" style="font-size:11px;color:var(--u-muted);margin-left:6px;"></span>
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Not</label>
                <textarea name="notes" rows="2" maxlength="1000"
                          style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn ok">Aday Ekle</button>
                <button type="button" onclick="document.getElementById('new-candidate-panel').style.display='none';document.querySelector('.btn.ok[onclick]').style.display=''" class="btn">İptal</button>
            </div>
        </form>
    </section>
</section>

{{-- ─── Adaylar Tablosu ─────────────────────────────────────────────────────── --}}
@if($candidates->isEmpty())
<section class="panel" style="padding:40px;text-align:center;">
    <div style="font-size:32px;margin-bottom:8px;">👥</div>
    <div style="font-size:14px;color:var(--u-muted);">Bu filtreye ait aday bulunamadı.</div>
</section>
@else
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:var(--u-bg);">
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Aday</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">İlan</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Kaynak</th>
                <th style="padding:8px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Durum</th>
                <th style="padding:8px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Puan</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Atanan</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Tarih</th>
                <th style="padding:8px 12px;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">İşlem</th>
            </tr></thead>
            <tbody>
            @foreach($candidates as $c)
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:8px 12px;">
                    <div style="font-weight:600;color:var(--u-text);">{{ $c->fullName() }}</div>
                    @if($c->email)<div style="font-size:11px;color:var(--u-muted);">{{ $c->email }}</div>@endif
                </td>
                <td style="padding:8px 12px;color:var(--u-muted);font-size:11px;">{{ $c->posting?->title ?? '—' }}</td>
                <td style="padding:8px 12px;font-size:11px;">{{ \App\Models\Hr\HrCandidate::$sourceLabels[$c->source] ?? $c->source }}</td>
                <td style="padding:8px 12px;text-align:center;">
                    <span class="badge {{ \App\Models\Hr\HrCandidate::$statusBadge[$c->status] ?? 'info' }}">{{ \App\Models\Hr\HrCandidate::$statusLabels[$c->status] ?? $c->status }}</span>
                </td>
                <td style="padding:8px 12px;text-align:center;">
                    @if($c->rating)
                    <span style="font-weight:700;color:#d97706;">{{ str_repeat('★',$c->rating) }}{{ str_repeat('☆',5-$c->rating) }}</span>
                    @else<span style="color:var(--u-muted);">—</span>@endif
                </td>
                <td style="padding:8px 12px;font-size:11px;color:var(--u-muted);">{{ $c->assignedTo?->name ?? '—' }}</td>
                <td style="padding:8px 12px;font-size:11px;color:var(--u-muted);white-space:nowrap;">{{ $c->created_at->format('d.m.Y') }}</td>
                <td style="padding:8px 12px;">
                    <a href="/manager/hr/recruitment/candidates/{{ $c->id }}" class="btn alt" style="font-size:10px;padding:3px 10px;">Detay →</a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

@endsection

@push('scripts')
<script>
document.getElementById('cv-upload').addEventListener('change', function() {
    var nm = document.getElementById('cv-name');
    nm.textContent = this.files[0] ? this.files[0].name : '';
});
</script>
@endpush

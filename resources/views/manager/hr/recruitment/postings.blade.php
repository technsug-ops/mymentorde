@extends('manager.layouts.app')

@section('title', 'İş İlanları')
@section('page_title', 'İşe Alım — İş İlanları')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- ─── Üst Bar ─────────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="/manager/hr/recruitment" class="btn{{ $status==='' ? ' ok' : '' }}" style="font-size:11px;padding:5px 14px;">Tümü <span style="opacity:.7;">({{ $stats['total'] }})</span></a>
        <a href="/manager/hr/recruitment?status=active" class="btn{{ $status==='active' ? ' ok' : ' alt' }}" style="font-size:11px;padding:5px 14px;">Aktif ({{ $stats['active'] }})</a>
        <a href="/manager/hr/recruitment?status=draft" class="btn{{ $status==='draft' ? ' ok' : '' }}" style="font-size:11px;padding:5px 14px;">Taslak ({{ $stats['draft'] }})</a>
        <a href="/manager/hr/recruitment?status=closed" class="btn{{ $status==='closed' ? ' ok' : '' }}" style="font-size:11px;padding:5px 14px;">Kapandı ({{ $stats['closed'] }})</a>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/manager/hr/recruitment/candidates" class="btn alt" style="font-size:12px;">👥 Adaylar</a>
        <a href="/manager/hr/recruitment/onboarding" class="btn alt" style="font-size:12px;">🎯 Onboarding</a>
        <button type="button" onclick="document.getElementById('new-posting-panel').style.display='block';this.style.display='none'" class="btn ok" style="font-size:12px;">+ Yeni İlan</button>
    </div>
</div>

{{-- ─── Yeni İlan Formu ─────────────────────────────────────────────────────── --}}
<section id="new-posting-panel" style="display:none;margin-bottom:20px;">
    <div style="font-size:14px;font-weight:700;color:var(--u-text);margin-bottom:10px;">Yeni İş İlanı</div>
    <section class="panel" style="padding:18px 20px;">
        <form method="POST" action="/manager/hr/recruitment/postings">
            @csrf
            <div class="grid2" style="gap:12px;margin-bottom:12px;">
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Pozisyon Başlığı *</label>
                    <input type="text" name="title" required maxlength="150" placeholder="ör. Senior Backend Developer"
                           style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Departman</label>
                    <input type="text" name="department" maxlength="80" placeholder="ör. Yazılım, Pazarlama…"
                           style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Çalışma Şekli *</label>
                    <select name="employment_type" required style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                        <option value="full_time">Tam Zamanlı</option>
                        <option value="part_time">Yarı Zamanlı</option>
                        <option value="internship">Staj</option>
                        <option value="freelance">Freelance</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Durum *</label>
                    <select name="status" required style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                        <option value="draft">Taslak</option>
                        <option value="active">Aktif (Yayınla)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Lokasyon</label>
                    <input type="text" name="location" maxlength="100" placeholder="ör. Berlin / Remote"
                           style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Son Başvuru Tarihi</label>
                    <input type="date" name="deadline_at"
                           style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Açıklama</label>
                <textarea name="description" rows="3" maxlength="5000" placeholder="Pozisyon hakkında kısa açıklama…"
                          style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);resize:vertical;"></textarea>
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Gereksinimler</label>
                <textarea name="requirements" rows="3" maxlength="3000" placeholder="Aranan nitelikler, deneyim, teknik beceriler…"
                          style="width:100%;box-sizing:border-box;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn ok">İlanı Oluştur</button>
                <button type="button" onclick="document.getElementById('new-posting-panel').style.display='none';document.querySelector('.btn.ok[onclick]').style.display=''" class="btn">İptal</button>
            </div>
        </form>
    </section>
</section>

{{-- ─── İlanlar Listesi ─────────────────────────────────────────────────────── --}}
@if($postings->isEmpty())
<section class="panel" style="padding:40px;text-align:center;">
    <div style="font-size:32px;margin-bottom:8px;">📋</div>
    <div style="font-size:14px;color:var(--u-muted);">Henüz iş ilanı yok. "Yeni İlan" ile başlayın.</div>
</section>
@else
<div style="display:flex;flex-direction:column;gap:10px;">
@foreach($postings as $posting)
@php
    $statusColors = ['active'=>'ok','draft'=>'pending','paused'=>'warn','closed'=>'info'];
    $statusLabels = ['active'=>'Aktif','draft'=>'Taslak','paused'=>'Duraklatıldı','closed'=>'Kapandı'];
    $empLabels    = ['full_time'=>'Tam Zamanlı','part_time'=>'Yarı Zamanlı','internship'=>'Staj','freelance'=>'Freelance'];
@endphp
<section class="panel" style="padding:14px 18px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                <span style="font-size:15px;font-weight:700;color:var(--u-text);">{{ $posting->title }}</span>
                <span class="badge {{ $statusColors[$posting->status] ?? 'info' }}">{{ $statusLabels[$posting->status] ?? $posting->status }}</span>
                @if($posting->is_remote)
                <span class="badge info" style="font-size:10px;">🌐 Remote</span>
                @endif
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:11px;color:var(--u-muted);">
                @if($posting->department)<span>🏢 {{ $posting->department }}</span>@endif
                <span>⏱ {{ $empLabels[$posting->employment_type] ?? $posting->employment_type }}</span>
                @if($posting->location)<span>📍 {{ $posting->location }}</span>@endif
                @if($posting->deadline_at)<span>⏰ Son: {{ $posting->deadline_at->format('d.m.Y') }}</span>@endif
                <span>👥 {{ $posting->candidates_count }} aday</span>
                <span style="color:var(--u-muted);">{{ $posting->created_at->format('d.m.Y') }}</span>
            </div>
            @if($posting->description)
            <div style="font-size:12px;color:var(--u-muted);margin-top:6px;max-width:600px;">{{ Str::limit($posting->description, 120) }}</div>
            @endif
        </div>
        <div style="display:flex;gap:6px;align-items:flex-start;flex-wrap:wrap;">
            <a href="/manager/hr/recruitment/candidates?posting_id={{ $posting->id }}" class="btn alt" style="font-size:11px;padding:4px 12px;">Adaylar ({{ $posting->candidates_count }})</a>
            <button type="button" onclick="toggleEdit({{ $posting->id }})" class="btn" style="font-size:11px;padding:4px 10px;">✏️ Düzenle</button>
        </div>
    </div>

    {{-- Düzenleme formu (gizli) --}}
    <div id="edit-{{ $posting->id }}" style="display:none;margin-top:14px;padding-top:14px;border-top:1px solid var(--u-line);">
        <form method="POST" action="/manager/hr/recruitment/postings/{{ $posting->id }}">
            @csrf @method('PUT')
            <div class="grid2" style="gap:10px;margin-bottom:10px;">
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Başlık *</label>
                    <input type="text" name="title" required value="{{ $posting->title }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Durum *</label>
                    <select name="status" required style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                        @foreach(['draft'=>'Taslak','active'=>'Aktif','paused'=>'Duraklatıldı','closed'=>'Kapandı'] as $v=>$l)
                        <option value="{{ $v }}" {{ $posting->status===$v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Çalışma Şekli *</label>
                    <select name="employment_type" required style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                        @foreach(['full_time'=>'Tam Zamanlı','part_time'=>'Yarı Zamanlı','internship'=>'Staj','freelance'=>'Freelance'] as $v=>$l)
                        <option value="{{ $v }}" {{ $posting->employment_type===$v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Lokasyon</label>
                    <input type="text" name="location" value="{{ $posting->location }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Son Başvuru Tarihi</label>
                    <input type="date" name="deadline_at" value="{{ $posting->deadline_at?->format('Y-m-d') }}"
                           style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
            </div>
            <div style="margin-bottom:8px;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Açıklama</label>
                <textarea name="description" rows="2" maxlength="5000"
                          style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);resize:vertical;">{{ $posting->description }}</textarea>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Gereksinimler</label>
                <textarea name="requirements" rows="2" maxlength="3000"
                          style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);resize:vertical;">{{ $posting->requirements }}</textarea>
            </div>
            <div style="display:flex;gap:6px;">
                <button type="submit" class="btn ok" style="font-size:11px;">Kaydet</button>
                <button type="button" onclick="toggleEdit({{ $posting->id }})" class="btn" style="font-size:11px;">İptal</button>
            </div>
        </form>
    </div>
</section>
@endforeach
</div>
@endif

@endsection

@push('scripts')
<script>
function toggleEdit(id) {
    var el = document.getElementById('edit-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush

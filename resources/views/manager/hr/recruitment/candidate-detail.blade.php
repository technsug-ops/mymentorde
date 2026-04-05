@extends('manager.layouts.app')

@section('title', $candidate->fullName() . ' — Aday Detayı')
@section('page_title', 'Aday: ' . $candidate->fullName())

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

<div style="margin-bottom:14px;">
    <a href="/manager/hr/recruitment/candidates" style="font-size:12px;color:var(--u-muted);text-decoration:none;">← Adaylar</a>
</div>

<div class="grid2" style="gap:16px;align-items:start;">

    {{-- ─── Sol: Aday Bilgileri + Durum ─────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Profil Kartı --}}
        <section class="panel" style="padding:18px 20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:48px;height:48px;border-radius:50%;background:var(--u-brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0;">
                    {{ mb_strtoupper(mb_substr($candidate->first_name,0,1)) }}
                </div>
                <div>
                    <div style="font-size:17px;font-weight:700;color:var(--u-text);">{{ $candidate->fullName() }}</div>
                    <div style="font-size:12px;color:var(--u-muted);">
                        <span class="badge {{ \App\Models\Hr\HrCandidate::$statusBadge[$candidate->status] ?? 'info' }}">{{ \App\Models\Hr\HrCandidate::$statusLabels[$candidate->status] ?? $candidate->status }}</span>
                        @if($candidate->posting)
                        <span style="margin-left:6px;">{{ $candidate->posting->title }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;font-size:12px;">
                @if($candidate->email)
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">E-posta</span><a href="mailto:{{ $candidate->email }}" style="color:var(--u-brand);">{{ $candidate->email }}</a></div>
                @endif
                @if($candidate->phone)
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">Telefon</span><span>{{ $candidate->phone }}</span></div>
                @endif
                @if($candidate->linkedin_url)
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">LinkedIn</span><a href="{{ $candidate->linkedin_url }}" target="_blank" rel="noopener" style="color:var(--u-brand);">Profil →</a></div>
                @endif
                @if($candidate->portfolio_url)
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">Portföy</span><a href="{{ $candidate->portfolio_url }}" target="_blank" rel="noopener" style="color:var(--u-brand);">Link →</a></div>
                @endif
                @if($candidate->cv_path)
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">CV</span><span style="display:inline-flex;align-items:center;gap:4px;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:5px;padding:2px 8px;color:#7c3aed;font-size:11px;font-weight:600;">📎 CV Yüklendi</span></div>
                @endif
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">Kaynak</span><span>{{ \App\Models\Hr\HrCandidate::$sourceLabels[$candidate->source] ?? $candidate->source }}</span></div>
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">Başvuru</span><span>{{ $candidate->created_at->format('d.m.Y') }}</span></div>
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">Atanan</span><span>{{ $candidate->assignedTo?->name ?? '—' }}</span></div>
                @if($candidate->rating)
                <div style="display:flex;gap:8px;"><span style="color:var(--u-muted);width:80px;flex-shrink:0;">Puan</span><span style="color:#d97706;font-weight:700;">{{ str_repeat('★',$candidate->rating) }}{{ str_repeat('☆',5-$candidate->rating) }}</span></div>
                @endif
            </div>
            @if($candidate->notes)
            <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--u-line);">
                <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:4px;">Notlar</div>
                <div style="font-size:12px;color:var(--u-text);">{{ $candidate->notes }}</div>
            </div>
            @endif
            @if($candidate->rejection_reason)
            <div style="margin-top:10px;padding:8px 12px;border-radius:8px;background:#fef2f2;border:1px solid #fca5a5;font-size:12px;color:#991b1b;">
                <strong>Red Gerekçesi:</strong> {{ $candidate->rejection_reason }}
            </div>
            @endif
        </section>

        {{-- Durum Güncelle --}}
        <section class="panel" style="padding:16px 20px;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text);margin-bottom:12px;">Durumu Güncelle</div>
            <form method="POST" action="/manager/hr/recruitment/candidates/{{ $candidate->id }}/status">
                @csrf @method('PATCH')
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div>
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Durum</label>
                        <select name="status" required style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                            @foreach(\App\Models\Hr\HrCandidate::$statusLabels as $v => $l)
                            <option value="{{ $v }}" {{ $candidate->status===$v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Puan (1–5)</label>
                        <select name="rating" style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                            <option value="">— Puan ver —</option>
                            @for($i=1;$i<=5;$i++)
                            <option value="{{ $i }}" {{ $candidate->rating==$i ? 'selected' : '' }}>{{ str_repeat('★',$i) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Atanan Kişi</label>
                        <select name="assigned_to" style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                            <option value="">— Seç —</option>
                            @foreach($team as $m)
                            <option value="{{ $m->id }}" {{ $candidate->assigned_to==$m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Not</label>
                        <textarea name="notes" rows="2" maxlength="1000" style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);resize:vertical;">{{ $candidate->notes }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Red Gerekçesi (reddedilirse)</label>
                        <input type="text" name="rejection_reason" value="{{ $candidate->rejection_reason }}" maxlength="300"
                               style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                    </div>
                    <button type="submit" class="btn ok" style="font-size:12px;">Güncelle</button>
                </div>
            </form>
        </section>
    </div>

    {{-- ─── Sağ: Mülakatlar ─────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Mülakat Planla --}}
        <section class="panel" style="padding:16px 20px;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text);margin-bottom:12px;">🗓 Mülakat Planla</div>
            <form method="POST" action="/manager/hr/recruitment/candidates/{{ $candidate->id }}/interviews">
                @csrf
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div>
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Görüşmeci *</label>
                        <select name="interviewer_user_id" required style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                            <option value="">— Seç —</option>
                            @foreach($team as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid2" style="gap:8px;">
                        <div>
                            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Tarih & Saat *</label>
                            <input type="datetime-local" name="scheduled_at" required
                                   style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                        </div>
                        <div>
                            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Süre (dk) *</label>
                            <input type="number" name="duration_minutes" value="60" min="15" max="240" required
                                   style="width:100%;box-sizing:border-box;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Tür *</label>
                        <select name="type" required style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                            @foreach(\App\Models\Hr\HrInterview::$typeLabels as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn ok" style="font-size:12px;">Mülakatı Planla</button>
                </div>
            </form>
        </section>

        {{-- Mülakat Geçmişi --}}
        <section class="panel" style="padding:16px 20px;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text);margin-bottom:12px;">📋 Mülakat Geçmişi ({{ $candidate->interviews->count() }})</div>
            @if($candidate->interviews->isEmpty())
            <div style="font-size:12px;color:var(--u-muted);text-align:center;padding:16px 0;">Henüz mülakat planlanmadı.</div>
            @else
            <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($candidate->interviews as $iv)
            @php
                $ivStatusColor = ['scheduled'=>'warn','completed'=>'ok','cancelled'=>'danger','no_show'=>'info'];
            @endphp
            <div style="padding:10px 14px;border-radius:8px;background:var(--u-bg);border:1px solid var(--u-line);">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-bottom:6px;">
                    <div style="font-size:12px;font-weight:600;color:var(--u-text);">
                        {{ \App\Models\Hr\HrInterview::$typeLabels[$iv->type] ?? $iv->type }}
                        <span style="font-weight:400;color:var(--u-muted);margin-left:6px;">— {{ $iv->interviewer?->name }}</span>
                    </div>
                    <span class="badge {{ $ivStatusColor[$iv->status] ?? 'info' }}" style="font-size:10px;">{{ \App\Models\Hr\HrInterview::$statusLabels[$iv->status] ?? $iv->status }}</span>
                </div>
                <div style="font-size:11px;color:var(--u-muted);margin-bottom:8px;">
                    📅 {{ $iv->scheduled_at->format('d.m.Y H:i') }} &nbsp;·&nbsp; ⏱ {{ $iv->duration_minutes }} dk
                    @if($iv->score) &nbsp;·&nbsp; Skor: <strong style="color:var(--u-text);">{{ $iv->score }}/10</strong>@endif
                    @if($iv->recommendation) &nbsp;·&nbsp; {{ \App\Models\Hr\HrInterview::$recommendationLabels[$iv->recommendation] ?? '' }}@endif
                </div>
                @if($iv->feedback)
                <div style="font-size:11px;color:var(--u-text);background:#fff;border:1px solid var(--u-line);border-radius:6px;padding:6px 10px;margin-bottom:8px;">{{ $iv->feedback }}</div>
                @endif
                {{-- Güncelleme formu --}}
                <details style="margin-top:4px;">
                    <summary style="font-size:11px;color:var(--u-muted);cursor:pointer;list-style:none;">✏️ Güncelle</summary>
                    <form method="POST" action="/manager/hr/recruitment/interviews/{{ $iv->id }}" style="margin-top:8px;">
                        @csrf @method('PATCH')
                        <div class="grid2" style="gap:8px;margin-bottom:8px;">
                            <div>
                                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Durum</label>
                                <select name="status" style="width:100%;padding:5px 8px;border:1.5px solid var(--u-line);border-radius:6px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                                    @foreach(\App\Models\Hr\HrInterview::$statusLabels as $v => $l)
                                    <option value="{{ $v }}" {{ $iv->status===$v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Skor (1–10)</label>
                                <input type="number" name="score" value="{{ $iv->score }}" min="1" max="10"
                                       style="width:100%;box-sizing:border-box;padding:5px 8px;border:1.5px solid var(--u-line);border-radius:6px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                            </div>
                            <div>
                                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Öneri</label>
                                <select name="recommendation" style="width:100%;padding:5px 8px;border:1.5px solid var(--u-line);border-radius:6px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                                    <option value="">— Seç —</option>
                                    @foreach(\App\Models\Hr\HrInterview::$recommendationLabels as $v => $l)
                                    <option value="{{ $v }}" {{ $iv->recommendation===$v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Geri Bildirim</label>
                            <textarea name="feedback" rows="2" maxlength="2000"
                                      style="width:100%;box-sizing:border-box;padding:5px 8px;border:1.5px solid var(--u-line);border-radius:6px;font-size:12px;background:var(--u-bg);color:var(--u-text);resize:vertical;">{{ $iv->feedback }}</textarea>
                        </div>
                        <button type="submit" class="btn ok" style="font-size:11px;padding:4px 14px;">Kaydet</button>
                    </form>
                </details>
            </div>
            @endforeach
            </div>
            @endif
        </section>

    </div>
</div>

@endsection

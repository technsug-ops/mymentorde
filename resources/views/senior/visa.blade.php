@extends('senior.layouts.app')
@section('title', 'Vize Takibi')
@section('page_title', 'Vize Takibi')

@section('content')

@if(session('status'))
<div style="padding:10px 16px;border-radius:8px;background:#16a34a;color:#fff;margin-bottom:14px;font-weight:600;font-size:var(--tx-sm);">✓ {{ session('status') }}</div>
@endif

@php
$allVisas     = \App\Models\StudentVisaApplication::whereIn('student_id', $assignedIds);
$approvedCnt  = (clone $allVisas)->where('status','approved')->count();
$inReviewCnt  = (clone $allVisas)->where('status','in_review')->count();
$preparingCnt = (clone $allVisas)->whereIn('status',['not_started','preparing'])->count();
$totalCnt     = $visas->total();

$studentNameMap = $assignments->pluck('student_name','student_id')->toArray();
@endphp

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#0e7490,#0891b2);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">🛂 Vize Takibi</div>
    <div style="font-size:var(--tx-sm);opacity:.8;margin-bottom:16px;">Öğrencilerin vize başvuru süreçleri</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach([
            ['label'=>'Toplam',       'count'=>$totalCnt,     'st'=>''],
            ['label'=>'Hazırlık',     'count'=>$preparingCnt, 'st'=>'preparing'],
            ['label'=>'İnceleniyor',  'count'=>$inReviewCnt,  'st'=>'in_review'],
            ['label'=>'Onaylandı',    'count'=>$approvedCnt,  'st'=>'approved'],
        ] as $chip)
        @php $active = $filterStatus === $chip['st']; @endphp
        <a href="{{ route('senior.visa', array_filter(['status'=>$chip['st'],'student_id'=>$filterStudent])) }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;font-size:var(--tx-xs);font-weight:700;text-decoration:none;
                  background:{{ $active ? 'rgba(255,255,255,.3)':'rgba(255,255,255,.12)' }};
                  color:#fff;border:1.5px solid {{ $active ? 'rgba(255,255,255,.7)':'rgba(255,255,255,.2)' }};">
            {{ $chip['label'] }}
            <span style="background:rgba(255,255,255,.22);border-radius:999px;padding:1px 8px;font-size:var(--tx-xs);">{{ $chip['count'] }}</span>
        </a>
        @endforeach
    </div>
</div>

{{-- Filtre --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <form method="GET" action="{{ route('senior.visa') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:2;min-width:160px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Öğrenci</div>
            <select name="student_id" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                <option value="">— Tümü —</option>
                @foreach($assignments as $a)
                    <option value="{{ $a->student_id }}" {{ $filterStudent === $a->student_id ? 'selected' : '' }}>{{ $a->student_name }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:130px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Durum</div>
            <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                <option value="">— Tümü —</option>
                @foreach($statusLabels as $val => $lbl)
                    <option value="{{ $val }}" {{ $filterStatus === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="background:#0891b2;color:#fff;border:none;border-radius:7px;padding:9px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Filtrele</button>
            <a href="{{ route('senior.visa') }}" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:9px 14px;font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Sıfırla</a>
        </div>
    </form>
</div>

{{-- Yeni Kayıt Formu --}}
<details style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:16px 18px;margin-bottom:16px;">
    <summary style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);cursor:pointer;list-style:none;">
        ＋ Yeni Vize Kaydı Ekle
    </summary>
    <form method="POST" action="{{ route('senior.visa.store') }}" style="margin-top:16px;">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Öğrenci *</label>
                <select name="student_id" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    <option value="">Seçin</option>
                    @foreach($assignments as $a)
                        <option value="{{ $a->student_id }}">{{ $a->student_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Vize Türü *</label>
                <select name="visa_type" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    @foreach($visaTypeLabels as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Durum *</label>
                <select name="status" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    @foreach($statusLabels as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Konsolosluk Şehri</label>
                <select name="consulate_city" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    <option value="">—</option>
                    <option value="İstanbul">İstanbul</option>
                    <option value="Ankara">Ankara</option>
                    <option value="İzmir">İzmir</option>
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Başvuru Tarihi</label>
                <input type="date" name="application_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Konsolosluk Randevusu</label>
                <input type="date" name="appointment_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Karar Tarihi</label>
                <input type="date" name="decision_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Geçerlilik Başlangıcı</label>
                <input type="date" name="valid_from" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Geçerlilik Bitiş</label>
                <input type="date" name="valid_until" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
        </div>
        <div style="margin-top:12px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:6px;">Sunulan Belgeler</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($documentLabels as $key => $lbl)
                <label style="display:inline-flex;align-items:center;gap:5px;font-size:var(--tx-sm);cursor:pointer;">
                    <input type="checkbox" name="submitted_documents[]" value="{{ $key }}"> {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>
        <div style="margin-top:12px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Not</label>
            <textarea name="notes" rows="2" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);resize:vertical;"></textarea>
        </div>
        <div style="margin-top:12px;display:flex;align-items:center;gap:16px;">
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:var(--tx-sm);cursor:pointer;">
                <input type="checkbox" name="is_visible_to_student" value="1" checked> Öğrenciye görünür
            </label>
            <button type="submit" style="background:#0891b2;color:#fff;border:none;border-radius:7px;padding:9px 22px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Kaydet</button>
        </div>
    </form>
</details>

{{-- Liste --}}
@forelse($visas as $v)
@php $sName = $studentNameMap[$v->student_id] ?? $v->student_id; @endphp
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;overflow:hidden;margin-bottom:10px;">
    <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;border-bottom:1px solid var(--u-line);">
        <div style="width:42px;height:42px;border-radius:10px;background:rgba(8,145,178,.1);border:1px solid rgba(8,145,178,.2);display:flex;align-items:center;justify-content:center;font-size:var(--tx-xl);flex-shrink:0;">
            {{ $v->status === 'approved' ? '✅' : ($v->status === 'rejected' ? '❌' : '🛂') }}
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">{{ $sName }}</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);">
                {{ $v->visaTypeLabel() }}
                @if($v->consulate_city) · {{ $v->consulate_city }} @endif
                @if($v->appointment_date) · Randevu: {{ $v->appointment_date->format('d.m.Y') }} @endif
            </div>
        </div>
        <span class="badge {{ $v->statusBadge() }}">{{ $v->statusLabel() }}</span>
        <div style="display:flex;gap:6px;flex-shrink:0;">
            <form method="POST" action="{{ route('senior.visa.visibility', $v->id) }}" style="display:inline;">
                @csrf
                <button type="submit" title="{{ $v->is_visible_to_student ? 'Öğrenciden gizle' : 'Öğrenciye göster' }}"
                    style="background:{{ $v->is_visible_to_student ? 'rgba(22,163,74,.1)':'var(--u-bg)' }};border:1px solid var(--u-line);border-radius:6px;padding:5px 10px;font-size:var(--tx-xs);cursor:pointer;">
                    {{ $v->is_visible_to_student ? '👁 Görünür' : '🙈 Gizli' }}
                </button>
            </form>
            <button onclick="document.getElementById('vEdit{{ $v->id }}').style.display='block';this.parentNode.parentNode.nextElementSibling.style.display='block'"
                style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:6px;padding:5px 10px;font-size:var(--tx-xs);cursor:pointer;">✏️ Güncelle</button>
            <form method="POST" action="{{ route('senior.visa.delete', $v->id) }}" onsubmit="return confirm('Sil?')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" style="background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);border-radius:6px;padding:5px 10px;font-size:var(--tx-xs);cursor:pointer;color:#dc2626;">🗑</button>
            </form>
        </div>
    </div>

    {{-- Güncelle formu (gizli) --}}
    <div id="vEdit{{ $v->id }}" style="display:none;padding:14px 16px;background:var(--u-bg);">
        <form method="POST" action="{{ route('senior.visa.update', $v->id) }}">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:10px;">
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Durum</label>
                    <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        @foreach($statusLabels as $val => $lbl)
                            <option value="{{ $val }}" {{ $v->status === $val ? 'selected':'' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Randevu</label>
                    <input type="date" name="appointment_date" value="{{ $v->appointment_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Karar Tarihi</label>
                    <input type="date" name="decision_date" value="{{ $v->decision_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Geçerli Başlangıç</label>
                    <input type="date" name="valid_from" value="{{ $v->valid_from?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Geçerli Bitiş</label>
                    <input type="date" name="valid_until" value="{{ $v->valid_until?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Vize Numarası</label>
                    <input type="text" name="visa_number" value="{{ $v->visa_number }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Sunulan Belgeler</label>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    @foreach($documentLabels as $key => $lbl)
                    <label style="display:inline-flex;align-items:center;gap:5px;font-size:var(--tx-sm);cursor:pointer;">
                        <input type="checkbox" name="submitted_documents[]" value="{{ $key }}"
                            {{ in_array($key, $v->submitted_documents ?? []) ? 'checked':'' }}> {{ $lbl }}
                    </label>
                    @endforeach
                </div>
            </div>
            <textarea name="notes" rows="2" placeholder="Not..." style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;margin-bottom:8px;">{{ $v->notes }}</textarea>
            <textarea name="rejection_reason" rows="2" placeholder="Red gerekçesi (varsa)..." style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;margin-bottom:8px;">{{ $v->rejection_reason }}</textarea>
            <div style="display:flex;align-items:center;gap:16px;">
                <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <input type="checkbox" name="is_visible_to_student" value="1" {{ $v->is_visible_to_student ? 'checked':'' }}> Öğrenciye görünür
                </label>
                <button type="submit" style="background:#0891b2;color:#fff;border:none;border-radius:6px;padding:7px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Güncelle</button>
            </div>
        </form>
    </div>
</div>
@empty
<div style="text-align:center;padding:48px;background:var(--u-card);border:1px solid var(--u-line);border-radius:14px;color:var(--u-muted);">
    <div style="font-size:40px;margin-bottom:8px;">🛂</div>
    <div style="font-size:var(--tx-base);font-weight:700;margin-bottom:4px;">Vize kaydı bulunamadı</div>
    <div style="font-size:var(--tx-sm);">Yeni kayıt eklemek için yukarıdaki formu kullanın.</div>
</div>
@endforelse

{{ $visas->withQueryString()->links('partials.pagination') }}

@endsection

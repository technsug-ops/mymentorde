@extends('senior.layouts.app')
@section('title', 'Konut Takibi')
@section('page_title', 'Konut Takibi')

@section('content')

@if(session('status'))
<div style="padding:10px 16px;border-radius:8px;background:#16a34a;color:#fff;margin-bottom:14px;font-weight:600;font-size:var(--tx-sm);">✓ {{ session('status') }}</div>
@endif

@php
$allHsg      = \App\Models\StudentAccommodation::whereIn('student_id', $assignedIds);
$confirmedCnt= (clone $allHsg)->where('booking_status','confirmed')->count();
$bookedCnt   = (clone $allHsg)->where('booking_status','booked')->count();
$searchingCnt= (clone $allHsg)->where('booking_status','searching')->count();
$totalCnt    = $accommodations->total();

$studentNameMap = $assignments->pluck('student_name','student_id')->toArray();
@endphp

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#047857,#059669);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">🏠 Konut Takibi</div>
    <div style="font-size:var(--tx-sm);opacity:.8;margin-bottom:16px;">Öğrencilerin konut ve barınma durumları</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach([
            ['label'=>'Toplam',      'count'=>$totalCnt,     'st'=>''],
            ['label'=>'Aranıyor',    'count'=>$searchingCnt, 'st'=>'searching'],
            ['label'=>'Rezerve',     'count'=>$bookedCnt,    'st'=>'booked'],
            ['label'=>'Onaylandı',   'count'=>$confirmedCnt, 'st'=>'confirmed'],
        ] as $chip)
        @php $active = $filterStatus === $chip['st']; @endphp
        <a href="{{ route('senior.housing', array_filter(['status'=>$chip['st'],'student_id'=>$filterStudent])) }}"
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
    <form method="GET" action="{{ route('senior.housing') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
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
            <button type="submit" style="background:#059669;color:#fff;border:none;border-radius:7px;padding:9px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Filtrele</button>
            <a href="{{ route('senior.housing') }}" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:9px 14px;font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Sıfırla</a>
        </div>
    </form>
</div>

{{-- Yeni Kayıt Formu --}}
<details style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:16px 18px;margin-bottom:16px;">
    <summary style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);cursor:pointer;list-style:none;">
        ＋ Yeni Konut Kaydı Ekle
    </summary>
    <form method="POST" action="{{ route('senior.housing.store') }}" style="margin-top:16px;">
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
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Konut Türü *</label>
                <select name="type" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    @foreach($typeLabels as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Rezervasyon Durumu *</label>
                <select name="booking_status" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    @foreach($statusLabels as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:span 2;">
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Adres</label>
                <input type="text" name="address" placeholder="Straße 12, Wohnung 3" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Şehir</label>
                <input type="text" name="city" placeholder="Berlin" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Aylık Kira (€)</label>
                <input type="number" name="monthly_cost_eur" min="0" max="9999" step="0.01" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Taşınma Tarihi</label>
                <input type="date" name="move_in_date" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Ev Sahibi / İletişim</label>
                <input type="text" name="landlord_name" placeholder="Ad Soyad" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Ev Sahibi Telefon</label>
                <input type="text" name="landlord_phone" placeholder="+49..." style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
        </div>
        <div style="margin-top:12px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:4px;">Not</label>
            <textarea name="notes" rows="2" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);resize:vertical;"></textarea>
        </div>
        <div style="margin-top:12px;display:flex;align-items:center;gap:16px;">
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <input type="checkbox" name="utilities_included" value="1"> Faturalar dahil
            </label>
            <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <input type="checkbox" name="is_visible_to_student" value="1" checked> Öğrenciye görünür
            </label>
            <button type="submit" style="background:#059669;color:#fff;border:none;border-radius:7px;padding:9px 22px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Kaydet</button>
        </div>
    </form>
</details>

{{-- Liste --}}
@forelse($accommodations as $acc)
@php $sName = $studentNameMap[$acc->student_id] ?? $acc->student_id; @endphp
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;overflow:hidden;margin-bottom:10px;">
    <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;border-bottom:1px solid var(--u-line);">
        <div style="width:42px;height:42px;border-radius:10px;background:rgba(5,150,105,.1);border:1px solid rgba(5,150,105,.2);display:flex;align-items:center;justify-content:center;font-size:var(--tx-xl);flex-shrink:0;">
            {{ $acc->booking_status === 'confirmed' ? '🏠' : ($acc->booking_status === 'searching' ? '🔍' : '🔑') }}
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">{{ $sName }}</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);">
                {{ $typeLabels[$acc->type] ?? $acc->type }}
                @if($acc->city) · {{ $acc->city }} @endif
                @if($acc->monthly_cost_eur) · €{{ number_format($acc->monthly_cost_eur,0) }}/ay @endif
                @if($acc->move_in_date) · Taşınma: {{ $acc->move_in_date->format('d.m.Y') }} @endif
            </div>
        </div>
        <span class="badge {{ $acc->statusBadge() }}">{{ $acc->statusLabel() }}</span>
        <div style="display:flex;gap:6px;flex-shrink:0;">
            <button onclick="document.getElementById('hEdit{{ $acc->id }}').style.display='block'"
                style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:6px;padding:5px 10px;font-size:var(--tx-xs);cursor:pointer;">✏️ Güncelle</button>
            <form method="POST" action="{{ route('senior.housing.delete', $acc->id) }}" onsubmit="return confirm('Sil?')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" style="background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);border-radius:6px;padding:5px 10px;font-size:var(--tx-xs);cursor:pointer;color:#dc2626;">🗑</button>
            </form>
        </div>
    </div>

    {{-- Güncelle formu --}}
    <div id="hEdit{{ $acc->id }}" style="display:none;padding:14px 16px;background:var(--u-bg);">
        <form method="POST" action="{{ route('senior.housing.update', $acc->id) }}">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:10px;">
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Durum</label>
                    <select name="booking_status" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                        @foreach($statusLabels as $val => $lbl)
                            <option value="{{ $val }}" {{ $acc->booking_status === $val ? 'selected':'' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:span 2;">
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Adres</label>
                    <input type="text" name="address" value="{{ $acc->address }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Şehir</label>
                    <input type="text" name="city" value="{{ $acc->city }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Aylık Kira (€)</label>
                    <input type="number" name="monthly_cost_eur" value="{{ $acc->monthly_cost_eur }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Taşınma Tarihi</label>
                    <input type="date" name="move_in_date" value="{{ $acc->move_in_date?->format('Y-m-d') }}" style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);">
                </div>
            </div>
            <textarea name="notes" rows="2" placeholder="Not..." style="width:100%;border:1px solid var(--u-line);border-radius:6px;padding:7px 9px;font-size:var(--tx-sm);background:var(--u-card);color:var(--u-text);resize:vertical;margin-bottom:8px;">{{ $acc->notes }}</textarea>
            <div style="display:flex;align-items:center;gap:16px;">
                <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <input type="checkbox" name="utilities_included" value="1" {{ $acc->utilities_included ? 'checked':'' }}> Faturalar dahil
                </label>
                <label style="font-size:var(--tx-sm);cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <input type="checkbox" name="is_visible_to_student" value="1" {{ $acc->is_visible_to_student ? 'checked':'' }}> Öğrenciye görünür
                </label>
                <button type="submit" style="background:#059669;color:#fff;border:none;border-radius:6px;padding:7px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Güncelle</button>
            </div>
        </form>
    </div>
</div>
@empty
<div style="text-align:center;padding:48px;background:var(--u-card);border:1px solid var(--u-line);border-radius:14px;color:var(--u-muted);">
    <div style="font-size:40px;margin-bottom:8px;">🏠</div>
    <div style="font-size:var(--tx-base);font-weight:700;margin-bottom:4px;">Konut kaydı bulunamadı</div>
    <div style="font-size:var(--tx-sm);">Yeni kayıt eklemek için yukarıdaki formu kullanın.</div>
</div>
@endforelse

{{ $accommodations->withQueryString()->links('partials.pagination') }}

@endsection

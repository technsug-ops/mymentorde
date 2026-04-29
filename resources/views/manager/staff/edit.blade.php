@extends('manager.layouts.app')

@section('title', 'Personel Düzenle')
@section('page_title', 'Personel Düzenle')

@section('content')

<div style="margin-bottom:12px;">
    <a href="/manager/staff/{{ $user->id }}" style="font-size:var(--tx-sm);color:#7c3aed;font-weight:700;text-decoration:none;">← Geri</a>
</div>

<div style="max-width:560px;">
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:24px;">
    <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:20px;">Personel Bilgilerini Düzenle</div>

    @if($errors->any())
    <div style="margin-bottom:14px;padding:10px 14px;border-radius:8px;background:#fef2f2;color:#dc2626;font-size:12px;border:1px solid #fecaca;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="/manager/staff/{{ $user->id }}">
        @csrf
        @method('PUT')

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">Ad Soyad *</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                   style="width:100%;padding:9px 12px;border:2px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='var(--u-line)'">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">E-posta</label>
            <input type="email" value="{{ $user->email }}" disabled
                   style="width:100%;padding:9px 12px;border:2px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-muted);box-sizing:border-box;cursor:not-allowed;">
            <div style="font-size:11px;color:var(--u-muted);margin-top:3px;">E-posta değiştirilemez.</div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">Departman & Tür *</label>
            <select name="role" required
                    style="width:100%;padding:9px 12px;border:2px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='var(--u-line)'">
                @foreach($deptLabels as $deptKey => $deptName)
                <optgroup label="{{ $deptName }}">
                    @foreach($deptMap[$deptKey] as $roleVal)
                    <option value="{{ $roleVal }}" @selected(old('role', $user->role) === $roleVal)>
                        {{ $roleLabels[$roleVal] ?? $roleVal }}
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                       style="width:16px;height:16px;">
                <span style="font-size:13px;font-weight:600;color:var(--u-text);">Aktif</span>
            </label>
        </div>

        {{-- Senior için Hedef Kitle (track) — /randevu sayfasında kategorize görünür --}}
        @php
            $currentTags = method_exists($user, 'expertiseTags') ? array_map('strtolower', $user->expertiseTags()) : [];
            $hasTrack = function(string $code) use ($currentTags): bool {
                if ($code === 'bachelor') return in_array('bachelor', $currentTags, true) || in_array('lisans', $currentTags, true);
                if ($code === 'master')   return in_array('master', $currentTags, true) || in_array('yuksek_lisans', $currentTags, true) || in_array('yüksek_lisans', $currentTags, true);
                return in_array('other', $currentTags, true);
            };
            $tracks = [
                'bachelor' => ['emoji' => '🎓', 'title' => 'Bachelor', 'sub' => 'Lisans danışmanlığı'],
                'master'   => ['emoji' => '🎯', 'title' => 'Master',   'sub' => 'Yüksek lisans / Doktora'],
                'other'    => ['emoji' => '🌟', 'title' => 'Diğer',    'sub' => 'Dil okulu / Ausbildung / Vize'],
            ];
        @endphp
        <div id="staff-tracks-row" style="margin-bottom:22px; padding:14px 16px; background:var(--u-bg); border:1px solid var(--u-line); border-radius:10px; {{ $user->role === 'senior' ? '' : 'display:none;' }}">
            <div style="font-size:13px; font-weight:700; color:var(--u-text); margin-bottom:6px;">🎯 Hedef Kitle (Senior için)</div>
            <div style="font-size:11.5px; color:var(--u-muted); margin-bottom:12px;">
                /randevu sayfasında bu danışmanın hangi kategoride görüneceğini belirler. Birden fazla seçilebilir.
            </div>
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px;">
                @foreach($tracks as $code => $t)
                    <label style="display:flex; gap:10px; align-items:flex-start; padding:10px 12px; background:#fff; border:2px solid var(--u-line); border-radius:8px; cursor:pointer; transition:all .15s;"
                           onmouseover="this.style.borderColor='var(--u-brand)'"
                           onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--u-brand)':'var(--u-line)'">
                        <input type="checkbox" name="tracks[]" value="{{ $code }}"
                               {{ $hasTrack($code) ? 'checked' : '' }}
                               style="width:16px; height:16px; margin-top:2px;"
                               onchange="this.closest('label').style.borderColor=this.checked?'var(--u-brand)':'var(--u-line)';this.closest('label').style.background=this.checked?'rgba(126,88,191,.06)':'#fff';">
                        <div>
                            <div style="font-size:13px; font-weight:700; color:var(--u-text);">{{ $t['emoji'] }} {{ $t['title'] }}</div>
                            <div style="font-size:11px; color:var(--u-muted); margin-top:2px;">{{ $t['sub'] }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn ok" style="padding:9px 20px;">Kaydet</button>
            <a href="/manager/staff/{{ $user->id }}" class="btn alt" style="padding:9px 16px;">İptal</a>
        </div>

        <script>
            // Role değiştiğinde Hedef Kitle bloğunu sadece senior için göster
            (function(){
                var roleSelect = document.querySelector('select[name=role]');
                var trackBlock = document.getElementById('staff-tracks-row');
                if (!roleSelect || !trackBlock) return;
                roleSelect.addEventListener('change', function(){
                    trackBlock.style.display = (roleSelect.value === 'senior') ? '' : 'none';
                });
                // İlk yüklemede checkboxları görsel olarak işaretle
                trackBlock.querySelectorAll('input[type=checkbox]').forEach(function(cb){
                    if (cb.checked) {
                        cb.closest('label').style.borderColor = 'var(--u-brand)';
                        cb.closest('label').style.background = 'rgba(126,88,191,.06)';
                    }
                });
            })();
        </script>
    </form>
</div>
</div>

@endsection

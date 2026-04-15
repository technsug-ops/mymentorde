@extends('manager.layouts.app')
@section('title', $bulletin ? 'Duyuru Düzenle' : 'Yeni Duyuru')
@section('page_title', $bulletin ? 'Duyuru Düzenle' : 'Yeni Duyuru')

@section('content')
<div style="max-width:680px;">

@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#991b1b;margin-bottom:16px;font-size:13px;">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<section class="panel" style="padding:24px 28px;">
    <form method="POST" action="{{ $bulletin ? '/manager/bulletins/'.$bulletin->id : '/manager/bulletins' }}">
        @csrf
        @if($bulletin) @method('PUT') @endif

        <div style="display:flex;flex-direction:column;gap:16px;">

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Başlık *</label>
                <input type="text" name="title" value="{{ old('title', $bulletin?->title) }}" required maxlength="200"
                       style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:14px;background:var(--u-card);color:var(--u-text);">
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">İçerik *</label>
                <textarea name="body" required rows="7" maxlength="5000"
                          style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:14px;background:var(--u-card);color:var(--u-text);font-family:inherit;resize:vertical;">{{ old('body', $bulletin?->body) }}</textarea>
                <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Satır sonları korunur. Maks. 5000 karakter.</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Kategori *</label>
                    <select name="category" required
                            style="width:100%;padding:9px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);">
                        @foreach(\App\Models\CompanyBulletin::$categoryLabels as $val => $lbl)
                        <option value="{{ $val }}" @selected(old('category', $bulletin?->category) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Yayın Tarihi *</label>
                    <input type="datetime-local" name="published_at"
                           value="{{ old('published_at', $bulletin ? $bulletin->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                           required
                           style="width:100%;box-sizing:border-box;padding:9px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Bitiş Tarihi <span style="font-weight:400;">(opsiyonel)</span></label>
                    <input type="datetime-local" name="expires_at"
                           value="{{ old('expires_at', $bulletin?->expires_at?->format('Y-m-d\TH:i')) }}"
                           style="width:100%;box-sizing:border-box;padding:9px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);">
                    <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Boş bırakılırsa süresiz yayında kalır.</div>
                </div>
                <div style="display:flex;align-items:flex-end;padding-bottom:4px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--u-text);">
                        <input type="checkbox" name="is_pinned" value="1"
                               @checked(old('is_pinned', $bulletin?->is_pinned))
                               style="width:16px;height:16px;">
                        📌 Sayfada sabitle (pinned)
                    </label>
                </div>
            </div>

        </div>

        {{-- Hedef Kitle --}}
        <div style="margin-top:20px;padding-top:18px;border-top:1px solid var(--u-line);">
            <div style="font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">
                🎯 Hedef Kitle <span style="font-size:11px;font-weight:400;text-transform:none;">(Boş bırakılırsa tüm çalışanlara gösterilir)</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                {{-- Roller --}}
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <label style="font-size:12px;font-weight:600;color:var(--u-muted);">Roller</label>
                        <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:11px;color:var(--u-brand,#4577c4);font-weight:700;">
                            <input type="checkbox" class="bulletin-target-all" data-target="roles" style="width:14px;height:14px;margin:0;cursor:pointer;">
                            <span>Tümünü Seç</span>
                        </label>
                    </div>
                    @php
                        $allRoles = [
                            'manager'          => 'Manager',
                            'senior'           => 'Eğitim Danışmanı',
                            'mentor'           => 'Mentor',
                            'marketing_admin'  => 'Marketing Admin',
                            'marketing_staff'  => 'Marketing Staff',
                            'sales_admin'      => 'Sales Admin',
                            'sales_staff'      => 'Sales Staff',
                            'finance_admin'    => 'Finance Admin',
                            'finance_staff'    => 'Finance Staff',
                            'operations_admin' => 'Operations Admin',
                            'operations_staff' => 'Operations Staff',
                            'system_admin'     => 'System Admin',
                            'system_staff'     => 'System Staff',
                        ];
                        $selRoles = old('target_roles', $bulletin?->target_roles ?? []);
                    @endphp
                    <div class="bulletin-target-grid" data-group="roles"
                         style="display:grid;grid-template-columns:1fr 1fr;gap:4px;max-height:200px;overflow-y:auto;padding:8px;border:1.5px solid var(--u-line);border-radius:8px;background:var(--u-card);">
                        @foreach($allRoles as $val => $lbl)
                            <label style="display:flex;align-items:center;gap:6px;padding:4px 6px;border-radius:5px;cursor:pointer;font-size:12px;color:var(--u-text);">
                                <input type="checkbox" name="target_roles[]" value="{{ $val }}" class="bulletin-target-cb"
                                       @checked(is_array($selRoles) && in_array($val, $selRoles, true))
                                       style="width:13px;height:13px;margin:0;cursor:pointer;">
                                <span>{{ $lbl }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Boş bırakılırsa rol filtresi uygulanmaz.</div>
                </div>

                {{-- Departmanlar --}}
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <label style="font-size:12px;font-weight:600;color:var(--u-muted);">Departmanlar</label>
                        <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:11px;color:var(--u-brand,#4577c4);font-weight:700;">
                            <input type="checkbox" class="bulletin-target-all" data-target="departments" style="width:14px;height:14px;margin:0;cursor:pointer;">
                            <span>Tümünü Seç</span>
                        </label>
                    </div>
                    @php
                        $allDepts = [
                            'marketing'  => 'Pazarlama',
                            'sales'      => 'Satış',
                            'finance'    => 'Finans',
                            'operations' => 'Operasyon',
                            'hr'         => 'İnsan Kaynakları',
                            'it'         => 'Bilgi Teknolojileri',
                            'management' => 'Yönetim',
                        ];
                        $selDepts = old('target_departments', $bulletin?->target_departments ?? []);
                    @endphp
                    <div class="bulletin-target-grid" data-group="departments"
                         style="display:grid;grid-template-columns:1fr 1fr;gap:4px;max-height:200px;overflow-y:auto;padding:8px;border:1.5px solid var(--u-line);border-radius:8px;background:var(--u-card);">
                        @foreach($allDepts as $val => $lbl)
                            <label style="display:flex;align-items:center;gap:6px;padding:4px 6px;border-radius:5px;cursor:pointer;font-size:12px;color:var(--u-text);">
                                <input type="checkbox" name="target_departments[]" value="{{ $val }}" class="bulletin-target-cb"
                                       @checked(is_array($selDepts) && in_array($val, $selDepts, true))
                                       style="width:13px;height:13px;margin:0;cursor:pointer;">
                                <span>{{ $lbl }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div style="font-size:11px;color:var(--u-muted);margin-top:4px;">Boş bırakılırsa departman filtresi uygulanmaz.</div>
                </div>
            </div>
            <div style="font-size:11px;color:var(--u-muted);margin-top:10px;padding:8px 10px;background:#f8fafc;border-radius:6px;">
                💡 Roller ve departmanlar birleşim olarak çalışır: kullanıcı seçilen rollerden <strong>veya</strong> seçilen departmanlardan birine uyarsa duyuruyu görür.
            </div>
        </div>

        <script nonce="{{ $cspNonce ?? '' }}">
        (function(){
            function syncMasterState(masterCb, grid) {
                var cbs = grid.querySelectorAll('.bulletin-target-cb');
                var total = cbs.length;
                var checked = grid.querySelectorAll('.bulletin-target-cb:checked').length;
                masterCb.checked = (checked === total && total > 0);
                masterCb.indeterminate = (checked > 0 && checked < total);
            }

            document.querySelectorAll('.bulletin-target-all').forEach(function(masterCb){
                var grp  = masterCb.getAttribute('data-target');
                var grid = document.querySelector('.bulletin-target-grid[data-group="' + grp + '"]');
                if (!grid) return;

                // İlk yükleme: mevcut durumu yansıt
                syncMasterState(masterCb, grid);

                masterCb.addEventListener('change', function(){
                    grid.querySelectorAll('.bulletin-target-cb').forEach(function(cb){
                        cb.checked = masterCb.checked;
                    });
                });

                grid.addEventListener('change', function(e){
                    if (e.target.classList && e.target.classList.contains('bulletin-target-cb')) {
                        syncMasterState(masterCb, grid);
                    }
                });
            });
        })();
        </script>

        <div style="display:flex;gap:8px;margin-top:22px;padding-top:16px;border-top:1px solid var(--u-line);">
            <button type="submit" class="btn ok">{{ $bulletin ? 'Güncelle' : 'Yayınla' }}</button>
            <a href="/manager/bulletins" class="btn alt">İptal</a>
        </div>
    </form>
</section>

</div>
@endsection

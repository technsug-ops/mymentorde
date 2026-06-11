@extends('manager.layouts.app')
@section('title', 'Toplu Belge Talep')
@section('page_title', 'Toplu Belge Talep')

@section('content')
<div class="bulk-doc-request-page" style="max-width: 1180px; margin: 0 auto;">

    {{-- Header --}}
    <div style="display:flex; align-items:center; gap:14px; margin-bottom: 14px;">
        <div style="width:48px; height:48px; border-radius:12px; background: var(--accent-soft, #f3edff); color: var(--c-accent, #7e58bf); display:flex; align-items:center; justify-content:center;">
            <x-icon name="clipboard-list" size="26" />
        </div>
        <div>
            <h1 style="margin:0; font-size:22px; font-weight:700;">Toplu Belge Talep</h1>
            <p style="margin:2px 0 0; font-size:13.5px; color:#6b7280;">Aynı kategoriden belgeyi birden fazla aday ya da öğrenciden tek seferde isteyin. Her hedef için ayrı tek-kullanımlık yükleme linki üretilir.</p>
        </div>
    </div>

    {{-- Quota uyarı bandı --}}
    @if($quotaLimit && (int)$quotaLimit > 0)
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; display:flex; align-items:center; gap:12px;">
            <x-icon name="circle-alert" size="18" />
            <div style="font-size: 13px;">
                Bu ay kullanılan: <b>{{ $quotaUsage }}</b> / {{ $quotaLimit }} —
                Kalan kapasite: <b style="color: {{ $quotaRemaining > 0 ? '#16a34a' : '#dc2626' }};">{{ $quotaRemaining }}</b>
            </div>
        </div>
    @endif

    {{-- Önceki batch özet kartı (varsa) --}}
    @if(!empty($lastBatchSummary))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 14px; margin-bottom: 18px;">
            <div style="display:flex; align-items:center; gap:8px; font-weight:600; color: #047857; margin-bottom: 6px;">
                <x-icon name="check" size="18" />
                Son toplu işlem
            </div>
            <div style="font-size: 13px; color: #065f46;">
                {{ $lastBatchSummary['created_count'] ?? 0 }} link oluşturuldu —
                Email: {{ $lastBatchSummary['email_sent'] ?? 0 }},
                WhatsApp: {{ $lastBatchSummary['whatsapp_sent'] ?? 0 }}
                @if(!empty($lastBatchSummary['failed_count']))
                    , Hatalı: {{ $lastBatchSummary['failed_count'] }}
                @endif
                <a href="{{ route('manager.doc-request.bulk.export', ['batch' => $lastBatchSummary['batch']]) }}" style="margin-left: 12px; color: #047857; font-weight: 600;">CSV indir</a>
            </div>
        </div>
    @endif

    <form id="bulk-doc-request-form" style="background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding: 18px 20px;">
        @csrf

        {{-- Step 1: Hedef tipi --}}
        <div class="step" style="margin-bottom: 22px;">
            <div style="display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:8px;">
                <x-icon name="users" size="16" />
                <span>1. Hedef Tipi</span>
            </div>
            <div style="display:flex; gap:12px;">
                <label style="flex:1; display:flex; align-items:center; gap:10px; padding:12px; border:1.5px solid #e5e7eb; border-radius:10px; cursor:pointer;" data-target-type-label="guest">
                    <input type="radio" name="target_type" value="guest" checked>
                    <div>
                        <div style="font-weight:600;">Aday Öğrenci</div>
                        <div style="font-size:12px; color:#6b7280;">{{ count($guests) }} kişi listeden seçilebilir</div>
                    </div>
                </label>
                <label style="flex:1; display:flex; align-items:center; gap:10px; padding:12px; border:1.5px solid #e5e7eb; border-radius:10px; cursor:pointer;" data-target-type-label="student">
                    <input type="radio" name="target_type" value="student">
                    <div>
                        <div style="font-weight:600;">Öğrenci</div>
                        <div style="font-size:12px; color:#6b7280;">{{ count($students) }} kayıt listeden seçilebilir</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Step 2: Çoklu seç tablosu --}}
        <div class="step" style="margin-bottom: 22px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <div style="display:flex; align-items:center; gap:8px; font-weight:600;">
                    <x-icon name="users" size="16" />
                    <span>2. Hedefleri Seç <span id="selected-count" style="color:#6b7280; font-weight:400; font-size:13px;">(0 seçili)</span></span>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input id="target-search" type="search" placeholder="Ad/soyad/email ile filtrele..." style="padding:6px 10px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px;">
                    <button type="button" id="select-all-btn" style="padding:6px 10px; border:1px solid #e5e7eb; background:#fff; border-radius:8px; font-size:12px; cursor:pointer;">Tümünü Seç</button>
                    <button type="button" id="clear-all-btn" style="padding:6px 10px; border:1px solid #e5e7eb; background:#fff; border-radius:8px; font-size:12px; cursor:pointer;">Temizle</button>
                </div>
            </div>

            {{-- Guest tablosu --}}
            <div id="guest-table-wrap" style="max-height: 320px; overflow-y:auto; border: 1px solid #e5e7eb; border-radius:10px;">
                <table style="width:100%; border-collapse: collapse; font-size:13px;">
                    <thead style="position:sticky; top:0; background:#f9fafb; z-index:1;">
                        <tr style="text-align:left;">
                            <th style="padding:8px 10px; width:36px;"></th>
                            <th style="padding:8px 10px;">Ad Soyad</th>
                            <th style="padding:8px 10px;">Email</th>
                            <th style="padding:8px 10px;">Telefon</th>
                            <th style="padding:8px 10px;">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guests as $g)
                            <tr class="bulk-row" data-target-type="guest"
                                data-name="{{ strtolower(trim($g->first_name . ' ' . $g->last_name)) }}"
                                data-email="{{ strtolower((string)$g->email) }}">
                                <td style="padding:6px 10px; text-align:center;">
                                    <input type="checkbox" class="bulk-cb" data-tt="guest" value="{{ $g->id }}">
                                </td>
                                <td style="padding:6px 10px;">{{ trim($g->first_name . ' ' . $g->last_name) }}</td>
                                <td style="padding:6px 10px;">
                                    {{ $g->email ?: '—' }}
                                    @if(!$g->email)<span style="color:#dc2626; font-size:11px;">eksik</span>@endif
                                </td>
                                <td style="padding:6px 10px;">
                                    {{ $g->phone ?: '—' }}
                                    @if(!$g->phone)<span style="color:#dc2626; font-size:11px;">eksik</span>@endif
                                </td>
                                <td style="padding:6px 10px; color:#6b7280;">{{ $g->lead_status ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding:16px; text-align:center; color:#6b7280;">Aday öğrenci bulunamadı.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Student tablosu --}}
            <div id="student-table-wrap" style="display:none; max-height: 320px; overflow-y:auto; border: 1px solid #e5e7eb; border-radius:10px;">
                <table style="width:100%; border-collapse: collapse; font-size:13px;">
                    <thead style="position:sticky; top:0; background:#f9fafb; z-index:1;">
                        <tr style="text-align:left;">
                            <th style="padding:8px 10px; width:36px;"></th>
                            <th style="padding:8px 10px;">Ad Soyad</th>
                            <th style="padding:8px 10px;">Öğrenci ID</th>
                            <th style="padding:8px 10px;">Senior</th>
                            <th style="padding:8px 10px;">Şube</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $s)
                            <tr class="bulk-row" data-target-type="student"
                                data-name="{{ strtolower((string)($s->display_name ?? '')) }}"
                                data-email="{{ strtolower((string)$s->student_id) }}">
                                <td style="padding:6px 10px; text-align:center;">
                                    <input type="checkbox" class="bulk-cb" data-tt="student" value="{{ $s->student_id }}">
                                </td>
                                <td style="padding:6px 10px;">{{ $s->display_name ?? '—' }}</td>
                                <td style="padding:6px 10px;">{{ $s->student_id }}</td>
                                <td style="padding:6px 10px;">{{ $s->senior_email ?? '—' }}</td>
                                <td style="padding:6px 10px;">{{ $s->branch ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding:16px; text-align:center; color:#6b7280;">Öğrenci bulunamadı.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:6px; font-size:12px; color:#9ca3af;">En fazla {{ $maxTargets }} hedef seçilebilir.</div>
        </div>

        {{-- Step 3: Belge kategorisi --}}
        <div class="step" style="margin-bottom: 22px;">
            <div style="display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:8px;">
                <x-icon name="file-text" size="16" />
                <span>3. Belge Kategorisi</span>
            </div>
            <select name="category_code" required style="width:100%; padding:10px 12px; border:1.5px solid #e5e7eb; border-radius:10px; font-size:14px;">
                <option value="">— Kategori seçin —</option>
                @foreach($categories as $c)
                    <option value="{{ $c->code }}">{{ $c->name_tr }}@if($c->name_de) ({{ $c->name_de }})@endif</option>
                @endforeach
            </select>
        </div>

        {{-- Step 4: Kanal --}}
        <div class="step" style="margin-bottom: 22px;">
            <div style="display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:8px;">
                <x-icon name="send" size="16" />
                <span>4. Bildirim Kanalı</span>
            </div>
            <div style="display:flex; gap:12px;">
                <label style="flex:1; display:flex; align-items:center; gap:10px; padding:12px; border:1.5px solid #e5e7eb; border-radius:10px; cursor:pointer;">
                    <input type="checkbox" name="notification_channels[]" value="email" checked>
                    <x-icon name="mail" size="18" />
                    <div>
                        <div style="font-weight:600;">Email</div>
                        <div style="font-size:12px; color:#6b7280;">Hedefin email adresine ilk talep gönderilir</div>
                    </div>
                </label>
                <label style="flex:1; display:flex; align-items:center; gap:10px; padding:12px; border:1.5px solid #e5e7eb; border-radius:10px; cursor:pointer;">
                    <input type="checkbox" name="notification_channels[]" value="whatsapp">
                    <x-icon name="message-circle" size="18" />
                    <div>
                        <div style="font-weight:600;">WhatsApp</div>
                        <div style="font-size:12px; color:#6b7280;">Telefon kayıtlıysa hatırlatma gönderilir</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Step 5: Geçerlilik süresi --}}
        <div class="step" style="margin-bottom: 22px; display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <div style="display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:8px;">
                    <x-icon name="clock" size="16" />
                    <span>5. Geçerlilik Süresi (gün)</span>
                </div>
                <input type="number" name="expires_in_days" value="{{ $defaultExpires }}" min="{{ $minExpires }}" max="{{ $maxExpires }}" required style="width:100%; padding:10px 12px; border:1.5px solid #e5e7eb; border-radius:10px; font-size:14px;">
            </div>
            <div>
                <div style="display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:8px;">
                    <x-icon name="pencil" size="16" />
                    <span>Özel Mesaj (opsiyonel)</span>
                </div>
                <input type="text" name="custom_message" maxlength="500" placeholder="Hedefe gösterilecek kısa not (en fazla 500 karakter)" style="width:100%; padding:10px 12px; border:1.5px solid #e5e7eb; border-radius:10px; font-size:14px;">
            </div>
        </div>

        {{-- Aksiyonlar --}}
        <div style="display:flex; gap:10px; justify-content:flex-end; padding-top: 8px; border-top: 1px solid #f3f4f6;">
            <button type="button" id="preview-btn" style="padding: 10px 16px; background:#fff; color: #111; border: 1.5px solid #e5e7eb; border-radius: 10px; font-weight: 600; cursor: pointer; display:flex; align-items:center; gap:8px;">
                <x-icon name="eye" size="16" /> Preview
            </button>
            <button type="button" id="submit-btn" disabled style="padding: 10px 18px; background: #7e58bf; color: #fff; border: 0; border-radius: 10px; font-weight: 600; cursor: pointer; display:flex; align-items:center; gap:8px; opacity:.55;">
                <x-icon name="send" size="16" /> Onayla ve Gönder
            </button>
        </div>
    </form>

    {{-- Preview / Sonuç paneli --}}
    <div id="result-panel" style="display:none; margin-top: 18px; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding: 18px 20px;">
        <div id="result-content"></div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var maxTargets = {{ (int) $maxTargets }};
    var routes = {
        preview: '{{ route('manager.doc-request.bulk.preview') }}',
        store:   '{{ route('manager.doc-request.bulk.store') }}',
    };
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var radios = document.querySelectorAll('input[name="target_type"]');
    var guestWrap = document.getElementById('guest-table-wrap');
    var studentWrap = document.getElementById('student-table-wrap');
    var searchInput = document.getElementById('target-search');
    var selectAllBtn = document.getElementById('select-all-btn');
    var clearAllBtn = document.getElementById('clear-all-btn');
    var selectedCount = document.getElementById('selected-count');
    var previewBtn = document.getElementById('preview-btn');
    var submitBtn = document.getElementById('submit-btn');
    var resultPanel = document.getElementById('result-panel');
    var resultContent = document.getElementById('result-content');

    function activeType(){
        var r = document.querySelector('input[name="target_type"]:checked');
        return r ? r.value : 'guest';
    }
    function switchType(){
        if (activeType() === 'guest'){
            guestWrap.style.display = '';
            studentWrap.style.display = 'none';
        } else {
            guestWrap.style.display = 'none';
            studentWrap.style.display = '';
        }
        // Diğer taraftaki seçimleri temizle
        document.querySelectorAll('.bulk-cb').forEach(function(cb){ cb.checked = false; });
        updateCount();
    }
    function getCheckedIds(){
        var t = activeType();
        var out = [];
        document.querySelectorAll('.bulk-cb[data-tt="'+t+'"]:checked').forEach(function(cb){
            out.push(cb.value);
        });
        return out;
    }
    function updateCount(){
        var n = getCheckedIds().length;
        selectedCount.textContent = '(' + n + ' seçili)';
        var canSubmit = n > 0 && n <= maxTargets;
        submitBtn.disabled = !canSubmit;
        submitBtn.style.opacity = canSubmit ? '1' : '.55';
        if (n > maxTargets){
            selectedCount.style.color = '#dc2626';
        } else {
            selectedCount.style.color = '#6b7280';
        }
    }
    function getVisibleRows(){
        var wrap = activeType() === 'guest' ? guestWrap : studentWrap;
        return wrap.querySelectorAll('.bulk-row');
    }
    function filterRows(){
        var q = (searchInput.value || '').toLowerCase().trim();
        getVisibleRows().forEach(function(row){
            var name = row.getAttribute('data-name') || '';
            var email = row.getAttribute('data-email') || '';
            row.style.display = (q === '' || name.indexOf(q) >= 0 || email.indexOf(q) >= 0) ? '' : 'none';
        });
    }

    radios.forEach(function(r){ r.addEventListener('change', switchType); });
    document.addEventListener('change', function(e){
        if (e.target && e.target.classList && e.target.classList.contains('bulk-cb')){
            updateCount();
        }
    });
    searchInput.addEventListener('input', filterRows);
    selectAllBtn.addEventListener('click', function(){
        var i = 0;
        getVisibleRows().forEach(function(row){
            if (row.style.display === 'none') return;
            var cb = row.querySelector('.bulk-cb');
            if (!cb) return;
            if (i < maxTargets){ cb.checked = true; i++; }
        });
        updateCount();
    });
    clearAllBtn.addEventListener('click', function(){
        document.querySelectorAll('.bulk-cb').forEach(function(cb){ cb.checked = false; });
        updateCount();
    });

    function collectFormData(){
        var fd = new FormData();
        fd.append('target_type', activeType());
        getCheckedIds().forEach(function(id){ fd.append('target_ids[]', id); });
        var cat = document.querySelector('select[name="category_code"]').value;
        fd.append('category_code', cat);
        document.querySelectorAll('input[name="notification_channels[]"]:checked').forEach(function(c){
            fd.append('notification_channels[]', c.value);
        });
        fd.append('expires_in_days', document.querySelector('input[name="expires_in_days"]').value || '');
        fd.append('custom_message', document.querySelector('input[name="custom_message"]').value || '');
        return fd;
    }

    function showError(msg){
        resultPanel.style.display = '';
        resultContent.innerHTML = '<div style="color:#dc2626; font-weight:600;">' + msg + '</div>';
    }

    previewBtn.addEventListener('click', function(){
        if (getCheckedIds().length === 0){ showError('En az 1 hedef seçin.'); return; }
        var cat = document.querySelector('select[name="category_code"]').value;
        if (!cat){ showError('Belge kategorisi seçin.'); return; }
        if (document.querySelectorAll('input[name="notification_channels[]"]:checked').length === 0){
            showError('En az 1 bildirim kanalı seçin.'); return;
        }
        resultPanel.style.display = '';
        resultContent.innerHTML = '<div style="color:#6b7280;">Önizleme hazırlanıyor...</div>';
        fetch(routes.preview, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: collectFormData()
        }).then(function(r){ return r.json().then(function(j){ return { status: r.status, body: j }; }); })
        .then(function(res){
            if (res.status !== 200 || !res.body.success){
                showError(res.body.error || 'Önizleme hatası.'); return;
            }
            var html = '<div style="font-weight:600; margin-bottom:8px;">Önizleme</div>';
            html += '<div style="display:flex; gap:14px; margin-bottom: 12px;">';
            html += '<div style="padding:10px 14px; background:#ecfdf5; border-radius:10px;">Hazır: <b>' + res.body.ready_count + '</b></div>';
            html += '<div style="padding:10px 14px; background:#fef2f2; border-radius:10px;">Eksik bilgi: <b>' + res.body.missing_count + '</b></div>';
            html += '<div style="padding:10px 14px; background:#f3f4f6; border-radius:10px;">Toplam: <b>' + res.body.total_count + '</b></div>';
            html += '</div>';
            if (res.body.missing_count > 0){
                html += '<div style="font-size:13px; color:#b91c1c; margin-bottom: 4px;">Eksik bilgi olanlar (kanal mevcut değil — bu kanaldan bildirim gönderilmez ama token üretilir):</div>';
                html += '<ul style="margin: 0 0 12px; padding-left: 18px; font-size: 13px; color:#7f1d1d;">';
                res.body.missing.slice(0, 20).forEach(function(m){
                    html += '<li>' + escapeHtml(m.display_name || m.id) + ' — eksik: ' + m.missing_channels.join(', ') + '</li>';
                });
                if (res.body.missing.length > 20) html += '<li>... ve ' + (res.body.missing.length - 20) + ' tane daha</li>';
                html += '</ul>';
            }
            resultContent.innerHTML = html;
        })
        .catch(function(e){ showError('İstek başarısız: ' + e.message); });
    });

    submitBtn.addEventListener('click', function(){
        if (submitBtn.disabled) return;
        if (!confirm(getCheckedIds().length + ' hedef için belge talep linki oluşturulacak. Devam edilsin mi?')) return;
        submitBtn.disabled = true; submitBtn.style.opacity = '.55';
        resultPanel.style.display = '';
        resultContent.innerHTML = '<div style="color:#6b7280;">Talepler oluşturuluyor...</div>';

        fetch(routes.store, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: collectFormData()
        }).then(function(r){ return r.json().then(function(j){ return { status: r.status, body: j }; }); })
        .then(function(res){
            if (res.status !== 200 || !res.body.success){
                showError(res.body.error || 'Oluşturma başarısız.');
                submitBtn.disabled = false; submitBtn.style.opacity = '1';
                return;
            }
            var html = '<div style="display:flex; align-items:center; gap:8px; font-weight:700; color: #047857; margin-bottom:10px;">';
            html += '<span>' + res.body.created_count + ' belge talep linki başarıyla oluşturuldu</span></div>';
            html += '<div style="display:flex; gap:14px; margin-bottom: 12px; font-size:13px;">';
            html += '<div>Email gönderildi: <b>' + res.body.email_sent + '</b></div>';
            html += '<div>WhatsApp gönderildi: <b>' + res.body.whatsapp_sent + '</b></div>';
            html += '<div>Hatalı: <b style="color:' + (res.body.failed_count > 0 ? '#dc2626' : '#6b7280') + ';">' + res.body.failed_count + '</b></div>';
            html += '</div>';
            html += '<a href="' + res.body.csv_export_url + '" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#7e58bf; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">CSV İndir (Token URL listesi)</a>';
            if (res.body.failed_count > 0){
                html += '<details style="margin-top: 12px;"><summary style="cursor:pointer; color:#b91c1c; font-weight:600;">Hatalı kayıtları görüntüle</summary>';
                html += '<ul style="margin:8px 0 0; padding-left: 18px; font-size:13px; color:#7f1d1d;">';
                res.body.failed.forEach(function(f){
                    html += '<li>' + escapeHtml(f.display_name || f.target_id) + ' — ' + escapeHtml(f.error) + '</li>';
                });
                html += '</ul></details>';
            }
            resultContent.innerHTML = html;
            // Reset selections so kullanıcı yanlışlıkla tekrar göndermesin
            document.querySelectorAll('.bulk-cb').forEach(function(cb){ cb.checked = false; });
            updateCount();
        })
        .catch(function(e){
            showError('İstek başarısız: ' + e.message);
            submitBtn.disabled = false; submitBtn.style.opacity = '1';
        });
    });

    function escapeHtml(s){
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, function(c){
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    updateCount();
})();
</script>
@endsection

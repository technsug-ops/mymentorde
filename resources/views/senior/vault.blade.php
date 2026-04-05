@extends('senior.layouts.app')
@section('title', 'Hesap Kasası')
@section('page_title', 'Hesap Kasası')

@section('content')
@php
    $serviceOptions = [
        'uni_assist'      => 'Uni Assist',
        'studycheck'      => 'Studycheck',
        'anabin'          => 'Anabin / DAAD',
        'visa_portal'     => 'Vize Portalı',
        'language_school' => 'Dil Okulu',
        'blocked_account' => 'Bloke Hesap',
        'dorm_portal'     => 'Yurt Portalı',
        'email_account'   => 'E-posta Hesabı',
        'other'           => 'Diğer',
    ];
    $serviceIcons = [
        'uni_assist'      => '🏛',
        'studycheck'      => '📊',
        'anabin'          => '📋',
        'visa_portal'     => '🛂',
        'language_school' => '🗣',
        'blocked_account' => '🏦',
        'dorm_portal'     => '🏠',
        'email_account'   => '📧',
        'other'           => '🔑',
    ];
    $vaultList    = $vaults ?? collect();
    $visibleCnt   = $vaultList->where('is_visible_to_student', true)->count();
    $hiddenCnt    = $vaultList->where('is_visible_to_student', false)->count();
    $filterSid    = $filters['filterSid'] ?? 'all';
    $filterStatus = $filters['status']    ?? 'all';
    $filterQ      = $filters['q']         ?? '';
@endphp

@if(session('status'))
<div style="padding:10px 16px;border-radius:8px;background:#16a34a;color:#fff;margin-bottom:14px;font-weight:600;font-size:var(--tx-sm);">✓ {{ session('status') }}</div>
@endif

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">🔐 Hesap Kasası</div>
    <div style="font-size:var(--tx-sm);opacity:.8;margin-bottom:16px;">Öğrenci portal hesaplarını yönetin. Görünürlük açık olanlar öğrencinin hesap kasası sayfasında görünür.</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:8px 16px;display:flex;flex-direction:column;align-items:center;">
            <span style="font-size:var(--tx-xl);font-weight:800;">{{ $vaultList->count() }}</span>
            <span style="font-size:var(--tx-xs);opacity:.8;margin-top:1px;">Toplam</span>
        </div>
        <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:8px 16px;display:flex;flex-direction:column;align-items:center;">
            <span style="font-size:var(--tx-xl);font-weight:800;">{{ $visibleCnt }}</span>
            <span style="font-size:var(--tx-xs);opacity:.8;margin-top:1px;">Öğrenciye Açık</span>
        </div>
        <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:8px 16px;display:flex;flex-direction:column;align-items:center;">
            <span style="font-size:var(--tx-xl);font-weight:800;">{{ $hiddenCnt }}</span>
            <span style="font-size:var(--tx-xs);opacity:.8;margin-top:1px;">Gizli</span>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:16px;align-items:start;">

{{-- LEFT: Add Form --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);">
        <span style="font-weight:700;font-size:var(--tx-base);">🔑 Yeni Hesap Ekle</span>
    </div>
    <div style="padding:16px 18px;">
    @if(($studentOptions ?? collect())->isEmpty())
        <div style="padding:20px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Atanmış öğrenci bulunamadı.</div>
    @else
    <form method="POST" action="{{ route('senior.vault.store') }}">
        @csrf
        <div style="display:flex;flex-direction:column;gap:10px;">

            <div>
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Öğrenci *</div>
                <select name="student_id" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    <option value="">— seçiniz —</option>
                    @foreach($studentOptions as $opt)
                        <option value="{{ $opt['id'] }}" @selected(old('student_id') === $opt['id'])>{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Servis Tipi *</div>
                    <select name="service_name" required style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                        @foreach($serviceOptions as $key => $label)
                            <option value="{{ $key }}" @selected(old('service_name') === $key)>{{ $serviceIcons[$key] ?? '' }} {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Görünen Etiket *</div>
                    <input type="text" name="service_label" value="{{ old('service_label') }}" placeholder="Uni Assist Hesabı" required
                           style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                </div>
            </div>

            <div>
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Portal URL (opsiyonel)</div>
                <input type="url" name="account_url" value="{{ old('account_url') }}" placeholder="https://..."
                       style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">E-posta *</div>
                    <input type="email" name="account_email" value="{{ old('account_email') }}" placeholder="ornek@mail.com" required
                           style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Kullanıcı Adı (ops.)</div>
                    <input type="text" name="account_username" value="{{ old('account_username') }}" placeholder="kullanici123"
                           style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                </div>
            </div>

            <div>
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Şifre *</div>
                <input type="password" name="account_password" placeholder="minimum 4 karakter" required autocomplete="new-password"
                       style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:end;">
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Başvuru ID (ops.)</div>
                    <input type="text" name="application_id" value="{{ old('application_id') }}" placeholder="GST-00000123"
                           style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);">
                        <input type="checkbox" name="is_visible_to_student" value="1" @checked(old('is_visible_to_student'))>
                        <span style="font-size:var(--tx-sm);color:var(--u-text);">Öğrenciye görünür yap</span>
                    </label>
                </div>
            </div>

            <div>
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Not (opsiyonel)</div>
                <textarea name="notes" rows="2" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);resize:vertical;">{{ old('notes') }}</textarea>
            </div>

            <div style="padding:10px 14px;border:1px solid #c9d5f0;border-radius:8px;background:#eef2fd;font-size:var(--tx-xs);color:#3b5bdb;display:flex;gap:8px;align-items:flex-start;">
                <span>🔒</span>
                <span>Şifreler AES ile şifrelenir. "Öğrenciye görünür yap" işaretlenmeden öğrenci şifreyi göremez.</span>
            </div>

            <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:10px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;width:100%;">Hesap Ekle</button>
        </div>
    </form>
    @endif
    </div>
</div>

{{-- RIGHT: Vault List --}}
<div>
    {{-- Filter Bar --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
        <form method="GET" action="/senior/vault" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:2;min-width:150px;">
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Öğrenci</div>
                <select name="student_id" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    <option value="all" @selected($filterSid === 'all')>Tüm öğrenciler</option>
                    @foreach($studentOptions ?? [] as $opt)
                        <option value="{{ $opt['id'] }}" @selected($filterSid === $opt['id'])>{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:110px;">
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Durum</div>
                <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                    <option value="all"      @selected($filterStatus === 'all')>Tümü</option>
                    <option value="active"   @selected($filterStatus === 'active')>Aktif</option>
                    <option value="inactive" @selected($filterStatus === 'inactive')>Pasif</option>
                    <option value="expired"  @selected($filterStatus === 'expired')>Süresi Dolmuş</option>
                </select>
            </div>
            <div style="flex:1;min-width:100px;">
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Ara</div>
                <input type="text" name="q" value="{{ $filterQ }}" placeholder="Ara…"
                       style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end;">
                <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:9px 14px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Filtrele</button>
                <a href="/senior/vault" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:9px 12px;font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Sıfırla</a>
            </div>
        </form>
    </div>

    {{-- List --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
        <div style="padding:12px 18px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:8px;">
            <span style="font-weight:700;font-size:var(--tx-sm);">Hesap Listesi</span>
            <span class="badge info">{{ $vaultList->count() }} kayıt</span>
        </div>

        @forelse($vaultList as $row)
        @php
            $isVisible   = (bool)$row->is_visible_to_student;
            $svcLabel    = $serviceOptions[$row->service_name] ?? $row->service_name;
            $svcIcon     = $serviceIcons[$row->service_name]   ?? '🔑';
        @endphp
        <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                <div style="flex:1;min-width:0;">
                    {{-- Service + student --}}
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:5px;">
                        <span style="font-size:var(--tx-base);">{{ $svcIcon }}</span>
                        <span style="font-weight:800;font-size:var(--tx-sm);color:var(--u-text);">{{ $row->service_label }}</span>
                        <span style="font-size:var(--tx-xs);background:var(--u-bg);border:1px solid var(--u-line);border-radius:999px;padding:2px 8px;color:var(--u-muted);font-weight:600;">{{ $svcLabel }}</span>
                        <span class="badge {{ $isVisible ? 'ok' : 'warn' }}" style="font-size:var(--tx-xs);">{{ $isVisible ? '✓ Açık' : 'Gizli' }}</span>
                    </div>

                    {{-- Student + credentials --}}
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);display:flex;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                        <span>👤 {{ $row->student_id }}</span>
                        <span>📧 {{ $row->account_email }}</span>
                        @if($row->account_username)<span>👤 {{ $row->account_username }}</span>@endif
                        @if($row->application_id)<span>ID: {{ $row->application_id }}</span>@endif
                    </div>

                    @if($row->account_url)
                        <a href="{{ $row->account_url }}" target="_blank" rel="noopener"
                           style="font-size:var(--tx-xs);color:var(--u-brand);text-decoration:none;font-weight:600;">🔗 {{ $row->account_url }}</a>
                    @endif
                    @if($row->notes)
                        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;font-style:italic;">{{ $row->notes }}</div>
                    @endif
                </div>

                {{-- Actions --}}
                <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
                    <form method="POST" action="{{ route('senior.vault.toggle-visibility', $row->id) }}">
                        @csrf
                        <button type="submit" style="font-size:var(--tx-xs);padding:5px 12px;border:1px solid {{ $isVisible ? 'var(--u-line)' : '#7c3aed' }};border-radius:6px;background:{{ $isVisible ? 'var(--u-bg)' : '#f3f0ff' }};color:{{ $isVisible ? 'var(--u-muted)' : '#7c3aed' }};cursor:pointer;font-weight:600;white-space:nowrap;width:100%;">
                            {{ $isVisible ? '🙈 Gizle' : '👁 Öğrenciye Aç' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('senior.vault.destroy', $row->id) }}" onsubmit="return confirm('Bu hesap girdisi silinsin mi?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size:var(--tx-xs);padding:5px 12px;border:1px solid #fca5a5;border-radius:6px;background:#fff5f5;color:#dc2626;cursor:pointer;font-weight:600;width:100%;">Sil</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div style="padding:48px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Vault girdisi yok.</div>
        @endforelse
    </div>
    @if($vaults instanceof \Illuminate\Pagination\LengthAwarePaginator && $vaults->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--u-line);">{{ $vaults->links() }}</div>
    @endif
</div>

</div>{{-- /grid --}}

@endsection

@extends('manager.layouts.app')

@section('title', 'Partner Anlaşmaları')

@section('content')

{{-- Aynı ekran iki tarafa da açık; gördükleri iş farklı:
     operasyon anlaşma yazar/gönderir, partner okur ve imzalar. --}}

<div style="max-width:960px;">

    <h1 style="font-size:22px;font-weight:800;margin:0 0 6px;">
        {{ $isPartnerSide ? 'Anlaşmalarım' : 'Partner Anlaşmaları' }}
    </h1>
    <p style="color:#64748b;font-size:13.5px;margin:0 0 18px;line-height:1.6;">
        @if($isPartnerSide)
            Portal ile firmanız arasındaki çerçeve anlaşma. Öğrenci başına
            standart bedel buradan gelir — imzalandıktan sonra her aday için
            anlaşmayı tek adımda kapatabilirsiniz.
        @else
            Partner firmalarla yapılan çerçeve anlaşmalar. Öğrenci başına
            standart bedeli buraya yazarsanız partner her aday için anlaşmayı
            tek adımda kapatır; yazmazsanız her öğrenci için ayrı teklif
            vermeniz gerekir.
        @endif
    </p>

    @if(session('status'))
        <div style="border:1px solid rgba(22,163,74,.4);background:rgba(22,163,74,.08);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13.5px;">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div style="border:1px solid rgba(220,38,38,.4);background:rgba(220,38,38,.07);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13.5px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @unlesspartnerPanel
        <details style="border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;margin-bottom:18px;">
            <summary style="cursor:pointer;font-weight:700;font-size:14px;">+ Yeni çerçeve anlaşma</summary>

            @if($partnerOptions->isEmpty())
                <p style="color:#64748b;font-size:13px;margin:12px 0 0;">
                    Alt firmanız yok. Anlaşma açmak için önce partner firma tanımlanmalı.
                </p>
            @else
                <form method="POST" action="{{ route('manager.partner-agreements.store') }}" style="margin-top:14px;display:grid;gap:12px;">
                    @csrf
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;">
                        <label style="font-size:12.5px;font-weight:600;">Partner firma
                            <select name="partner_company_id" required style="width:100%;margin-top:4px;padding:8px;border:1px solid #cbd5e1;border-radius:7px;">
                                @foreach($partnerOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label style="font-size:12.5px;font-weight:600;">Başlık
                            <input type="text" name="title" required maxlength="200" placeholder="Ör. 2026 İş Ortaklığı Anlaşması"
                                   style="width:100%;margin-top:4px;padding:8px;border:1px solid #cbd5e1;border-radius:7px;">
                        </label>
                        <label style="font-size:12.5px;font-weight:600;">Öğrenci başı standart bedel (EUR)
                            <input type="number" name="standard_student_fee_eur" step="0.01" min="0" placeholder="800"
                                   style="width:100%;margin-top:4px;padding:8px;border:1px solid #cbd5e1;border-radius:7px;">
                        </label>
                        <label style="font-size:12.5px;font-weight:600;">Başlangıç
                            <input type="date" name="valid_from" style="width:100%;margin-top:4px;padding:8px;border:1px solid #cbd5e1;border-radius:7px;">
                        </label>
                        <label style="font-size:12.5px;font-weight:600;">Bitiş
                            <input type="date" name="valid_until" style="width:100%;margin-top:4px;padding:8px;border:1px solid #cbd5e1;border-radius:7px;">
                        </label>
                    </div>
                    <label style="font-size:12.5px;font-weight:600;">Anlaşma metni (opsiyonel)
                        <textarea name="body_text" rows="5" style="width:100%;margin-top:4px;padding:8px;border:1px solid #cbd5e1;border-radius:7px;font-family:inherit;"></textarea>
                    </label>
                    <div><button type="submit" class="btn btn-primary">Taslak oluştur</button></div>
                </form>
            @endif
        </details>
    @endpartnerPanel

    @forelse($agreements as $agreement)
        @php
            [$stLabel, $stColor] = match((string) $agreement->status) {
                'draft'      => ['Taslak', '#64748b'],
                'sent'       => ['İmza Bekliyor', '#d97706'],
                'signed'     => [$agreement->isActive() ? 'Yürürlükte' : 'İmzalı (süresi dışında)', '#16a34a'],
                'terminated' => ['Feshedildi', '#dc2626'],
                default      => ['—', '#64748b'],
            };
            $isMine = (int) $agreement->partner_company_id === (int) $companyId;
        @endphp

        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:start;">
                <div>
                    <div style="font-weight:800;font-size:15px;">{{ $agreement->title }}</div>
                    <div style="color:#64748b;font-size:12.5px;margin-top:3px;">
                        @unlesspartnerPanel
                            {{ $agreement->partnerCompany->name ?? ('Firma #' . $agreement->partner_company_id) }} ·
                        @endpartnerPanel
                        {{ optional($agreement->valid_from)->format('d.m.Y') ?: 'başlangıç yok' }}
                        –
                        {{ optional($agreement->valid_until)->format('d.m.Y') ?: 'bitiş yok' }}
                    </div>
                </div>
                <span style="font-weight:700;font-size:12px;color:{{ $stColor }};border:1px solid {{ $stColor }}33;background:{{ $stColor }}14;border-radius:20px;padding:3px 11px;">
                    {{ $stLabel }}
                </span>
            </div>

            <div style="margin-top:12px;font-size:13.5px;">
                <strong>Öğrenci başı standart bedel:</strong>
                @if($agreement->standardFee() !== null)
                    {{ number_format($agreement->standardFee(), 2, ',', '.') }} EUR
                @else
                    <span style="color:#d97706;">tanımsız — her öğrenci için ayrı teklif gerekir</span>
                @endif
            </div>

            @if($agreement->signed_at)
                <div style="color:#64748b;font-size:12px;margin-top:5px;">
                    İmza: {{ $agreement->signed_at->format('d.m.Y H:i') }}
                    @if($agreement->signed_by_email) · {{ $agreement->signed_by_email }} @endif
                </div>
            @endif

            @if($agreement->terminated_at)
                <div style="color:#dc2626;font-size:12px;margin-top:5px;">
                    Fesih: {{ $agreement->terminated_at->format('d.m.Y') }}
                    @if($agreement->termination_reason) · {{ $agreement->termination_reason }} @endif
                </div>
            @endif

            @if(trim((string) $agreement->body_text) !== '')
                <details style="margin-top:10px;">
                    <summary style="cursor:pointer;font-size:13px;font-weight:600;">Anlaşma metni</summary>
                    <div style="white-space:pre-wrap;font-size:13px;line-height:1.6;margin-top:8px;color:#334155;">{{ $agreement->body_text }}</div>
                </details>
            @endif

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
                {{-- Partner tarafı: yalnızca imza. İmza partnerin işi;
                     operasyonun onun adına imzalaması iki taraflı olmanın
                     anlamını ortadan kaldırırdı. --}}
                @if($isMine && (string) $agreement->status === 'sent')
                    <form method="POST" action="{{ route('manager.partner-agreements.sign', $agreement->id) }}"
                          enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        @csrf
                        <input type="file" name="signed_file" accept=".pdf,.jpg,.jpeg,.png" style="font-size:12px;">
                        <button type="submit" class="btn btn-primary">İmzala</button>
                        <span style="color:#64748b;font-size:11.5px;">İmzalı kopya yüklemek opsiyonel.</span>
                    </form>
                @endif

                @unlesspartnerPanel
                    @if((string) $agreement->status === 'draft')
                        <form method="POST" action="{{ route('manager.partner-agreements.send', $agreement->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Partnere gönder</button>
                        </form>
                    @endif

                    @if(in_array((string) $agreement->status, ['sent', 'signed'], true))
                        <form method="POST" action="{{ route('manager.partner-agreements.terminate', $agreement->id) }}"
                              onsubmit="return confirm('Anlaşma feshedilecek. Yürürlükteki öğrenci anlaşmaları etkilenmez. Devam?');"
                              style="display:flex;gap:6px;">
                            @csrf
                            <input type="text" name="termination_reason" maxlength="500" placeholder="Fesih sebebi (ops.)"
                                   style="padding:7px;border:1px solid #cbd5e1;border-radius:7px;font-size:12.5px;">
                            <button type="submit" class="btn" style="color:#dc2626;border-color:#fecaca;">Feshet</button>
                        </form>
                    @endif
                @endpartnerPanel
            </div>
        </div>
    @empty
        <div style="border:1px dashed #cbd5e1;border-radius:12px;padding:26px;text-align:center;color:#64748b;font-size:13.5px;">
            @if($isPartnerSide)
                Henüz size gönderilmiş bir anlaşma yok.
            @else
                Henüz anlaşma yok. Yukarıdan yeni bir çerçeve anlaşma açabilirsiniz.
            @endif
        </div>
    @endforelse

</div>

@endsection

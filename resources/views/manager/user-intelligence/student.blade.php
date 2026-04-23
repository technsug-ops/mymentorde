@extends('manager.layouts.app')
@section('title', 'Öğrenci Aktivite — ' . $student->name)
@section('page_title', '🎓 Öğrenci Aktivite — ' . $student->name)

@section('content')
<style>
.uis-wrap { max-width:1000px; margin:20px auto; padding:0 16px; }
.uis-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.uis-card h2 { margin:0 0 6px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:8px; }

.uis-header { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
@media(max-width:900px){ .uis-header { grid-template-columns:1fr; } }
.uis-meta-row { display:flex; gap:8px; font-size:12px; margin:6px 0; }
.uis-meta-row .key { color:#64748b; min-width:130px; }
.uis-meta-row .val { color:#1e293b; font-weight:600; }

.uis-table { width:100%; border-collapse:collapse; font-size:12px; }
.uis-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#64748b; font-weight:600; font-size:10px; text-transform:uppercase; }
.uis-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; }

.uis-back { display:inline-flex; align-items:center; gap:6px; color:#5b2e91; font-size:13px; margin-bottom:16px; text-decoration:none; }
.uis-back:hover { text-decoration:underline; }

.uis-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; }
.uis-badge.green  { background:#dcfce7; color:#166534; }
.uis-badge.yellow { background:#fef3c7; color:#92400e; }
.uis-badge.red    { background:#fee2e2; color:#991b1b; }
.uis-badge.gray   { background:#f1f5f9; color:#64748b; }
.uis-badge.blue   { background:#dbeafe; color:#1e40af; }
</style>

<div class="uis-wrap">
    <a href="{{ route('manager.user-intelligence') }}" class="uis-back">← User Intelligence'a dön</a>

    {{-- Aksiyon Bar — hızlı erişim --}}
    <x-analytics.action-bar :target="$student" type="student" />

    {{-- Öğrenci header --}}
    <div class="uis-card">
        <h2>👤 {{ $student->name }}</h2>
        <div class="uis-header">
            <div>
                <div class="uis-meta-row"><span class="key">Email</span><span class="val">{{ $student->email }}</span></div>
                <div class="uis-meta-row"><span class="key">Kayıt</span><span class="val">{{ $student->created_at->format('d.m.Y H:i') }}</span></div>
                <div class="uis-meta-row"><span class="key">Presence</span>
                    @php
                        $presCls = match($student->presence_status ?? 'offline') {
                            'online' => 'green',
                            'away'   => 'yellow',
                            'busy'   => 'red',
                            default  => 'gray',
                        };
                    @endphp
                    <span class="val"><span class="uis-badge {{ $presCls }}">{{ $student->presence_status ?? 'offline' }}</span></span>
                </div>
            </div>
            <div>
                <div class="uis-meta-row"><span class="key">Son Aktivite</span><span class="val">{{ $student->last_activity_at ? \Carbon\Carbon::parse($student->last_activity_at)->diffForHumans() : '—' }}</span></div>
                <div class="uis-meta-row"><span class="key">Email Doğrulandı</span><span class="val">{{ $student->email_verified_at ? '✅' : '❌' }}</span></div>
                <div class="uis-meta-row"><span class="key">Aktif</span><span class="val">{{ $student->is_active ? '✅' : '❌' }}</span></div>
            </div>
            <div>
                <div class="uis-meta-row"><span class="key">Başarısız Giriş</span><span class="val">{{ $student->failed_login_attempts ?? 0 }}</span></div>
                <div class="uis-meta-row"><span class="key">Kilitli mi?</span><span class="val">{{ $student->locked_until ? 'Evet ('. \Carbon\Carbon::parse($student->locked_until)->diffForHumans() .')' : 'Hayır' }}</span></div>
                <div class="uis-meta-row"><span class="key">Company ID</span><span class="val">{{ $student->company_id ?? '—' }}</span></div>
            </div>
        </div>
    </div>

    {{-- Randevular --}}
    <div class="uis-card">
        <h2>📅 Son Randevular ({{ $appointments->count() }})</h2>
        @if ($appointments->isEmpty())
            <div style="color:#94a3b8; padding:20px; text-align:center;">Henüz randevu yok.</div>
        @else
            <table class="uis-table">
                <thead>
                    <tr><th>Tarih</th><th>Durum</th><th>İptal Zamanı</th></tr>
                </thead>
                <tbody>
                @foreach ($appointments as $a)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($a->scheduled_at)->format('d.m.Y H:i') }}</td>
                        <td><span class="uis-badge {{ $a->status === 'completed' ? 'green' : ($a->status === 'cancelled' ? 'red' : 'blue') }}">{{ $a->status }}</span></td>
                        <td style="font-size:10px; color:#64748b;">
                            {{ $a->cancelled_at ? \Carbon\Carbon::parse($a->cancelled_at)->diffForHumans() : '—' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Ödemeler --}}
    <div class="uis-card">
        <h2>💰 Ödemeler ({{ $payments->count() }})</h2>
        @if ($payments->isEmpty())
            <div style="color:#94a3b8; padding:20px; text-align:center;">Henüz ödeme yok.</div>
        @else
            <table class="uis-table">
                <thead>
                    <tr><th>Fatura</th><th>Tutar</th><th>Durum</th><th>Vade</th><th>Ödendi</th></tr>
                </thead>
                <tbody>
                @foreach ($payments as $p)
                    @php
                        $statusCls = match($p->status) {
                            'paid'      => 'green',
                            'overdue'   => 'red',
                            'cancelled' => 'gray',
                            default     => 'yellow',
                        };
                    @endphp
                    <tr>
                        <td style="font-size:10px; color:#64748b;">{{ $p->invoice_number ?? '—' }}</td>
                        <td>€{{ number_format($p->amount_eur, 2) }}</td>
                        <td><span class="uis-badge {{ $statusCls }}">{{ $p->status }}</span></td>
                        <td style="font-size:10px; color:#64748b;">{{ $p->due_date ? \Carbon\Carbon::parse($p->due_date)->format('d.m.Y') : '—' }}</td>
                        <td style="font-size:10px; color:#64748b;">{{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('d.m.Y') : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Audit trail --}}
    <div class="uis-card">
        <h2>📝 Son Aksiyonlar ({{ $audits->count() }})</h2>
        <p style="font-size:12px; color:#64748b; margin:0 0 14px;">Son 30 aksiyon — kullanıcının yaptığı işlemler.</p>
        @if ($audits->isEmpty())
            <div style="color:#94a3b8; padding:20px; text-align:center;">Audit trail boş.</div>
        @else
            <table class="uis-table">
                <thead>
                    <tr><th>Aksiyon</th><th>Entity</th><th>Tarih</th><th>IP</th></tr>
                </thead>
                <tbody>
                @foreach ($audits as $a)
                    @php
                        $actionCls = match($a->action) {
                            'create' => 'green',
                            'update' => 'yellow',
                            'delete' => 'red',
                            'login'  => 'blue',
                            default  => 'gray',
                        };
                    @endphp
                    <tr>
                        <td><span class="uis-badge {{ $actionCls }}">{{ $a->action }}</span></td>
                        <td style="font-size:11px;">{{ \Illuminate\Support\Str::afterLast($a->entity_type ?? '', '\\') }}</td>
                        <td style="font-size:10px; color:#64748b;">{{ \Carbon\Carbon::parse($a->created_at)->format('d.m.Y H:i') }}</td>
                        <td style="font-size:10px; color:#94a3b8;">{{ $a->ip_address ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

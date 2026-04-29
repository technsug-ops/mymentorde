@extends('manager.layouts.app')

@section('title', 'Sessizlik Monitörü')
@section('page_title', 'Sessizlik Monitörü')
@section('page_subtitle', 'Aday + öğrenci timeline\'ında hareket olmayan kayıtlar — otomatik "süreç aktif" touchpoint kontrolü')

@push('head')
<style>
.sm-tabs { display:flex; gap:8px; margin-bottom: 14px; border-bottom: 1px solid var(--u-line); }
.sm-tab {
    padding: 8px 14px; font-size: 13px; font-weight: 600; color: var(--u-muted);
    text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px;
}
.sm-tab.active { color: var(--u-text); border-bottom-color: var(--u-brand, #2563eb); }
.sm-tab:hover { color: var(--u-text); }

.sm-info {
    background: rgba(37,99,235,.06); border: 1px solid rgba(37,99,235,.25);
    border-radius: 10px; padding: 10px 14px; margin-bottom: 14px;
    color: var(--u-muted); font-size: 12.5px;
}
.sm-success {
    background: rgba(22,163,74,.08); border: 1px solid rgba(22,163,74,.3);
    color: #15803d; padding: 10px 14px; border-radius: 10px; margin-bottom: 14px;
}
.sm-error {
    background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.3);
    color: rgb(185,28,28); padding: 10px 14px; border-radius: 10px; margin-bottom: 14px;
}

.sm-card { background: var(--u-card); border: 1px solid var(--u-line); border-radius: 10px;
    padding: 14px 16px; margin-bottom: 16px; }
.sm-card h3 { margin: 0 0 10px 0; font-size: 14px; color: var(--u-text); }

.sm-settings { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; align-items: end; }
.sm-settings label { font-size: 11.5px; color: var(--u-muted); display: block; margin-bottom: 4px; font-weight: 600; }
.sm-settings input { width:100%; padding: 6px 8px; border: 1px solid var(--u-line); border-radius: 6px;
    background: var(--u-bg); color: var(--u-text); font-size: 13px; }
.sm-settings input::placeholder { color: var(--u-muted); }
.sm-settings .sm-default-hint { font-size: 11px; color: var(--u-muted); margin-top: 2px; }

.sm-table { width:100%; border-collapse: collapse; font-size: 13px; background: var(--u-card);
    border: 1px solid var(--u-line); border-radius: 10px; overflow: hidden; }
.sm-table th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700;
    color: var(--u-muted); text-transform: uppercase; letter-spacing:.4px;
    border-bottom: 2px solid var(--u-line); background: var(--u-bg); }
.sm-table td { padding: 11px 12px; border-bottom: 1px solid var(--u-line); color: var(--u-text); vertical-align: top; }
.sm-table tr:last-child td { border-bottom: 0; }

.sm-name { font-weight: 700; font-size: 13.5px; }
.sm-meta { color: var(--u-muted); font-size: 11.5px; margin-top: 2px; }

.sm-badge { display: inline-block; padding: 2px 8px; border-radius: 999px;
    font-size: 11px; font-weight: 700; letter-spacing: .3px; }
.sm-stage-application { background: rgba(37,99,235,.1); color:#1d4ed8; }
.sm-stage-uni_assist  { background: rgba(168,85,247,.12); color:#7e22ce; }
.sm-stage-visa        { background: rgba(217,119,6,.12); color: rgb(180,83,9); }
.sm-stage-general     { background: rgba(100,116,139,.15); color: #475569; }
.sm-paused { background: rgba(100,116,139,.15); color: #475569; }
.sm-overdue { background: rgba(220,38,38,.12); color: rgb(185,28,28); }
.sm-fresh   { background: rgba(22,163,74,.12); color: #15803d; }

.sm-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.sm-actions form { display: inline; margin: 0; }
.sm-btn {
    display: inline-block; padding: 5px 10px; font-size: 11.5px; font-weight: 600;
    border-radius: 6px; border: 1px solid var(--u-line); background: var(--u-bg);
    color: var(--u-text); cursor: pointer; text-decoration: none;
}
.sm-btn:hover { background: var(--u-card); border-color: var(--u-brand); }
.sm-btn.primary { background: var(--u-brand, #2563eb); color: white; border-color: var(--u-brand); }
.sm-btn.warn { background: rgba(217,119,6,.1); color: rgb(180,83,9); border-color: rgba(217,119,6,.3); }
.sm-btn.warn:hover { background: rgba(217,119,6,.18); }

.sm-empty { padding: 30px 20px; text-align: center; color: var(--u-muted); }
</style>
@endpush

@section('content')
<div class="container-fluid">

    @if(session('success'))<div class="sm-success">✅ {{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="sm-error">
            @foreach($errors->all() as $err) ⚠ {{ $err }}<br> @endforeach
        </div>
    @endif

    <div class="sm-info">
        <strong>Akış:</strong> Cron her gün 09:30 (aday) / 09:35 (öğrenci) çalışır.
        Sessizlik tetik formülü = <code>max(updated_at, last_senior_action_at, last_silence_checkin_at)</code> + cadence günü.
        Senior gerçek bir not eklediğinde otomatik skip edilir.
        Cadence kaynağı: kişi override → şirket override → config default.
        Mail göndermez — sadece in-app bildirim + system event log.
    </div>

    {{-- ── Şirket bazında genel ayarlar ──────────────────────────────── --}}
    <div class="sm-card">
        <h3>⚙️ Şirket bazında cadence ayarları (tüm aday/öğrenciler için varsayılan)</h3>
        <form method="POST" action="{{ route('manager.silence-monitor.company-overrides') }}">
            @csrf
            <div class="sm-settings">
                @foreach($defaults as $stage => $def)
                    <div>
                        <label>{{ $stageLabels[$stage] ?? $stage }}</label>
                        <input type="number" min="1" max="365" name="{{ $stage }}"
                               value="{{ $companyOverrides[$stage] ?? '' }}"
                               placeholder="varsayılan: {{ $def }}">
                        <div class="sm-default-hint">Boş bırakılırsa default ({{ $def }} gün) kullanılır.</div>
                    </div>
                @endforeach
                <div>
                    <button class="sm-btn primary" type="submit">Kaydet</button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Tabs ──────────────────────────────────────────────────────── --}}
    <div class="sm-tabs">
        <a class="sm-tab {{ $tab === 'guests' ? 'active' : '' }}"
           href="{{ route('manager.silence-monitor.index', ['tab' => 'guests']) }}">
            Aday Öğrenci ({{ $tab === 'guests' ? count($rows) : '' }})
        </a>
        <a class="sm-tab {{ $tab === 'students' ? 'active' : '' }}"
           href="{{ route('manager.silence-monitor.index', ['tab' => 'students']) }}">
            Öğrenci ({{ $tab === 'students' ? count($rows) : '' }})
        </a>
    </div>

    @if(count($rows) === 0)
        <div class="sm-empty">🎉 Bu sekmede şu an sessiz kayıt yok.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th style="width:22%">Kişi</th>
                        <th>Stage</th>
                        <th>Sessizlik</th>
                        <th>Cadence</th>
                        <th>Son check-in</th>
                        <th>Durum</th>
                        <th style="min-width:280px;">Aksiyonlar</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $r)
                    @php
                        $type = $r['type']; $id = $r['id'];
                    @endphp
                    <tr>
                        <td>
                            <div class="sm-name">{{ $r['name'] !== '' ? $r['name'] : '—' }}</div>
                            <div class="sm-meta">{{ $r['email'] }}</div>
                        </td>
                        <td>
                            <span class="sm-badge sm-stage-{{ $r['stage'] }}">{{ $stageLabels[$r['stage']] ?? $r['stage'] }}</span>
                        </td>
                        <td>
                            <span class="sm-badge {{ $r['is_overdue'] ? 'sm-overdue' : 'sm-fresh' }}">{{ $r['days_silent'] }} gün</span>
                            <div class="sm-meta" style="margin-top:4px;">
                                {{ $r['last_activity_at']?->format('d.m.Y') ?? '—' }}
                            </div>
                        </td>
                        <td>
                            <strong>{{ $r['cadence_days'] }}</strong> gün
                            <div class="sm-meta">kaynak: {{ $r['cadence_source'] }}</div>
                        </td>
                        <td>
                            @if($r['last_checkin_at'])
                                <div>{{ $r['last_checkin_at']->format('d.m.Y H:i') }}</div>
                            @else
                                <span class="sm-meta">hiç</span>
                            @endif
                        </td>
                        <td>
                            @if($r['paused'])
                                <span class="sm-badge sm-paused">⏸ Duraklatıldı</span>
                            @else
                                <span class="sm-badge sm-fresh">⏱ Aktif</span>
                            @endif
                            @if($r['override_days'] > 0)
                                <div class="sm-meta" style="margin-top:4px;">override: {{ $r['override_days'] }} gün</div>
                            @endif
                        </td>
                        <td>
                            <div class="sm-actions">
                                <form method="POST" action="{{ route('manager.silence-monitor.trigger', ['type' => $type, 'id' => $id]) }}"
                                      onsubmit="return confirm('Şimdi check-in touchpoint gönderilsin mi?');">
                                    @csrf
                                    <button class="sm-btn primary" type="submit">📍 Şimdi gönder</button>
                                </form>

                                <form method="POST" action="{{ route('manager.silence-monitor.override', ['type' => $type, 'id' => $id]) }}"
                                      onsubmit="var d=prompt('Bu kişi için cadence (gün). Boş bırakırsan kaldırılır.', '{{ $r['override_days'] ?: '' }}'); if(d===null){return false;} this.querySelector('input[name=days]').value=d; return true;">
                                    @csrf
                                    <input type="hidden" name="days" value="">
                                    <button class="sm-btn" type="submit">⏱ Cadence override</button>
                                </form>

                                @if($r['paused'])
                                    <form method="POST" action="{{ route('manager.silence-monitor.resume', ['type' => $type, 'id' => $id]) }}">
                                        @csrf
                                        <button class="sm-btn" type="submit">▶ Aç</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('manager.silence-monitor.pause', ['type' => $type, 'id' => $id]) }}"
                                          onsubmit="return confirm('Bu kayıt için sessizlik check-in durdurulsun mu?');">
                                        @csrf
                                        <button class="sm-btn warn" type="submit">⏸ Durdur</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection

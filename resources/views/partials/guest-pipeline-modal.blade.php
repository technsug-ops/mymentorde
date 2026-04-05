{{-- ═══════════════════════════════════════════════════════════════
     Guest Pipeline Stage Modal — Aşama Değişiklik Formu
     Her iki pipeline view'ında @include ile kullanılır.
     JS: window.showPipelineModal() / window.hidePipelineModal()
════════════════════════════════════════════════════════════════ --}}
<div id="gpm-overlay" style="display:none;position:fixed;inset:0;z-index:99990;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);align-items:center;justify-content:center;">
    <div id="gpm-box" style="background:var(--u-card);border-radius:18px;padding:28px 30px;width:min(520px,96vw);max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,.25);position:relative;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div id="gpm-icon" style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;"></div>
            <div>
                <div id="gpm-title" style="font-size:var(--tx-lg);font-weight:800;color:var(--u-text);"></div>
                <div id="gpm-subtitle" style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;"></div>
            </div>
        </div>

        {{-- Aday bilgi şeridi --}}
        <div id="gpm-guest-bar" style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:var(--tx-sm);display:flex;align-items:center;gap:10px;">
            <div id="gpm-guest-av" style="width:32px;height:32px;border-radius:50%;background:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#fff;flex-shrink:0;"></div>
            <div>
                <div id="gpm-guest-name" style="font-weight:700;color:var(--u-text);"></div>
                <div id="gpm-stage-change" style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:1px;"></div>
            </div>
        </div>

        {{-- Dinamik form alanları --}}
        <div id="gpm-fields"></div>

        {{-- Butonlar --}}
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--u-line);">
            <button id="gpm-cancel" type="button"
                    style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:9px;padding:10px 20px;font-size:var(--tx-sm);font-weight:600;cursor:pointer;">
                İptal
            </button>
            <button id="gpm-confirm" type="button"
                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:9px;padding:10px 22px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;min-width:120px;">
                ✓ Onayla & Taşı
            </button>
        </div>
    </div>
</div>

{{-- Template-spesifik renk + tipografi override'ları --}}

/* ─── Template 1 — Classic ─── */
.promo-tpl-1 {
    background:
        radial-gradient(circle at 20% 0%, rgba(109,40,217,.08), transparent 50%),
        radial-gradient(circle at 80% 100%, rgba(79,70,229,.08), transparent 50%),
        #f8f7ff;
}
.promo-tpl-1 .promo-card {
    background: linear-gradient(160deg, #6d28d9 0%, #4f46e5 100%);
    color: white;
    --card-bg-circle: #f8f7ff;
}
.promo-tpl-1 .promo-card-inner::before {
    content: ''; position: absolute; inset: 0;
    background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0);
    background-size: 22px 22px; pointer-events: none; z-index: 0;
}
.promo-tpl-1 .promo-discount-bar { background: white; color: #6d28d9; }
.promo-tpl-1 .promo-cta { background: white; color: #6d28d9; }
.promo-tpl-1 .promo-cta:hover { background: #fde047; color: #4c1d95; }
.promo-tpl-1 .promo-code-value { color: white; }
.promo-tpl-1 .promo-logo .logo-text { color: white; }
.promo-tpl-1 .promo-logo .logo-text .accent { color: #fbbf24; }

/* ─── Template 2 — Bold ─── */
.promo-tpl-2 {
    background: linear-gradient(135deg, #fdf2f8 0%, #fff7ed 100%);
}
.promo-tpl-2 .promo-card {
    background: linear-gradient(160deg, #ec4899 0%, #f97316 100%);
    color: white;
    --card-bg-circle: #fdf2f8;
}
.promo-tpl-2 .promo-title { font-size: 34px; }
.promo-tpl-2 .promo-discount-bar { background: white; color: #db2777; font-size: 14px; }
.promo-tpl-2 .promo-cta { background: white; color: #db2777; }
.promo-tpl-2 .promo-cta:hover { background: #fde047; color: #831843; }
.promo-tpl-2 .promo-code-value { font-size: 36px; color: white; }
.promo-tpl-2 .promo-logo .logo-mark { background: rgba(255,255,255,.95); color: #db2777; }
.promo-tpl-2 .promo-logo .logo-text { color: white; }
.promo-tpl-2 .promo-logo .logo-text .accent { color: #fde047; }

/* ─── Template 3 — Premium (lüks · ayrıcalıklı · foil texture) ─── */
.promo-tpl-3 {
    background:
        radial-gradient(circle at 30% 10%, rgba(251,191,36,.06), transparent 50%),
        radial-gradient(circle at 70% 90%, rgba(217,119,6,.05), transparent 50%),
        linear-gradient(135deg, #0a0e1a 0%, #16213a 50%, #0f172a 100%);
}
.promo-tpl-3 .promo-card {
    background:
        /* Foil texture — diagonal sheen */
        linear-gradient(135deg, rgba(251,191,36,.04) 0%, transparent 30%, transparent 70%, rgba(251,191,36,.03) 100%),
        /* Subtle vignette */
        radial-gradient(ellipse at 50% 0%, rgba(251,191,36,.07), transparent 65%),
        radial-gradient(ellipse at 50% 100%, rgba(217,119,6,.05), transparent 65%),
        linear-gradient(165deg, #0f172a 0%, #1e293b 50%, #16213a 100%);
    border: 1px solid rgba(251,191,36,.45);
    color: #fde68a;
    --card-bg-circle: #0a0e1a;
    box-shadow:
        0 30px 70px rgba(0,0,0,.45),
        0 0 0 1px rgba(251,191,36,.1),
        inset 0 1px 0 rgba(251,191,36,.18);
}
.promo-tpl-3 .promo-card-inner::before {
    content: ''; position: absolute; inset: 0;
    background:
        /* Damask-style subtle pattern */
        repeating-linear-gradient(45deg, transparent 0 18px, rgba(251,191,36,.018) 18px 19px),
        repeating-linear-gradient(-45deg, transparent 0 18px, rgba(251,191,36,.018) 18px 19px);
    pointer-events: none; z-index: 0;
}
.promo-tpl-3 .promo-title {
    font-family: 'Playfair Display', serif; font-weight: 900;
    color: #fbbf24; letter-spacing: -1px;
    text-shadow: 0 2px 12px rgba(251,191,36,.15);
}
.promo-tpl-3 .promo-subtitle {
    color: #cbd5e1; font-style: italic;
    font-family: 'Playfair Display', serif; font-weight: 400; font-size: 16px;
}
.promo-tpl-3 .promo-discount-bar {
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 50%, #f59e0b 100%);
    color: #0a0e1a;
    box-shadow:
        0 4px 16px rgba(251,191,36,.35),
        inset 0 1px 0 rgba(255,255,255,.4),
        inset 0 -1px 0 rgba(0,0,0,.2);
    font-weight: 900; letter-spacing: 2px;
}
.promo-tpl-3 .promo-code-box {
    border-color: #fbbf24; background: rgba(251,191,36,.05);
    box-shadow: inset 0 0 30px rgba(251,191,36,.06);
}
.promo-tpl-3 .promo-code-value {
    color: #fbbf24; font-family: 'Playfair Display', serif;
    text-shadow: 0 1px 8px rgba(251,191,36,.3);
}
.promo-tpl-3 .promo-cta {
    background: linear-gradient(180deg, #fcd34d 0%, #fbbf24 50%, #f59e0b 100%);
    color: #0a0e1a;
    box-shadow:
        0 10px 24px rgba(251,191,36,.32),
        inset 0 1px 0 rgba(255,255,255,.5),
        inset 0 -1px 0 rgba(0,0,0,.15);
    border: 1px solid #f59e0b;
}
.promo-tpl-3 .promo-cta:hover { transform: translateY(-2px); filter: brightness(1.05); }
.promo-tpl-3 .promo-disclaimer { color: #64748b; font-style: italic; }
.promo-tpl-3 .promo-logo .logo-mark {
    background: linear-gradient(135deg, #fde68a 0%, #fbbf24 50%, #f59e0b 100%);
    color: #0f172a;
    box-shadow: 0 4px 12px rgba(251,191,36,.4), inset 0 1px 0 rgba(255,255,255,.5);
}
.promo-tpl-3 .promo-logo .logo-text { color: #fbbf24; font-family: 'Playfair Display', serif; }
.promo-tpl-3 .promo-logo .logo-text .accent { color: #fde68a; }
.promo-tpl-3 .promo-tagline { color: #94a3b8; letter-spacing: 3px; }

/* Premium altın divider — daha refined */
.promo-gold-divider {
    display: flex; align-items: center; justify-content: center;
    gap: 12px; margin: 0 0 26px; opacity: .85;
}
.promo-gold-divider .line {
    flex: 1; max-width: 90px; height: 1px;
    background: linear-gradient(90deg, transparent, #fbbf24, transparent);
}
.promo-gold-divider .dot {
    width: 5px; height: 5px;
    background: #fbbf24; transform: rotate(45deg);
}
.promo-gold-divider .dot.lg {
    width: 8px; height: 8px;
    background: linear-gradient(135deg, #fde68a, #f59e0b);
    box-shadow: 0 0 8px rgba(251,191,36,.6);
}

/* Premium hero crest — top monogram */
.promo-tpl-3 .promo-crest {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 8px; margin-bottom: 22px;
}
.promo-tpl-3 .promo-crest-tag {
    font-family: 'Playfair Display', serif; font-style: italic; font-size: 13px;
    color: rgba(251,191,36,.85); letter-spacing: 2px;
}
.promo-tpl-3 .promo-crest-icon {
    width: 80px; height: 80px;
}

/* ─── Template 4 — Playful ─── */
.promo-tpl-4 {
    background: linear-gradient(135deg, #fef9c3 0%, #ddd6fe 50%, #fce7f3 100%);
}
.promo-tpl-4 .promo-card {
    background:
        radial-gradient(circle at 80% 20%, rgba(253,224,71,.4), transparent 50%),
        radial-gradient(circle at 20% 80%, rgba(244,114,182,.3), transparent 50%),
        linear-gradient(160deg, #c084fc 0%, #fde047 100%);
    color: #581c87;
    --card-bg-circle: #fef9c3;
}
.promo-tpl-4 .promo-title { font-size: 30px; }
.promo-tpl-4 .promo-discount-bar { background: #581c87; color: #fde047; }
.promo-tpl-4 .promo-code-box { border-color: #581c87; background: rgba(255,255,255,.55); }
.promo-tpl-4 .promo-code-value { color: #581c87; }
.promo-tpl-4 .promo-cta { background: #581c87; color: white; }
.promo-tpl-4 .promo-cta:hover { background: #3b0764; }
.promo-tpl-4 .promo-logo .logo-mark { background: #581c87; color: #fde047; }
.promo-tpl-4 .promo-logo .logo-text { color: #581c87; }
.promo-tpl-4 .promo-logo .logo-text .accent { color: #db2777; }
.promo-tpl-4 .promo-tagline { color: #6d28d9; }

/* Playful sticker — daha zarif konum */
.promo-tpl-4 .promo-sticker {
    position: absolute; top: 28px; right: 24px; z-index: 4;
    transform: rotate(8deg);
    background: #fde047; color: #581c87; padding: 7px 14px; border-radius: 999px;
    font-size: 10.5px; font-weight: 900; letter-spacing: 1.5px;
    box-shadow: 0 6px 16px rgba(88,28,135,.25);
    border: 2px solid #581c87;
    pointer-events: none;
}

/* ─── Template 5 — Urgency ─── */
.promo-tpl-5 {
    background:
        repeating-linear-gradient(45deg, rgba(220,38,38,.04) 0 20px, transparent 20px 40px),
        linear-gradient(135deg, #fee2e2 0%, #fed7aa 100%);
}
.promo-tpl-5 .promo-card {
    background: linear-gradient(160deg, #b91c1c 0%, #dc2626 50%, #ea580c 100%);
    color: white;
    --card-bg-circle: #fee2e2;
}
.promo-tpl-5 .promo-card-inner::before {
    content: ''; position: absolute; inset: 0;
    background: repeating-linear-gradient(
        45deg, transparent 0 32px, rgba(255,255,255,.05) 32px 34px
    );
    z-index: 0; pointer-events: none;
}
.promo-tpl-5 .promo-ribbon {
    position: absolute; top: 24px; right: -42px; transform: rotate(45deg);
    background: #fbbf24; color: #7c2d12; font-weight: 900; padding: 6px 50px;
    font-size: 11px; letter-spacing: 3px; box-shadow: 0 6px 16px rgba(0,0,0,.25);
    z-index: 5; font-family: 'Bebas Neue', 'Inter', sans-serif;
}
.promo-tpl-5 .promo-discount-bar { background: white; color: #dc2626; font-size: 14px; font-weight: 900; }
.promo-tpl-5 .promo-code-value { color: white; }
.promo-tpl-5 .promo-cta { background: #fbbf24; color: #7c2d12; font-weight: 900; }
.promo-tpl-5 .promo-cta:hover { background: white; color: #dc2626; }
.promo-tpl-5 .promo-countdown {
    background: rgba(0,0,0,.32); padding: 10px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px;
    margin-bottom: 18px; letter-spacing: .5px;
    border: 1px solid rgba(251,191,36,.4);
}
.promo-tpl-5 .promo-countdown .num {
    background: #fbbf24; color: #7c2d12; padding: 2px 9px; border-radius: 5px;
    font-weight: 900; font-size: 14px;
}
.promo-tpl-5 .promo-logo .logo-mark { background: white; color: #dc2626; }
.promo-tpl-5 .promo-logo .logo-text { color: white; }
.promo-tpl-5 .promo-logo .logo-text .accent { color: #fde047; }

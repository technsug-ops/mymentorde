{{-- Template-spesifik renk + tipografi override'ları --}}

/* ─── Template 1 — Classic (Profesyonel · Sade) ─── */
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
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 100%; background-image:
        radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0);
    background-size: 18px 18px; pointer-events: none; z-index: 0;
}
.promo-tpl-1 .promo-discount-bar { background: white; color: #6d28d9; }
.promo-tpl-1 .promo-cta { background: white; color: #6d28d9; }
.promo-tpl-1 .promo-cta:hover { background: #fde047; }
.promo-tpl-1 .promo-code-value { color: white; }
.promo-tpl-1 .promo-logo .logo-text { color: white; }
.promo-tpl-1 .promo-logo .logo-text .accent { color: #fbbf24; }

/* ─── Template 2 — Bold (Canlı · Genç) ─── */
.promo-tpl-2 {
    background: linear-gradient(135deg, #fdf2f8 0%, #fff7ed 100%);
}
.promo-tpl-2 .promo-card {
    background: linear-gradient(160deg, #ec4899 0%, #f97316 100%);
    color: white;
    --card-bg-circle: #fdf2f8;
}
.promo-tpl-2 .promo-title { font-size: 38px; }
.promo-tpl-2 .promo-discount-bar { background: white; color: #db2777; font-size: 15px; }
.promo-tpl-2 .promo-cta { background: white; color: #db2777; }
.promo-tpl-2 .promo-cta:hover { background: #1e293b; color: white; }
.promo-tpl-2 .promo-code-value { font-size: 42px; color: white; }
.promo-tpl-2 .promo-logo .logo-mark { background: rgba(255,255,255,.95); color: #db2777; }
.promo-tpl-2 .promo-logo .logo-text { color: white; }
.promo-tpl-2 .promo-logo .logo-text .accent { color: #fde047; }

/* ─── Template 3 — Premium (Lüks · Şık) ─── */
.promo-tpl-3 {
    background:
        radial-gradient(circle at 50% 0%, rgba(251,191,36,.08), transparent 60%),
        linear-gradient(135deg, #0a0e1a 0%, #1e293b 100%);
}
.promo-tpl-3 .promo-card {
    background: linear-gradient(160deg, #0f172a 0%, #1e293b 100%);
    border: 1px solid rgba(251,191,36,.35);
    color: #fbbf24;
    --card-bg-circle: #0a0e1a;
}
.promo-tpl-3 .promo-title {
    font-family: 'Playfair Display', serif; font-weight: 900;
    color: #fbbf24; letter-spacing: -1px;
}
.promo-tpl-3 .promo-subtitle { color: #cbd5e1; font-style: italic; }
.promo-tpl-3 .promo-discount-bar {
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
    color: #0a0e1a; box-shadow: 0 4px 12px rgba(251,191,36,.3);
}
.promo-tpl-3 .promo-code-box { border-color: #fbbf24; background: rgba(251,191,36,.06); }
.promo-tpl-3 .promo-code-value { color: #fbbf24; font-family: 'Playfair Display', serif; }
.promo-tpl-3 .promo-cta { background: linear-gradient(90deg, #fbbf24, #f59e0b); color: #0a0e1a; }
.promo-tpl-3 .promo-cta:hover { background: #fbbf24; }
.promo-tpl-3 .promo-disclaimer { color: #64748b; }
.promo-tpl-3 .promo-logo .logo-mark { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #0f172a; }
.promo-tpl-3 .promo-logo .logo-text { color: #fbbf24; font-family: 'Playfair Display', serif; }
.promo-tpl-3 .promo-logo .logo-text .accent { color: #fde68a; }

/* ─── Template 4 — Playful (Genç · Renkli) ─── */
.promo-tpl-4 {
    background: linear-gradient(135deg, #fef9c3 0%, #ddd6fe 50%, #fce7f3 100%);
}
.promo-tpl-4 .promo-card {
    background:
        radial-gradient(circle at 80% 20%, rgba(253,224,71,.4), transparent 50%),
        radial-gradient(circle at 20% 80%, rgba(244,114,182,.35), transparent 50%),
        linear-gradient(160deg, #c084fc 0%, #fde047 100%);
    color: #581c87;
    --card-bg-circle: #fef9c3;
}
.promo-tpl-4 .promo-title { font-size: 32px; }
.promo-tpl-4 .promo-discount-bar { background: #581c87; color: #fde047; }
.promo-tpl-4 .promo-code-box { border-color: #581c87; background: rgba(255,255,255,.5); }
.promo-tpl-4 .promo-code-value { color: #581c87; }
.promo-tpl-4 .promo-cta { background: #581c87; color: white; }
.promo-tpl-4 .promo-cta:hover { background: #3b0764; }
.promo-tpl-4 .promo-logo .logo-mark { background: #581c87; color: #fde047; }
.promo-tpl-4 .promo-logo .logo-text { color: #581c87; }
.promo-tpl-4 .promo-logo .logo-text .accent { color: #db2777; }

/* ─── Template 5 — Urgency (Limited · Aciliyet) ─── */
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
        45deg, transparent 0 28px, rgba(255,255,255,.06) 28px 30px
    );
    z-index: 0; pointer-events: none;
}
.promo-tpl-5 .promo-ribbon {
    position: absolute; top: 22px; right: -42px; transform: rotate(45deg);
    background: #fbbf24; color: #7c2d12; font-weight: 900; padding: 6px 50px;
    font-size: 11px; letter-spacing: 3px; box-shadow: 0 6px 16px rgba(0,0,0,.25);
    z-index: 5; font-family: 'Bebas Neue', 'Inter', sans-serif;
}
.promo-tpl-5 .promo-discount-bar { background: white; color: #dc2626; font-size: 15px; font-weight: 900; }
.promo-tpl-5 .promo-code-value { color: white; }
.promo-tpl-5 .promo-cta { background: #fbbf24; color: #7c2d12; font-weight: 900; }
.promo-tpl-5 .promo-cta:hover { background: white; color: #dc2626; }
.promo-tpl-5 .promo-countdown {
    background: rgba(0,0,0,.32); padding: 10px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px;
    margin-bottom: 16px; letter-spacing: .5px;
    border: 1px solid rgba(251,191,36,.4);
}
.promo-tpl-5 .promo-countdown .num {
    background: #fbbf24; color: #7c2d12; padding: 2px 8px; border-radius: 5px;
    font-weight: 900; font-size: 14px;
}
.promo-tpl-5 .promo-logo .logo-mark { background: white; color: #dc2626; }
.promo-tpl-5 .promo-logo .logo-text { color: white; }
.promo-tpl-5 .promo-logo .logo-text .accent { color: #fde047; }

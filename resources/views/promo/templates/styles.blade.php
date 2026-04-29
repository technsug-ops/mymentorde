{{-- Template-spesifik renk + tipografi override'ları --}}

/* Template 1 — Classic */
.promo-tpl-1 { background: linear-gradient(135deg, #f8f7ff 0%, #ede9fe 100%); }
.promo-tpl-1 .promo-card { background: linear-gradient(160deg, #6d28d9 0%, #4f46e5 100%); color: white; }
.promo-tpl-1 .promo-discount-bar { background: white; color: #6d28d9; }
.promo-tpl-1 .promo-cta { background: white; color: #6d28d9; }
.promo-tpl-1 .promo-code-value { color: white; }

/* Template 2 — Bold */
.promo-tpl-2 { background: linear-gradient(135deg, #ec4899 0%, #f97316 100%); }
.promo-tpl-2 .promo-card { background: linear-gradient(160deg, #db2777 0%, #ea580c 100%); color: white; }
.promo-tpl-2 .promo-card::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,.1);
}
.promo-tpl-2 .promo-card::after {
    content: ''; position: absolute; bottom: -80px; left: -60px;
    width: 250px; height: 250px; border-radius: 50%; background: rgba(255,255,255,.08);
}
.promo-tpl-2 .promo-title { font-size: 38px; }
.promo-tpl-2 .promo-discount-bar { background: white; color: #db2777; font-size: 16px; }
.promo-tpl-2 .promo-cta { background: white; color: #db2777; }
.promo-tpl-2 .promo-code-value { font-size: 42px; color: white; }

/* Template 3 — Premium (Dark + Gold) */
.promo-tpl-3 { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
.promo-tpl-3 .promo-card {
    background: linear-gradient(160deg, #0f172a 0%, #1e293b 100%);
    border: 1px solid rgba(251,191,36,.3); color: #fbbf24;
}
.promo-tpl-3 .promo-brand { color: #fbbf24; }
.promo-tpl-3 .promo-title {
    font-family: 'Playfair Display', serif; font-weight: 900;
    color: #fbbf24; letter-spacing: -.5px;
}
.promo-tpl-3 .promo-subtitle { color: #cbd5e1; }
.promo-tpl-3 .promo-discount-bar { background: #fbbf24; color: #0f172a; }
.promo-tpl-3 .promo-code-box { border-color: #fbbf24; background: rgba(251,191,36,.05); }
.promo-tpl-3 .promo-code-value { color: #fbbf24; }
.promo-tpl-3 .promo-cta { background: #fbbf24; color: #0f172a; }
.promo-tpl-3 .promo-disclaimer { color: #94a3b8; }

/* Template 4 — Playful */
.promo-tpl-4 { background: linear-gradient(135deg, #fef3c7 0%, #f3e8ff 100%); }
.promo-tpl-4 .promo-card {
    background: linear-gradient(160deg, #c084fc 0%, #fbcfe8 50%, #fde047 100%);
    color: #581c87;
}
.promo-tpl-4 .promo-title { font-size: 30px; }
.promo-tpl-4 .promo-discount-bar { background: #581c87; color: #fde047; }
.promo-tpl-4 .promo-code-box { border-color: #581c87; }
.promo-tpl-4 .promo-code-value { color: #581c87; }
.promo-tpl-4 .promo-cta { background: #581c87; color: white; }

/* Template 5 — Urgency */
.promo-tpl-5 { background: linear-gradient(135deg, #fee2e2 0%, #fed7aa 100%); }
.promo-tpl-5 .promo-card {
    background: linear-gradient(160deg, #dc2626 0%, #ea580c 100%);
    color: white;
}
.promo-tpl-5 .promo-ribbon {
    position: absolute; top: 16px; right: -36px; transform: rotate(45deg);
    background: #fbbf24; color: #7c2d12; font-weight: 800; padding: 6px 48px;
    font-size: 11px; letter-spacing: 2px; box-shadow: 0 4px 12px rgba(0,0,0,.2);
}
.promo-tpl-5 .promo-discount-bar { background: white; color: #dc2626; font-size: 16px; }
.promo-tpl-5 .promo-code-value { color: white; }
.promo-tpl-5 .promo-cta { background: white; color: #dc2626; }
.promo-tpl-5 .promo-countdown {
    background: rgba(0,0,0,.3); padding: 8px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 700; display: inline-block; margin-bottom: 16px;
}

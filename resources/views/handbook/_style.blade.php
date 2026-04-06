{{-- Shared handbook body styles — include inside @push('styles') --}}
<style>
/* ── Handbook wrapper ── */
.handbook-wrap {
    max-width: 880px;
    margin: 0 auto;
    background: var(--u-card, #fff);
    border: 1px solid var(--u-line, #e2e8f0);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    padding: 32px 36px;
}
@media (max-width: 768px) { .handbook-wrap { padding: 18px 16px; } }

/* ── Lang switcher ── */
.handbook-lang { display: flex; gap: 6px; align-items: center; }
.handbook-lang a {
    padding: 6px 16px; border-radius: 8px; font-size: .82rem; font-weight: 700;
    background: var(--u-bg, #f1f5f9); color: var(--u-muted, #64748b);
    border: 1px solid var(--u-line, #e2e8f0); text-decoration: none;
    transition: all .15s;
}
.handbook-lang a:hover { border-color: var(--u-brand); color: var(--u-brand); }
.handbook-lang a.active { background: var(--u-brand); color: #fff; border-color: var(--u-brand); }

/* ── Body typography ── */
.handbook-body { line-height: 1.8; color: var(--u-text, #0f172a); font-size: .92rem; }

.handbook-body h1 {
    font-size: 1.5rem; font-weight: 800; color: var(--u-brand);
    margin: 2.5rem 0 .6rem; letter-spacing: -.01em;
}
.handbook-body h2 {
    font-size: 1.2rem; font-weight: 700; color: var(--u-text);
    margin: 2.2rem 0 .5rem; padding: 10px 16px;
    background: linear-gradient(135deg, rgba(var(--brand-rgb, 37,99,235), .08), rgba(var(--brand-rgb, 37,99,235), .03));
    border-left: 3px solid var(--u-brand); border-radius: 0 10px 10px 0;
}
.handbook-body h3 {
    font-size: 1.02rem; font-weight: 700; color: var(--u-text);
    margin: 1.6rem 0 .4rem; padding-bottom: .3rem;
    border-bottom: 1px dashed var(--u-line);
}
.handbook-body h4 {
    font-size: .92rem; font-weight: 600; color: var(--u-muted);
    margin: 1.2rem 0 .3rem; text-transform: uppercase; letter-spacing: .03em;
}
.handbook-body p { margin-bottom: .75rem; }

/* ── Lists ── */
.handbook-body ul, .handbook-body ol { margin: .5rem 0 1rem 1.4rem; }
.handbook-body li { margin-bottom: .35rem; }
.handbook-body ul li::marker { color: var(--u-brand); }

/* ── Table ── */
.handbook-body table {
    width: 100%; border-collapse: collapse; margin: 1rem 0;
    font-size: .85rem; border-radius: 10px; overflow: hidden;
    border: 1px solid var(--u-line);
}
.handbook-body thead th {
    background: var(--u-brand); color: #fff;
    padding: 10px 14px; text-align: left; font-weight: 600; font-size: .82rem;
    text-transform: uppercase; letter-spacing: .03em;
}
.handbook-body td {
    padding: 9px 14px; border-bottom: 1px solid var(--u-line);
    vertical-align: top;
}
.handbook-body tr:nth-child(even) td { background: var(--u-bg, #f8fafc); }
.handbook-body tr:hover td { background: rgba(var(--brand-rgb, 37,99,235), .04); }

/* ── Code ── */
.handbook-body code {
    background: var(--u-bg); border: 1px solid var(--u-line);
    padding: 2px 7px; border-radius: 5px; font-size: .82rem;
    font-family: 'Consolas', 'Monaco', monospace; color: var(--u-brand);
}
.handbook-body pre {
    background: #1e293b; color: #e2e8f0; border: none;
    padding: 18px 20px; border-radius: 10px; overflow-x: auto;
    margin: 1rem 0; font-size: .82rem; line-height: 1.6;
}
.handbook-body pre code {
    border: none; padding: 0; background: none; color: inherit;
}

/* ── Blockquote ── */
.handbook-body blockquote {
    border-left: 3px solid var(--u-brand); padding: 12px 18px;
    background: rgba(var(--brand-rgb, 37,99,235), .04);
    border-radius: 0 10px 10px 0; margin: 1rem 0;
    color: var(--u-muted); font-style: italic;
}

/* ── HR ── */
.handbook-body hr {
    border: none; height: 1px; margin: 2rem 0;
    background: linear-gradient(to right, transparent, var(--u-line), transparent);
}

/* ── Strong / Bold ── */
.handbook-body strong { color: var(--u-text); font-weight: 700; }

/* ── Links ── */
.handbook-body a { color: var(--u-brand); text-decoration: none; font-weight: 600; }
.handbook-body a:hover { text-decoration: underline; }

/* ── TOC block ── */
.handbook-toc {
    background: var(--u-bg); border: 1px solid var(--u-line); border-radius: 12px;
    padding: 18px 22px; margin-bottom: 28px;
}
.handbook-toc h4 { margin: 0 0 10px; font-size: .88rem; color: var(--u-muted); font-weight: 700; }
.handbook-toc a {
    color: var(--u-brand); text-decoration: none; display: block;
    padding: 3px 0; line-height: 1.6; font-size: .88rem;
}
.handbook-toc a:hover { text-decoration: underline; }

/* ── Images ── */
.handbook-body img {
    max-width: 100%; height: auto; border-radius: 10px;
    border: 1px solid var(--u-line); margin: .5rem 0;
}

/* ── Section Accordion (H2) ── */
.hb-section {
    margin-bottom: 8px; border: 1px solid var(--u-line);
    border-radius: 12px; overflow: hidden;
    background: var(--u-card, #fff);
}
.hb-section-head {
    width: 100%; border: none; background: var(--u-bg, #f1f5f9);
    padding: 14px 20px; font-size: 1.05rem; font-weight: 700;
    color: var(--u-text); cursor: pointer; text-align: left;
    display: flex; align-items: center; gap: 10px;
    transition: background .15s, color .15s;
}
.hb-section-head:hover { background: rgba(var(--brand-rgb, 37,99,235), .08); color: var(--u-brand); }
.hb-section-head.active { background: var(--u-brand); color: #fff; }
.hb-section-icon { font-size: .85rem; flex-shrink: 0; width: 16px; text-align: center; }
.hb-section-body { padding: 20px 24px; }
@media (max-width: 768px) { .hb-section-body { padding: 14px 16px; } }

/* ── FAQ Sub-Accordion (H3) ── */
.hb-faq-group {
    margin-bottom: 6px; border: 1px solid var(--u-line);
    border-radius: 10px; overflow: hidden;
}
.hb-faq-head {
    width: 100%; border: none; background: var(--u-card, #fff);
    padding: 11px 16px; font-size: .9rem; font-weight: 600;
    color: var(--u-text); cursor: pointer; text-align: left;
    display: flex; align-items: center; gap: 8px;
    transition: background .15s;
}
.hb-faq-head:hover { background: var(--u-bg); }
.hb-faq-head.active { background: rgba(var(--brand-rgb, 37,99,235), .06); color: var(--u-brand); }
.hb-faq-icon { font-size: .75rem; flex-shrink: 0; width: 14px; text-align: center; color: var(--u-muted); }
.hb-faq-body { padding: 10px 16px 14px; border-top: 1px solid var(--u-line); }
.hb-faq-body p { margin-bottom: .5rem; font-size: .88rem; }
.hb-faq-body strong { color: var(--u-brand); }
</style>

{{-- Shared handbook body styles — include inside @push('styles') --}}
<style>
.handbook-wrap { max-width:860px; }
.handbook-lang { display:flex; gap:8px; align-items:center; }
.handbook-lang a { padding:5px 14px; border-radius:6px; font-size:.85rem; font-weight:600;
    background:var(--u-bg); color:var(--u-muted); border:1px solid var(--u-line); text-decoration:none; }
.handbook-lang a.active { background:var(--u-brand); color:#fff; border-color:var(--u-brand); }

.handbook-body { line-height:1.75; color:var(--u-text); }
.handbook-body h1 { font-size:1.6rem; font-weight:800; color:var(--u-brand); margin:2.5rem 0 .75rem; }
.handbook-body h2 { font-size:1.25rem; font-weight:700; color:var(--u-brand); margin:2.5rem 0 .6rem;
    padding-bottom:.4rem; border-bottom:2px solid var(--u-line); }
.handbook-body h3 { font-size:1.05rem; font-weight:700; color:var(--u-text); margin:1.8rem 0 .5rem; }
.handbook-body h4 { font-size:.95rem; font-weight:600; color:var(--u-muted); margin:1.3rem 0 .4rem; }
.handbook-body p  { margin-bottom:.8rem; }
.handbook-body ul,.handbook-body ol { margin:.4rem 0 1rem 1.6rem; }
.handbook-body li { margin-bottom:.3rem; }
.handbook-body table { width:100%; border-collapse:collapse; margin:1rem 0; font-size:.9rem; }
.handbook-body th { background:var(--u-brand); color:#fff; padding:9px 12px; text-align:left; font-weight:600; }
.handbook-body td { padding:8px 12px; border-bottom:1px solid var(--u-line); }
.handbook-body tr:hover td { background:var(--u-bg); }
.handbook-body code { background:var(--u-bg); border:1px solid var(--u-line);
    padding:2px 6px; border-radius:4px; font-size:.85rem; font-family:monospace; }
.handbook-body pre { background:var(--u-bg); border:1px solid var(--u-line);
    padding:16px; border-radius:8px; overflow-x:auto; margin:1rem 0; }
.handbook-body pre code { border:none; padding:0; background:none; }
.handbook-body blockquote { border-left:3px solid var(--u-brand); padding-left:16px;
    color:var(--u-muted); margin:1rem 0; font-style:italic; }
.handbook-body hr { border:none; border-top:1px solid var(--u-line); margin:2rem 0; }
.handbook-body strong { color:var(--u-text); }
.handbook-toc { background:var(--u-bg); border:1px solid var(--u-line); border-radius:10px;
    padding:16px 20px; margin-bottom:28px; font-size:.88rem; }
.handbook-toc h4 { margin:0 0 10px; font-size:.9rem; color:var(--u-muted); }
.handbook-toc a { color:var(--u-brand); text-decoration:none; display:block;
    padding:2px 0; line-height:1.6; }
.handbook-toc a:hover { text-decoration:underline; }
</style>

{{-- Talep ekranlarının ortak stili — üç görünüm de aynı dili konuşsun. --}}
<style>
.pr-note  { background:rgba(30,64,175,.05); border:1px solid rgba(30,64,175,.2); border-left:3px solid #1e40af; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:12px; line-height:1.55; }
.pr-table { width:100%; border-collapse:collapse; font-size:12px; }
.pr-table thead tr { background:var(--bg,#f8fafc); }
.pr-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.pr-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.pr-table tbody tr:hover { background:rgba(30,64,175,.03); }
.pr-table td { padding:8px 10px; vertical-align:middle; }
.pr-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
.pr-open  { background:rgba(217,119,6,.1); color:#b45309; }
.pr-done  { background:rgba(22,163,74,.1); color:#15803d; }
.pr-late  { background:rgba(220,38,38,.1); color:#b91c1c; }
.pr-info  { background:rgba(30,64,175,.08); color:#1e40af; }
.pr-muted { color:var(--muted,#64748b); }
.pr-empty { padding:28px 14px; text-align:center; color:var(--muted,#64748b); font-size:13px; line-height:1.6; }
.pr-btn   { display:inline-block; padding:4px 10px; font-size:11px; font-weight:600; color:#1e40af; border:1px solid rgba(30,64,175,.3); border-radius:6px; background:rgba(30,64,175,.05); text-decoration:none; cursor:pointer; white-space:nowrap; }
.pr-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; display:block; }
.pr-group { border:1px solid var(--border,#e2e8f0); border-radius:8px; padding:10px 12px; margin-bottom:8px; }
.pr-group-title { font-size:11px; font-weight:700; color:var(--text,#0f172a); margin-bottom:7px; }
.pr-checks { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:5px; }
.pr-check { display:flex; align-items:center; gap:6px; font-size:12px; }
.pr-item { border:1px solid var(--border,#e2e8f0); border-radius:8px; padding:11px 13px; margin-bottom:8px; }
.pr-item.done { background:rgba(22,163,74,.04); border-color:rgba(22,163,74,.25); }
</style>

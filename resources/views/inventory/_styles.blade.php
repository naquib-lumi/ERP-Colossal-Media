<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
  :root{
    --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB; --thead:#F9FAFB;
    --text:#101828; --muted:#667085;
    --shadow:0 3px 10px rgba(16,24,40,.06);
    --primary:#3B82F6; --primary-600:#2563EB; --dark:#111827;
    --ok:#067647; --ok-bg:#ECFDF3; --warn:#B54708; --warn-bg:#FFFAEB; --bad:#B42318; --bad-bg:#FEF3F2;
  }
  body{background:var(--bg)}
  .inv-wrap{max-width:1200px;margin:0 auto;padding:20px}
  .inv-card{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow)}
  .inv-hd{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid var(--border);flex-wrap:wrap}
  .inv-title{font-size:20px;font-weight:700;color:var(--text)}
  .inv-sub{font-size:13px;color:var(--muted)}
  .inv-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid transparent;padding:8px 12px;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;white-space:nowrap}
  .inv-btn-primary{background:var(--primary);color:#fff}.inv-btn-primary:hover{background:var(--primary-600);color:#fff}
  .inv-btn-dark{background:var(--dark);color:#fff}.inv-btn-dark:hover{background:#0B1220;color:#fff}
  .inv-btn-ghost{background:#fff;border-color:var(--border);color:var(--text)}.inv-btn-ghost:hover{background:#F3F4F6;color:var(--text)}
  .inv-toolbar{display:flex;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border);flex-wrap:wrap}
  .inv-toolbar input,.inv-toolbar select{height:40px;border:1px solid var(--border);border-radius:10px;padding:0 12px;background:#fff;color:var(--text)}
  .inv-toolbar input{flex:1;min-width:200px}
  .inv-table-wrap{padding:6px 12px 10px;overflow-x:auto}
  .inv-table{width:100%;border-collapse:separate;border-spacing:0 8px}
  .inv-table thead th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;padding:10px 14px;background:var(--thead);white-space:nowrap}
  .inv-table tbody td{padding:12px 14px;color:var(--text);vertical-align:middle;background:#fff;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
  .inv-table tbody td:first-child{border-left:1px solid var(--border);border-radius:12px 0 0 12px}
  .inv-table tbody td:last-child{border-right:1px solid var(--border);border-radius:0 12px 12px 0}
  .inv-num{font-variant-numeric:tabular-nums;text-align:right;white-space:nowrap}
  .inv-neg{color:var(--bad);font-weight:600}
  .inv-pos{color:var(--ok)}
  .inv-badge{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
  .inv-badge.ok{background:var(--ok-bg);color:var(--ok)}
  .inv-badge.near{background:var(--warn-bg);color:var(--warn)}
  .inv-badge.low{background:var(--bad-bg);color:var(--bad)}
  .inv-badge.neg{background:var(--bad-bg);color:var(--bad)}
  .inv-actions{display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap}
  .inv-alert{margin:14px 20px 0;padding:12px 14px;border-radius:10px;font-size:14px}
  .inv-alert.ok{background:var(--ok-bg);color:var(--ok)}
  .inv-alert.bad{background:var(--bad-bg);color:var(--bad)}
  .inv-alert ul{margin:0;padding-left:18px}
  .inv-empty{text-align:center;color:var(--muted);padding:30px}
  .inv-pager{display:flex;gap:8px;align-items:center;justify-content:flex-end;padding:4px 20px 18px;color:var(--muted);font-size:13px}
  /* modal */
  .inv-mask{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:16px;z-index:2000}
  .inv-mask.open{display:flex}
  .inv-dialog{width:480px;max-width:100%;background:#fff;border-radius:12px;box-shadow:var(--shadow);overflow:hidden}
  .inv-dhd{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-weight:700;color:var(--text)}
  .inv-dbd{padding:18px}
  .inv-dft{padding:14px 18px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}
  .inv-field{margin-bottom:14px}
  .inv-label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}
  .inv-control{height:40px;border:1px solid var(--border);border-radius:10px;padding:0 12px;width:100%}
  textarea.inv-control{height:auto;min-height:70px;padding:10px 12px}
  .inv-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .inv-hint{font-size:12px;color:var(--muted);margin-top:4px}
  .inv-x{border:0;background:transparent;cursor:pointer;font-size:18px;color:var(--muted)}
</style>

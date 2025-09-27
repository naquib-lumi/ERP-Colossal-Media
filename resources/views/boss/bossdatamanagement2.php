@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  :root{
    --border:#E5E7EB;
    --thead:#F9FAFB;
    --text:#101828;
    --muted:#667085;
  }
  .page-wrap{max-width:1140px;margin:0 auto;padding:20px}
  .card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 1px 3px rgba(16,24,40,.08)}
  .hd{display:flex;align-items:center;justify-content:space-between;padding:20px}
  .title{font-size:20px;font-weight:700;color:var(--text)}
  .filters{display:flex;gap:12px}
  .control{height:40px;border:1px solid var(--border);border-radius:8px;padding:0 12px;background:#fff;outline:none}
  .control.input{min-width:260px}
  .table-wrap{margin:0 20px 20px;border:1px solid var(--border);border-radius:12px;overflow:hidden}
  table{width:100%;border-collapse:separate;border-spacing:0}
  thead th{background:var(--thead);color:#475467;font-weight:600;text-align:left;padding:14px 16px}
  tbody td{padding:14px 16px;color:var(--text);border-top:1px solid var(--border)}
  tbody tr:hover{background:#FAFAFB}
  .muted{color:var(--muted)}
  .currency{white-space:nowrap}
  .act-eye{display:inline-flex;width:32px;height:32px;border-radius:999px;align-items:center;justify-content:center;border:1px solid var(--border);background:#fff}
  .act-eye .bi{font-size:16px;color:#111827}
  .tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:16px 20px}
  .paging{display:flex;align-items:center;gap:8px}
  .p-btn{min-width:36px;height:36px;border:1px solid var(--border);border-radius:8px;background:#fff}
  .p-btn.active{background:#111827;color:#fff;border-color:#111827}
  .p-btn.icon{display:grid;place-items:center}
</style>

<div class="page-wrap">
  <div class="card">
    <div class="hd">
      <div class="title">Costing Data Management</div>
      <div class="filters">
        <input class="control input" type="search" placeholder="Search machine..." />
        <select class="control" aria-label="All Types">
          <option>All Types</option>
          <option>Printing</option>
          <option>Furnishing</option>
          <option>Delivery</option>
          <option>Installation</option>
        </select>
        <select class="control" aria-label="Last 30 Days">
          <option>Last 30 Days</option>
          <option>Last 7 Days</option>
          <option>This Month</option>
          <option>Last Month</option>
        </select>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Product ID</th>
            <th>Product Quantity</th>
            <th>Used Quantity</th>
            <th>Total Cost</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>ORD-2025-001</td>
            <td><span class="muted">6 Products</span></td>
            <td>12,540</td>
            <td class="currency">RM 37.62</td>
            <td><button class="act-eye"><i class="bi bi-eye"></i></button></td>
          </tr>
          <tr>
            <td>ORD-2025-002</td>
            <td><span class="muted">4 Products</span></td>
            <td>8,320</td>
            <td class="currency">RM 99.84</td>
            <td><button class="act-eye"><i class="bi bi-eye"></i></button></td>
          </tr>
          <tr>
            <td>ORD-2025-003</td>
            <td><span class="muted">3 Products</span></td>
            <td>6,750</td>
            <td class="currency">RM 57.38</td>
            <td><button class="act-eye"><i class="bi bi-eye"></i></button></td>
          </tr>
          <tr>
            <td>ORD-2025-004</td>
            <td><span class="muted">3 Products</span></td>
            <td>4,200</td>
            <td class="currency">RM 27.30</td>
            <td><button class="act-eye"><i class="bi bi-eye"></i></button></td>
          </tr>
          <tr>
            <td>ORD-2025-005</td>
            <td><span class="muted">5 Products</span></td>
            <td>9,850</td>
            <td class="currency">RM 44.33</td>
            <td><button class="act-eye"><i class="bi bi-eye"></i></button></td>
          </tr>
          <tr>
            <td>ORD-2025-006</td>
            <td><span class="muted">2 Products</span></td>
            <td>3,400</td>
            <td class="currency">RM 32.30</td>
            <td><button class="act-eye"><i class="bi bi-eye"></i></button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="tbl-footer">
      <div class="muted">Showing <strong>1</strong> to <strong>6</strong> of <strong>12</strong> results</div>
      <div class="paging">
        <button class="p-btn icon"><i class="bi bi-chevron-left"></i></button>
        <button class="p-btn active">1</button>
        <button class="p-btn">2</button>
        <button class="p-btn icon"><i class="bi bi-chevron-right"></i></button>
      </div>
    </div>
  </div>
</div>
@endsection

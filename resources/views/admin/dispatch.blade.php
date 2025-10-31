{{-- resources/views/admin/dispatch.blade.php --}}
@extends('layouts.app')

@section('title','Dispatch Control Overview')

@section('content')
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
:root{
  --bg:#F6F8FC; --card:#fff; --border:#E5E7EB; --text:#0F172A; --muted:#64748B;
  --shadow:0 8px 24px rgba(15,23,42,.06); --primary:#5c5cff; --r:16px;
}
body{background:var(--bg);}
.page{max-width:1200px;margin:0 auto;padding:24px;}

.page-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.page-head h1{font-weight:800;font-size:28px;color:var(--text);margin:0;}
.back-link{display:inline-flex;align-items:center;gap:6px;font-weight:700;color:var(--primary);text-decoration:none;transition:color .2s ease, transform .2s ease;}
.back-link:hover{color:#3f3fff;transform:translateX(-3px);}
.back-link i{font-size:18px;}

.tbar{background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);padding:14px;margin-bottom:18px}
.trow-top{display:grid;gap:10px;grid-template-columns:1.2fr 1fr 1.2fr 200px}
.trow-btm{display:grid;gap:10px;grid-template-columns:1.7fr .9fr .6fr auto;align-items:center}
@media (max-width:1200px){
  .trow-top{grid-template-columns:1fr 1fr}
  .trow-btm{grid-template-columns:1fr 1fr 1fr}
}
@media (max-width:780px){
  .page-head{flex-direction:column;align-items:flex-start;gap:8px;}
  .trow-top,.trow-btm{grid-template-columns:1fr}
}
.tinp,.tsel{height:44px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:0 12px;font-size:14px;outline:0;width:100%}
.tinp::placeholder{color:#A3AEC3}
.tinp:focus,.tsel:focus{border-color:#C8D1EA;box-shadow:0 0 0 4px rgba(79,70,229,.08)}
.tbtn{height:44px;border-radius:12px;padding:0 22px;font-weight:800;display:flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--border);background:#fff;cursor:pointer}
.tbtn-primary{background:var(--primary);color:#fff;border-color:var(--primary);width:100%}
.tbtn-soft{background:#F3F3FF;color:#5c5cff}
.tbtn-ghost{background:#fff;color:#0F172A}
.tico{font-size:18px;vertical-align:middle}
.t-export{justify-self:end}

/* ===== Table ===== */
.table-card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow)}
.table{width:100%;table-layout:fixed;border-collapse:separate;border-spacing:0;}
.table th{font-size:12px;text-transform:uppercase;color:#64748B;letter-spacing:.04em;padding:12px 16px;border-bottom:1px solid var(--border);text-align:left}
.table td{padding:14px 16px;border-bottom:1px solid var(--border);color:#0F172A;font-size:14px}
.pill{display:inline-block;padding:.25rem .6rem;border-radius:999px;background:#EEF2FF;color:#4F46E5;font-size:.75rem;font-weight:700}
.badge{display:inline-block;padding:.25rem .6rem;border-radius:999px;font-size:.75rem;font-weight:700}
.badge.green{background:#ECFDF5;color:#067647}
.badge.blue{background:#E0F2FE;color:#075985}
.badge.orange{background:#FFF7ED;color:#C2410C}
.action i{font-size:18px;margin:0 6px;color:#64748B;cursor:pointer}
.action i:hover{color:#111827}
.pagination{margin:0;padding:20px;display:flex;justify-content:end}
</style>

<div class="page">

  {{-- ===== 标题 + 返回按钮 ===== --}}
  <div class="page-head">
    <h1>Delivery & Installation Overview</h1>
    <a href="{{ route('admin.fulfillment') }}" class="back-link">
      <i class='bx bx-arrow-back'></i> Back to Fulfillment
    </a>
  </div>

  {{-- ===== Toolbar ===== --}}
  <form method="GET" action="{{ route('admin.dispatch') }}" class="tbar">
    <div class="trow-top">
      <input class="tinp" type="text" name="q" value="{{ request('q') }}" placeholder="Search by Order ID or Job Title">
      <input class="tinp" type="text" name="artist" value="{{ request('artist') }}" placeholder="Search artist name...">
      <input class="tinp" type="text" name="details" value="{{ request('details') }}" placeholder="Search orders or product details">
      <select class="tsel" name="status">
        <option value="all" @selected(request('status','all')==='all')>All statuses</option>
        <option value="in_progress" @selected(request('status')==='in_progress')>In progress</option>
        <option value="assigned" @selected(request('status')==='assigned')>Assigned</option>
        <option value="to_assign" @selected(request('status')==='to_assign')>To assign</option>
        <option value="completed" @selected(request('status')==='completed')>Completed</option>
        <option value="rejected" @selected(request('status')==='rejected')>Rejected</option>
      </select>
    </div>

    <div class="trow-btm" style="margin-top:10px">
      <input class="tinp" id="date_range" type="text" name="date_range"
             value="{{ request('date_range') }}"
             placeholder="Select date range (dd/mm/yyyy -dd/mm/yyyy)">
      <button type="submit" class="tbtn tbtn-primary">Filter</button>
      <a href="{{ route('admin.dispatch') }}" class="tbtn tbtn-soft">Reset</a>
      <a href="{{ route('admin.dispatch.export', request()->query()) }}" class="tbtn tbtn-ghost t-export">
        <i class='bx bx-export tico'></i> Export
      </a>
    </div>
  </form>

  {{-- ===== 表格 ===== --}}
  <div class="table-card">
    <table class="table">
      <thead>
        <tr>
          <th>PRODUCT ID</th>
          <th>PRODUCT NAME</th>
          <th>TASK TYPE</th>
          <th>DEADLINE</th>
          <th>STATUS</th>
          <th style="text-align:left;padding-left:15px;">ACTION</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tasks as $t)
          @php
            $status = strtolower($t['status'] ?? '');
            $badgeClass = match(true){
              $status === 'completed' => 'green',
              in_array($status, ['rejected']) => 'orange',
              default => 'blue',
            };
          @endphp
          <tr>
            <td>{{ $t['id'] }}</td>
            <td>{{ $t['name'] }}</td>
            <td><span class="pill">{{ $t['type'] ?? 'N/A' }}</span></td>
            <td>{{ $t['deadline'] }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ $t['status'] }}</span></td>
            <td class="action" style="text-align:left;padding-left:15px;">
              <a href="javascript:void(0)" title="View"><i class='bx bx-show-alt'></i></a>
              <a href="javascript:void(0)" title="Edit"><i class='bx bx-edit-alt'></i></a>
              <a href="javascript:void(0)" title="Confirm"><i class='bx bx-check'></i></a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center;color:#999;">No records found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const dr = document.getElementById('date_range');
  if(!dr) return;
  const fp = flatpickr(dr, {
    mode:'range',
    dateFormat:'d/m/Y',
    allowInput:true,
    locale:{ rangeSeparator:' - ' }
  });
  if (dr.value && dr.value.includes('-')) {
    const p = dr.value.split('-').map(s=>s.trim());
    if (p.length===2 && p[0] && p[1]) fp.setDate([p[0], p[1]], false, 'd/m/Y');
  }
});
</script>
@endsection

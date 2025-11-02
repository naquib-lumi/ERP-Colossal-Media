@extends('layouts.app')

@section('title','Delivery & Installation Overview')

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
.h1{font-weight:800;font-size:28px;color:var(--text);margin-bottom:16px}

/* ===== Toolbar Layout ===== */
.tbar{background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);padding:14px;margin-bottom:18px}
.trow-top{display:grid;gap:10px;grid-template-columns:1.2fr 1fr 1.2fr 200px}
.trow-btm{display:grid;gap:10px;grid-template-columns:1.7fr .9fr .6fr auto;align-items:center}
@media (max-width:1200px){
  .trow-top{grid-template-columns:1fr 1fr}
  .trow-btm{grid-template-columns:1fr 1fr 1fr}
}
@media (max-width:780px){
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

/* Back button (light) */
.tbtn-back{
  border:0;background:transparent;color:var(--primary);
  font-weight:800;display:inline-flex;align-items:center;gap:8px;
}
.tbtn-back:hover{color:#2e2eff}

/* ===== Table ===== */
.table-card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow)}
.table{width:100%;border-collapse:collapse}
.table th{font-size:12px;text-transform:uppercase;color:#64748B;letter-spacing:.04em;padding:12px 16px;border-bottom:1px solid var(--border);text-align:left}
.table td{padding:14px 16px;border-bottom:1px solid var(--border);color:#0F172A;font-size:14px}
.pill{display:inline-block;padding:.25rem .6rem;border-radius:999px;background:#EEF2FF;color:#4F46E5;font-size:.75rem;font-weight:700}
.badge{display:inline-block;padding:.25rem .6rem;border-radius:999px;font-size:.75rem;font-weight:700}
.badge.green{background:#ECFDF5;color:#067647}
.badge.blue{background:#E0F2FE;color:#075985}
.badge.orange{background:#FFF7ED;color:#C2410C}

.action i{font-size:18px;margin:0 6px;color:#64748B;cursor:pointer}
.action i:hover{color:#111827}
.op-upload{color:#C2410C}
.op-check{color:#067647}

.pagination{margin:0;padding:20px;display:flex;justify-content:end}
</style>

<div class="page">

  {{-- ===== Header + Back ===== --}}
  <div class="d-flex" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:6px;">
    <div class="h1" style="margin:0;">Delivery & Installation Overview</div>
    <a href="{{ route('admin.fulfillment') }}" class="tbtn-back">
      <i class='bx bx-arrow-back'></i> Back to Fulfillment
    </a>
  </div>

  {{-- ===== Toolbar ===== --}}
  <form method="GET" action="{{ route('admin.installation') }}" class="tbar">
    {{-- 上排：3输入 + 状态下拉 --}}
    <div class="trow-top">
      <input class="tinp" type="text" name="q" value="{{ request('q') }}" placeholder="Search by Order ID or Job Title">
      <input class="tinp" type="text" name="artist" value="{{ request('artist') }}" placeholder="Search artist name...">
      <input class="tinp" type="text" name="details" value="{{ request('details') }}" placeholder="Search orders or product details">

      <select class="tsel" name="status">
        <option value="all" @selected(request('status','all')==='all')>All statuses</option>
        <option value="pending_permit" @selected(request('status')==='pending_permit')>Pending Permit</option>
        <option value="in_progress" @selected(request('status')==='in_progress')>In progress</option>
        <option value="assigned" @selected(request('status')==='assigned')>Assigned</option>
        <option value="to_assign" @selected(request('status')==='to_assign')>To assign</option>
        <option value="completed" @selected(request('status')==='completed')>Completed</option>
        <option value="rejected" @selected(request('status')==='rejected')>Rejected</option>
      </select>
    </div>

    {{-- 下排：日期 + 按钮组 --}}
    <div class="trow-btm" style="margin-top:10px">
      <input class="tinp" id="date_range" type="text" name="date_range"
             value="{{ request('date_range') }}"
             placeholder="Select date range (dd/mm/yyyy - dd/mm/yyyy)">

      <button type="submit" class="tbtn tbtn-primary">Filter</button>
      <a href="{{ route('admin.installation') }}" class="tbtn tbtn-soft">Reset</a>

      <a href="{{ route('admin.installation.export', request()->query()) }}" class="tbtn tbtn-ghost t-export">
        <i class='bx bx-export tico'></i> Export
      </a>
    </div>
  </form>

  {{-- ===== Data Table ===== --}}
  <div class="table-card">
    <table class="table">
      <thead>
        <tr>
          <th>PRODUCT ID</th>
          <th>PRODUCT NAME</th>
          <th>TASK TYPE</th>
          <th>DEADLINE</th>
          <th>STATUS</th>
          <th>PERMIT / CONFIRM</th>
          <th>ACTION</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $item)
          @php
            $status = strtolower($item->status ?? '');
            $badgeClass = match(true){
              $status === 'completed' => 'green',
              in_array($status, ['rejected','pending_permit']) => 'orange',
              default => 'blue',
            };
            $rowId = $item->id ?? $item->order_id;
          @endphp
          <tr>
            <td>{{ $item->order_id }}</td>
            <td>{{ $item->product_name }}</td>
            <td><span class="pill">{{ $item->task_type ? ucfirst($item->task_type) : 'N/A' }}</span></td>
            <td>{{ $item->deadline ? $item->deadline->format('Y-m-d') : '—' }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ $status ? ucwords(str_replace('_',' ',$status)) : 'N/A' }}</span></td>
            <td>
              @if(!empty($item->has_permit) || !empty($item->permit_file))
                <i class='bx bx-check op-check' title="Confirmed"></i>
                @if(!empty($item->permit_file))
                  <a href="{{ Storage::url($item->permit_file) }}" target="_blank" style="margin-left:6px;font-size:12px;">View</a>
                @endif
              @else
                {{-- 上传 Permit（提交到 admin.installation.permit.upload） --}}
                <form id="permit-form-{{ $rowId }}" action="{{ route('admin.installation.permit.upload', $item->id) }}" method="POST" enctype="multipart/form-data" style="display:inline;">
                  @csrf
                  <input type="file" name="permit_file" id="permit-file-{{ $rowId }}" accept=".pdf,.jpg,.jpeg,.png" style="display:none">
                  <i class='bx bx-upload op-upload' title="Upload Permit" onclick="document.getElementById('permit-file-{{ $rowId }}').click()"></i>
                </form>
                <script>
                  (function(){
                    const input = document.getElementById('permit-file-{{ $rowId }}');
                    if (!input) return;
                    input.addEventListener('change', function(){
                      if (this.files && this.files.length > 0) {
                        document.getElementById('permit-form-{{ $rowId }}').submit();
                      }
                    });
                  })();
                </script>
              @endif
            </td>
            <td class="action">
              <a href="{{ route('admin.fulfillment.show', $item->id ?? 0) }}"><i class='bx bx-show-alt' title="View"></i></a>
              <a href="{{ route('admin.fulfillment.edit', $item->id ?? 0) }}"><i class='bx bx-edit' title="Edit"></i></a>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align:center;color:#999;">No records found.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="pagination">
      {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
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

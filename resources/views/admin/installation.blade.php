@extends('layouts.app')
@section('title','Delivery & Installation Overview')

@section('content')
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<style>
:root{
  --bg:#F6F8FC; --card:#fff; --border:#E5E7EB; --text:#0F172A; --muted:#64748B;
  --shadow:0 8px 24px rgba(15,23,42,.06); --primary:#5956E9; --r:16px;
}
body{background:var(--bg);}
.page{max-width:1200px;margin:0 auto;padding:24px;}
.h1{font-weight:800;font-size:32px;color:var(--text);margin:10px 0 18px;}

/* Filter bar */
.filterbar{
  background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);
  padding:16px;display:grid;grid-template-columns:1fr 1fr 1fr 220px 220px;gap:12px;align-items:center;margin-bottom:16px;
}
.filterbar .span-3{grid-column:1 / span 3;}
.filterbar .btn-wrap{grid-column:4 / span 2;display:flex;gap:14px;justify-content:space-between;}
.fb-input,.fb-select{
  height:48px;border:1px solid var(--border);border-radius:14px;background:#fff;outline:0;padding:0 16px;font-size:15px;color:var(--text);width:100%;
}
.fb-input::placeholder{color:#9AA4B2;}
.fb-btn{height:48px;border-radius:14px;border:1px solid var(--border);background:#fff;font-weight:800;padding:0 24px;cursor:pointer;display:flex;align-items:center;gap:10px;justify-content:center;}
.fb-btn-primary{background:var(--primary);border-color:var(--primary);color:#fff;box-shadow:0 10px 18px rgba(89,86,233,.25);}
.fb-btn-reset{background:#F4F3FF;color:var(--primary);border-color:#ECEBFF;}
.fb-btn-export{background:#fff;color:#0F172A;}

/* Table */
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;}
.table{width:100%;border-collapse:separate;border-spacing:0}
.table thead th{
  position:sticky; top:0; z-index:1;
  background:#F9FAFB;
  font-size:12px;text-transform:uppercase;color:#64748B;letter-spacing:.04em;padding:12px 16px;border-bottom:1px solid var(--border);text-align:left
}
.table tbody td{padding:14px 16px;border-bottom:1px solid var(--border);color:#0F172A}
.pill{display:inline-block;padding:.2rem .55rem;border-radius:999px;background:#EEF2FF;color:#4F46E5;font-size:.75rem;font-weight:700}
.badge{display:inline-block;padding:.2rem .55rem;border-radius:999px;font-size:.75rem;font-weight:700}
.badge.orange{background:#FFF7ED;color:#C2410C}
.badge.green{background:#ECFDF5;color:#067647}
.badge.blue{background:#E0F2FE;color:#075985}
.i16{font-size:18px;vertical-align:middle}
.op-upload{color:#C2410C}
.op-check{color:#067647}
.action i{font-size:18px;margin:0 6px;color:#64748B;cursor:pointer}
.action i:hover{color:#111827}

/* Pagination */
.pagination{display:flex;gap:6px;justify-content:flex-end;padding:12px}
.pagination a, .pagination span{padding:6px 10px;border:1px solid var(--border);border-radius:10px;background:#fff;color:#111827;text-decoration:none}
.pagination .active span{background:var(--primary);border-color:var(--primary);color:#fff}

/* Responsive */
@media (max-width:1200px){
  .filterbar{grid-template-columns:1fr 1fr 1fr 180px 180px;}
}
@media (max-width:1100px){
  .filterbar{grid-template-columns:1fr 1fr;}
  .filterbar .span-3{grid-column:1 / span 2;}
  .filterbar .btn-wrap{grid-column:1 / span 2;justify-content:flex-start;}
}
</style>

<div class="page">
  <div class="h1">Delivery & Installation Overview</div>

  {{-- 过滤表单（GET） --}}
  <form method="GET" action="{{ route('admin.installation') }}" id="filterForm">
    <div class="filterbar">
      <!-- 第一排 -->
      <input type="text" class="fb-input" name="q" value="{{ request('q') }}" placeholder="Search by Order ID or Job Title">
      <input type="text" class="fb-input" name="artist" value="{{ request('artist') }}" placeholder="Search artist name...">
      <input type="text" class="fb-input" name="details" value="{{ request('details') }}" placeholder="Search orders or product details">
      <select class="fb-select" name="task_type">
        @php $tt = request('task_type','all'); @endphp
        <option value="all" {{ $tt==='all'?'selected':'' }}>All Task Types</option>
        <option value="Installation" {{ $tt==='Installation'?'selected':'' }}>Installation</option>
        <option value="Self Pick up" {{ $tt==='Self Pick up'?'selected':'' }}>Self Pick up</option>
        <option value="Courier" {{ $tt==='Courier'?'selected':'' }}>Courier</option>
      </select>
      <select class="fb-select" name="status">
        @php $st = request('status','all'); @endphp
        <option value="all" {{ $st==='all'?'selected':'' }}>All statuses</option>
        <option value="Pending Permit" {{ $st==='Pending Permit'?'selected':'' }}>Pending Permit</option>
        <option value="In Progress" {{ $st==='In Progress'?'selected':'' }}>In Progress</option>
        <option value="Completed" {{ $st==='Completed'?'selected':'' }}>Completed</option>
      </select>

      <!-- 第二排 -->
      <input type="text" class="fb-input span-3" id="date_range" name="date_range"
             value="{{ request('date_range') }}" placeholder="Select date range (dd/mm/yyyy - dd/mm/yyyy)">
      <div class="btn-wrap">
        <button class="fb-btn fb-btn-primary" type="submit">Filter</button>

        {{-- Reset：跳到无参数的路由，彻底清空 --}}
        <a class="fb-btn fb-btn-reset" href="{{ route('admin.installation') }}" id="btnReset">Reset</a>

        {{-- Export：用隐藏表单携带当前筛选并在新标签打开 --}}
        <button type="button" class="fb-btn fb-btn-export" id="btnExport">
          <i class='bx bx-export'></i> Export
        </button>
      </div>
    </div>
  </form>

  {{-- 导出隐藏表单（target="_blank" 新标签） --}}
  <form method="GET" action="{{ route('admin.installation.export') }}" id="exportForm" target="_blank" style="display:none;">
    <input type="hidden" name="q" value="{{ request('q') }}">
    <input type="hidden" name="artist" value="{{ request('artist') }}">
    <input type="hidden" name="details" value="{{ request('details') }}">
    <input type="hidden" name="task_type" value="{{ request('task_type','all') }}">
    <input type="hidden" name="status" value="{{ request('status','all') }}">
    <input type="hidden" name="date_range" value="{{ request('date_range') }}">
  </form>

  {{-- 表格 --}}
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Product ID</th>
          <th>Product Name</th>
          <th>Task Type</th>
          <th>Deadline</th>
          <th>Status</th>
          <th>Permit / Confirm</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $item)
          @php $status = $item->status; @endphp
          <tr>
            <td>{{ $item->order_id }}</td>
            <td>{{ $item->product_name }}</td>
            <td><span class="pill">{{ $item->task_type }}</span></td>
            <td>{{ optional($item->deadline)->format('Y-m-d') }}</td>
            <td>
              @if($status === 'Pending Permit')
                <span class="badge orange">Pending Permit</span>
              @elseif($status === 'Completed')
                <span class="badge green">Completed</span>
              @else
                <span class="badge blue">{{ $status }}</span>
              @endif
            </td>
            <td>
              @if($status === 'Pending Permit')
                <i class='bx bx-upload i16 op-upload' title="Upload Permit"></i>
              @elseif($status === 'Completed')
                <i class='bx bx-check i16 op-check' title="Confirmed"></i>
              @else
                <i class='bx bx-dots-horizontal-rounded i16' title="N/A"></i>
              @endif
            </td>
            <td class="action">
              <i class='bx bx-show-alt' title="View"></i>
              <i class='bx bx-edit' title="Edit"></i>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" style="color:#64748B;">No records found.</td></tr>
        @endforelse
      </tbody>
    </table>

    {{-- 分页 --}}
    <div class="pagination">
      {{ $rows->onEachSide(1)->links() }}
    </div>
  </div>
</div>

{{-- 交互逻辑：导出携带当前筛选；日期范围初始化（可选） --}}
<script>
  // 同步当前筛选到隐藏导出表单
  document.getElementById('btnExport').addEventListener('click', function(){
    const f = document.getElementById('filterForm');
    const e = document.getElementById('exportForm');
    ['q','artist','details','task_type','status','date_range'].forEach(name=>{
      const src = f.querySelector(`[name="${name}"]`);
      const dst = e.querySelector(`[name="${name}"]`);
      if(src && dst){ dst.value = src.value; }
    });
    e.submit();
  });

  // （可选）Flatpickr 绑定：请先在布局中引入 flatpickr 的 js/css
  if (window.flatpickr) {
    flatpickr('#date_range', {
      mode: 'range',
      dateFormat: 'd/m/Y',
      allowInput: true
    });
  }
</script>
@endsection

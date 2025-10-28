@extends('layouts.app')

@section('title','Job Order Overview')

@section('content')
{{-- Icons & Datepicker --}}
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
:root{
  --bg:#F6F8FC; --card:#fff; --border:#E5E7EB; --text:#0F172A; --muted:#64748B;
  --shadow:0 4px 14px rgba(17,24,39,.06); --r:16px; --primary:#4F46E5;
  --ctl-h:44px; --gap:12px;
}
body{background:var(--bg);}
.page-h1{color:var(--text);font-weight:800;}
.sub{color:var(--muted);}

/* ===== Metrics ===== */
.metrics-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--gap);}
.metric{background:var(--card);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);
        display:flex;align-items:center;padding:14px 16px;min-height:78px;}
.metric .pill{width:44px;height:44px;border-radius:999px;display:flex;align-items:center;justify-content:center;
              font-size:20px;box-shadow:var(--shadow);}
.metric .txt{margin-left:12px}
.metric .label{font-size:12px;color:#6B7280}
.metric .value{font-size:22px;font-weight:800;color:var(--text)}
.metric.color-indigo .pill{background:#EEF2FF;color:#4F46E5}
.metric.color-amber  .pill{background:#FFF7E6;color:#B45309}
.metric.color-cyan   .pill{background:#E0F2FE;color:#0369A1}
.metric.color-rose   .pill{background:#FFE4E6;color:#E11D48}

/* ===== Toolbar ===== */
.toolbar{background:#fff;border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:12px}
.toolbar-grid{display:grid;grid-template-columns:repeat(12,1fr);grid-auto-rows:minmax(var(--ctl-h),auto);gap:var(--gap);align-items:center}
.cell{display:flex;align-items:center;width:100%;height:var(--ctl-h)}

.tool-chip{display:flex;align-items:center;gap:8px;height:100%;border:1px solid var(--border);border-radius:10px;background:#fff;padding:0 12px;width:100%}
.tool-chip i{color:#64748B}
.tool-chip input{border:0;outline:0;width:100%;height:calc(100% - 2px);line-height:1}

.tool-btn{display:inline-flex;align-items:center;justify-content:center;height:100%;width:100%;
  border:1px solid var(--border);border-radius:10px;background:#fff;font-weight:600;text-decoration:none;color:var(--text)}
.tool-btn.primary{background:var(--primary);border-color:var(--primary);color:#fff}
.tool-btn.link{background:#F3F4F6;color:#4F46E5;border:1px solid #E5E7EB}
.tool-btn.link:hover{background:#E0E7FF;color:#4338CA}

.tool-select{height:100%;width:100%;border:1px solid var(--border);border-radius:10px;background:#fff;padding:0 12px}

/* ===== Table ===== */
.table-card{background:#fff;border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow)}
.table-card .hd{padding:14px 16px;border-bottom:1px solid var(--border);font-weight:700;color:var(--text)}
.table-card table{width:100%;border-collapse:separate;border-spacing:0}
thead th{font-size:12px;color:#6B7280;text-transform:uppercase;background:#F8FAFC;padding:12px 14px;border-bottom:1px solid var(--border)}
tbody td{padding:14px;border-bottom:1px solid var(--border)}
tbody tr:hover{background:#FAFAFB}

/* ✅ Actions (only View, minimalist gray style) */
.actions{
  display:flex;
  justify-content:flex-end;
}
.actions a{
  width:34px;
  height:34px;
  display:grid;
  place-items:center;
  border:none;
  background:transparent;
  padding:0;
}
.actions a i{
  font-size:18px;
  color:#3180e7ff; /* gray */
  transition:color .2s ease, transform .2s ease;
}
.actions a:hover i{
  color:#475569;
  transform:scale(1.15);
}
.actions a:active i{
  color:#334155;
}

/* ===== Badges ===== */
.badge-soft{font-weight:700;border-radius:999px;padding:6px 10px;font-size:12px}
.soft-pending{background:#F3F4F6;color:#374151}
.soft-progress{background:#EEF2FF;color:#4F46E5}
.soft-complete{background:#ECFDF5;color:#059669}
.soft-reject{background:#FFF1F2;color:#E11D48}

/* ===== Pagination ===== */
.pager-wrap{display:flex;justify-content:space-between;align-items:center;padding:12px 16px}
.pager-left{color:var(--muted);font-size:.9rem}
.pager-right nav{display:block}
.pager-right .pagination{margin:0;display:flex;gap:8px}
.pager-right .page-item .page-link{border:1px solid var(--border);background:#fff;color:#475569;border-radius:10px;padding:.45rem .7rem;min-width:36px;text-align:center}
.pager-right .page-item.active .page-link{background:var(--primary);border-color:var(--primary);color:#fff}
.pager-right .page-item.disabled .page-link{background:#F1F5F9;color:#94A3B8;border-color:#E2E8F0}

/* Responsive */
@media (max-width:992px){.metrics-grid{grid-template-columns:repeat(2,1fr)} .toolbar-grid{grid-template-columns:repeat(6,1fr)}}
@media (max-width:576px){.metrics-grid{grid-template-columns:1fr} .toolbar-grid{grid-template-columns:repeat(4,1fr)}}
</style>

<div class="container-fluid">
  <div class="row mb-2">
    <div class="col">
      <h3 class="page-h1">Job Order Overview</h3>
    </div>
  </div>

  {{-- ===== Metrics ===== --}}
  <div class="metrics-grid mb-3">
    <div class="metric color-indigo">
      <div class="pill"><i class='bx bx-puzzle'></i></div>
      <div class="txt"><div class="label">Total Orders</div><div class="value">{{ $totalOrders ?? 0 }}</div></div>
    </div>
    <div class="metric color-amber">
      <div class="pill"><i class='bx bx-refresh'></i></div>
      <div class="txt"><div class="label">In Progress</div><div class="value">{{ $inProgress ?? 0 }}</div></div>
    </div>
    <div class="metric color-cyan">
      <div class="pill"><i class='bx bx-check'></i></div>
      <div class="txt"><div class="label">Completed</div><div class="value">{{ $completed ?? 0 }}</div></div>
    </div>
    <div class="metric color-rose">
      <div class="pill"><i class='bx bx-x'></i></div>
      <div class="txt"><div class="label">Rejected</div><div class="value">{{ $rejected ?? 0 }}</div></div>
    </div>
  </div>

  {{-- ===== Toolbar ===== --}}
  <form class="toolbar mb-3" method="GET" action="">
    <div class="toolbar-grid">
      <div style="grid-column:span 3">
        <div class="cell">
          <div class="tool-chip"><i class='bx bx-hash'></i>
            <input type="text" name="order_id" value="{{ request('order_id') }}" placeholder="Search Order ID">
          </div>
        </div>
      </div>
      <div style="grid-column:span 3">
        <div class="cell">
          <div class="tool-chip"><i class='bx bx-user'></i>
            <input type="text" name="artist" value="{{ request('artist') }}" placeholder="Search artist name...">
          </div>
        </div>
      </div>
      <div style="grid-column:span 4">
        <div class="cell">
          <div class="tool-chip"><i class='bx bx-search'></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search orders or product details">
          </div>
        </div>
      </div>
      <div style="grid-column:span 2">
        <div class="cell">
          @php $s = request('status'); @endphp
          <select class="tool-select" name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="in_progress" {{ $s==='in_progress'?'selected':'' }}>In Progress</option>
            <option value="completed"   {{ $s==='completed'?'selected':'' }}>Completed</option>
            <option value="rejected"    {{ $s==='rejected'?'selected':'' }}>Rejected</option>
          </select>
        </div>
      </div>

      <div style="grid-column:span 6">
        <div class="cell">
          <div class="tool-chip"><i class='bx bx-calendar'></i>
            <input type="text" id="dateRange" name="date_range"
                   value="{{ request('date_range') }}"
                   placeholder="Select date range (dd/mm/yyyy - dd/mm/yyyy)">
          </div>
        </div>
      </div>
      <div style="grid-column:span 2">
        <div class="cell"><button class="tool-btn primary" type="submit">Filter</button></div>
      </div>
      <div style="grid-column:span 2">
        <div class="cell"><a class="tool-btn link" href="{{ url()->current() }}">Reset</a></div>
      </div>
      <div style="grid-column:span 2">
        <div class="cell">
          <a class="tool-btn" href="{{ request()->fullUrlWithQuery(['export'=>1]) }}">
            <i class='bx bx-export me-1'></i>Export
          </a>
        </div>
      </div>
    </div>
  </form>

  {{-- ===== Table ===== --}}
  <div class="table-card">
    <div class="hd">Job Orders</div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th style="width:12%">Order ID</th>
            <th style="width:24%">Job Title</th>
            <th style="width:18%">Company</th>
            <th style="width:16%">Artist</th>
            <th style="width:14%">Status</th>
            <th style="width:10%">Deadline</th>
            <th style="width:6%">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $order)
            @php
              $label = $order->getStatusLabelAttribute();
              $badge = $label==='Completed'   ? 'badge-soft soft-complete' :
                       ($label==='In Progress'? 'badge-soft soft-progress' :
                       ($label==='Rejected'   ? 'badge-soft soft-reject'   : 'badge-soft soft-pending'));
            @endphp
            <tr>
              <td class="text-nowrap">#{{ $order->order_number }}</td>
              <td class="text-truncate" style="max-width:320px">{{ $order->orderTitle }}</td>
              <td>{{ $order->company?->name ?? '-' }}</td>
              <td>{{ $order->artist?->name ?? '-' }}</td>
              <td><span class="{{ $badge }}">{{ $label }}</span></td>
              <td class="text-nowrap">{{ $order->deadline ? $order->deadline->format('M d, Y') : '-' }}</td>
              <td class="actions">
                <a title="View" href="{{ url('admin/orders/'.$order->id) }}"><i class='bx bx-show-alt'></i></a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No job orders found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    <div class="pager-wrap">
      <div class="pager-left">
        Show
        <select id="perPageSelect"
                style="border:1px solid var(--border);border-radius:8px;height:34px;padding:4px 8px;">
          @php $pp=(int)request('per_page',10); @endphp
          @foreach([10,20,50] as $n)
            <option value="{{ $n }}" {{ $pp===$n?'selected':'' }}>{{ $n }}</option>
          @endforeach
        </select>
        entries
      </div>

      <div class="pager-right">
        {{ $orders->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  flatpickr("#dateRange", {
    mode: "range",
    dateFormat: "d/m/Y",
    allowInput: true,
    locale: { rangeSeparator: " - " }
  });

  const sel = document.getElementById('perPageSelect');
  if (sel){
    sel.addEventListener('change', function(){
      const url = new URL(window.location.href);
      const p = url.searchParams;
      p.set('per_page', sel.value);
      p.delete('page');
      window.location.href = url.toString();
    });
  }
});
</script>
@endsection

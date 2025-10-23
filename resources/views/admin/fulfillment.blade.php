@extends('layouts.app')
@section('title','Fulfillment Overview')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">

<style>
  :root{
    --bg:#F6F7FB; --card:#fff; --muted:#667085; --text:#0F172A;
    --border:#E5E7EB; --shadow:0 6px 18px rgba(16,24,40,.06);
    --primary:#5B55F6; --primary-600:#4B47E6;
    --success:#16A34A; --info:#2563EB; --warn:#F59E0B;
  }
  body{background:var(--bg)}
  .wrap{max-width:1200px;margin:0 auto;padding:22px}

  /* title */
  .h2{font-weight:800;color:var(--text);font-size:22px;margin:0 0 14px}

  /* metric cards */
  .metrics{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:16px}
  .metric{background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);padding:16px;display:flex;gap:12px;align-items:center}
  .metric .ic{width:42px;height:42px;border-radius:999px;background:#EEF2FF;display:grid;place-items:center}
  .metric .ic i{color:#334155;font-size:18px}
  .metric .caption{color:var(--muted);font-size:12px}
  .metric .num{font-weight:800;color:var(--text);font-size:20px}

  /* toolbar */
  .toolbar{background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);padding:16px;display:grid;gap:14px;margin-bottom:14px}
  .tb-row{display:grid;grid-template-columns:1.15fr 1.15fr 1.6fr .9fr .9fr;gap:12px}
  .tb-row2{display:grid;grid-template-columns:1.6fr .6fr .6fr .6fr;gap:12px}
  .control{height:44px;border:1px solid var(--border);border-radius:12px;padding:0 14px;background:#fff}
  .control::placeholder{color:#98A2B3}
  .btn{height:44px;border-radius:12px;border:1px solid transparent;font-weight:700}
  .btn-primary{background:var(--primary);color:#fff}
  .btn-primary:hover{background:var(--primary-600)}
  .btn-ghost{background:#F7F7FF;color:var(--primary);border:1px solid #E7E9FE}
  .btn-ghost:hover{background:#EEF0FF}
  .btn-outline{background:#fff;border:1px solid var(--border);color:#0F172A}
  .btn-outline i{margin-right:8px}

  /* table card */
  .card{background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow)}
  .card-hd{padding:12px 16px;border-bottom:1px solid var(--border);font-weight:700}
  .table-responsive{padding:10px 12px;overflow-x:visible;}

  table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 8px;
    table-layout:auto;
    font-size:14px;
  }
  thead th{
    font-size:12px;text-transform:uppercase;color:#8A94A6;letter-spacing:.04em;
    background:#F9FAFB;padding:10px 12px;white-space:nowrap;border-top:1px solid var(--border)
  }
  tbody tr{background:#fff;border:1px solid var(--border)}
  tbody td{
    padding:12px 10px;
    vertical-align:middle;
    word-break:break-word;
    white-space:normal;
  }
  tbody tr td:first-child{border-top-left-radius:10px;border-bottom-left-radius:10px}
  tbody tr td:last-child{border-top-right-radius:10px;border-bottom-right-radius:10px}

  /* col widths */
  .w-id{width:120px}
  .w-name{width:240px}
  .w-task{width:120px}
  .w-dead{width:120px}
  .w-status{width:130px}
  .w-date{width:120px}
  .w-loc{width:260px}
  .w-inst{width:130px}
  .w-cost{width:90px}
  .w-act{width:96px}
  .nowrap{white-space:nowrap}

  /* badge */
  .badge{padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;display:inline-block}
  .bd-green{background:#ECFDF5;border:1px solid #A7F3D0;color:#16A34A}
  .bd-blue{background:#EFF6FF;border:1px solid #BFDBFE;color:#2563EB}
  .bd-warn{background:#FFF7ED;border:1px solid #FED7AA;color:#EA580C}

  /* ✅ Action buttons */
  .actions{display:flex;justify-content:flex-end;gap:8px}
  .act{
    width:36px;height:36px;
    border:1px solid var(--border);
    border-radius:10px;
    display:grid;
    place-items:center;
    background:#fff;
    transition:all .2s ease;
  }
  .act i{
    font-size:16px;           /* ✅ 图标缩小 */
    color:var(--primary);
  }
  .act:hover{
    background:#F8FAFC;
    transform:scale(1.05);
  }

  /* footer */
  .table-ft{display:flex;justify-content:space-between;align-items:center;padding:10px 12px 14px;border-top:1px solid var(--border)}
  .muted{color:#8A94A6;font-size:12px}
  .select{height:34px;border:1px solid var(--border);border-radius:10px;padding:0 8px;background:#fff}
  .pagination{display:flex;gap:6px}
  .pg{min-width:34px;height:34px;border:1px solid var(--border);border-radius:10px;display:grid;place-items:center;background:#fff}
  .pg.active{background:var(--primary);color:#fff;border-color:var(--primary)}

  @media (max-width: 992px){
    .metrics{grid-template-columns:1fr}
    .tb-row{grid-template-columns:1fr}
    .tb-row2{grid-template-columns:1fr 1fr}
  }
</style>

<div class="wrap">
  <h2 class="h2">Fulfillment Overview</h2>

  {{-- Metrics --}}
  <div class="metrics">
    <div class="metric">
      <div class="ic"><i class='bx bx-file-find'></i></div>
      <div>
        <div class="caption">Delivery Needing Permit Upload</div>
        <div class="num">{{ $needPermit }}</div>
      </div>
    </div>
    <div class="metric">
      <div class="ic"><i class='bx bx-send'></i></div>
      <div>
        <div class="caption">Dispatch Control Overview</div>
        <div class="num">{{ $dispatchCount }}</div>
      </div>
    </div>
  </div>

  {{-- Toolbar --}}
  <form class="toolbar" method="get" action="{{ route('admin.fulfillment') }}">
    <div class="tb-row">
      <input class="control" name="order_id" value="{{ request('order_id') }}" placeholder="Search by Order ID or Job Title">
      <input class="control" name="artist" value="{{ request('artist') }}" placeholder="Search artist name...">
      <input class="control" name="q" value="{{ request('q') }}" placeholder="Search orders or product details">
      <select class="control" name="task">
        <option value="">All Task Types</option>
        <option value="delivery" {{ request('task')==='delivery'?'selected':'' }}>Delivery</option>
        <option value="installation" {{ request('task')==='installation'?'selected':'' }}>Installation</option>
      </select>
      <select class="control" name="status">
        <option value="">All statuses</option>
        @foreach(['pending'=>'Pending','in_progress'=>'In Progress','completed'=>'Completed'] as $k=>$v)
          <option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div class="tb-row2">
      <input id="dateRange" class="control" name="date_range" value="{{ request('date_range') }}" placeholder="Select date range (dd/mm/yyyy - dd/mm/yyyy)">
      <button class="btn btn-primary" type="submit">Filter</button>
      <a class="btn btn-ghost" href="{{ route('admin.fulfillment') }}">Reset</a>
      <a class="btn btn-outline" href="{{ route('admin.fulfillment', array_merge(request()->all(), ['export'=>1])) }}">
        <i class='bx bx-export'></i> Export
      </a>
    </div>
  </form>

  {{-- Table --}}
  <div class="card">
    <div class="card-hd">Products / Tasks</div>
    <div class="table-responsive">
      <table>
        <thead>
        <tr>
          <th class="w-id nowrap">Product ID</th>
          <th class="w-name">Product Name</th>
          <th class="w-task">Task Type</th>
          <th class="w-dead nowrap">Deadline</th>
          <th class="w-status">Status</th>
          <th class="w-date nowrap">Delivery Date</th>
          <th class="w-loc">Delivery Location</th>
          <th class="w-inst">Installation Type</th>
          <th class="w-cost nowrap">Cost</th>
          <th class="w-act nowrap" style="text-align:right">Action</th>
        </tr>
        </thead>
        <tbody>
        @forelse($fulfillments as $row)
          <tr>
            <td class="w-id nowrap">{{ $row->product_code }}</td>
            <td class="w-name">{{ $row->product_name ?? '-' }}</td>
            <td class="w-task">{{ $row->task_type_label }}</td>
            <td class="w-dead nowrap">{{ $row->deadline ? \Carbon\Carbon::parse($row->deadline)->format('Y-m-d') : '–' }}</td>
            <td class="w-status">
              @php
                $s=$row->status;
                $cls = $s==='completed'?'bd-green':($s==='in_progress'?'bd-blue':'bd-warn');
                $txt = ucfirst(str_replace('_',' ',$s));
              @endphp
              <span class="badge {{ $cls }}">{{ $txt }}</span>
            </td>
            <td class="w-date nowrap">{{ $row->delivery_date ? \Carbon\Carbon::parse($row->delivery_date)->format('Y-m-d') : '–' }}</td>
            <td class="w-loc">{{ $row->delivery_location ?? '–' }}</td>
            <td class="w-inst">{{ $row->installation_type ?? 'Inhouse' }}</td>
            <td class="w-cost nowrap">{{ isset($row->cost)?'RM '.number_format($row->cost,0):'N/A' }}</td>
            <td class="w-act nowrap" style="text-align:right">
              <div class="actions">
                <a class="act" href="{{ route('admin.fulfillment.show',$row->id) }}" title="View"><i class='bx bx-show'></i></a>
                <a class="act" href="{{ route('admin.fulfillment.edit',$row->id) }}" title="Edit"><i class='bx bx-edit'></i></a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="muted" style="padding:26px">No results found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="table-ft">
      <div class="muted">
        <form id="perPageForm" method="get">
          @foreach(request()->except('per_page','page') as $k=>$v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
          @endforeach
          Show
          <select class="select" name="per_page" onchange="document.getElementById('perPageForm').submit()">
            @foreach([10,20,50] as $n)
              <option value="{{ $n }}" {{ (int)request('per_page',10)===$n?'selected':'' }}>{{ $n }}</option>
            @endforeach
          </select>
          entries
        </form>
      </div>
      <div class="pagination">
        @if ($fulfillments->onFirstPage())
          <div class="pg">Prev</div>
        @else
          <a class="pg" href="{{ $fulfillments->previousPageUrl() }}">&laquo;</a>
        @endif
        @foreach ($fulfillments->getUrlRange(1, $fulfillments->lastPage()) as $page => $url)
          <a class="pg {{ $page==$fulfillments->currentPage()?'active':'' }}" href="{{ $url }}">{{ $page }}</a>
        @endforeach
        @if ($fulfillments->hasMorePages())
          <a class="pg" href="{{ $fulfillments->nextPageUrl() }}">&raquo;</a>
        @else
          <div class="pg">Next</div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- 日期区间 --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
  $(function () {
    const $dr = $('#dateRange');
    if ($dr.length){
      $dr.daterangepicker({
        autoUpdateInput: !!$dr.val(),
        locale: { format: 'DD/MM/YYYY', cancelLabel: 'Clear' }
      });
      $dr.on('cancel.daterangepicker', function(){ $(this).val(''); });
    }
  });
</script>
@endsection

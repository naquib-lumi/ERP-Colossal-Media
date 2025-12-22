@extends('layouts.app')

@section('title','Data Management')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .x-dialog{
    width:min(800px,95vw);
    background:#fff;
    border:1px solid var(--border);
    border-radius:16px;
    box-shadow:var(--shadow);
    display:flex;
    flex-direction:column;
    max-height:90vh;
  }
  .x-ttl{
    flex:1;
    font-weight:700;
    color:var(--text);
  }
  .dropdown-menu{
    position:absolute;
    right:0;
    top:36px;
    background:#fff;
    border:1px solid var(--border);
    border-radius:12px;
    box-shadow:var(--shadow);
    display:none;
    min-width:180px;
    z-index:40;
  }
  .dropdown-item{
    display:block;
    width:100%;
    text-align:left;
    border:0;
    background:#fff;
    padding:10px 12px;
    font-size:14px;
    color:#111827;
    cursor:pointer;
  }
  .dropdown-item:hover{
    background:#F3F4F6;
  }
  .badge-redo {
    display:inline-block;
    padding: .15rem .45rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 600;
    background: #fff7ed;   /* warm amber */
    color: #b45309;
    vertical-align: middle;
  }
  :root{
    --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB; --thead:#F9FAFB;
    --text:#101828; --muted:#667085; --chip:#F2F4F7;
    --shadow:0 3px 10px rgba(16,24,40,.06);
    --primary:#3B82F6; --primary-600:#2563EB;
    --dark:#111827; --dark-700:#0B1220;
  }
  body{background:var(--bg)}
  .page-wrap{max-width:1200px;margin:0 auto;padding:20px}
  .card{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow)}
  .card-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--border)}
  .title{font-size:28px;font-weight:700;color:var(--text)}
  .actions{display:flex;gap:10px}
  .btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--border);background:#fff;padding:8px 12px;border-radius:10px;font-weight:600;cursor:pointer}
  .btn i{font-size:16px}
  .btn-primary{background:var(--primary);color:#fff;border-color:var(--primary)}
  .btn-dark{background:var(--dark);color:#fff;border-color:var(--dark)}
  .btn-ghost{background:#fff;color:var(--text)}
  .toolbar{display:flex;gap:12px;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border)}
  .search{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);border-radius:10px;padding:8px 10px;width:100%}
  .search input{border:0;outline:0;width:100%}
  .select select{border:1px solid var(--border);border-radius:10px;padding:8px 10px;background:#fff}
  .select {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .table-wrap{overflow:auto}
  table{width:100%;border-collapse:separate;border-spacing:0 8px}
  thead th{font-size:12px;letter-spacing:.04em;color:var(--muted);text-transform:uppercase;text-align:left;padding:8px 10px}
  tbody tr{background:#fff;box-shadow:var(--shadow)}
  tbody td{padding:14px 10px;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
  tbody td:first-child{border-left:1px solid var(--border);border-radius:12px 0 0 12px}
  tbody td:last-child{border-right:1px solid var(--border);border-radius:0 12px 12px 0}
  .chip{display:inline-flex;align-items:center;gap:6px;background:var(--chip);color:var(--muted);border-radius:999px;padding:6px 10px;font-size:12px}
  .kebab{border:1px solid var(--border);background:#fff;border-radius:10px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer}
  .count{font-size:12px;color:var(--muted)}
  /* Tabs */
  .tabs{display:flex;gap:24px;padding:6px 2px 0}
  .tab-btn{appearance:none;background:none;border:0;padding:10px 2px;font-weight:700;color:var(--muted);cursor:pointer;position:relative}
  .tab-btn.active{color:var(--text)}
  .tab-btn.active::after{content:"";position:absolute;left:0;right:0;bottom:-8px;height:3px;background:var(--dark);border-radius:99px}
  .tabs-border{height:1px;background:var(--border);margin-bottom:16px}
  .tab-panel{display:none}
  .tab-panel.active{display:block}
  /* Modal */
  .x-mask{position:fixed;inset:0;background:rgba(2,6,23,.5);display:none;align-items:center;justify-content:center;padding:20px;z-index:50}
  .x-mask.show{display:flex}
  .x{width:min(680px,95vw);background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);display:flex;flex-direction:column;max-height:90vh}
  .x-hd{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;font-weight:700}
  .x-bd{padding:16px;overflow:auto}
  .x-ft{padding:14px 16px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
  .field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
  .label{font-weight:700;color:var(--text)}
  .control{border:1px solid var(--border);border-radius:10px;padding:8px 10px}

  /* ===================== Another Data (original design) ===================== */
  .another-head{
    display:flex; align-items:center; justify-content:space-between;
    margin:12px 18px 10px;
  }
  .another-title{
    font-size:26px; font-weight:800; letter-spacing:.2px; color:#0f172a;
    margin:0;
  }
  .another-controls{ display:flex; gap:12px; }
  .ad-input, .ad-select{
    height:44px; border:1px solid #E6E9EF; border-radius:12px; background:#fff; outline:none;
    font-size:14px; color:#0f172a;
  }
  .ad-input{ width:320px; padding:0 14px; }
  .ad-select{ padding:0 40px 0 14px; min-width:150px; appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpath fill='%23667085' d='M4.47 6.97a.75.75 0 0 1 1.06 0L8 9.44l2.47-2.47a.75.75 0 0 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 0-1.06Z'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 12px center;
  }

  /* big rounded card + soft header */
  .ad-card{
    margin:0 18px 12px; background:#fff; border:1px solid #E6E9EF;
    border-radius:16px; box-shadow:0 1px 0 rgba(16,24,40,.03); overflow:hidden;
  }
  .ad-table{ width:100%; border-collapse:separate; border-spacing:0; }
  .ad-table thead th{
    background:#F3F6FA;
    color:#475467; font-weight:700; text-align:left;
    padding:16px 18px; letter-spacing:.02em; border-bottom:1px solid #EEF2F7;
  }
  .ad-table tbody td{
    padding:18px; color:#0f172a; border-top:1px solid #F1F5F9;
  }
  .ad-table tbody tr:hover{ background:#FAFAFB; }

  /* force right aligned numerics */
  .ad-table th.ad-num,
  .ad-table td.ad-num{
    text-align:right !important;
    white-space:nowrap;
  }

  .ad-actions{ text-align:center; width:80px; min-width:80px; }
  .ad-qty-link{ color:#2563eb; font-weight:600; text-decoration:none; }
  .ad-eye{ display:inline-flex; width:36px; height:36px; border-radius:9999px; align-items:center; justify-content:center;
    border:1px solid #E6E9EF; background:#fff; }
  .ad-eye .bi{ font-size:18px; color:#111827; }

  /* footer & pager */
  .ad-foot{
    display:flex; align-items:center; justify-content:flex-end;
    margin:10px 18px 0;
  }
  .ad-range{ color:#6b7280; font-size:14px; }
  .ad-pager{ display:flex; gap:8px; align-items:center;}
  .ad-page, .ad-nav{
    min-width:36px; height:36px; border:1px solid #E6E9EF; background:#fff; color:#0f172a;
    border-radius:10px; display:grid; place-items:center; padding:0 10px; cursor:pointer;
  }
  .ad-page.active{ background:#0f172a; color:#fff; border-color:#0f172a; }
  .ad-nav{ width:36px; }
</style>

<div class="page-wrap">
  <div class="d-flex align-items-center justify-content-between">
    <h1 class="title">Data Management</h1>
  </div>
  @php $activeTab = $activeTab ?? request('tab', 'cost')@endphp
  <!-- Tabs -->
  <div class="tabs">
    <a href="{{ route('boss.datamanagement', ['tab' => 'cost']) }}"
      class="tab-btn {{ $activeTab === 'cost' ? 'active' : '' }}"
      data-target="#costData">Material Cost</a>

    <a href="{{ route('boss.datamanagement', ['tab' => 'another']) }}"
      class="tab-btn {{ $activeTab === 'another' ? 'active' : '' }}"
      data-target="#anotherData">Order Costing</a>
  </div>
  <div class="tabs-border"></div>

<div class="tab-panel {{ ($activeTab === 'cost') ? 'active' : '' }}" id="costData">
    <div class="card">
      <div class="card-hd">
        <div>
          <div class="title" style="font-size: 20px !important;">Material Cost</div>
        </div>
        <div class="actions">
          <button id="btnManageTypes" class="btn btn-primary"><i class="bi bi-list"></i> Manage Types</button>
          <button id="btnAddMaterial" class="btn btn-dark"><i class="bi bi-plus-lg"></i> Add Material</button>
        </div>
      </div>

      <div class="toolbar">
        <form method="GET" action="{{ route('boss.datamanagement') }}" class="toolbar" style="border:0;padding:0;width: 100%;" >
          <input type="hidden" name="tab" value="cost">

          <div class="search">
            <i class="bi bi-search"></i>
            <input
              name="q"
              type="search"
              value="{{ request('q','') }}"
              placeholder="Search materials..."
              onkeydown="if(event.key==='Enter'){ this.form.submit(); }"
            >
          </div>

          <div class="select">
            <select name="type" onchange="this.form.submit()">
              <option value="all">All Material Types</option>
              @foreach($types as $type)
                <option value="{{ $type->id }}" {{ request('type','all') == $type->id ? 'selected' : '' }}>
                  {{ $type->name }}
                </option>
              @endforeach
            </select>
          </div>
        </form>
      </div>

      <div class="table-wrap" style="padding:6px 12px 10px;">
        <table id="tbl">
          <thead>
            <tr>
              <th style="font-weight: bold;">Material Name</th>
              <th style="font-weight: bold;">Material Type</th>
              <th style="font-weight: bold;">Unit Cost / sq inch (RM)</th>
              <th style="font-weight: bold;">Used Quantity</th>
              <th style="font-weight: bold;">Total Cost</th> 
              <th style="font-weight: bold;">Status</th>
              <th style="text-align:right; font-weight:bold">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($materials as $m)
              <tr data-type="{{ $m->material_type_id }}" data-uom="{{ $m->unit_id }}">
                <td>{{ $m->materialName }}</td>
                <td>{{ optional($m->materialType)->name }}</td>
                <td>RM {{ number_format($m->unitCost, 4) }} / SQ INCH</td>
                <td class="usedQty">
                  {{ number_format((float)($m->used_quantity ?? 0)) }}
                </td>
                <td class="totalCost">
                  RM {{ number_format((float)($m->total_cost ?? 0), 2) }}
                </td>

                {{-- NEW: Status column --}}
                <td class="mat-status">
                  {{ $m->active ? 'Active' : 'Inactive' }}
                </td>

                <td class="text-end" style="display:flex;justify-content:end;position:relative">
                  <button class="kebab" title="Actions" data-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu ad-menu" style="position:absolute;right:0;top:40px;background:#fff;border:1px solid var(--border);border-radius:10px;min-width:180px;padding:6px">
                    <button 
                      class="dropdown-item btnEdit"
                      data-id="{{ $m->MaterialID }}"
                      data-name="{{ $m->materialName }}"
                      data-cost="{{ $m->unitCost }}"
                      data-type-id="{{ $m->material_type_id }}"
                      data-type-name="{{ optional($m->materialType)->name }}"
                    >
                      <i class="bi bi-pencil me-2"></i> Edit Unit Cost
                    </button>

                    {{-- NEW: Activate / Deactivate instead of Delete --}}
                    <button
                      class="dropdown-item btnToggleMaterial"
                      data-id="{{ $m->MaterialID }}"
                      data-active="{{ (int) $m->active }}"
                    >
                      <i class="bi bi-toggle{{ $m->active ? 'on' : 'off' }} me-2"></i>
                      {{ $m->active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="mt-3 d-flex justify-content-between align-items-center px-2">
          <div class="count text-muted">
            Showing {{ $materials->firstItem() ?? 0 }}–{{ $materials->lastItem() ?? 0 }}
            of {{ $materials->total() }} materials
          </div>
          <div>
            {{ $materials
              ->withQueryString()
              ->appends(['tab' => 'cost'])
              ->links('pagination::bootstrap-5') }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Keep "Another Data" as-is (do not touch) -->
<div class="tab-panel {{ ($activeTab === 'another') ? 'active' : '' }}" id="anotherData">
  <!-- Card + table -->
  <div class="ad-card">
    <!-- Header: title left, controls right -->
  <div class="another-head">
    <div class="title" style="font-size: 20px !important;">Order Costing Data</div>
    <div class="another-controls">
      <form method="GET" action="{{ route('boss.datamanagement') }}" class="another-controls" id="adFilters">
        <input type="hidden" name="tab" value="another">

        {{-- 1) Search by Order ID --}}
        <input name="q_id" value="{{ $filters['q_id'] ?? request('q_id') }}"
              id="adSearchId" class="ad-input" type="search" placeholder="Order ID" style="max-width:120px">

        {{-- 2) Search by Title / Company / Product --}}
        <input name="q_text" value="{{ $filters['q_text'] ?? request('q_text') }}"
              id="adSearchText" class="ad-input" type="search" placeholder="Title, company, or product…" style="max-width:200px">

        {{-- 3) Status filter --}}
        @php $status = $filters['status'] ?? request('status','all'); @endphp
        <select name="status" id="adType" class="ad-select" aria-label="Status">
          <option value="all" {{ $status==='all'?'selected':'' }}>All Status</option>
          <option value="in_progress" {{ $status==='in_progress'?'selected':'' }}>In Progress</option>
          <option value="completed" {{ $status==='completed'?'selected':'' }}>Completed</option>
          <option value="rejected" {{ $status==='rejected'?'selected':'' }}>Rejected</option>
        </select>

        {{-- 4) Date range --}}
        @php $range = $filters['range'] ?? request('range','30'); @endphp
        <select name="range" id="adDate" class="ad-select" aria-label="Date range">
          <option value="30" {{ $range==='30'?'selected':'' }}>Last 30 Days</option>
          <option value="7"  {{ $range==='7'?'selected':''  }}>Last 7 Days</option>
          <option value="m"  {{ $range==='m'?'selected':''  }}>This Month</option>
          <option value="lm" {{ $range==='lm'?'selected':'' }}>Last Month</option>
          <option value="all"{{ $range==='all'?'selected':''}}>All Time</option>
        </select>

        <button class="btn btn-primary" type="submit">Apply</button>
        <button type="button" class="btn btn-ghost" id="adReset">Reset</button>
      </form>
    </div>
  </div>
    <table class="ad-table">
      @php
        $sort = $filters['sort'] ?? request('sort','date');
        $dir  = $filters['dir']  ?? request('dir','desc');
        $flip = $dir === 'asc' ? 'desc' : 'asc';
        $q = request()->except('page','orders_page');
        $link = function($key) use($q,$sort,$dir,$flip){
            return route('boss.datamanagement', array_merge($q, [
                'tab' => 'another',
                'sort'=> $key,
                'dir' => ($sort===$key ? $flip : 'desc'),
            ]));
        };
        $arrow = fn($key) => $sort===$key ? ($dir==='asc'?'▲':'▼') : '';
      @endphp
      <thead>
      <tr>
        <th>Order ID</th>
        <th>
          <a href="{{ $link('products') }}" class="sort-link">Product Quantity {{ $arrow('products') }}</a>
        </th>
        <th class="ad-num">
          <a href="{{ $link('used') }}" class="sort-link">Used Quantity {{ $arrow('used') }}</a>
        </th>
        <th class="ad-num">
          <a href="{{ $link('cost') }}" class="sort-link">Total Cost {{ $arrow('cost') }}</a>
        </th>
        <th class="ad-actions">Actions</th>
      </tr>
      </thead>
      <tbody id="adBody">
        @php
          // Only show orders that still have at least 1 non-printing product
          $visibleOrders = $orders->getCollection()->filter(function ($ord) {
              return (int)($ord->products_count ?? 0) > 0;
          });
        @endphp

        @forelse($visibleOrders as $ord)
          @php
            $baseNo = $ord->base_order_number
                      ?? $ord->order_number
                      ?? ('ORD-' . now()->format('Y') . '-' . str_pad((int)($ord->id ?? 0), 4, '0', STR_PAD_LEFT));
            $displayNo = '#' . ltrim($baseNo, '#');
            if (!empty($ord->is_redo)) {
                $displayNo .= 'R';
            }
            $showRedoBadge = ((int)($ord->status ?? 0) === 1);
          @endphp

          <tr
              id="job-{{ $ord->id }}"
              class="js-row-open"
              style="cursor:pointer;"
              data-href="{{ route('boss.orders.show', $ord->id) }}"
              data-code="{{ $displayNo }}"
              ondblclick="
                if (event.target.closest('a,button,input,select,textarea,label,svg,path,i')) return;
                window.location.href = this.dataset.href;
              "
            >
            <td>
              <span class="fw-semibold">{{ $displayNo }}</span>
              @if ($showRedoBadge)
                <span class="badge-redo ms-2">Rejected for REDO</span>
              @endif
            </td>
            <td>
              <a href="javascript:void(0)"      
                class="ad-qty-link"
                data-order-id="{{ $ord->id }}">
                {{ number_format((int)($ord->products_count ?? 0)) }} Products
              </a>
            </td>
            <td class="ad-num">{{ number_format((int)($ord->used_quantity ?? 0)) }}</td>
            <td class="ad-num">RM {{ number_format((float)($ord->total_cost ?? 0), 2) }}</td> 
            <td class="ad-actions">
              <a class="ad-eye" title="View" href="{{ route('boss.orders.show', $ord->id) }}"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="padding:20px; color:#667085;">No orders found.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="ad-foot">
      <div class="ad-pager">
        {{ $orders
          ->withQueryString()
          ->appends(['tab' => 'another'])
          ->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>

  <!-- Footer (range + pager) -->
  
</div>
</div>

<!-- ========== MODALS ========== -->
<!-- <div class="x-mask" id="mdlType">
  <div class="x">
    <div class="x-hd"><i class="bi bi-tags"></i> Add Material Type</div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Type Name *</div>
        <input id="typeName" type="text" class="control" placeholder="e.g., PVC, Board, Fabric">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlType">Cancel</button>
      <button class="btn btn-primary" id="btnSaveType">Save Type</button>
    </div>
  </div>
</div> -->

{{-- Manage Material Types modal --}}
<div id="mdlTypes" class="x-mask" aria-hidden="true">
  <div class="x-dialog" style="width:800px; max-width:90vw">
    <div class="x-hd">
      <div class="x-ttl">Manage Material Types</div>
      <button class="kebab" data-close="mdlTypes" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd" style="padding:0">
      <div class="toolbar" style="padding:14px 20px;border-bottom:1px solid var(--border);justify-content:flex-end">
        <button id="btnAddType" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Type</button>
      </div>
      <div style="overflow:auto;max-height:400px">
        <table id="tblTypes" style="width:100%;border-collapse:collapse">
          <thead>
            <tr>
              <th style="width:70%;padding:10px;text-align:left;">Material Type Name</th>
              <th style="width:15%;padding:10px;text-align:left;">Status</th>
              <th style="width:15%;padding:10px;text-align:left;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($types as $type)
              <tr class="{{ $type->active ? '' : 'inactive' }}">
                <td style="padding:10px;">{{ $type->name }}</td>
                <td style="padding:10px;">{{ $type->active ? 'Active' : 'Inactive' }}</td>
                <td style="padding:10px; text-align:right; position:relative">
                  <button class="kebab" data-toggle="dropdown" aria-expanded="false" title="Actions">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu">
                    <button class="dropdown-item btnEditType" data-id="{{ $type->id }}" data-name="{{ $type->name }}">
                      <i class="bi bi-pencil me-2"></i> Edit
                    </button>
                    <button class="dropdown-item btnToggleType" data-id="{{ $type->id }}" data-active="{{ $type->active }}">
                      <i class="bi bi-toggle{{ $type->active ? 'on' : 'off' }} me-2"></i> {{ $type->active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Small Add / Edit Type modal --}}
<div id="mdlType" class="x-mask" aria-hidden="true">
  <div class="x-dialog" role="dialog" aria-modal="true">
    <div class="x-hd">
      <div class="x-ttl" id="typeTitle">Add Material Type</div>
      <button class="kebab" data-close="mdlType" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Material Type Name</div>
        <input id="typeName" type="text" class="control" placeholder="e.g. Backlit Materials">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlType">Cancel</button>
      <button class="btn btn-dark" id="btnSaveType">Save</button>
    </div>
  </div>
</div>

<div class="x-mask" id="mdlMaterial">
  <div class="x">
    <div class="x-hd"><i class="bi bi-plus-square"></i> Add Material</div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Material Name *</div>
        <input id="matName" type="text" class="control" placeholder="e.g., PVC Banner, Foam Board">
      </div>
      <div class="field">
        <div class="label">Material Type *</div>
        <select id="matType" class="control">
          <option value="">Select type…</option>
          @foreach($types as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <div class="label">Unit Cost (RM) *</div>
        <input id="matCost" type="number" step="0.0001" class="control" placeholder="0.0000">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlMaterial">Cancel</button>
      <button class="btn btn-dark" id="btnSaveMaterial"><i class="bi bi-floppy2"></i> Save Material</button>
    </div>
  </div>
</div>

<div class="x-mask" id="mdlQuickEdit">
  <div class="x">
    <div class="x-hd">
      <i class="bi bi-pencil-square"></i>
      <span id="qeTitle">Edit Material</span>
    </div>

    <div class="x-bd">
      <input type="hidden" id="qeId">

      <div class="field">
        <div class="label">Material Name</div>
        <input id="qeName" type="text" class="control">
      </div>

      {{-- NEW: Material Type (only active types) --}}
      <div class="field">
        <div class="label">Material Type</div>
        <select id="qeType" class="control">
          @foreach($types->where('active', 1) as $t)
            <option value="{{ $t->id }}">{{ $t->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <div class="label">Current Unit Cost</div>
        <input id="qeCurrent" class="control" disabled>
      </div>

      <div class="field">
        <div class="label">New Unit Cost *</div>
        <input id="qeNew" type="number" step="0.0001" class="control">
      </div>
    </div>

    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlQuickEdit">Cancel</button>
      <button class="btn btn-dark" id="btnSaveQuick">
        <i class="bi bi-floppy2"></i> Save Changes
      </button>
    </div>
  </div>
</div>

<!-- Products Breakdown Modal -->
<div class="x-mask" id="mdlProducts">
  <div class="x" style="max-width:860px;">
    <div class="x-hd">
      <span id="mdlProductsTitle"><i class="bi bi-box-seam"></i> Products</span>
    </div>
    <div class="x-bd">
      <div id="mdlProductsLoading" class="text-muted" style="display:none;">Loading…</div>
      <div class="table-responsive">
        <table class="ad-table" id="mdlProductsTbl">
          <thead>
            <tr>
              <th style="white-space:nowrap;">Product</th>
              <th class="ad-num">Items Qty</th>
              <th class="ad-num">Product Qty</th>
              <th>Status</th>
              <th>Created At</th>
              <th class="ad-num">Total Cost</th>
            </tr>
          </thead>
          <tbody><!-- filled by JS --></tbody>
          <tfoot>
            <tr style="background:#f8fafc;font-weight:700;border-top:2px solid #e5e7eb;">
              <th colspan="5" style="text-align:right;padding:12px 18px;">Total</th>
              <th class="ad-num" id="mdlProductsGrand" style="padding:12px 18px;color:#0f172a;">RM 0.00</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlProducts">Close</button>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(() => {
  const $ = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  // Tabs
  document.addEventListener('click', (e)=>{
    const t = e.target.closest('.tab-btn');
    if(!t) return;
    $$('.tab-btn').forEach(b=>b.classList.remove('active'));
    t.classList.add('active');
    $$('.tab-panel').forEach(p=>p.classList.remove('active'));
    $(t.dataset.target).classList.add('active');
  });

  // Dropdown (simple)
  document.addEventListener('click', (e) => {
    const kebab = e.target.closest('.kebab');
    if (kebab) {
      const container = kebab.closest('td') || kebab.parentElement;
      // close all menus first
      document.querySelectorAll('.ad-menu, .dropdown-menu').forEach(m => (m.style.display = 'none'));
      // toggle the one in this cell / header
      const menu = container?.querySelector('.ad-menu, .dropdown-menu');
      if (menu) menu.style.display = (menu.style.display === 'block' ? 'none' : 'block');
      return;
    }
    // clicked outside → close menus
    document.querySelectorAll('.ad-menu, .dropdown-menu').forEach(m => (m.style.display = 'none'));
  });

  function openMask(id){ document.getElementById(id)?.classList.add('show'); }
  function closeMask(id){ document.getElementById(id)?.classList.remove('show'); }

  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-close]');
    if (!el) return;
    closeMask(el.dataset.close);
  });

  // Filters
  function applyFilter(){
    const q = $('#q').value.toLowerCase().trim();
    const t = $('#typeFilter').value;
    let shown = 0;

    $$('#tbl tbody tr').forEach(tr => {
      const name  = tr.children[0].textContent.toLowerCase();
      const passQ = !q || name.includes(q);
      const passT = (t === 'all' || tr.dataset.type === t);
      const ok    = passQ && passT;

      tr.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });

    const countEl = $('#countTotal');
    if (countEl) countEl.textContent = shown;
  }

  window.applyFilter = applyFilter;

  ['input','change'].forEach(ev => {
    document.addEventListener(ev, (e) => {
      if (['q','typeFilter'].includes(e.target.id)) applyFilter();
    });
  });

  // Open Manage Types modal
  const manageBtn = $('#btnManageTypes');
  if (manageBtn) {
    manageBtn.addEventListener('click', () => openMask('mdlTypes'));
  }

  // ===== Material Types: Add / Edit / Activate / Deactivate =====

// helper to reopen the add/edit modal
function openTypeModal({ id = null, name = '' } = {}) {
  const titleEl = document.getElementById('typeTitle');
  const inputEl = document.getElementById('typeName');
  const saveBtn = document.getElementById('btnSaveType');

  if (!titleEl || !inputEl || !saveBtn) return;

  saveBtn.dataset.id = id ? String(id) : '';
  titleEl.textContent = id
    ? `Edit Material Type - ${name}`
    : 'Add New Material Type';

  inputEl.value = name || '';

  openMask('mdlType');
}

// Delegate clicks for Add Type, Edit, Toggle
document.addEventListener('click', (e) => {
  const t = e.target;

  // Open "Add Type" modal
  if (t.closest('#btnAddType')) {
    openTypeModal({ id: null, name: '' });
    return;
  }

  // Edit existing type
  const editTypeBtn = t.closest('.btnEditType');
  if (editTypeBtn) {
    openTypeModal({
      id:   editTypeBtn.dataset.id,
      name: editTypeBtn.dataset.name || '',
    });
    return;
  }

  // Toggle Active / Inactive
  const togTypeBtn = t.closest('.btnToggleType');
  if (togTypeBtn) {
    const id = togTypeBtn.dataset.id;
    if (!id) return;

    const url = "{{ route('boss.material-types.toggle', ['id' => '___ID___']) }}".replace('___ID___', id);

    fetch(url, {
      method: 'PATCH', // real PATCH now
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({}), // no _method spoofing needed
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data || !data.success) {
          alert(data?.message || 'Failed to toggle type status');
          return;
        }

        const active = data.active ? 1 : 0;
        const tr = togTypeBtn.closest('tr');

        // update row classes & status text
        if (tr) {
          tr.classList.toggle('inactive', !active);
          const statusTd = tr.children[1];
          if (statusTd) statusTd.textContent = active ? 'Active' : 'Inactive';
        }

        // update button text + icon + dataset
        togTypeBtn.dataset.active = String(active);
        togTypeBtn.innerHTML = `<i class="bi bi-toggle${active ? 'on' : 'off'} me-2"></i> ${active ? 'Deactivate' : 'Activate'}`;
      })
      .catch(() => alert('Error toggling type status'));

    return;
  }
});

// Save (add or edit) type
document.addEventListener('click', (e) => {
  if (!e.target.closest('#btnSaveType')) return;

  const inputEl = document.getElementById('typeName');
  const name = (inputEl?.value || '').trim();

  // 🔍 Front-end validation
  if (!name) {
    return Swal.fire({
      icon: 'warning',
      title: 'Type name is required',
      text: 'Please enter a material type name before saving.'
    });
  }

  const saveBtn = document.getElementById('btnSaveType');
  const id = saveBtn?.dataset.id || '';
  const isEdit = !!id;

  const url = isEdit
    ? "{{ route('boss.material-types.update', ['id' => '___ID___']) }}".replace('___ID___', id)
    : "{{ route('boss.material-types.store') }}";

  const body = isEdit
    ? { typeName: name, _method: 'PUT' }
    : { typeName: name };

  // Optional loading state
  Swal.fire({
    title: 'Saving...',
    didOpen: () => Swal.showLoading(),
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false
  });

  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'Accept': 'application/json',
    },
    body: JSON.stringify(body),
  })
    .then((r) => r.json())
    .then((data) => {
      if (!data || !data.success || !data.type) {
        return Swal.fire({
          icon: 'error',
          title: 'Failed to save',
          text: data?.message || 'Unable to save material type. Please try again.'
        });
      }

      const type = data.type;

      // 1) Update dropdowns (filter + add-material)
      ['typeFilter', 'matType', 'qeType'].forEach((selId) => {
        const sel = document.getElementById(selId);
        if (!sel) return;

        if (isEdit) {
          Array.from(sel.options).forEach((o) => {
            if (o.value === String(type.id)) o.textContent = type.name;
          });
        } else {
          const o = document.createElement('option');
          o.value = type.id;
          o.textContent = type.name;
          sel.appendChild(o);
        }
      });

      // 2) Update Manage Types table
      const tbody = document.querySelector('#tblTypes tbody');
      if (tbody) {
        if (isEdit) {
          const row = Array.from(tbody.querySelectorAll('tr')).find((tr) => {
            const btn = tr.querySelector('.btnEditType');
            return btn && btn.dataset.id === String(type.id);
          });
          if (row) {
            row.children[0].textContent = type.name;
            const editBtn = row.querySelector('.btnEditType');
            if (editBtn) editBtn.dataset.name = type.name;
          }
        } else {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${type.name}</td>
            <td>Active</td>
            <td style="text-align:right; position:relative">
              <button class="kebab" data-toggle="dropdown" aria-expanded="false" title="Actions">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <div class="dropdown-menu">
                <button class="dropdown-item btnEditType" data-id="${type.id}" data-name="${type.name}">
                  <i class="bi bi-pencil me-2"></i> Edit
                </button>
                <button class="dropdown-item btnToggleType" data-id="${type.id}" data-active="1">
                  <i class="bi bi-toggleon me-2"></i> Deactivate
                </button>
              </div>
            </td>
          `;
          tbody.appendChild(tr);
        }
      }

      closeMask('mdlType');

      Swal.fire({
        icon: 'success',
        title: isEdit ? 'Material type updated' : 'Material type added',
        timer: 1200,
        showConfirmButton: false
      });
    })
    .catch(() => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error saving material type. Please try again.'
      });
    });
});
  
  // Add Material
  $('#btnAddMaterial').addEventListener('click', () => openMask('mdlMaterial'));

  $('#btnSaveMaterial').addEventListener('click', () => {
    const name   = ($('#matName')?.value || '').trim();
    const typeId = $('#matType') ? $('#matType').value : '';
    const cost   = ($('#matCost')?.value || '').trim();

    // 🔍 SweetAlert validation
    if (!name) {
      return Swal.fire({
        icon: 'warning',
        title: 'Material name is required',
        text: 'Please enter a material name before saving.'
      });
    }

    if (!typeId) {
      return Swal.fire({
        icon: 'warning',
        title: 'Material type is required',
        text: 'Please select a material type.'
      });
    }

    if (!cost || isNaN(cost) || Number(cost) < 0) {
      return Swal.fire({
        icon: 'warning',
        title: 'Invalid unit cost',
        text: 'Please enter a valid non-negative number.'
      });
    }

    const postData = {
      materialName: name,
      material_type_id: typeId,
      unitCost: cost
    };

    // ⏳ loading state
    Swal.fire({
      title: 'Saving...',
      didOpen: () => Swal.showLoading(),
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false
    });

    fetch("{{ route('boss.materials.store') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      },
      body: JSON.stringify(postData)
    })
      .then(r => r.json())
      .then((data) => {
        if (!data || !data.success) {
          return Swal.fire({
            icon: 'error',
            title: 'Failed to save',
            text: data?.message || 'Unable to create material. Please try again.'
          });
        }

        Swal.fire({
          icon: 'success',
          title: 'Material added!',
          timer: 1200,
          showConfirmButton: false
        }).then(() => {
          closeMask('mdlMaterial');
          location.reload();
        });
      })
      .catch(() => {
        Swal.fire({
          icon: 'error',
          title: 'Server error',
          text: 'Something went wrong while saving the material.'
        });
      });
  });

  // Edit (open)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btnEdit');
    if (!btn) return;

    window.__editingRow = btn.closest('tr');

    document.getElementById('qeId').value      = btn.dataset.id;
    document.getElementById('qeName').value    = btn.dataset.name || '';
    document.getElementById('qeCurrent').value = btn.dataset.cost
      ? Number(btn.dataset.cost).toFixed(4)
      : '';
    document.getElementById('qeNew').value     = btn.dataset.cost || '';

    // NEW: set current type and modal title
    const qeType = document.getElementById('qeType');
    if (qeType) {
      qeType.value = btn.dataset.typeId || '';
    }

    const titleEl = document.getElementById('qeTitle');
    if (titleEl) {
      titleEl.textContent = `Edit Material - ${btn.dataset.name || ''}`;
    }

    openMask('mdlQuickEdit');
  });

  // Save Quick Edit
  $('#btnSaveQuick').addEventListener('click', () => {
  const id     = $('#qeId').value;
  const name   = ($('#qeName').value || '').trim();
  const cost   = ($('#qeNew').value || '').trim();
  const typeId = $('#qeType') ? $('#qeType').value : '';

  // ========== Front-end validation (SweetAlert) ==========
  if (!name) {
    return Swal.fire({
      icon: 'warning',
      title: 'Material name is required',
      text: 'Please enter a material name before saving.'
    });
  }

  if (!typeId) {
    return Swal.fire({
      icon: 'warning',
      title: 'Material type is required',
      text: 'Please select a material type.'
    });
  }

  if (!cost || isNaN(cost) || Number(cost) < 0) {
    return Swal.fire({
      icon: 'warning',
      title: 'Invalid unit cost',
      text: 'Please enter a valid non-negative number for the new unit cost.'
    });
  }

  const postData = {
    qeName: name,
    qeNew:  cost,
    qeType: typeId
  };

  // Optional: small loading state
  Swal.fire({
    title: 'Saving...',
    didOpen: () => Swal.showLoading(),
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false
  });

  fetch("{{ route('boss.materials.update', ['id' => '___ID___']) }}".replace('___ID___', id), {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'Accept': 'application/json'
    },
    body: JSON.stringify(postData)
  })
    .then(r => r.json())
    .then((data) => {
      if (!data || !data.success) {
        return Swal.fire({
          icon: 'error',
          title: 'Failed to update',
          text: data?.message || 'Something went wrong while updating the material.'
        });
      }

      const mat = data.material || {};

      // Use the row we remembered when opening the modal
      const tr = window.__editingRow || null;
      window.__editingRow = null;

      if (tr) {
        const nameCell = tr.querySelector('td:nth-child(1)');
        const typeCell = tr.querySelector('td:nth-child(2)');
        const costCell = tr.querySelector('td:nth-child(3)');

        const newName = name;
        const newCost = Number(cost).toFixed(4);

        if (nameCell) nameCell.textContent = newName;
        if (costCell) costCell.textContent = `RM ${newCost}`;
        if (typeCell && mat.type_name) typeCell.textContent = mat.type_name;

        const btn = tr.querySelector('.btnEdit');
        if (btn) {
          btn.dataset.name   = newName;
          btn.dataset.cost   = cost;
          if (mat.type_id)   btn.dataset.typeId   = mat.type_id;
          if (mat.type_name) btn.dataset.typeName = mat.type_name;
        }
      }

      closeMask('mdlQuickEdit');

      // success alert + refresh
      Swal.fire({
        icon: 'success',
        title: 'Material updated',
        timer: 1200,
        showConfirmButton: false
      }).then(() => {
        location.reload();
      });
    })
    .catch(() => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error updating material. Please try again.'
      });
    });
});

  // Activate / Deactivate material
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btnToggleMaterial');
    if (!btn) return;

    const id = btn.dataset.id;
    if (!id) return;

    const url = "{{ route('boss.materials.toggle', ['id' => '___ID___']) }}".replace('___ID___', id);

    fetch(url, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({}),
    })
      .then(r => r.json())
      .then(data => {
        if (!data || !data.success) {
          alert(data?.message || 'Failed to update material status');
          return;
        }

        const active = data.active ? 1 : 0;
        const tr = btn.closest('tr');

        // update Status cell
        const statusTd = tr?.querySelector('.mat-status');
        if (statusTd) statusTd.textContent = active ? 'Active' : 'Inactive';

        // update button label + icon + data-active
        btn.dataset.active = String(active);
        btn.innerHTML = `<i class="bi bi-toggle${active ? 'on' : 'off'} me-2"></i> ${active ? 'Deactivate' : 'Activate'}`;
      })
      .catch(() => alert('Error updating material status'));
  });

  // Init
  window.addEventListener('load', applyFilter);
})();

function formatStatus(status) {
  if (!status) return '-';
  return status
    .toString()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase()); // capitalize each word
}

(function () {
  const $ = s => document.querySelector(s);

  function openProductsModal(orderId, orderLabel) {
    const modal = $('#mdlProducts');
    const tbody = $('#mdlProductsTbl tbody');
    const grand = $('#mdlProductsGrand');

    $('#mdlProductsTitle').innerHTML = `<i class="bi bi-box-seam"></i> Products for <b>${orderLabel}</b>`;
    tbody.innerHTML = '';
    grand.textContent = 'RM 0.00';
    $('#mdlProductsLoading').style.display = 'block';
    modal.classList.add('show');

    const url = `{{ route('boss.datamanagement.orderProducts', ['order' => '___ID___']) }}`.replace('___ID___', orderId);

    fetch(url)
      .then(r => r.json())
      .then(data => {
        $('#mdlProductsLoading').style.display = 'none';

        if (!data.success) {
          tbody.innerHTML = `<tr><td colspan="6" style="padding:16px;color:#6b7280;">Failed to load.</td></tr>`;
          return;
        }
        if (!data.products.length) {
          tbody.innerHTML = `<tr><td colspan="6" style="padding:16px;color:#6b7280;">No products found.</td></tr>`;
          return;
        }

        let total = 0;
        const fmtRM = n => 'RM ' + Number(n || 0).toFixed(2);
        const esc = s => (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

        tbody.innerHTML = data.products.map(p => {
          total += Number(p.total_cost || 0);
          return `
            <tr>
              <td>${esc(p.name ?? ('#' + p.product_id))}</td>
              <td class="ad-num">${Number(p.items_quantity || 0).toLocaleString()}</td>
              <td class="ad-num">${Number(p.order_quantity || 0).toLocaleString()}</td>
              <td>${formatStatus(p.status)}</td>
              <td>${esc(p.created_at)}</td>
              <td class="ad-num">${fmtRM(p.total_cost)}</td>
            </tr>`;
        }).join('');

        grand.textContent = fmtRM(total);
      })
      .catch(() => {
        $('#mdlProductsLoading').style.display = 'none';
        tbody.innerHTML = `<tr><td colspan="6" style="padding:16px;color:#6b7280;">Error loading data.</td></tr>`;
      });
  }

  // Attach click handler immediately (runs even if later JS breaks)
  document.addEventListener('click', (e) => {
    const a = e.target.closest('.ad-qty-link');
    if (!a) return;
    e.preventDefault();

    const orderId = a.dataset.orderId;
    const row = a.closest('tr');
    const orderLabel = row ? (row.querySelector('td:first-child')?.textContent?.trim() || `#${orderId}`) : `#${orderId}`;

    openProductsModal(orderId, orderLabel);
  });
})();

  (function(){
    const f = document.getElementById('adFilters');
    if (!f) return;

    // Submit on range / status change
    document.getElementById('adType')?.addEventListener('change', ()=> f.submit());
    document.getElementById('adDate')?.addEventListener('change', ()=> f.submit());

    // Press Enter in either search field => submit
    const enter = e => { if (e.key === 'Enter') { e.preventDefault(); f.submit(); } };
    document.getElementById('adSearchId')?.addEventListener('keydown', enter);
    document.getElementById('adSearchText')?.addEventListener('keydown', enter);
  })();

  (function(){
    const resetBtn = document.getElementById('adReset');
    if (!resetBtn) return;

    resetBtn.addEventListener('click', (e) => {
      e.preventDefault();
      // Go back to base list with only the tab param
      window.location.href = "{{ route('boss.datamanagement', ['tab' => 'another']) }}";
    });
  })();

// ========= PAGINATION =========
let currentPage = 1;
const rowsPerPage = 10;

function paginateTable() {
  const allRows = Array.from(document.querySelectorAll('#tbl tbody tr')).filter(r => r.style.display !== 'none');
  const totalRows = allRows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));
  currentPage = Math.min(currentPage, totalPages);

  allRows.forEach((row, i) => {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    row.style.display = (i >= start && i < end) ? '' : 'none';
  });

  document.getElementById('totalCount').textContent = totalRows;
  document.getElementById('shownCount').textContent = Math.min(totalRows, rowsPerPage);
  document.getElementById('pageInfo').textContent = `${currentPage} / ${totalPages}`;
  document.getElementById('prevPage').disabled = currentPage === 1;
  document.getElementById('nextPage').disabled = currentPage === totalPages;
}

document.getElementById('prevPage').addEventListener('click', () => {
  if (currentPage > 1) {
    currentPage--;
    paginateTable();
  }
});

document.getElementById('nextPage').addEventListener('click', () => {
  currentPage++;
  paginateTable();
});

// Modify applyFilter to reapply pagination after filtering
if (typeof window.applyFilter === 'function') {
  const _oldApplyFilter = window.applyFilter;
  window.applyFilter = function () {
    _oldApplyFilter();
    currentPage = 1;
    paginateTable();
  };
}

// Initial pagination after load
window.addEventListener('load', paginateTable);

// ============ Another Data (original, with dummy data) ============
(function () {
  const $$ = s => Array.from(document.querySelectorAll(s));

  function activate(targetSel) {
    // buttons
    $$('.tab-btn').forEach(a => a.classList.toggle('active', a.dataset.target === targetSel));
    // panels
    $$('.tab-panel').forEach(p => p.classList.toggle('active', ('#' + p.id) === targetSel));
  }

  function hashFor(targetSel) {
    return targetSel === '#anotherData' ? '#another' : '#cost';
  }

  function applyFromHash() {
    const h = (location.hash || '').toLowerCase();
    if (h === '#another') activate('#anotherData');
    else {
      activate('#costData');
      if (h !== '#cost') history.replaceState(null, '', '#cost');
    }
  }

  // 1) Click tabs → sync hash and activate now
  document.addEventListener('click', e => {
    const a = e.target.closest('.tab-btn');
    if (!a) return;
    const targetSel = a.dataset.target;
    const newHash = hashFor(targetSel);
    if (location.hash !== newHash) history.replaceState(null, '', newHash);
    activate(targetSel);
    e.preventDefault();
  });

  // 2) Immediately reflect current hash (works on reload/pagination)
  applyFromHash();

  // 3) Also handle back/forward
  window.addEventListener('hashchange', applyFromHash);
})();

document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.getElementById('adBody'); // your tbody id
  if (!tbody) return;

  tbody.addEventListener('dblclick', (e) => {
    const tr = e.target.closest('tr.js-row-open');
    if (!tr) return;

    // If user double-clicks on any clickable element, don't hijack it
    if (e.target.closest('a, button, input, select, textarea, label, svg, path, i')) return;

    const url = tr.dataset.href;
    if (url) window.location.assign(url);
  });
});
</script>
@endsection
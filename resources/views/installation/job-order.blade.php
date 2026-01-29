@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
  /* force row on desktop */
  .dc-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: nowrap;              /* keep one row on wide screens */
  }

  /* each child should size to content by default */
  .dc-toolbar > * { flex: 0 0 auto; }

  /* let the keyword search take the extra space */
  .dc-toolbar .grow {
    flex: 1 1 420px;                /* grows, with a sensible min width */
    min-width: 320px;
  }

  /* bootstrap input-group defaults to width:100%; override for toolbar */
  .dc-toolbar .input-group { width: auto; }

  .dc-toolbar .form-control,
  .dc-toolbar .form-select,
  .dc-toolbar .btn {
    height: 44px;
  }

  .dc-toolbar .input-group-text { background: #fff; }

  /* spacer to push buttons to the far right */
  .dc-toolbar .spacer { flex: 1 1 auto; }

  /* wrap vertically on small screens */
  @media (max-width: 992px) {
    .dc-toolbar { flex-wrap: wrap; }
    .dc-toolbar .spacer { flex-basis: 100%; height: 0; }
  }
  .table td i {
    font-size: 1rem;
    vertical-align: middle;
  }

  .filter-toolbar {
    display: flex;
    flex-wrap: wrap; /* allow wrapping */
    gap: 0.5rem;     /* spacing between items */
    align-items: center;
    justify-content: flex-start;
  }

  .filter-toolbar .input-group,
  .filter-toolbar select,
  .filter-toolbar .btn {
    flex: 1 1 auto;   /* allow flexible width */
    min-width: 180px; /* ensure readability on smaller screens */
  }

  @media (max-width: 992px) {
    /* for tablet/laptop */
    .filter-toolbar .btn { flex: 0 0 auto; /* keep buttons compact */ }
  }

  /* Layout polish */
  .card-soft {
    border: 1px solid #ECEFF3;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(16, 24, 40, .06)
  }

  .stat {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 18px;
    border-radius: 14px;
    border: 1px solid #ECEFF3;
    background: #fff;
    height: 100px;
  }

  .stat i { font-size: 20px }

  .stat .count {
    font-weight: 800;
    font-size: 18px;
    line-height: 1
  }

  .stat small { color: #667085 }

  /* Toolbar */
  .toolbar { gap: 10px }

  .toolbar .form-control,
  .toolbar .form-select,
  .toolbar .btn { height: 44px }

  .toolbar .form-select { min-width: 190px }
  .toolbar .btn { white-space: nowrap }

  /* prevent label wrapping like in your screenshot */
  .btn-icon {
    width: 44px; height: 44px;
    display: inline-flex; align-items: center; justify-content: center
  }

  /* Table */
  .table thead th {
    font-size: 12px;
    letter-spacing: .02em;
    font-weight: 700;
    color: #475467;
    background: #F8FAFC
  }

  .table tbody tr:hover { background: #FAFBFC }

  .pill {
    display: inline-block;
    padding: .35rem .7rem;
    border-radius: 999px;
    font-weight: 600;
    font-size: .825rem
  }

  .action-btn {
    width: 32px; height: 32px; padding: 0;
    border: 1px solid #D0D5DD; border-radius: 8px;
    background: #fff; color: #475467;
    display: inline-flex; align-items: center; justify-content: center
  }
  .action-btn:hover { background: #F2F4F7; color: #111827 }

  /* Empty state inside table */
  .empty-wrap { padding: 38px 12px; text-align: center }

  .empty-icon {
    width: 52px; height: 52px; border-radius: 12px;
    display: inline-flex; align-items: center; justify-content: center;
    background: #EEF2FF; color: #4F46E5; margin-bottom: 12px
  }

  .empty-title { font-weight: 700; color: #111827 }

  .empty-text {
    color: #667085;    /* ← 修复：补上分号与右花括号 */
  }

  /* ===== 下面是你追加的 Mobile Add-on，原样保留 ===== */

  @media (max-width: 992px) {
    /* ===== Toolbar 优化 ===== */
    .dc-toolbar {
      flex-wrap: wrap;
      gap: 10px;
      padding: 8px;
    }
    .dc-toolbar .grow { flex: 1 1 100%; min-width: 0; }
    .dc-toolbar .form-control,
    .dc-toolbar .form-select,
    .dc-toolbar .btn { height: 40px; font-size: 14px; }

    /* ===== Filter Toolbar ===== */
    .filter-toolbar { gap: 8px; justify-content: flex-start; }
    .filter-toolbar .input-group,
    .filter-toolbar select,
    .filter-toolbar .btn { flex: 1 1 45%; min-width: 160px; }

    /* ===== Stats Cards ===== */
    .stat {
      flex-direction: row; justify-content: flex-start;
      padding: 14px 16px; height: auto;
    }
    .stat i { font-size: 18px; }
    .stat .count { font-size: 16px; }
    .stat small { font-size: 12px; }

    /* ===== 表格容器：允许横向滚动 ===== */
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table { white-space: nowrap; border-radius: 10px; }
    .table thead th { font-size: 11px; }
    .table td, .table th { padding: 10px 12px; }

    /* ===== 空状态居中微调 ===== */
    .empty-wrap { padding: 30px 10px; }
    .empty-icon { width: 46px; height: 46px; margin-bottom: 10px; }

    /* ===== 按钮尺寸略缩 ===== */
    .btn-icon, .action-btn { width: 38px; height: 38px; }
  }

  /* 手机（<=576px）：全宽堆叠布局 */
  @media (max-width: 576px) {
    /* ===== Toolbar 全宽堆叠 ===== */
    .dc-toolbar {
      flex-direction: column; align-items: stretch;
      gap: 8px; padding: 8px;
    }
    .dc-toolbar > * { flex: 1 1 100%; min-width: 100%; }
    .dc-toolbar .btn { width: 100%; }

    /* ===== Filter Toolbar 堆叠 ===== */
    .filter-toolbar {
      flex-direction: column; align-items: stretch; gap: 8px;
    }
    .filter-toolbar .input-group,
    .filter-toolbar select,
    .filter-toolbar .btn {
      width: 100%; min-width: 0; flex: 1 1 100%;
    }

    /* ===== Stats Cards 堆叠 ===== */
    .stat { flex-direction: column; align-items: flex-start; gap: 6px; padding: 12px; }
    .stat .count { font-size: 18px; }
    .stat small { font-size: 11px; }

    /* ===== 表格横向滑动 ===== */
    .table { font-size: 13px; }
    .table td, .table th { padding: 8px 10px; }

    /* ===== 按钮大小适配单手操作 ===== */
    .btn-icon, .action-btn { width: 36px; height: 36px; }

    /* ===== 空状态适配窄屏 ===== */
    .empty-wrap { padding: 28px 8px; }
    .empty-title { font-size: 15px; }
    .empty-text { font-size: 13px; }
  }

  /* 极小屏 <=360px 再保底 */
  @media (max-width: 360px) {
    .dc-toolbar .form-control,
    .dc-toolbar .form-select,
    .dc-toolbar .btn { height: 38px; font-size: 13px; }
    .table td, .table th { padding: 6px 8px; }
    .btn-icon, .action-btn { width: 34px; height: 34px; }
    .stat .count { font-size: 16px; }
  }

  /* ===== 可选UX提升：table横滑渐隐提示 ===== */
  @media (max-width: 992px) {
    .table-responsive{
      background:
        linear-gradient(to right, #fff 25%, rgba(255,255,255,0)) left/20px 100% no-repeat,
        linear-gradient(to left,  #fff 25%, rgba(255,255,255,0)) right/20px 100% no-repeat;
      background-attachment: local, local;
    }
  }

  /* ===== 你前条消息里的 Filter Card 微调，保持原样 ===== */
  .filter-card{ padding: 12px 12px 14px 12px; }

  .filter-actions-top{
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;
  }
  .filter-actions-top .btn{
    height: 40px; line-height: 38px; border-radius: 10px; font-weight: 700; font-size: 13px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 12px;
  }

  .filter-card .input-group.input-group-sm .form-control,
  .filter-card .input-group.input-group-sm .form-select{ height: 40px; font-size: 13px; }
  .filter-card .form-control, .filter-card .form-select{ border-radius: 10px; }
  .filter-card .has-icon .input-group-text{ background:#fff; border-right:0; }

  .filter-card .form-group, .filters-row > *{ margin-bottom: 8px; }

  @media (max-width: 420px){
    .filter-actions-top{ grid-template-columns: 1fr; gap: 8px; }
    .filter-actions-top .btn{ width: 100%; }
  }

  .filter-card ::placeholder{ color:#9aa3af; }
  .filter-card .form-select{ padding-right: 2.25rem; background-position: right .6rem center; }


</style>
@php
// Safe defaults so the view never breaks
$stats = $stats ?? [];
$orders = $orders ?? collect();

$typeStyles = [
'printing' => 'bg-primary-subtle text-primary',
'furnishing' => 'bg-warning-subtle text-warning',
'installation' => 'bg-info-subtle text-info',
'courier' => 'bg-success-subtle text-success',
'self pickup' => 'bg-secondary-subtle text-secondary',
];
$statusStyles = [
'completed' => 'bg-success text-white',
'in_progress' => 'bg-warning-subtle text-warning',
'rejected' => 'bg-danger-subtle text-danger',
];

$totalResults = method_exists($orders, 'total') ? $orders->total() : $orders->count();
$hasResults = $totalResults > 0;

$taskLabel = function (?string $raw) {
    $t = strtolower((string)$raw);
    return match ($t) {
        'delivery',
            => 'Dispatch Control',
        'installation',
        'delivery_installation'
            => 'Delivery & Installation',
        default
            => \Illuminate\Support\Str::title($t),
    };
};
@endphp

<div class="container-fluid py-4 px-4">
  <h1 class="fw-bold mb-3" style="font-size:32px;letter-spacing:-.3px;">Job Order Table</h1>

  {{-- Stats --}}
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg cursor-pointer" data-go-status="printing">
      <div class="stat card-soft">
        <i class="bi bi-printer text-primary"></i>
        <div>
          <div class="count">{{ number_format($stats['printing'] ?? 0) }}</div><small>Printing Overview</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg cursor-pointer" data-go-status="furnishing">
      <div class="stat card-soft">
        <i class="bi bi-brush text-warning"></i>
        <div>
          <div class="count">{{ number_format($stats['furnishing'] ?? 0) }}</div><small>Furnishing Overview</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg cursor-pointer" data-go-status="installation">
      <div class="stat card-soft">
        <i class="bi bi-wrench-adjustable text-dark"></i>
        <div>
          <div class="count">{{ number_format($stats['installation'] ?? 0) }}</div><small>Delivery & Installation Overview</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="stat card-soft">
        <i class="bi bi-bag-check text-secondary"></i>
        <div>
          <div class="count">{{ number_format($stats['self_pickup'] ?? 0) }}</div><small>Self Pickup Overview</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="stat card-soft">
        <i class="bi bi-bicycle text-success"></i>
        <div>
          <div class="count">{{ number_format($stats['courier'] ?? 0) }}</div><small>Courier Overview</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Toolbar --}}
<div class="card card-soft mb-3">
  <div class="card-body">
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div>
        <div class="fw-bold">Delivery & Installation</div>
        <small class="text-muted">Filter &amp; search</small>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('installation.job-order') }}" class="btn btn-light border">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
        </a>
        <button form="dispatch-filter" class="btn btn-dark">
          <i class="bi bi-funnel me-1"></i> Apply Filter
        </button>
      </div>
    </div>

    {{-- Toolbar (single row on desktop) --}}
    <form id="dispatch-filter" method="GET" action="{{ route('installation.job-order') }}" class="dc-toolbar">
      <input type="hidden" name="method" value="{{ request('method') }}">

      {{-- Product ID --}}
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-hash"></i></span>
        <input type="text" name="pid" value="{{ request('pid', $pid ?? '') }}"
               class="form-control" placeholder="# Enter Product ID" style="max-width:130px;">
      </div>

      {{-- Keyword search (grows) --}}
      <div class="input-group grow">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="q" value="{{ request('q', $q ?? '') }}"
               class="form-control" placeholder="Order title, Company name, or Product">
      </div>

      {{-- Artist --}}
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
        <select name="artist" class="form-select" style="max-width:130px;">
          <option value="">All artists</option>
          @foreach(($artists ?? []) as $a)
            <option value="{{ $a->id }}" @selected((string)$a->id === (string)request('artist', $artist ?? ''))>
              {{ $a->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Type --}}
      <select name="task_type" class="form-select" style="max-width:150px;">
        <option value="">All Types</option>
        <option value="printing"     @selected(request('task_type')==='printing')>Printing</option>
        <option value="furnishing"   @selected(request('task_type')==='furnishing')>Furnishing</option>
        <option value="installation" @selected(request('task_type')==='installation')>Delivery &amp; Installation</option>
        <option value="delivery"     @selected(request('task_type')==='delivery')>Dispatch Control</option>
      </select>

      {{-- Status --}}
      <select name="status" class="form-select" style="max-width:140px;">
        <option value="">All Statuses</option>
        <option value="in_progress" @selected(request('status')==='in_progress')>In Progress</option>
        <option value="completed"   @selected(request('status')==='completed')>Completed</option>
        <option value="rejected"    @selected(request('status')==='rejected')>Rejected</option>
      </select>

    </form>
  </div>
</div>

  {{-- Table --}}
  <div class="card card-soft">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>PRODUCT ID</th>
            <th>ORDER TITLE</th>
            <th>TASK TYPE</th>

            {{-- DEADLINE sort (nearest / furthest) --}}
            <th>
              @php
              $isDead = request('sort_by') === 'deadline';
              $nextDead = ($isDead && request('sort_mode')==='near') ? 'far' : 'near';
              @endphp
              <a class="text-decoration-none text-dark"
                href="{{ request()->fullUrlWithQuery(['sort_by'=>'deadline','sort_mode'=>$nextDead,'page'=>1]) }}">
                DEADLINE
                @if($isDead)
                <span class="badge bg-light text-muted ms-1">{{ strtoupper(request('sort_mode','near')) }}</span>
                @endif
              </a>
            </th>

            <th>STATUS</th>

            {{-- DELIVERY DATE sort (nearest / furthest) --}}
            <th>
              @php
              $isDel = request('sort_by') === 'delivery_date';
              $nextDel = ($isDel && request('sort_mode')==='near') ? 'far' : 'near';
              @endphp
              <a class="text-decoration-none text-dark"
                href="{{ request()->fullUrlWithQuery(['sort_by'=>'delivery_date','sort_mode'=>$nextDel,'page'=>1]) }}">
                DELIVERY DATE
                @if($isDel)
                <span class="badge bg-light text-muted ms-1">{{ strtoupper(request('sort_mode','near')) }}</span>
                @endif
              </a>
            </th>

            <th>DELIVERY LOCATION</th>
            <th class="text-end">ACTIONS</th>
          </tr>
        </thead>
        <tbody class="small">
          @forelse($orders as $o)
          @php
          $type = strtolower($o->task_type ?? '');
          $status = strtolower($o->status ?? '');
          $typeCls = $typeStyles[$type] ?? 'bg-light text-muted';
          $statCls = $statusStyles[$status] ?? 'bg-light text-muted';
          // 🔹 default label from helper
          $taskLabelText = $taskLabel($o->task_type);

          // 🔹 override: Delivery + installation_task_type = 1
          if ($type === 'delivery' && (int)($o->installation_task_type ?? 0) === 1) {
              $taskLabelText = 'Delivery & Installation';
          }
          @endphp
          @php
              $baseType       = strtolower($o->task_type ?? '');
              $deliveryMethod = strtolower($o->delivery_method ?? '');
              $installFlag    = (int)($o->installation_task_type ?? 0);

              // 🔹 Decide logical type for this row
              if ($installFlag === 1 && $deliveryMethod === 'delivery_installation') {
                  // treat as Installation so it uses the blue pill + nice label
                  $logicalType = 'installation';
              } else {
                  $logicalType = $baseType; // printing / furnishing / delivery / ...
              }

              $type          = $logicalType;
              $typeCls       = $typeStyles[$type] ?? 'bg-light text-muted';
              $status        = strtolower($o->status ?? '');
              $statCls       = $statusStyles[$status] ?? 'bg-light text-muted';
          @endphp
          <tr class="js-row" data-href="{{ $o->details_url }}" style="cursor: pointer;">
            <td class="fw-semibold">{{ $o->product_code ?? $o->product_id ?? '—' }}</td>
            <td>{{ $o->order_title ?? '—' }}</td>

            <td>
              <span class="pill {{ $typeCls }}">
                {{ $taskLabel($type) }}
              </span>
            </td>

            <td>{{ $o->deadline ?? '—' }}</td>
            <td>
              @php
              $nice = $o->status ? \Illuminate\Support\Str::title(str_replace('_',' ', $o->status)) : '—';
              @endphp
              <span class="pill {{ $statCls }}">{{ $nice }}</span>
            </td>
            <td class="text-center">
              @if(!empty($o->delivery_date))
              {{ $o->delivery_date }}
              @else
              <i class="bi bi-exclamation-triangle text-warning" title="No delivery date"></i>
              @endif
            </td>

            <td class="text-center">
              @if(!empty($o->delivery_location))
              {{ $o->delivery_location }}
              @else
              <i class="bi bi-exclamation-triangle text-warning" title="No delivery location"></i>
              @endif
            </td>
            <td class="text-end">
              <div class="d-inline-flex gap-1">
                <a href="{{ $o->details_url ?? '#' }}" class="action-btn" title="View">
                  <i class="bi bi-eye"></i>
                </a>

                @if(!empty($o->can_edit) && $o->can_edit)
                <a href="{{ $o->edit_url ?? ($o->details_url ?? '#') }}" class="action-btn" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8">
              <div class="empty-wrap">
                <div class="empty-icon"><i class="bi bi-inboxes"></i></div>
                <div class="empty-title mb-1">No job orders found.</div>
                <div class="empty-text mb-2">
                  Try adjusting your filters or clear them to see more results.
                </div>
                <a href="{{ route('installation.job-order') }}" class="btn btn-light border">
                  <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filters
                </a>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center px-4 py-3 small text-muted">
      <div>
        @if(method_exists($orders,'total'))
        Showing {{ $hasResults ? ($orders->firstItem().' to '.$orders->lastItem().' of '.$orders->total().' results') : '0 results' }}
        @else
        {{ $hasResults ? ($orders->count().' results') : '0 results' }}
        @endif
      </div>
      <div>
        @if(method_exists($orders,'links')) {{ $orders->onEachSide(1)->links('pagination::bootstrap-5') }} @endif
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('dblclick', e => {
    const tr = e.target.closest('tr.js-row');
    if (!tr) return;
    // ignore when dbl-clicking interactive controls
    const tag = (e.target.tagName || '').toLowerCase();
    if (['a', 'button', 'input', 'select', 'textarea', 'label', 'svg', 'path', 'i'].includes(tag)) return;
    const url = tr.dataset.href;
    if (url) window.location.href = url;
  });

  (function() {
    const base = "{{ route('installation.job-order') }}";

    function setParam(url, key, val) {
      if (val === '' || val == null) {
        url.searchParams.delete(key);
      } else {
        url.searchParams.set(key, val);
      }
    }

    document.querySelectorAll('[data-go-status]').forEach(function(tile) {
      tile.addEventListener('click', function() {
        const val = (tile.getAttribute('data-go-status') || '').toLowerCase();
        const url = new URL(window.location.href);

        // always jump to first page when changing a tile filter
        url.searchParams.delete('page');

        // clear both knobs first so they don't conflict
        url.searchParams.delete('task_type');

        // map: printing/furnishing -> task_type, self_pickup/courier -> method
        if (val === 'printing' || val === 'furnishing' || val === 'installation' || val === 'delivery') {
          setParam(url, 'task_type', val);
        } 

        window.location.href = url.toString();
      });
    });
  })();

  (() => {
  // NEW: method tabs
  document.querySelectorAll('[data-go-method]').forEach(el => {
    el.addEventListener('click', () => {
      const val = (el.getAttribute('data-go-method') || '').trim();
      const url = new URL(window.location.href);

      // reset paging so you don't land on an empty page
      url.searchParams.delete('page');

      // ensure we only use the new method filter
      url.searchParams.delete('method');
      url.searchParams.set('method', val);

      window.location.href = url.toString();
    });
  });
})();
</script>
@endsection
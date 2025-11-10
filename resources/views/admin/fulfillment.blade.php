@extends('layouts.app')
@section('title','Fulfillment Overview')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
  .bg-purple { background-color: #6f42c1 !important; color: #fff !important; }
  .bg-teal { background-color: #20c997 !important; color: #fff !important; }
  .bg-orange { background-color: #fd7e14 !important; color: #fff !important; }

  .badge-pill{border-radius:999px;padding:.28rem .6rem;font-weight:600;font-size:.74rem}
  .badge-progress{background:#eef2ff;color:#4338ca}
  .badge-completed{background:#ecfdf5;color:#047857}
  .badge-rejected{background:#fef2f2;color:#b91c1c}
  .badge-pending{background:#fff7ed;color:#b45309}

  .toolbar .form-select,
  .toolbar .input-group{height:38px}

  .card-ft nav {
    gap: 20px; /* spacing between selector and navigator */
  }
  @media (max-width: 768px) {
    .card-ft nav {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }
  }
  .pill-pager .page-link {
    border-radius: 1.5rem;
    padding: 0.35rem 0.75rem;
    font-size: 0.875rem;
    color: #4a5568;
    border: none;
  }

  .pill-pager .page-item.active .page-link {
    background: #3b82f6;
    color: #fff;
    font-weight: 600;
  }

  .pill-pager .page-item.disabled .page-link {
    color: #a0aec0;
    background: #f1f5f9;
  }

  .pill-pager .page-item:not(.active):not(.disabled) .page-link:hover {
    background: #e5edff;
    color: #2563eb;
  }

  .btn-icon {
    width: 34px;
    height: 34px;
    display: inline-grid;
    place-items: center;
    border-radius: .65rem;
  }

  .btn-soft {
    border: none;
  }

  .btn-soft-primary {
    background: #eef4ff;
    color: #2f6fec;
  }

  .btn-soft-secondary {
    background: #f2f4f7;
    color: #3a3f45;
  }

  .btn-soft-warning {
    background: #fff4e5;
    color: #c15c01;
  }

  .btn-soft-primary:hover {
    background: #e3ecff;
    color: #1f5ce0;
  }

  .btn-soft-secondary:hover {
    background: #eaecef;
  }

  .btn-soft-warning:hover {
    background: #ffeed7;
  }

  .btn-actions {
    display: inline-flex;
    gap: .35rem;
  }

  /* Compact, centered Bootstrap paginator */
  .pagination {
    justify-content: center;
    gap: .25rem;
  }

  .pagination .page-link {
    padding: .35rem .6rem;
    border-radius: .5rem;
  }

  .pagination .page-item.active .page-link {
    box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .15);
  }

  /* Fix blown-up SVG chevrons from Tailwind paginator or global styles */
  .pagination svg {
    width: 16px !important;
    height: 16px !important;
    display: inline-block;
  }

  .pagination .page-link>span {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
  }

  /* Per-page selector spacing */
  .per-page-wrap {
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  :root {
    --bg: #F6F7FB;
    --card: #fff;
    --muted: #667085;
    --text: #0F172A;
    --border: #E5E7EB;
    --shadow: 0 6px 18px rgba(16, 24, 40, .06);
    --primary: #5B55F6;
    --primary-600: #4B47E6;
    --success: #16A34A;
    --info: #2563EB;
    --warn: #F59E0B;
  }

  /* ==== 防止侧栏打开时整页被横向撑开 ==== */
  html,
  body {
    max-width: 100%;
    overflow-x: hidden;
  }

  body {
    background: var(--bg)
  }

  .wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 22px;
    box-sizing: border-box;
  }

  /* title */
  .h2 {
    font-weight: 800;
    color: var(--text);
    font-size: 22px;
    margin: 0 0 14px
  }

  /* metric cards */
  .metrics {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 16px
  }

  .metric {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: var(--shadow);
    padding: 16px;
    display: flex;
    gap: 12px;
    align-items: center
  }

  .metric .ic {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    background: #EEF2FF;
    display: grid;
    place-items: center
  }

  .metric .ic i {
    color: #334155;
    font-size: 18px
  }

  .metric .caption {
    color: var(--muted);
    font-size: 12px
  }

  .metric .num {
    font-weight: 800;
    color: var(--text);
    font-size: 20px
  }

  /* toolbar */
  .toolbar {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: var(--shadow);
    padding: 16px;
    display: grid;
    gap: 14px;
    margin-bottom: 14px
  }

  .tb-row {
    display: grid;
    grid-template-columns: 1.15fr 1.15fr 1.6fr .9fr .9fr;
    gap: 12px;
    grid-auto-columns: minmax(0, 1fr)
  }

  .tb-row2 {
    display: grid;
    grid-template-columns: 1.6fr .6fr .6fr .6fr;
    gap: 12px;
    grid-auto-columns: minmax(0, 1fr)
  }

  .control {
    height: 44px;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0 14px;
    background: #fff;
    min-width: 0
  }

  .control::placeholder {
    color: #98A2B3
  }

  .btn {
    height: 44px;
    border-radius: 12px;
    border: 1px solid transparent;
    font-weight: 700
  }

  .btn-primary {
    background: var(--primary);
    color: #fff
  }

  .btn-primary:hover {
    background: var(--primary-600)
  }

  .btn-ghost {
    background: #F7F7FF;
    color: var(--primary);
    border: 1px solid #E7E9FE
  }

  .btn-ghost:hover {
    background: #EEF0FF
  }

  .btn-outline {
    background: #fff;
    border: 1px solid var(--border);
    color: #0F172A
  }

  .btn-outline i {
    margin-right: 8px
  }

  /* table card */
  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: var(--shadow)
  }

  .card-hd {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    font-weight: 700
  }

  /* ✅ 仅卡片内部可横向滚动 */
  .table-responsive {
    padding: 10px 12px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
    table-layout: auto;
    font-size: 14px;
    /* ✅ 防止列过多被过度压缩，造成再次撑出页面 */
    min-width: 980px;
    /* 可按需要微调 */
  }

  thead th {
    font-size: 12px;
    text-transform: uppercase;
    color: #8A94A6;
    letter-spacing: .04em;
    background: #F9FAFB;
    padding: 10px 12px;
    white-space: nowrap;
    border-top: 1px solid var(--border)
  }

  tbody tr {
    background: #fff;
    border: 1px solid var(--border)
  }

  tbody td {
    padding: 12px 10px;
    vertical-align: middle;
    word-break: break-word;
    white-space: normal;
  }

  tbody tr td:first-child {
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px
  }

  tbody tr td:last-child {
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px
  }

  /* col widths */
  .w-id {
    width: 120px
  }

  .w-name {
    width: 240px
  }

  .w-task {
    width: 120px
  }

  .w-dead {
    width: 120px
  }

  .w-status {
    width: 130px
  }

  .w-date {
    width: 120px
  }

  .w-loc {
    width: 260px
  }

  .w-inst {
    width: 130px
  }

  .w-cost {
    width: 90px
  }

  .w-act {
    width: 96px
  }

  .nowrap {
    white-space: nowrap
  }

  /* badge */
  .badge {
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    display: inline-block
  }

  .bd-green {
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #16A34A
  }

  .bd-blue {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: #2563EB
  }

  .bd-warn {
    background: #FFF7ED;
    border: 1px solid #FED7AA;
    color: #EA580C
  }

  /* ✅ Action buttons (compact gray style) */
  .actions {
    display: flex;
    justify-content: flex-end;
    gap: 6px;
    /* 缩小间距 */
  }

  .act {
    width: 34px;
    height: 34px;
    border: none;
    background: transparent;
    display: grid;
    place-items: center;
    transition: all 0.2s ease;
  }

  .act i {
    font-size: 18px;
    color: #3180e7ff;
    /* 浅灰色 */
    transition: color 0.2s ease, transform 0.2s ease;
  }

  .act:hover i {
    color: #475569;
    /* hover 时变深 */
    transform: scale(1.15);
  }

  .act:active i {
    color: #334155;
    /* 点击时更深 */
  }


  /* footer */
  .table-ft {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px 14px;
    border-top: 1px solid var(--border)
  }

  .muted {
    color: #8A94A6;
    font-size: 12px
  }

  .select {
    height: 34px;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 0 8px;
    background: #fff
  }

  .pagination {
    display: flex;
    gap: 6px
  }

  .pg {
    min-width: 34px;
    height: 34px;
    border: 1px solid var(--border);
    border-radius: 10px;
    display: grid;
    place-items: center;
    background: #fff
  }

  .pg.active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary)
  }

  @media (max-width: 992px) {
    .metrics {
      grid-template-columns: 1fr
    }

    .tb-row {
      grid-template-columns: 1fr
    }

    .tb-row2 {
      grid-template-columns: 1fr 1fr
    }
  }
</style>

{{-- Toolbar --}}
@php
  // keep current values in the form
  $filters = [
    'order_id' => request('order_id',''),
    'q'        => request('q',''),
    'artist'   => request('artist',''),
    'task'     => request('task',''),
    'status'   => request('status',''),
    'from'     => request('from',''),
    'to'       => request('to',''),
  ];

  // task list (labels)
  $tasks = [
    ''             => 'All Task Types',
    'printing'     => 'Printing',
    'furnishing'   => 'Furnishing',
    'delivery'     => 'Dispatch Controller',
    'installation' => 'Delivery & Installation',
  ];

  // if controller didn’t pass $statuses, provide a sane fallback
  $statuses = $statuses ?? ['pending','in_progress','completed','rejected'];
@endphp

<div class="card mb-4">
  <div class="card-body toolbar">
    <form id="ff-filter-form" method="GET" action="{{ route('admin.fulfillment') }}" class="row g-2 align-items-center">

      {{-- ===== Title + date + buttons ===== --}}
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h4 class="mb-0">Fulfillment Overview</h4>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="input-group" style="max-width:320px;">
            <input type="date" id="fromDate" name="from" class="form-control" value="{{ $filters['from'] }}">
            <span class="input-group-text">~</span>
            <input type="date" id="toDate"   name="to"   class="form-control" value="{{ $filters['to'] }}">
          </div>
          <button type="submit" form="ff-filter-form" class="btn btn-primary px-4">Filter</button>
          <a href="{{ route('admin.fulfillment') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </div>

      {{-- ===== Filter row ===== --}}

      {{-- Product / Order / ID (short) --}}
      <div class="col-12 col-lg-2">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-hash"></i></span>
          <input type="text"
                 name="order_id"
                 class="form-control"
                 placeholder="Enter Product ID"
                 value="{{ $filters['order_id'] }}">
        </div>
      </div>

      {{-- Search job title, company or product (long) --}}
      <div class="col-12 col-lg-4">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input id="ff-search"
                 type="text"
                 name="q"
                 class="form-control"
                 placeholder="Search job title, company or product name"
                 value="{{ $filters['q'] }}">
        </div>
      </div>

      {{-- All assignees (artist) --}}
      <div class="col-12 col-lg-2">
        <select name="artist" class="form-select">
          <option value="">All assignees</option>
          @foreach(($assignees ?? collect()) as $u)
            <option value="{{ $u->id }}" {{ (string)$filters['artist']===(string)$u->id ? 'selected' : '' }}>
              {{ $u->name }}{{ isset($u->role) ? ' ('.$u->role.')' : '' }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Status --}}
      <div class="col-6 col-lg-2">
        @php
          $statusOptions = ['' => 'All Statuses'];
          foreach ($statuses as $s) {
            $label = $s === 'in_progress'
              ? 'In Progress'
              : \Illuminate\Support\Str::of($s)->replace('_',' ')->title();
            $statusOptions[$s] = $label;
          }
        @endphp
        <select id="ff-status" name="status" class="form-select">
          @foreach($statusOptions as $val => $label)
            <option value="{{ $val }}" {{ $filters['status']===$val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      {{-- Task types --}}
      <div class="col-6 col-lg-2">
        <select id="ff-task" name="task" class="form-select">
          @foreach($tasks as $val => $label)
            <option value="{{ $val }}" {{ $filters['task']===$val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>

    </form>
  </div>
</div>

{{-- Fulfillment Table --}}
<div class="card">

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="min-width: 170px;">Product / Order</th>
            <th>Job Title</th>
            <th>Company</th>
            <th>Task Type</th>
            <th>Status</th>
            <th>Delivery Date</th>
            <th>Location</th>
            <th>Install Type</th>
            <th>Outsource Cost</th>
            <th class="text-center">Permit</th>
            <th class="text-end" style="min-width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
          @php
          $statusClass = match(strtolower($r->status)){
          'completed' => 'bg-success',
          'in_progress' => 'bg-info',
          'rejected' => 'bg-danger',
          default => 'bg-secondary'
          };

          $taskLabel  = $r->task_label ?? '-';
          $taskClass  = match (strtolower($taskLabel)) {
            'printing'                 => 'bg-secondary text-white',   // gray
            'furnishing'               => 'bg-purple text-white',      // custom purple (see CSS note below)
            'dispatch control'         => 'bg-warning text-dark',      // yellow
            'delivery & installation'  => 'bg-primary text-white',     // blue
            default                    => 'bg-light text-dark',
          };
          @endphp
          
          <tr>
            <td class="fw-semibold">{{ $r->product_code }}</td>
            <td>{{ $r->order_title ?? '-' }}</td>
            <td>{{ $r->company ?? '-' }}</td>
            <td><span class="badge {{ $taskClass }}">{{ $taskLabel }}</span></td>
            <td><span class="badge {{ $statusClass }}">
                {{ \Illuminate\Support\Str::of($r->status)->replace('_', ' ')->title() ?: '-' }}
              </span></td>

            {{-- Delivery date: show exclamation if missing --}}
            <td>
              @if($r->delivery_dt)
              {{ $r->delivery_dt }}
              @else
              <i class="bi bi-exclamation-circle text-warning" title="Missing delivery date"></i>
              @endif
            </td>

            {{-- Location: show exclamation if missing --}}
            <td>
              @if($r->delivery_loc !== '')
              {{ $r->delivery_loc }}
              @else
              <i class="bi bi-exclamation-circle text-warning" title="Missing delivery location"></i>
              @endif
            </td>

            @php
              $installLabel = match(strtolower((string) $r->install_type)) {
                'in-house', 'in_house', 'in house' => 'In House',
                'outsource'                        => 'Outsource',
                'both'                             => 'Both',
                default                            => ($r->install_type ?: '—'),
              };
            @endphp
            <td>{{ $installLabel }}</td>

            <td>
              @if(!is_null($r->outsource_cost))
              RM {{ number_format($r->outsource_cost, 2) }}
              @else
              —
              @endif
            </td>

            {{-- Permit upload trigger (ensure icon visible) --}}
            <td class="text-center">
              @if(!empty($r->permit_url))
                <a href="{{ route('admin.permits.download', $r->product_id) }}"
                  class="btn btn-icon btn-soft btn-soft-secondary"
                  title="Download permit" data-bs-toggle="tooltip">
                  <i class="bi bi-file-earmark-arrow-down"></i>
                </a>
              @else
                <button type="button" class="btn btn-icon btn-soft btn-soft-primary js-permit"
                        data-bs-toggle="modal" data-bs-target="#permitModal"
                        data-product="{{ $r->product_id }}" title="Upload permit"
                        data-bs-toggle="tooltip">
                  <i class="bi bi-cloud-arrow-up"></i>
                </button>
              @endif
            </td>

            {{-- Actions (restore) --}}
            <td class="text-end">
              <div class="btn-actions">
                <a href="{{ route('admin.fulfillment.product.show', $r->product_id) }}"
                  class="btn btn-icon btn-soft btn-soft-secondary"
                  title="View" data-bs-toggle="tooltip">
                <i class="bi bi-pencil"></i>
                </a>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="11" class="text-center text-muted py-4">No fulfillment rows found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-ft" style="padding: 20px;">
  <nav class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    {{-- Per Page Selector --}}
    <div class="per-page-wrap">
      <form method="GET" class="d-inline-flex align-items-center gap-2 mb-0">
        @foreach(request()->except('per_page') as $k => $v)
          <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <span class="text-muted">Show</span>
        <select name="per_page" class="form-select form-select-sm" style="width: 92px" onchange="this.form.submit()">
          @foreach([10,25,50,100] as $n)
            <option value="{{ $n }}" {{ (int)$per_page === $n ? 'selected' : '' }}>{{ $n }}</option>
          @endforeach
        </select>
        <span class="text-muted">entries</span>
      </form>
    </div>

    {{-- Page Navigator --}}
    <ul class="pagination pill-pager mb-0">
      {{-- Previous --}}
      @if ($rows->onFirstPage())
        <li class="page-item disabled"><span class="page-link">Previous</span></li>
      @else
        <li class="page-item"><a class="page-link" href="{{ $rows->previousPageUrl() }}">Previous</a></li>
      @endif

      @php
        $last = max(1, $rows->lastPage());
        $current = $rows->currentPage();
        $from = max(1, $current - 1);
        $to = min($last, $current + 1);
      @endphp

      {{-- First page + leading dots --}}
      @if ($from > 1)
        <li class="page-item"><a class="page-link" href="{{ $rows->url(1) }}">1</a></li>
        @if ($from > 2)
          <li class="page-item disabled"><span class="page-link">…</span></li>
        @endif
      @endif

      {{-- Main window --}}
      @for ($p = $from; $p <= $to; $p++)
        @if ($p == $current)
          <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
        @else
          <li class="page-item"><a class="page-link" href="{{ $rows->url($p) }}">{{ $p }}</a></li>
        @endif
      @endfor

      {{-- Trailing dots + last page --}}
      @if ($to < $last)
        @if ($to < $last - 1)
          <li class="page-item disabled"><span class="page-link">…</span></li>
        @endif
        <li class="page-item"><a class="page-link" href="{{ $rows->url($last) }}">{{ $last }}</a></li>
      @endif

      {{-- Next --}}
      @if ($rows->hasMorePages())
        <li class="page-item"><a class="page-link" href="{{ $rows->nextPageUrl() }}">Next</a></li>
      @else
        <li class="page-item disabled"><span class="page-link">Next</span></li>
      @endif
    </ul>
  </nav>
</div>
  </div>
</div>

{{-- Permit upload modal (front-end only) --}}
<div class="modal fade" id="permitModal" tabindex="-1" aria-labelledby="permitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="permitForm" method="POST" action="{{ route('admin.fulfillment.permit.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="permitModalLabel">Upload Permit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="product_id" id="pmProductId">
          <div class="mb-3">
            <label class="form-label">File (image or PDF)</label>
            <input type="file" name="permit" id="pmFile" class="form-control" accept="image/*,.pdf" required />
            <div class="form-text">Accepted: JPG/PNG/GIF/WebP/PDF (max 20MB)</div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="pmSubmit" class="btn btn-primary" disabled>Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- 日期区间 --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
  $(function() {
    const $dr = $('#dateRange');
    if ($dr.length) {
      $dr.daterangepicker({
        autoUpdateInput: !!$dr.val(),
        locale: {
          format: 'DD/MM/YYYY',
          cancelLabel: 'Clear'
        }
      });
      $dr.on('cancel.daterangepicker', function() {
        $(this).val('');
      });
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    [...document.querySelectorAll('[data-bs-toggle="tooltip"]')]
    .forEach(el => new bootstrap.Tooltip(el));
  });

  // Keep your existing modal-fill logic
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-permit');
    if (!btn) return;
    document.getElementById('pmProduct').textContent = btn.dataset.product || '-';
    document.getElementById('pmBreakdown').textContent = btn.dataset.breakdown || '-';
  });

  document.addEventListener('DOMContentLoaded', () => {
    // tooltips
    [...document.querySelectorAll('[data-bs-toggle="tooltip"]')]
      .forEach(el => new bootstrap.Tooltip(el));

    // Fill modal with product id
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.js-permit');
      if (!btn) return;
      document.getElementById('pmProductId').value = btn.dataset.product || '';
      document.getElementById('pmFile').value = '';
      document.getElementById('pmSubmit').disabled = true;
    });

    // enable submit when a file is chosen
    const pmFile = document.getElementById('pmFile');
    if (pmFile) {
      pmFile.addEventListener('change', () => {
        document.getElementById('pmSubmit').disabled = !pmFile.files.length;
      });
    }
  });

  function onPermitUploaded(productId, fileUrl){
    const cell = document.querySelector(
      `button.js-permit[data-product="${productId}"]`
    )?.parentElement;
    if(!cell) return;

    cell.innerHTML = `
      <a href="${fileUrl}" class="btn btn-icon btn-soft btn-soft-secondary"
        target="_blank" download title="View permit" data-bs-toggle="tooltip">
        <i class="bi bi-eye"></i>
      </a>`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const fromInput = document.getElementById('fromDate');
    const toInput   = document.getElementById('toDate');

    function syncMin() {
      if (fromInput.value) {
        toInput.min = fromInput.value;
        if (toInput.value && toInput.value < fromInput.value) {
          toInput.value = fromInput.value;
        }
      } else {
        toInput.removeAttribute('min');
      }
    }
    fromInput?.addEventListener('change', syncMin);
    syncMin();
</script>
@endsection
@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  /* ===== Cards (subtle) ===== */
  .stat-card {
    border: 1px solid #E7EAEE;
    border-radius: 14px;
    background: #fff;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .05)
  }

  .stat-card .num {
    font-size: 34px;
    font-weight: 800;
    letter-spacing: -.3px
  }

  .stat-card .label {
    color: #6B7280;
    font-weight: 600
  }

  /* ===== Table sizing + zebra + sticky head ===== */
  .table-progress {
    table-layout: fixed
  }

  .table-progress col.col-id {
    width: 170px
  }

  .table-progress col.col-stage {
    width: 18%
  }

  .table-progress col.col-date {
    width: 140px
  }

  .table-progress col.col-deadline {
    width: 140px
  }

  .table-progress col.col-actions {
    width: 120px
  }

  .table-progress thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #F8FAFC;
    font-size: 12px;
    color: #475467;
    font-weight: 700;
    border-bottom: 1px solid #EDF1F6
  }

  .table-progress tbody tr:nth-child(odd) {
    background: #FCFCFD
  }

  .table-progress td,
  .table-progress th {
    padding: 14px 14px;
    vertical-align: middle
  }

  .table-progress tbody tr:hover {
    background: #FAFBFF
  }

  /* ===== Unified pipeline ===== */
  .pipeline {
    position: relative;
    height: 18px;
  }

  .pipeline .track {
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 6px;
    transform: translateY(-50%);
    border-radius: 999px;
    background: #E5E7EB;
  }

  .pipeline .fill {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    height: 6px;
    border-radius: 999px;
    background: #12B76A;
    left: var(--start, 0%);
    /* NEW: where the green segment begins */
    width: calc(var(--end, 0%) - var(--start, 0%));
    /* NEW: segment length */
  }

  .dot {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #12B76A;
    box-shadow: 0 0 0 2px #fff;
  }

  .dot.gray {
    background: #98A2B3;
  }

  .dot.red {
    background: #F04438;
  }

  .dot.p1 {
    left: 12.5%
  }

  .dot.p2 {
    left: 37.5%
  }

  .dot.p3 {
    left: 62.5%
  }

  .dot.p4 {
    left: 87.5%
  }

  /* Node positions */
  .dot.p1 {
    left: 12.5%
  }

  .dot.p2 {
    left: 37.5%
  }

  .dot.p3 {
    left: 62.5%
  }

  .dot.p4 {
    left: 87.5%
  }

  /* Actions (compact, consistent) */
  .action-btn {
    width: 34px;
    height: 34px;
    border: 1px solid #D6DAE1;
    border-radius: 10px;
    background: #fff;
    color: #475467;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: .15s
  }

  .action-btn:hover {
    background: #EEF2F7;
    color: #1F2937
  }

  /* ===== Pagination: footed + centered ===== */
  .card-ft {
    padding: 12px 16px;
    border-top: 1px solid #EDF1F6;
    background: #fff
  }

  .pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin: 0
  }

  .pagination li {
    display: inline-block
  }

  .pagination .page-link {
    color: #475467;
    border: 1px solid #D0D5DD;
    border-radius: 10px;
    padding: 8px 12px;
    background: #fff;
    font-weight: 600;
    font-size: 13px;
    line-height: 1;
    transition: .15s
  }

  .pagination .page-link:hover {
    background: #F2F4F7;
    border-color: #CBD3DD
  }

  .pagination .active .page-link {
    background: #12B76A;
    border-color: #12B76A;
    color: #fff
  }

  .pagination .disabled .page-link {
    color: #A3AAB5;
    background: #F8FAFB;
    border-color: #E5E7EB;
    cursor: not-allowed
  }

  .table-responsive+nav[role="navigation"] {
    display: none !important;
  }

  /* tighten any paginator margins globally */
  nav[role="navigation"] {
    margin: 0 !important;
  }
</style>

<div class="container-fluid py-4 px-4" style="max-width:1200px;margin:0 auto">
  <h1 class="h4 fw-bold mb-3" style="letter-spacing:-.2px">Dashboard Overview</h1>

  {{-- KPIs --}}
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="stat-card">
        <div>
          <div class="label mb-1">In Progress</div>
          <div class="num">{{ $inProgress }}</div>
        </div>
        <i class="bi bi-clock fs-3 text-secondary"></i>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="stat-card">
        <div>
          <div class="label mb-1">Completed</div>
          <div class="num">{{ $completed }}</div>
        </div>
        <i class="bi bi-check2 fs-3 text-success"></i>
      </div>
    </div>
  </div>

  {{-- Production Status --}}
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0 fw-semibold">Production Status</h5>
        <small class="text-muted">Sorted by least progress</small>
      </div>

      <div class="table-responsive">
        <table class="table table-progress align-middle mb-0">
          <colgroup>
            <col class="col-id">
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-date">
            <col class="col-deadline">
            <col class="col-actions">
          </colgroup>

          <thead>
            <tr>
              <th>PRODUCT ID</th>
              <th>PRINTING</th>
              <th>FURNISHING</th>
              <th>DISPATCH CONTROL</th>
              <th>DELIVERY & INSTALLATION</th>
              <th>DATE IN</th>
              <th>DEADLINE</th>
              <th class="text-center">ACTIONS</th>
            </tr>
          </thead>

          <tbody>
            @php
            // constant maps
            $STAGES = ['printing','furnishing','delivery','installation'];
            $POS = ['printing'=>12.5,'furnishing'=>37.5,'delivery'=>62.5,'installation'=>87.5];
            $DOT = ['printing'=>'p1','furnishing'=>'p2','delivery'=>'p3','installation'=>'p4'];
            // helper to render a dot class or skip entirely if stage missing
            $dotClass = function(array $p, string $stage) use ($DOT) {
            if (!isset($p['stages'][$stage])) return null; // skip dot for missing stage
            $s = $p['stages'][$stage]['status'] ?? null;
            $posClass = $DOT[$stage];
            if ($s === 'completed') return "dot {$posClass}";
            if ($s === 'rejected') return "dot red {$posClass}";
            return "dot gray {$posClass}";
            };
            @endphp

            @forelse ($rows as $r)
            @php
            // stages that actually exist for this row
            $visible = array_values(array_filter($STAGES, fn($s)=> isset($r['stages'][$s])));
            // start at first present stage (handles “skip printing” → start at furnishing)
            $first = $visible[0] ?? null;
            $start = $first ? $POS[$first] : 0;

            // determine end of the green segment
            $rej = null;
            foreach ($visible as $s) {
            if (($r['stages'][$s]['status'] ?? null) === 'rejected') { $rej = $s; break; }
            }

            if ($rej) {
            $end = $POS[$rej]; // stop at rejected node
            } else {
            $lastCompleted = null;
            foreach ($visible as $s) {
            if (($r['stages'][$s]['status'] ?? null) === 'completed') { $lastCompleted = $s; }
            }
            if ($lastCompleted) {
            $end = ($lastCompleted === 'installation') ? 100 : $POS[$lastCompleted];
            } else {
            $end = $start; // nothing completed → no green segment
            }
            }

            // dates
            $dateIn = $r['orderDate'] ? \Carbon\Carbon::parse($r['orderDate'])->format('Y-m-d') : '—';
            $deadline = $r['deadline'] ? \Carbon\Carbon::parse($r['deadline'])->format('Y-m-d') : '—';
            @endphp

            <tr>
              <td>{{ $r['product_code'] }}</td>
              <td colspan="4">
                <div class="pipeline" style="--start:{{ $start }}%; --end:{{ $end }}%;">
                  <div class="track"></div>
                  <div class="fill"></div>
                  @php $d = $dotClass($r,'printing'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                  @php $d = $dotClass($r,'furnishing'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                  @php $d = $dotClass($r,'delivery'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                  @php $d = $dotClass($r,'installation'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                </div>
              </td>
              <td>{{ $dateIn }}</td>
              <td>{{ $deadline }}</td>
              <td class="text-center">
                <div class="d-inline-flex gap-1">
                  <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
                  <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
                  <button class="action-btn" title="Done"><i class="bi bi-check2"></i></button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">No data.</td>
            </tr>
            @endforelse
          </tbody>

        </table>
      </div>
    </div>

    {{-- Pagination pinned to card foot --}}
    @if ($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="card-ft">
      {{-- Bootstrap-5 numeric pager, 1 neighbor on each side --}}
      {{ $rows->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
    @endif

  </div>
</div>
@endsection
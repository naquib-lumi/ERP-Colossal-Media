@extends('layouts.app')

@section('content')
@push('styles')
<style>
    /* Table look like Figma */
    .table-modern thead th {
        background: #fbfbfc;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: .74rem;
        color: #6c7680;
        border-bottom: 1px solid #eceff3 !important;
    }

    .table-modern tbody td {
        vertical-align: middle;
        border-color: #f1f3f6 !important;
    }

    .table-modern tbody tr:hover td {
        background: #fafbfc;
    }

    /* Status pills */
    .badge-status {
        padding: .35rem .6rem;
        font-weight: 600;
        border-radius: 999px;
        font-size: .75rem;
    }

    /* Tiny icon buttons */
    .btn-icon {
        --size: 32px;
        width: var(--size);
        height: var(--size);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: .5rem;
    }

    /* Datatables nav bottom-right */
    div.dataTables_wrapper .dataTables_paginate {
        float: right;
    }

    div.dataTables_wrapper .dataTables_info {
        padding-top: .75rem;
        color: #6c7680;
    }

    div.dataTables_wrapper .dataTables_length {
        display: none;
        /* hide “Show N entries” to match figma */
    }

    div.dataTables_wrapper .dataTables_filter {
        display: none;
        /* we’re using the custom search input */
    }

    .d-flex.flex-nowrap>* {
        flex-shrink: 0;
    }

    /* Ensure the DT table/wrappers actually span full width of the card */
    table.dataTable {
        width: 100% !important;
    }

    .card-datatable .dataTables_wrapper,
    .card-datatable .dataTables_scroll,
    .card-datatable .dataTables_scrollHead,
    .card-datatable .dataTables_scrollBody {
        width: 100% !important;
    }

    /* Optional: remove extra row margins inside wrapper so it looks flush */
    .card-datatable .dataTables_wrapper .row {
        margin: 0;
    }

    .table-modern thead th {
        background: #fbfbfc;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: .74rem;
        color: #6c7680;
        border-bottom: 1px solid #eceff3 !important;
    }

    .table-modern tbody td {
        vertical-align: middle;
        border-color: #f1f3f6 !important;
    }

    .table-modern tbody tr:hover td {
        background: #fafbfc;
    }

    .btn-outline-secondary.btn-icon {
        border-color: #e5e7eb
    }

    .dataTables_length,
    .dataTables_filter {
        display: none
    }

    .dataTables_paginate {
        float: right
    }

    table.dataTable {
        width: 100% !important
    }

    .card-datatable .dataTables_wrapper,
    .card-datatable .dataTables_scroll,
    .card-datatable .dataTables_scrollHead,
    .card-datatable .dataTables_scrollBody {
        width: 100% !important
    }

    .card-datatable .dataTables_wrapper .row {
        margin: 0
    }
</style>
@endpush

<div class="container py-4">
    <h3 class="mb-4">Dashboard Overview</h3>

    <!-- Content wrapper -->
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-p-y">
            <!-- Product List Widget -->
            <div class="card mb-6">
                <div class="card-widget-separator-wrapper">
                    <div class="card-body card-widget-separator">
                        <div class="row gy-4 gy-sm-1">

                            <div class="col-sm-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                                <div>
                                <p class="mb-1">Total Order</p>
                                <h4 class="mb-1">{{ $metrics['total'] ?? 0 }}</h4>
                                </div>
                                <span class="avatar me-sm-6">
                                <span class="avatar-initial rounded w-px-44 h-px-44">
                                    <i class="icon-base bx bx-store-alt icon-lg text-heading"></i>
                                </span>
                                </span>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-6" />
                            </div>

                            <div class="col-sm-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                                <div>
                                <p class="mb-1">Pending</p>
                                <h4 class="mb-1">{{ $metrics['pending'] ?? 0 }}</h4>
                                </div>
                                <span class="avatar me-sm-6">
                                <span class="avatar-initial rounded w-px-44 h-px-44">
                                    <i class="icon-base bx bx-laptop icon-lg text-heading"></i>
                                </span>
                                </span>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-6" />
                            </div>

                            <div class="col-sm-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                                <div>
                                <p class="mb-1">In Progress</p>
                                <h4 class="mb-1">{{ $metrics['in_progress'] ?? 0 }}</h4>
                                </div>
                                <span class="avatar p-2 me-sm-6">
                                <span class="avatar-initial rounded w-px-44 h-px-44">
                                    <i class="icon-base bx bx-gift icon-lg text-heading"></i>
                                </span>
                                </span>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-6" />
                            </div>

                            <div class="col-sm-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
                                <div>
                                <p class="mb-1">Completed</p>
                                <h4 class="mb-1">{{ $metrics['completed'] ?? 0 }}</h4>
                                </div>
                                <span class="avatar p-2 me-sm-6">
                                <span class="avatar-initial rounded w-px-44 h-px-44">
                                    <i class="icon-base bx bx-wallet icon-lg text-heading"></i>
                                </span>
                                </span>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-6" />
                            </div>

                            <div class="col-sm-6 col-lg">
                            <div class="d-flex justify-content-between align-items-start pb-4 pb-sm-0">
                                <div>
                                <p class="mb-1">Rejected</p>
                                <h4 class="mb-1">{{ $metrics['rejected'] ?? 0 }}</h4>
                                </div>
                                <span class="avatar p-2 me-sm-6">
                                <span class="avatar-initial rounded w-px-44 h-px-44">
                                    <i class="icon-base bx bx-x-circle icon-lg text-danger"></i>
                                </span>
                                </span>
                            </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Orders Table -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="mb-0">Job Orders</h5>

                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                            <!-- Search -->
                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text bg-white border-end-0"><i class="bx bx-search"></i></span>
                                <input id="jobSearch" type="text" class="form-control border-start-0" placeholder="Search orders…">
                            </div>

                            <!-- Status filter -->
                            <select id="statusFilter" class="form-select w-auto">
                                <option value="">All Status</option>
                                <option value="to_assign">To Assign</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="rejected">Rejected</option>
                            </select>

                            <!-- Export -->
                            <button id="exportExcel" class="btn btn-dark">
                                <i class="bx bx-export me-1"></i> Export
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="jobOrdersTable" class="table table-modern table-hover w-100">
                            @php $isHead = auth()->user()->role === 'head-artist'; @endphp
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>{{ $isHead ? 'Artist' : 'Salesperson' }}</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                <tr>
                                    <td>#ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $order->orderTitle ?? '-' }}</td>
                                    <td>{{ $order->companyName ?? '-' }}</td>
                                    <td>
                                        @if ($isHead)
                                            {{ optional($order->artist)->name ?? '-' }}
                                        @else
                                            {{ optional($order->salesperson)->name ?? '-' }}
                                        @endif
                                    </td>                                    
                                    @php
                                        $key = strtolower($order->orderStatus ?? '');
                                        $map = [
                                            'to_assign'   => ['To Assign',   'bg-label-secondary'],
                                            'assigned'    => ['Assigned',    'bg-label-info'],
                                            'in_progress' => ['In Progress', 'bg-label-primary'],
                                            'pending'     => ['Pending',     'bg-label-warning'],
                                            'completed'   => ['Completed',   'bg-label-success'],
                                            'rejected'    => ['Rejected',    'bg-label-danger'],
                                        ];
                                        [$label, $cls] = $map[$key] ?? [$order->orderStatus ?? '-', 'bg-label-secondary'];
                                    @endphp
                                    <td><span class="badge {{ $cls }}">{{ $label }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($order->deadline)->format('M d, Y') }}</td>

                                    <td class="text-end">
                                        <button class="btn btn-outline-secondary btn-icon" title="View">
                                            <i class="bx bx-show"></i>
                                        </button>
                                        <a href="{{ route('artist.orders.edit', $order->id) }}" class="btn btn-outline-secondary btn-icon" title="Edit">
                                            <i class="bx bx-edit-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-4">
                <!-- Salesperson Meeting Status Report -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Salesperson Meeting Status</h6>

                                <select id="salespersonFilter" class="form-select w-auto">
                                <option value="">All Salespersons</option>
                                @foreach ($salespersons as $sp)
                                    <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                                @endforeach
                                </select>
                            </div>

                            <div id="meetingStatusChart"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6>Job Order Fulfillment Report</h6>
                            <div id="fulfillmentChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
<script>
  (function() {
    // Bail if jQuery is missing
    if (!window.jQuery) { console.error('jQuery not loaded → DataTables will not init'); return; }

    $(function () {
      // ===== Helpers for status pill =====
      const statusMap = {
        to_assign: { label: 'To Assign',    cls: 'assign'    },
        assigned:  { label: 'Assigned',     cls: 'assign'    },
        in_progress:{label: 'In Progress',  cls: 'progress'  },
        pending:   { label: 'Pending',      cls: 'progress'  },
        completed: { label: 'Completed',    cls: 'completed' },
        rejected:  { label: 'Rejected',     cls: 'rejected'  }
      };
      const pill = raw => {
        const key = String(raw || '').toLowerCase();
        const m = statusMap[key] || { label: raw || '-', cls: 'progress' };
        return `<span class="badge-status badge-${m.cls}">${m.label}</span>`;
      };

      // ===== DataTables init (guarded) =====
      let dt;
      try {
        if (!$.fn.DataTable) throw new Error('DataTables plugin not loaded');

        dt = $('#jobOrdersTable').DataTable({
          dom: 'Brt<"d-flex justify-content-between align-items-center mt-3"ip>',
          paging: true,
          pageLength: 5,
          autoWidth: false,
          responsive: true,
          order: [],
          buttons: [{
            extend: 'excel',
            title: 'Job Orders',
            className: 'd-none',
            exportOptions: { columns: [0,1,2,3,4,5] }
          }],
          columnDefs: [
            { // status pill
              targets: 4,
              createdCell: function (td, cellData) {
                $(td).html(pill(cellData));
              }
            },
            { // actions
              targets: -1,
              orderable: false,
              searchable: false,
              className: 'text-end'
            }
          ],
          language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ results',
            paginate: { previous: 'Previous', next: 'Next' }
          },
          drawCallback: function() {
            this.api().columns.adjust().responsive.recalc();
          }
        });

        // External controls
        $('#jobSearch').on('keyup', function () { dt.search(this.value).draw(); });
        $('#statusFilter').on('change', function () { dt.column(4).search(this.value).draw(); });
        $('#exportExcel').on('click', function () { dt.button(0).trigger(); });
        window.addEventListener('resize', () => setTimeout(() => dt.columns.adjust().responsive.recalc(), 100));

      } catch (e) {
        console.error('Failed to initialize DataTables:', e);
      }

      // ===== Apex donut: Scheduled vs Canceled (meetings) =====
      try {
        if (!window.ApexCharts) { console.warn('ApexCharts not loaded, skipping chart'); return; }

        // Colors fallback (works even if you don’t have a theme object)
        const colors = {
          success: '#26ee6fff',
          danger:  '#ff5d5dff',
          text:    '#6c7680',
          card:    '#ffffff'
        };

        const el = document.querySelector('#meetingStatusChart');
        if (!el) return;

        const apex = new ApexCharts(el, {
          chart: { type: 'donut', height: 290 },
          labels: ['Scheduled', 'Canceled'],
          series: [0, 0], // will update via AJAX below
          colors: [colors.success, colors.danger],
          stroke: { width: 2, colors: [colors.card] },
          dataLabels: { enabled: true,
                        formatter: (val, opts) => `${Math.round(val)}%`, // val is already the % for donut
                        style: {
                        fontSize: '14px',
                        fontWeight: 700,
                        colors: ['#fff'] // white text over the colored slices
                        },
                        dropShadow: {
                        enabled: true,
                        blur: 2,
                        opacity: 0.6
                        } 
                    },
          legend: {
            position: 'bottom',
            labels: { colors: colors.text },
            markers: { width: 10, height: 10, radius: 10 }
          },
          plotOptions: {
            pie: {
                dataLabels: {
                    offset: -6 // nudge labels toward the ring so they sit nicely on slices
                },
                donut: {
                    size: '50%',
                    labels: { show: true } // keep your center labels if you use them
                }
            }
          },
          tooltip: { y: { formatter: v => `${v} meetings` } }
        });
        apex.render();

        // Loader for counts
        const loadCounts = (salespersonId = '') => {
          $.getJSON('{{ route('artist.meetingStatusCounts') }}', { salesperson_id: salespersonId })
            .done(res => {
              const s = Number(res && res.scheduled) || 0;
              const c = Number(res && res.canceled)  || 0;
              apex.updateSeries([s, c]);
            })
            .fail(() => console.warn('Failed to load meeting status counts'));
        };

        // initial load (all)
        loadCounts('');

        // dropdown change (make sure your select id matches)
        $('#salespersonFilter').on('change', function() {
          loadCounts(this.value);
        });

        // Optional: refresh when table draws (not necessary for server data)
        // $('#jobOrdersTable').on('draw.dt', () => loadCounts($('#salespersonFilter').val()));

      } catch (e) {
        console.error('Failed to build Apex chart:', e);
      }
    });
  })();
</script>
@endpush


        @endsection
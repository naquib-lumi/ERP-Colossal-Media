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

    #fulfillmentChart .chartjs {
        max-height: none !important;
        min-height: 0 !important;
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
                        @if(!empty($isHead) && $isHead)
                            <div class="row gy-4 gy-sm-1">
                                {{-- To assign --}}
                                <div class="col-sm-6 col-lg">
                                <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                                    <div>
                                    <p class="mb-1">To assign</p>
                                    <h4 class="mb-1">{{ $metrics['to_assign'] ?? 0 }}</h4>
                                    </div>
                                    <span class="avatar me-sm-6">
                                    <span class="avatar-initial rounded w-px-44 h-px-44">
                                        <i class="icon-base bx bx-share-alt icon-lg text-heading"></i>
                                    </span>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-6" />
                                </div>

                                {{-- Assigned --}}
                                <div class="col-sm-6 col-lg">
                                <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                                    <div>
                                    <p class="mb-1">Assigned</p>
                                    <h4 class="mb-1">{{ $metrics['assigned'] ?? 0 }}</h4>
                                    </div>
                                    <span class="avatar me-sm-6">
                                    <span class="avatar-initial rounded w-px-44 h-px-44">
                                        <i class="icon-base bx bx-user-check icon-lg text-heading"></i>
                                    </span>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-6" />
                                </div>

                                {{-- In progress --}}
                                <div class="col-sm-6 col-lg">
                                <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                                    <div>
                                    <p class="mb-1">In Progress</p>
                                    <h4 class="mb-1">{{ $metrics['in_progress'] ?? 0 }}</h4>
                                    </div>
                                    <span class="avatar p-2 me-sm-6">
                                    <span class="avatar-initial rounded w-px-44 h-px-44">
                                        <!-- <i class="icon-base bx bx-gift icon-lg text-heading"></i> -->
                                    </span>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-6" />
                                </div>

                                {{-- Completed --}}
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

                                {{-- Rejected --}}
                                <div class="col-sm-6 col-lg">
                                <div class="d-flex justify-content-between align-items-start pb-4 pb-sm-0">
                                    <div>
                                    <p class="mb-1">Rejected</p>
                                    <h4 class="mb-1">{{ $metrics['rejected'] ?? 0 }}</h4>
                                    </div>
                                    <span class="avatar p-2 me-sm-6">
                                    <span class="avatar-initial rounded w-px-44 h-px-44">
                                        <i class="icon-base bx bx-x-circle icon-lg text-heading"></i>
                                    </span>
                                    </span>
                                </div>
                                </div>
                            </div>
                        @else
                            {{-- Normal artist (your current block) --}}
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
                                        <i class="icon-base bx bx-x-circle icon-lg text-heading"></i>
                                    </span>
                                    </span>
                                </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Job Orders Table -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="mb-0">Job Orders</h5>

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text bg-white border-end-0"><i class="bx bx-search"></i></span>
                                <input id="jobSearch" type="text" class="form-control border-start-0" placeholder="Search orders…">
                            </div>

                            <select id="statusFilter" class="form-select w-auto">
                                <option value="">All statuses</option>
                                @if(!$isHead ?? false)
                                <option value="pending">Pending</option>
                                @endif
                                <option value="in_progress">In progress</option>
                                <option value="completed">Completed</option>
                                <option value="rejected">Rejected</option>

                                {{-- head artist only --}}
                                @if($isHead ?? false)
                                <option value="to_assign">To assign</option>
                                <option value="assigned">Assigned</option>
                                @endif
                            </select>

                            <button id="exportExcel" class="btn btn-dark">
                                <i class="bx bx-export me-1"></i> Export
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive" id="orders-table-wrapper">
                        @include('artist.partials.orders-table', ['orders' => $orders])
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
        let dt = null;

        // remove previous custom filter if any
        function clearDtFilters() {
            if (!$.fn || !$.fn.dataTable) return;
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search
                .filter(fn => !fn._ordersStatusFilter);
        }
        const wrap = document.getElementById('orders-table-wrapper');
        const status = document.getElementById('statusFilter');
        const search = document.getElementById('jobSearch');

        // --- state kept in URL (no 'page' anymore: DataTables paginates client-side)
        const qs = new URLSearchParams(location.search);
        const params = {
            status: qs.get('status') || (status?.value ?? ''),
            q: qs.get('q') || (search?.value ?? '')
        };
        if (status) status.value = params.status;
        if (search) search.value = params.q;

        let inflight; // AbortController for fetch cancellation
        const cache = new Map(); // tiny cache for HTML snippets

        function setUrl() {
            const url = new URL(location.href);
            url.searchParams.delete('status');
            url.searchParams.delete('q');
            if (params.status) url.searchParams.set('status', params.status);
            if (params.q) url.searchParams.set('q', params.q);
            history.replaceState(null, '', url.pathname + (url.search ? url.search : ''));
        }

        function keyFor(p) {
            const k = new URLSearchParams();
            if (p.status) k.set('status', p.status);
            if (p.q) k.set('q', p.q);
            return k.toString();
        }

        function buildUrl(p) {
            const base = @json(route('artist.orders'));
            const url = new URL(base, location.origin);
            if (p.status) url.searchParams.set('status', p.status);
            if (p.q) url.searchParams.set('q', p.q);
            return url.toString();
        }

        function debounce(fn, ms = 300) {
            let t;
            return (...a) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...a), ms);
            };
        }

        // ---- DataTables (re)initialization
        function initDataTable() {
            if (!window.jQuery || !$.fn.DataTable) return;

            // Destroy previous instance safely
            if (dt) {
                dt.destroy();
                dt = null;
            }
            clearDtFilters();

            // IMPORTANT: assign to outer 'dt', don't redeclare with 'const' or 'let' here
            dt = $('#jobOrdersTable').DataTable({
                dom: '<"d-flex justify-content-between align-items-center"lB>rt<"d-flex justify-content-between align-items-center"ip>',
                paging: true,
                pageLength: 5,
                lengthMenu: [
                    [5, 10, 20, 30],
                    [5, 10, 20, 30]
                ],
                autoWidth: false,
                responsive: true,
                order: [],
                buttons: [{
                    extend: 'excel',
                    title: 'Job Orders',
                    className: 'd-none',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                }],
                columnDefs: [
                    { targets: -1, orderable: false, searchable: false, className: 'text-end' },
                    // (optional) safety: fill blanks instead of warning if a cell is missing
                    { targets: '_all', defaultContent: '' }
                ],
                language: {
                    lengthMenu: 'Show _MENU_',
                    emptyTable: 'No matching records found',
                    zeroRecords: 'No matching records found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ results',
                    infoEmpty: 'Showing 0 to 0 of 0 results',
                    paginate: { previous: 'Previous', next: 'Next' }
                },
                drawCallback: function() {
                    this.api().columns.adjust().responsive.recalc();
                }
            });

            // --- custom filter by data-status-code
            const filterFn = function(settings, data, dataIndex) {
                const desired = (document.getElementById('statusFilter')?.value || '').toLowerCase();
                if (!desired) return true; // no filter
                const node = dt.row(dataIndex).node();
                const code = (node.querySelector('td[data-status-code]')?.dataset.statusCode || '').toLowerCase();
                return code === desired;
            };
            filterFn._ordersStatusFilter = true; // tag so we can remove it cleanly
            $.fn.dataTable.ext.search.push(filterFn);

            // external controls
            $('#jobSearch').off('keyup.dt').on('keyup.dt', function() {
                dt.search(this.value).draw();
            });

            $('#statusFilter').off('change.dt').on('change.dt', function() {
                dt.draw(); // just redraw; the custom filter uses the dropdown’s value
            });

            $('#exportExcel').off('click.dt').on('click.dt', function() {
                dt.button(0).trigger();
            });

            // apply current external values
            const s = document.getElementById('jobSearch');
            if (s && s.value) dt.search(s.value).draw();
            const f = document.getElementById('statusFilter');
            if (f && f.value) dt.draw();
        }

        async function load(force = false) {
            const k = keyFor(params);
            setUrl();

            if (cache.has(k) && !force) {
                wrap.innerHTML = cache.get(k);
                initDataTable();
                return;
            }

            // cancel previous request
            inflight?.abort?.();
            inflight = new AbortController();

            wrap.style.opacity = 0.6;
            try {
                const res = await fetch(buildUrl(params), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: inflight.signal
                });
                const html = await res.text();
                cache.set(k, html);
                wrap.innerHTML = html;
                initDataTable(); // ← re-init after content swap
            } catch (e) {
                if (e.name !== 'AbortError') console.error(e);
            } finally {
                wrap.style.opacity = 1;
            }
        }

        // initial render
        load(true);

        // status -> change (fetch new HTML, then DT paginates client-side)
        status?.addEventListener('change', () => {
            params.status = status.value || '';
            load();
        });

        // search -> input (server fetch; DT still paginates the new set)
        search?.addEventListener('input', debounce(() => {
            params.q = search.value.trim();
            load();
        }, 300));

    // Fulfillment bar chart 
    if (!window.Chart) return;

    const wrapFulfillment = document.getElementById('fulfillmentChart');
    if (!wrapFulfillment) return;

    // fixed-height wrapper (align with donut)
    wrapFulfillment.innerHTML = `
        <div id="fulfillmentChartWrap" class="position-relative" style="height:280px;">
        <canvas id="fulfillmentBar" class="chartjs" style="width:100%;height:100%;"></canvas>
        </div>
    `;

    // status colors (match theme look)
    const STATUS = {
        completed:   { label: 'Completed',   color: '#28c76f' },
        in_progress: { label: 'In Progress', color: '#00cfe8' },
        pending:     { label: 'Pending',     color: '#ff9f43' },
        rejected:    { label: 'Rejected',    color: '#ea5455' }
    };

    fetch(@json(route('artist.fulfillmentCounts')))
        .then(r => r.json())
        .then(res => {
        // allow backward compat if API still returns plain totals
        const totals    = res.totals    ?? res;
        const breakdown = res.breakdown ?? { printing:{}, furnishing:{}, installation:{} };

        const labels = ['Printing','Furnishing','Installation'];
        const keys   = ['printing','furnishing','installation'];
        const counts = [totals.printing||0, totals.furnishing||0, totals.installation||0];

        const border = ['#27AE60','#2D9CDB','#F2C94C'];
        const bg     = ['rgba(39,174,96,0.65)','rgba(45,156,219,0.65)','rgba(242,201,76,0.65)'];

        const max = Math.max(...counts);
        const suggestedMax = Math.ceil((max || 5)/5)*5;

        new Chart(document.getElementById('fulfillmentBar').getContext('2d'), {
            type: 'bar',
            data: { labels, datasets: [{
            label: 'Orders',
            data: counts,
            backgroundColor: bg,
            borderColor: border,
            borderWidth: 1,
            borderRadius: 8,
            barPercentage: 0.55,
            categoryPercentage: 0.5
            }]},
            options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => `${c.parsed.y} orders` } }
            },
            scales: {
                x: { grid: { display:false }, ticks: { font:{ weight:600 } } },
                y: { beginAtZero:true, suggestedMax, ticks:{ stepSize:1 } }
            }
            }
        });

        // ------- captions with status-color indicators -------
        const cap = document.createElement('div');
        cap.className = 'd-flex justify-content-around pt-3';
        cap.innerHTML = keys.map((k, idx) => {
            const b = breakdown[k] || {};
            // build little colored-dot chips for non-zero statuses
            const chips = Object.entries(STATUS)
            .filter(([s]) => (b[s] ?? 0) > 0)
            .map(([s, meta]) => `
                <span class="d-inline-flex align-items-center me-3 mb-1">
                <span style="width:10px;height:10px;background:${meta.color};border-radius:50%;display:inline-block;margin-right:6px;"></span>
                <span class="text-muted">${meta.label} ${b[s]}</span>
                </span>
            `).join('');

            return `
            <div class="text-center" style="min-width:160px;">
                <span class="badge rounded-pill" style="background:${border[idx]};width:16px;height:16px;"></span>
                <div class="fw-semibold mt-1">${labels[idx]}</div>
                <div class="text-muted">${counts[idx]} orders</div>
                <div class="d-flex justify-content-center flex-wrap mt-1">${chips || '<span class="text-muted">No status</span>'}</div>
            </div>
            `;
        }).join('');
        wrapFulfillment.appendChild(cap);
    });

    // -----------------------------------------------

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
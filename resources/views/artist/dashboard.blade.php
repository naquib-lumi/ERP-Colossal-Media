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

    .filters-hz{
        display:flex; align-items:center; gap:.5rem;
        white-space:nowrap; overflow-x:auto; overscroll-behavior-x:contain;
        padding:.5rem .75rem; background:#fff;
        border:1px solid rgba(0,0,0,.08); border-radius:12px;
        box-shadow:0 1px 3px rgba(16,24,40,.04);
    }
    .filters-hz::-webkit-scrollbar{ height:8px; }
    .filters-hz::-webkit-scrollbar-thumb{ background:#e6e9ed; border-radius:8px; }

    .hz-field{
        display:flex; align-items:center; gap:.4rem;
        padding:.35rem .6rem; background:#fff;
        border:1px solid rgba(0,0,0,.08); border-radius:5px;
    }
    .hz-field:focus-within{ border-color:#b6d4fe; box-shadow:0 0 0 2px rgba(13,110,253,.15); }

    .hz-input{ border:0; outline:0; background:transparent; min-width:11rem; font-size:.875rem; }
    .hz-input[type="date"]{ min-width:9.25rem; }
    .hz-select{ border:0; outline:0; background:transparent; font-size:.875rem; padding-right:1rem; }
    .hz-btn{ border-radius:5px; padding:.35rem .8rem; }

    .hz-sep{ color:#98a2b3; user-select:none; }

    .hz-grow{ min-width:16rem; }
    @media (max-width: 992px){
        .hz-grow{ min-width:12rem; }
    }
</style>
@endpush

<div class="container py-4">
    <h3 class="mb-4">Dashboard Overview</h3>

    <div class="content-wrapper">
        <div class="container-p-y">
            <div class="card mb-6">
                <div class="card-widget-separator-wrapper">
                    <div class="card-body card-widget-separator">
                        @if(!empty($isHead) && $isHead)
                            <div class="row gy-4 gy-sm-1">
                                {{-- To assign --}}
                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="to_assign">
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
                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="assigned">
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
                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="in_progress">
                                <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                                    <div>
                                    <p class="mb-1">In Progress</p>
                                    <h4 class="mb-1">{{ $metrics['in_progress'] ?? 0 }}</h4>
                                    </div>
                                    <span class="avatar p-2 me-sm-6">
                                    <span class="avatar-initial rounded w-px-44 h-px-44">
                                        <i class="icon-base bx bx-pencil icon-lg text-heading"></i>
                                    </span>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-6" />
                                </div>

                                {{-- Completed --}}
                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="completed">
                                <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
                                    <div>
                                    <p class="mb-1">Completed</p>
                                    <h4 class="mb-1">{{ $metrics['completed'] ?? 0 }}</h4>
                                    </div>
                                    <span class="avatar p-2 me-sm-6">
                                    <span class="avatar-initial rounded w-px-44 h-px-44">
                                        <i class="icon-base bx bx-check icon-lg text-heading"></i>
                                    </span>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-6" />
                                </div>

                                {{-- Rejected --}}
                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="rejected">
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

                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="pending">
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

                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="in_progress">
                                <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                                    <div>
                                    <p class="mb-1">In Progress</p>
                                    <h4 class="mb-1">{{ $metrics['in_progress'] ?? 0 }}</h4>
                                    </div>
                                    <span class="avatar p-2 me-sm-6">
                                    <span class="avatar-initial rounded w-px-44 h-px-44">
                                        <i class="icon-base bx bx-pencil icon-lg text-heading"></i>
                                    </span>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-6" />
                                </div>

                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="completed">
                                <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
                                    <div>
                                    <p class="mb-1">Completed</p>
                                    <h4 class="mb-1">{{ $metrics['completed'] ?? 0 }}</h4>
                                    </div>
                                    <span class="avatar p-2 me-sm-6">
                                    <span class="avatar-initial rounded w-px-44 h-px-44">
                                        <i class="icon-base bx bx-check icon-lg text-heading"></i>
                                    </span>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-6" />
                                </div>

                                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="rejected">
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

                        
                    </div>

                    <div class="table-responsive" id="orders-table-wrapper-top5" data-dashboard>
                        @include('artist.partials.orders-table', [
                        'orders'        => $orders,        
                        'tableContext'  => 'dashboard',   
                        'tableId'       => 'jobOrdersTop5' 
                        ])               
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

        if (wrap) {
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

                const $t = $('#jobOrdersTable');
                if (!$t.length || $t.is('[data-no-dt="1"]')) return;

                // IMPORTANT: assign to outer 'dt', don't redeclare with 'const' or 'let' here
                dt = $t.DataTable({
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
        }
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
        const $table = $('#jobOrdersTable');

        // If dashboard table (has data-no-dt) OR already initialized, skip.
        if (!$table.length || $table.is('[data-no-dt="1"]') || $.fn.dataTable.isDataTable('#jobOrdersTable')) {
            // skip
        } else {
            $table.DataTable({
            dom: 'Brt<"d-flex justify-content-between align-items-center mt-3"ip>',
            paging: true,
            pageLength: 5,
            autoWidth: false,
            responsive: true,
            order: [], // (use [[5,'asc']] if you want deadline asc here)
            buttons: [{
                extend: 'excel',
                title: 'Job Orders',
                className: 'd-none',
                exportOptions: { columns: [0,1,2,3,4,5] }
            }],
            columnDefs: [
                { targets: -1, orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ results',
                paginate: { previous: 'Previous', next: 'Next' }
            },
            drawCallback: function() {
                this.api().columns.adjust().responsive.recalc();
            }
            });

            // external controls (only if we actually initialized)
            $('#jobSearch').off('keyup.dt').on('keyup.dt', function () {
            $table.DataTable().search(this.value).draw();
            });
            $('#statusFilter').off('change.dt').on('change.dt', function () {
            $table.DataTable().column(4).search(this.value).draw();
            });
            $('#exportExcel').off('click.dt').on('click.dt', function () {
            $table.DataTable().button(0).trigger();
            });
        }
        } catch (e) {
        console.error('Failed to initialize DataTables:', e);
        }

        // === Dashboard top-5 DataTable (local-only) ===
        const $dashTable = window.jQuery ? jQuery('#jobOrdersTop5') : null;
        if ($dashTable && $dashTable.length) {
        try {
            if (!jQuery.fn.DataTable) throw new Error('DataTables plugin not loaded');

            // clear old filters for this table
            jQuery.fn.dataTable.ext.search = jQuery.fn.dataTable.ext.search
            .filter(fn => !fn._dashDateFilter && !fn._dashStatusFilter);

            const dt = $dashTable.DataTable({
            dom: 'rt<"d-flex justify-content-between align-items-center mt-2"ip>',
            paging: true,
            pageLength: 5,
            lengthChange: false,
            searching: true,
            autoWidth: false,
            responsive: true,
            order: [], // keep the server/pre-sorted (top-5) order
            columnDefs: [
                { targets: -1, orderable: false, searchable: false, className: 'text-end' },
                { targets: '_all', defaultContent: '' }
            ]
            });

            // ---- HEAD-ARTIST: Artist search (col 3) ----
            const artistInput = document.getElementById('artistFilter');
            if (artistInput) {
            artistInput.addEventListener('input', function () {
                dt.column(3).search(this.value).draw();
            });
            }

            // ---- HEAD-ARTIST: Date range filter (using data-deadline) ----
            const fromEl = document.getElementById('dateFrom');
            const toEl   = document.getElementById('dateTo');
            if (fromEl || toEl) {
            const dateFilter = function (settings, data, dataIndex) {
                if (settings.nTable !== $dashTable.get(0)) return true;

                const row = dt.row(dataIndex).node();
                const td  = row ? row.querySelector('td:nth-child(6)') : null; // 6th col = Deadline
                const iso = td ? td.getAttribute('data-deadline') : '';
                if (!iso) return true;

                const d = new Date(iso);
                if (isNaN(d)) return true;

                const from = fromEl && fromEl.value ? new Date(fromEl.value) : null;
                const to   = toEl   && toEl.value   ? new Date(toEl.value)   : null;

                if (from && d < from) return false;
                if (to   && d > to)   return false;
                return true;
            };
            dateFilter._dashDateFilter = true;
            jQuery.fn.dataTable.ext.search.push(dateFilter);

            const redraw = () => dt.draw();
            fromEl && fromEl.addEventListener('change', redraw);
            toEl   && toEl.addEventListener('change', redraw);
            }

            // ---- ORIGINAL dashboard controls on the right ----
            const jobSearch = document.getElementById('jobSearch');
            if (jobSearch) {
            jobSearch.addEventListener('input', function () {
                dt.search(this.value).draw();
            });
            }

            const statusSel = document.getElementById('statusFilter');
            if (statusSel) {
            const statusFilter = function (settings, data, dataIndex) {
                if (settings.nTable !== $dashTable.get(0)) return true;
                const want = (statusSel.value || '').toLowerCase();
                if (!want) return true;
                const rowNode = dt.row(dataIndex).node();
                const code = (rowNode?.querySelector('td[data-status-code]')?.dataset.statusCode || '').toLowerCase();
                return code === want;
            };
            statusFilter._dashStatusFilter = true;
            jQuery.fn.dataTable.ext.search.push(statusFilter);
            statusSel.addEventListener('change', () => dt.draw());
            }

            // keep layout tidy
            window.addEventListener('resize', () => setTimeout(() => dt.columns.adjust().responsive.recalc(), 80));
        } catch (e) {
            console.warn('Dashboard top-5 DataTables init failed:', e);
        }
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
  (function () {
    const base = "{{ route('artist.orders') }}";
    document.querySelectorAll('[data-go-status]').forEach(function (tile) {
        tile.addEventListener('click', function () {
        const s = tile.getAttribute('data-go-status');
        const url = new URL(base, window.location.origin);
        if (s) url.searchParams.set('status', s);
        window.location.href = url.toString();
        });
    });
    })();
</script>
@endpush
@endsection
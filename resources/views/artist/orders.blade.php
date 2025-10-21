@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .bg-light .input-group-text { border-color:#e9ecef; }
    .bg-light .form-control, .bg-light .form-select { border-color:#e9ecef; }
    .bg-light.border { border-color:#e9ecef !important; }

    #ordersFilterForm .form-control,
    #ordersFilterForm .form-select,
    #ordersFilterForm .input-group-text { height: 42px; }

    /* Compact select for entries-per-page */
    #pageLength { min-width: 72px; }
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
        font-size: .95rem;
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

    .btn-outline-secondary.btn-icon {
        border-color: #e5e7eb;
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

    div.dataTables_wrapper .dataTables_length { display: none; }
</style>
@endpush

<div class="container py-4">
    <h3 class="mb-4">Job Order Overview</h3>

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
                                            <i class="icon-base bx bx-gift icon-lg text-heading"></i>
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
                                            <i class="icon-base bx bx-wallet icon-lg text-heading"></i>
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
                                            <i class="icon-base bx bx-gift icon-lg text-heading"></i>
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
                                            <i class="icon-base bx bx-wallet icon-lg text-heading"></i>
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

                        @php $statusVal = request('status', $statusRaw ?? ''); @endphp
                        <div class="w-100"></div>
                        <div class="w-100 border rounded-3 px-3 py-3">
                            <form id="ordersFilterForm" method="GET" action="{{ route('artist.orders') }}">
                            <div class="row g-2 align-items-center">

                                {{-- Order ID (client-side, column 0 only) --}}
                                <div class="col-12 col-lg-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-hash"></i></span>
                                    <input id="orderIdSearch" type="text" class="form-control" placeholder="Search Order ID">
                                </div>
                                </div>

                                {{-- Artist name --}}
                                <div class="col-12 col-lg-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-user"></i></span>
                                    <input type="text"
                                        name="artist"
                                        class="form-control"
                                        placeholder="{{ ($isHead ?? false) ? 'Search artist name…' : 'Search salesperson name…' }}"
                                        value="{{ request('artist') }}">
                                </div>
                                </div>

                                {{-- Global order/product search (server) --}}
                                <div class="col-12 col-lg-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                                    <input name="q" type="text" class="form-control" placeholder="Search orders or product details" value="{{ request('q') }}">
                                </div>
                                </div>
                                
                                {{-- Date from --}}
                                <div class="col-6 col-lg-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                    <input type="date" name="from" class="form-control" value="{{ request('from') }}" placeholder="YYYY-MM-DD">
                                </div>
                                </div>

                                {{-- Date to --}}
                                <div class="col-6 col-lg-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                    <input type="date" name="to" class="form-control" value="{{ request('to') }}" placeholder="YYYY-MM-DD">
                                </div>
                                </div>

                                {{-- Status --}}
                                <div class="col-12 col-lg-2">
                                <select name="status" class="form-select">
                                    <option value="">All statuses</option>
                                    @if(!($isHead ?? false))
                                    <option value="pending"     {{ $statusVal==='pending' ? 'selected' : '' }}>Pending</option>
                                    @endif
                                    <option value="in_progress" {{ $statusVal==='in_progress' ? 'selected' : '' }}>In progress</option>
                                    <option value="completed"   {{ $statusVal==='completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="rejected"    {{ $statusVal==='rejected' ? 'selected' : '' }}>Rejected</option>
                                    @if($isHead ?? false)
                                    <option value="to_assign" {{ $statusVal==='to_assign' ? 'selected' : '' }}>To assign</option>
                                    <option value="assigned"  {{ $statusVal==='assigned'  ? 'selected' : '' }}>Assigned</option>
                                    @endif
                                </select>
                                </div>

                                {{-- Keep deadline sort if set --}}
                                @if(request('deadline_sort'))
                                <input type="hidden" name="deadline_sort" value="{{ request('deadline_sort') }}">
                                @endif

                                {{-- Actions (right aligned) --}}
                                <div class="col-12 col-lg d-flex gap-2 justify-content-lg-end">
                                <button type="submit" class="btn btn-primary px-4">Filter</button>
                                <a href="{{ route('artist.orders') }}" class="btn btn-outline-secondary">Reset</a>
                                <button id="exportExcel" type="button" class="btn btn-dark">
                                    <i class="bx bx-export me-1"></i> Export
                                </button>
                                <a href="{{ route('artist.orders.create') }}" class="btn btn-light text-primary">Add Order</a>
                                </div>

                            </div>
                            </form>

                            <!-- {{-- Second row: Order ID search (client-only, Column 0) + Export/Add --}}
                            <div class="row g-2 mt-3 align-items-center">
                                <div class="col-12 col-md d-flex gap-2 mt-2 mt-md-0 justify-content-md-end">
                                    <button id="exportExcel" class="btn btn-dark">
                                    <i class="bx bx-export me-1"></i> Export
                                    </button>
                                    <a href="{{ route('artist.orders.create') }}" class="btn btn-light text-primary">Add Order</a>
                                </div>
                            </div> -->

                        </div>
                    </div>

                    <div class="table-responsive" id="orders-table-wrapper">
                        @include('artist.partials.orders-table', ['orders' => $orders])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')

<script>
    const LEN_KEY = 'orders.pageLength.v2';  // new versioned key

    function getSavedLen() {
        const raw = localStorage.getItem(LEN_KEY);
        if (raw == null) {
            localStorage.setItem(LEN_KEY, '5');  // first time → default 5
            return 5;
        }
        const v = parseInt(raw, 10);
        return [5,10,20,30].includes(v) ? v : 5;
    }

    function bindPageLengthControl(dt) {
        const sel = document.getElementById('pageLength');
        if (!sel || !dt) return;

        // reflect current DT length
        sel.value = String(dt.page.len());

        sel.onchange = function () {
            const v = parseInt(sel.value, 10) || 5;
            localStorage.setItem(LEN_KEY, String(v));
            dt.page.len(v).draw();
        };
    }

    (function preloadOrderIdFromUrl(){
    const box = document.getElementById('orderIdSearch');
    if (!box) return;
    const oid = new URLSearchParams(location.search).get('oid') || '';
    box.value = oid;
    })();

    (function persistOidOnSubmit(){
    const form = document.getElementById('ordersFilterForm');
    const box  = document.getElementById('orderIdSearch');
    if (!form || !box) return;

    form.addEventListener('submit', () => {
        let hid = form.querySelector('input[name="oid"]');
        if (!hid) {
        hid = document.createElement('input');
        hid.type = 'hidden';
        hid.name = 'oid';
        form.appendChild(hid);
        }
        hid.value = box.value.trim(); // carry current Order ID into the URL
    });
    })();

    (function syncOidWhileTyping(){
    const box = document.getElementById('orderIdSearch');
    if (!box) return;

    const debounce = (fn, ms=200) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };
    const setQuery = (k,v)=>{
        const u = new URL(location.href);
        if (v) u.searchParams.set(k, v); else u.searchParams.delete(k);
        history.replaceState(null, '', u);
    };

    box.addEventListener('input', debounce(() => {
        setQuery('oid', box.value.trim());
    }, 200));
    })();

    function bindOrderIdFilter(dt) {
    const input = document.getElementById('orderIdSearch');
    if (!input || !dt || !$.fn || !$.fn.dataTable) return;

    let term = (input.value || '').trim().toLowerCase();

    // register a single global DT filter that only checks column 0
    const orderIdFilterFn = function(settings, data) {
        if (!term) return true;
        const col0 = (data[0] || '').toString().toLowerCase();
        return col0.includes(term);
    };
    orderIdFilterFn._orderIdFilter = true;
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search
        .filter(fn => !fn._orderIdFilter) // avoid duplicates on re-init
        .concat(orderIdFilterFn);

    // re-draw when typing
    const debounce = (fn, ms=200) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };
    input.addEventListener('input', debounce(function(){
        term = (this.value || '').trim().toLowerCase();
        dt.draw();
    }, 200));

    if (term) dt.draw(); // apply initial value (from URL) on init
    }

    (function() {
        let dt = null;

        // remove previous custom filter if any
        function clearDtFilters() {
            if (!$.fn || !$.fn.dataTable) return;
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(fn =>
                !fn._ordersStatusFilter && !fn._orderIdFilter
            );
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
                dom: 'Brt<"d-flex justify-content-between align-items-center mt-3"ip>',
                paging: true,
                pageLength: getSavedLen(),           // ← use saved/default length
                lengthMenu: [[5,10,20,30],[5,10,20,30]],
                autoWidth: false,
                responsive: true,
                order: [],
                buttons: [{
                    extend: 'excel',
                    title: 'Job Orders',
                    className: 'd-none',
                    exportOptions: { columns: [0,1,2,3,4,5] }
                }],
                columnDefs: [{
                        targets: -1,
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                    // (optional) safety: fill blanks instead of warning if a cell is missing
                    {
                        targets: '_all',
                        defaultContent: ''
                    }
                ],
                language: {
                    lengthMenu: 'Show _MENU_',
                    emptyTable: 'No matching records found',
                    zeroRecords: 'No matching records found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ results',
                    infoEmpty: 'Showing 0 to 0 of 0 results',
                    paginate: {
                        previous: 'Previous',
                        next: 'Next'
                    }
                },
                drawCallback: function() {
                    this.api().columns.adjust().responsive.recalc();
                }
            });

            bindPageLengthControl(dt);
            bindOrderIdFilter(dt);

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
        // load(true);

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

        // Bail if jQuery is missing
        if (!window.jQuery) {
            console.error('jQuery not loaded → DataTables will not init');
            return;
        }

        $(function() {
            // ===== Helpers for status pill =====
            const statusMap = {
                to_assign: {
                    label: 'To Assign',
                    cls: 'assign'
                },
                assigned: {
                    label: 'Assigned',
                    cls: 'assign'
                },
                in_progress: {
                    label: 'In Progress',
                    cls: 'progress'
                },
                pending: {
                    label: 'Pending',
                    cls: 'progress'
                },
                completed: {
                    label: 'Completed',
                    cls: 'completed'
                },
                rejected: {
                    label: 'Rejected',
                    cls: 'rejected'
                }
            };
            const pill = raw => {
                const key = String(raw || '').toLowerCase();
                const m = statusMap[key] || {
                    label: raw || '-',
                    cls: 'progress'
                };
                return `<span class="badge-status badge-${m.cls}">${m.label}</span>`;
            };

            // ===== DataTables init (guarded) =====
            try {
                if (!$.fn.DataTable) throw new Error('DataTables plugin not loaded');

                dt = $('#jobOrdersTable').DataTable({
                    dom: '<"d-flex justify-content-between align-items-center"lB>rt<"d-flex justify-content-between align-items-center"ip>',
                    paging: true,
                    pageLength: getSavedLen(),           // ← use saved/default length
                    lengthMenu: [[5,10,20,30],[5,10,20,30]],
                    autoWidth: false,
                    responsive: true,
                    order: [],
                    buttons: [{
                        extend: 'excel',
                        title: 'Job Orders',
                        className: 'd-none',
                        exportOptions: { columns: [0,1,2,3,4,5] }
                    }],
                    columnDefs: [{ // status pill
                            targets: 4,
                            createdCell: function(td, cellData) {
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
                        paginate: {
                            previous: 'Previous',
                            next: 'Next'
                        }
                    },
                    drawCallback: function() {
                        this.api().columns.adjust().responsive.recalc();
                    }
                });
                
                bindPageLengthControl(dt);
                bindOrderIdFilter(dt);

                // External controls
                $('#jobSearch').on('keyup', function() {
                    dt.search(this.value).draw();
                });
                let activeStatusCode = ''; // canonical code to filter

                $('#statusFilter')
                    .off('change.dt')
                    .on('change.dt', function() {
                        activeStatusCode = (this.value || '').toLowerCase(); // e.g. "in_progress" or "pending"
                        dt.draw();
                    });
                $('#exportExcel').on('click', function() {
                    dt.button(0).trigger();
                });
                window.addEventListener('resize', () => setTimeout(() => dt.columns.adjust().responsive.recalc(), 100));

            } catch (e) {
                console.error('Failed to initialize DataTables:', e);
            }


        });
    })();

    (function() {
        const form = document.getElementById('ordersFilterForm');
        if (!form) return;

        // Submit on change for quick UX
        form.addEventListener('change', function(e) {
            const el = e.target;
            if (['SELECT', 'INPUT'].includes(el.tagName)) {
                // skip text inputs so typing doesn't submit; Enter will submit
                if (el.tagName === 'INPUT' && el.type === 'text') return;
                form.submit();
            }
        });

        // Submit when pressing Enter in text inputs
        form.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.id !== 'orderIdSearch') {
            form.submit();
        }
        });
    })();

    (function() {
        // Format a Date/string to YYYY-MM-DD safely
        function toISODateString(val) {
            if (!val) return '';
            // already ISO?
            if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;

            // Try parsing common local formats; fall back to invalid => ''
            const d = new Date(val);
            if (isNaN(d.getTime())) return '';

            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        }

        // Normalize date inputs before submit (so server always receives YYYY-MM-DD)
        const form = document.getElementById('ordersFilterForm') || document.querySelector('form[action*="artist/orders"]');
        if (!form) return;

        function normalizeDates() {
            const from = form.querySelector('input[name="from"]');
            const to = form.querySelector('input[name="to"]');
            if (from) {
                const iso = toISODateString(from.value);
                if (iso) from.value = iso;
            }
            if (to) {
                const iso = toISODateString(to.value);
                if (iso) to.value = iso;
            }
        }

        // On submit, force-normalize
        form.addEventListener('submit', function() {
            normalizeDates();
        });

        // Also normalize immediately when user changes the date, so what they see is YYYY-MM-DD
        form.querySelectorAll('input[name="from"], input[name="to"]').forEach(function(inp) {
            inp.addEventListener('change', function() {
                const iso = toISODateString(inp.value);
                if (iso) inp.value = iso;
            });
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
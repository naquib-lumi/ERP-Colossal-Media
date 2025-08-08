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

    .badge-assign {
        background: #eef2ff;
        color: #4f46e5;
    }

    .badge-progress {
        background: #eaf6ff;
        color: #0284c7;
    }

    .badge-completed {
        background: #ecfdf5;
        color: #059669;
    }

    .badge-rejected {
        background: #fef2f2;
        color: #dc2626;
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

    .badge-status {
        padding: .35rem .6rem;
        font-weight: 600;
        border-radius: 999px;
        font-size: .75rem
    }

    .badge-assign {
        background: #eef2ff;
        color: #4f46e5
    }

    .badge-progress {
        background: #eaf6ff;
        color: #0284c7
    }

    .badge-completed {
        background: #ecfdf5;
        color: #059669
    }

    .badge-rejected {
        background: #fef2f2;
        color: #dc2626
    }

    .btn-icon {
        --size: 32px;
        width: var(--size);
        height: var(--size);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: .5rem
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
                                        <h4 class="mb-1">27</h4>
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
                                        <h4 class="mb-1">5</h4>
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
                                        <h4 class="mb-1">9</h4>
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
                                        <h4 class="mb-1">11</h4>
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
                                        <h4 class="mb-1">2</h4>
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
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Artist</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                <tr>
                                    {{-- 1) Order ID --}}
                                    <td>#ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</td>

                                    {{-- 2) Job Title --}}
                                    <td>{{ $order->orderTitle ?? '-' }}</td>

                                    {{-- 3) Company --}}
                                    <td>{{ $order->companyName ?? '-' }}</td>

                                    {{-- 4) Artist --}}
                                    <td>{{ optional($order->user)->name ?? '-' }}</td>

                                    {{-- 5) Status (raw text; DT will wrap into a pill) --}}
                                    <td class="js-status">{{ $order->orderStatus ?? 'to_assign' }}</td>

                                    {{-- 6) Deadline --}}
                                    <td>
                                        @if(!empty($order->deadline))
                                        {{ \Carbon\Carbon::parse($order->deadline)->format('M d, Y') }}
                                        @else
                                        -
                                        @endif
                                    </td>

                                    {{-- 7) Actions --}}
                                    <td class="text-end">
                                        <button class="btn btn-outline-secondary btn-icon" title="View">
                                            <i class="bx bx-show"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-icon" title="Edit">
                                            <i class="bx bx-edit-alt"></i>
                                        </button>
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
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6>Salesperson Meeting Status Report</h6>
                            <select class="form-select mb-2">
                                <option>All Salespersons</option>
                            </select>
                            <canvas id="meetingStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6>Job Order Fulfillment Report</h6>
                            <canvas id="fulfillmentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            $(function() {
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

                const pill = (raw) => {
                    const key = String(raw || '').toLowerCase();
                    const m = statusMap[key] || {
                        label: raw || '-',
                        cls: 'progress'
                    };
                    return `<span class="badge-status badge-${m.cls}">${m.label}</span>`;
                };

                const dt = $('#jobOrdersTable').DataTable({
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
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    }],
                    columnDefs: [{
                            targets: 4, // Status column
                            createdCell: function(td, cellData) {
                                $(td).html(pill(cellData)); // show pill
                            }
                        },
                        {
                            targets: -1,
                            orderable: false,
                            searchable: false,
                            className: 'text-end'
                        }
                    ],
                    language: {
                        info: "Showing _START_ to _END_ of _TOTAL_ results",
                        paginate: {
                            previous: "Previous",
                            next: "Next"
                        }
                    },
                    drawCallback: function() {
                        this.api().columns.adjust().responsive.recalc();
                    }
                });

                // External controls
                $('#jobSearch').on('keyup', function() {
                    dt.search(this.value).draw();
                });
                $('#statusFilter').on('change', function() {
                    dt.column(4).search(this.value).draw();
                });
                $('#exportExcel').on('click', function() {
                    dt.button(0).trigger();
                });

                window.addEventListener('resize', () => {
                    setTimeout(() => dt.columns.adjust().responsive.recalc(), 100);
                });
            });
        </script>
        @endpush

        @endsection
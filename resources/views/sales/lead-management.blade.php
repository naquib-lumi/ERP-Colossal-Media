@extends('layouts.app')

@section('title', 'Lead Management')
@section('content')
<style>
.swal2-popup.z-index-1060 {
    z-index: 1060 !important;
}
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Lead Management Table -->
    <div class="card">
       <h5 class="card-header bg-white text-dark pb-2 pt-2 text-md-start">
    Lead Management
</h5>

        <div class="card-datatable table-responsive p-3">
            <!-- Filters -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="w-100 border rounded-3 px-3 py-3">
                    <form id="leadsFilterForm" method="GET" action="{{ route('sales.leads') }}">
                        <div class="row g-2 align-items-center">
                            {{-- Global search --}}
                            <div class="col-12 col-lg-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                                    <input id="globalSearch" type="text" name="q" class="form-control" placeholder="Search leads by ID, Company, or Name..." value="{{ request('q') }}">
                                </div>
                            </div>

                            @if(Auth::user()->hasRole('head-salesperson'))
                            {{-- Salesperson filter --}}
                            <div class="col-12 col-lg-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-user"></i></span>
                                    <select id="salespersonFilter" name="salesperson_id" class="form-control">
                                        <option value="">All Salespersons</option>
                                        @foreach($salespeople as $salesperson)
                                        <option value="{{ $salesperson->id }}" {{ request('salesperson_id') == $salesperson->id ? 'selected' : '' }}>{{ $salesperson->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif

                            {{-- Status --}}
                            <div class="col-12 col-lg-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-filter-alt"></i></span>
                                    <select id="statusFilter" name="status" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="accept" {{ request('status') == 'accept' ? 'selected' : '' }}>Accept</option>
                                        <option value="reject" {{ request('status') == 'reject' ? 'selected' : '' }}>Reject</option>
                                        <option value="followup" {{ request('status') == 'followup' ? 'selected' : '' }}>Followup</option>
                                        <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>New</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Date from --}}
                            <div class="col-6 col-lg-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                    <input type="date" id="fromDate" name="from_date" class="form-control" value="{{ request('from_date') }}">
                                </div>
                            </div>

                            {{-- Date to --}}
                            <div class="col-6 col-lg-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                    <input type="date" id="toDate" name="to_date" class="form-control" value="{{ request('to_date') }}">
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="col-12 col-lg d-flex gap-2 justify-content-lg-end">
                         <button type="button" class="btn btn-dark" onclick="exportCsvWithFilters()">
                            <i class="bx bx-export me-1"></i> Export
                        </button>
                                <a href="{{ route('sales.leads') }}" class="btn btn-outline-secondary">Reset</a>
                                <a href="{{ route('leads.create') }}" class="btn btn-light text-primary">Add Lead</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <table class="datatables-ajax table table-striped table-hover" id="leadTable" style="width: 100%;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="text-center align-middle" style="width: 15%;">Lead Data</th>
                        <th class="text-center align-middle" style="width: 20%;">Company Details</th>
                        <th class="text-center align-middle" style="width: 20%;">Lead Details</th>
                        <th class="text-center align-middle" style="width: 15%;">Assigned Salesperson</th>
                        <th class="text-center align-middle" style="width: 15%;">Reminder</th>
                        <th class="text-center align-middle" style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated by DataTables AJAX -->
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal for Attachments -->
    <div class="modal fade" id="attachmentModal" tabindex="-1" aria-labelledby="attachmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="attachmentModalLabel">Attachments</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="attachmentBody">
                    <!-- Attachments will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Reminder Confirmation -->
<div class="modal fade" id="reminderConfirmModal" tabindex="-1" aria-labelledby="reminderConfirmLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reminderConfirmLabel">Confirm Reminder Completion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="reminderConfirmText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-primary" id="confirmReminderDoneBtn">Yes</button>
            </div>
        </div>
    </div>
</div>
    <!--/ Lead Management Table -->
    <hr class="my-12" />
</div>

<script>
    // Fallback to ensure jQuery is loaded
    window.jQuery || document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>');
    $(document).ready(function() {
        console.log('jQuery loaded:', $); // Debug to confirm jQuery
        let table = $('#leadTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('leads.get') }}',
                type: 'POST',
                data: function(d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    d.search = { value: $('#globalSearch').val() };
                    d.status = $('#statusFilter').val();
                    d.from_date = $('#fromDate').val();
                    d.to_date = $('#toDate').val();
                    if ($('#salespersonFilter').length) {
                        d.salesperson_id = $('#salespersonFilter').val();
                    }
                    return d;
                },
                error: function(xhr, error, thrown) {
                    console.log('AJAX Error: ', xhr.status, xhr.responseText);
                    if (xhr.status === 419) {
                        console.log('CSRF Token mismatch or session expired. Please refresh the page.');
                    } else if (xhr.status === 404) {
                        console.log('Route not found. Check server configuration or routes.');
                    } else if (xhr.status === 500) {
                        console.log('Server error. Check logs for details.');
                    }
                }
            },
            columns: [
                { data: 'lead_data', name: 'lead_data', orderable: true },
                { data: 'company_details', name: 'company_details', orderable: false },
                { data: 'lead_details', name: 'lead_details', orderable: false },
                { data: 'assigned_salesperson', name: 'assigned_salesperson', orderable: true },
                { data: 'reminder', name: 'reminder', orderable: true },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            dom: 'rtip',
            layout: {
                topStart: {
                    rowClass: 'row mx-3 my-2 justify-content-between align-items-center',
                    features: [
                        {
                            pageLength: {
                                menu: [7, 10, 25, 50, 100],
                                text: '<span class="text-muted">Show</span> <select class="form-select form-select-sm ms-2"><option value="7">7</option><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> <span class="text-muted">entries</span>'
                            }
                        }
                    ]
                },
                topEnd: null,
                bottomStart: {
                    rowClass: 'row mx-3 justify-content-between',
                    features: ['info']
                },
                bottomEnd: {
                    paging: {
                        firstLast: false,
                        layout: 'pageLength'
                    }
                }
            },
            language: {
                paginate: {
                    next: '<i class="bx bx-chevron-right"></i>',
                    previous: '<i class="bx bx-chevron-left"></i>'
                },
                info: 'Showing _START_ to _END_ of _TOTAL_ leads'
            },
            order: [[0, 'desc']],
            initComplete: function() {
                $('#leadsFilterForm').on('submit', function(e) {
                    e.preventDefault();
                    table.draw();
                });
                $('#statusFilter, #fromDate, #toDate, #salespersonFilter').on('change', function() {
                    table.draw();
                });
                $('#globalSearch').on('keyup', function() {
                    table.search(this.value).draw();
                });
            },
            drawCallback: function() {
                $('.dataTables_paginate .pagination').addClass('pagination-sm');
            }
        });
        // Add after DataTable initialization, inside $(document).ready
    $('#leadTable').on('dblclick', 'tbody tr', function(e) {
        if ($(e.target).closest('select, button, a, i').length) return;
        const leadId = $(this).find('.lead-id').text();
        window.location.href = `/leads/${leadId}/view`;
    });

        $('#leadTable').on('change', '.status-dropdown', function(e) {
            e.stopPropagation();
            let id = $(this).data('id');
            let status = $(this).val();
            $.ajax({
                url: '{{ route('leads.update.status', ['id' => ':id']) }}'.replace(':id', id),
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), status: status },
                success: function(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        table.ajax.reload(null, false);
                    }
                },
                error: function(xhr) {
                    alert('Error updating status: ' + xhr.responseText);
                }
            });
        });

        $('#leadTable').on('change', '.opportunity-dropdown', function(e) {
            e.stopPropagation();
            let id = $(this).data('id');
            let opportunity = $(this).val();
            $.ajax({
                url: '{{ route('leads.update.opportunity', ['id' => ':id']) }}'.replace(':id', id),
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), opportunity: opportunity },
                success: function(response) {
                    table.ajax.reload(null, false);
                },
                error: function(xhr) {
                    alert('Error updating opportunity: ' + xhr.responseText);
                }
            });
        });

        $('#leadTable').on('change', '.assign-dropdown', function(e) {
            e.stopPropagation();
            let id = $(this).data('id');
            let salespersonId = $(this).val();
            if (salespersonId) {
                $.ajax({
                    url: '{{ route('leads.update.salesperson', ['id' => ':id']) }}'.replace(':id', id),
                    type: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), salesperson_id: salespersonId },
                    success: function(response) {
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        alert('Error reassigning lead: ' + xhr.responseText);
                    }
                });
            }
        });

        $(document).on('click', '.confirm-reminder', function(e) {
            e.stopPropagation();
            e.preventDefault();
            let leadId = $(this).data('id');
            let reminderId = $(this).data('reminder-id');
            let title = $(this).data('title');

            $('#reminderConfirmText').text(`Is the reminder "${title}" done?`);
            $('#reminderConfirmModal').data('lead-id', leadId).data('reminder-id', reminderId).modal('show');
        });

  $(document).on('click', '#confirmReminderDoneBtn', function() {
    let leadId = $('#reminderConfirmModal').data('lead-id');
    let reminderId = $('#reminderConfirmModal').data('reminder-id');

    $.ajax({
        url: '{{ route('leads.confirm.reminder.status', ['id' => ':leadId', 'reminderId' => ':reminderId']) }}'.replace(':leadId', leadId).replace(':reminderId', reminderId),
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            confirm: 'yes'
        },
        success: function(response) {
            console.log('Reminder confirmed:', response);
            $('#reminderConfirmModal').modal('hide');
            let $link = $(`a.confirm-reminder[data-reminder-id="${reminderId}"]`);
            if ($link.length) {
                let $li = $link.closest('li');
                $li.addClass('text-secondary text-decoration-line-through').fadeOut(2500, function() {
                    $li.remove();
                    let $ul = $li.parent('ul');
                    if ($ul.children('li').length === 0) {
                        $ul.replaceWith('<span class="text-muted">No Reminders</span>');
                    }
                });
            }
        },
        error: function(xhr) {
            console.error('Reminder confirm error:', xhr.status, xhr.responseText);
            $('#reminderConfirmModal').modal('hide');
            let errorMsg = 'Error confirming reminder';
            if (xhr.status === 403) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error === 'Unauthorized') {
                        errorMsg = 'Only the assigned salesperson can confirm this reminder.';
                    }
                } catch (e) {
                    // fallback
                }
            } else {
                errorMsg += ': ' + (xhr.responseText || 'Unknown error');
            }
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMsg,
                confirmButtonText: 'OK',
                allowOutsideClick: true,
                allowEscapeKey: true
            });
        }
    });
});

        $('#leadTable').on('click', '.view-attachments', function(e) {
            e.stopPropagation();
            let id = $(this).data('id');
            $.ajax({
                url: '{{ route('leads.attachments', ['id' => ':id']) }}'.replace(':id', id),
                type: 'GET',
                success: function(response) {
                    $('#attachmentBody').html(response);
                    $('#attachmentModal').modal('show');
                },
                error: function(xhr) {
                    alert('Error loading attachments: ' + xhr.responseText);
                }
            });
        });

        // Bind delete events after modal load
        $('#attachmentModal').on('shown.bs.modal', function() {
            $('#attachmentBody').on('click', '.delete-attachment', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this attachment?')) {
                    let url = $(this).attr('href');
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $(e.target).closest('tr').remove();
                            if ($('#attachmentBody tbody tr').length === 0) {
                                $('#attachmentModal').modal('hide');
                            }
                        },
                        error: function(xhr) {
                            alert('Error deleting attachment: ' + xhr.responseText);
                        }
                    });
                }
            });
        });

        $('#leadTable').on('submit', 'form', function(e) {
            e.preventDefault();
            let form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.message) {
                        alert(response.message); // Show success popup
                        table.ajax.reload(null, false); // Reload table without resetting page
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 403) {
                        alert('Unauthorized: You can only delete your own leads.');
                    } else {
                        alert('Error deleting lead: ' + xhr.responseText);
                    }
                }
            });
        });
    });

    function exportCsvWithFilters() {
    var params = new URLSearchParams();
    params.append('search[value]', $('#globalSearch').val());
    params.append('status', $('#statusFilter').val());
    params.append('from_date', $('#fromDate').val());
    params.append('to_date', $('#toDate').val());
    if ($('#salespersonFilter').length) {
        params.append('salesperson_id', $('#salespersonFilter').val());
    }
    window.location.href = '{{ route('leads.export-csv') }}?' + params.toString();
}
</script>
@endsection
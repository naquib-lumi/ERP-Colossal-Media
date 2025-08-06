@extends('layouts.app')

@section('title', 'Lead Management')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Lead Management Table -->
    <div class="card">
        <h5 class="card-header bg-primary text-white pb-2 pt-2 text-md-start d-flex justify-content-between align-items-center">
            Lead Management
            <a href="{{ route('leads.create') }}" class="btn btn-light text-primary">Add Lead</a>
        </h5>
        <div class="card-datatable table-responsive p-3">
            <!-- Search Input -->
            <div class="row mb-3 mx-0">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="globalSearch" placeholder="Search leads by ID, Company, or Name..." aria-label="Search leads">
                </div>
            </div>
            <table class="datatables-ajax table table-striped table-hover" id="leadTable" style="width: 100%;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="text-center align-middle" style="width: 15%;">Lead Data</th>
                        <th class="text-center align-middle" style="width: 20%;">Company Details</th>
                        <th class="text-center align-middle" style="width: 20%;">Lead Details</th>
                        <th class="text-center align-middle" style="width: 15%;">Assigned Artist</th>
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
                { data: 'assigned_artist', name: 'assigned_artist', orderable: true },
                { data: 'reminder', name: 'reminder', orderable: true },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', className: 'btn btn-outline-secondary btn-sm' },
                { extend: 'csv', className: 'btn btn-outline-secondary btn-sm' },
                { extend: 'excel', className: 'btn btn-outline-secondary btn-sm' },
                { extend: 'pdf', className: 'btn btn-outline-secondary btn-sm' },
                { extend: 'print', className: 'btn btn-outline-secondary btn-sm' }
            ],
            layout: {
                topStart: {
                    rowClass: 'row mx-3 my-2 justify-content-between align-items-center',
                    features: [
                        {
                            pageLength: {
                                menu: [7, 10, 25, 50, 100],
                                text: '<span class="text-muted">Show</span> <select class="form-select form-select-sm ms-2"><option value="7">7</option><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> <span class="text-muted">entries</span>'
                            }
                        },
                        'buttons'
                    ]
                },
                topEnd: {
                    search: {
                        placeholder: 'Search leads...',
                        input: '<input type="search" class="form-control form-control-sm" placeholder="Search leads..." aria-label="Search">'
                    }
                },
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
                $('#globalSearch').on('keyup', function() {
                    table.search(this.value).draw();
                });
                $('#leadTable tbody').on('click', 'tr', function(e) {
                    if (!$(e.target).closest('select, button, a, form').length) {
                        window.location.href = $(this).data('href') || '/sales/leads';
                    }
                });
            },
            drawCallback: function() {
                $('.dataTables_paginate .pagination').addClass('pagination-sm');
            }
        });

        $('#leadTable').on('change', '.status-dropdown', function(e) {
            e.stopPropagation(); // Prevent tr click
            let id = $(this).data('id');
            let status = $(this).val();
            $.ajax({
                url: '{{ route('leads.update.status', ['id' => ':id']) }}'.replace(':id', id),
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), status: status },
                success: function(response) {
                    table.ajax.reload(null, false); // Reload without resetting page
                },
                error: function(xhr) {
                    alert('Error updating status: ' + xhr.responseText);
                }
            });
        });

        $('#leadTable').on('change', '.opportunity-dropdown', function(e) {
            e.stopPropagation(); // Prevent tr click
            let id = $(this).data('id');
            let opportunity = $(this).val();
            $.ajax({
                url: '{{ route('leads.update.opportunity', ['id' => ':id']) }}'.replace(':id', id),
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), opportunity: opportunity },
                success: function(response) {
                    table.ajax.reload(null, false); // Reload without resetting page
                },
                error: function(xhr) {
                    alert('Error updating opportunity: ' + xhr.responseText);
                }
            });
        });

        $('#leadTable').on('click', '.confirm-reminder', function(e) {
            e.stopPropagation(); // Prevent tr click
            e.preventDefault();
            let id = $(this).data('id');
            let confirmed = $(this).data('confirmed');
            if (confirmed == '1') {
                $.ajax({
                    url: '{{ route('leads.confirm.reminder', ['id' => ':id']) }}'.replace(':id', id),
                    type: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        $(e.target).text(response.reminderText);
                        $(e.target).data('confirmed', '0');
                        table.ajax.reload(null, false); // Reload without resetting page
                    },
                    error: function(xhr) {
                        alert('Error confirming reminder: ' + xhr.responseText);
                    }
                });
            }
        });

        $('#leadTable').on('click', '.view-attachments', function(e) {
            e.stopPropagation(); // Prevent tr click
            let id = $(this).data('id');
            $.ajax({
                url: '{{ route('leads.attachments', ['id' => ':id']) }}'.replace(':id', id),
                type: 'GET',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    $('#attachmentBody').html(response);
                    $('#attachmentModal').modal('show');
                },
                error: function(xhr) {
                    alert('Error loading attachments: ' + xhr.responseText);
                }
            });
        });
    });
</script>
@endsection
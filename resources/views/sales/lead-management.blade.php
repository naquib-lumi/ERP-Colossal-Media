@extends('layouts.app')

@section('title', 'Lead Management')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Lead Management Table -->
    <div class="card">
        <h5 class="card-header pb-0 text-md-start text-center">Lead Management</h5>
        <div class="card-datatable text-nowrap">
            <div class="table-responsive">
                <table class="datatables-ajax table table-bordered" id="leadTable">
                    <thead>
                        <tr>
                            <th>Lead Data</th>
                            <th>Company Details</th>
                            <th>Lead Details</th>
                            <th>Assigned To</th>
                            <th>Reminder</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leads as $lead)
                        <tr data-href="/leads/{{ $lead->id }}">
                            <td>{{ $lead->id }}<br>{{ $lead->status }}<br>Opportunity: 50/50</td>
                            <td>
                                <i class="bx bxs-building"></i> {{ $lead->company_name }}<br>
                                <i class="bx bxs-phone"></i> {{ $lead->company_phone ?? 'N/A' }}<br>
                                <i class="bx bx-globe"></i> {{ $lead->website ?? 'N/A' }}<br>
                                <button class="btn btn-sm btn-info view-attachments" data-id="{{ $lead->id }}">View Attachments</button>
                            </td>
                            <td>{{ $lead->name }}<br>{{ $lead->phone }}<br>{{ $lead->email }}<br>{{ $lead->remark ?? 'No remark' }}</td>
                            <td>{{ $lead->user->name ?? 'Not Assigned' }}</td>
                            <td>
                                <a href="#" class="confirm-reminder" data-id="{{ $lead->id }}" data-confirmed="{{ $lead->date ? '1' : '0' }}">
                                    {{ $lead->date ? ($lead->date->isPast() ? '<s>' . $lead->date->format('Y-m-d') . '</s>' : $lead->date->format('Y-m-d')) : 'No Reminder' }}
                                </a>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="/leads/{{ $lead->id }}/edit" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="/leads/{{ $lead->id }}" class="btn btn-sm btn-secondary">View</a>
                                    <form action="/leads/{{ $lead->id }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal for Attachments -->
    <div class="modal fade" id="attachmentModal" tabindex="-1" aria-labelledby="attachmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attachmentModalLabel">Attachments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="attachmentBody">
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
    $(document).ready(function() {
        let table = $('#leadTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('leads.get') }}',
                type: 'POST',
                data: function(d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    d.search = { value: $('input[type="search"]').val() }; // Global search
                    return d;
                },
                error: function(xhr, error, thrown) {
                    console.log('AJAX Error: ', xhr.status, xhr.responseText);
                    if (xhr.status === 419) {
                        console.log('CSRF Token mismatch or session expired. Please refresh the page.');
                    }
                }
            },
            columns: [
                { data: 'lead_data', name: 'lead_data', orderable: true },
                { data: 'company_details', name: 'company_details', orderable: false },
                { data: 'lead_details', name: 'lead_details', orderable: false },
                { data: 'assigned_to', name: 'assigned_to', orderable: true },
                { data: 'reminder', name: 'reminder', orderable: true },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            layout: {
                topStart: {
                    rowClass: 'row mx-3 my-0 justify-content-between',
                    features: [
                        {
                            pageLength: {
                                menu: [7, 10, 25, 50, 100],
                                text: 'Show_MENU_entries'
                            }
                        },
                        'buttons'
                    ]
                },
                topEnd: {
                    search: {
                        placeholder: 'Search leads...'
                    }
                },
                bottomStart: {
                    rowClass: 'row mx-3 justify-content-between',
                    features: ['info']
                },
                bottomEnd: {
                    paging: {
                        firstLast: false
                    }
                }
            },
            language: {
                paginate: {
                    next: '<i class="bx bx-chevron-right"></i>',
                    previous: '<i class="bx bx-chevron-left"></i>'
                }
            },
            order: [[0, 'desc']], // Sort by Lead Data (ID) descending (latest first)
            initComplete: function() {
                this.api().columns().every(function() {
                    var column = this;
                    var input = document.createElement("input");
                    $(input).appendTo($(column.header()))
                        .on('keyup', function() {
                            column.search($(this).val(), false, false, true).draw();
                        });
                });
                $('#leadTable tbody').on('click', 'tr', function() {
                    window.location.href = $(this).data('href');
                });
            }
        });

        // Status dropdown update
        $('#leadTable').on('change', '.status-dropdown', function() {
            let id = $(this).data('id');
            let status = $(this).val();
            $.ajax({
                url: '{{ route('leads.update.status', ['id' => ':id']) }}'.replace(':id', id),
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), status: status },
                success: function(response) {
                    table.ajax.reload();
                },
                error: function(xhr) {
                    alert('Error updating status: ' + xhr.responseText);
                }
            });
        });

        // Confirm reminder
        $('#leadTable').on('click', '.confirm-reminder', function(e) {
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
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        alert('Error confirming reminder: ' + xhr.responseText);
                    }
                });
            }
        });

        // View attachments modal
        $('#leadTable').on('click', '.view-attachments', function() {
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
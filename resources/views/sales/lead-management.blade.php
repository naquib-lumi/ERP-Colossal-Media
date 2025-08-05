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
                        <tr class="filter-row">
                            <th><input type="text" class="form-control form-control-sm" placeholder="Search Lead Data" id="filter-lead-data"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Search Company" id="filter-company-name"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Search Lead" id="filter-lead-name"></th>
                            <th>
                                <select class="form-select form-select-sm" id="filter-assigned-to">
                                    <option value="">All</option>
                                    @foreach (\App\Models\User::where('role', 'salesperson')->get() as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th>
                                <select class="form-select form-select-sm" id="filter-reminder">
                                    <option value="">All</option>
                                    <option value="No Reminder">No Reminder</option>
                                    @foreach (['accept', 'reject', 'followup', 'new'] as $status)
                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th></th> <!-- No filter for Actions -->
                        </tr>
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
                        <tr>
                            <td>{{ $lead->id }}</td>
                            <td>{{ $lead->company_name }}</td>
                            <td>{{ $lead->name }}</td>
                            <td>{{ $lead->user->name ?? 'Unassigned' }}</td>
                            <td>{{ $lead->date ?? 'No Reminder' }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary edit-lead" data-id="{{ $lead->id }}">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-lead" data-id="{{ $lead->id }}">Delete</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
                    d.lead_data = $('#filter-lead-data').val();
                    d.company_name = $('#filter-company-name').val();
                    d.lead_name = $('#filter-lead-name').val();
                    d.assigned_to = $('#filter-assigned-to').val();
                    d.reminder = $('#filter-reminder').val();
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
                { data: 'id', name: 'id' },
                { data: 'company_name', name: 'company_name' },
                { data: 'name', name: 'name' },
                { data: 'user.name', name: 'user.name', defaultContent: 'Unassigned' },
                { data: 'date', name: 'date', defaultContent: 'No Reminder' },
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
            columnDefs: [
                {
                    targets: -1,
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary edit-lead" data-id="${row.id}">Edit</button>
                                <button class="btn btn-sm btn-danger delete-lead" data-id="${row.id}">Delete</button>
                            </div>
                        `;
                    }
                }
            ],
            initComplete: function() {
                this.api().columns().every(function() {
                    var column = this;
                    var input = $(column.header()).find('input, select');
                    if (input.length) {
                        input.on('keyup change', function() {
                            column.search(this.value).draw();
                        });
                    }
                });
            }
        });

        $('#leadTable').on('click', '.edit-lead', function() {
            const id = $(this).data('id');
            window.location.href = `/leads/${id}/edit`;
        });

        $('#leadTable').on('click', '.delete-lead', function() {
            if (confirm('Are you sure you want to delete this lead?')) {
                const id = $(this).data('id');
                $.ajax({
                    url: `/api/leads/${id}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        alert('Error deleting lead: ' + xhr.responseText);
                    }
                });
            }
        });
    });
</script>
@endsection
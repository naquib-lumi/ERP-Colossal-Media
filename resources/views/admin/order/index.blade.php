@extends('layouts.app')

@section('title', 'Job Order')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <h5 class="card-header bg-primary text-white pb-2 pt-2 d-flex justify-content-between align-items-center">
            Job Order
        </h5>
        <div class="card-datatable table-responsive p-3">
            <div class="row mb-3 mx-0">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="globalSearch" placeholder="Search by company, lead name">
                </div>
                <div class="col-md-4">
                    <select class="form-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="to_assign">To Assign</option>
                        <option value="assigned">Assigned</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                @if (Auth::user()->hasRole('head-salesperson'))
                <div class="col-md-4">
                    <select class="form-control" id="salespersonFilter">
                        <option value="">All Salespersons</option>
                        @foreach (App\Models\User::role('salesperson')->get() as $sp)
                            <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <table class="datatables-ajax table table table-hover" id="orderTable" style="width: 100%;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Order ID</th>
                        <th>Order Name</th>
                        <th>Company Info</th>
                        <th>Lead Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="productsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <table class="table" id="productsTable">
                    <thead><tr><th>Name</th><th>Quantity</th><th>Remark</th><th>Material</th><th>Location</th><th>Date Time</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let table = $('#orderTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('admin.orders.data') }}',
                type: 'POST',
                data: function(d) {
                    d._token = '{{ csrf_token() }}';
                    d.search = { value: $('#globalSearch').val() };
                    d.status = $('#statusFilter').val();
                    d.salesperson = $('#salespersonFilter').val();
                    return d;
                }
            },
            columns: [
                { data: 'order_id' },
                { data: 'order_name' },
                { data: 'company_info' },
                { data: 'lead_details' },
                { data: 'status' },
                { data: 'actions' }
            ],
            order: [[0, 'desc']],
            initComplete: function() {
                $('#globalSearch').on('keyup', function() {
                    table.draw();
                });
                $('#statusFilter, #salespersonFilter').on('change', function() {
                    table.draw();
                });
            }
        });

        $(document).on('click', '.view-products', function() {
            let id = $(this).data('id');
            $.ajax({
                url: '/orders/' + id + '/products',
                method: 'GET',
                success: function(data) {
                    let tbody = $('#productsTable tbody');
                    tbody.empty();
                    data.forEach(product => {
                        tbody.append(`<tr>
                            <td>${product.product_name}</td>
                            <td>${product.quantity}</td>
                            <td>${product.remark || ''}</td>
                            <td>${product.material_info || ''}</td>
                            <td>${product.location || ''}</td>
                            <td>${product.date_time || ''}</td>
                        </tr>`);
                    });
                    $('#productsModal').modal('show');
                }
            });
        });

        $(document).on('click', '.submit-order', function() {
            let id = $(this).data('id');
            $.ajax({
                url: '/orders/' + id + '/submit',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    table.ajax.reload();
                }
            });
        });
    });
</script>
@endsection
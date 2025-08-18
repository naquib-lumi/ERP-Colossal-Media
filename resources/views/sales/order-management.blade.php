@extends('layouts.app')

@section('title', 'Sales Management')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <h5 class="card-header bg-primary text-white pb-2 pt-2 d-flex justify-content-between align-items-center">
            Sales Management
            <a href="{{ route('orders.create') }}" class="btn btn-light text-primary">Add Order</a>
        </h5>
        <div class="card-datatable table-responsive p-3">
            <div class="row mb-3 mx-0">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="globalSearch" placeholder="Search orders...">
                </div>
            </div>
            <table class="datatables-ajax table table-striped table-hover" id="orderTable" style="width: 100%;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th style="width: 15%;">Order Data</th>
                        <th style="width: 20%;">Lead Details</th>
                        <th style="width: 20%;">Order Details</th>
                        <th style="width: 25%;">Products</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let table = $('#orderTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('orders.get') }}',
                type: 'POST',
                data: function(d) {
                    d._token = '{{ csrf_token() }}';
                    d.search = { value: $('#globalSearch').val() };
                    return d;
                }
            },
            columns: [
                { data: 'order_data' },
                { data: 'lead_details' },
                { data: 'order_details' },
                { data: 'products' },
                { data: 'actions' }
            ],
            order: [[0, 'desc']],
            initComplete: function() {
                $('#globalSearch').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }
        });
    });
</script>
@endsection
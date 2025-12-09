@extends('layouts.app')

@section('title', 'Admin Calendar')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="user-role" content="{{ Auth::user()->hasRole('admin') ? 'admin' : '' }}">

    <style>
        .app-calendar-wrapper .row.g-0 {
            align-items: flex-start;
        }

        .app-calendar-content .card {
            margin-top: 0 !important;
            box-shadow: none;
            border: 0;
        }

        .app-calendar-content .card-body {
            padding-top: .25rem !important;
        }

        #calendarToolbar .form-control,
        #calendarToolbar .btn,
        #calendarToolbar .input-group-text {
            height: 36px;
            padding: 0 .65rem;
        }

        #calendarToolbar,
        .btn-toolbar {
            gap: .5rem;
            margin-bottom: .5rem;
            padding: .5rem;
            justify-content: flex-end;
        }

        .status-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        @media (max-width: 1200px) {
            #calendarToolbar {
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        .tab-content .tab-pane {
            padding: 1rem;
        }
    </style>

    <div class="card app-calendar-wrapper">
        <div class="row g-0">
            <!-- Calendar Sidebar -->
            <div class="col app-calendar-sidebar flex-grow-0 border-end" id="app-calendar-sidebar">
                <div class="p-4">
                    <h5>Admin Calendar</h5>
                    <div class="px-3 pt-2">
                        <div class="inline-calendar" data-flatpickr></div>
                    </div>
                    <hr class="mx-4 my-3" />
                </div>
            </div>
            <!-- /Calendar Sidebar -->

            <!-- Calendar & Tabs -->
            <div class="col app-calendar-content">
                <ul class="nav nav-tabs" id="calendarTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="salesperson-tab" data-bs-toggle="tab" href="#salesperson-calendar"
                            role="tab" aria-controls="salesperson-calendar" aria-selected="true">Salesperson
                            Calendar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="order-tab" data-bs-toggle="tab" href="#order-calendar" role="tab"
                            aria-controls="order-calendar" aria-selected="false">Delivery Calendar</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Salesperson Calendar Tab -->
                    <div class="tab-pane fade show active" id="salesperson-calendar" role="tabpanel"
                        aria-labelledby="salesperson-tab">
                        <div id="calendarToolbar" class="d-flex align-items-center">
                            <div class="input-group" style="min-width:230px;max-width:260px;">
                                <select id="filter-salesperson" class="form-select">
                                    <option value="">All Salespeople</option>
                                    @foreach ($salespeople as $sp)
                                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-auto">

                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-4 btn-toolbar justify-content-start">
                            <div>
                                <h6 class="mb-2">Meeting Status</h6>
                                <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0 ps-2">
                                    <li class="d-flex align-items-center">
                                        <span class="status-dot me-2" style="background-color:#4e73df;"></span> Scheduled
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <span class="status-dot me-2" style="background-color:#000a0b;"></span> Cancelled
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <span class="status-dot me-2" style="background-color:#f6c23e;"></span> Postponed
                                    </li>

                                </ul>
                            </div>
                        </div>


                        <div class="card shadow-none border-0">
                            <div class="card-body pb-0">
                                <div id="salespersonCalendar" style="min-height:700px"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Delivery Calendar Tab -->
                    <div class="tab-pane fade" id="order-calendar" role="tabpanel" aria-labelledby="order-tab">
                        <div id="orderToolbar" class="d-flex align-items-center">
                            <div class="d-flex align-items-center gap-2 ms-auto">

                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-4 btn-toolbar justify-content-start">
                            <div>
                                <h6 class="mb-2">Delivery Method</h6>
                                <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0 ps-2">
                                    <li class="d-flex align-items-center">
                                        <span class="status-dot me-2" style="background-color:#007bff;"></span> Self
                                        Pickup
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <span class="status-dot me-2" style="background-color:#28a745;"></span> Courier
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <span class="status-dot me-2" style="background-color:#0dcaf0;"></span> Delivery
                                    </li>
                                        <li class="d-flex align-items-center">
                                        <span class="status-dot me-2" style="background-color:#dc3545;"></span> Installation
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card shadow-none border-0">
                            <div class="card-body pb-0">
                                <div id="orderCalendar" style="min-height:700px"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="app-overlay"></div>
            </div>
            <!-- /Calendar & Tabs -->
        </div>
    </div>
@endsection

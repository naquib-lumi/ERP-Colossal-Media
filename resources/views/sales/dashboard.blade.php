@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('content')
    <h1>Dashboard Overview</h1>
    <p class="mb-4">Welcome, {{ auth()->user()->name }}! Here's your sales activity summary for {{ $currentYear }}.</p>
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row" >
            <!-- Left Column: Sales Activity -->
            <div class="col-md-6 mb-4">
               <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <div class="card-title me-2">
                        <h5 class="mb-1">Sales Activity</h5>
                        <p class="card-subtitle">Monthly lead status counts for {{ $currentYear }}</p>
                    </div>
                </div>
                <div class="p-3 rounded w-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <div class="me-2" style="width: 16px; height: 16px; background-color: #28c76f; "></div>
                            <span>Accept</span>
                        </div>
                        <strong>{{ $acceptCount }}</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <div class="me-2" style="width: 16px; height: 16px; background-color: #000000;"></div>
                            <span>Rejected</span>
                        </div>
                        <strong> {{ $rejectCount }}</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="me-2" style="width: 16px; height: 16px; background-color: #ff9f43;"></div>
                            <span>Follow Up</span>
                        </div>
                        <strong>{{ $followupCount }}</strong>
                    </div>
                </div>

                <div class="card-body px-1 pb-0">
                    <div id="salesActivityChart" class="w-100" style="background-color: transparent;"></div>
                </div>
            </div>

            </div>
            <!-- Right Column: Upcoming Meetings -->
            <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Upcoming Meetings</h5>
                </div>

                <div class="card-body">
                    @forelse ($meetings as $meeting)
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-bold">{{ $meeting->title }}</h6>
                                    <small class="text-muted">{{ $meeting->start_time->format('h:i A') }} - {{ $meeting->end_time->format('h:i A') }}</small>
                                </div>
                                <span class="badge bg-primary">{{ $meeting->start_time->diffForHumans() }}</span>
                            </div>
                      <p class="mt-2 mb-0 text-muted">
                        @if ($meeting->location)
                            @if (filter_var($meeting->location, FILTER_VALIDATE_URL))
                                <a href="{{ $meeting->location }}" target="_blank" rel="noopener">
                                    {{ $meeting->location }}
                                </a>
                            @else
                                {{ $meeting->location }}
                            @endif
                        @else
                            No Location provided
                        @endif
                    </p>
                        </div>
                    @empty
                        <div class="text-center text-muted">
                            No upcoming meetings.
                        </div>
                    @endforelse
                </div>
            </div>
          </div>

        </div>

        <!-- <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Leads (Accepted): {{ $acceptCount }}</h5>
                        <h5>Leads (Rejected): {{ $rejectCount }}</h5>
                        <h5>Leads (FollowUp): {{ $followupCount }}</h5>
                        <h5>Leads (New): {{ $leads->where('status', 'new')->count() }}</h5>
                        <h5>Total Leads: {{ $leads->count() }}</h5>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="card">
  <div class="card-header">
    <h5 class="mb-0">Quick Shortcuts</h5>
  </div>
  <div class="card-body">
    <div class="row row-bordered overflow-visible g-3 text-center">
      <div class="col-6 col-md-3">
        <div class="p-3 border rounded text-center h-100">
          <a href="{{ route('sales.calendar') }}" class="stretched-link text-decoration-none text-body">
            <div class="mb-2">
              <i class="bx bx-calendar fs-2 text-primary"></i>
            </div>
            <strong>Calendar</strong><br>
            <small>View Schedule</small>
          </a>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 border rounded text-center h-100">
          <a href="" class="stretched-link text-decoration-none text-body">
            <div class="mb-2">
              <i class="bx bx-video fs-2 text-info"></i>
            </div>
            <strong>Meetings</strong><br>
            <small>All Meetings</small>
          </a>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 border rounded text-center h-100">
          <a href="" class="stretched-link text-decoration-none text-body">
            <div class="mb-2">
              <i class="bx bx-user-plus fs-2 text-success"></i>
            </div>
            <strong>New Client</strong><br>
            <small>Register Client</small>
          </a>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 border rounded text-center h-100">
          <a href="" class="stretched-link text-decoration-none text-body">
            <div class="mb-2">
              <i class="bx bx-group fs-2 text-warning"></i>
            </div>
            <strong>Clients</strong><br>
            <small>Browse List</small>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

    </div>

    @if (Request::is('sales/dashboard') || Request::is('sales/dashboard/*'))
        <script>
            'use strict';

            document.addEventListener('DOMContentLoaded', function (e) {
                let cardColor, headingColor, labelColor, legendColor, borderColor, fontFamily;

                cardColor = '#fff';
                headingColor = '#333';
                labelColor = '#666';
                legendColor = '#666';
                borderColor = '#ddd';
                fontFamily = 'Public Sans, sans-serif';

                // Sales Activity (Bar Chart) - Reverted to old styling
                const salesActivityChartEl = document.querySelector('#salesActivityChart');
                if (salesActivityChartEl) {
                    const salesActivityChartConfig = {
                        chart: {
                            type: 'bar',
                            height: 235, // Match the card-body height
                            stacked: true,
                            toolbar: { show: false }
                        },
                        series: [
                            {
                                name: 'Accepted/Month',
                                data: @json(array_slice($acceptCounts, 0, $currentMonth))
                            },
                            {
                                name: 'Rejected/Month',
                                data: @json(array_slice($rejectCounts, 0, $currentMonth))
                            },
                            {
                                name: 'FollowUp/Month',
                                data: @json(array_slice($followupCounts, 0, $currentMonth))
                            }
                        ],
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '40%',
                                borderRadius: 9,
                                startingShape: 'rounded',
                                endingShape: 'rounded',
                                borderRadiusApplication: 'around'
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: {
                            curve: 'smooth',
                            width: 6,
                            lineCap: 'round',
                            colors: [cardColor]
                        },
//                  legend: {
//   show: true,
//   position: 'top',
//   horizontalAlign: 'center',
//   labels: {
//     colors: labelColor,
//     useSeriesColors: false
//   },
//   markers: {
//     width: 12,
//     height: 12,
//     radius: 12
//   }
// },
                        legend:{
                          show:false
                        },
             colors: ['#28c76f', '#000000', '#ff9f43'], // Accepted, Rejected (black), Follow Up


                        fill: { opacity: 1 },
                        grid: {
                            show: false,
                            strokeDashArray: 7,
                            padding: { top: 0, left: 0, right: 0 } // Adjusted padding to fit bottom
                        },
                        xaxis: {
                            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'].slice(0, @json($currentMonth)),
                            labels: {
                                show: true,
                                style: {
                                    colors: labelColor,
                                    fontSize: '15px',
                                    fontFamily: fontFamily
                                }
                            },
                            axisBorder: { show: false },
                            axisTicks: { show: false }
                        },
                        yaxis: { show: false },
                        responsive: [
                            { breakpoint: 1440, options: { plotOptions: { bar: { borderRadius: 10, columnWidth: '50%' } } } },
                            { breakpoint: 1300, options: { plotOptions: { bar: { borderRadius: 11, columnWidth: '55%' } } } },
                            { breakpoint: 1200, options: { plotOptions: { bar: { borderRadius: 10, columnWidth: '45%' } } } },
                            { breakpoint: 1040, options: { plotOptions: { bar: { borderRadius: 10, columnWidth: '50%' } } } },
                            { breakpoint: 992, options: { plotOptions: { bar: { borderRadius: 12, columnWidth: '40%' } }, chart: { height: 320 } } },
                            { breakpoint: 768, options: { plotOptions: { bar: { borderRadius: 11, columnWidth: '25%' } } } },
                            { breakpoint: 576, options: { plotOptions: { bar: { borderRadius: 10, columnWidth: '35%' } } } },
                            { breakpoint: 440, options: { plotOptions: { bar: { borderRadius: 10, columnWidth: '45%' } } } },
                            { breakpoint: 360, options: { plotOptions: { bar: { borderRadius: 8, columnWidth: '50%' } } } }
                        ],
                        states: {
                            hover: { filter: { type: 'none' } },
                            active: { filter: { type: 'none' } }
                        }
                    };
                    const salesActivityChart = new ApexCharts(salesActivityChartEl, salesActivityChartConfig);
                    salesActivityChart.render();
                }


            });
        </script>
    @endif
@endsection
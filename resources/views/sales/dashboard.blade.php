@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('content')
    <h1>Dashboard Overview</h1>
    <p class="mb-4">Welcome, {{ auth()->user()->name }}! Here's your sales activity summary for {{ $currentYear }}.</p>
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- Left Column: Sales Activity -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div class="card-title me-2">
                            <h5 class="mb-1">Sales Activity</h5>
                            <p class="card-subtitle">
                                @if (auth()->user()->hasRole('head-salesperson') && $selectedSalespersonId)
                                    Monthly lead status counts for {{ $currentYear }} - {{ $salespeople->find($selectedSalespersonId)?->name }}
                                @else
                                    Monthly lead status counts for {{ $currentYear }}
                                @endif
                            </p>
                        </div>
                        @if (auth()->user()->hasRole('head-salesperson'))
                        <div class="ms-auto">
                            <form method="GET" action="{{ route('sales.dashboard') }}" class="d-inline">
                                <select name="salesperson_id" id="salesperson_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                    <option value="">All</option>
                                    @foreach ($salespeople as $sp)
                                        <option value="{{ $sp->id }}" {{ $selectedSalespersonId == $sp->id ? 'selected' : '' }}>
                                            {{ $sp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        @endif
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

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <div class="me-2" style="width: 16px; height: 16px; background-color: #ff9f43;"></div>
                                <span>Follow Up</span>
                            </div>
                            <strong>{{ $followupCount }}</strong>
                        </div>

                        <!-- ✅ 新增：Meeting 统计（蓝色） -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="me-2" style="width: 16px; height: 16px; background-color: #3B82F6;"></div>
                                <span>Meeting</span>
                            </div>
                            <strong>{{ $meetingCount }}</strong>
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
                <div class="border rounded p-3 mb-3 bg-light meeting-item" data-meeting-id="{{ $meeting->id }}" style="cursor: pointer;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1 fw-bold">{{ $meeting->title }}</h6>
                            <small class="text-muted">{{ $meeting->start_time->format('h:i A') }} -
                                {{ $meeting->end_time->format('h:i A') }}</small>
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
                        @elseif ($meeting->url)
                            <a href="{{ $meeting->url }}" target="_blank" rel="noopener">
                                {{ $meeting->url }}
                            </a>
                        @else
                            No Location or URL provided
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

<!-- Meeting Details Modal -->
<div class="modal fade" id="meetingDetailsModal" tabindex="-1" aria-labelledby="meetingDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="meetingDetailsModalLabel">Meeting Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="meetingDetailsContent">
                    <!-- Populated by JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle meeting item click
    document.querySelectorAll('.meeting-item').forEach(item => {
        item.addEventListener('click', function() {
            const meetingId = this.dataset.meetingId;
            fetch(`/meetings/${meetingId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch');
                    return response.json();
                })
                .then(data => {
                    const content = document.getElementById('meetingDetailsContent');
                    content.innerHTML = `
                        <h6>${data.title}</h6>
                        <p><strong>Start:</strong> ${new Date(data.start_time).toLocaleString()}</p>
                        <p><strong>End:</strong> ${new Date(data.end_time).toLocaleString()}</p>
                        <p><strong>Status:</strong> <span id="status-display">${data.status}</span></p>
                        ${data.note ? `<p><strong>Note:</strong> ${data.note}</p>` : ''}
                        ${data.lead ? `<p><strong>Lead:</strong> ${data.lead.name}</p>` : ''}
                        <p><strong>Location/URL:</strong> ${data.location || data.url || 'None'}</p>
                        <select class="form-select mt-2" id="status-select" data-id="${data.id}">
                            <option value="scheduled" ${data.status === 'scheduled' ? 'selected' : ''}>Scheduled</option>
                            <option value="canceled" ${data.status === 'canceled' ? 'selected' : ''}>Canceled</option>
                            <option value="postponed" ${data.status === 'postponed' ? 'selected' : ''}>Postponed</option>
                        </select>
                    `;
                    const select = document.getElementById('status-select');
                    select.addEventListener('change', function() {
                        const status = this.value;
                        fetch(`/meetings/${meetingId}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ status: status })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Failed to update');
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                document.getElementById('status-display').textContent = status;
                            }
                        })
                        .catch(error => console.error('Error updating status:', error));
                    });
                    new bootstrap.Modal(document.getElementById('meetingDetailsModal')).show();
                })
                .catch(error => console.error('Error fetching meeting:', error));
        });
    });
});
</script>

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
                                    <i class="bx bx-calendar fs-2"></i>
                                </div>
                                <strong>Calendar</strong><br>
                                <small>View Schedule</small>
                            </a>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded text-center h-100">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#meetingsModal"
                                class="stretched-link text-decoration-none text-body">
                                <div class="mb-2">
                                    <i class="bx bx-video fs-2"></i>
                                </div>
                                <strong>Meetings</strong><br>
                                <small>All Meetings</small>
                            </a>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded text-center h-100">
                            <a href="{{ route('leads.create') }}" class="stretched-link text-decoration-none text-body">
                                <div class="mb-2">
                                    <i class="bx bx-user-plus fs-2 "></i>
                                </div>
                                <strong>New Client</strong><br>
                                <small>Register Client</small>
                            </a>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded text-center h-100">
                            <a href="{{ route('sales.leads') }}" class="stretched-link text-decoration-none text-body">
                                <div class="mb-2">
                                    <i class="bx bx-group fs-2 "></i>
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

    <div class="modal fade" id="meetingsModal" tabindex="-1" aria-labelledby="meetingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="meetingsModalLabel">All Meetings this Month</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="meetingsList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @if (Request::is('sales/dashboard') || Request::is('sales/dashboard/*'))
        <script>
    'use strict';

    document.addEventListener('DOMContentLoaded', function(e) {
        let cardColor, headingColor, labelColor, legendColor, borderColor, fontFamily;

        cardColor = '#fff';
        headingColor = '#333';
        labelColor = '#666';
        legendColor = '#666';
        borderColor = '#ddd';
        fontFamily = 'Public Sans, sans-serif';

        // In controller: $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Sales Activity (Bar Chart) - Reverted to old styling
        const salesActivityChartEl = document.querySelector('#salesActivityChart');
        if (salesActivityChartEl) {
            const salesActivityChartConfig = {
                chart: {
                    type: 'bar',
                    height: 235, // Match the card-body height
                    stacked: true,
                    toolbar: {
                        show: false
                    }
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
                    },
                    {
                        name: 'Meeting/Month',
                        data: @json(array_slice($meetingCounts, 0, $currentMonth))
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
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 6,
                    lineCap: 'round',
                    colors: [cardColor]
                },
                legend: {
                    show: false
                },
                // ✅ 颜色列表追加蓝色（与上方统计色一致）
                colors: ['#28c76f', '#000000', '#ff9f43', '#3B82F6'],

                fill: {
                    opacity: 1
                },
                grid: {
                    show: false,
                    strokeDashArray: 7,
                    padding: {
                        top: 0,
                        left: 0,
                        right: 0
                    } // Adjusted padding to fit bottom
                },
                xaxis: {
                    categories: @json(array_slice($monthNames, 0, $currentMonth)),
                    labels: {
                        show: true,
                        style: {
                            colors: labelColor,
                            fontSize: '15px',
                            fontFamily: fontFamily
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    show: false
                },
                responsive: [
                    {
                        breakpoint: 1440,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 10,
                                    columnWidth: '50%'
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 1300,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 11,
                                    columnWidth: '55%'
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 1200,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 10,
                                    columnWidth: '45%'
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 1040,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 10,
                                    columnWidth: '50%'
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 992,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 12,
                                    columnWidth: '40%'
                                }
                            },
                            chart: {
                                height: 320
                            }
                        }
                    },
                    {
                        breakpoint: 768,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 11,
                                    columnWidth: '25%'
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 576,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 10,
                                    columnWidth: '35%'
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 440,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 10,
                                    columnWidth: '45%'
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 360,
                        options: {
                            plotOptions: {
                                bar: {
                                    borderRadius: 8,
                                    columnWidth: '50%'
                                }
                            }
                        }
                    }
                ],
                states: {
                    hover: {
                        filter: {
                            type: 'none'
                        }
                    },
                    active: {
                        filter: {
                            type: 'none'
                        }
                    }
                }
            };
            const salesActivityChart = new ApexCharts(salesActivityChartEl, salesActivityChartConfig);
            salesActivityChart.render();
        }


    });
</script>
       <script>
    document.addEventListener('DOMContentLoaded', function() {
        function fetchMeetings() {
            fetch('/meetings')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    let html = '<ul class="list-group">';
                    const currentDate = new Date();
                    data.forEach(meeting => {
                        const startDate = new Date(meeting.start_time);
                        const overdue = (startDate < currentDate && meeting.status === 'scheduled') ? ' (Overdue)' : '';
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>${meeting.title}</h6>
                                        <small>${startDate.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })} - ${new Date(meeting.end_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</small><br>
                                        <small>Status: <span class="status-${meeting.id}">${meeting.status}${overdue}</span></small><br>
                                        ${meeting.lead ? `<small>Lead: ${meeting.lead.name}</small><br>` : ''}
                                        ${meeting.user ? `<small>Responsible: ${meeting.user.name}</small>` : ''}
                                    </div>
                                    <select class="form-select status-select" data-id="${meeting.id}">
                                        <option value="scheduled" ${meeting.status === 'scheduled' ? 'selected' : ''}>Scheduled</option>
                                        <option value="canceled" ${meeting.status === 'canceled' ? 'selected' : ''}>Canceled</option>
                                        <option value="postponed" ${meeting.status === 'postponed' ? 'selected' : ''}>Postponed</option>
                                    </select>
                                </li>`;
                    });
                    html += '</ul>';
                    document.getElementById('meetingsList').innerHTML = html;

                    document.querySelectorAll('.status-select').forEach(select => {
                        select.addEventListener('change', function() {
                            updateStatus(this.dataset.id, this.value);
                        });
                    });
                })
                .catch(error => {
                    console.error('Error fetching meetings:', error);
                    document.getElementById('meetingsList').innerHTML = '<p class="text-danger">Error loading meetings.</p>';
                });
        }

        function updateStatus(id, status) {
            fetch(`/calendar/meetings/${id}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.querySelector(`.status-${id}`).innerText = status;
                }
            })
            .catch(error => {
                console.error('Error updating status:', error);
            });
        }

        const meetingsModal = document.getElementById('meetingsModal');
        if (meetingsModal) {
            meetingsModal.addEventListener('show.bs.modal', fetchMeetings);
        }
        });
     </script>
    @endif
@endsection
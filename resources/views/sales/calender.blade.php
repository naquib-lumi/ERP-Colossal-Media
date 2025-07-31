@extends('layouts.app')

@section('title', 'Calendar')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Calendar</h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('sales.schedule-meeting') }}" class="btn btn-primary mb-3">Schedule Meeting</a>
                    <div id="calendar"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var calendarEl = document.getElementById('calendar');
                            var calendar = new FullCalendar.Calendar(calendarEl, {
                                initialView: 'dayGridMonth',
                                events: [
                                    @foreach($meetings as $meeting)
                                    {
                                        title: '{{ $meeting->title }}',
                                        start: '{{ $meeting->start_time }}',
                                        end: '{{ $meeting->end_time }}',
                                        status: '{{ $meeting->status }}'
                                    },
                                    @endforeach
                                ]
                            });
                            calendar.render();
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
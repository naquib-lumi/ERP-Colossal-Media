@extends('layouts.app')

@section('title', 'Sales Calendar')

@section('content')
<div class="card app-calendar-wrapper">
    <div class="row g-0">
        <!-- Calendar Sidebar -->
        <div class="col app-calendar-sidebar flex-grow-0 border-end" id="app-calendar-sidebar">
            <div class="p-4">
                <button class="btn btn-primary btn-toggle-sidebar w-100 mb-2"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#addReminderSidebar"
                        aria-controls="addReminderSidebar">
                    <i class="bx bx-plus me-2"></i>
                    <span class="align-middle">Add Reminder</span>
                </button>
                <button class="btn btn-primary btn-toggle-sidebar w-100"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#addMeetingSidebar"
                        aria-controls="addMeetingSidebar">
                    <i class="bx bx-plus me-2"></i>
                    <span class="align-middle">Add Meeting</span>
                </button>
            </div>
            <div class="px-3 pt-2">
                <div class="inline-calendar" data-flatpickr></div>
            </div>
            <hr class="mx-4 my-3" />
            <div class="px-4 pb-2">
                <h5 class="mb-3">Event Filters</h5>
                <div class="form-check form-check-secondary mb-3 ms-2">
                    <input class="form-check-input select-all" type="checkbox" id="selectAll" data-value="all" checked />
                    <label class="form-check-label" for="selectAll">View All</label>
                </div>
                <div class="app-calendar-events-filter">
                    <div class="form-check form-check-primary mb-3 ms-2">
                        <input class="form-check-input input-filter" type="checkbox" id="select-meeting" data-value="meeting" checked />
                        <label class="form-check-label" for="select-meeting">Meeting</label>
                    </div>
                    <div class="form-check form-check-warning mb-3 ms-2">
                        <input class="form-check-input input-filter" type="checkbox" id="select-reminder" data-value="reminder" checked />
                        <label class="form-check-label" for="select-reminder">Reminder</label>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Calendar Sidebar -->

        <!-- Calendar & Modals -->
        <div class="col app-calendar-content">
            <div class="card shadow-none border-0">
                <div class="card-body pb-0">
                    <div id="calendar"></div>
                </div>
            </div>
            <div class="app-overlay"></div>

            <!-- Reminder Offcanvas -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="addReminderSidebar" aria-labelledby="addReminderSidebarLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="addReminderSidebarLabel">Add Reminder</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form class="pt-0" id="reminderForm" onsubmit="return false">
                        @csrf
                        <input type="hidden" name="id">
                        <div class="mb-3">
                            <label class="form-label" for="reminderLeadId">Lead</label>
                            <select class="form-select select2" id="reminderLeadId" name="lead_id" required>
                                <option value="">Search for a lead</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reminderTitle">Title</label>
                            <input type="text" class="form-control" id="reminderTitle" name="title" placeholder="Reminder Title" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reminderDueDate">Due Date & Time</label>
                            <input type="datetime-local" class="form-control" id="reminderDueDate" name="due_date" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="recurrenceType">Recurrence Type</label>
                            <select class="form-select select2" id="recurrenceType" name="recurrence_type">
                                <option value="none">None</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="recurrenceTime">Recurrence Time</label>
                            <input type="time" class="form-control" id="recurrenceTime" name="recurrence_time" value="{{ now()->format('H:i') }}" />
                        </div>
                        <div class="d-flex mt-4 gap-2">
                            <button type="submit" class="btn btn-primary btn-add-reminder me-2">Add</button>
                            <button type="reset" class="btn btn-label-secondary btn-cancel" data-bs-dismiss="offcanvas">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Meeting Offcanvas -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="addMeetingSidebar" aria-labelledby="addMeetingSidebarLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="addMeetingSidebarLabel">Add Meeting</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form class="pt-0" id="meetingForm" onsubmit="return false">
                        @csrf
                        <input type="hidden" name="id">
                        <div class="mb-3">
                            <label class="form-label" for="meetingLeadId">Lead</label>
                            <select class="form-select select2" id="meetingLeadId" name="lead_id" required>
                                <option value="">Search for a lead</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="meetingTitle">Title</label>
                            <input type="text" class="form-control" id="meetingTitle" name="title" placeholder="Meeting Title" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="meetingStartTime">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="meetingStartTime" name="start_time" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="meetingDuration">Duration (minutes)</label>
                            <input type="number" class="form-control" id="meetingDuration" name="duration" min="1" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeOnline" value="online" checked />
                                <label class="form-check-label" for="typeOnline">Online</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeOffline" value="offline" />
                                <label class="form-check-label" for="typeOffline">Offline</label>
                            </div>
                        </div>
                        <div class="mb-3" id="onlineUrl" style="display: block;">
                            <label class="form-label" for="meetingUrl">URL</label>
                            <input type="url" class="form-control" id="meetingUrl" name="url" placeholder="https://example.com" />
                        </div>
                        <div class="mb-3" id="offlineLocation" style="display: none;">
                            <label class="form-label" for="meetingLocation">Location</label>
                            <input type="text" class="form-control" id="meetingLocation" name="location" placeholder="Enter Location" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="meetingNote">Description</label>
                            <textarea class="form-control" id="meetingNote" name="note" placeholder="Meeting Description"></textarea>
                        </div>
                        <div class="d-flex mt-4 gap-2">
                            <button type="submit" class="btn btn-primary btn-add-meeting me-2">Add</button>
                            <button type="reset" class="btn btn-label-secondary btn-cancel" data-bs-dismiss="offcanvas">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Calendar & Modals -->
    </div>
</div>
@endsection
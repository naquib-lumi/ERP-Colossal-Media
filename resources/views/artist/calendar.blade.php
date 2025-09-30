@extends('layouts.app')
@section('title', 'Artist Calendar')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="user-id" content="{{ $artistId ?? Auth::id() }}">

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

  #artistCalendarWrapper {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    min-height: calc(100vh - 120px); /* adjust header/footer space */
  }

  #app-calendar-sidebar {
    display: flex;
    flex-direction: column;
    height: auto;     /* let flex stretch handle */
    flex: 0 0 300px;  /* keep your width */
    min-height: 100%;
  }

  #app-calendar-sidebar .card,
  #app-calendar-sidebar > div {
    flex-shrink: 0;
  }

  .app-calendar-wrapper.sidebar-collapsed #app-calendar-sidebar { display: none !important; }
  .app-calendar-wrapper.sidebar-collapsed .app-calendar-content {
    width: 100% !important;
    flex: 0 0 100% !important;
  }

  /* One-line toolbar that stays tight under the app header */
  #calendarToolbar .form-control,
  #calendarToolbar .btn,
  #calendarToolbar .input-group-text {
    height: 36px;
    padding: 0 .65rem;
  }

  #calendarToolbar, .btn-toolbar {
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
</style>

<div class="card app-calendar-wrapper" id="artistCalendarWrapper">
  <div class="row g-0">
    <!-- Sidebar (unchanged layout; we only append Upcoming here) -->
    <div class="col app-calendar-sidebar flex-grow-0 border-end" id="app-calendar-sidebar">
      <div class="px-3 pt-3">
        <button
          type="button"
          class="btn btn-outline-secondary w-100 mb-3 btn-toggle-sidebar"
          id="btnToggleSidebar">
          <i class="bx bx-chevron-left me-1"></i>
          <span>Hide Sidebar</span>
        </button>
      </div>

      <div class="px-3 pt-2">
        <div class="inline-calendar" data-flatpickr></div>
      </div>

      <hr class="mx-4 my-3" />

      <div class="px-4 pb-2">
        <h6 class="mb-2">Event Filters</h6>
        <div class="form-check form-check-secondary mb-2 ms-2">
          <input class="form-check-input select-all" type="checkbox" id="selectAll" data-value="all" checked />
          <label class="form-check-label" for="selectAll">View All</label>
        </div>
        <div class="app-calendar-events-filter ms-1">
          <div class="form-check form-check-primary mb-2">
            <input class="form-check-input input-filter" type="checkbox" id="select-meeting" data-value="meeting" checked />
            <label class="form-check-label" for="select-meeting">Meeting</label>
          </div>
          <div class="form-check form-check-warning mb-3">
            <input class="form-check-input input-filter" type="checkbox" id="select-reminder" data-value="reminder" checked />
            <label class="form-check-label" for="select-reminder">Reminder</label>
          </div>
        </div>
        
      </div>
      <hr class="mx-4 my-3" />
      {{-- Upcoming Meetings (left sidebar) --}}
      <div class="px-4 pb-2">
        <h6>Upcoming Meetings</h6>
        <div class="card card-body p-2" id="upcomingList">
          <div class="text-muted small">No upcoming items.</div>
        </div>
      </div>
    </div>
    <!-- /Sidebar -->

    <!-- Main calendar -->
    <div class="col app-calendar-content">
      {{-- TOP TOOLBAR (single row) --}}
      <div id="calendarToolbar" class="d-flex align-items-center">
        <div class="input-group" style="min-width:260px;max-width:280px;">
          <span class="input-group-text">From</span>
          <input type="date" class="form-control" id="filterStart">
        </div>

        <div class="input-group" style="min-width:240px;max-width:260px;">
          <span class="input-group-text">To</span>
          <input type="date" class="form-control" id="filterEnd">
        </div>

        <div class="input-group" style="min-width:230px;max-width:260px;">
          <select class="form-select" id="filterSalesperson">
            <option value="">Search Salesperson</option>
          </select>
        </div>

        <input type="text" class="form-control" id="searchClient" placeholder="Search by title / client" style="min-width:180px;max-width:240px;">
      </div>

      <div class="d-flex align-items-center gap-4 btn-toolbar justify-content-start" style="margin-bottom: 3rem;">
      <div>
        <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0" style="margin-left:10px;">
          <li class="d-flex align-items-center">
            <span class="status-dot me-2" style="background-color:#4e73df;"></span> Scheduled
          </li>
          <li class="d-flex align-items-center">
            <span class="status-dot me-2" style="background-color:#000a0b;"></span> Cancelled
          </li>
          <li class="d-flex align-items-center">
            <span class="status-dot me-2" style="background-color:#f6c23e;"></span> Postponed
          </li>
          <li class="d-flex align-items-center">
            <span class="status-dot me-2" style="background-color:#28a730;"></span> Reminder
          </li>
        </ul>
      </div>

      <div class="d-flex align-items-center gap-2 ms-auto">
        <button type="button" class="btn btn-outline-secondary" id="btnToday">Today</button>
        <button type="button" class="btn btn-outline-secondary" id="btnReset">Reset</button>
        <button type="button" class="btn btn-outline-primary" id="btnExport">
          <i class="bx bx-download me-1"></i> Export PDF
        </button>
      </div>
    </div>

      <div class="card shadow-none border-0">
        <div class="card-body pb-0">
          <div id="calendar" style="min-height:700px"></div>
        </div>
      </div>

      <div class="app-overlay"></div>
    </div>
    <!-- /Main calendar -->
  </div>
</div>
@endsection

@push('scripts')
{{-- This will be injected by @stack('scripts') in commonMaster.blade.php --}}
<script src="{{ asset('assets/js/artist-app-calendar.js') }}"></script>
@endpush
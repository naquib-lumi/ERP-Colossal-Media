@extends('layouts.app')
@section('title', 'Delivery & Installation Calendar')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="user-id" content="{{ $artistId ?? Auth::id() }}">

<style>
  .modal .btn-close::before {
    background-color: white !important;
  }
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
    
      <div class="px-3 pt-2">
        <div class="inline-calendar" data-flatpickr></div>
      </div>

      <hr class="mx-4 my-3" />

      
      <hr class="mx-4 my-3" />
      <div class="px-4 pb-2">
        <h6>In Progress Delivery & Installations</h6>
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
        <input type="text" class="form-control" id="searchClient" placeholder="Search by title / client" style="width:100%;">
        <button type="button" class="btn btn-outline-secondary" id="btnToday">Today</button>
        <button type="button" class="btn btn-outline-secondary" id="btnReset">Reset</button>
      </div>

      <div class="d-flex align-items-center gap-4 btn-toolbar justify-content-start" style="margin-bottom: 3rem;">
      
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
<script src="{{ asset('assets/js/installation-app-calendar.js') }}"></script>
@endpush
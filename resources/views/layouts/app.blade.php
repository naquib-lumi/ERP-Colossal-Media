@php
  $configData = \App\Helpers\Helpers::appClasses();
@endphp
@section('layoutContent')
  @extends('layouts.commonMaster')
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
          <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">Colossal ERP</span>
          </a>
          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="menu-icon tf-icons ti ti-menu-2 d-none d-xl-block ti-sm align-middle"></i>
            <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
          </a>
        </div>
        <div class="menu-inner-shadow"></div>
        <ul class="menu-inner py-1">
          @if (auth()->check())
            <li class="menu-item {{ request()->routeIs('sales.dashboard', 'admin.dashboard', 'boss.dashboard') ? 'active open' : '' }}">
              <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-smart-home"></i>
                <div data-i18n="Dashboard">Dashboard</div>
              </a>
            </li>
            @if (auth()->user()->role === 'Salesperson')
              <li class="menu-item {{ request()->routeIs('sales.leads') ? 'active' : '' }}">
                <a href="{{ route('sales.leads') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-users"></i>
                  <div data-i18n="Leads">Leads</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('sales.calendar') ? 'active' : '' }}">
                <a href="{{ route('sales.calendar') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-calendar"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('sales.orders') ? 'active' : '' }}">
                <a href="{{ route('sales.orders') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-file-description"></i>
                  <div data-i18n="Orders">Orders</div>
                </a>
              </li>
            @endif
            @if (auth()->user()->role === 'Artist')
              <li class="menu-item {{ request()->routeIs('job.orders') ? 'active' : '' }}">
                <a href="{{ route('job.orders') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-paint"></i>
                  <div data-i18n="Job Orders">Job Orders</div>
                </a>
              </li>
            @endif
            @if (auth()->user()->role === 'Admin')
              <li class="menu-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <a href="{{ route('admin.settings') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-settings"></i>
                  <div data-i18n="Settings">Settings</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <a href="{{ route('admin.reports') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-report"></i>
                  <div data-i18n="Reports">Reports</div>
                </a>
              </li>
            @endif
            @if (in_array(auth()->user()->role, ['Printing', 'Installation', 'Delivery', 'Furnishing']))
              <li class="menu-item {{ request()->routeIs('operations.tasks') ? 'active' : '' }}">
                <a href="{{ route('operations.tasks') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-tools"></i>
                  <div data-i18n="Tasks">Tasks</div>
                </a>
              </li>
            @endif
            @if (auth()->user()->role === 'Boss')
              <li class="menu-item {{ request()->routeIs('boss.reports') ? 'active' : '' }}">
                <a href="{{ route('boss.reports') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-report"></i>
                  <div data-i18n="Reports">Reports</div>
                </a>
              </li>
            @endif
          @endif
        </ul>
      </aside>
      <div class="layout-page">
        @include('layouts.sections.navbar.navigation')
        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            @yield('content')
          </div>
          <!-- <footer class="content-footer footer bg-footer-theme">
            <div class="container-xxl">
              <div
                class="footer-container d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column"
              >
                <div>
                  ©
                  <script>
                    document.write(new Date().getFullYear());
                  </script>
                  , made with ❤️ by <a href="https://pixinvent.com" target="_blank" class="fw-medium">Pixinvent</a>
                </div>
                <div class="d-none d-lg-inline-block">
                  <a href="{{ config('variables.licenseUrl') }}" target="_blank" class="footer-link me-4">License</a>
                  <a href="{{ config('variables.moreThemes') }}" target="_blank" class="footer-link me-4">More Themes</a>
                  <a href="{{ config('variables.documentation') }}" target="_blank" class="footer-link me-4">Documentation</a>
                  <a href="{{ config('variables.support') }}" target="_blank" class="footer-link d-none d-sm-inline-block">Support</a>
                </div>
              </div>
            </div>
          </footer> -->
          <div class="content-backdrop fade"></div>
        </div>
      </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>
  </div>
  @endsection
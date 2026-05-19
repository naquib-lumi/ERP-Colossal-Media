@php
$configData = \App\Helpers\Helpers::appClasses();
@endphp
@section('layoutContent')
@extends('layouts.commonMaster')
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">
    <!-- Menu -->
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" lang="en">
      <div class="app-brand demo">
  <a href="{{ route('dashboard') }}" class="app-brand-link">
    <span class="brand-lg">
      <img src="{{ asset('assets/img/branding/login-logo.png') }}" alt="Logo">
    </span>
    <span class="brand-sm">
      <img src="{{ asset('assets/img/favicon/favicon.ico') }}" alt="Logo">
    </span>
  </a>

  <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
    <i class="icon-base bx bx-chevron-left"></i>
  </a>
</div>

<style>
  .brand-lg, .brand-sm { display:none; line-height:0; }
  .brand-lg img { height:36px; width:auto; display:block; }
  .brand-sm img { height:24px; width:auto; display:block; } /* 可改 26~28px */

  html.layout-menu-expanded .brand-lg,
  html.layout-menu-hover .brand-lg { display:inline-flex; align-items:center; }
  html.layout-menu-expanded .brand-sm,
  html.layout-menu-hover .brand-sm { display:none; }

  html:not(.layout-menu-expanded):not(.layout-menu-hover) .brand-sm { 
    display:inline-flex; align-items:center; 
  }
  html:not(.layout-menu-expanded):not(.layout-menu-hover) .brand-lg { 
    display:none; 
  }
</style>

      <div class="menu-inner-shadow"></div>

      <ul class="menu-inner py-1">
        @if (auth()->check())
        @if (auth()->user()->role !== 'data-entry')
        <li class="menu-item {{ request()->routeIs('sales.dashboard', 'admin.dashboard', 'boss.dashboard') ? 'active open' : '' }}">
          <a href="{{ route('dashboard') }}" class="menu-link">
            <i class="menu-icon icon-base bx bx-home-smile"></i>
                <div data-i18n="Dashboard">Dashboard</div>
              </a>
            </li>
            @endif
            @if (auth()->user()->role === 'salesperson')
              <li class="menu-item {{ request()->routeIs('sales.leads') ? 'active' : '' }}">
                <a href="{{ route('sales.leads') }}" class="menu-link">
             <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="Leads">Leads</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('sales.calendar') ? 'active' : '' }}">
                <a href="{{ route('sales.calendar') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-calendar"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('sales.orders') ? 'active' : '' }}">
                <a href="{{ route('sales.orders') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-file"></i>
                  <div data-i18n="Orders">Orders</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('sales.progress') ? 'active' : '' }}">
                <a href="{{ route('sales.progress') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                  <div data-i18n="Fullfilment">Fullfilment</div>
                </a>
              </li>
            @endif
                 @if (auth()->user()->role === 'head-salesperson')
              <li class="menu-item {{ request()->routeIs('sales.leads') ? 'active' : '' }}">
                <a href="{{ route('sales.leads') }}" class="menu-link">
             <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="Leads">Leads</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('sales.calendar') ? 'active' : '' }}">
                <a href="{{ route('sales.calendar') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-calendar"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('sales.orders') ? 'active' : '' }}">
                <a href="{{ route('sales.orders') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-file"></i>
                  <div data-i18n="Orders">Orders</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('sales.progress') ? 'active' : '' }}">
                <a href="{{ route('sales.progress') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                  <div data-i18n="Fullfilment">Fullfilment</div>
                </a>
              </li>
            @endif
            @if (in_array(auth()->user()->role, ['artist', 'head-artist']))
              <li class="menu-item {{ request()->routeIs('artist.orders') ? 'active' : '' }}">
                <a href="{{ route('artist.orders') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-file"></i>
                  <div data-i18n="Job Orders">Job Orders</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('artist.calendar') ? 'active' : '' }}">
                <a href="{{ route('artist.calendar') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-calendar"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('artist.fulfillment.index') ? 'active' : '' }}">
                <a href="{{ route('artist.fulfillment.index') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-check"></i>
                  <div data-i18n="Fulfillment">Fulfillment</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('artist.progress') ? 'active' : '' }}">
                <a href="{{ route('artist.progress') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                  <div data-i18n="Progress">Order Progress</div>
                </a>
              </li>
            @endif
            @if (in_array(auth()->user()->role, ['data-entry']))
              <li class="menu-item {{ request()->routeIs('data-entry.orders') ? 'active' : '' }}">
                <a href="{{ route('data-entry.orders') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-file"></i>
                  <div data-i18n="Job Orders">Job Orders</div>
                </a>
              </li>
            @endif
            @if (auth()->user()->role === 'admin')
            <li class="menu-item {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
                <a href="{{ route('admin.orders') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-file"></i>
                    <div data-i18n="Job Order">Job Order</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.manageuser') ? 'active' : '' }}">
                <a href="{{ route('admin.manageuser') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-group"></i>
                    <div data-i18n="Manage User">Manage User</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.costing-data') ? 'active' : '' }}">
                <a href="{{ route('admin.costing-data') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-data"></i>
                    <div data-i18n="Material Data">Material Data</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.calendar') ? 'active' : '' }}">
                <a href="{{ route('admin.calendar') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-calendar"></i>
                    <div data-i18n="Calendar">Calendar</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <a href="{{ route('admin.reports') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                    <div data-i18n="Reports">Reports</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.fulfillment') ? 'active' : '' }}">
                <a href="{{ route('admin.fulfillment') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-package"></i>
                    <div data-i18n="Fulfillment">Fulfillment</div>
                </a>
            </li>
        @endif
            @if (in_array(auth()->user()->role, ['operations-printing']))
              <li class="menu-item {{ request()->routeIs('printing.progress') ? 'active' : '' }}">
                <a href="{{ route('printing.progress') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                  <div data-i18n="Fullfilment">Fullfilment</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('printing.history') ? 'active' : '' }}">
                <a href="{{ route('printing.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-history"></i>
                  <div data-i18n="Order History">Order History</div>
                </a>
              </li>
            @endif
            @if (in_array(auth()->user()->role, ['operations-furnishing']))
              <li class="menu-item {{ request()->routeIs('furnishing.progress') ? 'active' : '' }}">
                <a href="{{ route('furnishing.progress') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                  <div data-i18n="Fullfilment">Fullfilment</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('furnishing.history') ? 'active' : '' }}">
                <a href="{{ route('furnishing.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-history"></i>
                  <div data-i18n="Order History">Order History</div>
                </a>
              </li>
            @endif
            @if (in_array(auth()->user()->role, ['operations-delivery-installation']))
              <li class="menu-item {{ request()->routeIs('installation.calendar') ? 'active' : '' }}">
                <a href="{{ route('installation.calendar') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-calendar"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('installation.job-order') ? 'active' : '' }}">
                <a href="{{ route('installation.job-order') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-file"></i>
                  <div data-i18n="Job Order">Job Order</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('installation.history') ? 'active' : '' }}">
                <a href="{{ route('installation.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-history"></i>
                  <div data-i18n="Order History">Order History</div>
                </a>
              </li>
            @endif
            @if (in_array(auth()->user()->role, ['operations-dispatch-control']))
              <li class="menu-item {{ request()->routeIs('dispatchcontrol.job-order') ? 'active' : '' }}">
                <a href="{{ route('dispatchcontrol.job-order') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-file"></i>
                  <div data-i18n="Job Order">Job Order</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('dispatchcontrol.history') ? 'active' : '' }}">
                <a href="{{ route('dispatchcontrol.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-history"></i>
                  <div data-i18n="Order History">Order History</div>
                </a>
              </li>
            @endif
            @if (auth()->user()->role === 'boss')
                <li class="menu-item {{ request()->routeIs('boss.orders') ? 'active' : '' }}">
                  <a href="{{ route('boss.orders') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-file"></i>
                    <div data-i18n="Job Orders">Job Orders</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('boss.leads') ? 'active' : '' }}">
                  <a href="{{ route('boss.leads') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-user"></i>
                    <div data-i18n="Leads">Leads</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('boss.reports') ? 'active' : '' }}">
                  <a href="{{ route('boss.reports') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                    <div data-i18n="Reports">Reports</div>
                  </a>
                </li>

                <li class="menu-item {{ request()->routeIs('boss.calendar') ? 'active' : '' }}">
                    <a href="{{ route('boss.calendar') }}" class="menu-link">
                        <i class="menu-icon icon-base bx bx-calendar"></i>
                        <div data-i18n="Calendar">Calendar</div>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('boss.fulfillment') ? 'active' : '' }}">
                    <a href="{{ route('boss.fulfillment') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-package"></i>
                    <div data-i18n="Fulfillment">Fulfillment</div>
                  </a>
                 </li>

                 <li class="menu-item {{ request()->routeIs('boss.manageuser') ? 'active' : '' }}">
                <a href="{{ route('boss.manageuser') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-group"></i>
                  <div data-i18n="Manage User">Manage User</div>
                 </a>
                 </li>
                 <li class="menu-item {{ request()->routeIs('boss.datamanagement') ? 'active' : '' }}">
                <a href="{{ route('boss.datamanagement') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-data"></i>
                  <div data-i18n="Data Management">Data Management</div>
                </a>
              </li>

            @endif
          @endif
        </ul>
        </aside>

    <div class="menu-mobile-toggler d-xl-none rounded-1">
      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
        <i class="bx bx-menu icon-base"></i>
        <i class="bx bx-chevron-right icon-base"></i>
      </a>
    </div>
    <!-- / Menu -->

    <!-- Layout container -->
    <div class="layout-page">
      <!-- Navbar -->
      @include('layouts.sections.navbar.navigation')
      <!-- Content wrapper -->
      <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
          @yield('content')
        </div>
        <!-- / Content -->

        <!-- Footer -->
        <!-- <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div
                  class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                  <div class="mb-2 mb-md-0">
                    ©
                    <script>
                      document.write(new Date().getFullYear());
                    </script>
                    , made with ❤️ by
                    <a href="https://themeselection.com" target="_blank" class="footer-link">ThemeSelection</a>
                  </div>
                  <div class="d-none d-lg-inline-block">
                    <a href="https://themeselection.com/license/" class="footer-link me-4" target="_blank">License</a>
                    <a href="https://themeselection.com/" target="_blank" class="footer-link me-4">More Themes</a>

                    <a
                      href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/documentation/"
                      target="_blank"
                      class="footer-link me-4"
                      >Documentation</a
                    >

                    <a
                      href="https://themeselection.com/support/"
                      target="_blank"
                      class="footer-link d-none d-sm-inline-block"
                      >Support</a
                    >
                  </div>
                </div>
              </div>
            </footer> -->
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
      </div>
      <!-- Content wrapper -->
    </div>
    <!-- / Layout page -->
  </div>

  <!-- Overlay -->
  <div class="layout-overlay layout-menu-toggle"></div>

  <!-- Drag Target Area To SlideIn Menu On Small Screens -->
  <div class="drag-target"></div>
</div>
@endsection
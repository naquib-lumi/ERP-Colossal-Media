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
          <span class="logo-svg text-primary">
            <svg width="150" height="40" viewBox="0 0 254 75" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <rect width="254" height="75" fill="url(#pattern0_131_98)" />
              <defs>
                <pattern id="pattern0_131_98" patternContentUnits="objectBoundingBox" width="1" height="1">
                  <use xlink:href="#image0_131_98" transform="matrix(0.0085 0 0 0.0288 -0.0008 0)" />
                </pattern>
                <image id="image0_131_98" width="117" height="35" preserveAspectRatio="xMidYMid slice" xlink:href="data:image/png;base64,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" />
            </svg>
          </span>

          <h4 class="logo-text">CX</h4>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
          <i class="icon-base bx bx-chevron-left"></i>
        </a>
      </div>

      <style>
        /* Hide SVG by default, show text */
        .logo-svg {
          display: none;
        }

        .logo-text {
          display: inline;
        }

        html.layout-menu-hover .logo-svg {
          display: inline;
        }

        html.layout-menu-expanded .logo-svg {
          display: inline;
        }

        html.layout-menu-expanded .logo-text {
          display: none;
        }

        html.layout-menu-hover .logo-text {
          display: none;
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
              <li class="menu-item {{ request()->routeIs('artist.profile.show') ? 'active' : '' }}">
                <a href="{{ route('artist.profile.show') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="Profile">Profile</div>
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
            <li class="menu-item {{ request()->routeIs('admin.manageuser') ? 'active' : '' }}">
                <a href="{{ route('admin.manageuser') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-group"></i>
                    <div data-i18n="Manage User">Manage User</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
                <a href="{{ route('admin.orders') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-file"></i>
                    <div data-i18n="Orders">Orders</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.coasing-data') ? 'active' : '' }}">
                <a href="{{ route('admin.coasing-data') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-data"></i>
                    <div data-i18n="Coasing Data">Coasing Data</div>
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
            <li class="menu-item {{ request()->routeIs('admin.data-key-in') ? 'active' : '' }}">
                <a href="{{ route('admin.data-key-in') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-key"></i>
                    <div data-i18n="Data Key In">Data Key In</div>
                </a>
            </li>
        @endif
            @if (in_array(auth()->user()->role, ['operations-printing']))
              <li class="menu-item {{ request()->routeIs('printing.history') ? 'active' : '' }}">
                <a href="{{ route('printing.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-history"></i>
                  <div data-i18n="Order History">Order History</div>
                </a>
              </li>
              <!-- <li class="menu-item {{ request()->routeIs('printing.history') ? 'active' : '' }}">
                <a href="{{ route('printing.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="User">User</div>
                </a>
              </li> -->
              <li class="menu-item {{ request()->routeIs('printing.profile') ? 'active' : '' }}">
                <a href="{{ route('printing.profile') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="Profile">Profile</div>
                </a>
              </li>
            @endif
            @if (in_array(auth()->user()->role, ['operations-furnishing']))
              <li class="menu-item {{ request()->routeIs('furnishing.history') ? 'active' : '' }}">
                <a href="{{ route('furnishing.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-history"></i>
                  <div data-i18n="Order History">Order History</div>
                </a>
              </li>
              <!-- <li class="menu-item {{ request()->routeIs('furnishing.history') ? 'active' : '' }}">
                <a href="{{ route('furnishing.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="User">User</div>
                </a>
              </li> -->
              <li class="menu-item {{ request()->routeIs('furnishing.profile') ? 'active' : '' }}">
                <a href="{{ route('furnishing.profile') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="Profile">Profile</div>
                </a>
              </li>
            @endif
            @if (in_array(auth()->user()->role, ['operations-delivery-installation']))
              <li class="menu-item {{ request()->routeIs('installation.history') ? 'active' : '' }}">
                <a href="{{ route('installation.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-history"></i>
                  <div data-i18n="Order History">Order History</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('installation.calendar') ? 'active' : '' }}">
                <a href="{{ route('installation.calendar') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-calendar"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li>
                            <li class="menu-item {{ request()->routeIs('installation.profile') ? 'active' : '' }}">
              <a href="{{ route('installation.profile') }}" class="menu-link">
                <i class="menu-icon icon-base bx bx-user"></i>
                <div data-i18n="Profile">Profile</div>
              </a>
            </li>
              <!-- <li class="menu-item {{ request()->routeIs('installation.history') ? 'active' : '' }}">
                <a href="{{ route('installation.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li> -->
              <!-- <li class="menu-item {{ request()->routeIs('installation.history') ? 'active' : '' }}">
                <a href="{{ route('installation.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="User">User</div>
                </a>
              </li> -->
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
              <li class="menu-item {{ request()->routeIs('dispatchcontrol.user') ? 'active' : '' }}">
                <a href="{{ route('dispatchcontrol.user') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="Profile">Profile</div>
                </a>
              </li>
              <!-- <li class="menu-item {{ request()->routeIs('installation.history') ? 'active' : '' }}">
                <a href="{{ route('installation.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li> -->
              <!-- <li class="menu-item {{ request()->routeIs('installation.history') ? 'active' : '' }}">
                <a href="{{ route('installation.history') }}" class="menu-link">
                  <i class="menu-icon icon-base bx bx-user"></i>
                  <div data-i18n="User">User</div>
                </a>
              </li> -->
            @endif
            @if (auth()->user()->role === 'boss')
                <li class="menu-item {{ request()->routeIs('boss.reports') ? 'active' : '' }}">
                  <a href="{{ route('boss.reports') }}" class="menu-link">
                    <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                    <div data-i18n="Reports">Reports</div>
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
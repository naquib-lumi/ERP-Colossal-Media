<nav
  class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
  id="layout-navbar"
>
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="icon-base bx bx-menu icon-md"></i>
    </a>
  </div>
  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <!-- <div class="navbar-nav align-items-center">
      <div class="nav-item navbar-search-wrapper mb-0">
        <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
          <i class="ti ti-search ti-md me-2 ti-sm"></i>
          <span class="d-none d-md-inline-block text-muted">Search (Ctrl+K)</span>
        </a>
      </div>
    </div> -->
    <ul class="navbar-nav flex-row align-items-center ms-auto">
      <!-- <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
          <i class="ti ti-language ti-md"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-end">
          <a class="dropdown-item" href="javascript:void(0);">
            <span class="align-middle">English</span>
          </a>
          <a class="dropdown-item" href="javascript:void(0);">
            <span class="align-middle">French</span>
          </a>
        </div>
      </li>
      <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
          <i class="ti ti-md ti-sun"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-styles">
          <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
            <span class="align-middle"><i class="ti ti-sun ti-md me-2"></i>Light</span>
          </a>
          <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
            <span class="align-middle"><i class="ti ti-moon ti-md me-2"></i>Dark</span>
          </a>
          <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
            <span class="align-middle"><i class="ti ti-device-desktop-analytics ti-md me-2"></i>System</span>
          </a>
        </div>
      </li> -->
      @if (auth()->check())
        <li class="nav-item dropdown-c navbar-dropdown dropdown me-2 me-xl-0">
          <a
            class="nav-link dropdown-toggle hide-arrow"
            href="javascript:void(0);"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
          >
                 <i class="icon-base bx bx-grid-alt icon-md"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end py-0">
            <div class="dropdown-menu-header border-bottom">
              <div class="dropdown-header d-flex align-items-center py-3">
                <h5 class="text-body text-nowrap mb-0">Shortcuts</h5>
                <a
                          href="javascript:void(0)"
                          class="dropdown-shortcuts-add py-2"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          title="Add shortcuts"
                          ><i class="icon-base bx bx-plus-circle text-heading"></i
                        ></a>
              </div>
            </div>
            <div class="dropdown-shortcuts-list scrollable-container">
              @if (auth()->user()->role === 'Salesperson')
                <a class="dropdown-shortcuts-item" href="{{ route('sales.leads') }}">
                  <i class="ti ti-users ti-sm"></i> Leads
                </a>
                <a class="dropdown-shortcuts-item" href="{{ route('sales.calendar') }}">
                  <i class="ti ti-calendar ti-sm"></i> Calendar
                </a>
              @elseif (auth()->user()->role === 'Artist')
                <a class="dropdown-shortcuts-item" href="{{ route('job.orders') }}">
                  <i class="ti ti-paint ti-sm"></i> Job Orders
                </a>
              @elseif (auth()->user()->role === 'Admin')
                <a class="dropdown-shortcuts-item" href="{{ route('admin.reports') }}">
                  <i class="ti ti-report ti-sm"></i> Reports
                </a>
              @elseif (in_array(auth()->user()->role, ['Printing', 'Installation', 'Delivery', 'Furnishing']))
                <a class="dropdown-shortcuts-item" href="{{ route('operations.tasks') }}">
                  <i class="ti ti-tools ti-sm"></i> Tasks
                </a>
              @elseif (auth()->user()->role === 'Boss')
                <a class="dropdown-shortcuts-item" href="{{ route('boss.reports') }}">
                  <i class="ti ti-report ti-sm"></i> Reports
                </a>
              @endif
            </div>
          </div>
        </li>
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
          <a
            class="nav-link dropdown-toggle hide-arrow"
            href="javascript:void(0);"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
          >
              <span class="position-relative">
                      <i class="icon-base bx bx-bell icon-md"></i>
                      <span class="badge rounded-pill bg-danger badge-dot badge-notifications border"></span>
                    </span>
          </a>
          <div class="dropdown-menu dropdown-menu-end py-0">
            <div class="dropdown-menu-header border-bottom">
              <div class="dropdown-header d-flex align-items-center py-3">
                <h5 class="text-body text-nowrap mb-2">Notifications</h5>
              </div>
            </div>
            <div class="dropdown-notifications-list scrollable-container">
              <ul class="list-group">
                @if (auth()->user()->role === 'Salesperson')
                  <li class="list-group-item">New Lead Assigned - 1h ago</li>
                @elseif (auth()->user()->role === 'Artist')
                  <li class="list-group-item">New Job Order Received - 2h ago</li>
                @elseif (auth()->user()->role === 'Admin')
                  <li class="list-group-item">System Report Generated - 3h ago</li>
                @elseif (in_array(auth()->user()->role, ['Printing', 'Installation', 'Delivery', 'Furnishing']))
                  <li class="list-group-item">New Task Assigned - 4h ago</li>
                @elseif (auth()->user()->role === 'Boss')
                  <li class="list-group-item">Waste Report Updated - 5h ago</li>
                @endif
              </ul>
            </div>
            <div class="dropdown-menu-footer border-top p-3">
              <a href="javascript:void(0);" class="btn btn-primary d-grid">View all notifications</a>
            </div>
          </div>
        </li>
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <div class="avatar avatar-online">
              <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="h-auto rounded-circle" />
            </div>
          </a>
          <div class="dropdown-menu dropdown-menu-end">
            <div class="dropdown-header">
              <div class="avatar-wrapper">
                <div class="avatar avatar-md">
                  <img src="{{ asset('assets/img/avatars/1.png') }}" alt="Avatar" class="rounded-circle" />
                </div>
                <div class="ms-2">
                  <h6 class="mb-0">{{ auth()->user()->name ?? 'John Doe' }}</h6>
                  <small class="text-muted">{{ auth()->user()->role ?? 'Admin' }}</small>
                </div>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
              <i class="ti ti-user me-2 ti-sm"></i>
              <span class="align-middle">My Profile</span>
            </a>
            @if (auth()->user()->role === 'Admin')
              <a class="dropdown-item" href="{{ route('admin.settings') }}">
                <i class="ti ti-settings me-2 ti-sm"></i>
                <span class="align-middle">Settings</span>
              </a>
            @endif
            <div class="dropdown-divider"></div>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                <i class="ti ti-logout me-2 ti-sm"></i>
                <span class="align-middle">Log Out</span>
              </a>
            </form>
          </div>
        </li>
      @endif
    </ul>
  </div>
  <div class="navbar-search-wrapper search-input-wrapper d-none">
    <input
      type="text"
      class="form-control search-input container-xxl border-0"
      placeholder="Search..."
      aria-label="Search..."
    />
    <i class="ti ti-x ti-md search-toggler cursor-pointer"></i>
  </div>
</nav>
<nav
  class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
  id="layout-navbar"
>
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="ti ti-menu-2 ti-sm"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <!-- Search -->
    <div class="navbar-nav align-items-center">
      <div class="nav-item navbar-search-wrapper mb-0">
        <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
          <i class="ti ti-search ti-md me-2 ti-sm"></i>
          <span class="d-none d-md-inline-block text-muted">Search (Ctrl+K)</span>
        </a>
      </div>
    </div>
    <!-- /Search -->

    <ul class="navbar-nav flex-row align-items-center ms-auto">
  <!-- Notifications (All roles) -->
  <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
    <a
      class="nav-link dropdown-toggle hide-arrow"
      href="javascript:void(0);"
      data-bs-toggle="dropdown"
      data-bs-auto-close="outside"
      aria-expanded="false"
    >
      <i class="ti ti-bell ti-md"></i>
      <span class="badge bg-danger rounded-pill badge-notifications">8</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end py-0">
      <div class="dropdown-menu-header border-bottom">
        <div class="dropdown-header d-flex align-items-center py-3">
          <h5 class="text-body text-nowrap mb-2">Notification</h5>
        </div>
      </div>
      <div class="dropdown-notifications-list scrollable-container">
        <ul class="list-group">
          @if (Auth::user()->role === 'Salesperson')
          <li class="list-group-item">New Lead Assigned</li>
          @elseif (Auth::user()->role === 'Artist')
          <li class="list-group-item">New Job Order Received</li>
          @elseif (Auth::user()->role === 'Admin')
          <li class="list-group-item">System Report Generated</li>
          @endif
        </ul>
      </div>
    </div>
  </li>

  <!-- User -->
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
            <h6 class="mb-0">{{ Auth::user()->name ?? 'John Doe' }}</h6>
            <small class="text-muted">{{ Auth::user()->role ?? 'Admin' }}</small>
          </div>
        </div>
      </div>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="{{ route('profile.edit') }}">
        <i class="ti ti-user me-2 ti-sm"></i>
        <span class="align-middle">My Profile</span>
      </a>
      @if (Auth::user()->role === 'Admin')
      <a class="dropdown-item" href="{{ route('admin.settings') }}">
        <i class="ti ti-settings me-2 ti-sm"></i>
        <span class="align-middle">System Settings</span>
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
</ul>
  </div>

  <!-- Search Small Screens -->
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
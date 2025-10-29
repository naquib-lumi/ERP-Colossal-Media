
<!-- Common Master Blade Updated -->
<!DOCTYPE html>
<html lang="en"
    class="     "
    dir="ltr" data-skin="default"
    data-assets-path="https://dev.colossal360.com.my/assets/" data-base-url="https://dev.colossal360.com.my" data-framework="laravel"
    data-template="vertical-menu-template" data-bs-theme="light"
    >


<link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/css/core.css" />
<link rel="stylesheet" href="https://dev.colossal360.com.my/assets/css/demo.css" />
<!-- Vendors CSS -->

<link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/libs/@form-validation/form-validation.css" />

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title> |
        Colossal ERP</title>
    <meta name="description"
        content="" />
    <meta name="keywords"
        content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://dev.colossal360.com.my/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/libs/pickr/pickr-themes.css" />


    <!-- Vendors CSS -->
    <link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/libs/flatpickr/flatpickr.css" />
    <link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/libs/quill/editor.css" />
    <link rel="stylesheet" href="https://dev.colossal360.com.my/assets/vendor/libs/@form-validation/form-validation.css" />

    <!-- Page CSS (conditional) -->
    
    
        <!-- Helpers -->
    <script src="https://dev.colossal360.com.my/assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <!-- <script src="https://dev.colossal360.com.my/assets/vendor/js/template-customizer.js"></script> UNCOMMENT-CUSTOMIZER-->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="https://dev.colossal360.com.my/assets/js/config.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/jquery/jquery.js"></script>


    <script>
        $(document).ready(function() {
            // Mark as read when clicked
            $('.dropdown-notifications-read').on('click', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');
                let notificationItem = $(this).closest('.dropdown-notifications-item');
                let notificationId = notificationItem.data('id');

                if (!notificationId) {
                    console.error('Notification ID is undefined');
                    alert('Error: Notification ID is missing.');
                    return;
                }

                let markAsReadUrl = 'https://dev.colossal360.com.my/notifications/PLACEHOLDER/read'
                    .replace('PLACEHOLDER', notificationId);

                $.ajax({
                    url: markAsReadUrl,
                    type: 'POST',
                    data: {
                        _token: 'YQRkRC0BaX4pShivAxA7bOu6OcI8omFwEjpB4qbU'
                    },
                    success: function() {
                        notificationItem.addClass('marked-as-read');
                        let count = parseInt($('.badge-notifications').text() || 0);
                        if (count > 0) {
                            $('.badge-notifications').text(count - 1);
                            if (count - 1 === 0) $('.badge-notifications').remove();
                        }
                        window.location.href = url;
                    },
                    error: function(xhr) {
                        console.error('Error marking notification as read: ', xhr.responseText);
                        alert('Error marking notification as read: ' + xhr.responseText);
                    }
                });
            });

            // Mark all as read
            $('.dropdown-notifications-all').on('click', function() {
                $.ajax({
                    url: 'https://dev.colossal360.com.my/notifications/read-all',
                    type: 'POST',
                    data: {
                        _token: 'YQRkRC0BaX4pShivAxA7bOu6OcI8omFwEjpB4qbU'
                    },
                    success: function() {
                        $('.dropdown-notifications-item').addClass('marked-as-read');
                        $('.badge-notifications').remove();
                    },
                    error: function(xhr) {
                        console.error('Error marking all notifications as read: ', xhr
                            .responseText);
                        alert('Error marking all notifications as read: ' + xhr.responseText);
                    }
                });
            });

            // Archive notification
            $('.dropdown-notifications-archive').on('click', function(e) {
                e.preventDefault();
                let notificationItem = $(this).closest('.dropdown-notifications-item');
                let notificationId = notificationItem.data('id');

                if (!notificationId) {
                    console.error('Notification ID is undefined');
                    alert('Error: Notification ID is missing.');
                    return;
                }

                let archiveUrl = 'https://dev.colossal360.com.my/notifications/PLACEHOLDER/archive'.replace(
                    'PLACEHOLDER', notificationId);

                $.ajax({
                    url: archiveUrl,
                    type: 'POST',
                    data: {
                        _token: 'YQRkRC0BaX4pShivAxA7bOu6OcI8omFwEjpB4qbU'
                    },
                    success: function() {
                        notificationItem.remove();
                        let count = parseInt($('.badge-notifications').text() || 0);
                        if (count > 0) {
                            $('.badge-notifications').text(count - 1);
                            if (count - 1 === 0) $('.badge-notifications').remove();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error archiving notification: ', xhr.responseText);
                        alert('Error archiving notification: ' + xhr.responseText);
                    }
                });
            });

            // Real-time notification count update
            setInterval(function() {
                $.ajax({
                    url: 'https://dev.colossal360.com.my/notifications/count',
                    success: function(count) {
                        if (count > 0) {
                            $('.badge-notifications').text(count).show();
                            $('.bx-bell').addClass('animate_animated animate_tada');
                            setTimeout(() => $('.bx-bell').removeClass(
                                'animate_animated animate_tada'), 1000);
                        } else {
                            $('.badge-notifications').remove();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching notification count: ', xhr.responseText);
                    }
                });
            }, 60000); // Check every minute
        });
    </script>
</head>






</head>

<body>

    
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">
    <!-- Menu -->
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" lang="en">
      <div class="app-brand demo">
        <a href="https://dev.colossal360.com.my/dashboard" class="app-brand-link">
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
                        <li class="menu-item ">
          <a href="https://dev.colossal360.com.my/dashboard" class="menu-link">
            <i class="menu-icon icon-base bx bx-home-smile"></i>
                <div data-i18n="Dashboard">Dashboard</div>
              </a>
            </li>
                                                                                         <li class="menu-item ">
                <a href="https://dev.colossal360.com.my/admin/manageuser" class="menu-link">
                    <i class="menu-icon icon-base bx bx-group"></i>
                    <div data-i18n="Manage User">Manage User</div>
                </a>
            </li>
            <li class="menu-item ">
                <a href="https://dev.colossal360.com.my/admin/orders" class="menu-link">
                    <i class="menu-icon icon-base bx bx-file"></i>
                    <div data-i18n="Orders">Orders</div>
                </a>
            </li>
            <li class="menu-item ">
                <a href="https://dev.colossal360.com.my/admin/coasing-data" class="menu-link">
                    <i class="menu-icon icon-base bx bx-data"></i>
                    <div data-i18n="Coasing Data">Coasing Data</div>
                </a>
            </li>
            <li class="menu-item ">
                <a href="https://dev.colossal360.com.my/admin/calendar" class="menu-link">
                    <i class="menu-icon icon-base bx bx-calendar"></i>
                    <div data-i18n="Calendar">Calendar</div>
                </a>
            </li>
            <li class="menu-item ">
                <a href="https://dev.colossal360.com.my/admin/reports" class="menu-link">
                    <i class="menu-icon icon-base bx bx-bar-chart-alt"></i>
                    <div data-i18n="Reports">Reports</div>
                </a>
            </li>
            <li class="menu-item ">
                <a href="https://dev.colossal360.com.my/admin/fulfillment" class="menu-link">
                    <i class="menu-icon icon-base bx bx-package"></i>
                    <div data-i18n="Fulfillment">Fulfillment</div>
                </a>
            </li>
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
      <nav style="z-index: 10;"
            class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
            id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="icon-base bx bx-menu icon-md"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
              <!-- Search -->
              <!-- <div class="navbar-nav align-items-center">
                <div class="nav-item navbar-search-wrapper mb-0">
                  <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
                    <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
                  </a>
                </div>
              </div> -->

              <!-- /Search -->

              <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                <!-- Language -->
                <!-- <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="icon-base bx bx-globe icon-md"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-language="en" data-text-direction="ltr">
                        <span>English</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-language="fr" data-text-direction="ltr">
                        <span>French</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-language="ar" data-text-direction="rtl">
                        <span>Arabic</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-language="de" data-text-direction="ltr">
                        <span>German</span>
                      </a>
                    </li>
                  </ul>
                </li> -->
                <!--/ Language -->

                <!-- Style Switcher -->
                <!-- <li class="nav-item dropdown me-2 me-xl-0">
                  <a
                    class="nav-link dropdown-toggle hide-arrow"
                    id="nav-theme"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <i class="icon-base bx bx-sun icon-md theme-icon-active"></i>
                    <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item align-items-center active"
                        data-bs-theme-value="light"
                        aria-pressed="false">
                        <span><i class="icon-base bx bx-sun icon-md me-3" data-icon="sun"></i>Light</span>
                      </button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item align-items-center"
                        data-bs-theme-value="dark"
                        aria-pressed="true">
                        <span><i class="icon-base bx bx-moon icon-md me-3" data-icon="moon"></i>Dark</span>
                      </button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item align-items-center"
                        data-bs-theme-value="system"
                        aria-pressed="false">
                        <span><i class="icon-base bx bx-desktop icon-md me-3" data-icon="desktop"></i>System</span>
                      </button>
                    </li>
                  </ul>
                </li> -->
                <!-- / Style Switcher-->

                <!-- Quick links  -->
                <!-- <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-2 me-xl-0">
                  <a
                    class="nav-link dropdown-toggle hide-arrow"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false">
                    <i class="icon-base bx bx-grid-alt icon-md"></i>
                  </a>
                  <div class="dropdown-menu dropdown-menu-end p-0">
                    <div class="dropdown-menu-header border-bottom">
                      <div class="dropdown-header d-flex align-items-center py-3">
                        <h6 class="mb-0 me-auto">Shortcuts</h6>
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
                      <div class="row row-bordered overflow-visible g-0">
                        <div class="dropdown-shortcuts-item col">
                          <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                            <i class="icon-base bx bx-calendar icon-26px text-heading"></i>
                          </span>
                          <a href="app-calendar.html" class="stretched-link">Calendar</a>
                          <small>Appointments</small>
                        </div>
                        <div class="dropdown-shortcuts-item col">
                          <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                            <i class="icon-base bx bx-food-menu icon-26px text-heading"></i>
                          </span>
                          <a href="app-invoice-list.html" class="stretched-link">Invoice App</a>
                          <small>Manage Accounts</small>
                        </div>
                      </div>
                      <div class="row row-bordered overflow-visible g-0">
                        <div class="dropdown-shortcuts-item col">
                          <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                            <i class="icon-base bx bx-user icon-26px text-heading"></i>
                          </span>
                          <a href="app-user-list.html" class="stretched-link">User App</a>
                          <small>Manage Users</small>
                        </div>
                        <div class="dropdown-shortcuts-item col">
                          <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                            <i class="icon-base bx bx-check-shield icon-26px text-heading"></i>
                          </span>
                          <a href="app-access-roles.html" class="stretched-link">Role Management</a>
                          <small>Permission</small>
                        </div>
                      </div>
                      <div class="row row-bordered overflow-visible g-0">
                        <div class="dropdown-shortcuts-item col">
                          <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                            <i class="icon-base bx bx-pie-chart-alt-2 icon-26px text-heading"></i>
                          </span>
                          <a href="index.html" class="stretched-link">Dashboard</a>
                          <small>User Dashboard</small>
                        </div>
                        <div class="dropdown-shortcuts-item col">
                          <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                            <i class="icon-base bx bx-cog icon-26px text-heading"></i>
                          </span>
                          <a href="pages-account-settings-account.html" class="stretched-link">Setting</a>
                          <small>Account Settings</small>
                        </div>
                      </div>
                      <div class="row row-bordered overflow-visible g-0">
                        <div class="dropdown-shortcuts-item col">
                          <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                            <i class="icon-base bx bx-help-circle icon-26px text-heading"></i>
                          </span>
                          <a href="pages-faq.html" class="stretched-link">FAQs</a>
                          <small>FAQs & Articles</small>
                        </div>
                        <div class="dropdown-shortcuts-item col">
                          <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                            <i class="icon-base bx bx-window-open icon-26px text-heading"></i>
                          </span>
                          <a href="modal-examples.html" class="stretched-link">Modals</a>
                          <small>Useful Popups</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </li> -->
                <!-- Quick links -->

                <!-- Notification -->
<li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
    <span class="position-relative">
      <i class="icon-base bx bx-bell icon-md"></i>
                    <span class="badge rounded-pill bg-danger badge-dot badge-notifications border">54</span>
          </span>
  </a>

  <ul class="dropdown-menu dropdown-menu-end p-0">
    <li class="dropdown-menu-header border-bottom">
      <div class="dropdown-header d-flex align-items-center py-3">
        <h6 class="mb-0 me-auto">Notifications</h6>
        <div class="d-flex align-items-center h6 mb-0">
                      <span class="badge bg-label-primary me-2">54 New</span>
                    <a href="javascript:void(0)" class="dropdown-notifications-all p-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read">
            <i class="icon-base bx bx-envelope-open text-heading"></i>
          </a>
        </div>
      </div>
    </li>

    <li class="dropdown-notifications-list scrollable-container">
      <ul class="list-group list-group-flush">
                          <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="1fc5fa73-c99f-40b3-9fa6-0ddd65acb855">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('1fc5fa73-c99f-40b3-9fa6-0ddd65acb855', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Delivery &amp; Installation completed for Product product abc by Delivery Installation A (operations delivery installation), product completed.</h6>
                  <small class="text-body-secondary">
                    2025-10-28 12:50
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('1fc5fa73-c99f-40b3-9fa6-0ddd65acb855', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('1fc5fa73-c99f-40b3-9fa6-0ddd65acb855', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="8d346ab7-05d8-4e9b-804f-2a2328468a06">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('8d346ab7-05d8-4e9b-804f-2a2328468a06', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Updates on Product product abc by Delivery Installation A (operations delivery installation) (remarks added: 1).</h6>
                  <small class="text-body-secondary">
                    2025-10-28 12:49
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('8d346ab7-05d8-4e9b-804f-2a2328468a06', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('8d346ab7-05d8-4e9b-804f-2a2328468a06', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="d8b4f6ad-9b12-456d-93ac-b68cec23e235">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('d8b4f6ad-9b12-456d-93ac-b68cec23e235', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Product product abc has been accepted by Delivery Installation A (operations delivery installation).</h6>
                  <small class="text-body-secondary">
                    2025-10-28 12:48
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('d8b4f6ad-9b12-456d-93ac-b68cec23e235', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('d8b4f6ad-9b12-456d-93ac-b68cec23e235', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="139bd0a6-b4b4-45b5-a75b-777e53442764">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('139bd0a6-b4b4-45b5-a75b-777e53442764', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Furnishing completed for Product product abc by Furnishing A (operations furnishing). Next stage: installation.</h6>
                  <small class="text-body-secondary">
                    2025-10-28 12:46
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('139bd0a6-b4b4-45b5-a75b-777e53442764', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('139bd0a6-b4b4-45b5-a75b-777e53442764', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="5e0191e7-ad0c-4180-aa02-6e10b7460a06">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('5e0191e7-ad0c-4180-aa02-6e10b7460a06', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Updates on Product product abc by Furnishing A (operations furnishing) (remarks added: 1).</h6>
                  <small class="text-body-secondary">
                    2025-10-28 12:46
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('5e0191e7-ad0c-4180-aa02-6e10b7460a06', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('5e0191e7-ad0c-4180-aa02-6e10b7460a06', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="c492b2b7-0821-4f17-83f2-5b78663e3ea2">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('c492b2b7-0821-4f17-83f2-5b78663e3ea2', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Updates on Product product abc by Furnishing A (operations furnishing) (remarks added: 1).</h6>
                  <small class="text-body-secondary">
                    2025-10-28 12:39
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('c492b2b7-0821-4f17-83f2-5b78663e3ea2', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('c492b2b7-0821-4f17-83f2-5b78663e3ea2', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="bed6e829-26dd-44d1-802d-da91ff5f9ad3">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('bed6e829-26dd-44d1-802d-da91ff5f9ad3', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Updates on Product product abc by Furnishing A (operations furnishing) (cutter set: 1, remarks added: 1).</h6>
                  <small class="text-body-secondary">
                    2025-10-28 12:38
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('bed6e829-26dd-44d1-802d-da91ff5f9ad3', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('bed6e829-26dd-44d1-802d-da91ff5f9ad3', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="76f64324-9e1c-452f-a935-e2ad66c803fc">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('76f64324-9e1c-452f-a935-e2ad66c803fc', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Product product abc has been accepted by Furnishing A (operations furnishing).</h6>
                  <small class="text-body-secondary">
                    2025-10-28 12:21
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('76f64324-9e1c-452f-a935-e2ad66c803fc', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('76f64324-9e1c-452f-a935-e2ad66c803fc', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="9becbdcc-7457-487e-88fc-5180b807cf1c">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('9becbdcc-7457-487e-88fc-5180b807cf1c', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">Order ##ORD-2025-0056 has been **submitted** by Head Artist 1 (head artist). 1 Product(s). Deadline: 2025-11-05. Initial task(s): furnishing.</h6>
                  <small class="text-body-secondary">
                    2025-10-28 11:54
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('9becbdcc-7457-487e-88fc-5180b807cf1c', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('9becbdcc-7457-487e-88fc-5180b807cf1c', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item" data-id="5b69867a-239e-4e71-a9e5-cd0322fbe459">
            <a href="javascript:void(0)" class="d-flex w-100 text-decoration-none text-body" onclick="markAsReadAndGo('5b69867a-239e-4e71-a9e5-cd0322fbe459', 'https://dev.colossal360.com.my/admin/orders/56')">
              <div class="d-flex w-100">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="icon-base bx bx-bell"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <h6 class="small mb-0">New order #ORD-2025-0056 created by Head Artist 1 (head artist). 1 Product(s) added. Deadline: 2025-11-05.</h6>
                  <small class="text-body-secondary">
                    2025-10-28 11:53
                  </small>
                </div>

                <div class="flex-shrink-0 dropdown-notifications-actions">
                  <a href="javascript:void(0)" class="dropdown-notifications-read"
                    onclick="markAsReadKeep('5b69867a-239e-4e71-a9e5-cd0322fbe459', this)">
                    <span class="badge badge-dot bg-success"></span>
                  </a>
                  <a href="javascript:void(0)" class="dropdown-notifications-archive"
                    onclick="archiveNotification('5b69867a-239e-4e71-a9e5-cd0322fbe459', this)">
                    <span class="icon-base bx bx-x"></span>
                  </a>
                </div>
              </div>
            </a>
          </li>
              </ul>
    </li>

    <li class="border-top">
      <div class="d-grid p-4">
        <a class="btn btn-primary btn-sm d-flex" href="https://dev.colossal360.com.my/notifications">
          <small class="align-middle">View all notifications</small>
        </a>
      </div>
    </li>
  </ul>
</li>

<script>
  const CSRF = 'YQRkRC0BaX4pShivAxA7bOu6OcI8omFwEjpB4qbU';

  // Click the message → PATCH, then redirect, and remove from list
  function markAsReadAndGo(id, url) {
    fetch(`https://dev.colossal360.com.my/notifications/___ID___/read`.replace('___ID___', id), {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(r => r.ok ? r.json() : Promise.reject())
      .then(() => {
        // update counter immediately
        const badge = document.querySelector('.badge-notifications');
        if (badge) {
          const next = Math.max(0, (parseInt(badge.textContent || '0', 10) - 1));
          next ? badge.textContent = next : badge.remove();
        }
        // remove item from the dropdown
        const li = document.querySelector(`li.dropdown-notifications-item[data-id="${id}"]`);
        li && li.remove();
        // redirect
        window.location.href = url || '/';
      })
      .catch(() => { /* no alert */ });
  }

  // Click the green dot → PATCH only; keep the item visible
  function markAsReadKeep(id, dotEl) {
    event.stopPropagation(); // don’t trigger the outer link
    fetch(`https://dev.colossal360.com.my/notifications/___ID___/read`.replace('___ID___', id), {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(r => r.ok ? r.json() : Promise.reject())
      .then(() => {
        // turn the dot to muted and flag the li as read
        const li = dotEl.closest('li.dropdown-notifications-item');
        if (li) li.classList.add('marked-as-read');

        const dot = dotEl.querySelector('.badge-dot');
        if (dot) {
          dot.classList.remove('bg-success');
          dot.classList.add('bg-secondary'); // visually “read”
        }

        // update the bell count
        const badge = document.querySelector('.badge-notifications');
        if (badge) {
          const next = Math.max(0, (parseInt(badge.textContent || '0', 10) - 1));
          next ? badge.textContent = next : badge.remove();
        }
      })
      .catch(() => { /* no alert */ });
  }

  // Archive (X) → POST and remove from list
  function archiveNotification(id, btnEl) {
    event.stopPropagation();
    fetch(`https://dev.colossal360.com.my/notifications/___ID___/archive`.replace('___ID___', id), {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(r => r.ok ? r.json() : Promise.reject())
      .then(() => {
        const li = btnEl.closest('li.dropdown-notifications-item');
        li && li.remove();
      }).catch(() => { /* no alert */ });
  }

  // Mark all as read
  document.querySelector('.dropdown-notifications-all')?.addEventListener('click', function(e){
    e.preventDefault();
    fetch(`https://dev.colossal360.com.my/notifications/read-all`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(() => {
      document.querySelectorAll('.dropdown-notifications-item').forEach(li => li.remove());
      document.querySelector('.badge-notifications')?.remove();
      document.querySelector('.badge.bg-label-primary')?.remove();
    }).catch(() => {});
  });
</script>
<!--/ Notification -->

                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a
                    class="nav-link dropdown-toggle hide-arrow p-0"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="https://dev.colossal360.com.my/assets/img/avatars/1.png"  alt class="rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    
                    <a class="dropdown-item" href="https://dev.colossal360.com.my/admin/profile">
                      <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                          <div class="avatar avatar-online">
                            <img src="https://dev.colossal360.com.my/assets/img/avatars/1.png" alt="" class="w-px-40 h-auto rounded-circle" />
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="mb-0">Admin User</h6>
                          <small class="text-body-secondary">admin</small>
                        </div>
                      </div>
                    </a>
                  </li>
                    <!-- <li>
                      <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="pages-profile-user.html">
                        <i class="icon-base bx bx-user icon-md me-3"></i><span>My Profile</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="pages-account-settings-account.html">
                        <i class="icon-base bx bx-cog icon-md me-3"></i><span>Settings</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="pages-account-settings-billing.html">
                        <span class="d-flex align-items-center align-middle">
                          <i class="flex-shrink-0 icon-base bx bx-credit-card icon-md me-3"></i
                          ><span class="flex-grow-1 align-middle">Billing Plan</span>
                          <span class="flex-shrink-0 badge rounded-pill bg-danger">4</span>
                        </span>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="pages-pricing.html">
                        <i class="icon-base bx bx-dollar icon-md me-3"></i><span>Pricing</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="pages-faq.html">
                        <i class="icon-base bx bx-help-circle icon-md me-3"></i><span>FAQ</span>
                      </a>
                    </li> -->
                    <li>
                      <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
          <form action="https://dev.colossal360.com.my/logout" method="POST" style="display: inline;">
    <input type="hidden" name="_token" value="YQRkRC0BaX4pShivAxA7bOu6OcI8omFwEjpB4qbU" autocomplete="off">    <a class="dropdown-item" href="javascript:void(0)" onclick="this.closest('form').submit();">
        <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Log Out</span>
    </a>
</form>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->      <!-- Content wrapper -->
      <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
          <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
:root{
  --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB;
  --text:#101828; --muted:#667085;
  --shadow:0 2px 6px rgba(16,24,40,.05);
  --success:#16A34A; --danger:#DC2626; --primary:#111827;
  --accent:#2E3A8C;
}
body{background:var(--bg);}
.page-wrap{max-width:1240px;margin:0 auto}
.card.soft{border:0;background:var(--card);box-shadow:var(--shadow);border-radius:16px}
.form-control,.form-select,.btn{min-height:38px;font-size:14px}

/* Tabs */
.nav-tabs .nav-link{border:0;color:#475467;padding:14px 18px}
.nav-tabs .nav-link.active{color:#111827;border-bottom:3px solid var(--accent);border-radius:0}

/* Section switch */
.section{display:none}
.section.active{display:block}

/* KPI */
.kpi .title{font-size:12px;color:var(--muted)}
.kpi .num{font-weight:700;font-size:22px;color:var(--text)}
.kpi .delta{font-size:12px}
.kpi .icon-pill{
  background:#F2F4F7;color:#667085;border-radius:10px;padding:6px 8px;line-height:1
}

/* Charts & legends */
.chart-wrap{height:260px}
.legend-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;vertical-align:middle}
.legend-row{color:#667085;font-size:13px}

/* Table (通用) */
.table-wrap{border:1px solid var(--border);border-radius:12px;overflow:hidden}
.table thead th{background:#F8FAFC;color:#475467;font-weight:700}
.table>:not(caption)>*>*{padding:12px 14px;vertical-align:middle}
.badge-dot{display:inline-block;width:8px;height:8px;border-radius:999px;margin-right:6px}

/* 紧凑按钮 */
.btn-sm-compact{min-height:36px;font-size:13px;padding:0 14px;border-radius:6px}
.btn-dark-compact{background:#1E2235;color:#fff;border:0}
.btn-dark-compact:hover{background:#111827}

/* Meeting Outcomes：左图表 / 右筛选 */
.outcomes-grid{
  display:grid;
  grid-template-columns: 1.7fr 1fr;
  gap:16px;
  align-items:start;
}
.sidebar{
  border-left:1px solid var(--border);
  padding-left:12px;
}
.filter-stack .form-control,
.filter-stack .form-select{
  min-height:36px; font-size:13px; border-radius:8px;
}
.filter-stack .label{
  font-size:12px; color:#667085; margin-bottom:4px;
}
/* 小屏改为上下排 */
@media (max-width: 992px){
  .outcomes-grid{ grid-template-columns: 1fr; }
  .sidebar{ border-left:0; border-top:1px solid var(--border); padding-left:0; padding-top:12px; }
}

/* 让两张图卡片等高 */
.charts-row .card.soft{height:100%}
</style>

<div class="card soft p-0 mb-3">
  <!-- Tabs（已移除 Machine Usage） -->
  <ul class="nav nav-tabs px-3 pt-3" id="reportTabs" style="border-bottom:1px solid var(--border)">
    <li class="nav-item"><a class="nav-link active" data-target="#salesSec" href="javascript:void(0)">Sales Report</a></li>
    <li class="nav-item"><a class="nav-link" data-target="#orderSec" href="javascript:void(0)">Order Report</a></li>
  </ul>

  <!-- ===================== Sales Report ===================== -->
  <div class="section active" id="salesSec">
    <!-- Filters -->
    <div class="p-3 border-bottom">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label small">Select Salesperson</label>
          <select class="form-select" id="salesperson">
            <option>All Salespersons</option><option>Alex</option><option>Brenda</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Time Period</label>
          <div class="btn-group w-100" role="group">
            <button class="btn btn-outline-dark btn-sm active">Yearly</button>
            <button class="btn btn-outline-dark btn-sm">Quarterly</button>
            <button class="btn btn-outline-dark btn-sm">Monthly</button>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Date Range</label>
          <div class="d-flex align-items-center gap-2">
            <input type="date" class="form-control" value="2025-01-01">
            <span class="text-muted small">to</span>
            <input type="date" class="form-control" value="2025-01-31">
          </div>
        </div>
        <div class="col-md-2 text-md-end">
          <button class="btn btn-dark-compact btn-sm-compact w-100">
            <i class="bi bi-download me-1"></i> Export
          </button>
        </div>
      </div>
    </div>

    <!-- KPI -->
    <div class="p-3">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Total Leads Added</p>
              <span class="icon-pill"><i class="bi bi-magnet"></i></span>
            </div>
            <div class="num">36</div>
            <span class="delta text-success">+12% from last period</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Total Meetings Held</p>
              <span class="icon-pill"><i class="bi bi-calendar3"></i></span>
            </div>
            <div class="num">18</div>
            <span class="delta text-success">+8% from last period</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Accepted Meetings</p>
              <span class="icon-pill"><i class="bi bi-check2-square"></i></span>
            </div>
            <div class="num">10</div>
            <span class="text-muted small">55.6% acceptance rate</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Rejected</p>
              <span class="icon-pill"><i class="bi bi-x-square"></i></span>
            </div>
            <div class="num">8</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="p-3">
      <div class="row g-3 align-items-stretch charts-row">
        <!-- 左：Monthly Performance -->
        <div class="col-lg-7">
          <div class="card soft p-3 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="fw-bold mb-0">Monthly Performance</h6>
              <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0">Month</label>
                <select id="mpMonth" class="form-select form-select-sm" style="width:140px">
                  <option>Jan</option><option>Feb</option><option>Mar</option>
                  <option>Apr</option><option>May</option><option selected>Jun</option>
                </select>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-3 legend-row mb-2 mt-2">
              <span><i class="legend-dot" style="background:#60a5fa"></i>Leads Added</span>
              <span><i class="legend-dot" style="background:#22c55e"></i>Accepted</span>
              <span><i class="legend-dot" style="background:#ef4444"></i>Rejected</span>
              <span><i class="legend-dot" style="background:#06b6d4"></i>50/50</span>
              <span><i class="legend-dot" style="background:#a78bfa"></i>Low Chance</span>
            </div>

            <div class="chart-wrap flex-grow-1"><canvas id="barMonthly"></canvas></div>
          </div>
        </div>

        <!-- 右：Meeting Outcomes -->
        <div class="col-lg-5">
          <div class="card soft p-3 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold mb-0">Meeting Outcomes</h6>
            </div>

            <div class="outcomes-grid">
              <!-- 左：饼图 -->
              <div>
                <div class="chart-wrap"><canvas id="pieOutcome"></canvas></div>
                <div class="mt-2 small">
                  <span class="legend-dot" style="background:#22c55e"></span>Accepted
                  <span class="legend-dot" style="background:#ef4444;margin-left:14px"></span>Rejected
                </div>
              </div>

              <!-- 右：竖排筛选 -->
              <aside class="sidebar">
                <div class="filter-stack d-flex flex-column gap-3">
                  <div>
                    <div class="label">Start date</div>
                    <input type="date" class="form-control" value="2025-01-01">
                  </div>
                  <div>
                    <div class="label">End date</div>
                    <input type="date" class="form-control" value="2025-01-31">
                  </div>
                  <div>
                    <div class="label">Salesperson</div>
                    <select class="form-select">
                      <option>All Salespersons</option>
                      <option>Alex</option>
                      <option>Brenda</option>
                    </select>
                  </div>
                  <div>
                    <div class="label">Period</div>
                    <select class="form-select">
                      <option selected>Monthly</option>
                      <option>Quarterly</option>
                      <option>Yearly</option>
                    </select>
                  </div>
                </div>
              </aside>
            </div>
          </div>
        </div>
      </div>
    </div> <!-- /p-3 -->
  </div><!-- /salesSec -->

  <!-- ===================== Order Report ===================== -->
  <div class="section" id="orderSec">
    <!-- Filters -->
    <div class="p-3 border-bottom">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label small">Time Period</label>
          <select class="form-select" id="ordPeriod">
            <option selected>Monthly</option>
            <option>Quarterly</option>
            <option>Yearly</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label small">Date Range</label>
          <div class="d-flex gap-2">
            <input type="date" class="form-control" value="2025-01-01">
            <input type="date" class="form-control" value="2025-01-31">
          </div>
        </div>
        <div class="col-md-2 ms-auto text-md-end">
          <button class="btn btn-dark-compact btn-sm-compact w-100">
            <i class="bi bi-download me-1"></i> Export
          </button>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="p-3">
      <div class="card soft p-3">
        <h6 class="fw-bold mb-2">Job Order Fulfillment</h6>
        <div class="chart-wrap"><canvas id="orderFulfill"></canvas></div>
        <div class="small mt-2">
          <span class="badge-dot" style="background:#22c55e"></span>New Order
          <span class="badge-dot" style="background:#f59e0b;margin-left:14px"></span>In Progress
          <span class="badge-dot" style="background:#06b6d4;margin-left:14px"></span>Completed
          <span class="badge-dot" style="background:#ef4444;margin-left:14px"></span>Overdue
        </div>
      </div>
    </div>
  </div><!-- /orderSec -->
</div>

<script>
// ===== Tabs（仅两页） =====
document.querySelectorAll('#reportTabs .nav-link').forEach(a=>{
  a.addEventListener('click', ()=>{
    document.querySelectorAll('#reportTabs .nav-link').forEach(x=>x.classList.remove('active'));
    a.classList.add('active');
    document.querySelectorAll('.section').forEach(sec=>sec.classList.remove('active'));
    document.querySelector(a.dataset.target).classList.add('active');
  });
});

// ===== Monthly Performance（单月构成） =====
const mpCtx = document.getElementById('barMonthly');

const mpLabels = ['Leads Added','Accepted','Rejected','50/50','Low Chance'];
const mpColors = ['#60a5fa','#22c55e','#ef4444','#06b6d4','#a78bfa'];

const monthlyData = {
  Jan: [100,30,15,50,5],
  Feb: [80,25,12,35,8],
  Mar: [95,22,14,40,7],
  Apr: [110,21,16,38,6],
  May: [90,34,10,45,4],
  Jun: [100,30,15,50,5],
};

function makeDataset(values){
  return [{
    label: 'This Month',
    data: values,
    backgroundColor: mpColors,
    borderRadius: 6,
    borderSkipped: false
  }];
}

let currentMonth = 'Jun';
let barMonthly = new Chart(mpCtx, {
  type: 'bar',
  data: { labels: mpLabels, datasets: makeDataset(monthlyData[currentMonth]) },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed.y}` } }
    },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, ticks: { stepSize: 20 } }
    }
  }
});

// 月份下拉切换
const mpSel = document.getElementById('mpMonth');
mpSel?.addEventListener('change', () => {
  currentMonth = mpSel.value;
  barMonthly.data.datasets = makeDataset(monthlyData[currentMonth]);
  barMonthly.update();
});

// ===== Meeting Outcomes 饼图 =====
const pieCtx = document.getElementById('pieOutcome');
if (pieCtx) {
  new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: ['Accepted', 'Rejected'],
      datasets: [{
        data: [10, 8],
        backgroundColor: ['#22c55e', '#ef4444'],
        borderWidth: 0
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });
}

// ===== Order chart =====
new Chart(document.getElementById('orderFulfill'),{
  type:'bar',
  data:{
    labels:['New Order','In Progress','Completed','Overdue'],
    datasets:[{ data:[30,12,17,1], backgroundColor:['#22c55e','#f59e0b','#06b6d4','#ef4444'] }]
  },
  options:{
    responsive:true, maintainAspectRatio:false,
    plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}
  }
});
</script>
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

    <!-- Core JS (bottom, in order) -->
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/popper/popper.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/js/bootstrap.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/@algolia/autocomplete-js.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/pickr/pickr.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/hammer/hammer.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/i18n/i18n.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/js/menu.js"></script>

    <!-- Vendors JS (conditional) -->
    
      

    

    <script src="https://dev.colossal360.com.my/assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/select2/select2.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/moment/moment.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/flatpickr/flatpickr.js"></script>

    
  

    <script src="https://dev.colossal360.com.my/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/chartjs/chartjs.js"></script>

    
        <!-- endbuild -->


    <!-- Vendors JS -->
    
      
 

    <script src="https://dev.colossal360.com.my/assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/select2/select2.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/moment/moment.js"></script>
    <script src="https://dev.colossal360.com.my/assets/vendor/libs/flatpickr/flatpickr.js"></script>


        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Main JS -->
    <script src="https://dev.colossal360.com.my/assets/js/main.js"></script>


    

    
    </body>

</html>

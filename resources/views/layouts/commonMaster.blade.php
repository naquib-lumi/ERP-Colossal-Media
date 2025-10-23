<!-- Common Master Blade Updated -->
<!DOCTYPE html>
@php
    use Illuminate\Support\Str;
    use App\Helpers\Helpers;
    $menuFixed =
        $configData['myLayout'] === 'vertical'
            ? $menuFixed ?? ''
            : ($configData['myLayout'] === 'front'
                ? ''
                : $configData['headerType'] ?? '');
    $navbarType =
        $configData['myLayout'] === 'vertical'
            ? $configData['navbarType'] ?? ''
            : ($configData['myLayout'] === 'front'
                ? 'layout-navbar-fixed'
                : '');
    $isFront = ($isFront ?? '') == true ? 'Front' : '';
    $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';
    $isAdminLayout = !Str::contains($configData['myLayout'] ?? '', 'front');
    $skinName = $isAdminLayout ? $configData['skinName'] ?? 'default' : 'default';
    $semiDarkEnabled = $isAdminLayout && filter_var($configData['semiDark'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $primaryColorCSS = '';
    if (isset($configData['color']) && $configData['color']) {
        $primaryColorCSS = Helpers::generatePrimaryColorCSS($configData['color']);
    }
@endphp
<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
    class="{{ $navbarType ?? '' }} {{ $contentLayout ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
    dir="{{ $configData['textDirection'] }}" data-skin="{{ $skinName }}"
    data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{ url('/') }}" data-framework="laravel"
    data-template="{{ $configData['myLayout'] }}-menu-template" data-bs-theme="{{ $configData['themeOpt'] }}"
    @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>


<link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
<!-- Vendors CSS -->

<link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}" />

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>@yield('title') |
        {{ config('variables.templateName') ? config('variables.templateName') : 'Colossal ERP' }}</title>
    <meta name="description"
        content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
    <meta name="keywords"
        content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />


    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}" />

    <!-- Page CSS (conditional) -->
    @if (Request::is('sales/calendar') || Request::is('calendar/*'))
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-calendar.css') }}" />
    @endif

    @if (Request::is('sales/dashboard') || Request::is('dashboard/*'))
        <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    @endif

    @if (Request::is('sales/leads') || Request::is('sales/calendar') || Request::is('sales/orders') || Request::is('sales/dashboard'))
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
        <link rel="stylesheet"
            href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    @endif
    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <!-- <script src="{{ asset('assets/vendor/js/template-customizer.js') }}"></script> UNCOMMENT-CUSTOMIZER-->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>


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

                let markAsReadUrl = '{{ route('notifications.markAsRead', ['id' => 'PLACEHOLDER']) }}'
                    .replace('PLACEHOLDER', notificationId);

                $.ajax({
                    url: markAsReadUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
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
                    url: '{{ route('notifications.markAllAsRead') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
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

                let archiveUrl = '{{ route('notifications.archive', ['id' => 'PLACEHOLDER']) }}'.replace(
                    'PLACEHOLDER', notificationId);

                $.ajax({
                    url: archiveUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
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
                    url: '{{ route('notifications.count') }}',
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
{{-- DataTables CSS for pages that need it --}}

@if (Request::is('artist/*') || Request::is('dashboard') || Request::is('dashboard/') || Request::is('sales/leads') || Request::is('sales/orders') || Request::is('artist/calendar') || Request::is('calendar/*'))
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-select-bs5/select.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/datatables-fixedheader-bs5/fixedheader.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/dropzone/dropzone.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-calendar.css') }}" />
@endif

@if (Request::is('installation/*') || Request::is('installation/calendar') || Request::is('calendar/*'))
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-calendar.css') }}" />
@endif

@if (Request::is('data-entry/*'))
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-select-bs5/select.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/datatables-fixedheader-bs5/fixedheader.bootstrap5.css') }}" />
@endif

{{-- Page-level extra styles from views --}}
@stack('styles')
</head>

<body>

    @yield('layoutContent')

    <!-- Core JS (bottom, in order) -->
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@algolia/autocomplete-js.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>

    <!-- Vendors JS (conditional) -->
    @if (Request::is('sales/calendar') || Request::is('calendar/*'))
        <script src="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.js') }}"></script>
        <script src="{{ asset('assets/js/app-calendar-events.js') }}"></script>
        <script src="{{ asset('assets/js/app-calendar.js') }}"></script>
    @endif

    <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>

    @if (Request::is('sales/dashboard') || Request::is('dashboard/*'))
        <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    @endif

  {{-- Update this @if block in the layout --}}
@if (Request::is('artist/*') || Request::is('dashboard') || Request::is('dashboard/') || Request::is('artist/calendar') || Request::is('calendar/*') )
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>
    <script src="{{ asset('assets/js/artist-app-calendar.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.js') }}"></script>
    <script src="{{ asset('assets/js/app-calendar-events.js') }}"></script>
@endif

@if(Request::is('admin/*'))
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>
@endif

    @if (Request::is('installation/*') || Request::is('installation/calendar') || Request::is('calendar/*') )
        <script src="{{ asset('assets/js/installation-app-calendar.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.js') }}"></script>
        <script src="{{ asset('assets/js/app-calendar-events.js') }}"></script>
    @endif

    @if (Request::is('data-entry/*'))
        <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>

    @endif
    <!-- endbuild -->


    <!-- Vendors JS -->
    @if (Request::is('sales/calendar') || Request::is('calendar'))
        <script src="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.js') }}"></script>
        <!-- Page JS -->
        <!-- <script src="{{ asset('assets/js/app-calendar-events.js') }}"></script> -->
        <script src="{{ asset('assets/js/app-calendar.js') }}"></script>
    @endif

    <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>


    @if (Request::is('sales/dashboard') || Request::is('dashboard/*'))
        <!-- <script src="{{ asset('assets/js/dashboards-crm.js') }}"></script> -->
        <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets/js/main.js') }}"></script>


    @if (Request::is('sales/leads') || Request::is('sales/orders'))
        <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
        <!-- <script src="{{ asset('assets/js/tables-datatables-advanced.js') }}"></script> -->
    @endif


    {{-- View-level scripts --}}
    @stack('scripts')
</body>

</html>

<!-- Common Master Blade -->
<!DOCTYPE html>
@php
  use Illuminate\Support\Str;
  use App\Helpers\Helpers;
  $menuFixed = $configData['myLayout'] === 'vertical' ? ($menuFixed ?? '') : ($configData['myLayout'] === 'front' ? '' : ($configData['headerType'] ?? ''));
  $navbarType = $configData['myLayout'] === 'vertical' ? ($configData['navbarType'] ?? '') : ($configData['myLayout'] === 'front' ? 'layout-navbar-fixed' : '');
  $isFront = ($isFront ?? '') == true ? 'Front' : '';
  $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';
  $isAdminLayout = !Str::contains($configData['myLayout'] ?? '', 'front');
  $skinName = $isAdminLayout ? ($configData['skinName'] ?? 'default') : 'default';
  $semiDarkEnabled = $isAdminLayout && filter_var($configData['semiDark'] ?? false, FILTER_VALIDATE_BOOLEAN);
  $primaryColorCSS = '';
  if (isset($configData['color']) && $configData['color']) {
      $primaryColorCSS = Helpers::generatePrimaryColorCSS($configData['color']);
  }
@endphp
<html
lang="{{ session()->get('locale') ?? app()->getLocale() }}"
 class="{{ $navbarType ?? '' }} {{ $contentLayout ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
  dir="{{ $configData['textDirection'] }}" data-skin="{{ $skinName }}" data-assets-path="{{ asset('/assets') . '/' }}"
  data-base-url="{{ url('/') }}" data-framework="laravel" data-template="{{ $configData['myLayout'] }}-menu-template"
  data-bs-theme="{{ $configData['themeOpt'] }}" @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>
    @yield('title') | {{ config('variables.templateName') ? config('variables.templateName') : 'Colossal ERP' }}
    - {{ config('variables.templateSuffix') ? config('variables.templateSuffix') : 'Admin Template' }}
  </title>
    <meta name="description"  content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" /> 
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
    <!-- build:css assets/vendor/css/theme.css  -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <!-- endbuild -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-calendar.css') }}" />
    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <!-- <script src="{{ asset('assets/vendor/js/template-customizer.js') }}" ></script> UNCOMMENT-CUSTOMIZER-->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('assets/js/config.js') }}" ></script>
  </head>

  <body>
      @yield('layoutContent')
    <!-- Layout wrapper -->
    
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/theme.js  -->

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}" ></script>

    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}" ></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}" ></script>
    <script src="{{ asset('assets/vendor/libs/@algolia/autocomplete-js.js') }}" ></script>

    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}" ></script>

    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}" ></script>

    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}" ></script>

    <script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}" ></script>

    <script src="{{ asset('assets/vendor/js/menu.js') }}" ></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.js') }}" ></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}" ></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}" ></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}" ></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}" ></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}" ></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}" ></script>

    <!-- Main JS -->

    <script src="{{ asset('assets/js/main.js') }}" ></script>

    <!-- Page JS -->
    <script src="{{ asset('assets/js/app-calendar-events.js') }}" ></script>
    <script src="{{ asset('assets/js/app-calendar.js') }}" ></script>
  <!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>
</html>
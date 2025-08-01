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
<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
  class="{{ $navbarType ?? '' }} {{ $contentLayout ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
  dir="{{ $configData['textDirection'] }}" data-skin="{{ $skinName }}" data-assets-path="{{ asset('/assets') . '/' }}"
  data-base-url="{{ url('/') }}" data-framework="laravel" data-template="{{ $configData['myLayout'] }}-menu-template"
  data-bs-theme="{{ $configData['themeOpt'] }}" @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>
<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>
    @yield('title') | {{ config('variables.templateName') ? config('variables.templateName') : 'Colossal ERP' }}
    - {{ config('variables.templateSuffix') ? config('variables.templateSuffix') : 'Admin Template' }}
  </title>
  <meta name="description"
    content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords"
    content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fontawesome.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
  @if ($configData['hasCustomizer'])
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
  @endif
  @if ($primaryColorCSS && (config('custom.custom.primaryColor') || isset($_COOKIE['admin-primaryColor']) || isset($_COOKIE['front-primaryColor'])))
    <style id="primary-color-style">
      {!! $primaryColorCSS !!}
    </style>
  @endif
  @include('layouts.sections.scriptsIncludes' . $isFront)
  @yield('vendor-style')
  @yield('page-style')
</head>
<body>
  @yield('layoutContent')
  <div class="buy-now">
    <a href="{{ config('variables.productPage') }}" target="_blank" class="btn btn-danger btn-buy-now">Buy Now</a>
  </div>
  @include('layouts.sections.scripts' . $isFront)
</body>
</html>
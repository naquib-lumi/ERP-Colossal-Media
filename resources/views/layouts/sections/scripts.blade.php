<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}" defer></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}" defer></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}" defer></script>
@if ($configData['hasCustomizer'])
  <script src="{{ asset('assets/vendor/libs/@algolia/autocomplete-js.js') }}" defer></script>
  <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}" defer></script>
@endif
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}" defer></script>
<script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}" defer></script>
<script src="{{ asset('assets/vendor/js/menu.js') }}" defer></script>
<script src="{{ asset('assets/js/main.js') }}" defer></script>
@yield('vendor-script')
@stack('pricing-script')
@yield('page-script')
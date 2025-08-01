@php
  $menuCollapsed = $configData['menuCollapsed'] === 'layout-menu-collapsed' ? json_encode(true) : false;
  $skin = $configData['skins'] ?? 0;
  $skinName = $configData['skinName'] ?? '';
  $defaultSkin = $skinName ?: $skin;
  $isAdminLayout = !str_contains($configData['myLayout'] ?? '', 'front');
  $primaryColorCookieName = $isAdminLayout ? 'admin-primaryColor' : 'front-primaryColor';
  $primaryColor = isset($_COOKIE[$primaryColorCookieName]) ? $_COOKIE[$primaryColorCookieName] : $configData['color'] ?? null;
@endphp

<script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
@if ($configData['hasCustomizer'])
  <script src="{{ asset('assets/vendor/js/template-customizer.js') }}"></script>
@endif
<script src="{{ asset('assets/js/config.js') }}"></script>

@if ($configData['hasCustomizer'])
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (window.TemplateCustomizer) {
        try {
          const appliedSkin = document.documentElement.getAttribute('data-skin') || "{{ $defaultSkin }}";
          window.templateCustomizer = new TemplateCustomizer({
            defaultTextDir: "{{ $configData['textDirection'] }}",
            @if ($primaryColor)
              defaultPrimaryColor: "{{ $primaryColor }}",
            @endif
            defaultTheme: "{{ $configData['themeOpt'] }}",
            defaultSkin: appliedSkin,
            defaultSemiDark: {{ $configData['semiDark'] ? 'true' : 'false' }},
            defaultShowDropdownOnHover: "{{ $configData['showDropdownOnHover'] }}",
            displayCustomizer: "{{ $configData['displayCustomizer'] }}",
            lang: '{{ app()->getLocale() }}',
            'controls': <?php echo json_encode($configData['customizerControls']); ?>,
          });
          @if ($primaryColor)
            if (window.Helpers && typeof window.Helpers.setColor === 'function') {
              window.Helpers.setColor("{{ $primaryColor }}", true);
            }
          @endif
        } catch (error) {
          console.warn('Template customizer initialization error:', error);
        }
      }
    });
  </script>
@endif
@php
  // ===== Brand tokens (tweak as you like) =====
  $appName   = $appName      ?? config('app.name');
  $title     = $title        ?? 'Notification';
  $intro     = $intro        ?? null;            // short sentence under title
  $message   = $messageText  ?? '';              // main message (plain text)
  $ctaLabel  = $ctaLabel     ?? 'View Details';
  $ctaUrl    = $ctaUrl       ?? url('/');
  $helpText  = $helpText     ?? "If the button doesn’t work, copy the link below into your browser.";
  $details   = $details      ?? [];              // array of ['label' => '', 'value' => '']
  $secondary = $secondary    ?? null;            // ['label' => '', 'url' => ''] optional
  $heroEmoji = $heroEmoji    ?? '📬';            // simple hero mark; swap to <img> if you prefer

  // Colors
  $brand    = $brandColor    ?? '#696cff';
  $bg       = '#EEF3F8';
  $text     = '#0F172A';
  $muted    = '#6B7280';
  $border   = '#E5E7EB';

  // Header gradient like HubSpot/Pitch styles (subtle)
  $g1 = $headerStart ?? '#696cff';
  $g2 = $headerEnd   ?? '#696cff';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>{{ $subject ?? ($title . ' – ' . $appName) }}</title>
<style>
  @media only screen and (max-width: 600px) {
    .inner { width: 100% !important; }
    .btn   { width: 100% !important; display:block !important; text-align:center !important; }
    .pxy   { padding-left:18px !important; padding-right:18px !important; }
  }
</style>
</head>
<body style="margin:0; padding:0; background:{{ $bg }};">

{{-- Invisible preview text for inbox snippets --}}
@if(!empty($previewText ?? null))
  <span style="display:none!important;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
    {{ $previewText }}
  </span>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $bg }};">
  <tr>
    <td align="center" style="padding:24px 12px;">
      <table role="presentation" class="inner" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:100%;">

        {{-- Brand bar / logo on subtle gradient --}}
        <tr>
          <td style="border-radius:12px 12px 0 0; background:linear-gradient(90deg, {{ $g1 }} 0%, {{ $g2 }} 100%); padding:14px;">
            <table role="presentation" width="100%">
              <tr>
                <td align="center" style="font-family:Arial,Helvetica,sans-serif; color:#fff;">
                  @if(!empty($logoUrl ?? null))
                    <img src="{{ $logoUrl }}" alt="{{ $appName }}" height="28" style="display:block;margin:0 auto;border:0;">
                  @else
                    <div style="font-weight:700; font-size:16px;">{{ $appName }}</div>
                  @endif
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Card --}}
        <tr>
          <td style="background:#fff; border:1px solid {{ $border }}; border-top:0; border-radius:0 0 12px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="pxy" style="padding:28px 28px 8px 28px; font-family:Arial,Helvetica,sans-serif;">

                  {{-- Hero + Title (Discord/Pitch vibe) --}}
                  <div style="text-align:center; margin-bottom:6px; font-size:36px; line-height:1;">{{ $heroEmoji }}</div>
                  <h1 style="margin:12px 0 8px; font-size:22px; line-height:1.35; color:{{ $text }}; text-align:center;">
                    {{ $title }}
                  </h1>

                  @if($intro)
                    <p style="margin:0 0 16px; font-size:14px; color:{{ $muted }}; text-align:center;">
                      {{ $intro }}
                    </p>
                  @endif

                  {{-- Main copy --}}
                  <div style="margin:14px 0 18px; font-size:14px; line-height:1.7; color:{{ $text }};">
                    {!! nl2br(e($message)) !!}
                  </div>

                  {{-- Optional details table (clean “key:value” like product/order facts) --}}
                  @if(!empty($details))
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px; border:1px solid {{ $border }}; border-radius:8px;">
                      @foreach($details as $row)
                        <tr>
                          <td style="padding:10px 14px; font-size:13px; color:{{ $muted }}; width:38%; border-bottom:1px solid {{ $border }};">
                            {{ $row['label'] ?? '' }}
                          </td>
                          <td style="padding:10px 14px; font-size:13px; color:{{ $text }}; border-bottom:1px solid {{ $border }};">
                            {!! e($row['value'] ?? '') !!}
                          </td>
                        </tr>
                      @endforeach
                    </table>
                  @endif

                  {{-- Primary CTA --}}
                  <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:18px 0 8px;">
                    <tr>
                      <td align="center">
                        <a href="{{ $ctaUrl }}" target="_blank"
                           class="btn"
                           style="background:{{ $brand }}; color:#fff; text-decoration:none;
                                  font-weight:600; font-size:14px; padding:12px 20px; border-radius:8px; display:inline-block;">
                          {{ $ctaLabel }}
                        </a>
                      </td>
                    </tr>
                  </table>

                  {{-- Optional secondary link (subtle) --}}
                  @if(!empty($secondary) && !empty($secondary['label']) && !empty($secondary['url']))
                    <p style="margin:8px 0 0; text-align:center; font-size:13px;">
                      <a href="{{ $secondary['url'] }}" target="_blank" style="color:{{ $brand }}; text-decoration:none;">
                        {{ $secondary['label'] }}
                      </a>
                    </p>
                  @endif

                  {{-- Divider --}}
                  <div style="height:1px; background:{{ $border }}; margin:22px 0 12px;"></div>

                  {{-- Fallback URL --}}
                  <p style="margin:0 0 6px; font-size:12px; color:{{ $muted }};">
                    {{ $helpText }}
                  </p>
                  <p style="margin:0 0 6px; font-size:12px;">
                    <a href="{{ $ctaUrl }}" target="_blank" style="color:{{ $brand }};">{{ $ctaUrl }}</a>
                  </p>

                  {{-- Signoff --}}
                  @if(!empty($salutation ?? null))
                    <p style="margin:18px 0 0; font-size:14px; color:{{ $text }};">{!! $salutation !!}</p>
                  @endif
                </td>
              </tr>

              {{-- Footer --}}
              <tr>
                <td align="center" style="padding:18px; font-size:12px; color:#64748B; font-family:Arial,Helvetica,sans-serif;">
                  © {{ date('Y') }} {{ $appName }}. All rights reserved.
                </td>
              </tr>

            </table>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>

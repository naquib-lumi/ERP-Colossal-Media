@php
  $appName   = $appName      ?? config('app.name');
  $title     = $title        ?? 'Reminder';
  $intro     = $intro        ?? 'A quick nudge so you don’t miss this.';
  $message   = $messageText  ?? '';
  $ctaLabel  = $ctaLabel     ?? 'Review Now';
  $ctaUrl    = $ctaUrl       ?? url('/');

  $brand     = $brandColor   ?? '#F59E0B'; // amber
  $bg        = '#F5F7FA';
  $text      = '#0F172A';
  $muted     = '#6B7280';
  $border    = '#E5E7EB';
  $g1        = $headerStart  ?? '#F59E0B';
  $g2        = $headerEnd    ?? '#F97316';
  $heroEmoji = $heroEmoji    ?? '⏰';
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
  }
</style>
</head>
<body style="margin:0; padding:0; background:{{ $bg }};">

@if(!empty($previewText ?? null))
  <span style="display:none!important;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
    {{ $previewText }}
  </span>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $bg }};">
  <tr>
    <td align="center" style="padding:24px 12px;">
      <table role="presentation" class="inner" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:100%;">

        <tr>
          <td style="border-radius:12px 12px 0 0; background:linear-gradient(90deg, {{ $g1 }} 0%, {{ $g2 }} 100%); padding:14px;">
            <table role="presentation" width="100%">
              <tr>
                <td align="center" style="font-family:Arial,Helvetica,sans-serif; color:#fff;">
                  @if(!empty($logoUrl))
                    <img src="{{ $logoUrl }}" alt="{{ $appName }}" height="28" style="display:block;margin:0 auto;border:0;">
                  @else
                    <div style="font-weight:700; font-size:16px;">{{ $appName }}</div>
                  @endif
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="background:#fff; border:1px solid {{ $border }}; border-top:0; border-radius:0 0 12px 12px;">
            <table role="presentation" width="100%">
              <tr>
                <td style="padding:28px; font-family:Arial,Helvetica,sans-serif;">
                  <div style="text-align:center; margin-bottom:6px; font-size:36px; line-height:1;">{{ $heroEmoji }}</div>
                  <h1 style="margin:12px 0 8px; font-size:22px; line-height:1.35; color:{{ $text }}; text-align:center;">
                    {{ $title }}
                  </h1>
                  <p style="margin:0 0 16px; font-size:14px; color:{{ $muted }}; text-align:center;">
                    {{ $intro }}
                  </p>

                  <div style="margin:14px 0 18px; font-size:14px; line-height:1.7; color:{{ $text }};">
                    {!! nl2br(e($message)) !!}
                  </div>

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

                  <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:18px 0 8px;">
                    <tr>
                      <td align="center">
                        <a href="{{ $ctaUrl }}" target="_blank"
                           class="btn"
                           style="background:{{ $brand }}; color:#fff; text-decoration:none; font-weight:600; font-size:14px;
                                  padding:12px 20px; border-radius:8px; display:inline-block;">
                          {{ $ctaLabel }}
                        </a>
                      </td>
                    </tr>
                  </table>

                  <div style="height:1px; background:{{ $border }}; margin:22px 0 12px;"></div>

                  <p style="margin:0 0 6px; font-size:12px; color:{{ $muted }};">
                    If the button doesn’t work, copy and paste this URL into your browser:
                  </p>
                  <p style="margin:0 0 6px; font-size:12px;">
                    <a href="{{ $ctaUrl }}" target="_blank" style="color:{{ $brand }};">{{ $ctaUrl }}</a>
                  </p>

                  @if(!empty($salutation ?? null))
                    <p style="margin:18px 0 0; font-size:14px; color:{{ $text }};">{!! $salutation !!}</p>
                  @endif
                </td>
              </tr>

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

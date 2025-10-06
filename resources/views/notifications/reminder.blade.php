  @component('mail::message')
  # Reminder: {{ $reminder->title }}

  Dear {{ $user->name }},

  You have a reminder for the following task:

  - **Title**: {{ $reminder->title }}
  - **Due Date**: {{ $reminder->remind_at->format('Y-m-d H:i') }}
  - **Lead ID**: {{ $reminder->lead_id }}
  - **Status**: {{ ucfirst($reminder->status) }}

  <!-- [View Lead]({{ url('/sales/leads/' . $reminder->lead_id) }}) -->

  Please take appropriate action to address this reminder.

  Thanks,
  {{ config('app.name') }}
  @endcomponent
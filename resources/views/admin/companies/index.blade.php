@extends('layouts.app')
@section('title', 'Companies')
@section('content')
@include('inventory._styles')

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <div class="inv-title">Companies</div>
        <div class="inv-sub">The companies that issue quotations and delivery orders. Their logo and details are printed on both.</div>
      </div>
    </div>

    @include('inventory._flash')

    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr><th>Logo</th><th>Company</th><th>Address</th><th>Contact</th><th style="text-align:right">Actions</th></tr>
        </thead>
        <tbody>
          @foreach ($companies as $c)
            <tr>
              <td>@if ($c->logoPath())<img src="{{ asset($c->logo) }}" alt="{{ $c->name }}" style="max-height:40px;max-width:160px">@else - @endif</td>
              <td>
                <strong>{{ $c->name }}</strong>@if ($c->is_default) <span class="inv-badge ok">Default</span>@endif
                @if ($c->reg_no)<div class="inv-sub">{{ $c->reg_no }}</div>@endif
              </td>
              <td style="max-width:280px">{!! nl2br(e($c->address)) !!}</td>
              <td>{{ $c->contactLine() ?: '-' }}</td>
              <td><div class="inv-actions"><a class="inv-btn inv-btn-ghost" href="{{ route('admin.companies.edit', $c) }}"><i class="bi bi-pencil"></i> Edit</a></div></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

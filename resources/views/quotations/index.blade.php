@extends('layouts.app')
@section('title', 'Quotations')
@section('content')
@include('inventory._styles')

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <div class="inv-title">Quotations</div>
        <div class="inv-sub">Newest first. A quotation is locked once an order is created from it.</div>
      </div>
      @if ($canCreate)
        <a class="inv-btn inv-btn-dark" href="{{ route('quotations.create') }}"><i class="bi bi-plus-lg"></i> New quotation</a>
      @endif
    </div>

    @include('inventory._flash')

    <form class="inv-toolbar" method="GET" action="{{ route('quotations.index') }}">
      <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search number, customer or Attn..." aria-label="Search quotations">
      <select name="status" aria-label="Status">
        <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
        <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
        <option value="converted" @selected($filters['status'] === 'converted')>Converted to order</option>
      </select>
      <button class="inv-btn inv-btn-dark" type="submit"><i class="bi bi-search"></i> Filter</button>
      @if ($filters['q'] !== '' || $filters['status'] !== 'all')
        <a class="inv-btn inv-btn-ghost" href="{{ route('quotations.index') }}">Clear</a>
      @endif
    </form>

    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th>Quotation</th>
            <th>Customer</th>
            <th>Company</th>
            <th>Salesperson</th>
            <th class="inv-num">Grand total (RM)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($quotations as $q)
            <tr>
              <td>
                <a href="{{ route('quotations.show', $q) }}"><strong>{{ $q->quotation_number }}</strong></a>
                <div class="inv-sub">{{ $q->quotation_date?->format('d M Y') }}</div>
              </td>
              <td>{{ $q->lead->company_name ?? '-' }}@if ($q->attention)<div class="inv-sub">Attn: {{ $q->attention }}</div>@endif</td>
              <td>{{ $q->company->name ?? '-' }}</td>
              <td>{{ $q->salesperson->name ?? '-' }}</td>
              <td class="inv-num">{{ number_format((float) $q->grand_total, 2) }}</td>
              <td>
                @if ($q->isConverted())
                  <span class="inv-badge ok">Converted</span>
                  @if ($q->order)<div class="inv-sub">{{ $q->order->order_number }}</div>@endif
                @else
                  <span class="inv-badge near">Pending</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="inv-empty">No quotations found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($quotations->hasPages())
      <div class="inv-pager">
        Page {{ $quotations->currentPage() }} of {{ $quotations->lastPage() }}
        @if ($quotations->previousPageUrl())<a class="inv-btn inv-btn-ghost" href="{{ $quotations->previousPageUrl() }}">Previous</a>@endif
        @if ($quotations->nextPageUrl())<a class="inv-btn inv-btn-ghost" href="{{ $quotations->nextPageUrl() }}">Next</a>@endif
      </div>
    @endif
  </div>
</div>
@endsection

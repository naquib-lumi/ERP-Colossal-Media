@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
@include('inventory._styles')

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <div class="inv-title">Inventory</div>
        <div class="inv-sub">Stock on hand per material, in whole units (roll, sheet, piece, box...).</div>
      </div>
    </div>

    @include('inventory._flash')

    <form class="inv-toolbar" method="GET" action="{{ route('inventory.index') }}">
      <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search name or reference..." aria-label="Search materials">
      <select name="type" aria-label="Material type">
        <option value="all">All material types</option>
        @foreach ($types as $t)
          <option value="{{ $t->id }}" @selected((string) $filters['type'] === (string) $t->id)>{{ $t->name }}</option>
        @endforeach
      </select>
      <select name="status" aria-label="Stock status">
        <option value="all" @selected($filters['status'] === 'all')>All stock</option>
        <option value="near" @selected($filters['status'] === 'near')>Near low stock</option>
        <option value="low" @selected($filters['status'] === 'low')>Low stock</option>
        <option value="negative" @selected($filters['status'] === 'negative')>Negative stock</option>
      </select>
      <button class="inv-btn inv-btn-dark" type="submit"><i class="bi bi-search"></i> Filter</button>
      @if ($filters['q'] !== '' || $filters['type'] !== 'all' || $filters['status'] !== 'all')
        <a class="inv-btn inv-btn-ghost" href="{{ route('inventory.index') }}">Clear</a>
      @endif
    </form>

    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th>Material</th>
            <th>Type</th>
            <th class="inv-num">Stock</th>
            <th class="inv-num">Alert at</th>
            <th>Status</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($materials as $m)
            <tr>
              <td>
                <a href="{{ route('inventory.show', $m) }}">{{ $m->materialName }}</a>
                @if ($m->internal_ref)<div class="inv-sub">{{ $m->internal_ref }}</div>@endif
              </td>
              <td>{{ $m->materialType->name ?? '-' }}</td>
              <td class="inv-num {{ $m->stock_quantity < 0 ? 'inv-neg' : '' }}">
                {{ number_format($m->stock_quantity) }} {{ $m->quantity_unit }}
              </td>
              <td class="inv-num">{{ $m->low_stock_quantity !== null ? number_format($m->low_stock_quantity) : '-' }}</td>
              <td>@include('inventory._status', ['m' => $m])</td>
              <td>
                <div class="inv-actions">
                  <a class="inv-btn inv-btn-ghost" href="{{ route('inventory.show', $m) }}"><i class="bi bi-clock-history"></i> History</a>
                  @include('inventory._row-actions', ['m' => $m])
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="inv-empty">No materials match these filters.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($materials->hasPages())
      <div class="inv-pager">
        Page {{ $materials->currentPage() }} of {{ $materials->lastPage() }}
        @if ($materials->previousPageUrl())<a class="inv-btn inv-btn-ghost" href="{{ $materials->previousPageUrl() }}">Previous</a>@endif
        @if ($materials->nextPageUrl())<a class="inv-btn inv-btn-ghost" href="{{ $materials->nextPageUrl() }}">Next</a>@endif
      </div>
    @endif
  </div>
</div>

@include('inventory._modals')
@endsection

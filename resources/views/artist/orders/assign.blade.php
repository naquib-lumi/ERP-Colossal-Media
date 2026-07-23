@extends('layouts.app')

@section('content')
@push('styles')
<style>
  .assign-artist-section {
    width: 100%;
    max-width: 850px;
    min-width: 0;
  }

  .assign-artist-controls {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    width: 100%;
    min-width: 0;
  }

  .assign-artist-select {
    position: relative;
    flex: 1 1 0;
    width: 0;
    min-width: 0;
    max-width: 100%;
  }

  .assign-artist-select>.select2-container {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
  }

  .assign-artist-select .select2-selection--single {
    height: 38px;
    min-height: 38px;
    max-width: 100%;
    display: flex;
    align-items: center;
    border-color: #d9dee3;
    border-radius: 6px;
    box-sizing: border-box;
  }

  .assign-artist-select .select2-selection--single .select2-selection__rendered {
    display: block;
    width: 100%;
    min-width: 0;
    padding-left: 14px;
    padding-right: 55px;
    line-height: 36px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    box-sizing: border-box;
  }

  .assign-artist-select .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: 6px;
  }

  /*
     * The dropdown is appended inside .assign-artist-select,
     * so keep every part within the wrapper width.
     */
  .assign-artist-select .select2-dropdown {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box;
  }

  .assign-artist-select .select2-search--dropdown {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
  }

  .assign-artist-select .select2-search--dropdown .select2-search__field {
    display: block;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0;
    box-sizing: border-box;
  }

  .assign-artist-select .select2-results {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
  }

  .assign-artist-select .select2-results__option {
    max-width: 100%;
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    box-sizing: border-box;
  }

  .assign-artist-button {
    flex: 0 0 auto;
    min-width: 145px;
    height: 38px;
    padding: 0 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    white-space: nowrap;
    border-radius: 6px;
    box-shadow: 0 3px 8px rgba(105, 108, 255, 0.22);
  }

  .assign-artist-button i {
    font-size: 17px;
  }

  @media (max-width: 767.98px) {
    .assign-artist-controls {
      flex-direction: column;
    }

    .assign-artist-select {
      flex: none;
      width: 100%;
    }

    .assign-artist-button {
      width: 100%;
    }
  }
</style>
@endpush
<div class="container-xxl">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
      Job Order Status –
      #ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}
    </h3>

    <a href="{{ route('artist.orders') }}" class="btn btn-light">
      <i class="bx bx-chevron-left"></i>
      Back to Orders
    </a>
  </div>

  {{-- Lead / company / headline info --}}
  <div class="card mb-4">
    <div class="card-header">
      Lead Information
    </div>

    <div class="card-body row g-3">
      <div class="col-md-6">
        <div class="small text-muted">Company Name</div>
        <div class="fw-medium">
          {{ $order->companyName ?? '-' }}
        </div>
      </div>

      <div class="col-md-3">
        <div class="small text-muted">Phone</div>
        <div class="fw-medium">
          {{ $order->leadPhone ?? '-' }}
        </div>
      </div>

      <div class="col-md-3">
        <div class="small text-muted">Email</div>
        <div class="fw-medium">
          {{ $order->leadEmail ?? '-' }}
        </div>
      </div>
    </div>
  </div>

  {{-- Job details --}}
  <div class="card mb-4">
    <div class="card-header">
      Job Order Details
    </div>

    <div class="card-body row g-3">
      <div class="col-md-4">
        <div class="small text-muted">Job Title</div>
        <div class="fw-medium">
          {{ $order->orderTitle ?? '-' }}
        </div>
      </div>

      <div class="col-md-4">
        <div class="small text-muted">Created Date</div>
        <div class="fw-medium">
          {{ optional($order->created_at)->format('d/m/Y') ?? '-' }}
        </div>
      </div>

      <div class="col-md-4">
        <div class="small text-muted">Deadline</div>
        <div class="fw-medium">
          @if($order->deadline)
          {{ \Carbon\Carbon::parse($order->deadline)->format('d/m/Y') }}
          @else
          -
          @endif
        </div>
      </div>

      <div class="col-md-4">
        <div class="small text-muted">Created By (Salesperson)</div>
        <div class="fw-medium">
          {{ optional($order->salesperson)->name ?? '-' }}
        </div>
      </div>

      <div class="col-md-4">
        <div class="small text-muted">Current Status</div>

        <span class="badge bg-secondary">
          {{
            \Illuminate\Support\Str::of($order->orderStatus)
              ->replace('_', ' ')
              ->title()
          }}
        </span>
      </div>

      <div class="col-md-4">
        <div class="small text-muted">Current Artist</div>
        <div class="fw-medium">
          {{ optional($order->artist)->name ?? '— (not assigned)' }}
        </div>
      </div>
    </div>
  </div>

  {{-- Product details --}}
  <div class="card mb-4">
    <div class="card-header">
      Product Details
    </div>

    <div class="card-body">
      @php
      $products = $order->products ?? collect();
      @endphp

      @if($products->isEmpty())
      <div class="text-muted">
        No product items.
      </div>
      @else
      <div class="accordion" id="prodAcc">
        @foreach($products as $idx => $p)
        <div class="accordion-item">
          <h2 class="accordion-header" id="ph{{ $idx }}">
            <button
              class="accordion-button {{ $idx ? 'collapsed' : '' }}"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#pc{{ $idx }}"
              aria-expanded="{{ $idx ? 'false' : 'true' }}"
              aria-controls="pc{{ $idx }}">
              Product #{{ $p->ProductID }}
              —
              {{ $p->productName ?? '-' }}
            </button>
          </h2>

          <div
            id="pc{{ $idx }}"
            class="accordion-collapse collapse {{ $idx ? '' : 'show' }}"
            aria-labelledby="ph{{ $idx }}"
            data-bs-parent="#prodAcc">
            <div class="accordion-body">
              <div class="mb-2">
                <strong>Total Quantity:</strong>
                {{ $p->totalQuantity ?? '-' }}
                <br>

                <strong>Material:</strong>
                {{ $p->materialRemark ?? '-' }}
                <br>

                <strong>Remarks:</strong>
                {{ $p->productRemark ?? '-' }}
              </div>

              @php
              $breaks = $p->deliveryBreakdowns ?? collect();
              @endphp

              @if($breaks->count())
              <div class="table-responsive">
                <table class="table table-striped table-sm">
                  <thead>
                    <tr>
                      <th>Delivery Method</th>
                      <th>Quantity</th>
                      <th>Location</th>
                      <th>Date &amp; Time</th>
                    </tr>
                  </thead>

                  <tbody>
                    @foreach($breaks as $d)
                    <tr>
                      <td>
                        {{
                                  \Illuminate\Support\Str::of($d->method)
                                    ->replace('_', ' ')
                                    ->title()
                                }}
                      </td>

                      <td>
                        {{ $d->quantity ?? '-' }}
                      </td>

                      <td>
                        {{ $d->location ?? '-' }}
                      </td>

                      <td>
                        {{
                                  optional($d->when)->format('M d, Y h:i A')
                                  ?? '-'
                                }}
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @else
              <em>No delivery breakdowns for this product.</em>
              @endif
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @endif
    </div>
  </div>

  {{-- Assign artist --}}
  <div class="card">
    <div class="card-header">Assign Artist</div>

    <div class="card-body">
      <form
        method="POST"
        action="{{ route('artist.orders.assign.store', $order->id) }}">
        @csrf

        <div class="assign-artist-section">
          <label for="artistSelect" class="form-label">
            Artist <span class="text-danger">*</span>
          </label>

          <div class="assign-artist-controls">
            <div class="assign-artist-select">
              <select
                name="artist_id"
                id="artistSelect"
                class="form-select @error('artist_id') is-invalid @enderror"
                style="width: 100%;"
                required>
                <option value="">Search or select artist…</option>

                @foreach($artists as $artist)
                <option
                  value="{{ $artist->id }}"
                  data-name="{{ $artist->name }}"
                  data-role="{{ $artist->role }}"
                  data-email="{{ $artist->email }}"
                  @selected(
                  (string) old('artist_id', $order->artist_id)
                  === (string) $artist->id
                  )
                  >
                  {{ $artist->name }} ({{ $artist->role }})
                </option>
                @endforeach
              </select>
            </div>

            <button
              type="submit"
              class="btn btn-primary assign-artist-button">
              <i class="bx bx-user-check"></i>
              <span>Assign Artist</span>
            </button>
          </div>

          @error('artist_id')
          <div class="invalid-feedback d-block">
            {{ $message }}
          </div>
          @enderror

          <div class="form-text mt-1">
            Only active artists and head artists are available.
          </div>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {
    const $artistSelect = $('#artistSelect');

    if (
        !$artistSelect.length ||
        typeof $.fn.select2 !== 'function'
    ) {
        return;
    }

    function getArtistInformation(item) {
        const option = item.element;

        if (!option) {
            return {
                name: item.text || '',
                role: '',
                email: ''
            };
        }

        return {
            name: option.dataset.name || item.text || '',
            role: option.dataset.role || '',
            email: option.dataset.email || ''
        };
    }

    function formatArtistResult(item) {
        if (!item.id) {
            return item.text;
        }

        const artist = getArtistInformation(item);
        const $result = $('<div>', {
            class: 'py-1'
        });

        $('<div>', {
            class: 'fw-semibold',
            text: artist.role
                ? `${artist.name} (${artist.role})`
                : artist.name
        }).appendTo($result);

        $('<div>', {
            class: 'text-muted small mt-1',
            text: artist.email || 'No email available'
        }).appendTo($result);

        return $result;
    }

    function formatArtistSelection(item) {
        if (!item.id) {
            return item.text;
        }

        const artist = getArtistInformation(item);

        const nameAndRole = artist.role
            ? `${artist.name} (${artist.role})`
            : artist.name;

        return artist.email
            ? `${nameAndRole} — ${artist.email}`
            : nameAndRole;
    }

    $artistSelect.select2({
        placeholder: 'Search artist...',
        allowClear: true,
        width: '100%',

        /*
         * Prevent Select2 from appending the dropdown to <body>.
         * It will now remain within the dropdown wrapper.
         */
        dropdownParent: $artistSelect.closest('.assign-artist-select'),

        templateResult: formatArtistResult,
        templateSelection: formatArtistSelection
    });
});
</script>
@endpush
@extends('layouts.app') {{-- or your layout --}}

@section('content')

@push('styles')
<style>
    .assign-artist-section {
        width: 100%;
        max-width: 900px;
        min-width: 0;
    }

    .assign-artist-controls {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        width: 100%;
        min-width: 0;
    }

    /*
     * width: 0 prevents long artist names or emails from
     * increasing the width of the Bootstrap flex row.
     */
    .assign-artist-select {
        position: relative;
        flex: 1 1 0;
        width: 0;
        min-width: 0;
        max-width: 100%;
    }

    .assign-artist-select > .select2-container {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
    }

    .assign-artist-select .select2-selection--single {
        height: 40px;
        min-height: 40px;
        max-width: 100%;
        display: flex;
        align-items: center;
        border: 1px solid #d9dee3;
        border-radius: 6px;
        box-sizing: border-box;
    }

    .assign-artist-select
    .select2-selection--single
    .select2-selection__rendered {
        display: block;
        width: 100%;
        min-width: 0;
        padding-left: 14px;
        padding-right: 54px;
        line-height: 38px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        box-sizing: border-box;
    }

    .assign-artist-select
    .select2-selection--single
    .select2-selection__arrow {
        height: 38px;
        right: 7px;
    }

    .assign-artist-select .select2-selection__clear {
        position: relative;
        z-index: 2;
        margin-right: 8px;
    }

    /*
     * The Select2 dropdown is attached to this wrapper instead
     * of the body, preventing horizontal page scrolling.
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

    .assign-artist-select
    .select2-search--dropdown
    .select2-search__field {
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

    .assign-artist-select .select2-results__options {
        overflow-x: hidden;
    }

    .assign-artist-select .select2-results__option {
        width: 100%;
        max-width: 100%;
        padding: 10px 12px;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
        box-sizing: border-box;
    }

    .artist-option-name {
        color: #374151;
        font-weight: 600;
        line-height: 1.35;
    }

    .artist-option-email {
        margin-top: 3px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.35;
    }

    .assign-artist-button {
        flex: 0 0 auto;
        min-width: 150px;
        height: 40px;
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
    <h3 class="mb-0">Job Order Status – #ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</h3>
    <a href="{{ route('boss.orders') }}" class="btn btn-light">
      <i class="bx bx-chevron-left"></i> Back to Orders
    </a>
  </div>

  {{-- Lead / company / headline info --}}
  <div class="card mb-4">
    <div class="card-header">Lead Information</div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <div class="small text-muted">Company Name</div>
        <div class="fw-medium">{{ $order->companyName ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Phone</div>
        <div class="fw-medium">{{ $order->leadPhone ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Email</div>
        <div class="fw-medium">{{ $order->leadEmail ?? '-' }}</div>
      </div>
    </div>
  </div>

  {{-- Job details --}}
  <div class="card mb-4">
    <div class="card-header">Job Order Details</div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <div class="small text-muted">Job Title</div>
        <div class="fw-medium">{{ $order->orderTitle ?? '-' }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Created Date</div>
        <div class="fw-medium">{{ optional($order->created_at)->format('d/m/Y') }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Deadline</div>
        <div class="fw-medium">{{ \Carbon\Carbon::parse($order->deadline)->format('d/m/Y') }}</div>
      </div>

      <div class="col-md-4">
        <div class="small text-muted">Created By (Salesperson)</div>
        <div class="fw-medium">{{ optional($order->salesperson)->name ?? '-' }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Current Status</div>
        <span class="badge bg-secondary">{{ \Illuminate\Support\Str::of($order->orderStatus)->replace('_',' ')->title() }}</span>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Artist</div>
        <div class="fw-medium">{{ optional($order->artist)->name ?? '— (not assigned)' }}</div>
      </div>
    </div>
  </div>

  {{-- Product details (Accordion for multiple products) --}}
  <div class="card mb-4">
    <div class="card-header">Product Details</div>
    <div class="card-body">
      @php $products = $order->products ?? collect(); @endphp

      @if($products->isEmpty())
        <div class="text-muted">No product items.</div>
      @else
        <div class="accordion" id="prodAcc">
          @foreach($order->products as $idx => $p)
            <div class="accordion-item">
              <h2 class="accordion-header" id="ph{{ $idx }}">
                <button class="accordion-button {{ $idx ? 'collapsed' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#pc{{ $idx }}"
                        aria-expanded="{{ $idx ? 'false' : 'true' }}" aria-controls="pc{{ $idx }}">
                  Product #{{ $p->ProductID }} — {{ $p->productName ?? '-' }}
                </button>
              </h2>
              <div id="pc{{ $idx }}" class="accordion-collapse collapse {{ $idx ? '' : 'show' }}"
                  aria-labelledby="ph{{ $idx }}" data-bs-parent="#prodAcc">
                <div class="accordion-body">
                  <div class="mb-2">
                    <strong>Total Quantity:</strong> {{ $p->totalQuantity ?? '-' }}<br>
                    <strong>Material:</strong> {{ $p->materialRemark ?? '-' }}<br>
                    <strong>Remarks:</strong> {{ $p->productRemark ?? '-' }}
                  </div>

                  @php $breaks = $p->deliveryBreakdowns; @endphp
                  @if($breaks->count())
                    <div class="table-responsive">
                      <table class="table table-striped table-sm">
                        <thead>
                          <tr>
                            <th>Delivery Method</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Date & Time</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($breaks as $d)
                            <tr>
                              <td>{{ $d->method }}</td>
                              <td>{{ $d->quantity }}</td>
                              <td>{{ $d->location }}</td>
                              <td>{{ optional($d->when)->format('M d, Y h:i A') }}</td>
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
    <div class="card-header">
      Assign Artist
    </div>

    <div class="card-body">
      <form
        method="POST"
        action="{{ route('boss.orders.assign.store', $order->id) }}"
      >
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
                required
              >
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
              class="btn btn-primary assign-artist-button"
            >
              <i class="bx bx-user-check"></i>
              <span>Assign Artist</span>
            </button>
          </div>

          @error('artist_id')
            <div class="invalid-feedback d-block mt-1">
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
    const $dropdownParent = $artistSelect.closest('.assign-artist-select');

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
            return $('<span>').text(item.text || '');
        }

        const artist = getArtistInformation(item);

        const displayName = artist.role
            ? `${artist.name} (${artist.role})`
            : artist.name;

        const $result = $('<div>', {
            class: 'artist-option'
        });

        $('<div>', {
            class: 'artist-option-name',
            text: displayName
        }).appendTo($result);

        $('<div>', {
            class: 'artist-option-email',
            text: artist.email || 'No email available'
        }).appendTo($result);

        return $result;
    }

    function formatArtistSelection(item) {
        if (!item.id) {
            return item.text || '';
        }

        const artist = getArtistInformation(item);

        const displayName = artist.role
            ? `${artist.name} (${artist.role})`
            : artist.name;

        return artist.email
            ? `${displayName} — ${artist.email}`
            : displayName;
    }

    $artistSelect.select2({
        placeholder: 'Search artist...',
        allowClear: true,
        width: '100%',
        dropdownParent: $dropdownParent,
        templateResult: formatArtistResult,
        templateSelection: formatArtistSelection,
        escapeMarkup: function (markup) {
            return markup;
        }
    });
});
</script>
@endpush
@extends('layouts.app')

@section('title', 'Job Order Details – ' . $order->order_number)

@section('content')
    <div class="container-xxl py-3">

        {{-- Header & Export --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="mb-0">
                Job Order Details –  {{ $order->order_number }}
            </h4>

            {{-- hook up to your existing export if available --}}
            <a href="{{ route('artist.orders.edit', $order->id) }}?export=pdf" class="btn btn-dark">
                <i class="bx bx-printer me-1"></i> Export PDF
            </a>
        </div>

        {{-- Job order information --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block mb-1">Job Title</small>
                        <div class="fw-medium">{{ $order->orderTitle ?? '-' }}</div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block mb-1">Created By</small>
                        <div class="fw-medium">{{ optional($order->salesperson)->name ?? '-' }}</div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block mb-1">Company Name</small>
                        <div class="fw-medium">{{ $order->companyName ?? '-' }}</div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block mb-1">Design Confirmation Required</small>
                        <div class="fw-medium">
                            {{ (int) data_get($order, 'approval') === 1 ? 'Yes' : 'No' }}
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block mb-1">Created Date</small>
                        <div class="fw-medium">{{ \Carbon\Carbon::parse($order->created_at)->format('Y-m-d') }}</div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block mb-1">Deadline</small>
                        <div class="fw-medium">
                            {{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('Y-m-d') : '-' }}
                        </div>
                    </div>

<div class="col-md-6 col-lg-6">
    <small class="text-muted d-block mb-1">Attachments from Lead</small>
    @if ($leadAttachments->isEmpty())
        <div class="fw-medium">-</div>
    @else
        <ul class="list-group list-group-flush">
            @foreach ($leadAttachments as $att)
                <li class="list-group-item d-flex align-items-center gap-2">
                    <i class="bx bx-paperclip"></i>
                    <span title="{{ $att['name'] }}">{{ Str::limit($att['name'], 20, '...') }}</span>
                    @if ($att['size'])
                        <small class="text-muted fs-6">{{ number_format($att['size'] / 1024, 0) }} KB</small>
                    @endif
                    <a class="btn btn-icon btn-sm btn-outline-secondary" href="{{ $att['url'] }}" download title="Download">
                        <i class="bx bx-download"></i>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Product & breakdown details --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>Product &amp; Breakdown Details</div>
            <div class="badge bg-secondary">
                {{ optional($order->artist)->name ? 'Artist: ' . $order->artist->name : 'Unassigned' }}
            </div>
        </div>

        <div class="card-body">

            {{-- Products accordion --}}
            @php $products = $order->products ?? collect(); @endphp

            @if ($products->isEmpty())
                <div class="text-muted">No products.</div>
            @else
                <div class="accordion" id="productsAcc">
                    @foreach ($products as $pi => $product)
                        @php
                            $pId = 'product_' . $pi;
                            $pOpen = $pi === 0 ? 'show' : '';
                            $items = data_get($product, 'items', collect());
                        @endphp

                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header" id="h_{{ $pId }}">
                                <button class="accordion-button {{ $pi ? 'collapsed' : '' }}" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#c_{{ $pId }}"
                                    aria-expanded="{{ $pi ? 'false' : 'true' }}" aria-controls="c_{{ $pId }}">
                                    <div class="w-100 d-flex flex-wrap gap-3">
                                        <div class="me-auto">
                                            <strong>Product</strong>
                                            <span class="text-muted">#{{ data_get($product, 'ProductID') }}</span>
                                            — {{ data_get($product, 'productName', '-') }}
                                        </div>
                                        <div>
                                            <small class="text-muted">Qty:</small>
                                            <span class="fw-semibold">{{ data_get($product, 'totalQuantity', '-') }}</span>
                                        </div>
                                        <div>
                                            <small class="text-muted">Material / Remark:</small>
                                            <span class="fw-semibold">{{ data_get($product, 'materialRemark', '-') }}</span>
                                        </div>
                                    </div>
                                </button>
                            </h2>

                            <div id="c_{{ $pId }}" class="accordion-collapse collapse {{ $pOpen }}"
                                aria-labelledby="h_{{ $pId }}" data-bs-parent="#productsAcc">
                                <div class="accordion-body">

                                    {{-- Items accordion (nested) --}}
                                    @if ($items && count($items))
                                        <div class="accordion" id="itemsAcc_{{ $pId }}">
                                            @foreach ($items as $ii => $item)
                                                @php
                                                    $iId = $pId . '_item_' . $ii;
                                                    $iOpen = $ii === 0 ? 'show' : '';
                                                    $materials = collect(data_get($item, 'material', []))
                                                        ->filter()
                                                        ->join(', ');
                                                @endphp

                                                <div class="accordion-item mb-2">
                                                    <h2 class="accordion-header" id="h_{{ $iId }}">
                                                        <button class="accordion-button {{ $ii ? 'collapsed' : '' }}"
                                                            type="button" data-bs-toggle="collapse"
                                                            data-bs-target="#c_{{ $iId }}"
                                                            aria-expanded="{{ $ii ? 'false' : 'true' }}"
                                                            aria-controls="c_{{ $iId }}">
                                                            <div class="w-100 d-flex flex-wrap gap-3">
                                                                <div class="me-auto">
                                                                    <strong>Item {{ $ii + 1 }}</strong>
                                                                    <span class="text-muted">—
                                                                        {{ data_get($item, 'itemName', '-') }}</span>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted">Qty/Item:</small>
                                                                    <span
                                                                        class="fw-semibold">{{ data_get($item, 'quantity', '-') }}</span>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted">Material:</small>
                                                                    <span
                                                                        class="fw-semibold">{{ $materials ?: '-' }}</span>
                                                                </div>
                                                            </div>
                                                        </button>
                                                    </h2>

                                                    <div id="c_{{ $iId }}"
                                                        class="accordion-collapse collapse {{ $iOpen }}"
                                                        aria-labelledby="h_{{ $iId }}"
                                                        data-bs-parent="#itemsAcc_{{ $pId }}">
                                                        <div class="accordion-body">

                                                            <div class="row gy-2">
                                                                <div class="col-md-6">
                                                                    <small class="text-muted d-block">Size (inches) –
                                                                        Width</small>
                                                                    <div class="fw-medium">
                                                                        {{ data_get($item, 'sizeWidth', '-') }}</div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <small class="text-muted d-block">Height</small>
                                                                    <div class="fw-medium">
                                                                        {{ data_get($item, 'sizeHeight', '-') }}</div>
                                                                </div>

                                                                <div class="col-md-3">
                                                                    <small class="text-muted d-block">Bleed (Top)</small>
                                                                    <div class="fw-medium">
                                                                        {{ data_get($item, 'bleedTop', '-') }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <small class="text-muted d-block">Bottom</small>
                                                                    <div class="fw-medium">
                                                                        {{ data_get($item, 'bleedBottom', '-') }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <small class="text-muted d-block">Left</small>
                                                                    <div class="fw-medium">
                                                                        {{ data_get($item, 'bleedLeft', '-') }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <small class="text-muted d-block">Right</small>
                                                                    <div class="fw-medium">
                                                                        {{ data_get($item, 'bleedRight', '-') }}</div>
                                                                </div>

                                                                @php
                                                                    $specification = $item->spec;
                                                                @endphp

                                                                <div class="col-md-3">
                                                                    <small class="text-muted d-block">Lamination</small>
                                                                    <div>{{ $specification->lamination ?? '-' }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <small class="text-muted d-block">Printer</small>
                                                                    <div>{{ $specification->printer ?? '-' }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <small class="text-muted d-block">Cutter</small>
                                                                    <div>{{ $specification->cutter ?? '-' }}</div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <small class="text-muted d-block">Finishing</small>
                                                                    <div class="fw-medium">
                                                                        {{ data_get($item, 'finishing', '-') }}</div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-muted">No items for this product.</div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Delivery Method Summary --}}
    <div class="card mb-4">
        <div class="card-header">Delivery Method Summary</div>
        <div class="card-body">
            @php $deliveries = $order->deliveryBreakdowns ?? collect(); @endphp

            @if ($deliveries->isEmpty())
                <div class="text-muted">No delivery breakdowns.</div>
            @else
                <div class="vstack gap-3">
                    @foreach ($deliveries as $d)
                        <div class="border rounded p-3">
                            <div class="fw-semibold mb-2">Delivery Method: {{ $d->method ?? '-' }}</div>
                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-3">
                                    <small class="text-muted d-block">Quantity</small>
                                    <div class="fw-medium">{{ $d->quantity ?? '-' }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <small class="text-muted d-block">Location</small>
                                    <div class="fw-medium">{{ $d->location ?? '-' }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-5">
                                    <small class="text-muted d-block">Date &amp; Time</small>
                                    <div class="fw-medium">
                                        @php
                                            $dateStr = trim(($d->date ?? '') . ' ' . ($d->time ?? ''));
                                            $display = '-';
                                            if ($dateStr !== '') {
                                                try {
                                                    $display = \Carbon\Carbon::parse($dateStr)->format('Y-m-d g:i A');
                                                } catch (\Throwable $e) {
                                                    $display = trim(($d->date ?? '') . ' ' . ($d->time ?? ''));
                                                }
                                            }
                                        @endphp
                                        {{ $display }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Product Remarks --}}
    @php
        // Adjust keys to your schema; these are examples if you store remarks per product or globally
        $remarks = collect([data_get($order, 'productRemark'), data_get($order, 'furnishingRemark')])->filter();
    @endphp

    @if ($remarks->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header">Product Remarks</div>
            <div class="card-body vstack gap-2">
                @foreach ($remarks as $r)
                    <div class="border rounded p-2">{{ $r }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Attachments --}}
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Attachments</span>
        </div>

        <div class="card-body">
            @if ($attachments->isEmpty())
                <p class="text-muted mb-0">No attachments.</p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach ($attachments as $f)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-paperclip"></i>
                                <span>{{ $f['name'] }}</span>
                                @if (!empty($f['size']))
                                    <small class="text-muted">
                                        {{ number_format($f['size'] / 1024, 0) }} KB
                                    </small>
                                @endif
                            </div>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ $f['url'] }}" download>
                                Download
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

<div class="text-end">
    <button type="button" class="btn btn-secondary mt-3" onclick="history.back()">Close</button>
</div>

    </div>
@endsection

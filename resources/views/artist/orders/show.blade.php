@extends('layouts.app')

@section('title', 'Job Order Details – '.str_pad($order->order_number, 4, '0', STR_PAD_LEFT))

@section('content')
@php
    // Map method code -> label
    function dd_method_label($v) {
        $v = strtolower((string) $v);
        return [
            'courier'               => 'Courier',
            'self_pickup'           => 'Self Pickup',
            'pickup'                => 'Pickup',
            'installation'          => 'Installation',
            'delivery_installation' => 'Delivery & Installation',
        ][$v] ?? ucfirst($v ?: '-');
    }

    // Nice date time from separate date+time columns
    function dd_datetime(?string $d, ?string $t) {
        if (!$d && !$t) return '—';
        try {
            if ($d && $t)   return \Carbon\Carbon::parse("$d $t")->format('M d, Y · h:i A');
            if ($d)         return \Carbon\Carbon::parse($d)->format('M d, Y');
            return \Carbon\Carbon::parse($t)->format('h:i A');
        } catch (\Throwable $e) { return trim(($d ?: '').' '.$t) ?: '—'; }
    }

    // Yes/No from tinyint/nullable
    function yn($v) { return ((int)$v) === 1 ? 'Yes' : 'No'; }
@endphp
<div class="container-xxl py-3">

    {{-- Header & Export --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">
            @if($order->redo && $order->relationLoaded('originalOrder') || $order->redo)
                @php
                $order->loadMissing('originalOrder:id,order_number');
                @endphp
                Job Order Details – {{ optional($order->originalOrder)->order_number ? '#'.ltrim($order->originalOrder->order_number,'#').'R' : ('#ORD-'.str_pad($order->redo,4,'0',STR_PAD_LEFT).'R') }}
            @endif
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
                        {{ (int) data_get($order,'approval') === 1 ? 'Yes' : 'No' }}
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
                    <small class="text-muted d-block mb-1">Attachment from Lead</small>

                    @php
                        $leadFiles = \App\Models\LeadAttachment::where('lead_id', $order->lead_id)
                            ->latest()->get();

                    @endphp

                    <div class="fw-medium">
                        @if($leadFiles->isNotEmpty())
                            @foreach ($leadFiles as $att)
                                <a href="{{ asset('storage/' . ltrim($att->file_location, '/')) }}"
                                target="_blank"
                                class="d-inline-flex align-items-center text-decoration-underline me-3 mb-1">
                                    {{ basename($att->file_location) }}
                                    <i class="bx bx-download ms-1"></i>
                                </a>
                            @endforeach
                        @else
                            -
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
                {{ optional($order->artist)->name ? 'Artist: '. $order->artist->name : 'Unassigned' }}
            </div>
        </div>

        <div class="card-body">

            {{-- Products accordion --}}
            @php $products = $order->products ?? collect(); @endphp

            @if($products->isEmpty())
            <div class="text-muted">No products.</div>
            @else
            <div class="accordion" id="productsAcc">
                @foreach($products as $pi => $product)
                @php
                $pId = 'product_'.$pi;
                $pOpen = $pi === 0 ? 'show' : '';
                $items = data_get($product, 'items', collect());
                
                $isRedoSelected = !empty($product->editable);
                $origPid   = (int) ($product->redoOf ?: $product->ProductID);
                $pidPadded = sprintf('%04d', $origPid);
                $pidLabel  = '#'.$pidPadded . (($product->redoOf && (int)$product->editable === 1) ? 'R' : '');
                @endphp

                <div class="accordion-item mb-2">
                    <h2 class="accordion-header" id="h_{{ $pId }}">
                        <button class="accordion-button {{ $pi ? 'collapsed' : '' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#c_{{ $pId }}"
                            aria-expanded="{{ $pi ? 'false':'true' }}" aria-controls="c_{{ $pId }}">
                            <div class="w-100 d-flex flex-wrap gap-3">
                                <div class="me-auto">
                                    <strong>Product</strong>
                                    <span class="fw-semibold">{{ $pidLabel }} — {{ data_get($product,'productName','-') }}</span>
                                </div>
                                <div>
                                    <small class="text-muted">Qty:</small>
                                    <span class="fw-semibold">{{ data_get($product,'totalQuantity','-') }}</span>
                                </div>
                                <div>
                                    <small class="text-muted">Material / Remark:</small>
                                    <span class="fw-semibold">{{ data_get($product,'materialRemark','-') }}</span>
                                </div>
                            </div>
                        </button>
                    </h2>

                    <div id="c_{{ $pId }}" class="accordion-collapse collapse {{ $pOpen }}" aria-labelledby="h_{{ $pId }}" data-bs-parent="#productsAcc">
                        <div class="accordion-body">

                            {{-- Items accordion (nested) --}}
                            @if($items && count($items))
                            <div class="accordion" id="itemsAcc_{{ $pId }}">
                                @foreach($items as $ii => $item)
                                @php
                                    $iId = $pId.'_item_'.$ii;
                                    $iOpen = $ii === 0 ? 'show' : '';
                                    $materialsCol = collect(data_get($item, 'material', []));
                                    if (is_string($materialsCol)) {
                                        $decoded = json_decode($materialsCol, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            $materialsCol = collect($decoded);
                                        } else {
                                            $materialsCol = collect(array_map('trim', explode(',', $materialsCol)));
                                        }
                                    }
                                    $materials = $materialsCol->filter()->join(', ');

                                    $szUnit = $item->sizeUnit ?: 'mm';
                                    $blUnit = $item->bleedUnit ?: ($item->sizeUnit ?: 'mm');

                                    // Size numbers (strip trailing zeros)
                                    $w = $item->sizeWidth  !== null ? rtrim(rtrim((string)$item->sizeWidth , '0'), '.') : '–';
                                    $h = $item->sizeHeight !== null ? rtrim(rtrim((string)$item->sizeHeight, '0'), '.') : '–';

                                    // Bleed numbers (T,R,B,L)
                                    $bt = $item->bleedTop    !== null ? rtrim(rtrim((string)$item->bleedTop   , '0'), '.') : '0';
                                    $br = $item->bleedRight  !== null ? rtrim(rtrim((string)$item->bleedRight , '0'), '.') : '0';
                                    $bb = $item->bleedBottom !== null ? rtrim(rtrim((string)$item->bleedBottom, '0'), '.') : '0';
                                    $bl = $item->bleedLeft   !== null ? rtrim(rtrim((string)$item->bleedLeft  , '0'), '.') : '0';

                                    $primeCentre = ((int) data_get($item, 'prime_centre')) === 1 ? 'Yes' : 'No';
                                @endphp

                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header" id="h_{{ $iId }}">
                                        <button class="accordion-button {{ $ii ? 'collapsed':'' }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#c_{{ $iId }}"
                                            aria-expanded="{{ $ii ? 'false':'true' }}" aria-controls="c_{{ $iId }}">
                                            <div class="w-100 d-flex flex-wrap gap-3">
                                                <div class="me-auto">
                                                    <strong>Item {{ $ii+1 }}</strong>
                                                    <span class="text-muted">— {{ data_get($item,'itemName','-') }}</span>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Qty/Item:</small>
                                                    <span class="fw-semibold">{{ data_get($item,'quantity','-') }}</span>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Material:</small>
                                                    <span class="fw-semibold">{{ $materials ?: '-' }}</span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>

                                    <div id="c_{{ $iId }}" class="accordion-collapse collapse {{ $iOpen }}" aria-labelledby="h_{{ $iId }}" data-bs-parent="#itemsAcc_{{ $pId }}">
                                        <div class="accordion-body">

                                            <div class="row gy-2 mt-2">
                                                <div class="text-muted col-md-4">
                                                    <small class="text-muted d-block">Size</small>
                                                    <span class="text-body fw-semibold">{{ $w }} × {{ $h }} {{ $szUnit }}</span>
                                                </div>

                                                <div class="text-muted col-md-4">
                                                    <small class="text-muted d-block">Bleed (T • R • B • L)</small>
                                                    <span class="text-body fw-semibold">
                                                        {{ $bt }} {{ $blUnit }} • {{ $br }} {{ $blUnit }} • {{ $bb }} {{ $blUnit }} • {{ $bl }} {{ $blUnit }}
                                                    </span>
                                                </div>

                                                <div class="text-muted col-md-4 mb-2">
                                                    <small class="text-muted d-block">Prime Centre</small>
                                                    <span class="text-body fw-semibold">{{ $primeCentre }}</span>
                                                </div>

                                                @php
                                                    $specification = $item->spec;
                                                @endphp

                                                <div class="col-md-4">
                                                    <small class="text-muted d-block">Lamination</small>
                                                    <span class="text-body fw-semibold">{{ $specification->lamination ?? '-' }}</span>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted d-block">Printer</small>
                                                    <span class="text-body fw-semibold">{{ $specification->printer ?? '-' }}</span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <small class="text-muted d-block">Cutter</small>
                                                    <span class="text-body fw-semibold">{{ $specification->cutter ?? '-' }}</span>
                                                </div>
                                                <div class="col-12">
                                                    <small class="text-muted d-block">Assemble</small>
                                                    <span class="text-body fw-semibold">{{ data_get($item,'finishing','-') }}</span>
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

    {{-- Delivery Breakdown (grouped by product) --}}
    <div class="card mb-4">
    <div class="card-header">Delivery Method Summary</div>
    <div class="card-body">
        @php
        $products = $order->relationLoaded('products')
            ? $order->products
            : \App\Models\Product::with('deliveryBreakdowns')->where('OrderID', $order->id)->get();
        @endphp

        @forelse($products as $pIndex => $p)
        @php
            $isRedoOrder = !empty($order->redo);

            $origPid = $isRedoOrder && !empty($p->redoOf)
                ? (int) $p->redoOf
                : (int) $p->ProductID;

            $pidLabel = '#'.str_pad((string)$origPid, 4, '0', STR_PAD_LEFT);
            if ($isRedoOrder && (int)($p->editable ?? 0) === 1) {
                $pidLabel .= 'R';
            }
        @endphp
        <div class="bg-body-tertiary rounded-2 px-3 py-2 mb-3 fw-semibold">
            Product {{ $pidLabel }} — {{ data_get($product,'productName','-') }}
        </div>

        @php $deliveries = $p->deliveryBreakdowns ?? collect(); @endphp

        @forelse($deliveries as $d)
            @php
            $methodRaw = strtolower((string) $d->method);
            $methodLabel = match ($methodRaw) {
                'courier'               => 'Courier',
                'self_pickup', 'pickup' => 'Self Pickup',
                'installation'          => 'Installation',
                'delivery_installation' => 'Delivery & Installation',
                default                 => ucfirst((string) $d->method),
            };

            // Build a readable datetime from separate date/time columns
            $dt = null;
            $dateStr = trim((string) $d->date);
            $timeStr = trim((string) $d->time);
            try {
                if ($timeStr && preg_match('/\d{4}-\d{2}-\d{2}/', $timeStr)) {
                // time field already contains a full datetime
                $dt = \Carbon\Carbon::parse($timeStr);
                } elseif ($dateStr && $timeStr) {
                $dt = \Carbon\Carbon::parse($dateStr.' '.$timeStr);
                } elseif ($dateStr) {
                $dt = \Carbon\Carbon::parse($dateStr);
                } elseif ($timeStr) {
                $dt = \Carbon\Carbon::parse($timeStr);
                }
            } catch (\Throwable $e) {}
            @endphp

            <div class="border rounded p-3 mb-3">
            <div class="text-muted small">
                Delivery Method:
                <span class="text-body fw-semibold">{{ $methodLabel }}</span>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-sm-6 col-lg-3">
                <small class="text-muted d-block">Installation Type</small>
                <div class="fw-medium">{{ $d->deliver_install_type ?: '—' }}</div>
                </div>

                <div class="col-sm-6 col-lg-3">
                <small class="text-muted d-block">Outsource Cost (RM)</small>
                <div class="fw-medium">
                    {{ ($d->outsource_cost !== null && $d->outsource_cost !== '') ? number_format((float)$d->outsource_cost, 2) : '—' }}
                </div>
                </div>

                <div class="col-sm-6 col-lg-2">
                <small class="text-muted d-block">Quantity</small>
                <div class="fw-medium">{{ $d->quantity ?? '-' }}</div>
                </div>

                <div class="col-sm-6 col-lg-4">
                <small class="text-muted d-block">Location</small>
                <div class="fw-medium">{{ $d->location ?? '-' }}</div>
                </div>

                <div class="col-sm-6 col-lg-4">
                <small class="text-muted d-block">Date &amp; Time</small>
                <div class="fw-medium">{{ $dt ? $dt->format('M d, Y h:i A') : '—' }}</div>
                </div>
            </div>
            </div>
        @empty
            <div class="text-muted border rounded p-3 mb-4">No delivery breakdowns.</div>
        @endforelse
        @empty
        <div class="text-muted border rounded p-3 mb-3">No products.</div>
        @endforelse
    </div>
    </div>

    {{-- Product Remarks --}}
    @php
        // Get products with their remarks (works even if controller didn't eager load)
        $remarkProducts = method_exists($order, 'products')
            ? $order->products()->with(['remarks' => function ($q) { $q->orderBy('RemarkID'); }])
                    ->orderBy('ProductID')->get()
            : collect();

        // helper: display code for the product id with the "R" rule
        $productCode = function ($p) {
            $baseId = $p->redoOf ?: $p->ProductID;                   // show original id if redo
            $suffix = ($p->redoOf && (int)($p->editable ?? 0) === 1) // only selected redo gets R
                    ? 'R' : '';
            return 'Product #'.str_pad($baseId, 4, '0', STR_PAD_LEFT).$suffix;
        };

        // helper: human operation label
        $opLabel = function ($op) {
            $op = strtolower((string)$op);
            return match ($op) {
                'printing'               => 'Printing',
                'furnishing'             => 'Furnishing',
                'installation'           => 'Installation',
                'courier'                => 'Delivery',
                'self_pickup', 'pickup'  => 'Self Pickup',
                default                  => ($op !== '' ? ucfirst($op) : 'General'),
            };
        };
    @endphp

    <div class="card mb-4">
    <div class="card-header">Product Remarks</div>
    <div class="card-body">
        @forelse($remarkProducts as $p)
        <div class="mb-3">
            <div class="bg-body-tertiary rounded-2 px-3 py-2 mb-3 fw-semibold">
            {{ $productCode($p) }} — {{ data_get($product,'productName','-') }}
            </div>

            @if(($p->remarks ?? collect())->isEmpty())
            <div class="text-muted small ms-1">No remarks for this product.</div>
            @else
            <div class="vstack gap-2">
                @foreach($p->remarks as $rm)
                <div class="d-flex align-items-start gap-2 p-2 border rounded">
                    <span class="badge bg-secondary me-2">{{ $opLabel($rm->operation) }}</span>
                    <div class="flex-grow-1">{{ $rm->remark ?? '—' }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div class="text-muted">No product remarks.</div>
        @endforelse
    </div>
    </div>

    {{-- Attachments --}}
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Attachments</span>
        </div>

        <div class="card-body">
            @if($attachments->isEmpty())
            <p class="text-muted mb-0">No attachments.</p>
            @else
            <ul class="list-group list-group-flush">
                @foreach($attachments as $f)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-paperclip"></i>
                        <span>{{ $f['name'] }}</span>
                        @if(!empty($f['size']))
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

</div>
@endsection
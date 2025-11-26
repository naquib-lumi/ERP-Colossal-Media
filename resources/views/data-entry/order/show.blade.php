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
@php
// base order id for redo context
$baseOrderId = $order->redo ?: $order->id;

// latest redo record for this base order
$redoRecord = DB::table('report_redo')
->where('OrderID', $baseOrderId)
->orderByDesc('ReportID') // use correct PK
->first();

$redoBy = null;
if ($redoRecord && $redoRecord->user_id) {
$redoBy = \App\Models\User::find($redoRecord->user_id)?->name;
}
$redoReason = $redoRecord->reason ?? null;

// helper: whether a product is the selected redo copy
$isRedoOrder = (bool) $order->redo;
@endphp
<style>
    .reason-modal .modal-content{border:0;overflow:hidden}
    .reason-modal .modal-header{padding:14px 16px;color:#fff}
    .reason-modal .rm-chip{display:inline-flex;align-items:center;gap:.4rem;font-size:.75rem;font-weight:700;letter-spacing:.02em;padding:.25rem .6rem;border-radius:999px;background:#e9ecef;color:#212529}
    .reason-modal .rm-reason-box{border:1px solid rgba(0,0,0,.06);background:#fff;border-radius:.75rem;padding:14px}
    .reason-modal .rm-reason-text{white-space:pre-wrap;font-size:.95rem}
    .reason-modal.is-redo .modal-header{background:linear-gradient(135deg,#b00020 0%,#dc3545 60%,#ff6b6b 100%)}
    .reason-modal.is-redo .rm-chip{background:#ffe3e3;color:#b00020;border:1px solid #ffb3b3}
    .reason-modal.is-reject .modal-header{background:linear-gradient(135deg,#e74c3c 0%,#ff6b6b 60%,#ffa8a8 100%)}
    .reason-modal.is-reject .rm-chip{background:#ffe1e1;color:#8a0018;border:1px solid #ffb3b3}
    .reason-modal .btn-close-white{filter:brightness(0) invert(1);opacity:.85}
    .reason-modal .btn-close-white:hover{opacity:1}

    .btn-purple:hover {
        background: #5a4cd9 !important;
        transform: translateY(-1px);
    }

    .redo-banner {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        background: #dc3545;
        color: #fff;
        padding: .1rem .35rem;
        border-radius: 12px;
        box-shadow: 0 6px 14px rgba(220, 53, 69, .25);
        border: 1px solid #b02a37;
    }

    .redo-banner .icon {
        font-size: 1.1rem;
        line-height: 1;
    }

    .redo-banner .tag {
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .35);
        color: #fff;
        border-radius: 999px;
        padding: .1rem .55rem;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .redo-banner .by {
        font-weight: 700;
        padding: .15rem .45rem;
        background: rgba(255, 255, 255, .12);
        border-radius: 999px;
    }

    .redo-banner .reason {
        max-width: 420px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: .1rem .45rem;
        background: #fff;
        color: #b02a37;
        border-radius: 999px;
        border: 1px solid #f1aeb5;
    }

    .redo-offset {
        margin-left: .5rem;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="container-xxl py-3">

    {{-- Header & Export --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <a href="{{ route('data-entry.orders') }}"
            class="text-decoration-none text-muted me-3"
            style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="bi bi-arrow-left-circle fw-semibold"
                style="font-size: 1.4rem; font-weight: 600; color: #6c757d;"></i>
        </a>
        <h4 class="mb-0 flex-grow-1">
            @if($order->redo && $order->relationLoaded('originalOrder') || $order->redo)
            @php
            $order->loadMissing('originalOrder:id,order_number');
            @endphp
            Job Order Details – {{ optional($order->originalOrder)->order_number ? '#'.ltrim($order->originalOrder->order_number,'#').'R' : ('#ORD-'.str_pad($order->redo,4,'0',STR_PAD_LEFT).'R') }}
            @else
            Job Order Details – {{ $order->order_number }}
            @endif
        </h4>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('data-entry.orders.edit', $order->id) }}"
                class="btn d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm"
                style="background:#6C5CE7; border:none; color:white; border-radius:8px;">
                <i class="bx bx-edit-alt fs-5"></i>
                <span>Edit Order</span>
            </a>
        </div>
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

                <!-- <div class="col-md-6 col-lg-6">
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
                </div> -->
            </div>
            {{-- ===== Non-artist attachments (Sales etc.) at the top ===== --}}
            @if(isset($headerAttachments) && $headerAttachments->count())
            <hr class="my-4">

            <h6 class="fw-semibold mb-2">Sales Attachments</h6>

            <div class="d-flex flex-column gap-2">
                @foreach($headerAttachments as $f)
                <div class="d-flex align-items-center justify-content-between border rounded p-2">
                    <div class="d-flex flex-column">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-file"></i>
                        <span class="fw-medium">{{ $f['name'] }}</span>
                    </div>

                    <div class="small text-muted mt-1" style="color:#6c757d; font-size:12px">
                        @if(!empty($f['ext']))
                        .{{ $f['ext'] }}
                        @endif

                        @if(!empty($f['size']))
                        · {{ number_format($f['size'] / 1024, 0) }} KB
                        @endif

                        @if(!empty($f['uploaded_by']))
                        · Uploaded by {{ $f['uploaded_by'] }}
                        @endif

                        @if(!empty($f['uploaded_at']))
                        · {{ $f['uploaded_at'] }}
                        @endif
                    </div>
                    </div>

                    <a href="{{ $f['url'] }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                    Download
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Product & breakdown details --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>Product &amp; Breakdown Details</div>
            <div>
                <div class="d-flex gap-2">
                    {{-- Artist badge --}}
                    @if(!empty($order->artist_id) && !empty($order->artist))
                    <span class="badge bg-secondary">
                        {{ 'Artist: ' . $order->artist->name }}
                    </span>
                    @else
                    <span class="badge bg-secondary">Unassigned</span>
                    @endif

                    {{-- Data Entry badge --}}
                    @if(!empty($order->data_entry_id) && !empty($order->dataEntry))
                    <span class="badge fw-semibold px-3 py-2" style="background:#E0F7FF; color:#00AEEF;">
                        {{ 'Data Entry: ' . $order->dataEntry->name }}
                    </span>
                    @endif
                </div>
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

                $isRejected = strtolower((string)($product->status ?? '')) === 'rejected';

                $rejectRecord = DB::table('report_redo')
                    ->where('OrderID', $order->id)
                    ->orderByDesc('ReportID')
                    ->first();

                $rejectBy = null;
                if ($rejectRecord && $rejectRecord->user_id) {
                    $rejectBy = \App\Models\User::find($rejectRecord->user_id)?->name;
                }
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
                                    @php
                                    $selectedForRedo = $isRedoOrder && (int)($product->editable ?? 0) === 1;
                                    @endphp

                                    {{-- existing REDO banner --}}
                                    @if($selectedForRedo)
                                        <span class="redo-banner redo-offset ms-2 js-reason-banner cursor-pointer"
                                            data-type="REDO"
                                            data-reason="{{ $redoRecord->reason ?? '' }}"
                                            data-by="{{ $redoBy ?? '' }}">
                                        <i class="bi bi-exclamation-octagon-fill icon"></i>
                                        <span class="tag" style="font-size:12px;">REDO</span>
                                        @if(!empty($redoRecord->reason))
                                            <span style="font-size:12px;" class="reason">{{ Str::limit($redoRecord->reason, 90) }}</span>
                                        @endif
                                        @if($redoBy)
                                            <span class="by" style="font-size:12px;">by {{ $redoBy }}</span>
                                        @endif
                                        </span>
                                    @endif

                                    @if((int)($product->editable ?? 0) === 1 && strtolower((string)($product->status ?? '')) === 'rejected')
                                        <span class="redo-banner redo-offset" style="background-color:rgb(255, 62, 29); border-color:#b02a37 !important;"
                                            title="{{ $rejectRecord->reason ?? '' }}">
                                            <i class="bi bi-x-octagon-fill icon"></i>
                                            <span class="tag" style="font-size: 10px;">REJECTED</span>
                                            @if(!empty($rejectRecord?->reason))
                                                <span style="font-size: 12px;" class="reason"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="{{ $rejectRecord->reason }}">
                                                    {{ \Illuminate\Support\Str::limit($rejectRecord->reason, 90) }}
                                                </span>
                                            @endif
                                            @if($rejectBy)
                                                <span class="by" style="font-size: 10px;">by {{ $rejectBy }}</span>
                                            @endif
                                        </span>
                                    @endif
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

            // latest reject/redo for THIS order (order-level)
            $orderReject = DB::table('report_redo')
                ->where('OrderID', $order->id)
                ->orderByDesc('ReportID')
                ->first();

            $orderRejectBy = null;
            if ($orderReject && $orderReject->user_id) {
                $orderRejectBy = \App\Models\User::find($orderReject->user_id)?->name;
            }

            // if this is a redo order we still need that too (you already had this above)
            $isRedoOrder = (bool) $order->redo;
        @endphp

        @forelse($products as $pIndex => $p)
        @php
            $origPid = $isRedoOrder && !empty($p->redoOf)
            ? (int) $p->redoOf
            : (int) $p->ProductID;

            $pidLabel = '#'.str_pad((string)$origPid, 4, '0', STR_PAD_LEFT);

            // show R if redo & selected
            $selectedForRedo = $isRedoOrder && (int)($p->editable ?? 0) === 1;
            if ($selectedForRedo) {
            $pidLabel .= 'R';
            }

            // ✅ product-level rejected detection
            $isRejectedSelected = (int)($p->editable ?? 0) === 1
                                && strtolower((string)$p->status) === 'rejected';
        @endphp
        <div class="bg-body-tertiary rounded-2 px-3 py-2 mb-3 fw-semibold">
            Product {{ $pidLabel }} — {{ data_get($product,'productName','-') }}

            {{-- existing REDO banner --}}
            @if($selectedForRedo)
                <span class="redo-banner redo-offset ms-2 js-reason-banner cursor-pointer"
                    data-type="REDO"
                    data-reason="{{ $redoRecord->reason ?? '' }}"
                    data-by="{{ $redoBy ?? '' }}">
                <i class="bi bi-exclamation-octagon-fill icon"></i>
                <span class="tag" style="font-size:12px;">REDO</span>
                @if(!empty($redoRecord->reason))
                    <span style="font-size:12px;" class="reason">{{ Str::limit($redoRecord->reason, 90) }}</span>
                @endif
                @if($redoBy)
                    <span class="by" style="font-size:12px;">by {{ $redoBy }}</span>
                @endif
                </span>
            @endif

            {{-- ✅ NEW: REJECTED banner (order-level text, product-level flag) --}}
            @if($isRejectedSelected)
            <span class="redo-banner redo-offset bg-danger text-white"
                    title="{{ $orderReject->reason ?? '' }}">
                <i class="bi bi-x-octagon-fill icon"></i>
                <span class="tag" style="font-size: 10px;">REJECTED</span>
                @if($orderRejectBy)
                <span class="by" style="font-size: 10px;">by {{ $orderRejectBy }}</span>
                @endif
                @if(!empty($orderReject?->reason))
                <span style="font-size: 12px;" class="reason" data-bs-toggle="tooltip" data-bs-placement="top"
                        title="{{ $orderReject->reason }}">{{ \Illuminate\Support\Str::limit($orderReject->reason, 90) }}</span>
                @endif
            </span>
            @endif
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
    // Pull products + remarks (+ remark user) even if the controller didn't eager-load them
    $remarkProducts = method_exists($order, 'products')
    ? $order->products()
    ->with([
    'remarks' => fn ($q) => $q->orderBy('RemarkID'),
    'remarks.user:id,name', // <- creator
        ])
        ->orderBy('ProductID')
        ->get()
        : collect();

        // Product code like "#...-P0001R" logic (R only for selected redo)
        $productCode = function ($p) {
        $baseId = $p->redoOf ?: $p->ProductID;
        $suffix = ($p->redoOf && (int)($p->editable ?? 0) === 1) ? 'R' : '';
        return 'Product #'.str_pad($baseId, 4, '0', STR_PAD_LEFT).$suffix;
        };

        // Display label for each operation
        $opLabel = function (?string $op) {
        $op = strtolower((string)$op);
        return match ($op) {
        'printing' => 'Printing',
        'furnishing' => 'Furnishing',
        'installation' => 'Installation',
        'courier' => 'Delivery',
        'self_pickup', 'pickup' => 'Self Pickup',
        'artist' => 'Artist',
        default => ($op !== '' ? ucfirst($op) : 'General'),
        };
        };

        // Colors (same set you asked for in Fulfillment)
        $opStyle = function (?string $op) {
        $op = strtolower((string)$op);
        return match ($op) {
        'printing' => ['#EEF2FF', '#4F46E5'],
        'furnishing' => ['#FFF7ED', '#C2410C'],
        'installation' => ['#ECFEFF', '#0E7490'],
        'courier' => ['#ECFDF5', '#047857'],
        'self_pickup' => ['#F3F4F6', '#111827'],
        'artist' => ['#eaecf9ff', '#8295fdff'],
        default => ['#F3F4F6', '#111827'],
        };
        };
        @endphp

    <div class="card mb-4">
    <div class="card-header">Product Remarks</div>
    <div class="card-body">
        @php
        $orderReject = DB::table('report_redo')
            ->where('OrderID', $order->id)
            ->orderByDesc('ReportID')
            ->first();

        $orderRejectBy = null;
        if ($orderReject && $orderReject->user_id) {
            $orderRejectBy = \App\Models\User::find($orderReject->user_id)?->name;
        }

        $isRedoOrder = (bool) $order->redo;
        @endphp

        @forelse($remarkProducts as $p)
        @php
            $selectedForRedo = $isRedoOrder && (int)($p->editable ?? 0) === 1;

            // ✅ product-level reject flag
            $isRejectedSelected = (int)($p->editable ?? 0) === 1
                                && strtolower((string)$p->status) === 'rejected';

            $productCode = function($prod) {
            $orig = $prod->redoOf ?: $prod->ProductID;
            return '#'.str_pad($orig, 4, '0', STR_PAD_LEFT).(
                $prod->redoOf && (int)($prod->editable ?? 0) === 1 ? 'R' : ''
            );
            };
        @endphp

        <div class="mb-3">
            <div class="bg-body-tertiary rounded-2 px-3 py-2 mb-3 fw-semibold">
            {{ $productCode($p) }} — {{ data_get($product,'productName','-') }}

            {{-- existing REDO banner --}}
            @if($selectedForRedo)
                <span class="redo-banner redo-offset ms-2 js-reason-banner cursor-pointer"
                    data-type="REDO"
                    data-reason="{{ $redoRecord->reason ?? '' }}"
                    data-by="{{ $redoBy ?? '' }}">
                <i class="bi bi-exclamation-octagon-fill icon"></i>
                <span class="tag" style="font-size:12px;">REDO</span>
                @if(!empty($redoRecord->reason))
                    <span style="font-size:12px;" class="reason">{{ Str::limit($redoRecord->reason, 90) }}</span>
                @endif
                @if($redoBy)
                    <span class="by" style="font-size:12px;">by {{ $redoBy }}</span>
                @endif
                </span>
            @endif

            {{-- ✅ NEW: REJECTED banner --}}
            @if($isRejectedSelected)
                <span class="redo-banner redo-offset bg-danger text-white"
                    title="{{ $orderReject->reason ?? '' }}">
                <i class="bi bi-x-octagon-fill icon"></i>
                <span class="tag" style="font-size: 10px;">REJECTED</span>
                @if($orderRejectBy)
                    <span class="by" style="font-size: 10px;">by {{ $orderRejectBy }}</span>
                @endif
                @if(!empty($orderReject?->reason))
                    <span style="font-size: 12px;" class="reason"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="{{ $orderReject->reason }}">
                    {{ \Illuminate\Support\Str::limit($orderReject->reason, 90) }}
                    </span>
                @endif
                </span>
            @endif
            </div>

            @if(($p->remarks ?? collect())->isEmpty())
            <div class="text-muted small ms-1">No remarks for this product.</div>
            @else
            <div class="vstack gap-2">
                @foreach($p->remarks as $rm)
                @php [$bg,$fg] = $opStyle($rm->operation); @endphp
                <div class="border rounded-2 p-3 d-flex flex-column gap-1">
                    <div class="d-flex align-items-center gap-3">
                    <span class="px-2 py-1 rounded-pill fw-semibold flex-shrink-0"
                            style="background:{{ $bg }}; color:{{ $fg }}; font-size:.8rem; min-width:max-content;">
                        {{ $opLabel($rm->operation) }}
                    </span>
                    <div class="flex-grow-1">
                        <div class="mb-0" style="line-height:1.5;">
                        {{ $rm->remark ?: '—' }}
                        </div>
                    </div>
                    </div>
                    <div class="text-muted small ms-1" style="font-weight: 700;">
                    by {{ optional($rm->user)->name ?? '—' }}
                    </div>
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
                <span>Artist Attachments</span>
            </div>

            <div class="card-body">
                @if($attachments->isEmpty())
                    <p class="text-muted mb-0">No attachments.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($attachments as $f)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex flex-column">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bx bx-paperclip"></i>
                                        <span>{{ $f['name'] }}</span>
                                    </div>

                                    <div class="small text-muted mt-1" style="color:#6c757d; font-size:12px">
                                        @if(!empty($f['ext']))
                                            .{{ $f['ext'] }}
                                        @endif

                                        @if(!empty($f['size']))
                                            · {{ number_format($f['size'] / 1024, 0) }} KB
                                        @endif

                                        @if(!empty($f['uploaded_by']))
                                            · Uploaded by {{ $f['uploaded_by'] }}
                                        @endif

                                        @if(!empty($f['uploaded_at']))
                                            · {{ $f['uploaded_at'] }}
                                        @endif
                                    </div>
                                </div>

                                <a class="btn btn-sm btn-outline-secondary" href="{{ $f['url'] }}" target="_blank">
                                    Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    <div class="d-flex justify-content-end mt-4">
        <a href="{{ route('data-entry.orders.edit', $order->id) }}"
            class="btn d-flex align-items-center gap-2 px-4 py-2 fw-semibold shadow-sm"
            style="background:#6C5CE7; border:none; color:white; border-radius:8px;">
            <i class="bx bx-edit-alt fs-5"></i>
            <span>Edit Order</span>
        </a>
    </div>
</div>

{{-- Reason Modal --}}
<div class="modal fade reason-modal" id="reasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <div class="d-flex align-items-center gap-2">
          <span class="rm-chip" id="reasonChip">
            <i class="bi" id="reasonIcon" aria-hidden="true"></i>
            <span id="reasonTag">REDO</span>
          </span>
          <h5 class="modal-title mb-0" id="reasonModalTitle" style="color: white;">Detail</h5>
        </div>
      </div>

      <div class="modal-body pt-0">
        <div id="reasonBy" class="text-muted small mb-2" style="font-weight: bold; margin-top:20px;"></div>
        <div class="rm-reason-box">
          <div class="fw-medium text-muted small mb-1" style="font-weight: bold;">Reason</div>
          <div id="reasonText" class="rm-reason-text"></div>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@push('scripts')
<script>
    // enable Bootstrap tooltips if not already
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    });

    (function(){
  document.addEventListener('click', function (e) {
    const el = e.target.closest('.js-reason-banner');
    if (!el) return;

    e.preventDefault();
    e.stopPropagation();

    const type   = (el.getAttribute('data-type') || '').toUpperCase(); // REDO | REJECTED
    const reason = (el.getAttribute('data-reason') || '').trim();
    const by     = (el.getAttribute('data-by') || '').trim();

    const modal  = document.getElementById('reasonModal');
    const title  = document.getElementById('reasonModalTitle');
    const byEl   = document.getElementById('reasonBy');
    const textEl = document.getElementById('reasonText');
    const tag    = document.getElementById('reasonTag');
    const icon   = document.getElementById('reasonIcon');

    // theme classes
    modal.classList.remove('is-redo','is-reject');

    if (type === 'REDO') {
      modal.classList.add('is-redo');
      title.textContent = 'Redo Detail';
      tag.textContent = 'REDO';
      icon.className = 'bi bi-exclamation-octagon-fill';
    } else {
      modal.classList.add('is-reject');
      title.textContent = 'Rejected Detail';
      tag.textContent = 'REJECTED';
      icon.className = 'bi bi-x-octagon-fill';
    }

    byEl.textContent = by ? `By ${by}` : '';
    textEl.textContent = reason || '(No reason provided)';

    bootstrap.Modal.getOrCreateInstance(modal).show();
  });

  // Optional: keyboard "Enter" on focused banner
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    const a = document.activeElement;
    if (a && a.classList.contains('js-reason-banner')) a.click();
  });
})();
</script>
@endpush
@endsection
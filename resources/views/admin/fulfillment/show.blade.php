{{-- resources/views/admin/fulfillment/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Product Details')

@push('styles')
<style>
  .mini-card{border:1px solid #edf0f4;border-radius:.75rem;padding:.9rem 1rem;background:#fff}
  .badge-soft{border-radius:999px;padding:.28rem .6rem;font-weight:600;font-size:.74rem}
  .badge-completed{background:#ecfdf5;color:#047857}
  .badge-progress{background:#eef2ff;color:#4338ca}
  .badge-pending{background:#fff7ed;color:#b45309}
  .badge-rejected{background:#fef2f2;color:#b91c1c}
  .key{color:#6b7280;font-size:.85rem}
  .file-row{border:1px solid #edf0f4;border-radius:.75rem;padding:.75rem 1rem;background:#fff}
  .remark-pill{border:1px solid #edf0f4;border-radius:.6rem;padding:.6rem .8rem;background:#fff}
  .remark-pill .op{font-weight:600;margin-right:.25rem}
  .remark-pill .txt{color:#111827}
  .remarks-list .list-group-item{border:1px solid #eef0f3;border-radius:12px!important;padding:.85rem 1rem;margin-bottom:.5rem}
  .remarks-op{min-width:210px;text-align:center;font-weight:600;border-radius:999px;padding:.4rem .75rem}
  .remarks-op.printing{background:#EEF2FF;color:#4F46E5}
  .remarks-op.furnishing{background:#FFF7ED;color:#C2410C}
  .remarks-op.installation{background:#ECFEFF;color:#0E7490}
  .remarks-op.courier{background:#ECFDF5;color:#047857}
  .remarks-op.self_pickup{background:#F3F4F6;color:#111827}
  .remarks-op.artist{background:#f9fae2;color:#b58900}
  .remarks-text{line-height:1.4}
</style>
@endpush

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

@php
  // ---- 基础对象化 ----
  $ord = is_array($order ?? null) ? (object)$order : ($order ?? (object)[]);

  // ---- 标题用的编码 ----
  $productCode = $productCode
    ?? ($ord->product_code ?? $ord->order_number ?? ('ORD-' . ($ord->id ?? '')));

  // ---- 进度卡片：若外部没传 $progress，则根据 orderStatus 给一个兜底 ----
  $progress = $progress ?? [];
  $statusToStage = [
    'completed'   => ['printing'=>'completed','furnishing'=>'completed','delivery'=>'completed','installation'=>'completed'],
    'in_progress' => ['printing'=>'completed','furnishing'=>'in_progress','delivery'=>'pending','installation'=>'pending'],
    'pending'     => ['printing'=>'pending','furnishing'=>'pending','delivery'=>'pending','installation'=>'pending'],
    'rejected'    => ['printing'=>'rejected','furnishing'=>'rejected','delivery'=>'rejected','installation'=>'rejected'],
    'to_assign'   => ['printing'=>'pending','furnishing'=>'pending','delivery'=>'pending','installation'=>'pending'],
  ];
  $os = strtolower((string)($ord->orderStatus ?? 'pending'));
  if (empty($progress) && isset($statusToStage[$os])) {
    foreach ($statusToStage[$os] as $k=>$v){
      $progress[$k] = ['status'=>$v,'accepted_at'=>null,'completed_at'=>null,'duration'=>null];
    }
  }

  // ---- 规范 lead 附件：允许数组/对象混用 ----
  $leadAttachments = collect($leadAttachments ?? [])->map(function($x){
    $o = is_array($x) ? (object)$x : $x;
    return (object)[
      'url'  => $o->url  ?? ($o->link ?? '#'),
      'name' => $o->name ?? ($o->filename ?? 'file'),
      'size' => $o->size ?? null,
    ];
  });

  // ---- 从 $order 整理一个 $product（模板期望的结构）----
  $product = (object)[
    'productName'    => $ord->orderTitle ?? ($ord->product_name ?? '-'),
    'totalQuantity'  => (int) (collect($ord->products ?? [])->sum(function($p){
                          $po = is_array($p)?(object)$p:$p; return (int)($po->Quantity ?? $po->quantity ?? 0);
                        })),
    'materialRemark' => $ord->material_remark ?? $ord->remark ?? $ord->description ?? null,
    'items'          => collect($ord->products ?? [])->map(function($p){
                          $po = is_array($p)?(object)$p:$p;
                          return (object)[
                            'itemName'  => $po->ProductName ?? $po->name ?? ($po->product_name ?? 'Item'),
                            'quantity'  => (int)($po->Quantity ?? $po->quantity ?? 0),
                            'sizeWidth' => $po->SizeWidth ?? $po->size_width ?? null,
                            'sizeHeight'=> $po->SizeHeight ?? $po->size_height ?? null,
                            'sizeUnit'  => $po->SizeUnit ?? $po->size_unit ?? '',
                            // bleed 优先取各自字段
                            'bleedTop'    => $po->BleedTop ?? $po->bleed_top ?? null,
                            'bleedRight'  => $po->BleedRight ?? $po->bleed_right ?? null,
                            'bleedBottom' => $po->BleedBottom ?? $po->bleed_bottom ?? null,
                            'bleedLeft'   => $po->BleedLeft ?? $po->bleed_left ?? null,
                            'bleedUnit'   => $po->BleedUnit ?? $po->bleed_unit ?? null,
                            'prime_centre'=> $po->prime_centre ?? 0,
                            // 可能是数组/JSON/集合
                            'material'   => $po->material ?? ($po->materials ?? null),
                            // 规格块（若你项目没有就为空）
                            'spec'       => (object)[
                              'lamination' => $po->lamination ?? null,
                              'printer'    => $po->printer ?? null,
                              'cutter'     => $po->cutter ?? null,
                            ],
                          ];
                        }),
    'remarks'        => collect($ord->remarks ?? []), // 若项目无此字段则为空列表
  ];

  // ---- 派送/安装摘要（若无字段则为空）----
  $deliveries = collect($deliveries ?? [])->when(($deliveries ?? null) === null, function($c) use ($ord){
    $method = $ord->delivery_installation_type ?? $ord->task_type ?? null;
    $date   = $ord->delivery_date ?? $ord->install_date ?? null;
    $loc    = $ord->delivery_address ?? $ord->shipping_address ?? null;
    $qty    = $product->totalQuantity ?? null;
    if ($method || $date || $loc || $qty){
      $c->push((object)[
        'method'=>$method, 'date'=>$date, 'location'=>$loc, 'quantity'=>$qty,
      ]);
    }
  });

  // ---- 订单附件（若上层未传，则为空）----
  $orderFiles = collect($orderFiles ?? [])->map(function($f){
    if (is_array($f)) return $f;
    $o = (object)$f;
    return ['name'=>$o->name ?? 'file', 'ext'=>$o->ext ?? '', 'url'=>$o->url ?? '#'];
  });

  // ---- 进度卡片辅助 ----
  $stageMeta = [
    'printing'     => ['title' => 'Printing',                 'icon' => 'bi-printer'],
    'furnishing'   => ['title' => 'Furnishing',               'icon' => 'bi-tools'],
    'delivery'     => ['title' => 'Dispatch Control',         'icon' => 'bi-truck'],
    'installation' => ['title' => 'Delivery & Installation',  'icon' => 'bi-wrench'],
  ];
  $stageOrder = ['printing','furnishing','delivery','installation'];
  $badgeFor = function ($status) {
    return match ($status) {
      'completed'   => 'badge-completed',
      'in_progress' => 'badge-progress',
      'rejected'    => 'badge-rejected',
      default       => 'badge-pending',
    };
  };
@endphp

<div class="container py-4">
  {{-- Header --}}
  <div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.fulfillment') }}"
       class="text-decoration-none text-muted me-3"
       style="display:inline-flex;align-items:center;gap:8px;">
      <i class="bi bi-arrow-left-circle fw-semibold"
         style="font-size:1.4rem;font-weight:600;color:#6c757d;"></i>
    </a>

    <h4 class="mb-0">
      Product Details – {{ $productCode }}
    </h4>
  </div>

  {{-- Progress cards --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        @foreach ($stageOrder as $stage)
          @php
            $p    = $progress[$stage] ?? ['status'=>'pending','accepted_at'=>null,'completed_at'=>null,'duration'=>null];
            $meta = $stageMeta[$stage];
            $badge= $badgeFor($p['status'] ?? 'pending');
          @endphp
          <div class="col-sm-6 col-lg-3">
            <div class="mini-card h-100">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi {{ $meta['icon'] }} fs-5 text-muted"></i>
                <div class="fw-semibold">{{ $meta['title'] }}</div>
                <span class="ms-auto badge-soft {{ $badge }}">
                  {{ str_replace('_',' ', $p['status'] ?? 'pending') }}
                </span>
              </div>
              <div class="small">
                <div><span class="key">Accepted:</span>
                  {{ !empty($p['accepted_at']) ? \Carbon\Carbon::parse($p['accepted_at'])->format('Y-m-d') : '—' }}
                </div>
                <div><span class="key">Completed:</span>
                  {{ !empty($p['completed_at']) ? \Carbon\Carbon::parse($p['completed_at'])->format('Y-m-d') : '—' }}
                </div>
                <div><span class="key">Duration:</span>
                  {{ $p['duration'] ?? '—' }}
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Job Order Information --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <h6 class="mb-3">Job Order Information</h6>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="key">Job Title</div>
          <div class="fw-semibold">{{ $ord->orderTitle ?? '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="key">Created By</div>
          <div class="fw-semibold">{{ $ord->salesperson->name ?? '-' }}</div>
        </div>

        <div class="col-md-6">
          <div class="key">Company Name</div>
          <div class="fw-semibold">
            {{ $ord->companyName ?? ($ord->lead->company_name ?? ($ord->lead->name ?? '-')) }}
          </div>
        </div>
        <div class="col-md-6">
          <div class="key">Design Confirmation Required</div>
          <div class="fw-semibold">{{ ($ord->approval ?? 0) ? 'Yes' : 'No' }}</div>
        </div>

        <div class="col-md-6">
          <div class="key">Created Date</div>
          <div class="fw-semibold">
            {{ ($ord->created_at ?? null) ? \Carbon\Carbon::parse($ord->created_at)->format('Y-m-d') : '-' }}
          </div>
        </div>
        <div class="col-md-6">
          <div class="key">Deadline</div>
          <div class="fw-semibold">
            {{ ($ord->deadline ?? null) ? \Carbon\Carbon::parse($ord->deadline)->format('Y-m-d') : '-' }}
          </div>
        </div>

        <div class="col-md-6">
          <h6 class="mb-3">Attachment from Lead</h6>
          @forelse($leadAttachments as $f)
            <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
              <div>
                <i class="bi bi-file-earmark me-2"></i>
                <a href="{{ $f->url }}" target="_blank" download class="text-decoration-none">
                  {{ $f->name }}
                </a>
                @if($f->size)
                  <span class="text-muted ms-2">({{ number_format($f->size/1024, 1) }} KB)</span>
                @endif
              </div>
            </div>
          @empty
            <div class="text-muted">No lead attachments.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- Product & Breakdown Details --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Product & Breakdown Details</h6>
        <div class="d-flex gap-2">
          @if(!empty($ord->artist_id) && !empty($ord->artist))
            <span class="badge bg-light text-muted fw-semibold px-3 py-2">
              {{ $ord->artist->name }}
            </span>
          @endif
          @if(!empty($ord->data_entry_id) && !empty($ord->dataEntry))
            <span class="badge fw-semibold px-3 py-2" style="background:#E0F7FF;color:#00AEEF;">
              {{ $ord->dataEntry->name }}
            </span>
          @endif
        </div>
      </div>

      <div class="border rounded p-3 mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center">
          <div class="fw-semibold">Product</div>
          <div>{{ $productCode }}: {{ $product->productName ?? '-' }}</div>
          <div class="ms-auto key">Qty:
            <span class="fw-semibold">{{ $product->totalQuantity ?? 0 }}</span>
          </div>
        </div>
        <div class="key mt-2">Material / Remark:
          <span class="fw-semibold">{{ $product->materialRemark ?? '-' }}</span>
        </div>
      </div>

      {{-- Items --}}
      @forelse($product->items as $it)
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">Item {{ $loop->iteration }} — {{ $it->itemName ?? 'Item' }}</div>
                <div class="text-muted small">Qty: {{ (int)($it->quantity ?? 0) }}</div>
              </div>
            </div>

            <div class="row g-3 mt-2">
              {{-- Size + unit --}}
              <div class="col-md-3">
                <small class="text-muted d-block">Size</small>
                <div class="fw-medium">
                  {{ rtrim((string)($it->sizeWidth ?? '')) }}
                  × {{ rtrim((string)($it->sizeHeight ?? '')) }}
                  {{ $it->sizeUnit ?? '' }}
                </div>
              </div>

              {{-- Bleed (Top / Right / Bottom / Left) + unit --}}
              <div class="col-md-5">
                <small class="text-muted d-block">Bleed</small>
                @php
                  $bu = $it->bleedUnit ?: ($it->sizeUnit ?? '');
                  $fmt = fn($v) => ($v === null || $v === '') ? '—' : rtrim((string)$v);
                @endphp
                <div class="fw-medium">
                  Top: {{ $fmt($it->bleedTop) }} {{ $bu }} &nbsp; |
                  Right: {{ $fmt($it->bleedRight) }} {{ $bu }} &nbsp; |
                  Bottom: {{ $fmt($it->bleedBottom) }} {{ $bu }} &nbsp; |
                  Left: {{ $fmt($it->bleedLeft) }} {{ $bu }}
                </div>
              </div>

              {{-- Prime Centre --}}
              <div class="col-md-4">
                <small class="text-muted d-block">Prime Centre</small>
                <div class="fw-medium">{{ ((int)($it->prime_centre ?? 0) === 1) ? 'Yes' : 'No' }}</div>
              </div>

              <div class="col-md-12">
                @php
                  $mat = $it->material ?? null;
                  if ($mat instanceof \Illuminate\Support\Collection) { $mat = $mat->toArray(); }
                  if (is_array($mat)) {
                    $materialText = implode(', ', array_filter($mat, fn($v) => $v !== '' && $v !== null));
                  } elseif (is_string($mat)) {
                    $decoded = json_decode($mat, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                      $materialText = implode(', ', array_filter($decoded, fn($v) => $v !== '' && $v !== null));
                    } else {
                      $materialText = trim($mat);
                    }
                  } else {
                    $materialText = '';
                  }
                @endphp

                <small class="text-muted d-block">Material</small>
                <div class="fw-medium mb-2">
                  {{ $materialText !== '' ? $materialText : '—' }}
                </div>

                @if(!empty($it->spec))
                <div class="row g-3">
                  <div class="col-md-4">
                    <small class="text-muted d-block">Lamination</small>
                    <div class="fw-medium">{{ $it->spec->lamination ?? '—' }}</div>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted d-block">Printer</small>
                    <div class="fw-medium">{{ $it->spec->printer ?? '—' }}</div>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted d-block">Cutter</small>
                    <div class="fw-medium">{{ $it->spec->cutter ?? '—' }}</div>
                  </div>
                </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      @empty
        <p class="text-muted mb-0">No items.</p>
      @endforelse
    </div>
  </div>

  {{-- Delivery Method Summary --}}
  <div class="card mb-3">
    <div class="card-body">
      <h6 class="mb-3">Delivery Method Summary</h6>
      @forelse($deliveries as $d)
        @php $d = is_array($d)?(object)$d:$d; @endphp
        <div class="border rounded p-3 mb-3">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="fw-semibold">Delivery Method: {{ $d->method ?: '-' }}</div>
              <div class="key">Quantity:</div>
              <div class="fw-semibold">{{ $d->quantity ?? '-' }}</div>
            </div>
            <div class="col-md-6">
              <div class="key">Date &amp; Time:</div>
              <div class="fw-semibold">
                {{ !empty($d->date) ? \Carbon\Carbon::parse($d->date)->format('Y-m-d') : '-' }}
              </div>
            </div>
            <div class="col-12">
              <div class="key">Location:</div>
              <div class="fw-semibold">{{ $d->location ?: 'Not required for pickup' }}</div>
            </div>
          </div>
        </div>
      @empty
        <div class="text-muted">No delivery breakdowns.</div>
      @endforelse
    </div>
  </div>

  {{-- Product Remarks --}}
  <div class="card mb-3">
    <div class="card-body pb-2">
      <h6 class="mb-3">Product Remarks</h6>

      @php
        $label = [
          'printing'     => 'To Printing',
          'furnishing'   => 'To Furnishing',
          'installation' => 'To Delivery & Installation',
          'courier'      => 'To Courier',
          'self_pickup'  => 'To Self Pickup',
          'artist'       => 'To Artist',
        ];
      @endphp

      <ul class="list-group list-group-flush remarks-list">
        @forelse ($product->remarks as $r)
          @php
            $r = is_array($r)?(object)$r:$r;
            $op        = strtolower((string)($r->operation ?? ''));
            $author    = optional($r->user)->name ?? optional($r->author)->name ?? '—';
            $timestamp = !empty($r->created_at) ? \Carbon\Carbon::parse($r->created_at)->format('Y-m-d') : '';
          @endphp

          <li class="list-group-item">
            <div class="d-flex align-items-start gap-3">
              <span class="remarks-op {{ $op }}">
                {{ $label[$op] ?? ucfirst($op ?: 'Note') }}
              </span>
              <div class="flex-grow-1">
                <div class="remarks-text">{{ $r->remark ?? '-' }}</div>
                <div class="text-muted small mt-1">
                  by <span class="fw-semibold">{{ $author }}</span>
                  @if($timestamp) • <span>{{ $timestamp }}</span>@endif
                </div>
              </div>
            </div>
          </li>
        @empty
          <li class="list-group-item text-muted">No product remarks.</li>
        @endforelse
      </ul>
    </div>
  </div>

  {{-- Attachments --}}
  <div class="card mt-4 mb-4">
    <div class="card-body">
      <h6 class="mb-3">Attachments</h6>
      @forelse($orderFiles as $f)
        <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
          <div>
            <i class="bi bi-file-earmark me-2"></i>
            <span class="fw-semibold">{{ $f['name'] ?? 'file' }}</span>
            @if(!empty($f['ext'])) <small class="text-muted ms-2">.{{ $f['ext'] }}</small> @endif
          </div>
          <div class="d-flex gap-2">
            <a href="{{ $f['url'] ?? '#' }}" class="btn btn-sm btn-outline-secondary" target="_blank">Open</a>
            <a href="{{ $f['url'] ?? '#' }}" class="btn btn-sm btn-dark" download>Download</a>
          </div>
        </div>
      @empty
        <div class="text-muted">No attachments uploaded for this order.</div>
      @endforelse
    </div>
  </div>

  <div class="text-end">
    <a href="{{ route('admin.fulfillment') }}" class="btn btn-secondary">Close</a>
  </div>
</div>
@endsection

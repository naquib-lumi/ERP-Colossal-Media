{{-- Link to the materials an order needs. Needs $order. Shown only to users allowed to see it. --}}
@if (auth()->check() && \App\Http\Controllers\OrderMaterialsController::canView(auth()->user(), $order))
  <a href="{{ route('orders.materials', $order->id) }}" class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2 fw-semibold" style="border-radius:8px;">
    <i class="bx bx-layer fs-5"></i>
    <span>Materials</span>
  </a>
@endif

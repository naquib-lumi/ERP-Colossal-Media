{{-- Link to an order's delivery orders. Needs $order. Shown only to users allowed to see them. --}}
@if (auth()->check() && \App\Services\DeliveryOrderService::canView(auth()->user(), $order))
  <a href="{{ route('orders.delivery-orders', $order->id) }}" class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2 fw-semibold" style="border-radius:8px;">
    <i class="bx bx-package fs-5"></i>
    <span>Delivery orders</span>
  </a>
@endif

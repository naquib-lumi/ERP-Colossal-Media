{{-- Auto-fill: "Copy as new order". Needs $order. Shown only to users allowed to copy it. --}}
@if (auth()->check() && \App\Services\OrderCopyService::canCopy(auth()->user(), $order))
  <form method="POST" action="{{ route('orders.copy', $order->id) }}" class="d-inline-flex"
        data-msg="Create a new draft order copied from {{ $order->order_number }}? Products, items and remarks are copied; you set the new deadline and delivery dates."
        onsubmit="return confirm(this.dataset.msg)">
    @csrf
    <button type="submit" class="btn btn-outline-primary d-flex align-items-center gap-2 px-3 py-2 fw-semibold"
            style="border-radius:8px;" title="Start a new order pre-filled from this one">
      <i class="bx bx-copy fs-5"></i>
      <span>Copy as new order</span>
    </button>
  </form>
@endif

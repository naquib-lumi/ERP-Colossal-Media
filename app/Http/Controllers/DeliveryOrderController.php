<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Mail\DeliveryOrderMail;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Services\DeliveryOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/** Delivery orders: list, per-order page, create/update, print, PDF and email to the client. */
class DeliveryOrderController extends Controller
{
    public function __construct(private DeliveryOrderService $service)
    {
    }

    /** All delivery orders the user may see (menu page for dispatch / delivery staff). */
    public function index(Request $request)
    {
        $user   = $request->user();
        $q      = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', DeliveryOrder::STATUS_ISSUED);

        $dos = DeliveryOrder::with(['order:id,order_number,companyName,leadName,lead_id,salesperson_id'])
            ->withSum('lines', 'quantity')
            ->when($user->hasRole(Role::Salesperson), fn (Builder $b) => $b->whereHas('order', fn ($o) => $o
                ->where(fn ($w) => $w->where('salesperson_id', $user->id)->orWhereHas('lead', fn ($l) => $l->where('salesperson_id', $user->id)))))
            ->when(in_array($status, [DeliveryOrder::STATUS_ISSUED, DeliveryOrder::STATUS_CANCELLED], true), fn ($b) => $b->where('status', $status))
            ->when($q !== '', fn (Builder $b) => $b->where(fn ($w) => $w
                ->where('do_number', 'like', "%{$q}%")
                ->orWhere('location', 'like', "%{$q}%")
                ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$q}%")->orWhere('companyName', 'like', "%{$q}%"))))
            ->orderByRaw('delivery_date IS NULL, delivery_date DESC')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('delivery-orders.index', [
            'dos'      => $dos,
            'filters'  => compact('q', 'status'),
            'canPrint' => DeliveryOrderService::canPrint($user),
        ]);
    }

    /** The delivery orders of one order. */
    public function forOrder(Request $request, Order $order)
    {
        abort_unless(DeliveryOrderService::canView($request->user(), $order), 403);

        $all = DeliveryOrder::with('lines')->where('order_id', $order->id)->orderBy('id')->get();

        return view('delivery-orders.order', [
            'order'       => $order,
            'issued'      => $all->where('status', DeliveryOrder::STATUS_ISSUED)->values(),
            'cancelled'   => $all->where('status', DeliveryOrder::STATUS_CANCELLED)->values(),
            'canBuild'    => $this->service->canBuildFor($order),
            'canGenerate' => DeliveryOrderService::canGenerate($request->user()),
            'canEmail'    => DeliveryOrderService::canEmail($request->user(), $order),
            'canPrint'    => DeliveryOrderService::canPrint($request->user()),
            'outOfDate'   => $this->service->isOutOfDate($order),
            'hasRows'     => $this->service->plan($order)->isNotEmpty(),
        ]);
    }

    public function sync(Request $request, Order $order)
    {
        abort_unless(DeliveryOrderService::canGenerate($request->user()) && DeliveryOrderService::canView($request->user(), $order), 403);

        if (! $this->service->canBuildFor($order)) {
            return back()->with('error', 'Delivery orders can be created once the order is submitted.');
        }

        $issued = $this->service->sync($order, $request->user()->id);

        return back()->with('success', $issued->isEmpty()
            ? 'This order has no delivery locations yet. Add them to the products first.'
            : "{$issued->count()} delivery order(s) are up to date.");
    }

    /** Printable A4 page. */
    public function show(Request $request, DeliveryOrder $deliveryOrder)
    {
        abort_unless(DeliveryOrderService::canView($request->user(), $deliveryOrder->order), 403);

        return view('delivery-orders.print', $this->documentData($deliveryOrder, forPdf: false) + [
            'canEmail' => DeliveryOrderService::canEmail($request->user(), $deliveryOrder->order),
            'canPrint' => DeliveryOrderService::canPrint($request->user()),
        ]);
    }

    public function pdf(Request $request, DeliveryOrder $deliveryOrder)
    {
        abort_unless(DeliveryOrderService::canView($request->user(), $deliveryOrder->order)
            && DeliveryOrderService::canPrint($request->user()), 403);

        return $this->makePdf($deliveryOrder)->download("{$deliveryOrder->do_number}.pdf");
    }

    public function email(Request $request, DeliveryOrder $deliveryOrder)
    {
        abort_unless(DeliveryOrderService::canEmail($request->user(), $deliveryOrder->order), 403);

        $data = $request->validate(['to' => ['required', 'email:rfc', 'max:255']]);

        if ($deliveryOrder->isCancelled()) {
            return back()->with('error', "{$deliveryOrder->do_number} is cancelled and cannot be emailed.");
        }

        try {
            Mail::to($data['to'])->send(new DeliveryOrderMail($deliveryOrder, $this->makePdf($deliveryOrder)->output()));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Could not send {$deliveryOrder->do_number}. Please check the email address and try again.");
        }

        $deliveryOrder->forceFill([
            'emailed_at'   => now(),
            'emailed_to'   => $data['to'],
            'emailed_hash' => $deliveryOrder->content_hash,
        ])->save();
        $deliveryOrder->log('emailed', $request->user()->id, $data['to']);

        return back()->with('success', "{$deliveryOrder->do_number} emailed to {$data['to']}.");
    }

    /** Delivery status, history and (for dispatch / delivery staff) the "Delivered" form. */
    public function status(Request $request, DeliveryOrder $deliveryOrder)
    {
        abort_unless(DeliveryOrderService::canView($request->user(), $deliveryOrder->order), 403);

        $deliveryOrder->load(['order', 'lines', 'deliveredBy:id,name', 'events.user:id,name']);

        return view('delivery-orders.status', [
            'do'         => $deliveryOrder,
            'canConfirm' => DeliveryOrderService::canConfirmDelivery($request->user()),
            'canPrint'   => DeliveryOrderService::canPrint($request->user()),
        ]);
    }

    /** Mark delivered with a photo of the signed DO. Once delivered, the DO is frozen. */
    public function deliver(Request $request, DeliveryOrder $deliveryOrder)
    {
        abort_unless(DeliveryOrderService::canConfirmDelivery($request->user())
            && DeliveryOrderService::canView($request->user(), $deliveryOrder->order), 403);

        $data = $request->validate([
            'delivered_at'     => ['required', 'date'],
            'signed_photo'     => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'delivery_remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'signed_photo.required' => 'Take or upload a photo of the signed delivery order.',
        ]);

        // The form is filled in Malaysian time; store it in the app's timezone.
        $deliveredAt = \Illuminate\Support\Carbon::parse($data['delivered_at'], 'Asia/Kuala_Lumpur')->setTimezone(config('app.timezone'));
        if ($deliveredAt->isAfter(now()->addMinutes(5))) {
            return back()->withInput()->withErrors(['delivered_at' => 'The delivery time cannot be in the future.']);
        }

        if ($deliveryOrder->isCancelled()) {
            return back()->with('error', "{$deliveryOrder->do_number} is cancelled.");
        }
        if ($deliveryOrder->isDelivered()) {
            return back()->with('error', "{$deliveryOrder->do_number} is already marked delivered.");
        }

        $path = $this->storeSignedPhoto($request->file('signed_photo'), $deliveryOrder);

        $deliveryOrder->forceFill([
            'delivered_at'      => $deliveredAt,
            'delivered_by'      => $request->user()->id,
            'delivery_remarks'  => isset($data['delivery_remarks']) ? trim($data['delivery_remarks']) : null,
            'signed_photo_path' => $path,
        ])->save();
        $deliveryOrder->log('delivered', $request->user()->id, $deliveryOrder->delivery_remarks);

        return redirect()->route('delivery-orders.status', $deliveryOrder)->with('success', "{$deliveryOrder->do_number} marked delivered.");
    }

    /** The signed DO photo, for anyone who may see the DO. Kept off the public disk. */
    public function photo(Request $request, DeliveryOrder $deliveryOrder)
    {
        abort_unless(DeliveryOrderService::canView($request->user(), $deliveryOrder->order), 403);
        abort_unless($deliveryOrder->signed_photo_path && Storage::disk('local')->exists($deliveryOrder->signed_photo_path), 404);

        return Storage::disk('local')->response($deliveryOrder->signed_photo_path);
    }

    /** Save the photo on the private disk, scaled down to 1600 px as JPEG when GD can read it. */
    private function storeSignedPhoto(UploadedFile $file, DeliveryOrder $do): string
    {
        $dir  = "delivery-orders/{$do->id}";
        $name = 'signed-' . now()->format('YmdHis');

        $image = function_exists('imagecreatefromstring') ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;
        if ($image === false) {
            return $file->storeAs($dir, $name . '.' . $file->extension(), 'local');
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, 1600 / max($w, $h));
        if ($scale < 1) {
            $image = imagescale($image, (int) round($w * $scale), (int) round($h * $scale));
        }

        ob_start();
        imagejpeg($image, null, 80);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        $path = "{$dir}/{$name}.jpg";
        Storage::disk('local')->put($path, $jpeg);

        return $path;
    }

    private function makePdf(DeliveryOrder $do)
    {
        return Pdf::loadView('delivery-orders.pdf', $this->documentData($do, forPdf: true))->setPaper('a4');
    }

    private function documentData(DeliveryOrder $do, bool $forPdf): array
    {
        $do->loadMissing(['lines', 'order.lead']);
        $company = $do->company();

        return [
            'do'      => $do,
            'order'   => $do->order,
            'company' => $company,
            // dompdf reads images from disk; the browser needs a URL.
            'logoSrc' => $company->logoPath() ? ($forPdf ? $company->logoPath() : asset($company->logo)) : null,
        ];
    }
}

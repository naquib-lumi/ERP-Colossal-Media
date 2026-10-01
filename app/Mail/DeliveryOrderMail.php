<?php

namespace App\Mail;

use App\Models\DeliveryOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Soft copy of a delivery order for the client, with the DO as a PDF attachment. */
class DeliveryOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DeliveryOrder $deliveryOrder, private string $pdf)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Delivery Order {$this->deliveryOrder->do_number} – " . config('company.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.delivery-order', with: ['do' => $this->deliveryOrder]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, "{$this->deliveryOrder->do_number}.pdf")->withMime('application/pdf'),
        ];
    }
}

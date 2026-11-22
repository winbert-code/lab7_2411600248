<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PurchaseOrder $order,
        public readonly ?string $message = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Purchase order {$this->order->reference_number} approved");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-approved',
            with: [
                'order' => $this->order->loadMissing('supplier', 'approvedBy'),
                'note' => $this->message,
                'url' => route('purchase-orders.show', $this->order),
            ],
        );
    }

    /** @return array<int, \Illuminate\Mail\Mailables\Attachment> */
    public function attachments(): array
    {
        return [];
    }
}

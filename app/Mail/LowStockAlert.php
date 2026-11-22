<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly int $reorderPoint,
        public readonly int $suggestedQuantity,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Low stock alert: {$this->product->name}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-alert',
            with: [
                'product' => $this->product,
                'reorderPoint' => $this->reorderPoint,
                'suggestedQuantity' => $this->suggestedQuantity,
                'url' => route('products.show', $this->product),
            ],
        );
    }

    /** @return array<int, \Illuminate\Mail\Mailables\Attachment> */
    public function attachments(): array
    {
        return [];
    }
}

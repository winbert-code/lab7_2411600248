<?php

namespace App\Notifications;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Product $product,
        public readonly ?InventoryTransaction $transaction = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Low stock alert: '.$this->product->name,
            'message' => "{$this->product->name} ({$this->product->sku}) has {$this->product->quantity} units left. Reorder level is {$this->product->reorder_level}.",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'quantity' => (int) $this->product->quantity,
            'reorder_level' => (int) $this->product->reorder_level,
            'suggested_action' => 'Create a purchase order to reorder this item.',
            'icon' => 'bi-exclamation-triangle-fill',
            'url' => route('products.show', $this->product),
        ];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Low stock alert: '.$this->product->name)
            ->greeting('Hello '.$notifiable->name.',')
            ->line("{$this->product->name} (SKU {$this->product->sku}) is running low.")
            ->line("Current quantity: {$this->product->quantity}. Reorder level: {$this->product->reorder_level}.")
            ->line('Suggested action: create a purchase order to reorder this item.')
            ->action('View product', route('products.show', $this->product));
    }
}

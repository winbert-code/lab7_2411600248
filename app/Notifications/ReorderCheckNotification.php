<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReorderCheckNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  array<int, string>  $references */
    public function __construct(
        public readonly int $orderCount,
        public readonly array $references = [],
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
            'title' => 'Automated reorder check complete',
            'message' => $this->orderCount > 0
                ? "{$this->orderCount} draft purchase order(s) were generated for review."
                : 'No purchase orders were needed.',
            'icon' => 'bi-clipboard-check',
            'color' => $this->orderCount > 0 ? 'warning' : 'success',
            'references' => $this->references,
            'url' => route('purchase-orders.index'),
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
            ->subject('Automated reorder check complete')
            ->line($this->toArray($notifiable)['message'])
            ->action('Review purchase orders', route('purchase-orders.index'));
    }
}

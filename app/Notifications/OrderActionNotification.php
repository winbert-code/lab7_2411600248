<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class OrderActionNotification extends Notification
{
    public function __construct(
        public readonly string $module,
        public readonly string $orderNumber,
        public readonly string $action,
        public readonly User $actor,
        public readonly ?string $url = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $module = strtolower($this->module);

        return [
            'title' => "{$this->module} {$this->action}: {$this->orderNumber}",
            'message' => "{$this->actor->name} {$this->action} {$module} {$this->orderNumber}.",
            'module' => $this->module,
            'order_number' => $this->orderNumber,
            'action' => $this->action,
            'actor_name' => $this->actor->name,
            'icon' => $this->module === 'Sales order' ? 'bi-cart-check' : 'bi-clipboard-check',
            'color' => 'primary',
            'url' => $this->url,
        ];
    }
}

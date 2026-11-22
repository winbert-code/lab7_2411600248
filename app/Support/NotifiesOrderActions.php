<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\OrderActionNotification;
use Illuminate\Support\Facades\Notification;

trait NotifiesOrderActions
{
    /**
     * Staff actions go to admins; admin/manager actions go back to staff.
     */
    private function notifyOppositeRole(
        string $module,
        string $orderNumber,
        string $action,
        User $actor,
        ?string $url = null,
    ): void {
        $roles = $actor->isStaff()
            ? [User::ROLE_ADMIN]
            : [User::ROLE_STAFF];

        $recipients = User::query()
            ->active()
            ->whereIn('role', $roles)
            ->where('id', '!=', $actor->id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new OrderActionNotification(
            module: $module,
            orderNumber: $orderNumber,
            action: $action,
            actor: $actor,
            url: $url,
        ));
    }
}

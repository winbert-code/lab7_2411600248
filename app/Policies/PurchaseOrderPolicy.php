<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    /** Admins bypass every check except where an explicit business rule applies. */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrder $order): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canCreateOrders();
    }

    public function update(User $user, PurchaseOrder $order): bool
    {
        return $order->isDraft()
            && ($order->created_by === $user->id || $order->user_id === $user->id || $user->isManager());
    }

    public function delete(User $user, PurchaseOrder $order): bool
    {
        return $order->isDraft();
    }

    /** Managers approve normal orders; orders above the threshold need an admin. */
    public function approve(User $user, PurchaseOrder $order): bool
    {
        if (! $order->canBeApproved() || ! $user->canApproveOrders()) {
            return false;
        }

        // High-value orders need a manager's first approval AND an admin's final approval.
        if ($order->isHighValue()
            && (int) $order->approval_level >= PurchaseOrder::APPROVAL_FIRST
            && ! $user->isAdmin()) {
            return false;
        }

        return true;
    }

    public function reject(User $user, PurchaseOrder $order): bool
    {
        return $order->canBeApproved() && $user->canApproveOrders();
    }

    public function send(User $user, PurchaseOrder $order): bool
    {
        return $order->status === PurchaseOrder::STATUS_APPROVED
            && ($user->isManager() || $user->isAdmin());
    }

    public function receive(User $user, PurchaseOrder $order): bool
    {
        return $order->canBeReceived() && $user->canReceiveOrders();
    }

    public function export(User $user): bool
    {
        return $user->isInventoryManager();
    }
}

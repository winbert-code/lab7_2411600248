<?php

namespace App\Observers;

use App\Exceptions\InsufficientStockException;
use App\Mail\LowStockAlert;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Services\ReorderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class InventoryTransactionObserver
{
    public function __construct(private readonly ReorderService $reorder) {}

    /**
     * Keep product quantities in sync with the audit trail.
     *
     * All writes happen inside one DB transaction. The product row is locked
     * with FOR UPDATE so two simultaneous stock movements cannot race and
     * produce a negative balance or a lost update.
     */
    public function created(InventoryTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $product = Product::query()
                ->whereKey($transaction->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (int) $product->quantity;
            $after = $this->calculateBalance($transaction, $before);

            if ($after < 0) {
                throw InsufficientStockException::forProduct(
                    $product->name,
                    $before,
                    (int) $transaction->quantity
                );
            }

            $product->quantity = $after;
            $product->save();

            // Update via the query builder so saving the audit row never fires
            // another observer event.
            InventoryTransaction::query()
                ->whereKey($transaction->id)
                ->update([
                    'previous_quantity' => $before,
                    'balance_after' => $after,
                ]);

            $transaction->previous_quantity = $before;
            $transaction->balance_after = $after;

            $this->sendLowStockNotification($product, $before, $after, $transaction);
        });
    }

    /**
     * Deleting an audit record reverses its effect, keeping the ledger balanced.
     */
    public function deleting(InventoryTransaction $transaction): void
    {
        if ($transaction->previous_quantity === null) {
            return;
        }

        DB::transaction(function () use ($transaction) {
            $product = Product::query()
                ->whereKey($transaction->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (int) $product->quantity;
            $after = $transaction->isAdjustment()
                ? max(0, (int) $transaction->previous_quantity)
                : $before + ($transaction->isStockOut()
                    ? (int) $transaction->quantity
                    : -(int) $transaction->quantity);

            if ($after < 0) {
                throw InsufficientStockException::forProduct($product->name, $before, (int) $transaction->quantity);
            }

            $product->quantity = $after;
            $product->save();
        });
    }

    private function calculateBalance(InventoryTransaction $transaction, int $current): int
    {
        return match ($transaction->type) {
            InventoryTransaction::TYPE_IN => $current + (int) $transaction->quantity,
            InventoryTransaction::TYPE_OUT => $current - (int) $transaction->quantity,
            InventoryTransaction::TYPE_ADJUSTMENT => (int) $transaction->quantity,
            default => $current,
        };
    }

    /**
     * Notify admins, managers and staff when a product *crosses* the reorder
     * line, and at most once per product per hour. This avoids alert spam
     * while a product is already in a low-stock state.
     */
    private function sendLowStockNotification(
        Product $product,
        int $before,
        int $after,
        InventoryTransaction $transaction
    ): void {
        if ($after > $product->reorder_level || $before <= $product->reorder_level) {
            return;
        }

        $recentlyNotified = DB::table('notifications')
            ->where('type', LowStockNotification::class)
            ->where('created_at', '>=', now()->subHour())
            ->where('data', 'like', '%"product_id":'.$product->id.'%')
            ->exists();

        if ($recentlyNotified) {
            return;
        }

        $recipients = User::query()
            ->whereIn('role', ['admin', 'manager', 'staff'])
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new LowStockNotification($product, $transaction));

        // Lab 7: also deliver a queued Mailable with the calculated reorder
        // suggestion to administrators and managers.
        $emailRecipients = User::query()
            ->active()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_MANAGER])
            ->get();

        if ($emailRecipients->isEmpty()) {
            return;
        }

        $point = $this->reorder->calculateReorderPoint($product);
        $suggestion = $this->reorder->calculateOptimalOrderQuantity($product, $point);

        foreach ($emailRecipients as $recipient) {
            Mail::to($recipient)->queue(new LowStockAlert($product, $point, $suggestion));
        }
    }
}

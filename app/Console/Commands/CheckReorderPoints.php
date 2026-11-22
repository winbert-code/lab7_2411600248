<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReorderCheckNotification;
use App\Services\ReorderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckReorderPoints extends Command
{
    protected $signature = 'replenishment:check-reorder
                            {--dry-run : Report products without creating purchase orders}';

    protected $description = 'Check reorder points, generate draft purchase orders and notify administrators';

    public function handle(ReorderService $reorder): int
    {
        $this->info('Checking reorder points at '.now()->toDateTimeString().'…');

        $alerts = $reorder->getProductsNeedingReorder(fresh: true);

        if ($alerts->isEmpty()) {
            $this->info('All products are above their reorder points. No action needed.');
            $this->notifyAdministrators(0, collect());

            return self::SUCCESS;
        }

        $this->table(
            ['Product', 'SKU', 'Supplier', 'Stock', 'Reorder point', 'Suggested qty'],
            $alerts->map(fn (array $alert) => [
                $alert['product']->name,
                $alert['product']->sku,
                $alert['supplier']->company_name,
                $alert['current_stock'],
                $alert['reorder_point'],
                $alert['suggested_quantity'],
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry run: no purchase orders were created.');
            return self::SUCCESS;
        }

        $orders = $reorder->generatePurchaseOrders();

        foreach ($orders as $order) {
            $this->line(sprintf(
                '<info>Created %s</info> for %s — %s item(s), total ₱%s',
                $order->reference_number,
                $order->supplier_name,
                $order->items()->count(),
                number_format((float) $order->total, 2),
            ));
        }

        if ($orders->isEmpty()) {
            $this->comment('No new purchase orders were needed (existing open orders already cover the alerts).');
        }

        $this->notifyAdministrators($orders->count(), $orders->pluck('reference_number'));
        $this->info('Reorder check finished successfully.');

        return self::SUCCESS;
    }

    private function notifyAdministrators(int $count, \Illuminate\Support\Collection $references): void
    {
        $admins = User::query()->active()->where('role', User::ROLE_ADMIN)->get();

        if ($admins->isEmpty()) {
            $this->warn('No active administrators found to notify.');
            return;
        }

        Notification::send($admins, new ReorderCheckNotification($count, $references->all()));
        $this->line('Notified '.$admins->count().' administrator(s).');
    }
}

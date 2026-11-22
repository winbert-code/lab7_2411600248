<?php

namespace App\Http\Controllers;

use App\Services\ReorderService;
use Illuminate\Http\RedirectResponse;

class ReplenishmentController extends Controller
{
    public function __construct(private readonly ReorderService $reorder) {}

    public function run(): RedirectResponse
    {
        $orders = $this->reorder->generatePurchaseOrders(auth()->user());

        return redirect()->route('purchase-orders.index')
            ->with('success', $orders->isEmpty()
                ? 'Reorder check complete. No purchase orders were needed.'
                : 'Reorder check complete. '.$orders->count().' draft purchase order(s) generated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\ActivityLog;
use App\Models\InventoryTransaction;
use App\Models\Note;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SearchService;
use App\Support\NotifiesOrderActions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PurchaseOrderController extends Controller
{
    use NotifiesOrderActions;

    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        return view('purchase-orders.index', [
            'orders' => $this->search->searchPurchaseOrders($request->all()),
            'suppliers' => Supplier::orderBy('company_name')->get(['id', 'company_name']),
            'statuses' => PurchaseOrder::STATUSES,
            'filters' => $request->only(['search', 'status', 'supplier', 'from', 'to']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('purchase-orders.form', [
            'order' => new PurchaseOrder([
                'reference_number' => PurchaseOrder::generateReferenceNumber(),
                'order_date' => now()->toDateString(),
                'tax_rate' => config('replenishment.tax_rate', 12),
            ]),
            'items' => collect([['product_id' => '', 'quantity' => 1, 'unit_cost' => '']]),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'unit_price', 'cost_price', 'supplier_id']),
            'suppliers' => Supplier::active()->orderBy('company_name')->get(),
            'action' => route('purchase-orders.store'),
            'method' => 'POST',
            'editing' => false,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);
        $data = $request->validated();

        $order = DB::transaction(function () use ($data) {
            $reference = ($data['reference_number'] ?? null) ?: PurchaseOrder::generateReferenceNumber();
            $order = PurchaseOrder::create([
                'reference_number' => $reference,
                // Legacy Lab 6 column is NOT NULL; keep it in sync.
                'order_number' => $reference,
                'supplier_id' => $data['supplier_id'],
                'supplier' => Supplier::findOrFail($data['supplier_id'])->company_name,
                'status' => ! empty($data['submit_for_approval'])
                    ? PurchaseOrder::STATUS_PENDING
                    : PurchaseOrder::STATUS_DRAFT,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_delivery_date'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? config('replenishment.tax_rate', 12),
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'currency' => 'PHP',
                'created_by' => auth()->id(),
                'user_id' => auth()->id(),
            ]);

            $this->saveItems($order, $data['items']);
            $order->calculateTotal();

            return $order;
        });

        ActivityLog::record(
            ActivityLog::MODULE_PURCHASE_ORDER,
            ActivityLog::ACTION_CREATED,
            "Purchase order {$order->reference_number} was created for {$order->supplier_name}.",
            $order,
        );

        $this->notifyManagers($order);
        $this->notifyOppositeRole('Purchase order', $order->reference_number, 'created', auth()->user(), route('purchase-orders.show', $order));

        return redirect()->route('purchase-orders.show', $order)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'supplier', 'items.product', 'createdBy', 'approvedBy', 'user',
            'approvalNotes' => fn ($query) => $query->with('user')->latest(),
        ]);

        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('update', $purchaseOrder);

        return view('purchase-orders.form', [
            'order' => $purchaseOrder,
            'items' => $purchaseOrder->items,
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'unit_price', 'cost_price', 'supplier_id']),
            'suppliers' => Supplier::active()->orderBy('company_name')->get(),
            'action' => route('purchase-orders.update', $purchaseOrder),
            'method' => 'PUT',
            'editing' => true,
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);
        $data = $request->validated();

        DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder->update([
                'reference_number' => ($data['reference_number'] ?? null) ?: $purchaseOrder->reference_number,
                'supplier_id' => $data['supplier_id'],
                'supplier' => Supplier::findOrFail($data['supplier_id'])->company_name,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_delivery_date'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? config('replenishment.tax_rate', 12),
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->saveItems($purchaseOrder, $data['items']);
            $purchaseOrder->calculateTotal();
        });

        ActivityLog::record(
            ActivityLog::MODULE_PURCHASE_ORDER,
            ActivityLog::ACTION_UPDATED,
            "Purchase order {$purchaseOrder->reference_number} was updated.",
            $purchaseOrder,
        );

        $this->notifyOppositeRole('Purchase order', $purchaseOrder->reference_number, 'updated', auth()->user(), route('purchase-orders.show', $purchaseOrder));

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('delete', $purchaseOrder);
        $reference = $purchaseOrder->reference_number;

        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        ActivityLog::record(
            ActivityLog::MODULE_PURCHASE_ORDER,
            ActivityLog::ACTION_CANCELLED,
            "Draft purchase order {$reference} was deleted.",
        );

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Draft purchase order deleted.');
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('approve', $purchaseOrder);
        abort_unless($purchaseOrder->canBeApproved(), 422, 'Only pending purchase orders can be approved.');

        $actor = auth()->user();

        // Two-step approval for high-value purchases: manager first, admin final.
        if ($purchaseOrder->isHighValue() && (int) $purchaseOrder->approval_level < PurchaseOrder::APPROVAL_FIRST) {
            $purchaseOrder->update(['approval_level' => PurchaseOrder::APPROVAL_FIRST]);
            $this->addNote($purchaseOrder, 'First-level approval by '.$actor->name.'. Awaiting administrator approval.', 'approval');
            ActivityLog::record(
                ActivityLog::MODULE_PURCHASE_ORDER,
                ActivityLog::ACTION_APPROVED,
                "First-level approval recorded for {$purchaseOrder->reference_number}.",
                $purchaseOrder,
            );
            $this->notifyAdmins($purchaseOrder);

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'First-level approval recorded. This high-value order needs administrator approval.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_APPROVED,
            'approval_level' => PurchaseOrder::APPROVAL_FINAL,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->addNote($purchaseOrder, 'Approved by '.$actor->name.'.', 'approval');

        ActivityLog::record(
            ActivityLog::MODULE_PURCHASE_ORDER,
            ActivityLog::ACTION_APPROVED,
            "Purchase order {$purchaseOrder->reference_number} was approved by {$actor->name}.",
            $purchaseOrder,
        );

        $this->notifyCreator($purchaseOrder, 'Your purchase order was approved.', new PurchaseOrderApproved($purchaseOrder));
        $this->notifyOppositeRole('Purchase order', $purchaseOrder->reference_number, 'approved', $actor, route('purchase-orders.show', $purchaseOrder));

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order approved.');
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('reject', $purchaseOrder);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_REJECTED,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        $this->addNote($purchaseOrder, 'Rejected by '.auth()->user()->name.': '.$data['rejection_reason'], 'rejection');

        ActivityLog::record(
            ActivityLog::MODULE_PURCHASE_ORDER,
            ActivityLog::ACTION_REJECTED,
            "Purchase order {$purchaseOrder->reference_number} was rejected.",
            $purchaseOrder,
        );

        $this->notifyCreator($purchaseOrder, 'Your purchase order was rejected.', new PurchaseOrderApproved(
            $purchaseOrder,
            'Rejection reason: '.$data['rejection_reason'],
        ));

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order rejected.');
    }

    public function send(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('send', $purchaseOrder);
        abort_unless($purchaseOrder->status === PurchaseOrder::STATUS_APPROVED, 422, 'Only approved orders can be sent.');

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_SENT,
            'sent_at' => now(),
        ]);
        $this->addNote($purchaseOrder, 'Marked as sent to supplier.', 'status');

        ActivityLog::record(
            ActivityLog::MODULE_PURCHASE_ORDER,
            ActivityLog::ACTION_SENT,
            "Purchase order {$purchaseOrder->reference_number} was sent to {$purchaseOrder->supplier_name}.",
            $purchaseOrder,
        );

        if ($purchaseOrder->supplier_record?->email) {
            Mail::to($purchaseOrder->supplier_record->email)->queue(new PurchaseOrderSent($purchaseOrder));
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order marked as sent to the supplier.');
    }

    /** Partial receipts are supported: each line receives its outstanding balance by default. */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('receive', $purchaseOrder);

        $data = $request->validate([
            'received' => ['required', 'array', 'min:1'],
            'received.*' => ['integer', 'min:0', 'max:100000'],
        ]);

        $lines = $purchaseOrder->items()->whereIn('id', array_keys($data['received']))->get();
        abort_if($lines->isEmpty(), 422, 'No valid order lines were selected.');

        foreach ($lines as $line) {
            abort_if(
                (int) $data['received'][$line->id] > $line->outstanding_quantity,
                422,
                'Received quantity cannot exceed the outstanding quantity for '.$line->product?->name.'.'
            );
        }

        $receivedUnits = 0;

        DB::transaction(function () use ($purchaseOrder, $lines, $data, &$receivedUnits) {
            foreach ($lines as $line) {
                $quantity = (int) $data['received'][$line->id];

                if ($quantity <= 0) {
                    continue;
                }

                InventoryTransaction::create([
                    'product_id' => $line->product_id,
                    'type' => InventoryTransaction::TYPE_IN,
                    'quantity' => $quantity,
                    'reference' => $purchaseOrder->reference_number,
                    'reason' => 'Purchase order receipt',
                    'notes' => "Received from {$purchaseOrder->supplier_name}",
                    'user_id' => auth()->id(),
                ]);

                $line->increment('received_quantity', $quantity);
                $receivedUnits += $quantity;
            }

            $remaining = (int) $purchaseOrder->items()
                ->selectRaw('SUM(quantity - received_quantity) as remaining')
                ->value('remaining');

            $purchaseOrder->update([
                'status' => $remaining === 0 ? PurchaseOrder::STATUS_RECEIVED : PurchaseOrder::STATUS_SENT,
                'partially_received' => $remaining > 0,
                'received_date' => $remaining === 0 ? now()->toDateString() : null,
            ]);
        });

        $this->addNote($purchaseOrder, "Received {$receivedUnits} unit(s); warehouse: ".auth()->user()->name.'.', 'receipt');

        ActivityLog::record(
            ActivityLog::MODULE_PURCHASE_ORDER,
            ActivityLog::ACTION_RECEIVED,
            "Purchase order {$purchaseOrder->reference_number} received {$receivedUnits} unit(s).",
            $purchaseOrder,
        );

        $this->notifyOppositeRole('Purchase order', $purchaseOrder->reference_number, 'received', auth()->user(), route('purchase-orders.show', $purchaseOrder));

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "Received {$receivedUnits} unit(s). Inventory levels were updated.");
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->status !== PurchaseOrder::STATUS_RECEIVED, 422, 'Received orders cannot be cancelled.');

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);
        $this->addNote($purchaseOrder, 'Cancelled by '.auth()->user()->name.'.', 'status');

        ActivityLog::record(
            ActivityLog::MODULE_PURCHASE_ORDER,
            ActivityLog::ACTION_CANCELLED,
            "Purchase order {$purchaseOrder->reference_number} was cancelled.",
            $purchaseOrder,
        );

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order cancelled.');
    }

    /** @param  array<int, array<string, mixed>>  $items */
    private function saveItems(PurchaseOrder $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
            ]);
        }
    }

    private function addNote(PurchaseOrder $order, string $body, string $type): void
    {
        Note::create([
            'notable_type' => $order->getMorphClass(),
            'notable_id' => $order->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'body' => $body,
        ]);
    }

    private function notifyManagers(PurchaseOrder $order): void
    {
        $recipients = User::query()
            ->active()
            ->whereIn('role', [User::ROLE_MANAGER, User::ROLE_ADMIN])
            ->where('id', '!=', auth()->id())
            ->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new PurchaseOrderCreated($order));
        }
    }

    private function notifyAdmins(PurchaseOrder $order): void
    {
        $recipients = User::query()->active()->where('role', User::ROLE_ADMIN)->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new PurchaseOrderCreated($order));
        }
    }

    private function notifyCreator(PurchaseOrder $order, string $subject, mixed $mailable): void
    {
        $creator = $order->createdBy ?: $order->user;

        if ($creator?->email) {
            Mail::to($creator->email)->queue($mailable);
        }
    }
}

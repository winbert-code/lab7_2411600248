<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\ActivityLog;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $leadTime = $request->integer('lead_time', 0);
        $sort = $request->string('sort')->toString();

        $suppliers = Supplier::query()
            ->withCount(['purchaseOrders', 'products'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->where('status', $status))
            ->when($leadTime > 0, fn ($q) => $q->withLeadTime($leadTime))
            ->when($sort === 'name', fn ($q) => $q->orderBy('company_name'))
            ->when($sort === 'lead_time', fn ($q) => $q->orderBy('lead_time_days'))
            ->when(! in_array($sort, ['name', 'lead_time'], true), fn ($q) => $q->latest())
            ->paginate(12)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search', 'status', 'leadTime', 'sort'));
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('suppliers.create', [
            'supplier' => new Supplier([
                'code' => (new Supplier())->nextCode(),
                'status' => Supplier::STATUS_ACTIVE,
                'payment_terms' => 'Net 30',
                'lead_time_days' => 7,
                'minimum_order_amount' => 0,
            ]),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);
        $data = $request->validated();
        $data['code'] = $data['code'] ?: (new Supplier())->nextCode();
        $data['minimum_order_amount'] = $data['minimum_order_amount'] ?? 0;

        $supplier = Supplier::create($data);

        ActivityLog::record(
            ActivityLog::MODULE_SUPPLIER,
            ActivityLog::ACTION_CREATED,
            "Supplier {$supplier->company_name} was created.",
            $supplier,
        );

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier added successfully.');
    }

    public function show(Supplier $supplier): View
    {
        $this->authorize('view', $supplier);

        $metrics = Cache::remember(
            "supplier:{$supplier->id}:metrics",
            (int) config('replenishment.cache_ttl_seconds', 60),
            fn (): array => $supplier->performanceMetrics(),
        );

        return view('suppliers.show', [
            'supplier' => $supplier,
            'metrics' => $metrics,
            'recentOrders' => $supplier->purchaseOrders()
                ->with('items.product:id,name')
                ->latest('order_date')
                ->take(5)
                ->get(),
            'products' => $supplier->products()->orderBy('name')->get(),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);
        $data = $request->validated();
        $data['minimum_order_amount'] = $data['minimum_order_amount'] ?? 0;

        $supplier->update($data);
        Cache::forget("supplier:{$supplier->id}:metrics");

        ActivityLog::record(
            ActivityLog::MODULE_SUPPLIER,
            ActivityLog::ACTION_UPDATED,
            "Supplier {$supplier->company_name} was updated.",
            $supplier,
        );

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        $openOrders = $supplier->purchaseOrders()
            ->whereIn('status', [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_PENDING,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SENT,
            ])->count();

        if ($openOrders > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', "Cannot delete {$supplier->company_name}: {$openOrders} open purchase order(s) still reference it.");
        }

        $supplier->delete();
        Cache::forget("supplier:{$supplier->id}:metrics");

        ActivityLog::record(
            ActivityLog::MODULE_SUPPLIER,
            ActivityLog::ACTION_DEACTIVATED,
            "Supplier {$supplier->company_name} was soft-deleted.",
            $supplier,
        );

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier soft-deleted. Historical purchase orders remain intact.');
    }

    public function restore(Supplier $supplier): RedirectResponse
    {
        $this->authorize('restore', $supplier);
        $supplier->restore();

        ActivityLog::record(
            ActivityLog::MODULE_SUPPLIER,
            ActivityLog::ACTION_RESTORED,
            "Supplier {$supplier->company_name} was restored.",
            $supplier,
        );

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier restored successfully.');
    }
}

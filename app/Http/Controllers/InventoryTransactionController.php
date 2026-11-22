<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreInventoryTransactionRequest;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $productId = $request->integer('product_id');
        $type = $request->string('type')->toString();
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $type = array_key_exists($type, InventoryTransaction::TYPES) ? $type : '';

        $transactions = InventoryTransaction::query()
            ->with(['product:id,name,sku,quantity', 'user:id,name'])
            ->when($productId > 0, fn ($query) => $query->where('product_id', $productId))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%"));
                });
            })
            ->betweenDates($from !== '' ? $from : null, $to !== '' ? $to : null)
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'types' => InventoryTransaction::TYPES,
            'filters' => compact('search', 'productId', 'type', 'from', 'to'),
        ]);
    }

    public function create(): View
    {
        return view('transactions.create', [
            'products' => Product::orderBy('name')->get(),
            'types' => InventoryTransaction::TYPES,
        ]);
    }

    public function store(StoreInventoryTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $transaction = DB::transaction(function () use ($data) {
                return InventoryTransaction::create($data);
            });
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Stock movement recorded. Inventory has been updated.');
    }

    public function show(InventoryTransaction $transaction): View
    {
        $transaction->load(['product.brand', 'product.category', 'user']);

        return view('transactions.show', ['transaction' => $transaction]);
    }

    public function destroy(InventoryTransaction $transaction): RedirectResponse
    {
        try {
            DB::transaction(function () use ($transaction) {
                $transaction->delete();
            });
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction removed and the stock balance was reversed.');
    }

    /** Stock adjustment: enter a physical count, store the difference as an audit row. */
    public function createAdjustment(): View
    {
        return view('transactions.adjust', [
            'products' => Product::orderBy('name')->get(),
            'reasons' => InventoryTransaction::ADJUSTMENT_REASONS,
        ]);
    }

    public function storeAdjustment(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::query()->findOrFail($data['product_id']);
        $difference = (int) $data['physical_count'] - (int) $product->quantity;

        if ($difference === 0) {
            return back()->withInput()->with('error', 'The physical count matches the system quantity; no adjustment is needed.');
        }

        $transaction = DB::transaction(function () use ($data, $product) {
            return InventoryTransaction::create([
                'product_id' => $product->id,
                'type' => InventoryTransaction::TYPE_ADJUSTMENT,
                'quantity' => (int) $data['physical_count'],
                'reference' => 'ADJ-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Stock adjustment recorded and inventory updated.');
    }
}

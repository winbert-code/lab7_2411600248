<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $brandId = $request->integer('brand_id');
        $categoryId = $request->integer('category_id');
        $status = $request->string('status')->toString();

        if (! in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'all';
        }

        $products = Product::query()
            ->with(['brand', 'category'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%");
                });
            })
            ->when($brandId > 0, fn ($query) => $query->where('brand_id', $brandId))
            ->when($categoryId > 0, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status === 'active', fn ($query) => $query->active())
            ->when($status === 'inactive', fn ($query) => $query->inactive())
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'search' => $search,
            'brandId' => $brandId,
            'categoryId' => $categoryId,
            'status' => $status,
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'product' => new Product(),
            'brands' => $this->brandOptions(new Product()),
            'categories' => $this->categoryOptions(new Product()),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $initialQuantity = (int) $data['quantity'];

        // The observer applies the stock movement when the audit row is created,
        // so the product itself starts at zero.
        $data['quantity'] = 0;

        try {
            $product = DB::transaction(function () use ($data, $initialQuantity) {
                $product = Product::create($data);

                if ($initialQuantity > 0) {
                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'type' => InventoryTransaction::TYPE_IN,
                        'quantity' => $initialQuantity,
                        'reference' => 'Initial stock',
                        'reason' => 'Initial stock',
                        'user_id' => auth()->id(),
                    ]);
                }

                return $product;
            });
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        ActivityLog::record(
            ActivityLog::MODULE_PRODUCT,
            ActivityLog::ACTION_CREATED,
            "{$product->name} ({$product->sku}) was added with {$initialQuantity} units.",
            $product,
        );

        return redirect()->route('products.show', $product)
            ->with('success', "{$product->name} was added to the inventory.");
    }

    public function show(Request $request, Product $product): View
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $product->load(['brand', 'category']);

        $transactions = $product->transactions()
            ->with('user')
            ->betweenDates($from !== '' ? $from : null, $to !== '' ? $to : null)
            ->latest()
            ->orderByDesc('id')
            ->get();

        return view('products.show', [
            'product' => $product,
            'transactions' => $transactions,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'brands' => $this->brandOptions($product),
            'categories' => $this->categoryOptions($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $quantityBefore = (int) $product->quantity;
        $newQuantity = (int) $data['quantity'];

        // Keep the quantity unchanged here; the observer updates it from the
        // adjustment transaction so the audit trail stays authoritative.
        $data['quantity'] = $quantityBefore;

        try {
            DB::transaction(function () use ($data, $product, $newQuantity, $quantityBefore) {
                $product->update($data);

                if ($newQuantity !== $quantityBefore) {
                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'type' => InventoryTransaction::TYPE_ADJUSTMENT,
                        'quantity' => $newQuantity,
                        'reference' => 'Stock adjustment',
                        'reason' => 'System error',
                        'user_id' => auth()->id(),
                    ]);
                }
            });
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        ActivityLog::record(
            ActivityLog::MODULE_PRODUCT,
            ActivityLog::ACTION_UPDATED,
            "{$product->name} was updated. Stock is now {$newQuantity} units.",
            $product,
        );

        return redirect()->route('products.show', $product)
            ->with('success', "{$product->name} was updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->deactivate();

        ActivityLog::record(
            ActivityLog::MODULE_PRODUCT,
            ActivityLog::ACTION_DEACTIVATED,
            "{$name} was marked inactive. Its stock record was kept.",
            $product,
        );

        return redirect()->route('products.index')
            ->with('success', "{$name} was marked inactive. Its inventory record and stock history were kept.");
    }

    public function restore(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->activate();

        ActivityLog::record(
            ActivityLog::MODULE_PRODUCT,
            ActivityLog::ACTION_RESTORED,
            "{$name} was restored to active stock.",
            $product,
        );

        return redirect()->route('products.index')
            ->with('success', "{$name} is active again.");
    }

    /**
     * Inactive brands cannot be chosen for new stock, but an inactive brand
     * already assigned to this product must stay visible on the edit form.
     */
    private function brandOptions(Product $product)
    {
        $brands = Brand::active()->orderBy('name')->get();

        if ($product->brand && ! $product->brand->isActive()) {
            $brands->push($product->brand);
        }

        return $brands->sortBy('name')->values();
    }

    /**
     * Inactive categories cannot be chosen for new stock, but an inactive
     * category already assigned to this product must stay visible on edit.
     */
    private function categoryOptions(Product $product)
    {
        $categories = Category::active()->orderBy('name')->get();

        if ($product->category && ! $product->category->isActive()) {
            $categories->push($product->category);
        }

        return $categories->sortBy('name')->values();
    }
}

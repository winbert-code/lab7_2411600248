<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());

        $products = Product::query()
            ->with(['brand', 'category'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.index', [
            'products' => $products,
            'search' => $search,
            'activeProducts' => Product::active()->count(),
            'totalUnits' => (int) Product::active()->sum('quantity'),
            'lowStock' => Product::active()->lowStock()->count(),
            'outOfStock' => Product::active()->outOfStock()->count(),
        ]);
    }
}

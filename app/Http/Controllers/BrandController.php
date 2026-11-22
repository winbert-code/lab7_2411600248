<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        if (! in_array($status, [Brand::STATUS_ACTIVE, Brand::STATUS_INACTIVE], true)) {
            $status = '';
        }

        $brands = Brand::query()
            ->withCount(['products as active_products_count' => fn ($query) => $query->where('status', Product::STATUS_ACTIVE)])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('brands.index', [
            'brands' => $brands,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('brands.create', ['brand' => new Brand()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:brands,name'],
            'slug' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
        ]);

        $brand = Brand::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $data['slug'] ?? null),
            'description' => $data['description'] ?? null,
            'status' => Brand::STATUS_ACTIVE,
        ]);

        ActivityLog::record(
            ActivityLog::MODULE_BRAND,
            ActivityLog::ACTION_CREATED,
            "{$brand->name} was added as a brand.",
            $brand,
        );

        return redirect()->route('brands.index')
            ->with('success', "{$data['name']} was added as a brand.");
    }

    public function edit(Brand $brand): View
    {
        return view('brands.edit', ['brand' => $brand]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:brands,name,'.$brand->id],
            'slug' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
        ]);

        $brand->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $data['slug'] ?? null, $brand->id),
            'description' => $data['description'] ?? null,
        ]);

        ActivityLog::record(
            ActivityLog::MODULE_BRAND,
            ActivityLog::ACTION_UPDATED,
            "{$brand->name} brand details were updated.",
            $brand,
        );

        return redirect()->route('brands.index')
            ->with('success', "{$brand->name} was updated.");
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $name = $brand->name;
        $brand->deactivate();

        ActivityLog::record(
            ActivityLog::MODULE_BRAND,
            ActivityLog::ACTION_DEACTIVATED,
            "{$name} brand was deactivated. Its products were kept.",
            $brand,
        );

        return redirect()->route('brands.index')
            ->with('success', "{$name} was deactivated. Its products were kept and you can restore it anytime.");
    }

    public function restore(Brand $brand): RedirectResponse
    {
        $brand->activate();

        ActivityLog::record(
            ActivityLog::MODULE_BRAND,
            ActivityLog::ACTION_RESTORED,
            "{$brand->name} brand was restored for stock again.",
            $brand,
        );

        return redirect()->route('brands.index')
            ->with('success', "{$brand->name} was restored and is available for stock again.");
    }

    private function uniqueSlug(string $name, ?string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug !== '' && $slug !== null ? $slug : $name);
        $base = $base !== '' ? $base : 'item';
        $candidate = $base;
        $suffix = 2;

        while (Brand::when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}

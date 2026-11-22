<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        if (! in_array($status, [Category::STATUS_ACTIVE, Category::STATUS_INACTIVE], true)) {
            $status = '';
        }

        $categories = Category::query()
            ->withCount(['products as active_products_count' => fn ($query) => $query->where('status', Product::STATUS_ACTIVE)])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new Category()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:categories,name'],
            'slug' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
        ]);

        $category = Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $data['slug'] ?? null),
            'description' => $data['description'] ?? null,
            'status' => Category::STATUS_ACTIVE,
        ]);

        ActivityLog::record(
            ActivityLog::MODULE_CATEGORY,
            ActivityLog::ACTION_CREATED,
            "{$category->name} was added as a category.",
            $category,
        );

        return redirect()->route('categories.index')
            ->with('success', "{$data['name']} was added as a category.");
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:categories,name,'.$category->id],
            'slug' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $data['slug'] ?? null, $category->id),
            'description' => $data['description'] ?? null,
        ]);

        ActivityLog::record(
            ActivityLog::MODULE_CATEGORY,
            ActivityLog::ACTION_UPDATED,
            "{$category->name} category details were updated.",
            $category,
        );

        return redirect()->route('categories.index')
            ->with('success', "{$category->name} was updated.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->deactivate();

        ActivityLog::record(
            ActivityLog::MODULE_CATEGORY,
            ActivityLog::ACTION_DEACTIVATED,
            "{$name} category was deactivated. Its products were kept.",
            $category,
        );

        return redirect()->route('categories.index')
            ->with('success', "{$name} was deactivated. Its products were kept and you can restore it anytime.");
    }

    public function restore(Category $category): RedirectResponse
    {
        $category->activate();

        ActivityLog::record(
            ActivityLog::MODULE_CATEGORY,
            ActivityLog::ACTION_RESTORED,
            "{$category->name} category was restored for stock again.",
            $category,
        );

        return redirect()->route('categories.index')
            ->with('success', "{$category->name} was restored and is available for stock again.");
    }

    private function uniqueSlug(string $name, ?string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug !== '' && $slug !== null ? $slug : $name);
        $base = $base !== '' ? $base : 'item';
        $candidate = $base;
        $suffix = 2;

        while (Category::when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}

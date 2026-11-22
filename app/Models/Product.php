<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'brand_id',
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'description',
        'quantity',
        'reorder_level',
        'safety_stock',
        'unit_price',
        'cost_price',
        'lead_time_days',
        'package_size',
        'min_order_qty',
        'supplier',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reorder_level' => 'integer',
            'safety_stock' => 'integer',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'lead_time_days' => 'integer',
            'package_size' => 'integer',
            'min_order_qty' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** The preferred supplier (denormalised for quick display). */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** All suppliers that can fulfil this product, with pricing / lead time on the pivot. */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot(['unit_cost', 'lead_time_days', 'minimum_order_qty', 'is_primary'])
            ->withTimestamps();
    }

    /** Resolves the related supplier despite the legacy string column shadowing it. */
    public function getSupplierRecordAttribute(): ?Supplier
    {
        if ($this->relationLoaded('supplier')) {
            $related = $this->getRelation('supplier');

            return $related instanceof Supplier ? $related : null;
        }

        return $this->supplier_id ? $this->supplier()->first() : null;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(InventoryTransaction::class)->latestOfMany();
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query): void
    {
        $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function scopeOutOfStock(Builder $query): void
    {
        $query->where('quantity', '<=', 0);
    }

    public function scopeInCategory(Builder $query, int|string $category): Builder
    {
        return is_numeric($category)
            ? $query->where('category_id', (int) $category)
            : $query->whereHas('category', fn (Builder $q) => $q->where('slug', $category));
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"))
                ->orWhereHas('supplier', fn (Builder $s) => $s->where('company_name', 'like', "%{$term}%"));
        });
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isLowStock(): bool
    {
        return (int) $this->quantity <= (int) $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return (int) $this->quantity <= 0;
    }

    public function deactivate(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    public function activate(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function getStockStatusAttribute(): string
    {
        return $this->stockStatus();
    }

    public function getStockStatusColorAttribute(): string
    {
        return match (true) {
            $this->isOutOfStock() => 'danger',
            $this->isLowStock() => 'warning',
            default => 'success',
        };
    }

    public function stockStatus(): string
    {
        return match (true) {
            $this->isOutOfStock() => 'Out of Stock',
            $this->isLowStock() => 'Low Stock',
            default => 'In Stock',
        };
    }

    public function availabilityStatus(): string
    {
        return $this->isInactive() ? 'Inactive' : $this->stockStatus();
    }

    public function stockValue(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }

    /** Average units per day consumed over the configured usage window. */
    public function averageDailyUsage(int $days = 30): float
    {
        $window = max(1, $days);

        $units = (float) DB::table('inventory_transactions')
            ->where('product_id', $this->id)
            ->where('type', InventoryTransaction::TYPE_OUT)
            ->where('created_at', '>=', now()->subDays($window))
            ->sum('quantity');

        return round($units / $window, 2);
    }
}

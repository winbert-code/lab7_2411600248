<?php

namespace App\Models;

use App\Observers\InventoryTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(InventoryTransactionObserver::class)]
class InventoryTransaction extends Model
{
    use HasFactory;

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPES = [
        self::TYPE_IN => 'Stock in',
        self::TYPE_OUT => 'Stock out',
        self::TYPE_ADJUSTMENT => 'Adjustment',
    ];

    public const ADJUSTMENT_REASONS = [
        'Physical count' => 'Physical count',
        'Damaged goods' => 'Damaged goods',
        'Theft / loss' => 'Theft / loss',
        'System error' => 'System error',
        'Return' => 'Return',
        'Other' => 'Other',
    ];

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reference',
        'reason',
        'notes',
        'user_id',
        'previous_quantity',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'previous_quantity' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStockIn(Builder $query): void
    {
        $query->where('type', self::TYPE_IN);
    }

    public function scopeStockOut(Builder $query): void
    {
        $query->where('type', self::TYPE_OUT);
    }

    public function scopeAdjustments(Builder $query): void
    {
        $query->where('type', self::TYPE_ADJUSTMENT);
    }

    public function scopeRecent(Builder $query): void
    {
        $query->latest()->orderByDesc('id');
    }

    public function scopeForProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }
    }

    public function isStockIn(): bool
    {
        return $this->type === self::TYPE_IN;
    }

    public function isStockOut(): bool
    {
        return $this->type === self::TYPE_OUT;
    }

    public function isAdjustment(): bool
    {
        return $this->type === self::TYPE_ADJUSTMENT;
    }

    /** Signed movement, useful for charts and the +/- quantity column. */
    public function signedQuantity(): int
    {
        return match ($this->type) {
            self::TYPE_OUT => -$this->quantity,
            self::TYPE_ADJUSTMENT => $this->balance_after !== null && $this->previous_quantity !== null
                ? $this->balance_after - $this->previous_quantity
                : 0,
            default => $this->quantity,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IN => 'success',
            self::TYPE_OUT => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            default => 'secondary',
        };
    }

    public function getReferenceNumberAttribute(): string
    {
        return $this->reference ?: '—';
    }
}

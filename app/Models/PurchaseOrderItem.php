<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'quantity_ordered',
        'unit_cost',
        'unit_price',
        'received_quantity',
    ];

    protected $appends = ['line_total', 'outstanding_quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'received_quantity' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /** Legacy alias used by the previous lab views. */
    public function order(): BelongsTo
    {
        return $this->purchaseOrder();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Price is frozen at order time so later catalog changes cannot alter history. */
    public function getUnitPriceAttribute(): float
    {
        return (float) $this->unit_cost;
    }

    public function setUnitPriceAttribute(float|int|string $value): void
    {
        $this->attributes['unit_cost'] = $value;
    }

    public function getQuantityOrderedAttribute(): int
    {
        return (int) $this->quantity;
    }

    public function setQuantityOrderedAttribute(int $value): void
    {
        $this->attributes['quantity'] = $value;
    }

    public function getLineTotalAttribute(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }

    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->received_quantity);
    }

    public function lineTotal(): float
    {
        return $this->line_total;
    }
}

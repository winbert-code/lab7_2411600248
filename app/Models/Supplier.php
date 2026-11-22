<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'code',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'payment_terms',
        'lead_time_days',
        'minimum_order_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
            'minimum_order_amount' => 'decimal:2',
        ];
    }

    /** Products this supplier can supply (many-to-many with pivot data). */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['unit_cost', 'lead_time_days', 'minimum_order_qty', 'is_primary'])
            ->withTimestamps();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /** Polymorphic relation used for supplier-specific notes / audit entries. */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /** @param  Builder<Supplier>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /** @param  Builder<Supplier>  $query */
    public function scopeInactive(Builder $query): void
    {
        $query->where('status', self::STATUS_INACTIVE);
    }

    /** @param  Builder<Supplier>  $query */
    public function scopeWithLeadTime(Builder $query, int $days): void
    {
        $query->where('lead_time_days', '<=', $days);
    }

    public function getFullContactAttribute(): string
    {
        return "{$this->contact_person} · {$this->email} · {$this->phone}";
    }

    public function getPerformanceRatingAttribute(): float
    {
        return $this->performanceMetrics()['on_time_percentage'];
    }

    /** Cached by the caller (widgets / report) via Cache::remember. */
    public function performanceMetrics(): array
    {
        $orders = $this->purchaseOrders();

        $totalOrders = (int) (clone $orders)->count();
        $receivedOrders = (int) (clone $orders)->where('status', PurchaseOrder::STATUS_RECEIVED)->count();

        $onTime = (int) (clone $orders)
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->whereNotNull('received_date')
            ->whereColumn('received_date', '<=', 'expected_delivery_date')
            ->count();

        // Calculate in PHP rather than using MySQL's DATEDIFF() so the same
        // metrics work with SQLite in the automated test suite.
        $deliveredOrders = (clone $orders)
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->whereNotNull('received_date')
            ->whereNotNull('order_date')
            ->get(['order_date', 'received_date']);

        $deliveryDays = $deliveredOrders->map(
            fn (PurchaseOrder $order): int => Carbon::parse($order->order_date)
                ->diffInDays(Carbon::parse($order->received_date)),
        );
        $averageDeliveryDays = $deliveryDays->isNotEmpty()
            ? (float) $deliveryDays->average()
            : 0.0;

        $totals = \Illuminate\Support\Facades\DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.supplier_id', $this->id)
            ->whereNull('purchase_orders.deleted_at')
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity), 0) as ordered')
            ->selectRaw('COALESCE(SUM(purchase_order_items.received_quantity), 0) as received')
            ->first();

        $ordered = (int) ($totals->ordered ?? 0);
        $received = (int) ($totals->received ?? 0);
        $totalValue = (float) (clone $orders)->whereNotNull('approved_at')->sum('total');

        return [
            'total_orders' => $totalOrders,
            'received_orders' => $receivedOrders,
            'on_time_orders' => $onTime,
            'on_time_percentage' => $receivedOrders > 0 ? round($onTime / $receivedOrders * 100, 1) : 0.0,
            'average_delivery_days' => round($averageDeliveryDays, 1),
            'order_accuracy' => $ordered > 0 ? round($received / $ordered * 100, 1) : 0.0,
            'total_value' => $totalValue,
        ];
    }

    public function nextCode(): string
    {
        $last = static::withTrashed()->max('id') + 1;

        return 'SUP-'.str_pad((string) $last, 4, '0', STR_PAD_LEFT);
    }
}

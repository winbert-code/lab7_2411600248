<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SENT = 'sent';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending approval',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_SENT => 'Sent to supplier',
        self::STATUS_RECEIVED => 'Received',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const APPROVAL_FIRST = 1;
    public const APPROVAL_FINAL = 2;

    protected $fillable = [
        'reference_number',
        'order_number',
        'supplier_id',
        'supplier',
        'status',
        'order_date',
        'expected_date',
        'expected_delivery_date',
        'received_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'currency',
        'notes',
        'terms',
        'rejection_reason',
        'user_id',
        'created_by',
        'approved_by',
        'approved_at',
        'sent_at',
        'approval_level',
        'partially_received',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'expected_delivery_date' => 'date',
            'received_date' => 'date',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'approval_level' => 'integer',
            'partially_received' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Legacy user_id is kept so earlier Lab 6 records still resolve. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approval/receipt history notes. Named distinctly from the legacy
     * `notes` text column, which is still used for free-form order notes.
     */
    public function approvalNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /** @param  Builder<PurchaseOrder>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    /** @param  Builder<PurchaseOrder>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotIn('status', [
            self::STATUS_RECEIVED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
        ]);
    }

    /** @param  Builder<PurchaseOrder>  $query */
    public function scopeByStatus(Builder $query, ?string $status): void
    {
        if ($status !== null && $status !== '' && array_key_exists($status, self::STATUSES)) {
            $query->where('status', $status);
        }
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_APPROVED], true);
    }

    public function isHighValue(): bool
    {
        return (float) $this->total > (float) config('replenishment.high_value_threshold', 50000);
    }

    public function isAwaitingFinalApproval(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->isHighValue()
            && (int) $this->approval_level === self::APPROVAL_FIRST;
    }

    /** Recalculate subtotal, tax and grand total from the stored line items. */
    public function calculateTotal(): float
    {
        $subtotal = (float) $this->items()->sum(\Illuminate\Support\Facades\DB::raw('quantity * unit_cost'));
        $rate = (float) ($this->tax_rate ?: config('replenishment.tax_rate', 12));
        $tax = round($subtotal * $rate / 100, 2);

        $this->subtotal = round($subtotal, 2);
        $this->tax_rate = $rate;
        $this->tax_amount = $tax;
        $this->total = round($subtotal + $tax, 2);

        if ($this->exists) {
            $this->saveQuietly();
        }

        return (float) $this->total;
    }

    public function totalAmount(): float
    {
        return (float) ($this->total ?: $this->items->sum(fn (PurchaseOrderItem $item) => $item->lineTotal()));
    }

    public function totalQuantity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getReferenceNumberAttribute(?string $value): string
    {
        return $value ?: (string) $this->order_number;
    }

    /**
     * The legacy `supplier` column is a plain string, so it shadows the
     * relation of the same name. This accessor resolves the related model
     * without breaking the Lab 6 string attribute.
     */
    public function getSupplierRecordAttribute(): ?Supplier
    {
        if ($this->relationLoaded('supplier')) {
            $related = $this->getRelation('supplier');

            return $related instanceof Supplier ? $related : null;
        }

        return $this->supplier_id ? $this->supplier()->first() : null;
    }

    public function getSupplierNameAttribute(): string
    {
        return $this->supplier_record?->company_name ?: (string) $this->supplier;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RECEIVED, self::STATUS_APPROVED => 'success',
            self::STATUS_SENT => 'info',
            self::STATUS_PENDING => 'warning',
            self::STATUS_REJECTED, self::STATUS_CANCELLED => 'secondary',
            default => 'primary',
        };
    }

    public static function generateReferenceNumber(): string
    {
        do {
            $number = 'PO-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (static::withTrashed()->where('reference_number', $number)->exists());

        return $number;
    }

    /** Backwards-compatible alias used by the earlier lab views. */
    public static function generateNumber(): string
    {
        return static::generateReferenceNumber();
    }
}

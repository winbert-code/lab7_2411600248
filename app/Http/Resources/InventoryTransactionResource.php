<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InventoryTransaction */
class InventoryTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'type_color' => $this->type_color,
            'quantity' => (int) $this->quantity,
            'signed_quantity' => $this->signedQuantity(),
            'previous_quantity' => $this->previous_quantity,
            'balance_after' => $this->balance_after,
            'reference' => $this->reference_number,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'url' => route('products.show', $this->product),
            ]),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name ?? 'System'),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}

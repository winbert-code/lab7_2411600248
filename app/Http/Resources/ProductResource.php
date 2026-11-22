<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'quantity' => (int) $this->quantity,
            'reorder_level' => (int) $this->reorder_level,
            'status' => $this->availabilityStatus(),
            'status_color' => $this->stock_status_color,
            'unit_price' => (float) $this->unit_price,
            'stock_value' => $this->stockValue(),
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'url' => route('products.show', $this),
        ];
    }
}

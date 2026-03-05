<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variants = collect($this->whenLoaded('variants'));
        $images = collect($this->whenLoaded('images'))->sortBy([
            ['is_primary', 'desc'],
            ['position', 'asc'],
        ])->values();

        $stock = $variants->isNotEmpty()
            ? (int) $variants->sum('stock')
            : (int) ($this->stock_global ?? 0);

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => $this->category?->name,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'stock' => $stock,
            'stock_global' => $this->stock_global,
            'preorder_shipping_date' => optional($this->preorder_shipping_date)?->toDateString(),
            'price' => (float) $this->price,
            'description' => $this->description_short ?: $this->description,
            'description_short' => $this->description_short,
            'description_long' => $this->description_long,
            'details' => $this->details ?? [],
            'image' => $images->first()?->image_path,
            'images' => $images->pluck('image_path')->all(),
            'variants' => $variants->map(fn ($variant) => [
                'id' => $variant->id,
                'size' => $variant->size,
                'stock' => (int) $variant->stock,
                'sku' => $variant->sku,
            ])->values()->all(),
            'is_active' => (bool) $this->is_active,
            'is_dropship' => (bool) $this->is_dropship,
        ];
    }
}

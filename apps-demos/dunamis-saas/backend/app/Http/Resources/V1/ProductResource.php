<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageUrl = null;
        if ($this->image_path) {
            $imageUrl = str_starts_with((string) $this->image_path, 'http')
                ? $this->image_path
                : url(Storage::url($this->image_path));
        }

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'cost_ars' => (float) ($this->cost_ars ?? 0),
            'cost_usd' => $this->cost_usd !== null ? (float) $this->cost_usd : null,
            'sale_price' => (float) ($this->sale_price ?? 0),
            'stock_quantity' => (int) ($this->stock_quantity ?? 0),
            'min_stock_quantity' => (int) ($this->min_stock_quantity ?? 0),
            'image_url' => $imageUrl,
            'image_path' => $this->image_path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

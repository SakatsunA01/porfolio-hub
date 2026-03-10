<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'moneda_cobro' => $this->moneda_cobro,
            'exchange_rate_used' => $this->exchange_rate_used !== null ? (float) $this->exchange_rate_used : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client?->id,
                'name' => $this->client?->name,
                'email' => $this->client?->email,
            ]),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

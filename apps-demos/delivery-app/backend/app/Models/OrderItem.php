<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'combo_id',
        'bundle_id',
        'snapshot_name',
        'qty',
        'unit_base_price',
        'unit_extras_total',
        'unit_price',
        'line_subtotal',
        'line_extras_total',
        'line_total',
        'modifiers',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_base_price' => 'float',
        'unit_extras_total' => 'float',
        'unit_price' => 'float',
        'line_subtotal' => 'float',
        'line_extras_total' => 'float',
        'line_total' => 'float',
        'modifiers' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}


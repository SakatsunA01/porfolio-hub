<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer',
        'address',
        'payment_method',
        'payment_status',
        'items',
        'subtotal',
        'extras_total',
        'total',
        'cash_received',
        'change_amount',
        'status',
        'employee_id',
        'driver_id',
        'eta_min',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'float',
        'extras_total' => 'float',
        'total' => 'float',
        'cash_received' => 'float',
        'change_amount' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_menu_id',
        'item_type',
        'item_id',
        'promo_price',
        'sort_order',
    ];

    protected $casts = [
        'promo_price' => 'float',
        'sort_order' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(DailyMenu::class, 'daily_menu_id');
    }
}


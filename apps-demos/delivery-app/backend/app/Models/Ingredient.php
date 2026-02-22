<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'additional_price',
        'stock_quantity',
        'unit_cost',
        'is_active',
    ];

    protected $casts = [
        'additional_price' => 'float',
        'stock_quantity' => 'float',
        'unit_cost' => 'float',
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['is_default', 'is_removable', 'additional_price'])
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'commerce_id',
        'category_id',
        'name',
        'slug',
        'description',
        'description_short',
        'description_long',
        'price',
        'type',
        'stock_global',
        'preorder_shipping_date',
        'is_active',
        'is_dropship',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'price' => 'decimal:2',
        'stock_global' => 'integer',
        'preorder_shipping_date' => 'date',
        'is_active' => 'boolean',
        'is_dropship' => 'boolean',
    ];

    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}

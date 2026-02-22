<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'sku',
        'commerce_id',
        'category_id',
        'name',
        'description',
        'cost_price',
        'cost_ars',
        'cost_usd',
        'sale_price',
        'price',
        'details',
        'stock_quantity',
        'min_stock_quantity',
        'min_stock_alert',
        'image_path',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'cost_ars' => 'decimal:2',
        'cost_usd' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'price' => 'decimal:2',
        'details' => 'array',
        'stock_quantity' => 'integer',
        'min_stock_quantity' => 'integer',
        'min_stock_alert' => 'integer',
    ];
}

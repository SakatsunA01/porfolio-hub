<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use BelongsToTenant;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category',
        'tenant_id',
        'base_price',
        'prep_min',
        'stock_quantity',
        'min_stock_quantity',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'float',
        'prep_min' => 'integer',
        'stock_quantity' => 'integer',
        'min_stock_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class)
            ->withPivot(['is_default', 'is_removable', 'additional_price'])
            ->withTimestamps();
    }

    public function extras(): HasMany
    {
        return $this->hasMany(Extra::class);
    }

    public function combos(): BelongsToMany
    {
        return $this->belongsToMany(Combo::class)
            ->withPivot(['quantity'])
            ->withTimestamps();
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class)
            ->withPivot(['quantity'])
            ->withTimestamps();
    }
}

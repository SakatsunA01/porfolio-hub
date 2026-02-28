<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Combo extends Model implements HasMedia
{
    use HasFactory;
    use BelongsToTenant;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'base_price',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'float',
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['quantity'])
            ->withTimestamps();
    }
}

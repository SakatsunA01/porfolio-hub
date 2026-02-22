<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'commerce_id'];

    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

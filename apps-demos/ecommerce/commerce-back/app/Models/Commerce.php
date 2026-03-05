<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Commerce extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo_path',
        'brand_palette',
        'manifesto_text',
        'philosophy_text',
        'contact_text',
        'team_text',
    ];

    protected $casts = [
        'brand_palette' => 'array',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}

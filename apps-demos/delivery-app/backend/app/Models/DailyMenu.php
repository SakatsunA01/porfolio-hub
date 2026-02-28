<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyMenu extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'image_url',
        'is_active',
        'slot',
        'weekdays',
        'active_from',
        'active_to',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weekdays' => 'array',
        'active_from' => 'datetime',
        'active_to' => 'datetime',
        'priority' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DailyMenuItem::class)->orderBy('sort_order')->orderBy('id');
    }
}


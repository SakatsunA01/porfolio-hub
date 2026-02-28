<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'plan_key',
        'billing_status',
        'monthly_fee_ars',
        'next_billing_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monthly_fee_ars' => 'integer',
        'next_billing_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

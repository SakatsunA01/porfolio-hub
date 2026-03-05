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
        'shipping_fee_ars',
        'free_shipping_threshold_ars',
        'logo_url',
        'brand_theme_key',
        'brand_primary_color',
        'business_phone',
        'business_address',
        'business_hours_json',
        'next_billing_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monthly_fee_ars' => 'integer',
        'shipping_fee_ars' => 'integer',
        'free_shipping_threshold_ars' => 'integer',
        'business_hours_json' => 'array',
        'next_billing_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

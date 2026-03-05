<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'customer_key',
        'display_name',
        'phone',
        'last_address',
        'apartment',
        'address_reference',
        'delivery_notes',
        'avatar_url',
        'is_blocked',
        'notes',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];
}

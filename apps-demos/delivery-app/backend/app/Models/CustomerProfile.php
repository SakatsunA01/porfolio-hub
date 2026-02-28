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
        'customer_key',
        'display_name',
        'last_address',
        'is_blocked',
        'notes',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];
}

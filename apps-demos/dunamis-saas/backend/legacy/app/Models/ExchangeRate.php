<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'moneda',
        'nombre',
        'compra',
        'venta',
        'fecha_actualizacion',
    ];

    protected $casts = [
        'fecha_actualizacion' => 'datetime',
        'compra' => 'decimal:4',
        'venta' => 'decimal:4',
    ];
}

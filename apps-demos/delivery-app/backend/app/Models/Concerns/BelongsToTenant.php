<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (!property_exists($model, 'fillable')) {
                return;
            }

            if (!in_array('tenant_id', $model->getFillable(), true)) {
                return;
            }

            if (!empty($model->tenant_id)) {
                return;
            }

            $model->tenant_id = Auth::user()?->tenant_id;
        });
    }
}


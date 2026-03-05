<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->admin_sn === true;
    }

    public function create(User $user): bool
    {
        return $user->admin_sn === true;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->admin_sn === true;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->admin_sn === true;
    }
}

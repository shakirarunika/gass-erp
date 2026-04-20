<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

/**
 * Policy untuk mengatur akses CRUD pada master data Gudang.
 */
class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'ADMIN';
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->role === 'ADMIN';
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->role === 'ADMIN';
    }

    public function restore(User $user, Warehouse $warehouse): bool
    {
        return false;
    }

    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        return false;
    }
}

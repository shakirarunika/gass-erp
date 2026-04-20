<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

/**
 * Policy untuk mengatur akses CRUD pada master data Satuan.
 */
class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Unit $unit): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'ADMIN';
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->role === 'ADMIN';
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->role === 'ADMIN';
    }

    public function restore(User $user, Unit $unit): bool
    {
        return false;
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return false;
    }
}

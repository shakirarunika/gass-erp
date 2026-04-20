<?php

namespace App\Policies;

use App\Models\Plant;
use App\Models\User;

/**
 * Policy untuk mengatur akses CRUD pada master data Plant / Site.
 */
class PlantPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Plant $plant): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'ADMIN';
    }

    public function update(User $user, Plant $plant): bool
    {
        return $user->role === 'ADMIN';
    }

    public function delete(User $user, Plant $plant): bool
    {
        return $user->role === 'ADMIN';
    }

    public function restore(User $user, Plant $plant): bool
    {
        return false;
    }

    public function forceDelete(User $user, Plant $plant): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

/**
 * Policy untuk mengatur akses CRUD pada master data Departemen.
 */
class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Department $department): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'ADMIN';
    }

    public function update(User $user, Department $department): bool
    {
        return $user->role === 'ADMIN';
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->role === 'ADMIN';
    }

    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}

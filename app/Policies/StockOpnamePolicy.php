<?php

namespace App\Policies;

use App\Models\StockOpname;
use App\Models\User;

/**
 * Policy untuk mengatur akses CRUD pada Stock Opname.
 *
 * Semua user bisa melihat dan membuat draft opname.
 * Hanya ADMIN yang bisa mengedit (finalisasi) dan menghapus.
 */
class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockOpname $stockOpname): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, StockOpname $stockOpname): bool
    {
        if ($user->role === 'ADMIN') {
            return true;
        }

        // Staff hanya bisa edit opname yang masih DRAFT
        return $stockOpname->status === 'DRAFT';
    }

    public function delete(User $user, StockOpname $stockOpname): bool
    {
        return $user->role === 'ADMIN';
    }

    public function restore(User $user, StockOpname $stockOpname): bool
    {
        return false;
    }

    public function forceDelete(User $user, StockOpname $stockOpname): bool
    {
        return false;
    }
}

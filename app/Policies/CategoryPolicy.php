<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Policy untuk mengatur akses CRUD pada master data Kategori.
 *
 * Semua user bisa melihat, tapi hanya ADMIN yang bisa
 * membuat, mengubah, dan menghapus data kategori.
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'ADMIN';
    }

    public function update(User $user, Category $category): bool
    {
        return $user->role === 'ADMIN';
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->role === 'ADMIN';
    }

    public function restore(User $user, Category $category): bool
    {
        return false;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return false;
    }
}

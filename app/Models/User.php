<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Model User / Pengguna Sistem.
 *
 * Mendukung 2 role:
 * - ADMIN: Full akses ke seluruh fitur
 * - STAFF: Akses operasional gudang (terbatas)
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property int    $department_id
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /*
    |--------------------------------------------------------------------------
    | Filament Access Control
    |--------------------------------------------------------------------------
    */

    /**
     * Tentukan apakah user bisa mengakses panel Filament.
     *
     * Hanya user yang memiliki role (ADMIN/STAFF) yang diizinkan login.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return ! is_null($this->role);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    */

    /** Konfigurasi Spatie Activity Log — catat semua perubahan kolom. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Departemen tempat user ini ditugaskan. */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}

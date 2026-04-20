<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Plant / Site.
 *
 * Lokasi fisik operasional utama (induk dari gudang).
 * Setiap Plant bisa memiliki banyak gudang di bawahnya.
 *
 * @property int    $id
 * @property string $name
 * @property string $code
 * @property bool   $is_active
 */
class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Daftar gudang yang berada di bawah plant ini. */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }
}

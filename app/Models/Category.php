<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Kategori Barang.
 *
 * Digunakan untuk mengelompokkan barang dan sebagai
 * prefix pada kode barang yang di-generate otomatis.
 *
 * @property int    $id
 * @property string $name
 * @property string $code
 * @property bool   $is_active
 */
class Category extends Model
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

    /** Daftar barang yang termasuk kategori ini. */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}

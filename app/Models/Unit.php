<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Satuan Barang.
 *
 * Digunakan sebagai unit pengukuran pada barang
 * (contoh: Pieces, Kilogram, Roll, Liter).
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $code
 */
class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    /** Daftar barang yang menggunakan satuan ini. */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}

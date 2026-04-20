<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Departemen.
 *
 * Unit organisasi yang digunakan untuk mengelompokkan user
 * dan sebagai referensi pada transaksi pemakaian barang.
 *
 * @property int    $id
 * @property string $name
 * @property string $code
 */
class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    /** Daftar karyawan di departemen ini. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Gudang / Warehouse.
 *
 * Lokasi penyimpanan fisik barang yang berada di bawah sebuah Plant.
 * Setiap gudang memiliki kode unik dan menjadi referensi utama
 * pada transaksi dan monitoring stok.
 *
 * @property int    $id
 * @property int    $plant_id
 * @property string $name
 * @property string $code
 * @property bool   $is_active
 */
class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Plant (lokasi induk) dari gudang ini. */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /** Daftar stok barang yang ada di gudang ini. */
    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Stok Gudang (Inventory Stock).
 *
 * Menyimpan jumlah stok suatu barang di gudang tertentu.
 * Setiap kombinasi warehouse + item bersifat unik (unique index).
 * Data stok hanya boleh diubah melalui transaksi atau stock opname.
 *
 * @property int         $id
 * @property int         $warehouse_id
 * @property int         $item_id
 * @property int         $quantity
 * @property string|null $rack_location
 */
class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'quantity',
        'rack_location',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Gudang tempat stok ini berada. */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** Barang yang diwakili stok ini. */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
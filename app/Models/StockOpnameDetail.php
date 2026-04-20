<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Detail Stock Opname.
 *
 * Menyimpan hasil hitung fisik per item dalam sebuah stock opname,
 * termasuk stok sistem, stok fisik, selisih, dan harga snapshot.
 *
 * @property int         $id
 * @property int         $stock_opname_id
 * @property int         $item_id
 * @property int         $system_qty
 * @property int         $physical_qty
 * @property float       $cost_at_opname
 * @property string|null $description
 */
class StockOpnameDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'item_id',
        'system_qty',
        'physical_qty',
        'cost_at_opname',
        'description',
    ];

    protected $casts = [
        'system_qty'      => 'integer',
        'physical_qty'    => 'integer',
        'cost_at_opname'  => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Lifecycle Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (StockOpnameDetail $model) {
            // Snapshot harga modal saat opname dibuat
            if ($model->item_id && ! $model->cost_at_opname) {
                $model->cost_at_opname = $model->item->avg_cost ?? 0;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Barang yang dihitung dalam opname ini. */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** Induk stock opname. */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }
}

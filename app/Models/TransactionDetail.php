<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Detail Transaksi.
 *
 * Menyimpan item-level detail dari sebuah transaksi,
 * termasuk barang, quantity, dan harga per unit.
 *
 * @property int   $id
 * @property int   $transaction_id
 * @property int   $item_id
 * @property int   $quantity
 * @property float $price
 */
class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'item_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price'    => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Header transaksi yang memiliki detail ini. */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** Barang yang ditransaksikan. */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

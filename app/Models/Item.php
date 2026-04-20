<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Model Barang / Item.
 *
 * Entitas inti sistem inventory. Setiap item memiliki kode unik,
 * terhubung ke kategori, satuan, dan memiliki banyak stok di gudang.
 *
 * @property int    $id
 * @property int    $category_id
 * @property int    $unit_id
 * @property string $name
 * @property string $code
 * @property int    $min_stock
 * @property float  $avg_cost
 * @property bool   $is_active
 */
class Item extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'category_id',
        'unit_id',
        'name',
        'code',
        'min_stock',
        'avg_cost',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'avg_cost'  => 'decimal:2',
        'min_stock' => 'integer',
    ];

    /**
     * Konfigurasi Spatie Activity Log.
     *
     * Mencatat semua perubahan kolom, hanya yang berubah,
     * dan skip jika tidak ada perubahan.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Kategori yang memiliki item ini. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Satuan pengukuran item ini. */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** Daftar stok item ini di berbagai gudang. */
    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    /** Detail transaksi yang melibatkan item ini. */
    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }
}

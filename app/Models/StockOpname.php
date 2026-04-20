<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Stock Opname (Audit Fisik Stok).
 *
 * Stock Opname adalah proses pencocokkan antara stok di sistem
 * dengan stok fisik di gudang. Saat opname dibuat, sistem otomatis
 * mengisi semua item beserta stok sistemnya sebagai baseline.
 *
 * @property int    $id
 * @property int    $warehouse_id
 * @property string $opname_date
 * @property string $reason
 * @property string $status     DRAFT | PROCESSED
 * @property string $code       Auto-generated: SO-YYYYMM-001
 */
class StockOpname extends Model
{
    protected $fillable = [
        'warehouse_id',
        'opname_date',
        'reason',
        'status',
        'code',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Hitung persentase akurasi audit.
     *
     * Rumus: (Jumlah item yang cocok / Total item) × 100.
     * Mengembalikan 100% jika belum ada detail yang diaudit.
     */
    protected function accuracy(): Attribute
    {
        return Attribute::make(
            get: function () {
                $total = $this->details()->count();

                if ($total === 0) {
                    return 100;
                }

                $matchCount = $this->details()
                    ->whereRaw('physical_qty = system_qty')
                    ->count();

                return round(($matchCount / $total) * 100, 2);
            },
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (StockOpname $model) {
            self::generateCode($model);
        });

        static::created(function (StockOpname $model) {
            self::populateDetails($model);
        });
    }

    /**
     * Generate kode opname otomatis: SO-YYYYMM-001.
     */
    private static function generateCode(StockOpname $model): void
    {
        if (! empty($model->code)) {
            return;
        }

        $count = static::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        $model->code = 'SO-' . now()->format('Ym') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Populasi detail opname dengan semua item dan stok sistemnya.
     *
     * Menggunakan bulk insert untuk menghindari N+1 query problem.
     * Sebelumnya: 448 items × 2 queries = ~900 queries.
     * Sesudah: 2 queries + 1 bulk insert = 3 queries.
     */
    private static function populateDetails(StockOpname $model): void
    {
        $items = Item::select('id', 'avg_cost')->get();

        // Ambil semua stok di gudang ini dalam satu query
        $stocksByItem = InventoryStock::where('warehouse_id', $model->warehouse_id)
            ->pluck('quantity', 'item_id');

        // Siapkan data untuk bulk insert
        $details = $items->map(function (Item $item) use ($model, $stocksByItem) {
            return [
                'stock_opname_id' => $model->id,
                'item_id'         => $item->id,
                'system_qty'      => $stocksByItem->get($item->id, 0),
                'physical_qty'    => 0,
                'cost_at_opname'  => $item->avg_cost ?? 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        })->toArray();

        // Bulk insert — jauh lebih cepat daripada create() dalam loop
        StockOpnameDetail::insert($details);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Detail item-item yang diaudit dalam opname ini. */
    public function details(): HasMany
    {
        return $this->hasMany(StockOpnameDetail::class);
    }

    /** Gudang yang diaudit. */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

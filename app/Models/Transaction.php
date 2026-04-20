<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Model Transaksi Barang (Header).
 *
 * Transaksi adalah satu-satunya cara mengubah stok gudang.
 * Mendukung 2 tipe: IN (masuk) dan OUT (keluar).
 *
 * Workflow:
 * 1. User membuat transaksi dengan status DRAFT
 * 2. Admin meng-approve → status berubah ke APPROVED
 * 3. Saat APPROVED, sistem otomatis mutasi stok dan update harga rata-rata
 *
 * @property int         $id
 * @property int         $warehouse_id
 * @property int|null    $department_id
 * @property string      $type        IN | OUT
 * @property string      $status      DRAFT | APPROVED
 * @property string      $code        Auto-generated: TRX/IN/2026/01/0001
 * @property string      $trx_date
 * @property string|null $category
 * @property string|null $description
 */
class Transaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'warehouse_id',
        'department_id',
        'type',
        'status',
        'code',
        'trx_date',
        'category',
        'description',
    ];

    protected $casts = [
        'trx_date' => 'date',
    ];

    /** Konfigurasi Spatie Activity Log. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'type', 'category', 'warehouse_id'])
            ->logOnlyDirty();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Detail item-item dalam transaksi ini. */
    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /** Gudang tujuan/asal transaksi. */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** Departemen peminta (untuk transaksi OUT). */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            self::generateTransactionCode($transaction);
        });

        static::saving(function (Transaction $transaction) {
            self::enforceRoleProtection($transaction);
        });

        static::deleting(function (Transaction $transaction) {
            self::preventApprovedDeletion($transaction);
        });

        static::updated(function (Transaction $transaction) {
            self::processApproval($transaction);
        });
    }

    /**
     * Generate nomor transaksi otomatis: TRX/{TYPE}/{YYYY/MM}/{0001}.
     */
    private static function generateTransactionCode(Transaction $transaction): void
    {
        $type = $transaction->type;
        $date = $transaction->trx_date ? Carbon::parse($transaction->trx_date) : now();
        $yearMonth = $date->format('Y/m');

        $lastTrx = static::where('type', $type)
            ->whereYear('trx_date', $date->year)
            ->whereMonth('trx_date', $date->month)
            ->latest()
            ->first();

        $lastNumber = $lastTrx ? (int) substr($lastTrx->code, -4) : 0;
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        $transaction->code = "TRX/{$type}/{$yearMonth}/{$newNumber}";
    }

    /**
     * Proteksi role: STAFF tidak boleh set status APPROVED.
     *
     * Jika non-admin mencoba menyimpan dengan status APPROVED,
     * sistem otomatis mengembalikan ke DRAFT.
     */
    private static function enforceRoleProtection(Transaction $transaction): void
    {
        $user = auth()->user();

        if ($user && ($user->role ?? null) !== 'ADMIN' && $transaction->status === 'APPROVED') {
            $transaction->status = 'DRAFT';
        }
    }

    /**
     * Proteksi delete: Transaksi APPROVED tidak boleh dihapus oleh non-admin.
     *
     * @throws \RuntimeException
     */
    private static function preventApprovedDeletion(Transaction $transaction): void
    {
        $user = auth()->user();

        if ($transaction->status === 'APPROVED' && ($user->role ?? null) !== 'ADMIN') {
            throw new \RuntimeException('Transaksi APPROVED tidak boleh dihapus.');
        }
    }

    /**
     * Proses approval: mutasi stok dan update harga saat DRAFT → APPROVED.
     */
    private static function processApproval(Transaction $transaction): void
    {
        $wasNotApproved = $transaction->getOriginal('status') !== 'APPROVED';
        $isNowApproved = $transaction->status === 'APPROVED';

        if (! $isNowApproved || ! $wasNotApproved) {
            return;
        }

        DB::transaction(function () use ($transaction) {
            $transaction->applyStockMutation();

            if ($transaction->type === 'IN') {
                $transaction->updateMovingAverage();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
    |--------------------------------------------------------------------------
    */

    /**
     * Mutasi stok gudang berdasarkan detail transaksi.
     *
     * Menggunakan `lockForUpdate()` untuk mencegah race condition.
     * Untuk transaksi OUT, validasi stok mencukupi sebelum mengurangi.
     *
     * @throws \RuntimeException Jika stok tidak mencukupi (OUT)
     */
    public function applyStockMutation(): void
    {
        $this->loadMissing(['details.item']);

        DB::transaction(function () {
            foreach ($this->details as $detail) {
                $stock = $this->resolveOrCreateStock($detail->item_id);

                if ($this->type === 'IN') {
                    $stock->increment('quantity', $detail->quantity);
                    continue;
                }

                // OUT: blok stok minus
                if ($stock->quantity < $detail->quantity) {
                    $name = $detail->item?->name ?? ("Item ID " . $detail->item_id);
                    throw new \RuntimeException(
                        "Stok tidak cukup untuk: {$name}. Tersedia {$stock->quantity}, minta {$detail->quantity}."
                    );
                }

                $stock->decrement('quantity', $detail->quantity);
            }
        });
    }

    /**
     * Update harga modal rata-rata (Moving Average) untuk transaksi IN.
     *
     * Rumus: ((StokLama × HargaLama) + (StokMasuk × HargaMasuk)) / TotalStok
     */
    public function updateMovingAverage(): void
    {
        foreach ($this->details as $detail) {
            $item = $detail->item;

            $qtyMasuk = $detail->quantity;
            $hargaMasuk = $detail->price;

            // Stok sekarang (sudah ditambah di applyStockMutation)
            $stokBaru = $item->stocks()->sum('quantity');
            $stokLama = max(0, $stokBaru - $qtyMasuk);

            $totalNilaiLama = $stokLama * $item->avg_cost;
            $totalNilaiMasuk = $qtyMasuk * $hargaMasuk;

            if ($stokBaru > 0) {
                $newAvg = ($totalNilaiLama + $totalNilaiMasuk) / $stokBaru;
                $item->updateQuietly(['avg_cost' => $newAvg]);
            }
        }
    }

    /**
     * Cari atau buat record stok untuk item di gudang ini.
     *
     * Menggunakan `lockForUpdate()` untuk concurrency safety.
     * Handle race condition saat 2 request create bersamaan
     * melalui catch QueryException pada unique constraint violation.
     */
    private function resolveOrCreateStock(int $itemId): InventoryStock
    {
        $stock = InventoryStock::where('warehouse_id', $this->warehouse_id)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        try {
            return InventoryStock::create([
                'warehouse_id' => $this->warehouse_id,
                'item_id'      => $itemId,
                'quantity'     => 0,
            ]);
        } catch (QueryException $e) {
            // Race condition: request lain sudah create duluan
            return InventoryStock::where('warehouse_id', $this->warehouse_id)
                ->where('item_id', $itemId)
                ->lockForUpdate()
                ->first();
        }
    }
}

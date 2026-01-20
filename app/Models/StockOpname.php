<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    // Pastikan semua kolom form lo ada di sini!
    protected $fillable = [
        'warehouse_id',
        'opname_date',
        'reason',
        'status',
        'code'
    ];

    // INI DIA YANG BIKIN ERROR 500 KALAU ILANG!
    public function details(): HasMany
    {
        return $this->hasMany(StockOpnameDetail::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Accessor untuk hitung akurasi otomatis di tabel
    public function getAccuracyAttribute()
    {
        $total = $this->details()->count();
        if ($total === 0) return 100;

        $match = $this->details()
            ->whereRaw('physical_qty = system_qty')
            ->count();

        return round(($match / $total) * 100, 2);
    }
}

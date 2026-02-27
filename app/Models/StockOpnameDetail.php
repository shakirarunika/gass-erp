<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameDetail extends Model
{
    use HasFactory;

    // Lo pake guarded kosong udah bener buat tahap development
    protected $guarded = [];

    // Relasi ke barang
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // 👇 TAMBAHKAN INI: Relasi balik ke induk Stock Opname
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Saat baris SO dibuat, ambil avg_cost dari master item
            if ($model->item_id) {
                $model->cost_at_opname = $model->item->avg_cost ?? 0;
            }
        });
    }
}

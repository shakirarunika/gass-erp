<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class StockOpname extends Model
{
    protected function accuracy(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Ambil jumlah baris detail
                $total = $this->details()->count();

                // Kalau belum ada barang, anggap akurasi 100% (atau 0% terserah kebijakan lo)
                if ($total === 0) return 100;

                // Hitung yang jumlah FISIK-nya SAMA dengan SISTEM
                $matchCount = $this->details()
                    ->whereRaw('physical_qty = system_qty')
                    ->count();

                // Rumus: (Jumlah Cocok / Total Barang) * 100
                return round(($matchCount / $total) * 100, 2);
            },
        );
    }
    protected $fillable = ['warehouse_id', 'opname_date', 'reason', 'status', 'code'];

    // 👇 Tambahkan ini biar 'code' terisi otomatis (SO-202601-001)
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                // Kita hitung data yang bulannya SAMA dan tahunnya SAMA dengan sekarang
                $count = static::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count() + 1;

                $model->code = 'SO-' . now()->format('Ym') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($model) {
            // Begitu Header SO berhasil disimpan, server otomatis buatin detailnya di background
            $allItems = \App\Models\Item::all();

            foreach ($allItems as $item) {
                $stock = \App\Models\InventoryStock::where('item_id', $item->id)
                    ->where('warehouse_id', $model->warehouse_id)
                    ->first();

                $model->details()->create([
                    'item_id'    => $item->id,
                    'system_qty' => $stock ? $stock->quantity : 0,
                    'physical_qty' => 0,
                ]);
            }
        });
    }

    // 👇 WAJIB ADA buat Repeater di Resource
    public function details(): HasMany
    {
        return $this->hasMany(StockOpnameDetail::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}

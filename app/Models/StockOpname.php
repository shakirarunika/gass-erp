<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    protected $fillable = ['warehouse_id', 'opname_date', 'reason', 'status', 'code'];

    // 👇 Tambahkan ini biar 'code' terisi otomatis (SO-202601-001)
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $count = static::whereMonth('created_at', now()->month)->count() + 1;
                $model->code = 'SO-' . now()->format('Ym') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
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

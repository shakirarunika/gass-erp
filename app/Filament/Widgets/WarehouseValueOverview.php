<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * Widget Peringatan Valuasi Aset Keseluruhan.
 *
 * Menampilkan total nilai seluruh aset gudang, dengan peringatan visual
 * jika total valuasi telah melampaui limit budget yang ditentukan (1 Milyar).
 */
class WarehouseValueOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $totalAssetValue = Item::join('inventory_stocks', 'items.id', '=', 'inventory_stocks.item_id')
            ->select(DB::raw('SUM(inventory_stocks.quantity * items.avg_cost) as total_value'))
            ->value('total_value');

        // Set Batas Limit Valuasi Aset
        $limitValue = 1_000_000_000;
        
        $isDanger = $totalAssetValue > $limitValue;

        return [
            Stat::make('Total Nilai Aset Gudang', 'Rp ' . number_format($totalAssetValue, 0, ',', '.'))
                ->description($isDanger ? 'MELEBIHI LIMIT BUDGET!' : 'Aman (Dibawah Ketentuan Valuation)')
                ->descriptionIcon($isDanger ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($isDanger ? 'danger' : 'success')
                ->chart($isDanger ? [1, 5, 10, 20] : [20, 10, 5, 1]),
        ];
    }
}

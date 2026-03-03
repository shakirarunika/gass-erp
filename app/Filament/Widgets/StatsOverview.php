<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\StockOpname;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{

    protected int | string | array $columnSpan = 'full';

    // app/Filament/Widgets/StatsOverview.php
    protected function getStats(): array
    {
        $totalValuation = \App\Models\InventoryStock::join('items', 'items.id', '=', 'inventory_stocks.item_id')
            ->sum(\DB::raw('quantity * items.avg_cost'));

        $totalQty = \App\Models\InventoryStock::sum('quantity');

        return [
            Stat::make('Total Nilai Aset', 'Rp ' . number_format($totalValuation, 0, ',', '.'))
                ->description('Aset mengendap di semua gudang')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Barang', number_format($totalQty, 0, ',', '.'))
                ->description('Total fisik di sistem')
                ->descriptionIcon('heroicon-m-cube'),
        ];
    }
}

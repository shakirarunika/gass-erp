<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget Ringkasan Statistik.
 *
 * Menampilkan metrik utama di bagian atas dashboard, seperti:
 * 1. Total nilai aset (valuasi stok).
 * 2. Total jenis barang (SKU).
 * 3. Item dengan stok kritis (di bawah batas minimum).
 */
class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Hitung total aset (Total quantity * avg_cost)
        $totalValuation = Item::query()
            ->join('inventory_stocks', 'items.id', '=', 'inventory_stocks.item_id')
            ->selectRaw('SUM(inventory_stocks.quantity * items.avg_cost) as total')
            ->value('total') ?? 0;

        return [
            Stat::make('Total Nilai Aset', 'Rp ' . number_format($totalValuation, 0, ',', '.'))
                ->description('Aset mengendap di gudang')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Jenis Barang', Item::count() . ' SKU')
                ->description('Jumlah item unik di sistem')
                ->descriptionIcon('heroicon-m-rectangle-group'),

            Stat::make('Item Stok Kritis', Item::query()
                ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM inventory_stocks WHERE inventory_stocks.item_id = items.id) <= min_stock')
                ->count())
                ->description('Perlu order hari ini!')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Widget Tabel Rincian Valuasi Gudang.
 *
 * Menampilkan tabel rekapitulasi total item dan total valuasi aset
 * (Rupiah) untuk masing-masing gudang.
 */
class WarehouseValuationTable extends BaseWidget
{
    protected static ?string $heading = 'Rincian Aset Per Lokasi Gudang';
    
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(Warehouse::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Gudang')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('stocks_sum_quantity')
                    ->label('Total Item')
                    ->sum('stocks', 'quantity')
                    ->badge(),

                Tables\Columns\TextColumn::make('valuation')
                    ->label('Total Valuasi')
                    ->money('IDR')
                    ->state(function (Warehouse $record) {
                        return $record->stocks->sum(fn ($stock) => $stock->quantity * ($stock->item->avg_cost ?? 0));
                    }),
            ])
            ->paginated(false);
    }
}

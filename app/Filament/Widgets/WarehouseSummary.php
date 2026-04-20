<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Widget ringkasan aset per gudang.
 *
 * Menampilkan tabel gudang yang dikelompokkan berdasarkan Plant,
 * dengan informasi jumlah jenis item dan total valuasi aset.
 */
class WarehouseSummary extends BaseWidget
{
    protected static ?string $heading = 'Ringkasan Aset Gudang';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Warehouse::query()->with(['plant', 'stocks.item'])
            )
            ->defaultGroup('plant.name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Gudang')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stocks_count')
                    ->counts('stocks')
                    ->label('Jml Jenis Item')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('valuation')
                    ->label('Total Aset (Valuation)')
                    ->getStateUsing(function (Warehouse $record) {
                        return $record->stocks->sum(function ($stock) {
                            return $stock->quantity * $stock->item->avg_cost;
                        });
                    })
                    ->money('IDR')
                    ->sortable(false)
                    ->weight('bold'),
            ]);
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Widget Item Stok Kritis.
 *
 * Menampilkan 5 barang dengan stok fisik di bawah atau sama dengan
 * batas aman (min_stock) yang telah ditentukan.
 */
class LatestLowStockItems extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'DAFTAR BARANG DIBAWAH MINIMAL STOK';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->whereRaw(
                        '(SELECT COALESCE(SUM(quantity), 0) FROM inventory_stocks WHERE inventory_stocks.item_id = items.id) <= min_stock'
                    )
                    ->orderBy('name', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode'),

                Tables\Columns\TextColumn::make('stocks_sum_quantity')
                    ->label('Stok Saat Ini')
                    ->sum('stocks', 'quantity')
                    ->default(0)
                    ->badge()
                    ->color(
                        fn ($state, Item $record): string =>
                        $state <= 0 ? 'danger' : ($state <= $record->min_stock ? 'warning' : 'success')
                    ),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Batas Aman')
                    ->numeric(),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Satuan'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Detail')
                    ->url(fn (Item $record): string => "/admin/items/{$record->id}/edit")
                    ->icon('heroicon-m-eye'),
            ]);
    }
}

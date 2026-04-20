<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Widget Barang Mati (Dead Stock).
 *
 * Menampilkan daftar barang yang memiliki stok lebih dari nol,
 * tetapi tidak memiliki pergerakan transaksi dalam 6 bulan terakhir.
 */
class DeadStockItems extends BaseWidget
{
    protected static ?string $heading = 'Barang Mati (Tidak Bergerak > 6 Bulan)';

    protected static ?int $sort = 3;
    
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->whereHas('stocks', function (Builder $query) {
                        $query->where('quantity', '>', 0);
                    })
                    ->whereDoesntHave('transactionDetails', function (Builder $query) {
                        $query->where('created_at', '>=', now()->subMonths(6));
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori'),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Satuan'),

                Tables\Columns\TextColumn::make('stocks_sum_quantity')
                    ->label('Stok Mengendap')
                    ->sum('stocks', 'quantity')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('avg_cost')
                    ->label('Harga Modal')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('total_idle_value')
                    ->label('Nilai Aset Mati')
                    ->money('IDR')
                    ->state(function (Item $record) {
                        return $record->stocks->sum('quantity') * $record->avg_cost;
                    })
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Cek Detail')
                    ->url(fn (Item $record): string => "/admin/items/{$record->id}/edit")
                    ->icon('heroicon-m-eye'),
            ]);
    }

    protected function getTableAttributes(): array
    {
        return ['style' => 'height: 400px; overflow-y: auto;'];
    }
}

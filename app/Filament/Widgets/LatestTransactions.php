<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Widget Transaksi Terkini.
 *
 * Menampilkan daftar 5 transaksi terakhir secara realtime di dashboard.
 */
class LatestTransactions extends BaseWidget
{
    protected static ?string $heading = 'Transaksi Terkini (Realtime)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors(['success' => 'IN', 'danger' => 'OUT']),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Tujuan/Dept')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('open')
                    ->url(fn (Transaction $record) => route('filament.admin.resources.transactions.edit', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}

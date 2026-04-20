<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\InventoryStock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Widget Tabel Valuasi Kategori.
 *
 * Menampilkan rincian nilai aset gudang per kategori dalam bentuk tabel
 * yang dilengkapi dengan fitur grand total di bagian bawah.
 */
class CategoryValueTable extends BaseWidget
{
    protected static ?string $heading = 'Komposisi Nilai Aset Per Kategori';
    
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Category::query()
                    ->select('categories.*')
                    ->addSelect([
                        'total_val' => InventoryStock::query()
                            ->join('items', 'items.id', '=', 'inventory_stocks.item_id')
                            ->whereColumn('items.category_id', 'categories.id')
                            ->selectRaw('SUM(inventory_stocks.quantity * items.avg_cost)'),
                    ])
                    ->orderByDesc('total_val')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('')
                    ->formatStateUsing(fn () => ' ')
                    ->extraAttributes(fn ($record) => [
                        'style' => 'background-color: ' . $this->getCategoryColor($record->id) . '; width: 12px; border-radius: 99px;',
                    ]),

                Tables\Columns\TextColumn::make('name')
                    ->label('Kategori')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('total_val')
                    ->label('Total Valuasi')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->color('success')
                    ->weight('bold')
                    ->alignEnd()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('GRAND TOTAL')
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ),
            ])
            ->paginated(false);
    }

    protected function getTableAttributes(): array
    {
        return ['style' => 'height: 400px; overflow-y: auto;'];
    }

    private function getCategoryColor($id): string
    {
        $colors = ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#2ecc71', '#34495e'];

        return $colors[$id % count($colors)] ?? '#94a3b8';
    }
}

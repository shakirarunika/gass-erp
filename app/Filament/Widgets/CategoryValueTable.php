<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CategoryValueTable extends BaseWidget
{
    protected static ?string $heading = 'Komposisi Nilai Aset Per Kategori';

    // Pastikan column span-nya 1 biar bagi dua sama chart
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // 3. OTOMATIS SORT DARI VALUE TERBANYAK
                Category::query()
                    ->addSelect([
                        'total_valuation' => \App\Models\Item::query()
                            ->whereColumn('category_id', 'categories.id')
                            ->join('inventory_stocks', 'items.id', '=', 'inventory_stocks.item_id')
                            ->selectRaw('SUM(inventory_stocks.quantity * items.avg_cost)')
                    ])
                    ->orderByDesc('total_valuation')
            )
            ->columns([
                // 2. INDIKATOR WARNA (Hardcoded per ID agar aman)
                Tables\Columns\TextColumn::make('color_indicator')
                    ->label('')
                    ->getStateUsing(fn() => ' ')
                    ->extraAttributes(fn($record) => [
                        'style' => 'background-color: ' . $this->getCategoryColor($record->id) . '; width: 8px; border-radius: 4px;',
                    ]),

                Tables\Columns\TextColumn::make('name')
                    ->label('Kategori')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_valuation')
                    ->label('Total Valuasi')
                    // 4. HAPUS DESIMAL ,00
                    ->formatStateUsing(fn($state) => 'IDR ' . number_format($state, 0, ',', '.'))
                    ->color('success')
                    ->weight('bold')
                    ->alignEnd(),
            ])
            ->paginated(false)
            // 1. TINGGI WIDGET SAMA RATA (Fix Height)
            ->extraTableAttributes([
                'style' => 'height: 380px; overflow-y: auto;',
            ]);
    }

    // Fungsi pembantu buat warna biar matching sama chart (Opsional)
    private function getCategoryColor($id): string
    {
        $colors = ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#2ecc71', '#34495e'];
        return $colors[$id % count($colors)] ?? '#cbd5e1';
    }
}

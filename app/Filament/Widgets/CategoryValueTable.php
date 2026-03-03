<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\InventoryStock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class CategoryValueTable extends BaseWidget
{
    protected static ?string $heading = 'Komposisi Nilai Aset Per Kategori';

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // 3. SORT OTOMATIS BERDASARKAN VALUE TERBANYAK
                Category::query()
                    ->select('categories.*')
                    ->addSelect([
                        'total_val' => InventoryStock::query()
                            ->join('items', 'items.id', '=', 'inventory_stocks.item_id')
                            ->whereColumn('items.category_id', 'categories.id')
                            ->selectRaw('SUM(inventory_stocks.quantity * items.avg_cost)')
                    ])
                    ->orderByDesc('total_val')
            )
            ->columns([
                // 2. INDIKATOR WARNA (Matching dengan Chart)
                Tables\Columns\TextColumn::make('color')
                    ->label('')
                    ->getStateUsing(fn() => ' ')
                    ->extraAttributes(fn($record) => [
                        'style' => 'background-color: ' . $this->getCategoryColor($record->id) . '; width: 10px; border-radius: 99px;',
                    ]),

                Tables\Columns\TextColumn::make('name')
                    ->label('Kategori')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_val')
                    ->label('Total Valuasi')
                    // 4. HAPUS DESIMAL ,00
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->color('success')
                    ->weight('bold')
                    ->alignEnd(),
            ])
            ->paginated(false)
            // 1. TINGGI WIDGET SAMA RATA (Ganti extraTableAttributes jadi extraAttributes)
            ->extraAttributes([
                'class' => 'overflow-y-auto',
                'style' => 'max-height: 400px;',
            ]);
    }

    private function getCategoryColor($id): string
    {
        $colors = ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#2ecc71', '#34495e'];
        return $colors[$id % count($colors)] ?? '#94a3b8';
    }
}

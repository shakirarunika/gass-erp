<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CategoryValueTable extends BaseWidget
{
    protected static ?string $heading = 'Komposisi Nilai Aset Per Kategori';

    // Atur lebar agar pas bersanding dengan Chart SO
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(Category::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kategori')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Valuasi')
                    ->money('IDR')
                    ->state(function (Category $record) {
                        return $record->items->sum(function ($item) {
                            return $item->stocks->sum('quantity') * $item->avg_cost;
                        });
                    })
                    ->color('success')
                    ->weight('bold'),
            ])
            ->paginated(false); // Biar rapi gak ada tombol next page
    }
}

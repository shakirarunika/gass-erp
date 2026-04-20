<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Spatie\Activitylog\Models\Activity;

/**
 * Widget Aktivitas Terkini.
 *
 * Menampilkan log 5 aktivitas terakhir yang dilakukan oleh user
 * di dalam sistem (menggunakan Spatie Activitylog).
 */
class RecentActivityWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'AKTIVITAS TERAKHIR USER';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('H:i')
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->default('System')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Modul')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
            ])
            ->paginated(false);
    }
}

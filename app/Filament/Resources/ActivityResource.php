<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource untuk mengelola dan menampilkan Log Aktivitas.
 *
 * Menggunakan model Activity dari Spatie Activitylog.
 * Resource ini bersifat read-only (hanya view, tanpa create/edit/delete)
 * dan hanya dapat diakses oleh user dengan role ADMIN.
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationLabel = 'Log Aktivitas';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 4;

    /**
     * Hanya tampilkan navigasi untuk user dengan role ADMIN.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'ADMIN';
    }

    /**
     * Definisi Infolist untuk halaman detail (View).
     *
     * Menampilkan metadata aktivitas, objek yang terdampak,
     * dan perbandingan data sebelum/sesudah perubahan.
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                self::metadataSection(),
                self::subjectSection(),
                self::changeLogSection(),
            ]);
    }

    /**
     * Definisi tabel untuk halaman daftar (Index).
     */
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->default('System')
                    ->weight('bold')
                    ->icon('heroicon-m-user-circle'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn (string $state): string => self::getActionColor($state)),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Modul')
                    ->formatStateUsing(fn ($state) => self::getModuleLabel($state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID Ref')
                    ->fontFamily('mono')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Filter Jenis Aksi')
                    ->options([
                        'created' => 'Data Baru (Created)',
                        'updated' => 'Perubahan (Updated)',
                        'deleted' => 'Penghapusan (Deleted)',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->modalHeading('Rincian Aktivitas')
                    ->modalWidth('4xl'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Infolist Section Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Section: Metadata aktivitas (pelaku, aksi, waktu).
     */
    private static function metadataSection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make('Metadata Aktivitas')
            ->schema([
                Infolists\Components\TextEntry::make('causer.name')
                    ->label('Pelaku (User)')
                    ->icon('heroicon-o-user')
                    ->weight('bold')
                    ->placeholder('System / Otomatis'),

                Infolists\Components\TextEntry::make('description')
                    ->label('Jenis Tindakan')
                    ->badge()
                    ->color(fn (string $state): string => self::getActionColor($state)),

                Infolists\Components\TextEntry::make('created_at')
                    ->label('Waktu Kejadian')
                    ->dateTime('d M Y, H:i:s')
                    ->icon('heroicon-o-clock'),
            ])
            ->columns(3);
    }

    /**
     * Section: Objek yang terdampak (modul dan ID referensi).
     */
    private static function subjectSection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make('Objek Yang Terdampak')
            ->schema([
                Infolists\Components\TextEntry::make('subject_type')
                    ->label('Modul / Menu')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->weight('bold'),

                Infolists\Components\TextEntry::make('subject_id')
                    ->label('ID Data (Ref)')
                    ->fontFamily('mono')
                    ->copyable(),
            ])
            ->columns(2);
    }

    /**
     * Section: Log perubahan data (perbandingan sebelum dan sesudah).
     */
    private static function changeLogSection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make('Log Perubahan Data')
            ->description('Perbandingan data sebelum dan sesudah tindakan.')
            ->schema([
                Infolists\Components\Grid::make(2)
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('properties.old')
                            ->label('ORIGINAL (Sebelum)')
                            ->keyLabel('Kolom')
                            ->valueLabel('Nilai Lama')
                            ->placeholder('Tidak ada data lama (Data Baru)'),

                        Infolists\Components\KeyValueEntry::make('properties.attributes')
                            ->label('CHANGES (Sesudah)')
                            ->keyLabel('Kolom')
                            ->valueLabel('Nilai Baru')
                            ->placeholder('Tidak ada perubahan tercatat'),
                    ]),
            ])
            ->collapsible();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mapping warna badge berdasarkan jenis aksi (created/updated/deleted).
     */
    private static function getActionColor(string $state): string
    {
        return match ($state) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            default   => 'gray',
        };
    }

    /**
     * Mapping label modul berdasarkan nama class model.
     */
    private static function getModuleLabel(string $state): string
    {
        return match (class_basename($state)) {
            'Item'        => '📦 Stok Barang',
            'StockOpname' => '📋 Audit Opname',
            'Warehouse'   => '🏢 Gudang',
            'User'        => '👤 Pengguna',
            default       => class_basename($state),
        };
    }
}

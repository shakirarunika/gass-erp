<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryStockResource\Pages;
use App\Models\InventoryStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resource untuk menampilkan Stok Real-time per Gudang.
 *
 * Resource ini bersifat read-only untuk data stok (quantity, item, warehouse)
 * karena stok dihitung secara otomatis oleh sistem berdasarkan transaksi.
 * Satu-satunya field yang dapat diubah adalah lokasi rak penyimpanan.
 *
 * Fitur utama:
 * - Monitoring stok real-time per gudang
 * - Filter stok menipis (low stock alert)
 * - Kalkulasi nilai aset (quantity × harga modal)
 * - Export data stok ke Excel
 */
class InventoryStockResource extends Resource
{
    protected static ?string $model = InventoryStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Stok Real-time';
    protected static ?string $navigationGroup = 'Monitoring Stok';
    protected static ?int $navigationSort = 1;

    /**
     * Nonaktifkan fitur Create — stok hanya dibuat via transaksi.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Nonaktifkan fitur Delete — stok tidak boleh dihapus manual.
     */
    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    /**
     * Definisi form untuk Edit (hanya update lokasi rak).
     *
     * Section pertama menampilkan data stok yang terkunci (read-only).
     * Section kedua berisi field lokasi rak yang bisa diubah.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Stok (Locked)')
                    ->description('Data ini dihitung otomatis oleh sistem dan tidak dapat diubah manual.')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->label('Gudang')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('item_id')
                            ->relationship('item', 'name')
                            ->label('Barang')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Sisa Stok Saat Ini')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Manajemen Lokasi')
                    ->description('Anda hanya diperbolehkan mengubah lokasi penyimpanan.')
                    ->schema([
                        Forms\Components\TextInput::make('rack_location')
                            ->label('Lokasi Rak')
                            ->placeholder('Contoh: RAK-A-01')
                            ->helperText('Update lokasi jika barang dipindahkan.')
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    /**
     * Definisi tabel untuk halaman monitoring stok.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->modifyQueryUsing(
                fn (Builder $query) => $query->with(['item.unit', 'warehouse', 'item.category'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('item.category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('item.code')
                    ->label('Kode')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('item.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->sortable(),

                self::quantityColumn(),
                self::assetValueColumn(),

                Tables\Columns\TextColumn::make('rack_location')
                    ->label('Lokasi Rak')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-map-pin')
                    ->searchable()
                    ->placeholder('Belum set'),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Filter Gudang')
                    ->relationship('warehouse', 'name'),

                SelectFilter::make('category_id')
                    ->label('Filter Kategori')
                    ->relationship('item.category', 'name'),

                Filter::make('low_stock')
                    ->label('Hanya Stok Menipis')
                    ->toggle()
                    ->query(
                        fn (Builder $query) => $query->whereRaw(
                            'quantity <= (SELECT min_stock FROM items WHERE items.id = inventory_stocks.item_id)'
                        )
                    ),
            ])
            ->headerActions([
                self::exportExcelAction(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Update Rak')
                    ->modalHeading('Update Lokasi Penyimpanan')
                    ->icon('heroicon-o-pencil-square'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryStocks::route('/'),
            'edit'  => Pages\EditInventoryStock::route('/{record}/edit'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Column Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Kolom sisa stok dengan indikator warna berdasarkan minimum stok.
     *
     * Merah jika stok ≤ minimum, hijau jika masih aman.
     */
    private static function quantityColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('quantity')
            ->label('Sisa Stok')
            ->numeric()
            ->weight('bold')
            ->suffix(fn (InventoryStock $record) => ' ' . ($record->item->unit->name ?? ''))
            ->color(
                fn (InventoryStock $record) => $record->quantity <= ($record->item->min_stock ?? 0)
                    ? 'danger'
                    : 'success'
            )
            ->sortable();
    }

    /**
     * Kolom kalkulasi nilai aset (quantity × harga modal).
     *
     * Merupakan computed field sehingga tidak bisa di-sort di database.
     */
    private static function assetValueColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('total_value')
            ->label('Nilai Aset')
            ->money('IDR')
            ->state(fn (InventoryStock $record) => $record->quantity * ($record->item->avg_cost ?? 0))
            ->color('primary')
            ->weight('bold')
            ->sortable(false);
    }

    /*
    |--------------------------------------------------------------------------
    | Action Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Aksi export data stok ke file Excel.
     *
     * Menggunakan class InventoryReportExport untuk generate file.
     */
    private static function exportExcelAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('exportExcel')
            ->label('Download Data Stok')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function () {
                $query = InventoryStock::query()
                    ->with(['item.unit', 'warehouse', 'item.category']);

                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\InventoryReportExport($query),
                    'Stok-Realtime-' . date('Y-m-d') . '.xlsx'
                );
            });
    }
}

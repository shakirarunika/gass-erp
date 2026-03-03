<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryStockResource\Pages;
use App\Models\InventoryStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class InventoryStockResource extends Resource
{
    protected static ?string $model = InventoryStock::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Stok Real-time';
    protected static ?string $navigationGroup = 'Monitoring Stok';
    protected static ?int $navigationSort = 1;

    // 👇 PENTING: Matikan Create & Delete agar stok murni hasil kalkulasi sistem
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

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
                            ->disabled() // Terkunci
                            ->dehydrated(false), // Gak usah dikirim lagi ke DB saat save

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
                    ])->columns(3),

                Forms\Components\Section::make('Manajemen Lokasi')
                    ->description('Anda hanya diperbolehkan mengubah lokasi penyimpanan.')
                    ->schema([
                        Forms\Components\TextInput::make('rack_location')
                            ->label('Lokasi Rak')
                            ->placeholder('Contoh: RAK-A-01')
                            ->helperText('Update lokasi jika barang dipindahkan.')
                            ->required()
                            ->maxLength(255),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['item.unit', 'warehouse', 'item.category']))
            ->columns([
                // 1. TAMBAH KATEGORI (Biar enak liat pengelompokannya)
                Tables\Columns\TextColumn::make('item.category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                // 2. TAMBAH KODE BARANG (Berdiri sendiri biar gampang dicari)
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

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->weight('bold')
                    ->suffix(fn(InventoryStock $record) => " " . ($record->item->unit->name ?? ''))
                    ->color(
                        fn(InventoryStock $record) =>
                        $record->quantity <= ($record->item->min_stock ?? 0) ? 'danger' : 'success'
                    )
                    ->sortable(),

                // 3. TAMBAH TOTAL VALUASI (Qty * Harga Modal)
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Nilai Aset')
                    ->money('IDR')
                    ->state(function (InventoryStock $record) {
                        return $record->quantity * ($record->item->avg_cost ?? 0);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(false), // Karena ini computed field

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

                // Tambahin Filter Kategori di sini biar makin pro
                SelectFilter::make('category_id')
                    ->label('Filter Kategori')
                    ->relationship('item.category', 'name'),

                Filter::make('low_stock')
                    ->label('Hanya Stok Menipis')
                    ->toggle()
                    ->query(
                        fn(Builder $query) =>
                        $query->whereRaw('quantity <= (SELECT min_stock FROM items WHERE items.id = inventory_stocks.item_id)')
                    ),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Download Data Stok')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exports([
                        ExcelExport::make('all')
                            ->label('Download Full (Excel)')
                            ->fromModel()
                            ->withFilename('Inventory-Report-' . date('d-M-Y'))
                            ->withColumns([
                                Column::make('warehouse.name')->heading('Gudang'),
                                Column::make('item.category.name')->heading('Kategori'),
                                Column::make('item.code')->heading('Kode Barang'),
                                Column::make('item.name')->heading('Nama Barang'),
                                Column::make('rack_location')->heading('Lokasi Rak'),
                                Column::make('quantity')->heading('Qty Sistem'),
                                Column::make('item.unit.name')->heading('Satuan'),
                                // Nilai Aset di Excel
                                Column::make('total_value')->heading('Total Valuasi (IDR)')
                                    ->formatStateUsing(fn($record) => $record->quantity * ($record->item->avg_cost ?? 0)),
                            ]),
                    ]),
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
}

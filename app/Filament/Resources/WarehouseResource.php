<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Resource untuk mengelola data Gudang.
 *
 * Gudang merupakan lokasi penyimpanan fisik barang yang berada
 * di bawah sebuah Plant. Setiap gudang memiliki kode unik dan
 * menjadi referensi utama pada transaksi serta monitoring stok.
 */
class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 3;

    /**
     * Definisi form untuk Create & Edit gudang.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Lokasi Gudang')
                    ->schema([
                        Forms\Components\Select::make('plant_id')
                            ->relationship('plant', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Gudang')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Contoh: Gudang Bahan Baku'),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Gudang')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Definisi tabel untuk halaman daftar gudang.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('plant.name')
                    ->label('Lokasi Plant')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->weight('bold')
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Gudang')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stocks_count')
                    ->label('Varian Barang')
                    ->counts('stocks')
                    ->badge()
                    ->suffix(' SKU'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit'   => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}

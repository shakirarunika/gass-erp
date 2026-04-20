<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOpnameResource\Pages;
use App\Models\StockOpname;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource untuk mengelola Stock Opname (Audit Fisik Stok).
 *
 * Stock Opname adalah proses pencocokkan antara stok di sistem
 * dengan stok fisik di gudang. Resource ini mendukung:
 * - Pembuatan audit per gudang dengan status Draft/Processed
 * - Kalkulasi akurasi dan selisih stok otomatis
 * - Proteksi finalisasi hanya oleh ADMIN
 * - Tracking nilai aset berdasarkan hasil hitung fisik
 */
class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Stock Opname';
    protected static ?string $navigationGroup = 'Aktivitas Gudang';
    protected static ?int $navigationSort = 1;

    /*
    |--------------------------------------------------------------------------
    | Form Definition
    |--------------------------------------------------------------------------
    */

    /**
     * Definisi form untuk Create & Edit stock opname.
     *
     * Form ini dikunci (disabled) jika status sudah PROCESSED
     * untuk mencegah perubahan data yang sudah difinalisasi.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Info Audit')
                    ->disabled(fn ($record) => $record?->status === 'PROCESSED')
                    ->description('Pilih gudang untuk memuat daftar stok sistem secara otomatis.')
                    ->schema([
                        self::warehouseSelectField(),

                        Forms\Components\DatePicker::make('opname_date')
                            ->label('Tanggal Audit')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('reason')
                            ->label('Nama/Catatan Audit')
                            ->placeholder('Contoh: Opname Akhir Bulan - Gudang Sentul')
                            ->required()
                            ->maxLength(255),

                        self::statusSelectField(),
                    ])
                    ->columns(3),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Table Definition
    |--------------------------------------------------------------------------
    */

    /**
     * Definisi tabel untuk halaman daftar stock opname.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opname_date')
                    ->label('Tanggal Audit')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'DRAFT' ? 'warning' : 'success'),

                self::valuationColumn(),
                self::accuracyColumn(),
                self::varianceColumn(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'DRAFT'     => 'Draft',
                        'PROCESSED' => 'Processed',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'edit'   => Pages\EditStockOpname::route('/{record}/edit'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Reusable Field Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Select gudang yang akan diaudit.
     *
     * Dropdown terkunci otomatis jika sudah ada barang yang dipilih
     * di daftar detail, untuk mencegah inkonsistensi data stok.
     */
    private static function warehouseSelectField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('warehouse_id')
            ->relationship('warehouse', 'name')
            ->label('Gudang yang Diaudit')
            ->required()
            ->live()
            ->searchable()
            ->preload()
            ->disabled(function (Get $get) {
                $details = $get('details') ?? [];
                return collect($details)->contains(
                    fn ($item) => filled($item['item_id'] ?? null)
                );
            })
            ->dehydrated()
            ->helperText('Dropdown terkunci jika sudah ada barang di daftar. Hapus barang untuk ganti gudang.');
    }

    /**
     * Select status opname dengan proteksi role.
     *
     * Hanya user ADMIN yang dapat mengubah status ke PROCESSED (finalisasi).
     * User non-admin hanya bisa menyimpan sebagai DRAFT.
     */
    private static function statusSelectField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('status')
            ->options([
                'DRAFT'     => 'Draft (Proses Hitung)',
                'PROCESSED' => 'Processed (Final & Update Stok)',
            ])
            ->default('DRAFT')
            ->required()
            ->disabled(
                fn (string $operation) => $operation === 'edit' && auth()->user()->role !== 'ADMIN'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Column Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Kolom total valuasi aset berdasarkan hasil hitung fisik.
     *
     * Dihitung dari: SUM(physical_qty × cost_at_opname).
     */
    private static function valuationColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('total_valuation')
            ->label('Nilai Aset')
            ->money('IDR')
            ->state(
                fn (StockOpname $record) => $record->details()
                    ->sum(\DB::raw('physical_qty * cost_at_opname'))
            )
            ->color('gray');
    }

    /**
     * Kolom persentase akurasi audit.
     *
     * Kode warna:
     * - Hijau (≥99%): Sangat akurat
     * - Kuning (95-98%): Perlu perhatian
     * - Merah (<95%): Selisih signifikan
     */
    private static function accuracyColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('accuracy')
            ->label('Akurasi')
            ->state(fn (StockOpname $record): string => $record->accuracy . '%')
            ->badge()
            ->color(fn ($state) => match (true) {
                (float) $state >= 99 => 'success',
                (float) $state >= 95 => 'warning',
                default              => 'danger',
            })
            ->description(
                fn (StockOpname $record) => 'Audited: ' . $record->details()->count() . ' Items'
            );
    }

    /**
     * Kolom jumlah item yang memiliki selisih (fisik ≠ sistem).
     */
    private static function varianceColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('variance_count')
            ->label('Item Selisih')
            ->state(
                fn (StockOpname $record) => $record->details()
                    ->whereRaw('physical_qty != system_qty')
                    ->count() . ' Barang'
            )
            ->icon('heroicon-o-exclamation-triangle')
            ->color(fn ($state) => (int) $state > 0 ? 'danger' : 'gray');
    }
}

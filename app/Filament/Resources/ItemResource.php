<?php

namespace App\Filament\Resources;

use App\Exports\ItemsExport;
use App\Exports\ItemTemplateExport;
use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Imports\ItemImport;
use App\Models\Category;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Resource untuk mengelola Master Data Barang.
 *
 * Barang merupakan entitas inti dalam sistem inventory. Setiap barang
 * memiliki kode unik yang di-generate otomatis berdasarkan kategori,
 * dilengkapi dengan fitur:
 * - Auto-generate kode barang (prefix kategori + nomor urut)
 * - Notifikasi badge stok menipis di sidebar
 * - Global search berdasarkan nama dan kode
 * - Import/export data via Excel
 * - Kalkulasi total valuasi aset
 */
class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'name';

    /*
    |--------------------------------------------------------------------------
    | Navigation & Global Search
    |--------------------------------------------------------------------------
    */

    /**
     * Format judul hasil pencarian global: "NAMA BARANG (KODE)".
     */
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record->name} ({$record->code})";
    }

    /**
     * Badge merah di sidebar menunjukkan jumlah barang dengan stok menipis.
     */
    public static function getNavigationBadge(): ?string
    {
        $lowStockCount = static::getModel()::whereRaw(
            '(SELECT COALESCE(SUM(quantity), 0) FROM inventory_stocks WHERE inventory_stocks.item_id = items.id) <= min_stock'
        )->count();

        return $lowStockCount > 0 ? (string) $lowStockCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Jumlah barang dengan stok menipis';
    }

    /**
     * Atribut yang bisa dicari via Global Search.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code'];
    }

    /**
     * Detail tambahan yang ditampilkan di hasil Global Search.
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Kategori' => $record->category->name ?? '-',
            'Stok'     => $record->stocks()->sum('quantity') . ' ' . ($record->unit->name ?? 'Pcs'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Form Definition
    |--------------------------------------------------------------------------
    */

    /**
     * Definisi form untuk Create & Edit barang.
     *
     * Kode barang di-generate otomatis saat user memilih kategori.
     * Format kode: {KODE_KATEGORI}{NOMOR_URUT_6_DIGIT} (contoh: AKB000001).
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                self::categorySelectField(),

                Forms\Components\TextInput::make('name')
                    ->label('Nama Barang')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('code')
                    ->label('Kode Barang (Auto)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Kode digenerate otomatis berdasarkan Kategori.'),

                self::unitSelectField(),

                Forms\Components\TextInput::make('min_stock')
                    ->label('Minimum Stok (Alert)')
                    ->required()
                    ->numeric()
                    ->default(10),

                Forms\Components\TextInput::make('avg_cost')
                    ->label('Harga Modal (HPP)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Table Definition
    |--------------------------------------------------------------------------
    */

    /**
     * Definisi tabel untuk halaman daftar barang.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Satuan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Min. Stok')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('avg_cost')
                    ->label('HPP')
                    ->money('IDR')
                    ->sortable(),

                self::totalValuationColumn(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Filter Kategori'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                self::excelMenuGroup(),
                self::importExcelAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations & Pages
    |--------------------------------------------------------------------------
    */

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit'   => Pages\EditItem::route('/{record}/edit'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Reusable Field Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Select kategori dengan auto-generate kode barang.
     *
     * Saat kategori dipilih, sistem otomatis menghitung nomor urut
     * berikutnya (termasuk item yang sudah soft-deleted) dan
     * menggenerate kode barang dengan format: {PREFIX}{000001}.
     */
    private static function categorySelectField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('category_id')
            ->relationship('category', 'name')
            ->label('Kategori')
            ->required()
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(function ($state, Set $set) {
                if (! $state) {
                    return;
                }

                $category = Category::find($state);

                if (! $category?->code) {
                    return;
                }

                // Gunakan withTrashed() agar nomor urut tidak bentrok
                // dengan item yang pernah di-soft-delete
                $sequence = Item::where('category_id', $state)
                    ->withTrashed()
                    ->count() + 1;

                $generatedCode = $category->code . str_pad($sequence, 6, '0', STR_PAD_LEFT);
                $set('code', $generatedCode);
            });
    }

    /**
     * Select satuan dengan opsi buat satuan baru langsung dari form.
     */
    private static function unitSelectField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('unit_id')
            ->label('Satuan')
            ->relationship('unit', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->createOptionForm([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Satuan')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->label('Singkatan'),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Column Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Kolom total valuasi aset (total stok × harga modal).
     *
     * Computed field — tidak bisa di-sort di level database.
     */
    private static function totalValuationColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('total_valuation')
            ->label('Total Aset')
            ->money('IDR')
            ->state(function (Item $record): float {
                $totalQty = $record->stocks()->sum('quantity');
                return $totalQty * ($record->avg_cost ?? 0);
            })
            ->color('success')
            ->sortable(false);
    }

    /*
    |--------------------------------------------------------------------------
    | Action Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Grup aksi Excel (Download Template & Export Data).
     */
    private static function excelMenuGroup(): Tables\Actions\ActionGroup
    {
        return Tables\Actions\ActionGroup::make([
            Tables\Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => Excel::download(new ItemTemplateExport, 'template_import_barang.xlsx')),

            Tables\Actions\Action::make('exportData')
                ->label('Export Data Stok')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => Excel::download(new ItemsExport, 'data_stok_' . date('Y-m-d') . '.xlsx')),
        ])
            ->label('Menu Excel')
            ->icon('heroicon-m-ellipsis-vertical')
            ->color('info');
    }

    /**
     * Aksi import data barang dari file Excel.
     */
    private static function importExcelAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('importExcel')
            ->label('Import Barang')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->form([
                Forms\Components\FileUpload::make('attachment')
                    ->label('Upload File Excel (.xlsx)')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->disk('public')
                    ->directory('imports')
                    ->required(),
            ])
            ->action(function (array $data) {
                $filePath = Storage::disk('public')->path($data['attachment']);
                Excel::import(new ItemImport, $filePath);

                Notification::make()
                    ->title('Sukses Import')
                    ->body('Data barang berhasil ditambahkan ke database.')
                    ->success()
                    ->send();
            });
    }
}

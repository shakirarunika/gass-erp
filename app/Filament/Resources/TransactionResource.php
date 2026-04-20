<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Imports\TransactionImport;
use App\Models\InventoryStock;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Resource untuk mengelola Transaksi Barang (Masuk/Keluar).
 *
 * Transaksi adalah satu-satunya cara mengubah stok gudang.
 * Mendukung tipe IN/OUT, approval workflow, dan validasi stok.
 */
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';
    protected static ?string $navigationLabel = 'Transaksi Barang';
    protected static ?string $navigationGroup = 'Aktivitas Gudang';
    protected static ?int $navigationSort = 1;

    /** Badge kuning: jumlah transaksi DRAFT belum diproses. */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'DRAFT')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'description'];
    }

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public static function form(Form $form): Form
    {
        return $form->schema([
            self::transactionInfoSection(),
            self::itemDetailsSection(),
        ]);
    }

    /** Section header transaksi. */
    private static function transactionInfoSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Informasi Transaksi')
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('No. Transaksi')
                    ->placeholder('Otomatis (TRX/...)')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\DatePicker::make('trx_date')
                    ->label('Tanggal')->required()->default(now())
                    ->native(false)->displayFormat('d/m/Y'),

                Forms\Components\Select::make('type')
                    ->label('Tipe Gerakan')
                    ->options(['IN' => 'Masuk (+)', 'OUT' => 'Keluar (-)'])
                    ->required()->live()
                    ->afterStateUpdated(fn (Set $set) => $set('category', null)),

                Forms\Components\Select::make('category')
                    ->label('Kategori')
                    ->options(fn (Get $get) => match ($get('type')) {
                        'IN' => [
                            'PURCHASE' => '📦 Pembelian Vendor',
                            'RETURN_IN' => '🔄 Retur dari User',
                            'ADJUSTMENT_IN' => '⚖️ Koreksi Stok (+)',
                        ],
                        'OUT' => [
                            'USAGE' => '🛠️ Pemakaian Normal',
                            'CSR' => '🎁 Sumbangan / CSR',
                            'SCRAP' => '🗑️ Pemusnahan (Rusak)',
                            'RETURN_VENDOR' => '🔙 Retur ke Supplier',
                            'ADJUSTMENT_OUT' => '⚖️ Koreksi Stok (-)',
                        ],
                        default => [],
                    })
                    ->required()->live(),

                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label('Gudang')->required()->searchable()->preload()->live(),

                Forms\Components\Select::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Departemen Peminta')->searchable()->preload()
                    ->visible(fn (Get $get) => $get('type') === 'OUT' && in_array($get('category'), ['USAGE', 'CSR']))
                    ->required(fn (Get $get) => $get('type') === 'OUT' && $get('category') === 'USAGE'),

                Forms\Components\Select::make('status')
                    ->options(
                        fn () => (auth()->user()?->role === 'ADMIN')
                            ? ['DRAFT' => 'Draft (Simpan Saja)', 'APPROVED' => 'Approved (Update Stok)']
                            : ['DRAFT' => 'Draft (Simpan Saja)']
                    )
                    ->default('DRAFT')->required()
                    ->disabled(fn () => auth()->user()?->role !== 'ADMIN'),

                Forms\Components\Textarea::make('description')
                    ->label('Keterangan')->rows(3)->columnSpanFull(),
            ])->columns(2);
    }

    /** Section repeater daftar barang. */
    private static function itemDetailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Daftar Barang')
            ->schema([
                Forms\Components\Repeater::make('details')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('item_id')
                            ->relationship('item', 'name')
                            ->label('Barang')->required()->searchable()->preload()
                            ->live()->columnSpan(2)->distinct(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Qty')->numeric()->minValue(1)->required()->columnSpan(1)
                            ->maxValue(function (Get $get) {
                                if ($get('../../type') !== 'OUT') return null;
                                $wh = $get('../../warehouse_id');
                                $item = $get('item_id');
                                if (!$wh || !$item) return null;
                                return InventoryStock::where('warehouse_id', $wh)
                                    ->where('item_id', $item)->value('quantity') ?? 0;
                            }),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga')->numeric()->minValue(0)->default(0)->prefix('Rp')
                            ->visible(fn (Get $get) => $get('../../type') === 'IN')
                            ->required(fn (Get $get) => $get('../../type') === 'IN')
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('current_stock_info')
                            ->label('Stok Tersedia')
                            ->content(function (Get $get) {
                                $wh = $get('../../warehouse_id');
                                $item = $get('item_id');
                                if (!$wh) return 'Pilih Gudang dulu';
                                if (!$item) return '-';
                                $stock = InventoryStock::where('warehouse_id', $wh)
                                    ->where('item_id', $item)->value('quantity') ?? 0;
                                return $stock . ' Unit';
                            })
                            ->visible(fn (Get $get) => $get('../../type') === 'OUT')
                            ->columnSpan(1),
                    ])
                    ->columns(5)
                    ->addActionLabel('Tambah Barang'),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')->striped()
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('No. Transaksi')->searchable()->copyable()
                    ->weight('bold')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('trx_date')
                    ->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'IN' => 'success', 'OUT' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'warning', 'APPROVED' => 'success',
                    }),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state)),
            ])
            ->headerActions([self::importExcelAction()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([self::protectedBulkDeleteAction()]),
            ]);
    }

    /** Import transaksi dari Excel (hanya ADMIN). */
    private static function importExcelAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('importExcel')
            ->label('Import Excel')->icon('heroicon-o-arrow-up-tray')->color('success')
            ->visible(fn () => auth()->user()?->role === 'ADMIN')
            ->form([
                Forms\Components\Placeholder::make('import_notes')
                    ->content('Kolom wajib: trx_date, type, warehouse/gudang, item_code/kode_barang, quantity/qty. Kolom opsional: batch, category, status, department/departemen, description/keterangan, item_name/nama_barang, price/harga.'),
                Forms\Components\FileUpload::make('attachment')
                    ->label('Upload File Excel (.xlsx)')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                    ->disk('public')->directory('imports')->required(),
            ])
            ->action(function (array $data) {
                $filePath = Storage::disk('public')->path($data['attachment']);
                Excel::import(new TransactionImport(), $filePath);
                Notification::make()->title('Sukses Import')
                    ->body('Data transaksi berhasil ditambahkan ke database.')
                    ->success()->send();
            });
    }

    /** Bulk delete dengan proteksi: non-admin tidak bisa hapus APPROVED. */
    private static function protectedBulkDeleteAction(): Tables\Actions\DeleteBulkAction
    {
        return Tables\Actions\DeleteBulkAction::make()
            ->action(function (Collection $records) {
                $isAdmin = (auth()->user()->role ?? null) === 'ADMIN';
                $deletedCount = 0;

                foreach ($records as $record) {
                    if ($record->status === 'APPROVED' && !$isAdmin) continue;
                    $record->delete();
                    $deletedCount++;
                }

                if ($deletedCount < $records->count() && !$isAdmin) {
                    Notification::make()->warning()
                        ->title('Sebagian Data Tidak Dihapus')
                        ->body('Transaksi yang sudah APPROVED tidak dapat dihapus.')
                        ->send();
                }
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit'   => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}

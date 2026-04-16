<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOpnameResource\Pages;
use App\Models\StockOpname;
use App\Models\InventoryStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Stock Opname';
    protected static ?string $navigationGroup = 'Aktivitas Gudang';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Info Audit')
                    ->disabled(fn($record) => $record?->status === 'PROCESSED')
                    ->description('Pilih gudang untuk memuat daftar stok sistem secara otomatis.')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->label('Gudang yang Diaudit')
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload()
                            // 👇 PERBAIKAN LOGIC: Hanya kunci jika ada item yang punya item_id
                            ->disabled(function (Get $get) {
                                $details = $get('details') ?? [];
                                return collect($details)->contains(fn($item) => filled($item['item_id'] ?? null));
                            })
                            ->dehydrated()
                            /* ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) {
                                    $set('details', []);
                                    return;
                                }

                                // AMBIL SEMUA BARANG (448 barang)
                                $allItems = \App\Models\Item::all();

                                $dataRepeater = $allItems->map(function ($item) use ($state) {
                                    // Cari stok barang ini di gudang terpilih
                                    $stock = \App\Models\InventoryStock::where('item_id', $item->id)
                                        ->where('warehouse_id', $state)
                                        ->first();

                                    return [
                                        'item_id'      => $item->id,
                                        'system_qty'   => $stock ? $stock->quantity : 0, // Kalau gak ada di gudang, isi 0
                                        'physical_qty' => 0,
                                        'description'  => null,
                                    ];
                                })->toArray();

                                $set('details', $dataRepeater);
                            }) */
                            ->helperText('Dropdown terkunci jika sudah ada barang di daftar. Hapus barang untuk ganti gudang.'),

                        Forms\Components\DatePicker::make('opname_date')
                            ->label('Tanggal Audit')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('reason')
                            ->label('Nama/Catatan Audit')
                            ->placeholder('Contoh: Opname Akhir Bulan - Gudang Sentul')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->options([
                                'DRAFT'     => 'Draft (Proses Hitung)',
                                'PROCESSED' => 'Processed (Final & Update Stok)',
                            ])
                            ->default('DRAFT')
                            ->required()
                            // Proteksi Acting Supervisor: Cuma Admin yang bisa finalisasi
                            ->disabled(fn(string $operation) => $operation === 'edit' && auth()->user()->role !== 'ADMIN'),
                    ])->columns(3),

                Forms\Components\Section::make('Hasil Hitung Fisik')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->relationship('details') // WAJIB ada di Model!
                            ->disabled(fn($record) => $record?->status === 'PROCESSED')
                            ->schema([
                                Forms\Components\Select::make('item_id')
                                    ->relationship('item', 'name')
                                    ->label('Barang')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('system_qty')
                                    ->label('Sistem')
                                    ->numeric()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('physical_qty')
                                    ->label('Fisik')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true),

                                Placeholder::make('variance_hint')
                                    ->label('Selisih')
                                    ->content(function (Get $get) {
                                        $sistem = (float) $get('system_qty');
                                        $fisik  = (float) $get('physical_qty');
                                        $diff   = $fisik - $sistem;
                                        $color  = $diff < 0 ? 'text-danger-600' : ($diff > 0 ? 'text-success-600' : 'text-gray-500');
                                        return new HtmlString("<span class='font-bold {$color}'>{$diff}</span>");
                                    }),

                                Forms\Components\TextInput::make('description')
                                    ->label('Keterangan Selisih')
                                    ->required(fn(Get $get) => (float)$get('physical_qty') !== (float)$get('system_qty'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(5)
                            ->addable(false)
                            ->deletable(false)
                    ])
            ]);
    }

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
                    ->color(fn(string $state) => $state === 'DRAFT' ? 'warning' : 'success'),

                // 1. Ganti Total Item jadi Total Valuasi (Rupiah)
                Tables\Columns\TextColumn::make('total_valuation')
                    ->label('Nilai Aset')
                    ->money('IDR')
                    ->state(fn(StockOpname $record) => $record->details()->sum(\DB::raw('physical_qty * cost_at_opname')))
                    ->color('gray'),

                // 2. Akurasi dengan deskripsi (tetap dipertahankan karena sudah informatif)
                Tables\Columns\TextColumn::make('accuracy')
                    ->label('Akurasi')
                    ->state(fn(StockOpname $record): string => $record->accuracy . '%')
                    ->badge()
                    ->color(fn($state) => match (true) {
                        (float) $state >= 99 => 'success',
                        (float) $state >= 95 => 'warning',
                        default => 'danger',
                    })
                    ->description(fn(StockOpname $record) => "Audited: " . $record->details()->count() . " Items"),

                // 3. Tambahkan info "Item Selisih" biar lebih Actionable
                Tables\Columns\TextColumn::make('variance_count')
                    ->label('Item Selisih')
                    ->state(fn(StockOpname $record) => $record->details()->whereRaw('physical_qty != system_qty')->count() . " Barang")
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color(fn($state) => (int)$state > 0 ? 'danger' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['DRAFT' => 'Draft', 'PROCESSED' => 'Processed']),
            ])

            ->headerActions([])

            ->actions([
                Tables\Actions\EditAction::make(),
                // ... Aksi Download Excel lo yang lama sudah OK
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
}

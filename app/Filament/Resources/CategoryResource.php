<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Resource untuk mengelola data Kategori Barang.
 *
 * Kategori digunakan sebagai pengelompokan utama barang dan
 * menjadi prefix pada kode barang yang di-generate otomatis.
 * Dilengkapi proteksi delete agar kategori yang masih memiliki
 * barang tidak dapat dihapus.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 1;

    /**
     * Definisi form untuk Create & Edit kategori.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kategori')
                    ->description('Pastikan kode kategori unik dan mudah diingat.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Misal: Alat Kebersihan'),

                        self::uppercaseCodeField(maxLength: 5, placeholder: 'Misal: AKB')
                            ->helperText('Maksimal 5 karakter. Digunakan sebagai prefix Kode Barang.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Definisi tabel untuk halaman daftar kategori.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua Status')
                    ->trueLabel('Hanya Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::protectedDeleteAction(
                    relation: 'items',
                    message: 'Kategori ini masih digunakan oleh barang lain. Hapus/pindahkan barangnya terlebih dahulu.'
                ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Reusable Field Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Buat field kode dengan validasi uppercase dan alfanumerik.
     *
     * Pattern ini digunakan berulang di banyak resource master data
     * untuk memastikan konsistensi format kode.
     */
    private static function uppercaseCodeField(int $maxLength = 10, string $placeholder = ''): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('code')
            ->label('Kode Kategori')
            ->required()
            ->unique(ignoreRecord: true)
            ->maxLength($maxLength)
            ->placeholder($placeholder)
            ->regex('/^[A-Z0-9]+$/')
            ->validationMessages([
                'regex' => 'Kode hanya boleh Huruf Kapital dan Angka (tanpa spasi).',
            ])
            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state));
    }

    /**
     * Buat aksi delete dengan proteksi relasi.
     *
     * Mencegah penghapusan record jika masih memiliki relasi aktif.
     * Menampilkan notifikasi error dan membatalkan aksi jika terdeteksi.
     */
    private static function protectedDeleteAction(string $relation, string $message): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->before(function (Tables\Actions\DeleteAction $action, Category $record) use ($relation, $message) {
                if ($record->{$relation}()->count() > 0) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal Menghapus')
                        ->body($message)
                        ->send();

                    $action->cancel();
                }
            });
    }
}

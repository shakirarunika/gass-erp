<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource untuk mengelola data Satuan Barang.
 *
 * Satuan digunakan sebagai unit pengukuran pada barang
 * (contoh: Pieces, Kilogram, Roll, Liter).
 * Dilengkapi proteksi delete agar satuan yang masih
 * digunakan oleh barang tidak dapat dihapus.
 */
class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 2;

    /**
     * Definisi form untuk Create & Edit satuan.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Satuan')
                    ->description('Pastikan nama dan kode satuan sudah sesuai standar perusahaan.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Satuan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: Pieces, Kilogram, Roll')
                            ->maxLength(255),

                        self::uppercaseCodeField(
                            label: 'Kode Singkatan',
                            maxLength: 10,
                            placeholder: 'Contoh: PCS'
                        ),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Definisi tabel untuk halaman daftar satuan.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->weight('bold')
                    ->color('success')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Digunakan Pada')
                    ->counts('items')
                    ->suffix(' Item')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::protectedDeleteAction(),
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
            'index'  => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit'   => Pages\EditUnit::route('/{record}/edit'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Reusable Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Field kode dengan validasi uppercase dan alfanumerik.
     */
    private static function uppercaseCodeField(
        string $label = 'Kode',
        int $maxLength = 10,
        string $placeholder = ''
    ): Forms\Components\TextInput {
        return Forms\Components\TextInput::make('code')
            ->label($label)
            ->required()
            ->unique(ignoreRecord: true)
            ->maxLength($maxLength)
            ->placeholder($placeholder)
            ->regex('/^[A-Z0-9]+$/')
            ->validationMessages([
                'regex' => 'Kode hanya boleh Huruf dan Angka (tanpa spasi).',
            ])
            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
            ->dehydrateStateUsing(fn ($state) => strtoupper($state));
    }

    /**
     * Aksi delete dengan proteksi relasi barang.
     *
     * Mencegah penghapusan satuan jika masih digunakan oleh barang.
     */
    private static function protectedDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->before(function (Tables\Actions\DeleteAction $action, Unit $record) {
                if ($record->items()->exists()) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal Menghapus')
                        ->body('Satuan ini sedang digunakan oleh Barang lain. Ganti satuan barang dulu sebelum menghapus.')
                        ->send();

                    $action->cancel();
                }
            });
    }
}

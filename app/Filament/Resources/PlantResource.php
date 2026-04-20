<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantResource\Pages;
use App\Models\Plant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Resource untuk mengelola data Plant / Site.
 *
 * Plant merupakan lokasi fisik operasional utama (induk dari Gudang).
 * Setiap Plant bisa memiliki banyak gudang di bawahnya.
 * Dilengkapi proteksi delete agar Plant yang masih memiliki
 * gudang aktif tidak dapat dihapus.
 */
class PlantResource extends Resource
{
    protected static ?string $model = Plant::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 1;

    /**
     * Definisi form untuk Create & Edit plant.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Plant / Site')
                    ->description('Data ini adalah lokasi fisik operasional utama (Induk Gudang).')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Plant')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: SENTUL PLANT')
                            ->maxLength(255),

                        self::uppercaseCodeField(
                            label: 'Kode Plant',
                            maxLength: 10,
                            placeholder: 'Contoh: STL'
                        ),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Definisi tabel untuk halaman daftar plant.
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
                    ->label('Nama Plant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouses_count')
                    ->label('Jumlah Gudang')
                    ->counts('warehouses')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::protectedDeleteAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlants::route('/'),
            'create' => Pages\CreatePlant::route('/create'),
            'edit'   => Pages\EditPlant::route('/{record}/edit'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Reusable Field & Action Builders
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
                'regex' => 'Kode hanya boleh Huruf Kapital dan Angka (tanpa spasi).',
            ])
            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
            ->dehydrateStateUsing(fn ($state) => strtoupper($state));
    }

    /**
     * Aksi delete dengan proteksi relasi gudang.
     *
     * Mencegah penghapusan Plant jika masih memiliki gudang aktif.
     */
    private static function protectedDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->before(function (Tables\Actions\DeleteAction $action, Plant $record) {
                if ($record->warehouses()->exists()) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal Menghapus')
                        ->body('Plant ini masih memiliki Gudang aktif. Hapus gudang terlebih dahulu.')
                        ->send();

                    $action->cancel();
                }
            });
    }
}

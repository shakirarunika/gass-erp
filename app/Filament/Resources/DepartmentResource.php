<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource untuk mengelola data Departemen.
 *
 * Departemen merupakan unit organisasi yang digunakan untuk
 * mengelompokkan user/karyawan dan sebagai referensi pada
 * transaksi pemakaian barang (OUT - USAGE).
 * Dilengkapi proteksi delete agar departemen yang masih
 * memiliki karyawan tidak dapat dihapus.
 */
class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 2;

    /**
     * Definisi form untuk Create & Edit departemen.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Departemen')
                    ->description('Gunakan nama resmi departemen sesuai struktur organisasi.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Departemen')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Contoh: Human Resources & General Affairs'),

                        self::uppercaseCodeField(
                            label: 'Kode Singkatan',
                            maxLength: 10,
                            placeholder: 'Contoh: HRGA'
                        ),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Definisi tabel untuk halaman daftar departemen.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->weight('bold')
                    ->color('warning')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Departemen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Jml Karyawan')
                    ->counts('users')
                    ->badge()
                    ->color('gray')
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
            'index'  => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit'   => Pages\EditDepartment::route('/{record}/edit'),
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
                'regex' => 'Kode hanya boleh Huruf Kapital dan Angka (tanpa spasi/simbol).',
            ])
            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state));
    }

    /**
     * Aksi delete dengan proteksi relasi karyawan.
     *
     * Mencegah penghapusan departemen jika masih memiliki user aktif.
     */
    private static function protectedDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->before(function (Tables\Actions\DeleteAction $action, Department $record) {
                if ($record->users()->exists()) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal Menghapus')
                        ->body('Departemen ini masih memiliki Karyawan aktif. Pindahkan karyawan terlebih dahulu.')
                        ->send();

                    $action->cancel();
                }
            });
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Resource untuk mengelola data User / Pengguna sistem.
 *
 * Mendukung 2 role:
 * - ADMIN: Full akses ke seluruh fitur
 * - STAFF: Akses operasional gudang (terbatas)
 *
 * Fitur proteksi:
 * - User tidak bisa menghapus akun dirinya sendiri
 * - Bulk delete juga mencegah penghapusan akun sendiri
 * - Password hanya di-hash dan disimpan jika diisi
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Manajemen User';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 1;

    /*
    |--------------------------------------------------------------------------
    | Form Definition
    |--------------------------------------------------------------------------
    */

    /**
     * Definisi form untuk Create & Edit user.
     *
     * Terdiri dari 2 section:
     * 1. Profil Pengguna — informasi dasar dan penugasan
     * 2. Keamanan Akun — pengaturan password
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profil Pengguna')
                    ->description('Informasi dasar dan penugasan departemen.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Budi Santoso'),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('email@kantor.com'),

                        Forms\Components\Select::make('department_id')
                            ->label('Departemen')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('role')
                            ->label('Hak Akses (Role)')
                            ->options([
                                'ADMIN' => 'Administrator (Full Akses)',
                                'STAFF' => 'Staff Gudang (Operasional)',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                self::securitySection(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Table Definition
    |--------------------------------------------------------------------------
    */

    /**
     * Definisi tabel untuk halaman daftar user.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ADMIN' => 'danger',
                        'STAFF' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('department.code')
                    ->label('Dept')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter Role')
                    ->options([
                        'ADMIN' => 'Administrator',
                        'STAFF' => 'Staff Gudang',
                    ]),

                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Filter Departemen')
                    ->relationship('department', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::selfDeleteProtectionAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    self::selfDeleteProtectionBulkAction(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Section & Action Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Section keamanan akun: pengaturan password.
     *
     * Password hanya di-hash dan disimpan jika field diisi.
     * Pada mode edit, field bersifat opsional (kosongkan jika tidak ingin mengubah).
     */
    private static function securitySection(): Section
    {
        return Section::make('Keamanan Akun')
            ->schema([
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->helperText('Biarkan kosong jika tidak ingin mengubah password user ini.'),
            ]);
    }

    /**
     * Aksi delete dengan proteksi: user tidak bisa menghapus akun sendiri.
     */
    private static function selfDeleteProtectionAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->before(function (Tables\Actions\DeleteAction $action, User $record) {
                if ($record->id === auth()->id()) {
                    Notification::make()
                        ->danger()
                        ->title('Akses Ditolak')
                        ->body('Anda tidak dapat menghapus akun Anda sendiri saat sedang login.')
                        ->send();

                    $action->cancel();
                }
            });
    }

    /**
     * Bulk delete dengan proteksi: mencegah user menghapus akun sendiri
     * dalam seleksi massal.
     */
    private static function selfDeleteProtectionBulkAction(): Tables\Actions\DeleteBulkAction
    {
        return Tables\Actions\DeleteBulkAction::make()
            ->action(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                if ($records->contains(auth()->user())) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal')
                        ->body('Anda tidak dapat menghapus diri sendiri dalam seleksi massal.')
                        ->send();

                    $action->halt();
                }
            });
    }
}

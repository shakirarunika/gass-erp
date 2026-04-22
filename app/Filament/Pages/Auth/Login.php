<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    /**
     * @var view-string
     */
    protected static string $view = 'filament.pages.auth.login';

    protected function getEmailFormComponent(): \Filament\Forms\Components\Component
    {
        return \Filament\Forms\Components\TextInput::make('email')
            ->label('NIK KARYAWAN')
            ->placeholder('Contoh: 04.1234')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): \Filament\Forms\Components\Component
    {
        return \Filament\Forms\Components\TextInput::make('password')
            ->label('KATA SANDI')
            ->placeholder('........')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }
}

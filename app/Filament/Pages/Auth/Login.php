<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    // Pakai layout sendiri, bypass layouts.simple Filament
    protected static string $layout = 'filament.layouts.auth';

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Login - Auliachem CRM';
    }
}

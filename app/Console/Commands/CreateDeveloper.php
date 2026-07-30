<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateDeveloper extends Command
{
    protected $signature = 'app:create-developer
        {email=developer@auliachem.com : Email login akun Developer}
        {--name=Developer : Nama akun}
        {--password= : Password; bila kosong akan dibuat otomatis}';

    protected $description = 'Membuat atau memperbarui akun Developer tersembunyi';

    public function handle(): int
    {
        $password = (string) ($this->option('password') ?: Str::password(20));

        $developer = User::updateOrCreate(
            ['email' => (string) $this->argument('email')],
            [
                'name' => (string) $this->option('name'),
                'password' => $password,
                'role' => User::ROLE_DEVELOPER,
                'status' => 'Active',
                'position' => 'System Developer',
                'target' => 0,
            ]
        );

        $this->info('Akun Developer siap digunakan.');
        $this->line('Email: ' . $developer->email);
        $this->line('Password: ' . $password);
        $this->warn('Simpan password ini sekarang; password tidak ditampilkan lagi.');

        return self::SUCCESS;
    }
}

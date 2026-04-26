<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Buat 4 roles sesuai spec
        $roles = ['admin', 'manager', 'sales', 'marketing'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@auliachem.com'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // Contoh user per role (untuk testing)
        $samples = [
            ['name' => 'Manager Demo',    'email' => 'manager@auliachem.com',   'role' => 'manager'],
            ['name' => 'Sales Demo',      'email' => 'sales@auliachem.com',     'role' => 'sales'],
            ['name' => 'Marketing Demo',  'email' => 'marketing@auliachem.com', 'role' => 'marketing'],
        ];

        foreach ($samples as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole($data['role']);
        }

        $this->command->info('Seeder selesai! Admin: admin@auliachem.com / password');
    }
}

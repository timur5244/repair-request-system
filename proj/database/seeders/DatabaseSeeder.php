<?php

namespace Database\Seeders;

use App\Domain\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'dispatcher@example.test'],
            [
                'name' => 'Диспетчер',
                'password' => Hash::make('password'),
                'role' => UserRole::Dispatcher->value,
            ]
        );

        foreach (['Мастер 1', 'Мастер 2', 'Мастер 3'] as $i => $name) {
            User::query()->updateOrCreate(
                ['email' => 'master'.($i + 1).'@example.test'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => UserRole::Master->value,
                ]
            );
        }
    }
}

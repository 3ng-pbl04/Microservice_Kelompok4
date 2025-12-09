<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Khairii',
            'email' => 'khairii@gmail.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Rey',
            'email' => 'reyy@gmail.com',
            'password' => Hash::make('user12345'),
        ]);
    }
}

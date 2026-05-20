<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        Admin::updateOrCreate(
            ['email' => 'admin@eatwise.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Hamza2002'),
            ]
        );
    }
}

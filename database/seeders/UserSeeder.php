<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::create([
            'first_name' => 'Fabrizzio',
            'last_name' => 'Import',
            'email' => 'fabri@importcba.com',
            'password' => \Illuminate\Support\Facades\Hash::make('import123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        $user->assignRole('admin');
    }
}

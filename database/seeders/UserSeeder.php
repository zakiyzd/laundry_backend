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
       \App\Models\User::create([
        'name' => 'Zaki Admin',
        'email' => 'admin@mail.com',
        'password' => bcrypt('123456'), // Password untuk login nanti
        'role' => 'admin',
    ]);
    
    \App\Models\User::create([
        'name' => 'Budi Pelanggan',
        'email' => 'budi@mail.com',
        'password' => bcrypt('123456'),
        'role' => 'customer',
    ]); //
    }
}

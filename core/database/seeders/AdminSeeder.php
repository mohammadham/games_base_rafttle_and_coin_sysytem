<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admins')->insert([
            'name' => 'Super Admins',
            'email' => 'admin@site.com',
            'username' => 'admin',
            'password' => Hash::make('password'), // You should change this password
            'image' => '6624ee96387ea1713696406.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

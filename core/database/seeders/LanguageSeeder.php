<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('languages')->insert([
            [
                'name' => 'English',
                'code' => 'en',
                'is_default' => 0,
                'image' => '66d2d74aae3ff1725093706.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'فارسی',
                'code' => 'fa',
                'is_default' => 1,
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hindi',
                'code' => 'hi',
                'is_default' => 0,
                'image' => '66d2d7551e3741725093717.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bangla',
                'code' => 'bn',
                'is_default' => 0,
                'image' => '66d2d75e1ee4b1725093726.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Spanish',
                'code' => 'es',
                'is_default' => 0,
                'image' => '66d2d7689db241725093736.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

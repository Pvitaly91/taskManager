<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            "name" =>'Admin',
            "email" =>'Admin@admin.loc',
            "email_verified_at" => now(),
            'password' => bcrypt('admin123'),
        ]);
    }
}

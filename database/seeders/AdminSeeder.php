<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;



class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([

            'name' => 'Aya',
            'email' => 'ayaalsaied207@gmail.com',
            'password' => Hash::make(777777777),
            'type' => 'admin',


        ]);
    }
}

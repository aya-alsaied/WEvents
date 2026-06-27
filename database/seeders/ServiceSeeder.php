<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::insert([
            ['name' => 'Halls'],
            ['name' => 'Food'],
            ['name' => 'Decoration'],
            ['name' => 'Public Parties'],
        ]);
    }
}

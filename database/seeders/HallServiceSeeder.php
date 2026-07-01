<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HallService;

class HallServiceSeeder extends Seeder
{
    public function run(): void
    {
        HallService::insert([

            // خدمات للقاعة رقم 1
            [
                'hall_id' => 1,
                'name' => 'DJ',
                'price' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hall_id' => 1,
                'name' => 'Photography',
                'price' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hall_id' => 1,
                'name' => 'Lighting',
                'price' => 150,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // خدمات للقاعة رقم 2
            [
                'hall_id' => 2,
                'name' => 'Live Band',
                'price' => 300,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hall_id' => 2,
                'name' => 'Projector',
                'price' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hall_id' => 2,
                'name' => 'Flower Decoration',
                'price' => 250,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
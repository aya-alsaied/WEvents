<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hall;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        Hall::insert([
            [
                'provider_id' => 1,
                'name' => 'Royal Hall',
                'type' => 'inside',
                'CapacityOfPeople' => 300,
                'location' => 'Damascus',
                'full_day_price' => 1000,
                'hour_price' => 150,
                'information' => 'Luxury wedding hall',
                'rules' => 'No smoking',
                'images' => json_encode([
                    asset('storage/halls/hall1.jpg')
                ]),
                'buffer_minutes' => 60,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_id' => 1,
                'name' => 'Garden Hall',
                'type' => 'outside',
                'CapacityOfPeople' => 500,
                'location' => 'Damascus',
                'full_day_price' => 1500,
                'hour_price' => 200,
                'information' => 'Outdoor events',
                'rules' => 'No fireworks',
                'images' => json_encode([
                    asset('storage/halls/hall2.jpg')
                ]),
                'buffer_minutes' => 60,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Hall;
use Illuminate\Support\Facades\Storage;

class HallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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
                'images' => json_encode([Storage::url('Halls/hall1.jpg')]),
                'buffer_minutes' => 60,
                'status' => true,
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
                'images' => json_encode([Storage::url('Halls/hall2.jpg')]),
                'buffer_minutes' => 60,
                'status' => true,
            ]
        ]);
    }
}

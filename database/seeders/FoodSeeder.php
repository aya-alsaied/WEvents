<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Food;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Food::insert([
            [
                'provider_id' => 1,
                'name' => 'Open Buffet',
                'description' => 'Luxury buffet',
                'location' => 'Damascus',
                'price' => 20,
                'image' => 'food1.jpg',
                'status' => true,
            ],
            [
                'provider_id' => 1,
                'name' => 'VIP Buffet',
                'description' => 'Premium buffet',
                'location' => 'Damascus',
                'price' => 35,
                'image' => 'food2.jpg',
                'status' => true,
            ]
        ]);
    }
}

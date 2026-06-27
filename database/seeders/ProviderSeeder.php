<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Provider;
use Illuminate\Support\Facades\Hash;

class ProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Provider::insert([
            [
                'name' => 'Royal Events',
                'email' => 'provider1@test.com',
                'password' => Hash::make('12345678'),
                'phone' => '0991111111',
                'country' => 'Syria',
                'type' => 'provider',
                'image' => 'provider1.jpg',
                'background_image' => 'provider1.jpg',
                'isApproved' => true,
            ],
            [
                'name' => 'Golden Wedding',
                'email' => 'provider2@test.com',
                'password' => Hash::make('12345678'),
                'phone' => '0992222222',
                'country' => 'Syria',
                'type' => 'provider',
                'image' => 'provider2.jpg',
                'background_image' => 'provider1.jpg',
                'isApproved' => true,
            ]
        ]);
    }
}

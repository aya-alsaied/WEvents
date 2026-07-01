<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        /* User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);*/

        $this->call([
            AdminSeeder::class,
            ServiceSeeder::class,
            ProviderSeeder::class,
            CustomerSeeder::class,
            ProviderServiceSeeder::class,
            HallSeeder::class,
            HallServiceSeeder::class,
            FoodSeeder::class,
            OccasionSeeder::class,
            DecorationSeeder::class,
            DecorationOccasionSeeder::class,
            PublicPartySeeder::class,
        ]);
    }
}

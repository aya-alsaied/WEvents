<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Occasion;

class OccasionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Occasion::create([
            'name' => 'House Parties'
        ]);

        Occasion::create([
            'name' => 'Wedding'
        ]);

        Occasion::create([
            'name' => 'Graduation'
        ]);

        Occasion::create([
            'name' => 'Birthdays'
        ]);

        Occasion::create([
            'name' => 'Baby Gender'
        ]);

        Occasion::create([
            'name' => 'Conferences'
        ]);
    }
}

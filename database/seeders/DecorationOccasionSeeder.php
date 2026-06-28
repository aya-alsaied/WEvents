<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DecorationOccasionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('decoration_occasion')->insert([
            [
                'decoration_id' => 1,
                'occasion_id' => 2, // Wedding
            ],
            [
                'decoration_id' => 2,
                'occasion_id' => 3, // Graduation
            ],
            [
                'decoration_id' => 3,
                'occasion_id' => 4, // Birthdays
            ],
            [
                'decoration_id' => 4,
                'occasion_id' => 6, // Conferences
            ],
            [
                'decoration_id' => 5,
                'occasion_id' => 1, // House Parties
            ],

            // ديكور واحد لعدة مناسبات
            [
                'decoration_id' => 1,
                'occasion_id' => 4, // Birthdays
            ],
            [
                'decoration_id' => 1,
                'occasion_id' => 5, // Baby Gender
            ],
        ]);
    }
}
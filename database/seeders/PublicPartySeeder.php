<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PublicParty;

class PublicPartySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PublicParty::insert([
            [
                'provider_id' => 2,
                'name' => 'Summer Festival',
                'information' => 'Live music event',
                'date' => now()->addMonth(),
                'start_time' => '18:00:00',
                'end_time' => '23:00:00',
                'location' => 'Damascus',
                'price' => 25,
                'image' => 'party.jpg',
                'tickets' => 200,
                'status' => true,
            ]
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProviderServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('provider_service')->insert([
            [
                'provider_id' => 1,
                'service_id' => 1
            ],
            [
                'provider_id' => 1,
                'service_id' => 2
            ],
            [
                'provider_id' => 2,
                'service_id' => 3
            ]
        ]);
    }
}

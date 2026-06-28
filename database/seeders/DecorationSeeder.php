<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Decoration;
use Illuminate\Support\Facades\Storage;

class DecorationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Decoration::insert([
            [
                'provider_id' => 2,
                'information' => 'Wedding decoration package',
                'location' => 'Damascus',
                'price' => 500,
                'images' => json_encode([Storage::url('Decorations/decoration1.jpg')]),
                'status' => true,
            ]
        ]);
    }
}

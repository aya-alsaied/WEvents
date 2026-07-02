<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Decoration;
use Illuminate\Support\Facades\Storage;

class DecorationSeeder extends Seeder
{
    public function run(): void
    {
        Decoration::insert([
            [
                'provider_id' => 2,
                'information' => 'Luxury decoration package',
                'location' => 'Damascus',
                'price' => 500,
                'images' => json_encode([
                    Storage::url('storage/Decorations/decoration1.jpg')
                ]),
                'status' => true,
            ],
            [
                'provider_id' => 2,
                'information' => 'Modern decoration with flowers and lighting',
                'location' => 'Damascus',
                'price' => 700,
                'images' => json_encode([
                    Storage::url('Decorations/decoration2.jpg')
                ]),
                'status' => true,
            ],
            [
                'provider_id' => 2,
                'information' => 'Simple balloon decoration package',
                'location' => 'Aleppo',
                'price' => 250,
                'images' => json_encode([
                    Storage::url('storage/Decorations/decoration3.jpg')
                ]),
                'status' => true,
            ],
            [
                'provider_id' => 2,
                'information' => 'Premium stage and lighting decoration',
                'location' => 'Homs',
                'price' => 1000,
                'images' => json_encode([
                    Storage::url('Decorations/decoration4.jpg')
                ]),
                'status' => true,
            ],
            [
                'provider_id' => 2,
                'information' => 'Elegant decoration with flowers and candles',
                'location' => 'Latakia',
                'price' => 600,
                'images' => json_encode([
                    Storage::url('Decorations/decoration5.jpg')
                ]),
                'status' => true,
            ],
        ]);
    }
}
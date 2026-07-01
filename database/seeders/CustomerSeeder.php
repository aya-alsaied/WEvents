<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Aya',
                'email' => 'aya@test.com',
                'password' => Hash::make('12345678'),
                'phone' => '0933333333',
                'country' => 'Syria',
                'type' => 'customer',
                'isApproved' => true,
            ],
            [
                'name' => 'Ahmad',
                'email' => 'ahmad@test.com',
                'password' => Hash::make('12345678'),
                'phone' => '0944444444',
                'country' => 'Syria',
                'type' => 'customer',
                'isApproved' => true,
            ]
        ];

        foreach ($customers as $customerData) {
            // ننشئ العميل عبر الموديل
            $customer = Customer::create($customerData);
            
            // ننشئ له المحفظة تلقائياً
            $customer->wallet()->create([
                'balance' => 0
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. نقوم بحفظ الأدمن المنشأ داخل متغير اسمه $admin
        $admin = Admin::create([
            'name' => 'Aya',
            'email' => 'ayaalsaied207@gmail.com',
            'password' => Hash::make('777777777'), // يفضل وضع الرقم بين علامتي تنصيص ليعامل كنص آمن
            'type' => 'admin',
        ]);

        // 2. الآن المتغير $admin أصبح معرّفاً ويمكننا إنشاء المحفظة له بنجاح
        $admin->wallet()->create([
            'balance' => 0
        ]);
    }
}
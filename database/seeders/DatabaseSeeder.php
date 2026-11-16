<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Setting::query()->firstOrCreate(
            ['id' => 1],
            [
                'monthly_savings_goal' => '0.00',
                'minimum_savings_balance' => '0.00',
            ]
        );
    }
}

<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingsRepository
{
    public function current(): Setting
    {
        return Setting::query()->firstOrCreate(
            ['id' => 1],
            [
                'monthly_savings_goal' => '0.00',
                'minimum_savings_balance' => '0.00',
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Setting $settings, array $attributes): Setting
    {
        $settings->update($attributes);

        return $settings->refresh();
    }
}

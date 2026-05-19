<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed default global site settings.
     */
    public function run(): void
    {
        $defaults = [
            ['key' => 'maintenance_mode', 'value' => false, 'type' => 'boolean'],
        ];

        foreach ($defaults as $row) {
            Setting::firstOrCreate(
                ['key' => $row['key']],
                [
                    'value' => $row['type'] === 'boolean'
                        ? ($row['value'] ? '1' : '0')
                        : (string) $row['value'],
                    'type' => $row['type'],
                ]
            );
        }
    }
}

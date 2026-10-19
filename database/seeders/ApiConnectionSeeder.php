<?php

namespace Database\Seeders;

use App\Models\ApiConnection;
use Illuminate\Database\Seeder;

class ApiConnectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = [
            [
                'name' => 'Open-Meteo Weather',
                'provider' => 'open-meteo',
                'auth_type' => 'none',
            ],
            [
                'name' => 'JSONPlaceholder Demo',
                'provider' => 'jsonplaceholder',
                'auth_type' => 'none',
            ],
        ];

        foreach ($definitions as $definition) {
            $connection = ApiConnection::query()->updateOrCreate(
                [
                    'tenant_id' => null,
                    'name' => $definition['name'],
                ],
                [
                    'provider' => $definition['provider'],
                    'base_url' => config('signage.api_providers.'.$definition['provider'].'.base_url'),
                    'auth_type' => $definition['auth_type'],
                    'is_active' => true,
                ],
            );

            $connection->forceFill(['is_approved' => true])->save();
        }
    }
}

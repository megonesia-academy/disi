<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Demo Client',
                'billing_email' => 'billing@example.com',
                'status' => Tenant::STATUS_ACTIVE,
                'settings' => [
                    'timezone' => 'Asia/Jakarta',
                    'default_orientation' => 'landscape',
                ],
            ],
        );

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => Carbon::now(),
            ],
        );

        $tenant->users()->syncWithoutDetaching([
            $user->id => [
                'role' => 'owner',
                'accepted_at' => Carbon::now(),
            ],
        ]);
    }
}

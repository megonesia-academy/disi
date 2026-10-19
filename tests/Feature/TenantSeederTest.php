<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\TenantSeeder;

it('seeds multiple demo tenants with owners and workspace data', function (): void {
    $this->seed(TenantSeeder::class);

    expect(Tenant::query()->count())->toBe(7)
        ->and(Tenant::query()->where('slug', 'kopi-senja')->value('status'))
        ->toBe(Tenant::STATUS_SUSPENDED)
        ->and(User::query()->where('email', 'test@example.com')->value('app_role'))
        ->toBe(User::APP_ROLE_ADMIN)
        ->and(User::query()->where('email', 'mall@example.com')->value('app_role'))
        ->toBe(User::APP_ROLE_SUBSCRIBER);

    $tenant = Tenant::query()->where('slug', 'megonesia-mall')->firstOrFail();

    expect($tenant->users()->count())->toBe(1)
        ->and($tenant->screens()->count())->toBe(1)
        ->and($tenant->devices()->count())->toBe(1)
        ->and($tenant->playlists()->count())->toBe(1)
        ->and($tenant->schedules()->count())->toBe(1)
        ->and($tenant->mediaAssets()->count())->toBe(2);
});

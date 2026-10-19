<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lists platform users with their roles', function (): void {
    $admin = User::factory()->appAdmin()->create();
    User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Users')
            ->has('users', 2)
            ->where('admins_count', 1)
            ->where('subscribers_count', 1)
            ->where('users.0.email', $admin->email)
            ->where('users.0.app_role', User::APP_ROLE_ADMIN)
            ->where('users.0.is_self', true)
        );
});

it('promotes a subscriber to application admin', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $subscriber = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $subscriber), [
            'app_role' => User::APP_ROLE_ADMIN,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($subscriber->refresh()->app_role)->toBe(User::APP_ROLE_ADMIN)
        ->and($subscriber->isAppAdmin())->toBeTrue();
});

it('demotes another application admin to subscriber', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $otherAdmin = User::factory()->appAdmin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $otherAdmin), [
            'app_role' => User::APP_ROLE_SUBSCRIBER,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($otherAdmin->refresh()->app_role)->toBe(User::APP_ROLE_SUBSCRIBER);
});

it('prevents admins from changing their own platform role', function (): void {
    $admin = User::factory()->appAdmin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $admin), [
            'app_role' => User::APP_ROLE_SUBSCRIBER,
        ])
        ->assertSessionHasErrors('app_role');

    expect($admin->refresh()->app_role)->toBe(User::APP_ROLE_ADMIN);
});

it('rejects invalid platform roles', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $subscriber = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $subscriber), [
            'app_role' => 'owner',
        ])
        ->assertSessionHasErrors('app_role');

    expect($subscriber->refresh()->app_role)->toBe(User::APP_ROLE_SUBSCRIBER);
});

it('forbids subscribers from managing platform users', function (): void {
    $subscriber = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($subscriber)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($subscriber)
        ->patch(route('admin.users.role', $other), [
            'app_role' => User::APP_ROLE_ADMIN,
        ])
        ->assertForbidden();

    expect($other->refresh()->app_role)->toBe(User::APP_ROLE_SUBSCRIBER);
});

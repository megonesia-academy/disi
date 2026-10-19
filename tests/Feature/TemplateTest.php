<?php

use App\Models\ContentTemplate;
use App\Models\PlatformTemplate;
use App\Models\Tenant;
use App\Models\TenantEntitlement;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\PlatformTemplateLibrary;
use Database\Seeders\FeaturePriceSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createTemplateTenant(): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Template Workspace',
        'slug' => 'template-workspace-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
        'token_balance' => 100,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

function createPlatformTemplate(int $tokenCost = 0, string $slug = 'promo-board'): PlatformTemplate
{
    $template = PlatformTemplate::query()->create([
        'name' => 'Promo Board',
        'slug' => $slug,
        'html' => '<main><h1>{{ title }}</h1></main>',
        'css' => 'main { color: white; }',
        'schema' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Promo'],
        ],
        'token_cost' => $tokenCost,
        'is_active' => true,
    ]);

    app(PlatformTemplateLibrary::class)->sync($template);

    return $template;
}

it('syncs platform templates to every tenant', function (): void {
    [$tenantA] = createTemplateTenant();
    [$tenantB] = createTemplateTenant();

    $template = createPlatformTemplate(0);

    expect(ContentTemplate::query()
        ->where('platform_template_id', $template->id)
        ->count())->toBe(2)
        ->and(ContentTemplate::query()
            ->where('tenant_id', $tenantA->id)
            ->where('slug', 'promo-board')
            ->exists())->toBeTrue()
        ->and(ContentTemplate::query()
            ->where('tenant_id', $tenantB->id)
            ->where('slug', 'promo-board')
            ->exists())->toBeTrue();
});

it('lets tenants rent paid templates and unlocks them for playlists', function (): void {
    $this->seed(FeaturePriceSeeder::class);
    [$tenant, $owner] = createTemplateTenant();
    $template = createPlatformTemplate(30, 'paid-board');

    $copy = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('platform_template_id', $template->id)
        ->sole();

    $this->actingAs($owner)
        ->get(route('playlists.create'))
        ->assertInertia(fn (Assert $page) => $page->has('templates', 0));

    $this->actingAs($owner)
        ->post(route('templates.rent', $copy))
        ->assertRedirect();

    $copy->refresh();

    expect($copy->is_rented)->toBeTrue()
        ->and($tenant->refresh()->token_balance)->toBe(70)
        ->and(TokenTransaction::query()
            ->where('tenant_id', $tenant->id)
            ->where('feature_key', 'template')
            ->value('tokens'))->toBe(-30);

    $this->actingAs($owner)
        ->get(route('playlists.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('templates', 1)
            ->where('templates.0.id', $copy->id)
        );
});

it('makes free templates available without renting', function (): void {
    $this->seed(FeaturePriceSeeder::class);
    [$tenant, $owner] = createTemplateTenant();
    createPlatformTemplate(0, 'free-board');

    $this->actingAs($owner)
        ->get(route('templates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/Index')
            ->has('templates', 1)
            ->where('templates.0.token_cost', 0)
            ->where('templates.0.is_rented', false)
            ->where('balance', 100)
        );

    $this->actingAs($owner)
        ->get(route('playlists.create'))
        ->assertInertia(fn (Assert $page) => $page->has('templates', 1));
});

it('rejects renting a template when tokens are insufficient', function (): void {
    $this->seed(FeaturePriceSeeder::class);
    [$tenant, $owner] = createTemplateTenant();
    $tenant->forceFill(['token_balance' => 5])->save();
    $template = createPlatformTemplate(30, 'expensive-board');

    $copy = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('platform_template_id', $template->id)
        ->sole();

    $this->actingAs($owner)
        ->post(route('templates.rent', $copy))
        ->assertSessionHasErrors('billing');

    expect($copy->refresh()->is_rented)->toBeFalse()
        ->and($tenant->refresh()->token_balance)->toBe(5);
});

it('lets admins create and update platform templates and syncs copies', function (): void {
    [$tenant] = createTemplateTenant();
    $admin = User::factory()->appAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => 'Admin Board',
            'slug' => 'admin-board',
            'html' => '<h1>{{ title }}</h1>',
            'css' => 'h1 { color: red; }',
            'schema_json' => '[{"name":"title","label":"Title","type":"text","default":"Hi"}]',
            'token_cost' => 20,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.templates.index'));

    $platform = PlatformTemplate::query()->where('slug', 'admin-board')->sole();

    expect(ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('platform_template_id', $platform->id)
        ->exists())->toBeTrue();

    $this->actingAs($admin)
        ->put(route('admin.templates.update', $platform), [
            'name' => 'Admin Board Updated',
            'slug' => 'admin-board',
            'html' => '<h1>{{ title }}</h1>',
            'css' => 'h1 { color: blue; }',
            'schema_json' => '[{"name":"title","label":"Title","type":"text","default":"Hi"}]',
            'token_cost' => 25,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.templates.index'));

    $copy = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('platform_template_id', $platform->id)
        ->sole();

    expect($copy->name)->toBe('Admin Board Updated')
        ->and($copy->css)->toBe('h1 { color: blue; }')
        ->and($platform->refresh()->token_cost)->toBe(25);
});

it('forbids subscribers from managing platform templates', function (): void {
    [$tenant, $owner] = createTemplateTenant();
    $template = createPlatformTemplate(0, 'free-board');

    $this->actingAs($owner)
        ->get(route('admin.templates.index'))
        ->assertForbidden();

    $this->actingAs($owner)
        ->put(route('admin.templates.update', $template), [
            'name' => 'Hacked',
            'slug' => 'free-board',
            'html' => '<h1>x</h1>',
            'token_cost' => 0,
            'is_active' => true,
        ])
        ->assertForbidden();
});

it('keeps tenant entitlements untouched when templates sync', function (): void {
    [$tenant] = createTemplateTenant();
    $template = createPlatformTemplate(0, 'free-board');

    TenantEntitlement::query()->create([
        'tenant_id' => $tenant->id,
        'feature_key' => 'extra_screen',
        'quantity' => 2,
    ]);

    app(PlatformTemplateLibrary::class)->sync($template);

    expect(TenantEntitlement::query()
        ->where('tenant_id', $tenant->id)
        ->where('feature_key', 'extra_screen')
        ->value('quantity'))->toBe(2);
});

it('blocks paid template rentals when the subscription is past due', function (): void {
    $this->seed(FeaturePriceSeeder::class);
    [$tenant, $owner] = createTemplateTenant();

    $tenant->forceFill([
        'subscription_status' => Tenant::SUBSCRIPTION_PAST_DUE,
    ])->save();

    $template = createPlatformTemplate(30, 'past-due-board');

    $copy = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('platform_template_id', $template->id)
        ->sole();

    $this->actingAs($owner)
        ->post(route('templates.rent', $copy))
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect($copy->refresh()->is_rented)->toBeFalse()
        ->and($tenant->refresh()->token_balance)->toBe(100)
        ->and(TokenTransaction::query()->count())->toBe(0);
});

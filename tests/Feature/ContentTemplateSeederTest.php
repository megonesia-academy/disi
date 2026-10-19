<?php

use App\Models\ContentTemplate;
use App\Models\Tenant;
use Database\Seeders\ContentTemplateSeeder;

it('seeds hospital and airport signage templates for every tenant', function (): void {
    $tenant = Tenant::query()->create([
        'name' => 'Template Workspace',
        'slug' => 'template-workspace',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $this->seed(ContentTemplateSeeder::class);

    $hospital = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'hospital-service-board')
        ->firstOrFail();
    $airport = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'airport-departures-board')
        ->firstOrFail();

    expect($hospital->is_active)->toBeTrue()
        ->and($hospital->html)->toContain('{{ current_number }}')
        ->and($hospital->schema)->toHaveCount(21)
        ->and($airport->is_active)->toBeTrue()
        ->and($airport->html)->toContain('{{#each flights}}')
        ->and($airport->schema)->toHaveCount(8)
        ->and($airport->schema[5]['type'])->toBe('json')
        ->and($airport->schema[5]['default'])->toHaveCount(6);
});

it('seeds restaurant, cafe, and campus templates for every tenant', function (): void {
    $tenant = Tenant::query()->create([
        'name' => 'Template Workspace',
        'slug' => 'template-workspace-'.str()->random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $this->seed(ContentTemplateSeeder::class);

    $restaurant = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'restaurant-menu-board')
        ->firstOrFail();
    $restaurantSpecials = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'restaurant-specials-board')
        ->firstOrFail();
    $cafe = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'cafe-menu-board')
        ->firstOrFail();
    $campus = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'campus-information-board')
        ->firstOrFail();
    $campusEvent = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'campus-event-board')
        ->firstOrFail();
    $imageBoard = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'image-background-board')
        ->firstOrFail();

    expect($restaurant->is_active)->toBeTrue()
        ->and($restaurant->html)->toContain('{{ items }}')
        ->and($restaurant->schema)->toHaveCount(5)
        ->and($restaurantSpecials->is_active)->toBeTrue()
        ->and($restaurantSpecials->html)->toContain('{{#each dishes}}')
        ->and($restaurantSpecials->schema)->toHaveCount(7)
        ->and($restaurantSpecials->schema[4]['type'])->toBe('json')
        ->and($restaurantSpecials->schema[4]['default'])->toHaveCount(4)
        ->and($cafe->is_active)->toBeTrue()
        ->and($cafe->html)->toContain('{{#each menu}}')
        ->and($cafe->schema)->toHaveCount(7)
        ->and($cafe->schema[4]['type'])->toBe('json')
        ->and($cafe->schema[4]['default'])->toHaveCount(6)
        ->and($campus->is_active)->toBeTrue()
        ->and($campus->html)->toContain('{{#each agenda}}')
        ->and($campus->schema)->toHaveCount(11)
        ->and($campus->schema[6]['type'])->toBe('json')
        ->and($campus->schema[6]['default'])->toHaveCount(4)
        ->and($campusEvent->is_active)->toBeTrue()
        ->and($campusEvent->html)->toContain('{{ speaker }}')
        ->and($campusEvent->schema)->toHaveCount(11)
        ->and($imageBoard->is_active)->toBeTrue()
        ->and($imageBoard->html)->toContain("background-image: url('{{ background }}')")
        ->and($imageBoard->schema)->toHaveCount(9)
        ->and($imageBoard->schema[5]['type'])->toBe('image')
        ->and($imageBoard->schema[6]['type'])->toBe('image');
});

it('seeds service, cinema, hotel, school, and showcase templates', function (): void {
    $tenant = Tenant::query()->create([
        'name' => 'Template Workspace',
        'slug' => 'template-workspace-'.str()->random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $this->seed(ContentTemplateSeeder::class);

    $service = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'service-counter-board')
        ->firstOrFail();
    $cinema = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'cinema-showtimes-board')
        ->firstOrFail();
    $hotel = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'hotel-lobby-board')
        ->firstOrFail();
    $school = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'school-schedule-board')
        ->firstOrFail();
    $showcase = ContentTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', 'product-showcase-board')
        ->firstOrFail();

    expect($service->html)->toContain('{{#each counters}}')
        ->and($service->schema)->toHaveCount(9)
        ->and($service->schema[6]['type'])->toBe('json')
        ->and($service->schema[6]['default'])->toHaveCount(4)
        ->and($cinema->html)->toContain('{{#each movies}}')
        ->and($cinema->schema)->toHaveCount(7)
        ->and($cinema->schema[4]['type'])->toBe('json')
        ->and($cinema->schema[4]['default'])->toHaveCount(4)
        ->and($hotel->html)->toContain("background-image: url('{{ background }}')")
        ->and($hotel->schema)->toHaveCount(9)
        ->and($hotel->schema[3]['type'])->toBe('image')
        ->and($school->html)->toContain('{{#each lessons}}')
        ->and($school->schema)->toHaveCount(8)
        ->and($school->schema[5]['type'])->toBe('json')
        ->and($school->schema[5]['default'])->toHaveCount(6)
        ->and($showcase->html)->toContain('{{ product_1_image }}')
        ->and($showcase->schema)->toHaveCount(14)
        ->and($showcase->schema[3]['type'])->toBe('image')
        ->and($showcase->schema[6]['type'])->toBe('image');
});

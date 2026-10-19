<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $platform_template_id
 * @property string $name
 * @property string $slug
 * @property string $html
 * @property string|null $css
 * @property array<int, array<string, mixed>>|null $schema
 * @property bool $is_active
 * @property bool $is_rented
 */
#[Fillable(['tenant_id', 'platform_template_id', 'name', 'slug', 'html', 'css', 'schema', 'is_active', 'is_rented'])]
class ContentTemplate extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_rented' => 'boolean',
            'schema' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function platformTemplate(): BelongsTo
    {
        return $this->belongsTo(PlatformTemplate::class);
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class, 'template_id');
    }
}

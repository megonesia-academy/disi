<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $html
 * @property string|null $css
 * @property array<int, array<string, mixed>>|null $schema
 * @property int $token_cost
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'html', 'css', 'schema', 'token_cost', 'is_active', 'sort_order'])]
class PlatformTemplate extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'schema' => 'array',
            'sort_order' => 'integer',
            'token_cost' => 'integer',
        ];
    }

    public function tenantCopies(): HasMany
    {
        return $this->hasMany(ContentTemplate::class);
    }
}

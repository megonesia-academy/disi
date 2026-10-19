<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $key
 * @property string $label
 * @property string|null $description
 * @property string $kind
 * @property int $token_cost
 * @property int $free_limit
 * @property string $unit_label
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'label', 'description', 'kind', 'token_cost', 'free_limit', 'unit_label', 'is_active', 'sort_order'])]
class FeaturePrice extends Model
{
    use HasUuids;

    public const KIND_ADDON = 'addon';

    public const KIND_USAGE = 'usage';

    public const KIND_SUBSCRIPTION = 'subscription';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'free_limit' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'token_cost' => 'integer',
        ];
    }
}

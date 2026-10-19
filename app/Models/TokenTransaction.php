<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $type
 * @property int $tokens
 * @property int $balance_after
 * @property string|null $feature_key
 * @property string|null $description
 * @property array<string, mixed>|null $meta
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'type', 'tokens', 'balance_after', 'feature_key', 'description', 'meta', 'created_by'])]
class TokenTransaction extends Model
{
    use HasUuids;

    public const TYPE_GRANT = 'grant';

    public const TYPE_TOPUP = 'topup';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SUBSCRIPTION = 'subscription';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_REFUND = 'refund';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance_after' => 'integer',
            'meta' => 'array',
            'tokens' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

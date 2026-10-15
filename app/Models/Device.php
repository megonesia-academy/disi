<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $screen_id
 * @property string $name
 * @property string $key
 * @property string|null $player_secret
 * @property Carbon|null $last_seen_at
 * @property string|null $app_version
 * @property string $status
 * @property array<string, mixed>|null $capabilities
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'screen_id', 'name', 'key', 'player_secret', 'last_seen_at', 'app_version', 'status', 'capabilities', 'is_active'])]
class Device extends Model
{
    use HasUuids;

    public const STATUS_ONLINE = 'online';

    public const STATUS_OFFLINE = 'offline';

    public const OFFLINE_AFTER_MINUTES = 2;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function isOnline(): bool
    {
        if (! $this->is_active || ! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->greaterThanOrEqualTo(
            now()->subMinutes(self::OFFLINE_AFTER_MINUTES),
        );
    }

    public function displayStatus(): string
    {
        return $this->isOnline() ? self::STATUS_ONLINE : self::STATUS_OFFLINE;
    }

    public function hasValidPlayerSecret(?string $secret): bool
    {
        return is_string($secret)
            && is_string($this->player_secret)
            && hash_equals($this->player_secret, $secret);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }
}

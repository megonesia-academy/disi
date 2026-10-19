<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $billing_email
 * @property string $status
 * @property int $token_balance
 * @property string $subscription_status
 * @property Carbon|null $subscription_renews_at
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'billing_email', 'status', 'settings', 'token_balance', 'subscription_status', 'subscription_renews_at'])]
class Tenant extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const SUBSCRIPTION_ACTIVE = 'active';

    public const SUBSCRIPTION_PAST_DUE = 'past_due';

    public const SUBSCRIPTION_CANCELED = 'canceled';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'subscription_renews_at' => 'datetime',
            'token_balance' => 'integer',
        ];
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === self::SUBSCRIPTION_ACTIVE;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'accepted_at'])
            ->withTimestamps();
    }

    public function screens(): HasMany
    {
        return $this->hasMany(Screen::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(TenantEntitlement::class);
    }

    public function topupRequests(): HasMany
    {
        return $this->hasMany(TokenTopupRequest::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $code
 * @property string $token
 * @property string|null $device_id
 * @property Carbon $expires_at
 * @property Carbon|null $paired_at
 */
#[Fillable(['code', 'token', 'device_id', 'expires_at', 'paired_at'])]
class DisplayPairingSession extends Model
{
    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'paired_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPaired(): bool
    {
        return $this->paired_at !== null && $this->device_id !== null;
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

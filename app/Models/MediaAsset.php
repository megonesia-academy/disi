<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $type
 * @property string $disk
 * @property string $path
 * @property string|null $mime_type
 * @property int|null $size
 * @property int|null $duration_seconds
 * @property int|null $width
 * @property int|null $height
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'name', 'type', 'disk', 'path', 'mime_type', 'size', 'duration_seconds', 'width', 'height', 'metadata'])]
class MediaAsset extends Model
{
    use HasUuids;

    public const TYPE_IMAGE = 'image';

    public const TYPE_IMAGE_URL = 'image_url';

    public const TYPE_VIDEO = 'video';

    public const TYPE_VIDEO_URL = 'video_url';

    public const TYPE_AUDIO = 'audio';

    public const TYPE_WEB = 'web';

    /**
     * @var array<int, string>
     */
    public const TYPES = [
        self::TYPE_IMAGE,
        self::TYPE_IMAGE_URL,
        self::TYPE_VIDEO,
        self::TYPE_VIDEO_URL,
        self::TYPE_AUDIO,
        self::TYPE_WEB,
    ];

    /**
     * Media types that reference content by URL instead of a stored file.
     *
     * @var array<int, string>
     */
    public const EXTERNAL_URL_TYPES = [
        self::TYPE_IMAGE_URL,
        self::TYPE_VIDEO_URL,
        self::TYPE_WEB,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'height' => 'integer',
            'metadata' => 'array',
            'size' => 'integer',
            'width' => 'integer',
        ];
    }

    public function isImage(): bool
    {
        return in_array($this->type, [self::TYPE_IMAGE, self::TYPE_IMAGE_URL], true);
    }

    public function isVideo(): bool
    {
        return in_array($this->type, [self::TYPE_VIDEO, self::TYPE_VIDEO_URL], true);
    }

    public function usesExternalUrl(): bool
    {
        return in_array($this->type, self::EXTERNAL_URL_TYPES, true);
    }

    public function publicUrl(): string
    {
        if ($this->usesExternalUrl()) {
            return $this->path;
        }

        if ($this->disk === 'public') {
            return asset('storage/'.ltrim($this->path, '/'));
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }
}

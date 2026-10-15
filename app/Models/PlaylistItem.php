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
 * @property string $playlist_id
 * @property string|null $media_asset_id
 * @property string $template
 * @property string|null $template_id
 * @property string|null $api_connection_id
 * @property array<string, mixed>|null $content
 * @property array<string, mixed>|null $data_source
 * @property int $sort_order
 * @property int $duration_seconds
 * @property string|null $transition
 * @property array<string, mixed>|null $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'playlist_id', 'media_asset_id', 'template', 'template_id', 'api_connection_id', 'content', 'data_source', 'sort_order', 'duration_seconds', 'transition', 'config'])]
class PlaylistItem extends Model
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
            'config' => 'array',
            'content' => 'array',
            'data_source' => 'array',
            'duration_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function contentTemplate(): BelongsTo
    {
        return $this->belongsTo(ContentTemplate::class, 'template_id');
    }

    public function apiConnection(): BelongsTo
    {
        return $this->belongsTo(ApiConnection::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'name', 'provider', 'base_url', 'auth_type', 'credential', 'is_active'])]
class ApiConnection extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_approved' => 'boolean'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

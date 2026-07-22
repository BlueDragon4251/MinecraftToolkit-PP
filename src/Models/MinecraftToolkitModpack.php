<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinecraftToolkitModpack extends Model
{
    protected $table = 'minecraft_toolkit_modpacks';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'manifest_json' => 'array',
            'files_json' => 'array',
            'active' => 'boolean',
            'installed_at' => 'datetime',
        ];
    }

    public function setup(): BelongsTo
    {
        return $this->belongsTo(MinecraftToolkitSetup::class, 'setup_id');
    }
}

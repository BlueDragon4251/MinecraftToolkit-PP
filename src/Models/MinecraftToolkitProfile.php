<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinecraftToolkitProfile extends Model
{
    protected $table = 'minecraft_toolkit_profiles';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['software_json' => 'array', 'packages_json' => 'array', 'setup_json' => 'array', 'shared' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

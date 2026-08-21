<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Models;

use App\Models\Backup;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $server_id
 * @property string $server_uuid
 * @property int|null $user_id
 * @property int|null $setup_id
 * @property int|null $backup_id
 * @property string $status
 * @property string $stage
 * @property array<string, mixed> $payload_json
 * @property string|null $icon_file
 * @property string|null $modpack_file
 * @property string $modpack_mode
 * @property string|null $last_error
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $last_heartbeat_at
 * @property-read Server $server
 * @property-read User|null $user
 * @property-read MinecraftToolkitSetup|null $setup
 * @property-read Backup|null $backup
 */
class MinecraftToolkitSetupOperation extends Model
{
    public const ACTIVE_STATUSES = ['queued', 'backup_pending', 'installing'];

    protected $table = 'minecraft_toolkit_setup_operations';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<MinecraftToolkitSetup, $this> */
    public function setup(): BelongsTo
    {
        return $this->belongsTo(MinecraftToolkitSetup::class, 'setup_id');
    }

    /** @return BelongsTo<Backup, $this> */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }
}

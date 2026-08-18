<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Jobs;

use App\Models\Server;
use App\Repositories\Daemon\DaemonServerRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartMinecraftPostUpdateVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $serverId, public readonly int $packageId) {}

    public function handle(DaemonServerRepository $repository): void
    {
        $server = Server::find($this->serverId);
        if (! $server) {
            return;
        }
        $repository->setServer($server)->power('start');
        CheckMinecraftPostUpdateHealthJob::dispatch($this->serverId, $this->packageId)->delay(now()->addSeconds((int) config('minecrafttoolkit.post_update_health_wait_seconds', 60)));
    }
}

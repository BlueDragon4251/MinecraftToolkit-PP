<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Jobs;

use App\Models\Server;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetup;
use BlueWolf\MinecraftToolkit\Services\MinecraftUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckMinecraftToolkitUpdatesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 3600;

    public function handle(MinecraftUpdateService $updates): void
    {
        MinecraftToolkitSetup::query()->where('setup_status', 'completed')->whereNotNull('server_id')->chunkById(50, function ($setups) use ($updates): void {
            foreach ($setups as $setup) {
                $server = Server::find($setup->server_id);
                if ($server) {
                    try {
                        $updates->checkAll($server, $setup);
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }
            }
        });
    }

    public function uniqueId(): string
    {
        return 'minecrafttoolkit:update-checks';
    }
}

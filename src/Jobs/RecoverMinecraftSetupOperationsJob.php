<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Jobs;

use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetupOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class RecoverMinecraftSetupOperationsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 55;

    public function handle(): void
    {
        if (! Schema::hasTable('minecraft_toolkit_setup_operations')) {
            return;
        }

        MinecraftToolkitSetupOperation::query()
            ->whereIn('status', MinecraftToolkitSetupOperation::ACTIVE_STATUSES)
            ->orderBy('id')
            ->limit(100)
            ->get(['id'])
            ->each(fn (MinecraftToolkitSetupOperation $operation) => RunMinecraftSetupOperationJob::dispatch($operation->id));
    }

    public function uniqueId(): string
    {
        return 'minecrafttoolkit:recover-setup-operations';
    }
}

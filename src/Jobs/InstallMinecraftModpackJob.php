<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Jobs;

use App\Models\Server;
use App\Models\User;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetup;
use BlueWolf\MinecraftToolkit\Services\MinecraftModpackService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Throwable;

class InstallMinecraftModpackJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $serverId,
        public readonly int $setupId,
        public readonly string $source,
        public readonly string $projectId,
        public readonly string $mode,
        public readonly ?string $versionId,
        public readonly ?int $userId,
    ) {}

    public function handle(MinecraftModpackService $modpacks): void
    {
        $server = Server::find($this->serverId);
        $setup = MinecraftToolkitSetup::find($this->setupId);
        if (! $server instanceof Server || ! $setup instanceof MinecraftToolkitSetup) {
            return;
        }

        $modpack = $modpacks->installPublic(
            $server,
            $setup,
            $this->source,
            $this->projectId,
            $this->mode,
            $this->versionId,
            $this->userId
        );

        $this->notifyUser(
            'success',
            trans('minecrafttoolkit::strings.modpacks.install_complete', ['name' => $modpack->name])
        );
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            report($exception);
        }
        $this->notifyUser(
            'danger',
            trans('minecrafttoolkit::strings.modpacks.install_failed'),
            $exception?->getMessage()
        );
    }

    public function uniqueId(): string
    {
        return 'minecrafttoolkit:modpack-install:'.$this->serverId;
    }

    private function notifyUser(string $status, string $title, ?string $body = null): void
    {
        $user = $this->userId !== null ? User::find($this->userId) : null;
        if (! $user instanceof User) {
            return;
        }

        App::setLocale($user->language ?: 'en');
        $notification = Notification::make()
            ->title($title)
            ->status($status);
        if (is_string($body) && trim($body) !== '') {
            $notification->body(mb_substr(trim($body), 0, 1000));
        }
        $notification->sendToDatabase($user);
    }
}

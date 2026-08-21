<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Jobs;

use App\Models\Backup;
use App\Models\Server;
use App\Models\User;
use App\Services\Backups\InitiateBackupService;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetup;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetupOperation;
use BlueWolf\MinecraftToolkit\Services\MinecraftModpackService;
use BlueWolf\MinecraftToolkit\Services\MinecraftServerFileService;
use BlueWolf\MinecraftToolkit\Services\MinecraftServerStateService;
use BlueWolf\MinecraftToolkit\Services\MinecraftSetupOperationService;
use BlueWolf\MinecraftToolkit\Services\MinecraftSetupService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunMinecraftSetupOperationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 5;

    public int $uniqueFor = 7200;

    /** @var int[] */
    public array $backoff = [30, 60, 120, 300];

    public function __construct(public readonly int $operationId) {}

    public function handle(
        MinecraftSetupOperationService $operations,
        MinecraftServerFileService $files,
        MinecraftServerStateService $state,
        InitiateBackupService $backups,
        MinecraftSetupService $setups,
        MinecraftModpackService $modpacks,
    ): void {
        $operation = MinecraftToolkitSetupOperation::find($this->operationId);
        if (! $operation instanceof MinecraftToolkitSetupOperation || ! $operation->isActive()) {
            return;
        }

        $lock = Cache::lock('minecrafttoolkit.setup-operation.'.$operation->server_uuid, 7200);
        if (! $lock->get()) {
            $this->release(30);

            return;
        }

        try {
            $server = Server::find($operation->server_id);
            if (! $server instanceof Server) {
                $this->markFailed($operation, trans('minecrafttoolkit::strings.setup.server_missing'), $operations);

                return;
            }
            $operationUser = $operation->user_id !== null ? User::find($operation->user_id) : null;
            if ($operationUser instanceof User) {
                App::setLocale($operationUser->language ?: 'en');
            }

            $operation->forceFill(['last_heartbeat_at' => now()])->save();
            if ($operation->status === 'queued') {
                $state->assertOffline($server);
                if ($operations->hasExistingServerData($server, $files)) {
                    $backup = $backups
                        ->setIsLocked(true)
                        ->setIsScheduled(false)
                        ->setIgnoredFiles([])
                        ->handle($server, 'Minecraft Toolkit safety backup '.now()->toDateTimeString());
                    $operation->forceFill([
                        'backup_id' => $backup->id,
                        'status' => 'backup_pending',
                        'stage' => 'waiting_for_safety_backup',
                        'last_error' => null,
                        'last_heartbeat_at' => now(),
                    ])->save();

                    return;
                }

                $operation->forceFill([
                    'status' => 'installing',
                    'stage' => 'empty_server_verified',
                    'last_error' => null,
                    'last_heartbeat_at' => now(),
                ])->save();
            }

            if ($operation->status === 'backup_pending') {
                $backup = $operation->backup_id !== null ? Backup::find($operation->backup_id) : null;
                if (! $backup instanceof Backup) {
                    $this->markFailed($operation, trans('minecrafttoolkit::strings.setup.safety_backup_missing'), $operations);

                    return;
                }
                $backupState = $operations->safetyBackupState(
                    $backup,
                    now(),
                    (int) config('minecrafttoolkit.setup_backup_timeout_minutes', 120)
                );
                if ($backupState === 'waiting') {
                    return;
                }
                if ($backupState === 'timed_out') {
                    $this->markFailed($operation, trans('minecrafttoolkit::strings.setup.safety_backup_timeout'), $operations);

                    return;
                }
                if ($backupState !== 'verified') {
                    $this->markFailed($operation, trans('minecrafttoolkit::strings.setup.safety_backup_failed'), $operations);

                    return;
                }

                $operation->forceFill([
                    'status' => 'installing',
                    'stage' => 'safety_backup_verified',
                    'last_error' => null,
                    'last_heartbeat_at' => now(),
                ])->save();
            }

            $state->assertOffline($server);
            $user = $operationUser;
            $guard = Auth::guard();
            $previousUser = $guard->user();
            if ($user instanceof User) {
                $guard->setUser($user);
                App::setLocale($user->language ?: 'en');
            }

            try {
                $setup = $operation->setup;
                if (! $setup instanceof MinecraftToolkitSetup) {
                    $setup = null;
                    $recoveredSetup = MinecraftToolkitSetup::query()
                        ->where('server_uuid', $operation->server_uuid)
                        ->where('setup_status', 'completed')
                        ->where('setup_started_at', '>=', $operation->started_at)
                        ->first();
                    if ($recoveredSetup instanceof MinecraftToolkitSetup) {
                        $setup = $recoveredSetup;
                        $operation->forceFill([
                            'setup_id' => $setup->id,
                            'stage' => 'core_setup_completed',
                            'last_heartbeat_at' => now(),
                        ])->save();
                    }
                }
                if ($operation->stage !== 'core_setup_completed' || $setup === null) {
                    $payload = $operation->payload_json;
                    $iconPath = $operations->stagedPath($operation, $operation->icon_file);
                    $setup = $setups->setup($server, $payload, $iconPath, $operation->user_id);
                    $operation->forceFill([
                        'setup_id' => $setup->id,
                        'stage' => 'core_setup_completed',
                        'last_error' => null,
                        'last_heartbeat_at' => now(),
                    ])->save();
                }

                $modpackPath = $operations->stagedPath($operation, $operation->modpack_file);
                if ($modpackPath !== null && $operation->stage !== 'modpack_completed') {
                    $modpacks->installUpload($server, $setup, $modpackPath, $operation->modpack_mode);
                    $operation->forceFill([
                        'stage' => 'modpack_completed',
                        'last_heartbeat_at' => now(),
                    ])->save();
                }
            } finally {
                if ($previousUser instanceof User) {
                    $guard->setUser($previousUser);
                } else {
                    $guard->logout();
                }
            }

            $operation->forceFill([
                'status' => 'completed',
                'stage' => 'completed',
                'last_error' => null,
                'completed_at' => now(),
                'last_heartbeat_at' => now(),
            ])->save();
            $operations->cleanupStagedFiles($operation->uuid);
            $this->notify($operation, 'success', trans('minecrafttoolkit::strings.setup.complete'), trans('minecrafttoolkit::strings.setup.operation_complete_body'));
        } catch (Throwable $exception) {
            $operation->forceFill([
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                'last_heartbeat_at' => now(),
            ])->save();

            throw $exception;
        } finally {
            $lock->release();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $operation = MinecraftToolkitSetupOperation::find($this->operationId);
        if (! $operation instanceof MinecraftToolkitSetupOperation || ! $operation->isActive()) {
            return;
        }

        $message = $exception?->getMessage() ?: trans('minecrafttoolkit::strings.setup.operation_failed_body');
        $this->markFailed($operation, $message, app(MinecraftSetupOperationService::class));
        if ($exception !== null) {
            report($exception);
        }
    }

    public function uniqueId(): string
    {
        return 'minecrafttoolkit:setup-operation:'.$this->operationId;
    }

    private function markFailed(MinecraftToolkitSetupOperation $operation, string $message, MinecraftSetupOperationService $operations): void
    {
        $operation->forceFill([
            'status' => 'failed',
            'stage' => 'failed',
            'last_error' => mb_substr($message, 0, 2000),
            'completed_at' => now(),
            'last_heartbeat_at' => now(),
        ])->save();
        $operations->cleanupStagedFiles($operation->uuid);
        $this->notify($operation, 'danger', trans('minecrafttoolkit::strings.setup.failed'), $message);
    }

    private function notify(MinecraftToolkitSetupOperation $operation, string $status, string $title, string $body): void
    {
        $user = $operation->user_id !== null ? User::find($operation->user_id) : null;
        if (! $user instanceof User) {
            return;
        }

        App::setLocale($user->language ?: 'en');
        Notification::make()
            ->title($title)
            ->body(mb_substr($body, 0, 1000))
            ->status($status)
            ->sendToDatabase($user);
    }
}

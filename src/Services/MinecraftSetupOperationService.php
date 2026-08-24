<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Services;

use App\Models\Backup;
use App\Models\Server;
use BlueWolf\MinecraftToolkit\Exceptions\MinecraftToolkitException;
use BlueWolf\MinecraftToolkit\Jobs\RunMinecraftSetupOperationJob;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetupOperation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MinecraftSetupOperationService
{
    /** @param array<string, mixed> $data */
    public function queue(
        Server $server,
        array $data,
        mixed $icon,
        mixed $modpack,
        string $modpackMode,
        ?int $userId,
    ): MinecraftToolkitSetupOperation {
        $existing = MinecraftToolkitSetupOperation::query()
            ->where('server_id', $server->id)
            ->whereIn('status', MinecraftToolkitSetupOperation::ACTIVE_STATUSES)
            ->first();
        if ($existing instanceof MinecraftToolkitSetupOperation) {
            throw new MinecraftToolkitException(trans('minecrafttoolkit::strings.setup.operation_already_running'));
        }

        $uuid = (string) Str::uuid();
        $iconFile = null;
        $modpackFile = null;

        try {
            if ($icon !== null) {
                $iconFile = 'server-icon.png';
                $this->stageUpload($uuid, $iconFile, $icon, (int) config('minecrafttoolkit.max_icon_bytes', 2097152));
            }
            if ($modpack !== null) {
                $modpackFile = $this->safeModpackName($modpack);
                $this->stageUpload($uuid, $modpackFile, $modpack, (int) config('minecrafttoolkit.max_package_bytes', 104857600));
            }

            $operation = DB::transaction(function () use ($server, $data, $userId, $uuid, $iconFile, $modpackFile, $modpackMode): MinecraftToolkitSetupOperation {
                Server::query()->whereKey($server->id)->lockForUpdate()->firstOrFail();
                if (MinecraftToolkitSetupOperation::query()
                    ->where('server_id', $server->id)
                    ->whereIn('status', MinecraftToolkitSetupOperation::ACTIVE_STATUSES)
                    ->exists()) {
                    throw new MinecraftToolkitException(trans('minecrafttoolkit::strings.setup.operation_already_running'));
                }

                return MinecraftToolkitSetupOperation::query()->create([
                    'uuid' => $uuid,
                    'server_id' => $server->id,
                    'server_uuid' => $server->uuid,
                    'user_id' => $userId,
                    'status' => 'queued',
                    'stage' => 'queued',
                    'payload_json' => $data,
                    'icon_file' => $iconFile,
                    'modpack_file' => $modpackFile,
                    'modpack_mode' => in_array($modpackMode, ['combine', 'replace'], true) ? $modpackMode : 'combine',
                    'started_at' => now(),
                    'last_heartbeat_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            $this->cleanupStagedFiles($uuid);

            throw $exception;
        }

        RunMinecraftSetupOperationJob::dispatch($operation->id);

        return $operation;
    }

    public function hasExistingServerData(Server $server, MinecraftServerFileService $files): bool
    {
        return collect($files->listDirectory($server, '/'))
            ->contains(function (array $entry): bool {
                $name = trim((string) ($entry['name'] ?? ''));

                return $name !== '' && $name !== '.minecraft-toolkit';
            });
    }

    public function safetyBackupState(Backup $backup, Carbon $now, int $timeoutMinutes): string
    {
        if ($backup->completed_at === null) {
            return $backup->created_at?->lt($now->copy()->subMinutes($timeoutMinutes)) === true
                ? 'timed_out'
                : 'waiting';
        }

        return $backup->is_successful
            && is_string($backup->checksum)
            && $backup->checksum !== ''
            && (int) $backup->bytes > 0
            ? 'verified'
            : 'failed';
    }

    public function localBackupDirectory(MinecraftToolkitSetupOperation $operation): string
    {
        $timestamp = ($operation->started_at ?? $operation->created_at ?? now())->format('Y-m-d-H-i-s');
        $operationSuffix = substr(str_replace('-', '', strtolower($operation->uuid)), 0, 8);
        if (! preg_match('/^[0-9a-f]{8}$/', $operationSuffix)) {
            throw new MinecraftToolkitException('Invalid setup operation identifier.');
        }

        return '/.minecraft-toolkit/backups/'.$timestamp.'-'.$operationSuffix;
    }

    public function stagedPath(MinecraftToolkitSetupOperation $operation, ?string $file): ?string
    {
        if ($file === null || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._+() -]{0,199}$/', $file)) {
            return null;
        }

        $path = $this->stagingDirectory($operation->uuid).DIRECTORY_SEPARATOR.$file;

        return is_file($path) ? $path : null;
    }

    public function cleanupStagedFiles(string $uuid): void
    {
        $directory = $this->stagingDirectory($uuid);
        if (is_dir($directory)) {
            File::deleteDirectory($directory);
        }
    }

    private function stageUpload(string $uuid, string $fileName, mixed $upload, int $maxBytes): void
    {
        $contents = null;
        if (is_object($upload) && method_exists($upload, 'getContent')) {
            $contents = $upload->getContent();
        }
        if (! is_string($contents) && is_object($upload) && method_exists($upload, 'getRealPath')) {
            $path = $upload->getRealPath();
            $contents = is_string($path) ? file_get_contents($path) : false;
        }
        if (! is_string($contents) || $contents === '' || strlen($contents) > $maxBytes) {
            throw new MinecraftToolkitException(trans('minecrafttoolkit::strings.setup.upload_stage_failed'));
        }

        $directory = $this->stagingDirectory($uuid);
        File::ensureDirectoryExists($directory, 0700, true);
        if (File::put($directory.DIRECTORY_SEPARATOR.$fileName, $contents, true) !== strlen($contents)) {
            throw new MinecraftToolkitException(trans('minecrafttoolkit::strings.setup.upload_stage_failed'));
        }
    }

    private function safeModpackName(mixed $upload): string
    {
        $name = is_object($upload) && method_exists($upload, 'getClientOriginalName')
            ? (string) $upload->getClientOriginalName()
            : 'setup-modpack.zip';
        $name = basename(str_replace('\\', '/', $name));
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._+() -]{0,199}$/', $name)
            || str_contains($name, '..')
            || ! in_array($extension, ['zip', 'mrpack'], true)) {
            throw new MinecraftToolkitException(trans('minecrafttoolkit::strings.setup.upload_stage_failed'));
        }

        return $name;
    }

    private function stagingDirectory(string $uuid): string
    {
        if (! Str::isUuid($uuid)) {
            throw new MinecraftToolkitException('Invalid setup operation identifier.');
        }

        return storage_path('app/minecraft-toolkit/setup-operations/'.$uuid);
    }
}

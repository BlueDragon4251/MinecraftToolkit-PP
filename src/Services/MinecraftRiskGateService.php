<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Services;

use App\Models\Server;
use App\Models\User;
use BlueWolf\MinecraftToolkit\Exceptions\MinecraftToolkitException;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitLog;
use Illuminate\Support\Facades\Log;

class MinecraftRiskGateService
{
    /** @var array<string, string> */
    private const ADMIN_ONLY_CONFIG = [
        'startup_edits' => 'risk_gate_startup_edits_admin_only',
        'version_risk' => 'risk_gate_version_risk_admin_only',
        'package_removal' => 'risk_gate_package_removal_admin_only',
        'curseforge_usage' => 'risk_gate_curseforge_usage_admin_only',
        'crossplay_setup' => 'risk_gate_crossplay_setup_admin_only',
        'raw_properties' => 'risk_gate_raw_properties_admin_only',
    ];

    /** @var array<string, string> */
    private const LABELS = [
        'startup_edits' => 'Startup-Änderungen',
        'version_risk' => 'riskante Versionswechsel',
        'package_removal' => 'Paketentfernung',
        'curseforge_usage' => 'CurseForge',
        'crossplay_setup' => 'Crossplay',
        'raw_properties' => 'server.properties-Rohtext',
    ];

    public function assertAllowed(string $action, Server $server, ?User $user = null): void
    {
        $user ??= user();
        $configKey = self::ADMIN_ONLY_CONFIG[$action] ?? null;
        if ($configKey === null) {
            return;
        }

        if (! (bool) config("minecrafttoolkit.$configKey", false)) {
            return;
        }

        if ($user?->isRootAdmin()) {
            $this->audit($server, $action, 'allowed', 'Risk-Gate durch Root-Admin passiert.');

            return;
        }

        $label = self::LABELS[$action] ?? $action;
        $this->audit($server, $action, 'denied', "Risk-Gate blockierte $label.");

        throw new MinecraftToolkitException(
            "$label ist in dieser Installation nur für Root-Administratoren erlaubt."
        );
    }

    public function isAllowed(string $action, Server $server, ?User $user = null): bool
    {
        $user ??= user();
        $configKey = self::ADMIN_ONLY_CONFIG[$action] ?? null;
        if ($configKey === null || ! (bool) config("minecrafttoolkit.$configKey", false)) {
            return true;
        }

        return (bool) $user?->isRootAdmin();
    }

    public function audit(Server $server, string $action, string $result, string $message, array $context = []): void
    {
        if (! (bool) config('minecrafttoolkit.security_audit_log_enabled', true)) {
            return;
        }

        try {
            MinecraftToolkitLog::query()->create([
                'server_uuid' => $server->uuid,
                'user_id' => user()?->id,
                'action' => 'security_'.$action,
                'level' => $result === 'denied' ? 'warning' : 'info',
                'message' => $message,
                'context_json' => ['result' => $result] + $context,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Minecraft Toolkit could not persist a security audit log.', [
                'server_uuid' => $server->uuid,
                'action' => $action,
                'exception' => $exception,
            ]);
        }
    }
}

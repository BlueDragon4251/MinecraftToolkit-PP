<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Http\Controllers;

use App\Models\Server;
use App\Models\User;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitModpack;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitPackage;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetup;
use BlueWolf\MinecraftToolkit\Services\MinecraftPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MinecraftToolkitStateController extends Controller
{
    public function __construct(private readonly MinecraftPermissionService $permissions) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $servers = Server::query()->get()->filter(fn (Server $server): bool => $this->permissions->canView($user, $server));
        $setups = MinecraftToolkitSetup::query()->whereIn('server_id', $servers->pluck('id'))->get()->map(fn ($setup) => $this->payload($setup, $user));

        return response()->json(['data' => $setups->values()]);
    }

    public function show(Request $request, Server $server): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->permissions->canView($user, $server), 403);
        $setup = MinecraftToolkitSetup::query()->where('server_uuid', $server->uuid)->firstOrFail();

        return response()->json(['data' => $this->payload($setup, $user)]);
    }

    /** @return array<string, mixed> */
    private function payload(MinecraftToolkitSetup $setup, User $user): array
    {
        $packages = MinecraftToolkitPackage::query()->where('server_uuid', $setup->server_uuid)->where('managed', true)->get()->map(fn ($package): array => array_filter([
            'id' => $package->id, 'name' => $package->project_name, 'source' => $package->source, 'project_id' => $package->source_project_id,
            'version' => $package->version_number, 'type' => $package->package_type, 'path' => $package->file_path, 'pinned' => (bool) $package->update_pinned,
            'admin_notes' => $user->isRootAdmin() ? $package->admin_notes : null,
        ], fn ($value) => $value !== null))->values();

        return [
            'server_id' => $setup->server_id, 'server_uuid' => $setup->server_uuid, 'edition' => $setup->edition, 'software' => $setup->software,
            'minecraft_version' => $setup->minecraft_version, 'loader' => $setup->loader, 'loader_version' => $setup->loader_version,
            'status' => $setup->setup_status, 'crossplay' => (bool) $setup->crossplay_enabled, 'packages' => $packages,
            'active_modpack' => MinecraftToolkitModpack::query()->where('server_uuid', $setup->server_uuid)->where('active', true)->first()?->only(['name', 'source', 'version_number']),
        ];
    }
}

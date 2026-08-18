<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Console\Commands;

use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetup;
use BlueWolf\MinecraftToolkit\Services\CurseForgeService;
use BlueWolf\MinecraftToolkit\Services\MinecraftSoftwareService;
use BlueWolf\MinecraftToolkit\Services\ModrinthService;
use Illuminate\Console\Command;

class WarmMinecraftToolkitCacheCommand extends Command
{
    protected $signature = 'minecraft-toolkit:warm-cache';

    protected $description = 'Warm software, loader and package metadata caches.';

    public function handle(MinecraftSoftwareService $software, ModrinthService $modrinth, CurseForgeService $curseForge): int
    {
        foreach (array_keys($software->supportedSoftware()) as $name) {
            $versions = $software->versionOptions($name);
            if (in_array($name, ['fabric', 'forge', 'neoforge'], true)) {
                foreach (array_slice(array_keys($versions), 0, 10) as $version) {
                    $software->loaderVersionOptions($name, $version);
                }
            }
        }
        MinecraftToolkitSetup::query()->where('setup_status', 'completed')->each(function ($setup) use ($modrinth, $curseForge): void {
            try {
                $modrinth->popularPackages($setup, 0, 20);
            } catch (\Throwable) {
            }
            try {
                $curseForge->popularPackages($setup, 0, 20);
            } catch (\Throwable) {
            }
        });
        $this->info('Minecraft Toolkit caches warmed.');

        return self::SUCCESS;
    }
}

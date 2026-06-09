<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Providers;

use BlueWolf\MinecraftToolkit\Services\MinecraftPropertiesService;
use BlueWolf\MinecraftToolkit\Services\MinecraftServerFileService;
use BlueWolf\MinecraftToolkit\Services\MinecraftServerStateService;
use BlueWolf\MinecraftToolkit\Services\MinecraftSetupService;
use BlueWolf\MinecraftToolkit\Services\MinecraftSoftwareService;
use Illuminate\Support\ServiceProvider;

class MinecraftToolkitPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MinecraftPropertiesService::class);
        $this->app->singleton(MinecraftSoftwareService::class);
        $this->app->singleton(MinecraftServerFileService::class);
        $this->app->singleton(MinecraftServerStateService::class);
        $this->app->singleton(MinecraftSetupService::class);
    }

    public function boot(): void {}
}

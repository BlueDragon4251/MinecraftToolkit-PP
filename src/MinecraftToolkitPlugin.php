<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Panel;

class MinecraftToolkitPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'minecrafttoolkit';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();

        $panel->discoverPages(
            plugin_path($this->getId(), "src/Filament/$id/Pages"),
            "BlueWolf\\MinecraftToolkit\\Filament\\$id\\Pages"
        );
    }

    public function boot(Panel $panel): void {}

    public function getSettingsForm(): array
    {
        return [
            Toggle::make('enabled')
                ->label('Minecraft Toolkit aktivieren')
                ->default((bool) config('minecrafttoolkit.enabled', true)),
            Toggle::make('admins_only')
                ->label('Setup nur Administratoren erlauben')
                ->default((bool) config('minecrafttoolkit.admins_only', false)),
            Toggle::make('backup_before_overwrite')
                ->label('Vor dem Überschreiben Sicherung erstellen')
                ->default((bool) config('minecrafttoolkit.backup_before_overwrite', true)),
            TextInput::make('http_timeout')
                ->label('API-Timeout in Sekunden')
                ->numeric()
                ->minValue(5)
                ->required()
                ->default((int) config('minecrafttoolkit.http_timeout', 20)),
            TextInput::make('download_timeout')
                ->label('Download-Timeout in Sekunden')
                ->numeric()
                ->minValue(30)
                ->required()
                ->default((int) config('minecrafttoolkit.download_timeout', 300)),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'MINECRAFT_TOOLKIT_ENABLED' => (bool) ($data['enabled'] ?? false),
            'MINECRAFT_TOOLKIT_ADMINS_ONLY' => (bool) ($data['admins_only'] ?? false),
            'MINECRAFT_TOOLKIT_BACKUP_BEFORE_OVERWRITE' => (bool) ($data['backup_before_overwrite'] ?? true),
            'MINECRAFT_TOOLKIT_HTTP_TIMEOUT' => max(5, (int) ($data['http_timeout'] ?? 20)),
            'MINECRAFT_TOOLKIT_DOWNLOAD_TIMEOUT' => max(30, (int) ($data['download_timeout'] ?? 300)),
        ]);
    }
}

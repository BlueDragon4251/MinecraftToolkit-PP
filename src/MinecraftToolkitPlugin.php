<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use BlueWolf\MinecraftToolkit\Services\CurseForgeApiKeyProvider;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Placeholder;
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
        $pagesPath = plugin_path($this->getId(), "src/Filament/$id/Pages");

        if (is_dir($pagesPath)) {
            $panel->discoverPages(
                $pagesPath,
                "BlueWolf\\MinecraftToolkit\\Filament\\$id\\Pages"
            );
        }

    }

    public function boot(Panel $panel): void {}

    /** @return array<string, mixed> */
    public function getSettingsFormData(): array
    {
        return [
            'enabled' => (bool) config('minecrafttoolkit.enabled', true),
            'admins_only' => (bool) config('minecrafttoolkit.admins_only', false),
            'backup_before_overwrite' => (bool) config('minecrafttoolkit.backup_before_overwrite', true),
            'modrinth_enabled' => (bool) config('minecrafttoolkit.modrinth_enabled', true),
            'curseforge_enabled' => (bool) config('minecrafttoolkit.curseforge_enabled', true),
            'curseforge_proxy_url' => (string) config('minecrafttoolkit.curseforge_proxy_url', ''),
            'updater_enabled' => (bool) config('minecrafttoolkit.updater_enabled', true),
            'version_change_enabled' => (bool) config('minecrafttoolkit.version_change_enabled', true),
            'version_change_users_enabled' => (bool) config('minecrafttoolkit.version_change_users_enabled', true),
            'crossplay_enabled' => (bool) config('minecrafttoolkit.crossplay_enabled', true),
            'bedrock_port_required' => (bool) config('minecrafttoolkit.bedrock_port_required', true),
            'security_audit_log_enabled' => (bool) config('minecrafttoolkit.security_audit_log_enabled', true),
            'risk_gate_startup_edits_admin_only' => (bool) config('minecrafttoolkit.risk_gate_startup_edits_admin_only', false),
            'risk_gate_version_risk_admin_only' => (bool) config('minecrafttoolkit.risk_gate_version_risk_admin_only', false),
            'risk_gate_package_removal_admin_only' => (bool) config('minecrafttoolkit.risk_gate_package_removal_admin_only', false),
            'risk_gate_curseforge_usage_admin_only' => (bool) config('minecrafttoolkit.risk_gate_curseforge_usage_admin_only', false),
            'risk_gate_crossplay_setup_admin_only' => (bool) config('minecrafttoolkit.risk_gate_crossplay_setup_admin_only', false),
            'risk_gate_raw_properties_admin_only' => (bool) config('minecrafttoolkit.risk_gate_raw_properties_admin_only', false),
            'http_timeout' => (int) config('minecrafttoolkit.http_timeout', 20),
            'download_timeout' => (int) config('minecrafttoolkit.download_timeout', 300),
            'java_class_version_max' => (int) config('minecrafttoolkit.java_class_version_max', 69),
            'compatibility_cache_minutes' => (int) config('minecrafttoolkit.compatibility_cache_minutes', 30),
            'scheduled_update_checks' => (bool) config('minecrafttoolkit.scheduled_update_checks', true),
            'post_update_health_check' => (bool) config('minecrafttoolkit.post_update_health_check', false),
            'post_update_health_wait_seconds' => (int) config('minecrafttoolkit.post_update_health_wait_seconds', 60),
            'blueit_announcements_enabled' => (bool) config('minecrafttoolkit.blueit_announcements_enabled', true),
            'blueit_announcements_url' => (string) config('minecrafttoolkit.blueit_announcements_url', ''),
        ];
    }

    public function getSettingsForm(): array
    {
        return [
            Toggle::make('enabled')
                ->label(trans('minecrafttoolkit::strings.settings.enabled'))
                ->default((bool) config('minecrafttoolkit.enabled', true)),
            Toggle::make('admins_only')
                ->label(trans('minecrafttoolkit::strings.settings.admins_only'))
                ->default((bool) config('minecrafttoolkit.admins_only', false)),
            Toggle::make('backup_before_overwrite')
                ->label(trans('minecrafttoolkit::strings.settings.backup_before_overwrite'))
                ->default((bool) config('minecrafttoolkit.backup_before_overwrite', true)),
            Toggle::make('modrinth_enabled')
                ->label(trans('minecrafttoolkit::strings.settings.modrinth_enabled'))
                ->default((bool) config('minecrafttoolkit.modrinth_enabled', true)),
            Toggle::make('curseforge_enabled')
                ->label(trans('minecrafttoolkit::strings.settings.curseforge_enabled'))
                ->default((bool) config('minecrafttoolkit.curseforge_enabled', true))
                ->helperText(trans('minecrafttoolkit::strings.settings.curseforge_enabled_help')),
            Placeholder::make('curseforge_key_status')
                ->label(trans('minecrafttoolkit::strings.settings.curseforge_key_status'))
                ->content(function (): string {
                    if (app(CurseForgeApiKeyProvider::class)->hasKey()) {
                        return trans('minecrafttoolkit::strings.settings.direct_key_available');
                    }

                    $proxyHost = parse_url((string) config('minecrafttoolkit.curseforge_proxy_url', ''), PHP_URL_HOST);

                    return is_string($proxyHost) && $proxyHost !== ''
                        ? trans('minecrafttoolkit::strings.settings.proxy_active', ['host' => $proxyHost])
                        : trans('minecrafttoolkit::strings.settings.no_proxy_no_key');
                }),
            TextInput::make('curseforge_proxy_url')
                ->label(trans('minecrafttoolkit::strings.settings.proxy_url'))
                ->url()
                ->default('')
                ->placeholder(trans('minecrafttoolkit::strings.settings.standard_proxy_placeholder'))
                ->helperText(trans('minecrafttoolkit::strings.settings.proxy_url_help')),
            TextInput::make('curseforge_proxy_secret')
                ->label(trans('minecrafttoolkit::strings.settings.proxy_secret'))
                ->password()
                ->revealable(false)
                ->default('')
                ->placeholder(trans('minecrafttoolkit::strings.settings.standard_secret_placeholder'))
                ->helperText(trans('minecrafttoolkit::strings.settings.proxy_secret_help')),
            TextInput::make('curseforge_api_key')
                ->label(trans('minecrafttoolkit::strings.settings.api_key_override'))
                ->password()
                ->revealable(false)
                ->default('')
                ->placeholder(trans('minecrafttoolkit::strings.settings.optional_override_placeholder'))
                ->helperText(trans('minecrafttoolkit::strings.settings.api_key_override_help')),
            Toggle::make('updater_enabled')
                ->label(trans('minecrafttoolkit::strings.settings.updater_enabled'))
                ->default((bool) config('minecrafttoolkit.updater_enabled', true)),
            Toggle::make('version_change_enabled')
                ->label(trans('minecrafttoolkit::strings.settings.version_change_enabled'))
                ->default((bool) config('minecrafttoolkit.version_change_enabled', true)),
            Toggle::make('version_change_users_enabled')
                ->label(trans('minecrafttoolkit::strings.settings.version_change_users_enabled'))
                ->default((bool) config('minecrafttoolkit.version_change_users_enabled', true)),
            Toggle::make('crossplay_enabled')
                ->label(trans('minecrafttoolkit::strings.settings.crossplay_enabled'))
                ->default((bool) config('minecrafttoolkit.crossplay_enabled', true)),
            Toggle::make('bedrock_port_required')
                ->label(trans('minecrafttoolkit::strings.settings.bedrock_port_required'))
                ->default((bool) config('minecrafttoolkit.bedrock_port_required', true)),
            Toggle::make('security_audit_log_enabled')
                ->label(trans('minecrafttoolkit::strings.settings.security_audit_log_enabled'))
                ->default((bool) config('minecrafttoolkit.security_audit_log_enabled', true))
                ->helperText(trans('minecrafttoolkit::strings.settings.security_audit_log_help')),
            Toggle::make('risk_gate_startup_edits_admin_only')
                ->label(trans('minecrafttoolkit::strings.settings.risk_gate_startup_edits_admin_only'))
                ->default((bool) config('minecrafttoolkit.risk_gate_startup_edits_admin_only', false)),
            Toggle::make('risk_gate_version_risk_admin_only')
                ->label(trans('minecrafttoolkit::strings.settings.risk_gate_version_risk_admin_only'))
                ->default((bool) config('minecrafttoolkit.risk_gate_version_risk_admin_only', false)),
            Toggle::make('risk_gate_package_removal_admin_only')
                ->label(trans('minecrafttoolkit::strings.settings.risk_gate_package_removal_admin_only'))
                ->default((bool) config('minecrafttoolkit.risk_gate_package_removal_admin_only', false)),
            Toggle::make('risk_gate_curseforge_usage_admin_only')
                ->label(trans('minecrafttoolkit::strings.settings.risk_gate_curseforge_usage_admin_only'))
                ->default((bool) config('minecrafttoolkit.risk_gate_curseforge_usage_admin_only', false)),
            Toggle::make('risk_gate_crossplay_setup_admin_only')
                ->label(trans('minecrafttoolkit::strings.settings.risk_gate_crossplay_setup_admin_only'))
                ->default((bool) config('minecrafttoolkit.risk_gate_crossplay_setup_admin_only', false)),
            Toggle::make('risk_gate_raw_properties_admin_only')
                ->label(trans('minecrafttoolkit::strings.settings.risk_gate_raw_properties_admin_only'))
                ->default((bool) config('minecrafttoolkit.risk_gate_raw_properties_admin_only', false)),
            TextInput::make('http_timeout')
                ->label(trans('minecrafttoolkit::strings.settings.http_timeout'))
                ->numeric()
                ->minValue(5)
                ->required()
                ->default((int) config('minecrafttoolkit.http_timeout', 20)),
            TextInput::make('download_timeout')
                ->label(trans('minecrafttoolkit::strings.settings.download_timeout'))
                ->numeric()
                ->minValue(30)
                ->required()
                ->default((int) config('minecrafttoolkit.download_timeout', 300)),
            TextInput::make('java_class_version_max')
                ->label(trans('minecrafttoolkit::strings.settings.java_class_version_max'))
                ->helperText(trans('minecrafttoolkit::strings.settings.java_class_version_max_help'))
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->required()
                ->default((int) config('minecrafttoolkit.java_class_version_max', 69)),
            TextInput::make('compatibility_cache_minutes')
                ->label(trans('minecrafttoolkit::strings.settings.compatibility_cache_minutes'))
                ->numeric()
                ->minValue(0)
                ->required()
                ->default((int) config('minecrafttoolkit.compatibility_cache_minutes', 30)),
            Toggle::make('scheduled_update_checks')
                ->label(trans('minecrafttoolkit::strings.settings.scheduled_update_checks'))
                ->default((bool) config('minecrafttoolkit.scheduled_update_checks', true)),
            Toggle::make('post_update_health_check')
                ->label(trans('minecrafttoolkit::strings.settings.post_update_health_check'))
                ->helperText(trans('minecrafttoolkit::strings.settings.post_update_health_check_help'))
                ->default((bool) config('minecrafttoolkit.post_update_health_check', false)),
            TextInput::make('post_update_health_wait_seconds')
                ->label(trans('minecrafttoolkit::strings.settings.post_update_health_wait'))
                ->numeric()->minValue(15)->maxValue(300)->required()
                ->default((int) config('minecrafttoolkit.post_update_health_wait_seconds', 60)),
            Toggle::make('blueit_announcements_enabled')
                ->label(trans('minecrafttoolkit::strings.settings.blueit_announcements_enabled'))
                ->default((bool) config('minecrafttoolkit.blueit_announcements_enabled', true)),
            TextInput::make('blueit_announcements_url')
                ->label(trans('minecrafttoolkit::strings.settings.blueit_announcements_url'))
                ->url()
                ->default((string) config('minecrafttoolkit.blueit_announcements_url', '')),
            TextInput::make('blueit_announcements_secret')
                ->label(trans('minecrafttoolkit::strings.settings.blueit_announcements_secret'))
                ->password()
                ->revealable(false)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(trans('minecrafttoolkit::strings.settings.blueit_announcements_secret_help')),
        ];
    }

    public function saveSettings(array $data): void
    {
        $values = [
            'MINECRAFT_TOOLKIT_ENABLED' => $this->boolSetting($data, 'enabled', true),
            'MINECRAFT_TOOLKIT_ADMINS_ONLY' => $this->boolSetting($data, 'admins_only', false),
            'MINECRAFT_TOOLKIT_BACKUP_BEFORE_OVERWRITE' => $this->boolSetting($data, 'backup_before_overwrite', true),
            'MINECRAFT_TOOLKIT_MODRINTH_ENABLED' => $this->boolSetting($data, 'modrinth_enabled', true),
            'MINECRAFT_TOOLKIT_CURSEFORGE_ENABLED' => $this->boolSetting($data, 'curseforge_enabled', true),
            'MINECRAFT_TOOLKIT_UPDATER_ENABLED' => $this->boolSetting($data, 'updater_enabled', true),
            'MINECRAFT_TOOLKIT_VERSION_CHANGE_ENABLED' => $this->boolSetting($data, 'version_change_enabled', true),
            'MINECRAFT_TOOLKIT_VERSION_CHANGE_USERS_ENABLED' => $this->boolSetting($data, 'version_change_users_enabled', true),
            'MINECRAFT_TOOLKIT_CROSSPLAY_ENABLED' => $this->boolSetting($data, 'crossplay_enabled', true),
            'MINECRAFT_TOOLKIT_BEDROCK_PORT_REQUIRED' => $this->boolSetting($data, 'bedrock_port_required', true),
            'MINECRAFT_TOOLKIT_SECURITY_AUDIT_LOG_ENABLED' => $this->boolSetting($data, 'security_audit_log_enabled', true),
            'MINECRAFT_TOOLKIT_RISK_GATE_STARTUP_EDITS_ADMIN_ONLY' => $this->boolSetting($data, 'risk_gate_startup_edits_admin_only', false),
            'MINECRAFT_TOOLKIT_RISK_GATE_VERSION_RISK_ADMIN_ONLY' => $this->boolSetting($data, 'risk_gate_version_risk_admin_only', false),
            'MINECRAFT_TOOLKIT_RISK_GATE_PACKAGE_REMOVAL_ADMIN_ONLY' => $this->boolSetting($data, 'risk_gate_package_removal_admin_only', false),
            'MINECRAFT_TOOLKIT_RISK_GATE_CURSEFORGE_USAGE_ADMIN_ONLY' => $this->boolSetting($data, 'risk_gate_curseforge_usage_admin_only', false),
            'MINECRAFT_TOOLKIT_RISK_GATE_CROSSPLAY_SETUP_ADMIN_ONLY' => $this->boolSetting($data, 'risk_gate_crossplay_setup_admin_only', false),
            'MINECRAFT_TOOLKIT_RISK_GATE_RAW_PROPERTIES_ADMIN_ONLY' => $this->boolSetting($data, 'risk_gate_raw_properties_admin_only', false),
            'MINECRAFT_TOOLKIT_HTTP_TIMEOUT' => max(5, (int) ($data['http_timeout'] ?? 20)),
            'MINECRAFT_TOOLKIT_DOWNLOAD_TIMEOUT' => max(30, (int) ($data['download_timeout'] ?? 300)),
            'MINECRAFT_TOOLKIT_JAVA_CLASS_VERSION_MAX' => max(0, min(100, (int) ($data['java_class_version_max'] ?? 69))),
            'MINECRAFT_TOOLKIT_COMPATIBILITY_CACHE_MINUTES' => max(0, (int) ($data['compatibility_cache_minutes'] ?? 30)),
            'MINECRAFT_TOOLKIT_SCHEDULED_UPDATE_CHECKS' => $this->boolSetting($data, 'scheduled_update_checks', true),
            'MINECRAFT_TOOLKIT_POST_UPDATE_HEALTH_CHECK' => $this->boolSetting($data, 'post_update_health_check', false),
            'MINECRAFT_TOOLKIT_POST_UPDATE_HEALTH_WAIT' => max(15, min(300, (int) ($data['post_update_health_wait_seconds'] ?? 60))),
            'MINECRAFT_TOOLKIT_BLUEIT_ANNOUNCEMENTS_ENABLED' => $this->boolSetting($data, 'blueit_announcements_enabled', true),
        ];

        $proxyUrl = rtrim(trim((string) ($data['curseforge_proxy_url'] ?? '')), '/');
        if ($proxyUrl !== '') {
            $values['MINECRAFT_TOOLKIT_CURSEFORGE_PROXY_URL'] = $proxyUrl;
        }

        $proxySecret = trim((string) ($data['curseforge_proxy_secret'] ?? ''));
        if ($proxySecret !== '') {
            $values['MINECRAFT_TOOLKIT_CURSEFORGE_PROXY_SECRET'] = $proxySecret;
        }

        $apiKey = trim((string) ($data['curseforge_api_key'] ?? ''));
        if ($apiKey !== '') {
            $values['MINECRAFT_TOOLKIT_CURSEFORGE_API_KEY'] = $apiKey;
        }

        $announcementsUrl = rtrim(trim((string) ($data['blueit_announcements_url'] ?? '')), '/');
        if ($announcementsUrl !== '') {
            $values['MINECRAFT_TOOLKIT_BLUEIT_ANNOUNCEMENTS_URL'] = $announcementsUrl;
        }

        $announcementsSecret = trim((string) ($data['blueit_announcements_secret'] ?? ''));
        if ($announcementsSecret !== '') {
            $values['MINECRAFT_TOOLKIT_BLUEIT_ANNOUNCEMENTS_SECRET'] = $announcementsSecret;
        }

        $this->writeToEnvironment($values);
    }

    /** @param array<string, mixed> $data */
    private function boolSetting(array $data, string $key, bool $default): bool
    {
        $value = $data[$key] ?? config("minecrafttoolkit.$key", $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}

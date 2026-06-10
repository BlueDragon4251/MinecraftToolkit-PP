<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Filament\Server\Pages;

use App\Models\Server;
use BackedEnum;
use BlueWolf\MinecraftToolkit\Exceptions\MinecraftToolkitException;
use BlueWolf\MinecraftToolkit\Filament\Server\Pages\MinecraftOverviewPage;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetup;
use BlueWolf\MinecraftToolkit\Services\MinecraftPermissionService;
use BlueWolf\MinecraftToolkit\Services\MinecraftSetupService;
use BlueWolf\MinecraftToolkit\Services\MinecraftSoftwareService;
use BlueWolf\MinecraftToolkit\Services\ModrinthService;
use BlueWolf\MinecraftToolkit\Services\CurseForgeService;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

/**
 * @property \Filament\Schemas\Schema $form
 */
class MinecraftSetupPage extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-brand-minecraft';

    protected static UnitEnum|string|null $navigationGroup = 'Minecraft Toolkit';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'minecraft-setup';

    protected string $view = 'minecrafttoolkit::filament.server.pages.minecraft-setup';

    public ?array $data = [];

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->form->fill($this->defaults());
    }

    public static function canAccess(): bool
    {
        if (!(bool) config('minecrafttoolkit.enabled', true)
            || collect([
                'minecraft_toolkit_setups',
                'minecraft_toolkit_packages',
                'minecraft_toolkit_update_checks',
                'minecraft_toolkit_logs',
            ])->contains(fn (string $table): bool => !Schema::hasTable($table))) {
            return false;
        }

        $server = Filament::getTenant();
        $user = user();
        if (!$server instanceof Server || $user === null
            || !app(MinecraftPermissionService::class)->canModify($user, $server)) {
            return false;
        }

        return !MinecraftToolkitSetup::query()
            ->where('server_uuid', $server->uuid)
            ->where('setup_status', 'completed')
            ->exists();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public static function getNavigationLabel(): string
    {
        return trans('minecrafttoolkit::strings.navigation.setup');
    }

    public function getTitle(): string
    {
        return trans('minecrafttoolkit::strings.navigation.setup');
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Wizard::make([
                Step::make('software')
                    ->label(trans('minecrafttoolkit::strings.setup.software'))
                    ->icon('tabler-box')
                    ->schema([
                        Select::make('software')
                            ->label(trans('minecrafttoolkit::strings.setup.software'))
                            ->options(app(MinecraftSoftwareService::class)->supportedSoftware())
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('minecraft_version', null);
                                $set('loader_version', null);
                                $set('crossplay_enabled', false);
                            })
                            ->required()
                            ->helperText(trans('minecrafttoolkit::strings.setup.software_help')),
                    ]),
                Step::make('version')
                    ->label(trans('minecrafttoolkit::strings.setup.version'))
                    ->icon('tabler-tag')
                    ->schema([
                        Select::make('minecraft_version')
                            ->label(trans('minecrafttoolkit::strings.setup.version'))
                            ->options(fn (Get $get): array => app(MinecraftSoftwareService::class)
                                ->versionOptions((string) ($get('software') ?: 'vanilla')))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('loader_version', null))
                            ->required(),
                        Select::make('loader_version')
                            ->label(trans('minecrafttoolkit::strings.setup.loader_version'))
                            ->options(fn (Get $get): array => app(MinecraftSoftwareService::class)->loaderVersionOptions(
                                (string) $get('software'),
                                (string) $get('minecraft_version')
                            ))
                            ->visible(fn (Get $get): bool => in_array(
                                $get('software'),
                                ['fabric', 'forge', 'neoforge'],
                                true
                            ))
                            ->searchable()
                            ->required(fn (Get $get): bool => in_array(
                                $get('software'),
                                ['fabric', 'forge', 'neoforge'],
                                true
                            )),
                    ]),
                Step::make('settings')
                    ->label(trans('minecrafttoolkit::strings.setup.server_settings'))
                    ->icon('tabler-adjustments')
                    ->schema([
                        Section::make(trans('minecrafttoolkit::strings.setup.world_players'))
                            ->schema([
                                TextInput::make('motd')
                                    ->label('MOTD')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText(trans('minecrafttoolkit::strings.setup.motd_help')),
                                Section::make(trans('minecrafttoolkit::strings.setup.motd_formatter'))
                                    ->description(trans('minecrafttoolkit::strings.setup.motd_formatter_desc'))
                                    ->schema([
                                        TextInput::make('motd_formatter_text')
                                            ->label(trans('minecrafttoolkit::strings.setup.text'))
                                            ->maxLength(120)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->updateFormattedMotd($get, $set)),
                                        Select::make('motd_formatter_color')
                                            ->label(trans('minecrafttoolkit::strings.setup.color'))
                                            ->options($this->motdColorOptions())
                                            ->default('a')
                                            ->live()
                                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->updateFormattedMotd($get, $set)),
                                        Group::make()->columns(3)->schema([
                                            Toggle::make('motd_formatter_bold')
                                                ->label(trans('minecrafttoolkit::strings.setup.bold'))
                                                ->live()
                                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->updateFormattedMotd($get, $set)),
                                            Toggle::make('motd_formatter_italic')
                                                ->label(trans('minecrafttoolkit::strings.setup.italic'))
                                                ->live()
                                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->updateFormattedMotd($get, $set)),
                                            Toggle::make('motd_formatter_underlined')
                                                ->label(trans('minecrafttoolkit::strings.setup.underlined'))
                                                ->live()
                                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->updateFormattedMotd($get, $set)),
                                        ]),
                                    ])->columns(2),
                                TextInput::make('level_name')->label(trans('minecrafttoolkit::strings.setup.level_name'))->required()->maxLength(64),
                                TextInput::make('max_players')->label(trans('minecrafttoolkit::strings.setup.max_players'))->numeric()->minValue(1)->maxValue(100000)->required(),
                                Select::make('gamemode')->label(trans('minecrafttoolkit::strings.setup.gamemode'))->options([
                                    'survival' => 'Survival',
                                    'creative' => 'Creative',
                                    'adventure' => 'Adventure',
                                    'spectator' => 'Spectator',
                                ])->required(),
                                Select::make('difficulty')->label(trans('minecrafttoolkit::strings.setup.difficulty'))->options([
                                    'peaceful' => 'Peaceful',
                                    'easy' => 'Easy',
                                    'normal' => 'Normal',
                                    'hard' => 'Hard',
                                ])->required(),
                            ])->columns(2),
                        Section::make(trans('minecrafttoolkit::strings.setup.gameplay'))
                            ->schema([
                                Group::make()->columns(3)->schema([
                                    Toggle::make('online_mode')->label('Online Mode'),
                                    Toggle::make('whitelist')->label('Whitelist'),
                                    Toggle::make('pvp')->label('PVP'),
                                    Toggle::make('allow_nether')->label(trans('minecrafttoolkit::strings.setup.allow_nether')),
                                    Toggle::make('enable_command_block')->label(trans('minecrafttoolkit::strings.setup.command_blocks')),
                                    Toggle::make('allow_flight')->label(trans('minecrafttoolkit::strings.setup.allow_flight')),
                                    Toggle::make('enable_query')->label(trans('minecrafttoolkit::strings.setup.enable_query')),
                                    Toggle::make('enable_rcon')->label(trans('minecrafttoolkit::strings.setup.enable_rcon')),
                                ]),
                                Group::make()->columns(3)->schema([
                                    TextInput::make('spawn_protection')->label(trans('minecrafttoolkit::strings.setup.spawn_protection'))->numeric()->minValue(0)->maxValue(1000)->required(),
                                    TextInput::make('view_distance')->label(trans('minecrafttoolkit::strings.setup.view_distance'))->numeric()->minValue(2)->maxValue(32)->required(),
                                    TextInput::make('simulation_distance')->label(trans('minecrafttoolkit::strings.setup.simulation_distance'))->numeric()->minValue(2)->maxValue(32)->required(),
                                ]),
                            ]),
                    ]),
                Step::make('icon')
                    ->label(trans('minecrafttoolkit::strings.setup.server_icon'))
                    ->icon('tabler-photo')
                    ->visible(fn (Get $get): bool => $get('software') !== 'bedrock')
                    ->schema([
                        FileUpload::make('server_icon')
                            ->label(trans('minecrafttoolkit::strings.setup.server_icon'))
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(2048)
                            ->storeFiles(false)
                            ->helperText(trans('minecrafttoolkit::strings.setup.server_icon_help')),
                    ]),
                Step::make('crossplay')
                    ->label(trans('minecrafttoolkit::strings.setup.crossplay'))
                    ->icon('tabler-device-gamepad-2')
                    ->visible(fn (Get $get): bool => (bool) config('minecrafttoolkit.crossplay_enabled', true)
                        && in_array($get('software'), ['paper', 'purpur'], true))
                    ->schema([
                        Toggle::make('crossplay_enabled')
                            ->label(trans('minecrafttoolkit::strings.setup.enable_crossplay'))
                            ->helperText(trans('minecrafttoolkit::strings.setup.crossplay_help'))
                            ->live(),
                        Select::make('bedrock_allocation_id')
                            ->label(trans('minecrafttoolkit::strings.setup.bedrock_allocation'))
                            ->options(fn (): array => $this->bedrockAllocationOptions())
                            ->required(fn (Get $get): bool => (bool) $get('crossplay_enabled'))
                            ->visible(fn (Get $get): bool => (bool) $get('crossplay_enabled'))
                            ->helperText(trans('minecrafttoolkit::strings.setup.bedrock_allocation_help')),
                        Section::make('Hinweis')
                            ->description(trans('minecrafttoolkit::strings.setup.crossplay_desc'))
                            ->schema([]),
                    ]),
                Step::make('packages')
                    ->label(trans('minecrafttoolkit::strings.setup.packages'))
                    ->icon('tabler-packages')
                    ->visible(fn (Get $get): bool => in_array($get('software'), ['paper', 'purpur', 'folia', 'fabric', 'forge', 'neoforge'], true))
                    ->schema([
                        Select::make('setup_package_source')
                            ->label(trans('minecrafttoolkit::strings.setup.source'))
                            ->options(fn (): array => $this->setupSourceOptions())
                            ->default('modrinth')
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('setup_package_ids', []))
                            ->helperText(trans('minecrafttoolkit::strings.setup.packages_help')),
                        Select::make('setup_package_ids')
                            ->label(fn (Get $get): string => in_array($get('software'), ['paper', 'purpur', 'folia'], true) ? trans('minecrafttoolkit::strings.setup.select_plugins') : trans('minecrafttoolkit::strings.setup.select_mods'))
                            ->multiple()
                            ->searchable()
                            ->options(fn (Get $get): array => $this->popularPackageOptions($get))
                            ->getSearchResultsUsing(fn (string $search, Get $get): array => $this->searchPackageOptions($search, $get))
                            ->helperText(trans('minecrafttoolkit::strings.setup.packages_multi_help')),
                        Placeholder::make('package_warning')
                            ->label(trans('minecrafttoolkit::strings.setup.hint'))
                            ->content(trans('minecrafttoolkit::strings.setup.packages_multi_help')),
                    ]),
                Step::make('review')
                    ->label(trans('minecrafttoolkit::strings.setup.review'))
                    ->icon('tabler-list-check')
                    ->schema([
                        Section::make('Bereit für das Setup')
                            ->description(function (Get $get): string {
                                $software = (string) $get('software');
                                $artifact = match ($software) {
                                    'bedrock' => 'bedrock-server.zip',
                                    'forge' => 'forge-installer.jar',
                                    'neoforge' => 'neoforge-installer.jar',
                                    default => 'server.jar',
                                };

                                return sprintf(
                                    '%s %s wird über %s eingerichtet. Port %s kommt aus der primären Allocation; vorhandene Zieldateien werden gesichert.',
                                    app(MinecraftSoftwareService::class)->supportedSoftware()[$software] ?? 'Minecraft',
                                    trim(($get('minecraft_version') ?: '') . ' ' . ($get('loader_version') ?: '')),
                                    $artifact,
                                    Filament::getTenant()?->allocation?->port ?? 'fehlt'
                                );
                            })
                            ->schema([
                                Hidden::make('review_confirmed')->default(true),
                            ]),
                    ]),
            ])->columnSpanFull(),
        ];
    }

    public function setup(): void
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        try {
            $state = $this->form->getState();
            $icon = $state['server_icon'] ?? null;
            $usesBootstrapInstaller = in_array($state['software'] ?? null, ['forge', 'neoforge', 'bedrock'], true);
            unset(
                $state['server_icon'],
                $state['review_confirmed'],
                $state['motd_formatter_text'],
                $state['motd_formatter_color'],
                $state['motd_formatter_bold'],
                $state['motd_formatter_italic'],
                $state['motd_formatter_underlined']
            );

            app(MinecraftSetupService::class)->setup($server, $state, $icon);

            Notification::make()
                ->title(trans('minecrafttoolkit::strings.setup.complete'))
                ->body($usesBootstrapInstaller
                    ? 'Der Loader-Installer liegt bereit. Beim ersten Start werden die Laufzeitdateien erzeugt; das kann einige Minuten dauern.'
                    : 'Serversoftware, eula.txt und server.properties wurden über Wings eingerichtet.')
                ->success()
                ->send();

            $this->redirect(MinecraftOverviewPage::getUrl(panel: 'server', tenant: $server));
        } catch (MinecraftToolkitException $exception) {
            Notification::make()
                ->title(trans('minecrafttoolkit::strings.setup.failed'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private function defaults(): array
    {
        return [
            'software' => 'paper',
            'loader_version' => null,
            'motd' => 'A Minecraft Server',
            'level_name' => 'world',
            'max_players' => 20,
            'gamemode' => 'survival',
            'difficulty' => 'easy',
            'online_mode' => true,
            'whitelist' => false,
            'pvp' => true,
            'allow_nether' => true,
            'spawn_protection' => 16,
            'view_distance' => 10,
            'simulation_distance' => 10,
            'enable_command_block' => false,
            'allow_flight' => false,
            'enable_query' => false,
            'enable_rcon' => false,
            'crossplay_enabled' => false,
            'bedrock_allocation_id' => null,
            'motd_formatter_text' => '',
            'motd_formatter_color' => 'a',
            'motd_formatter_bold' => false,
            'motd_formatter_italic' => false,
            'motd_formatter_underlined' => false,
            'setup_package_source' => 'modrinth',
            'setup_package_ids' => [],
        ];
    }

    /** @return array<string, string> */
    public function motdColorOptions(): array
    {
        return [
            '0' => trans('minecrafttoolkit::strings.colors.black'),
            '1' => trans('minecrafttoolkit::strings.colors.dark_blue'),
            '2' => trans('minecrafttoolkit::strings.colors.dark_green'),
            '3' => trans('minecrafttoolkit::strings.colors.dark_aqua'),
            '4' => trans('minecrafttoolkit::strings.colors.dark_red'),
            '5' => trans('minecrafttoolkit::strings.colors.dark_purple'),
            '6' => trans('minecrafttoolkit::strings.colors.gold'),
            '7' => trans('minecrafttoolkit::strings.colors.gray'),
            '8' => trans('minecrafttoolkit::strings.colors.dark_gray'),
            '9' => trans('minecrafttoolkit::strings.colors.blue'),
            'a' => trans('minecrafttoolkit::strings.colors.green'),
            'b' => trans('minecrafttoolkit::strings.colors.aqua'),
            'c' => trans('minecrafttoolkit::strings.colors.red'),
            'd' => trans('minecrafttoolkit::strings.colors.light_purple'),
            'e' => trans('minecrafttoolkit::strings.colors.yellow'),
            'f' => trans('minecrafttoolkit::strings.colors.white'),
        ];
    }

    private function updateFormattedMotd(Get $get, Set $set): null
    {
        $text = trim((string) $get('motd_formatter_text'));
        if ($text === '') {
            return null;
        }

        $motd = '§' . ((string) ($get('motd_formatter_color') ?: 'f'));
        if ((bool) $get('motd_formatter_bold')) {
            $motd .= '§l';
        }
        if ((bool) $get('motd_formatter_italic')) {
            $motd .= '§o';
        }
        if ((bool) $get('motd_formatter_underlined')) {
            $motd .= '§n';
        }

        $set('motd', $motd . $text . '§r');

        return null;
    }

    /** @return array<string, string> */
    private function setupSourceOptions(): array
    {
        $options = [];
        if ((bool) config('minecrafttoolkit.modrinth_enabled', true)) {
            $options['modrinth'] = 'Modrinth';
        }
        if ((bool) config('minecrafttoolkit.curseforge_enabled', true) && app(CurseForgeService::class)->isConfigured()) {
            $options['curseforge'] = 'CurseForge';
        }

        return $options;
    }

    /** @return array<string, string> */
    private function popularPackageOptions(Get $get): array
    {
        return $this->packageOptions('', $get, true);
    }

    /** @return array<string, string> */
    private function searchPackageOptions(string $search, Get $get): array
    {
        return $this->packageOptions($search, $get, false);
    }

    /** @return array<string, string> */
    private function packageOptions(string $search, Get $get, bool $popular): array
    {
        $software = (string) $get('software');
        $minecraftVersion = (string) $get('minecraft_version');
        if ($software === '' || $minecraftVersion === ''
            || !in_array($software, ['paper', 'purpur', 'folia', 'fabric', 'forge', 'neoforge'], true)) {
            return [];
        }

        $source = (string) ($get('setup_package_source') ?: 'modrinth');
        if ($source === 'curseforge' && !app(CurseForgeService::class)->isConfigured()) {
            return [];
        }

        $setup = new MinecraftToolkitSetup([
            'server_uuid' => 'setup-preview',
            'software' => $software,
            'minecraft_version' => $minecraftVersion,
            'loader' => in_array($software, ['fabric', 'forge', 'neoforge'], true) ? $software : null,
            'loader_version' => $get('loader_version'),
            'setup_status' => 'preview',
        ]);

        try {
            $results = match ($source) {
                'modrinth' => $popular || trim($search) === ''
                    ? app(ModrinthService::class)->popularPackages($setup, 0, 25)
                    : app(ModrinthService::class)->searchPackages($search, $setup, 25),
                'curseforge' => $popular || trim($search) === ''
                    ? app(CurseForgeService::class)->popularPackages($setup, 0, 25)
                    : app(CurseForgeService::class)->searchPackages($search, $setup, 25),
                default => [],
            };
        } catch (\Throwable) {
            return [];
        }

        return collect($results)
            ->mapWithKeys(fn (array $result): array => [
                $source . ':' . $result['project_id'] => $result['title']
                    . (isset($result['downloads']) ? ' · ' . number_format((int) $result['downloads']) . ' Downloads' : ''),
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function bedrockAllocationOptions(): array
    {
        $server = Filament::getTenant();
        if (!$server instanceof Server) {
            return [];
        }

        $allocations = $server->allocations()
            ->where('id', '!=', $server->allocation_id)
            ->where('node_id', $server->node_id)
            ->get()
            ->mapWithKeys(fn ($allocation): array => [
                $allocation->id => $allocation->address,
            ])
            ->all();

        if (!(bool) config('minecrafttoolkit.bedrock_port_required', true) && $server->allocation) {
            return [$server->allocation->id => $server->allocation->address . ' (geteilt)'] + $allocations;
        }

        return $allocations;
    }
}

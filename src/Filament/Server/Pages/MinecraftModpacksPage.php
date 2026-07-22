<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Filament\Server\Pages;

use App\Models\Server;
use BackedEnum;
use BlueWolf\MinecraftToolkit\Exceptions\MinecraftToolkitException;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitModpack;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetup;
use BlueWolf\MinecraftToolkit\Services\CurseForgeService;
use BlueWolf\MinecraftToolkit\Services\MinecraftModpackService;
use BlueWolf\MinecraftToolkit\Services\MinecraftPermissionService;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

/**
 * @property \Filament\Schemas\Schema $form
 */
class MinecraftModpacksPage extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-stack-2';

    protected static UnitEnum|string|null $navigationGroup = 'Minecraft Toolkit';

    protected static ?int $navigationSort = 45;

    protected static ?string $slug = 'minecraft-modpacks';

    protected string $view = 'minecrafttoolkit::filament.server.pages.minecraft-modpacks';

    public ?array $data = [];

    /** @var array<int, array<string, mixed>> */
    public array $results = [];

    /** @var array<int, array<string, mixed>> */
    public array $modpacks = [];

    public int $page = 0;

    public static function canAccess(): bool
    {
        if (!(bool) config('minecrafttoolkit.enabled', true)
            || !Schema::hasTable('minecraft_toolkit_modpacks')) {
            return false;
        }

        $server = Filament::getTenant();
        $user = user();
        if (!$server instanceof Server || $user === null
            || !app(MinecraftPermissionService::class)->canModify($user, $server)) {
            return false;
        }

        return MinecraftToolkitSetup::query()
            ->where('server_uuid', $server->uuid)
            ->where('setup_status', 'completed')
            ->exists();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill([
            'source' => 'modrinth',
            'query' => '',
            'mode' => 'combine',
            'upload_mode' => 'combine',
            'upload_file' => null,
        ]);
        $this->refreshModpacks();
    }

    public static function getNavigationLabel(): string
    {
        return trans('minecrafttoolkit::strings.navigation.modpacks');
    }

    public function getTitle(): string
    {
        return trans('minecrafttoolkit::strings.navigation.modpacks');
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('source')
                ->label(trans('minecrafttoolkit::strings.modpacks.source'))
                ->options(fn (): array => $this->sourceOptions())
                ->required(),
            TextInput::make('query')
                ->label(trans('minecrafttoolkit::strings.modpacks.search'))
                ->placeholder('All the Mods, Better MC, Fabulously Optimized'),
            Radio::make('mode')
                ->label(trans('minecrafttoolkit::strings.modpacks.install_mode'))
                ->options([
                    'combine' => trans('minecrafttoolkit::strings.modpacks.combine'),
                    'replace' => trans('minecrafttoolkit::strings.modpacks.replace'),
                ])
                ->default('combine')
                ->required(),
            FileUpload::make('upload_file')
                ->label(trans('minecrafttoolkit::strings.modpacks.upload'))
                ->acceptedFileTypes(['application/zip', 'application/octet-stream'])
                ->storeFiles(false)
                ->helperText(trans('minecrafttoolkit::strings.modpacks.upload_help')),
            Radio::make('upload_mode')
                ->label(trans('minecrafttoolkit::strings.modpacks.upload_mode'))
                ->options([
                    'combine' => trans('minecrafttoolkit::strings.modpacks.combine'),
                    'replace' => trans('minecrafttoolkit::strings.modpacks.replace'),
                ])
                ->default('combine')
                ->required(),
        ];
    }

    public function search(): void
    {
        try {
            $state = $this->form->getState();
            $source = (string) ($state['source'] ?? 'modrinth');
            $this->results = app(MinecraftModpackService::class)->search(
                $source,
                (string) ($state['query'] ?? ''),
                $this->page * 20,
                20
            );
        } catch (MinecraftToolkitException $exception) {
            $this->notifyError($exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->notifyError(trans('minecrafttoolkit::strings.modpacks.search_failed'));
        }
    }

    public function installPublic(string $source, string $projectId): void
    {
        try {
            $state = $this->form->getState();
            app(MinecraftModpackService::class)->installPublic(
                $this->server(),
                $this->setup(),
                $source,
                $projectId,
                (string) ($state['mode'] ?? 'combine')
            );
            $this->refreshModpacks();
            Notification::make()->title(trans('minecrafttoolkit::strings.modpacks.installed'))->success()->send();
        } catch (MinecraftToolkitException $exception) {
            $this->notifyError($exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->notifyError(trans('minecrafttoolkit::strings.modpacks.install_failed'));
        }
    }

    public function installUpload(): void
    {
        try {
            $state = $this->form->getState();
            $file = $state['upload_file'] ?? null;
            if (is_array($file)) {
                $file = reset($file) ?: null;
            }
            app(MinecraftModpackService::class)->installUpload(
                $this->server(),
                $this->setup(),
                $file,
                (string) ($state['upload_mode'] ?? 'combine')
            );
            $this->form->fill(['upload_file' => null] + $state);
            $this->refreshModpacks();
            Notification::make()->title(trans('minecrafttoolkit::strings.modpacks.installed'))->success()->send();
        } catch (MinecraftToolkitException $exception) {
            $this->notifyError($exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->notifyError(trans('minecrafttoolkit::strings.modpacks.install_failed'));
        }
    }

    public function activate(int $modpackId): void
    {
        try {
            app(MinecraftModpackService::class)->activate($this->server(), $modpackId);
            $this->refreshModpacks();
            Notification::make()->title(trans('minecrafttoolkit::strings.modpacks.activated'))->success()->send();
        } catch (MinecraftToolkitException $exception) {
            $this->notifyError($exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->notifyError(trans('minecrafttoolkit::strings.modpacks.activate_failed'));
        }
    }

    public function nextPage(): void
    {
        $this->page++;
        $this->search();
    }

    public function previousPage(): void
    {
        $this->page = max(0, $this->page - 1);
        $this->search();
    }

    private function refreshModpacks(): void
    {
        $this->modpacks = MinecraftToolkitModpack::query()
            ->where('server_uuid', $this->server()->uuid)
            ->latest('id')
            ->get()
            ->map(fn (MinecraftToolkitModpack $modpack): array => [
                'id' => $modpack->id,
                'name' => $modpack->name,
                'source' => $modpack->source,
                'version' => $modpack->version_number,
                'file_name' => $modpack->file_name,
                'active' => (bool) $modpack->active,
                'installed_at' => $modpack->installed_at?->diffForHumans(),
            ])
            ->all();
    }

    /** @return array<string, string> */
    private function sourceOptions(): array
    {
        $options = [];
        if ((bool) config('minecrafttoolkit.modrinth_enabled', true)) {
            $options['modrinth'] = 'Modrinth';
        }
        if (app(CurseForgeService::class)->isConfigured()) {
            $options['curseforge'] = 'CurseForge';
        }

        return $options;
    }

    private function setup(): MinecraftToolkitSetup
    {
        return MinecraftToolkitSetup::query()
            ->where('server_uuid', $this->server()->uuid)
            ->where('setup_status', 'completed')
            ->latest('id')
            ->firstOrFail();
    }

    private function server(): Server
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $server;
    }

    private function notifyError(string $message): void
    {
        Notification::make()
            ->title(trans('minecrafttoolkit::strings.modpacks.error'))
            ->body($this->localizedErrorBody($message))
            ->danger()
            ->persistent()
            ->send();
    }

    private function localizedErrorBody(string $message): string
    {
        return str_starts_with(strtolower((string) app()->getLocale()), 'de')
            ? $message
            : trans('minecrafttoolkit::strings.common.action_failed_body');
    }
}

<x-filament-panels::page>
    <x-filament::section :heading="trans('minecrafttoolkit::strings.modpacks.public_modpacks')">
        <form wire:submit="search" class="space-y-4">
            {{ $this->form }}
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="tabler-search" wire:loading.attr="disabled">
                    {{ trans('minecrafttoolkit::strings.modpacks.search') }}
                </x-filament::button>
                <x-filament::button type="button" color="gray" icon="tabler-upload" wire:click="installUpload" wire:loading.attr="disabled">
                    {{ trans('minecrafttoolkit::strings.modpacks.install_upload') }}
                </x-filament::button>
            </div>
        </form>

        <div class="mt-6 space-y-3">
            @forelse ($results as $result)
                <div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 dark:border-white/10 md:flex-row md:items-center md:justify-between">
                    <div class="flex gap-3">
                        @if (!empty($result['icon_url']))
                            <img src="{{ $result['icon_url'] }}" alt="" style="width: 56px; height: 56px; min-width: 56px; max-width: 56px; object-fit: cover; border-radius: 0.5rem;">
                        @endif
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold">{{ $result['title'] }}</h3>
                                <span class="text-xs uppercase text-gray-500">{{ $result['source'] ?? ($data['source'] ?? '') }}</span>
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ $result['author'] ?? '' }}
                                @if (($result['downloads'] ?? 0) > 0)
                                    · {{ number_format($result['downloads']) }} {{ trans('minecrafttoolkit::strings.installer.downloads') }}
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $result['description'] }}</p>
                            @if (!empty($result['project_url']))
                                <a href="{{ $result['project_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-sm text-primary-600 hover:underline">
                                    {{ trans('minecrafttoolkit::strings.installer.project_page') }}
                                </a>
                            @endif
                        </div>
                    </div>
                    <x-filament::button size="sm" wire:click="installPublic('{{ $result['source'] ?? ($data['source'] ?? 'modrinth') }}', '{{ $result['project_id'] }}')" wire:loading.attr="disabled">
                        {{ trans('minecrafttoolkit::strings.modpacks.install') }}
                    </x-filament::button>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ trans('minecrafttoolkit::strings.modpacks.no_results') }}</p>
            @endforelse
        </div>

        <div class="mt-4 flex items-center gap-3">
            <x-filament::button color="gray" size="sm" wire:click="previousPage" :disabled="$page === 0" wire:loading.attr="disabled">
                {{ trans('minecrafttoolkit::strings.installer.previous') }}
            </x-filament::button>
            <span class="text-sm text-gray-500">{{ trans('minecrafttoolkit::strings.installer.page', ['page' => $page + 1]) }}</span>
            <x-filament::button color="gray" size="sm" wire:click="nextPage" wire:loading.attr="disabled">
                {{ trans('minecrafttoolkit::strings.installer.next') }}
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section :heading="trans('minecrafttoolkit::strings.modpacks.installed_modpacks')">
        <div class="space-y-3">
            @forelse ($modpacks as $modpack)
                <div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 dark:border-white/10 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold">{{ $modpack['name'] }}</span>
                            <span class="text-xs uppercase text-gray-500">{{ $modpack['source'] }}</span>
                            @if ($modpack['active'])
                                <span class="text-xs text-success-600">{{ trans('minecrafttoolkit::strings.modpacks.active') }}</span>
                            @endif
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            {{ $modpack['version'] ?: trans('minecrafttoolkit::strings.updater.unknown') }} · <code>{{ $modpack['file_name'] }}</code>
                            @if ($modpack['installed_at'])
                                · {{ $modpack['installed_at'] }}
                            @endif
                        </div>
                    </div>
                    @if (!$modpack['active'])
                        <x-filament::button size="sm" color="warning" wire:click="activate({{ $modpack['id'] }})" wire:loading.attr="disabled">
                            {{ trans('minecrafttoolkit::strings.modpacks.activate') }}
                        </x-filament::button>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ trans('minecrafttoolkit::strings.modpacks.no_installed') }}</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>

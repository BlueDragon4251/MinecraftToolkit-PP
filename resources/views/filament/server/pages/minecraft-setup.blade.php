<x-filament-panels::page>
    <form wire:submit="setup">
        {{ $this->form }}

        <div class="mt-5 overflow-hidden rounded-lg border border-gray-700 bg-gray-950 p-4 text-white shadow-inner">
            <div class="mb-2 text-xs uppercase tracking-wide text-gray-400">{{ trans('minecrafttoolkit::strings.setup.motd_preview') }}</div>
            <div class="flex items-center gap-3"><div class="h-12 w-12 bg-gray-800"></div><div><div class="font-mono text-base">Minecraft Server</div><div class="font-mono text-sm leading-tight">{!! $this->motdPreviewHtml() !!}</div></div></div>
        </div>

        <div class="mt-6">
            <x-filament::button type="submit" icon="tabler-player-play">
                {{ trans('minecrafttoolkit::strings.setup.review') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

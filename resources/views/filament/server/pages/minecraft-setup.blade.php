<x-filament-panels::page>
    <form wire:submit="setup">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="tabler-player-play">
                {{ trans('minecrafttoolkit::strings.setup.review') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

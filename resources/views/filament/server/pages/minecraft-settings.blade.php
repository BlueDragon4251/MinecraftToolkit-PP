<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        <div class="mt-4"><x-filament::button type="submit" icon="tabler-device-floppy">{{ trans('minecrafttoolkit::strings.settings_page.save_changes') }}</x-filament::button></div>
    </form>
    @if ($this->supportsCrossplay())
        <div class="flex flex-wrap gap-3">
            <x-filament::button wire:click="installCrossplay" icon="tabler-device-gamepad-2">{{ trans('minecrafttoolkit::strings.settings_page.install_crossplay') }}</x-filament::button>
            @if ($this->currentSetup()->crossplay_enabled)
                <x-filament::button wire:click="applyCrossplayConfig" color="gray" icon="tabler-file-settings">{{ trans('minecrafttoolkit::strings.settings_page.apply_crossplay') }}</x-filament::button>
            @endif
        </div>
    @endif
</x-filament-panels::page>

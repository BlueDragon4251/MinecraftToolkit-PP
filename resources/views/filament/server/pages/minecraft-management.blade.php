<x-filament-panels::page>
    <x-filament::section :heading="trans('minecrafttoolkit::strings.management.world_info')"><div class="grid gap-3 md:grid-cols-2"><div><strong>{{ trans('minecrafttoolkit::strings.management.world_name') }}:</strong> {{ $worldInfo['name'] ?? '-' }}</div><div><strong>{{ trans('minecrafttoolkit::strings.management.world_seed') }}:</strong> {{ ($worldInfo['seed'] ?? '') !== '' ? $worldInfo['seed'] : trans('minecrafttoolkit::strings.management.seed_generated') }}</div></div></x-filament::section>
    <form wire:submit="saveAccess">
        {{ $this->form }}
        <div class="mt-4"><x-filament::button type="submit" icon="tabler-device-floppy">{{ trans('minecrafttoolkit::strings.management.save_access') }}</x-filament::button></div>
    </form>
    @if($diagnostics !== [])
        <x-filament::section :heading="trans('minecrafttoolkit::strings.management.geyser_diagnostics')">
            <div class="grid gap-3 md:grid-cols-2">@foreach($diagnostics as $check)<div class="rounded-lg border p-3"><strong>{{ $check['ok'] ? '✓' : '✗' }} {{ $check['label'] }}</strong><div>{{ $check['detail'] }}</div></div>@endforeach</div>
        </x-filament::section>
    @endif
    @if($conflicts !== [])
        <x-filament::section :heading="trans('minecrafttoolkit::strings.management.conflicts')"><div class="space-y-2">@foreach($conflicts as $warning)<div class="rounded-lg border p-3 {{ $warning['severity'] === 'critical' ? 'text-danger-600' : 'text-warning-600' }}">{{ $warning['message'] }}</div>@endforeach</div></x-filament::section>
    @endif
</x-filament-panels::page>

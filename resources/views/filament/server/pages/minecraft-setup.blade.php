<x-filament-panels::page>
    @if ($hasActiveSetupOperation)
        <x-filament::section wire:poll.10s="refreshOperationState">
            <x-slot name="heading">{{ trans('minecrafttoolkit::strings.setup.operation_running') }}</x-slot>
            <p>{{ trans('minecrafttoolkit::strings.setup.operation_status', ['stage' => trans('minecrafttoolkit::strings.setup.stages.' . ($setupOperationStage ?? 'queued'))]) }}</p>
            @if ($setupSafetyBackupUuid)
                <p class="mt-2 text-sm text-gray-500">{{ trans('minecrafttoolkit::strings.setup.safety_backup_uuid', ['uuid' => $setupSafetyBackupUuid]) }}</p>
            @endif
            <p class="mt-2 text-sm text-gray-500">{{ trans('minecrafttoolkit::strings.setup.operation_running_help') }}</p>
        </x-filament::section>
    @else
        @if ($setupOperationStatus === 'failed')
            <x-filament::section>
                <x-slot name="heading">{{ trans('minecrafttoolkit::strings.setup.failed') }}</x-slot>
                <p>{{ $setupOperationError ?: trans('minecrafttoolkit::strings.setup.operation_failed_body') }}</p>
                @if ($setupSafetyBackupUuid)
                    <p class="mt-2 text-sm text-gray-500">{{ trans('minecrafttoolkit::strings.setup.safety_backup_uuid', ['uuid' => $setupSafetyBackupUuid]) }}</p>
                @endif
            </x-filament::section>
        @endif
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
    @endif
</x-filament-panels::page>

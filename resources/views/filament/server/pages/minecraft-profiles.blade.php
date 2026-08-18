<x-filament-panels::page>
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse($profiles as $profile)
            <x-filament::section :heading="$profile['name']">
                <p class="text-sm text-gray-500">{{ $profile['description'] }}</p><p class="mt-2 text-sm">{{ $profile['software'] }} · {{ $profile['packages'] }} {{ trans('minecrafttoolkit::strings.profiles.packages') }}</p>
                <div class="mt-4 flex flex-wrap gap-2"><x-filament::button wire:click="applyProfile({{ $profile['id'] }})">{{ trans('minecrafttoolkit::strings.profiles.apply') }}</x-filament::button><x-filament::button color="gray" wire:click="exportProfile({{ $profile['id'] }})">{{ trans('minecrafttoolkit::strings.profiles.export') }}</x-filament::button>@if($profile['owned'])<x-filament::button color="danger" wire:click="deleteProfile({{ $profile['id'] }})">{{ trans('minecrafttoolkit::strings.profiles.delete') }}</x-filament::button>@endif</div>
            </x-filament::section>
        @empty<p>{{ trans('minecrafttoolkit::strings.profiles.none') }}</p>@endforelse
    </div>
</x-filament-panels::page>

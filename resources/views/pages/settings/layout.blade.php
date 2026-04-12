<div>
    <div role="tablist" class="tabs tabs-border mb-6">
        <a href="{{ route('profile.edit') }}" wire:navigate role="tab" @class([
            'tab',
            'tab-active text-primary' => request()->routeIs('profile.edit'),
        ])>
            <x-mary-icon name="o-user" class="size-4 me-1" />
            {{ __('Profil') }}
        </a>
        <a href="{{ route('security.edit') }}" wire:navigate role="tab" @class([
            'tab',
            'tab-active text-primary' => request()->routeIs('security.edit') || request()->routeIs('password.confirm'),
        ])>
            <x-mary-icon name="o-lock-closed" class="size-4 me-1" />
            {{ __('Keamanan') }}
        </a>
    </div>

    <div class="max-w-2xl">
        <div>
            <h2 class="text-lg font-semibold">{{ $heading ?? '' }}</h2>
            @isset($subheading)
                <p class="text-sm text-base-content/60">{{ $subheading }}</p>
            @endisset
        </div>

        <div class="mt-5 w-full">
            {{ $slot }}
        </div>
    </div>
</div>

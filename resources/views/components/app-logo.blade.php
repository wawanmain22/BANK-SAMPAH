@props([
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary text-primary-content">
        <x-app-logo-icon class="size-5 fill-current" />
    </div>

    @unless($compact)
        <div class="grid text-sm leading-tight">
            <span class="font-semibold">{{ config('app.name', 'Bank Sampah') }}</span>
            <span class="text-xs text-base-content/60">{{ __('Operasional') }}</span>
        </div>
    @endunless
</div>

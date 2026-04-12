@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center gap-1">
    <h2 class="text-xl font-semibold">{{ $title }}</h2>
    <p class="text-sm text-base-content/60">{{ $description }}</p>
</div>

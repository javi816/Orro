@props(['title' => 'Sin datos', 'description' => null, 'icon' => 'sparkles'])

<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-10 text-center">
    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
        <x-ui.icon :name="$icon" class="h-5 w-5" />
    </span>
    <p class="mt-3 text-sm font-semibold text-slate-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-xs text-slate-500">{{ $description }}</p>
    @endif
    @isset($slot)
        <div class="mt-3">{{ $slot }}</div>
    @endisset
</div>

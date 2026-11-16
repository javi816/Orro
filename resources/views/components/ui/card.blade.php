@props(['title' => null, 'description' => null, 'padding' => 'p-5'])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/80 bg-white shadow-sm']) }}>
    @if ($title)
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold tracking-wide text-slate-900">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-1 text-xs text-slate-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</section>

@props(['periods', 'selected'])

<form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2">
    <label for="period-select" class="sr-only">Período</label>
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
            <x-ui.icon name="calendar" class="h-4 w-4" />
        </span>
        <select
            id="period-select"
            name="period"
            onchange="this.form.submit()"
            class="w-full appearance-none rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-8 text-sm font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
        >
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected($period->id === $selected->id)>
                    {{ $period->label() }} · {{ $period->status->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <noscript>
        <x-ui.button variant="secondary" type="submit">Ver</x-ui.button>
    </noscript>
</form>

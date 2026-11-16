@props(['label', 'value', 'hint' => null, 'tone' => 'slate', 'icon' => 'wallet'])

@php
    $tones = [
        'slate' => ['bg-slate-100 text-slate-600', 'text-slate-900'],
        'emerald' => ['bg-emerald-100 text-emerald-700', 'text-emerald-700'],
        'rose' => ['bg-rose-100 text-rose-700', 'text-rose-700'],
        'amber' => ['bg-amber-100 text-amber-700', 'text-amber-700'],
        'sky' => ['bg-sky-100 text-sky-700', 'text-sky-700'],
        'violet' => ['bg-violet-100 text-violet-700', 'text-violet-700'],
    ];
    [$iconClasses, $valueClasses] = $tones[$tone] ?? $tones['slate'];
@endphp

<div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
            <p class="mt-2 truncate text-2xl font-semibold {{ $valueClasses }}">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
            @endif
        </div>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $iconClasses }}">
            <x-ui.icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>

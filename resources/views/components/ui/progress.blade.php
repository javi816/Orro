@props(['value' => 0, 'max' => 1, 'tone' => 'emerald'])

@php
    $percentage = (float) $max > 0 ? min(100, max(0, ((float) $value / (float) $max) * 100)) : 0;
    $bar = match ($tone) {
        'rose' => 'bg-rose-500',
        'amber' => 'bg-amber-500',
        'sky' => 'bg-sky-500',
        default => 'bg-emerald-500',
    };
@endphp

<div {{ $attributes->merge(['class' => 'h-2 w-full overflow-hidden rounded-full bg-slate-100']) }}>
    <div class="h-full rounded-full transition-all {{ $bar }}" style="width: {{ number_format($percentage, 1, '.', '') }}%"></div>
</div>

@props(['label', 'name' => null, 'hint' => null])

<label class="block">
    <span class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</span>
    {{ $slot }}
    @if ($name && $errors->has($name))
        <span class="mt-1 block text-xs font-medium text-rose-600">{{ $errors->first($name) }}</span>
    @elseif ($hint)
        <span class="mt-1 block text-xs text-slate-500">{{ $hint }}</span>
    @endif
</label>

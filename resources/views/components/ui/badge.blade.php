@props(['status', 'label' => null])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $map = [
        'open' => ['Abierto', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'ready_to_close' => ['Listo para cerrar', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'closed' => ['Cerrado', 'bg-slate-100 text-slate-600 ring-slate-200'],
        'pending' => ['Pendiente', 'bg-amber-50 text-amber-700 ring-amber-200'],
        'received' => ['Recibido', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'paid' => ['Pagado', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'fixed' => ['Fijo', 'bg-violet-50 text-violet-700 ring-violet-200'],
        'variable' => ['Variable', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'opening_balance' => ['Saldo inicial', 'bg-slate-100 text-slate-600 ring-slate-200'],
        'deposit' => ['Aportación', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'withdrawal' => ['Retiro', 'bg-rose-50 text-rose-700 ring-rose-200'],
        'repayment' => ['Reposición', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'overdue' => ['Vencido', 'bg-rose-50 text-rose-700 ring-rose-200'],
        'active' => ['Activo', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'inactive' => ['Inactivo', 'bg-slate-100 text-slate-500 ring-slate-200'],
    ];

    [$defaultLabel, $classes] = $map[$value] ?? [ucfirst(str_replace('_', ' ', $value)), 'bg-slate-100 text-slate-600 ring-slate-200'];
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $classes }}">
    {{ $label ?? $defaultLabel }}
</span>

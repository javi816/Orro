@props(['summary'])

<div class="grid gap-4 sm:grid-cols-2">
    @foreach ([
        'income_received' => 'Total de ingresos recibidos',
        'expenses_paid' => 'Total de gastos realizados',
        'savings_deposited' => 'Total de ahorro aportado',
        'savings_balance' => 'Saldo de ahorro',
        'loans_originated' => 'Total de préstamos',
        'repayments_made' => 'Total de reposiciones',
        'pending_debt' => 'Deuda pendiente',
        'available_money' => 'Dinero disponible',
    ] as $key => $label)
        <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3">
            <span class="text-sm text-slate-500">{{ $label }}</span>
            <span class="text-sm font-semibold text-slate-800">@money($summary[$key] ?? '0.00')</span>
        </div>
    @endforeach
</div>

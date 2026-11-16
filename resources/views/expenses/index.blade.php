@php
    $operable = ! $period->isClosed();
@endphp

<x-layouts.app title="Gastos">
    <x-ui.page-header title="Gastos" subtitle="Gastos fijos y variables de {{ $period->label() }}">
        <x-slot:actions>
            <x-ui.period-select :periods="$periods" :selected="$period" />
            @if ($operable)
                <form method="POST" action="{{ route('expenses.generate') }}">
                    @csrf
                    <input type="hidden" name="period_id" value="{{ $period->id }}">
                    <x-ui.button variant="secondary" type="submit">
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        Generar fijos
                    </x-ui.button>
                </form>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($period->isClosed())
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            <x-ui.icon name="lock" class="h-4 w-4 text-slate-400" />
            Período cerrado: los gastos son de solo lectura.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Fijos pagados" :value="\App\Support\Money::format($summary['fixed_paid'])" hint="Compromisos cumplidos" tone="emerald" icon="check" />
        <x-ui.stat label="Fijos pendientes" :value="\App\Support\Money::format($summary['fixed_pending'])" :hint="$summary['fixed_pending_count'].' por pagar'" tone="amber" icon="warning" />
        <x-ui.stat label="Variables realizados" :value="\App\Support\Money::format($summary['variable_spent'])" :hint="'Presupuesto: '.\App\Support\Money::format($summary['variable_budget'])" tone="sky" icon="wallet" />
        <x-ui.stat label="Total pagado" :value="\App\Support\Money::format($summary['expenses_paid'])" hint="Fijos y variables" tone="rose" icon="expense" />
    </div>

    @if ($operable)
        <x-ui.card class="mt-5" title="Registrar gasto variable" description="Los gastos variables consumen el presupuesto variable del período.">
            <form method="POST" action="{{ route('expenses.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12">
                @csrf
                <input type="hidden" name="period_id" value="{{ $period->id }}">
                <div class="lg:col-span-4">
                    <x-ui.field label="Descripción" name="description">
                        <x-ui.input name="description" value="{{ old('description') }}" placeholder="Ej. Mercado, transporte…" required />
                    </x-ui.field>
                </div>
                <div class="lg:col-span-2">
                    <x-ui.field label="Importe" name="amount">
                        <x-ui.input name="amount" inputmode="decimal" value="{{ old('amount') }}" placeholder="0,00" required />
                    </x-ui.field>
                </div>
                <div class="lg:col-span-2">
                    <x-ui.field label="Fecha" name="expense_date">
                        <x-ui.input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" />
                    </x-ui.field>
                </div>
                <div class="lg:col-span-2">
                    <x-ui.field label="N.º de pago" name="payment_number" hint="Quincena o pago del mes.">
                        <x-ui.input type="number" name="payment_number" min="1" max="31" value="{{ old('payment_number', 1) }}" />
                    </x-ui.field>
                </div>
                <div class="lg:col-span-2">
                    <x-ui.field label="Estado" name="status">
                        <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            <option value="paid" @selected(old('status', 'paid') === 'paid')>Pagado</option>
                            <option value="pending" @selected(old('status') === 'pending')>Pendiente</option>
                        </select>
                    </x-ui.field>
                </div>
                <div class="flex items-end lg:col-span-12">
                    <x-ui.button type="submit">
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        Registrar gasto variable
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <x-ui.card title="Gastos fijos" description="Generados desde las plantillas activas." :padding="'p-0'">
            @if ($fixedExpenses->isEmpty())
                <div class="p-5">
                    <x-ui.empty title="Sin gastos fijos" description="Configura plantillas o genera los gastos del período." icon="expense" />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Descripción</th>
                                <th class="px-3 py-3 text-right font-medium">Previsto</th>
                                <th class="px-3 py-3 font-medium">Estado</th>
                                <th class="px-5 py-3 text-right font-medium">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($fixedExpenses as $expense)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-800">{{ $expense->description }}</p>
                                        <p class="text-xs text-slate-400">N.º {{ $expense->payment_number }}{{ $expense->expense_date ? ' · '.$expense->expense_date->format('d/m/Y') : '' }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-right font-medium text-slate-800">@money($expense->planned_amount)</td>
                                    <td class="px-3 py-3"><x-ui.badge :status="$expense->status" /></td>
                                    <td class="px-5 py-3 text-right">
                                        @if ($operable && $expense->status->value === 'pending')
                                            <form method="POST" action="{{ route('expenses.pay', $expense) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Marcar pagado</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">{{ $expense->actual_amount ? \App\Support\Money::format($expense->actual_amount) : '—' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Gastos variables" description="Registrados durante el período." :padding="'p-0'">
            @if ($variableExpenses->isEmpty())
                <div class="p-5">
                    <x-ui.empty title="Sin gastos variables" description="Registra un gasto variable para consumir el presupuesto del mes." icon="wallet" />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Descripción</th>
                                <th class="px-3 py-3 text-right font-medium">Importe</th>
                                <th class="px-3 py-3 font-medium">Estado</th>
                                <th class="px-5 py-3 text-right font-medium">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($variableExpenses as $expense)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-800">{{ $expense->description }}</p>
                                        <p class="text-xs text-slate-400">{{ $expense->expense_date?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-right font-medium text-slate-800">@money($expense->actual_amount ?? $expense->planned_amount)</td>
                                    <td class="px-3 py-3"><x-ui.badge :status="$expense->status" /></td>
                                    <td class="px-5 py-3 text-right">
                                        @if ($operable && $expense->status->value === 'pending')
                                            <form method="POST" action="{{ route('expenses.pay', $expense) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Marcar pagado</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>

    <p class="mt-4 text-xs text-slate-400">
        Los cambios en las plantillas de gastos fijos solo afectan a períodos generados después; nunca modifican el historial.
    </p>
</x-layouts.app>

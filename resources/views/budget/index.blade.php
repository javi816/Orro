@php
    $variableNegative = \App\Support\Money::compare($summary['variable_remaining'], '0.00') < 0;
@endphp

<x-layouts.app title="Presupuesto">
    <x-ui.page-header title="Presupuesto" subtitle="Distribución y límites de {{ $period->label() }}">
        <x-slot:actions>
            <x-ui.period-select :periods="$periods" :selected="$period" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($period->isClosed())
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            <x-ui.icon name="lock" class="h-4 w-4 text-slate-400" />
            Período cerrado: el presupuesto no se puede modificar.
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2" title="Presupuesto variable del mes" description="Define cuánto puedes gastar en gastos variables.">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-3xl font-semibold tracking-tight text-slate-900">@money($summary['variable_budget'])</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Gastado: @money($summary['variable_spent']) ·
                        Disponible: <span class="{{ $variableNegative ? 'font-semibold text-rose-600' : 'font-semibold text-emerald-700' }}">@money($summary['variable_remaining'])</span>
                    </p>
                </div>
                <x-ui.badge status="variable" />
            </div>

            <x-ui.progress
                class="mt-4"
                :value="$summary['variable_spent']"
                :max="$summary['variable_budget'] > 0 ? $summary['variable_budget'] : 1"
                :tone="$variableNegative ? 'rose' : 'emerald'"
            />

            @if (! $period->isClosed())
                <form method="POST" action="{{ route('budget.update') }}" class="mt-6 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="period_id" value="{{ $period->id }}">
                    <div class="w-full sm:w-56">
                        <x-ui.field label="Nuevo presupuesto variable" name="variable_budget" hint="Se aplica solo a este período.">
                            <x-ui.input name="variable_budget" inputmode="decimal" :value="old('variable_budget', $period->variable_budget)" placeholder="0,00" />
                        </x-ui.field>
                    </div>
                    <x-ui.button type="submit">Guardar presupuesto</x-ui.button>
                </form>
            @endif
        </x-ui.card>

        <x-ui.card title="Reserva de ahorro" description="Meta de {{ $period->label() }}">
            <div class="flex items-end justify-between">
                <p class="text-2xl font-semibold text-slate-900">@money($summary['savings_deposited'])</p>
                <p class="text-xs text-slate-500">de @money($summary['savings_goal'])</p>
            </div>
            <x-ui.progress class="mt-3" :value="$summary['savings_deposited']" :max="$summary['savings_goal'] > 0 ? $summary['savings_goal'] : 1" />
            <p class="mt-2 text-xs {{ \App\Support\Money::compare($summary['savings_missing'], '0.00') === 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                @if (\App\Support\Money::compare($summary['savings_goal'], '0.00') === 0)
                    Sin meta configurada.
                @elseif (\App\Support\Money::compare($summary['savings_missing'], '0.00') === 0)
                    Meta cumplida. Ahorro por separar: @money('0.00').
                @else
                    Ahorro por separar: @money($summary['savings_missing']).
                @endif
            </p>
            <a href="{{ route('savings.index', ['period' => $period->id]) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                Registrar aportación
                <x-ui.icon name="arrow-right" class="h-4 w-4" />
            </a>
        </x-ui.card>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <x-ui.card title="Gastos fijos" description="Compromisos recurrentes del período.">
            <div class="flex items-end justify-between">
                <p class="text-2xl font-semibold text-slate-900">@money($summary['fixed_paid'])</p>
                <p class="text-xs text-slate-500">pagados</p>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Pendientes</dt>
                    <dd class="font-medium {{ $summary['fixed_pending_count'] > 0 ? 'text-amber-700' : 'text-slate-700' }}">@money($summary['fixed_pending'])</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Total comprometido</dt>
                    <dd class="font-medium text-slate-700">@money(\App\Support\Money::add($summary['fixed_paid'], $summary['fixed_pending']))</dd>
                </div>
            </dl>
            <a href="{{ route('expenses.index', ['period' => $period->id]) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                Ver gastos
                <x-ui.icon name="arrow-right" class="h-4 w-4" />
            </a>
        </x-ui.card>

        <x-ui.card title="Resultado del mes" description="Cálculo del dinero disponible.">
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="flex items-center justify-between py-2">
                    <dt class="text-slate-500">Ingresos recibidos</dt>
                    <dd class="font-medium text-emerald-700">+ @money($summary['income_received'])</dd>
                </div>
                <div class="flex items-center justify-between py-2">
                    <dt class="text-slate-500">Préstamos recibidos</dt>
                    <dd class="font-medium text-emerald-700">+ @money($summary['loans_received'])</dd>
                </div>
                <div class="flex items-center justify-between py-2">
                    <dt class="text-slate-500">Gastos pagados</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['expenses_paid'])</dd>
                </div>
                <div class="flex items-center justify-between py-2">
                    <dt class="text-slate-500">Fijos pendientes</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['fixed_pending'])</dd>
                </div>
                <div class="flex items-center justify-between py-2">
                    <dt class="text-slate-500">Presupuesto variable restante</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['variable_remaining'])</dd>
                </div>
                <div class="flex items-center justify-between py-2">
                    <dt class="text-slate-500">Ahorro por separar</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['savings_missing'])</dd>
                </div>
                <div class="flex items-center justify-between py-2">
                    <dt class="text-slate-500">Reposiciones vencidas</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['overdue_repayments'])</dd>
                </div>
                <div class="flex items-center justify-between pt-3">
                    <dt class="font-semibold text-slate-900">Dinero disponible</dt>
                    <dd class="text-base font-semibold {{ \App\Support\Money::compare($summary['available_money'], '0.00') < 0 ? 'text-rose-600' : 'text-emerald-700' }}">@money($summary['available_money'])</dd>
                </div>
            </dl>
        </x-ui.card>
    </div>
</x-layouts.app>

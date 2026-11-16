@php
    use App\Support\Money;

    $availableNegative = Money::compare($summary['available_money'], '0.00') < 0;
    $variableNegative = Money::compare($summary['variable_remaining'], '0.00') < 0;
    $hasSavingsGoal = Money::compare($summary['savings_goal'], '0.00') > 0;
@endphp

<x-layouts.app title="Dashboard">
    <x-ui.page-header title="Dashboard" subtitle="Resumen financiero de {{ $period->label() }}">
        <x-slot:actions>
            <x-ui.period-select :periods="$periods" :selected="$period" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($period->isClosed())
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            <x-ui.icon name="lock" class="h-4 w-4 text-slate-400" />
            Período cerrado. La información es de solo lectura.
            <a href="{{ route('periods.show', $period) }}" class="ml-auto font-semibold text-emerald-700 hover:text-emerald-800">Ver cierre</a>
        </div>
    @elseif ($period->status->value === 'ready_to_close')
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <x-ui.icon name="check" class="h-4 w-4" />
            El período cumple las condiciones para cerrarse.
            <a href="{{ route('periods.show', $period) }}" class="ml-auto font-semibold hover:text-emerald-900">Revisar y cerrar</a>
        </div>
    @endif

    <div class="relative overflow-hidden rounded-3xl bg-slate-950 p-6 shadow-xl shadow-slate-900/10 sm:p-8">
        <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 left-1/3 h-72 w-72 rounded-full bg-sky-500/10 blur-3xl"></div>

        <div class="relative grid gap-8 lg:grid-cols-[1.15fr_1fr] lg:items-center">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300/80">Dinero disponible</p>
                <p class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl {{ $availableNegative ? 'text-rose-300' : 'text-white' }}">
                    @money($summary['available_money'])
                </p>
                <p class="mt-3 max-w-lg text-sm leading-6 text-slate-400">
                    Es lo que realmente puedes gastar después de reservar gastos pendientes, presupuesto variable, ahorro del mes y reposiciones vencidas.
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <x-ui.badge :status="$period->status" />
                    <a href="{{ route('budget.index', ['period' => $period->id]) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15">
                        Ver presupuesto
                        <x-ui.icon name="arrow-right" class="h-3.5 w-3.5" />
                    </a>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-inset ring-white/10">
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Entradas del mes</dt>
                    <dd class="mt-1.5 text-lg font-semibold text-white">@money(Money::add($summary['income_received'], $summary['loans_received']))</dd>
                    <p class="mt-0.5 text-[11px] text-slate-500">Ingresos + préstamos</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-inset ring-white/10">
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Comprometido</dt>
                    <dd class="mt-1.5 text-lg font-semibold text-amber-300">@money($summary['committed_money'])</dd>
                    <p class="mt-0.5 text-[11px] text-slate-500">Pendiente + reservas</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-inset ring-white/10">
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Ahorro acumulado</dt>
                    <dd class="mt-1.5 text-lg font-semibold text-white">@money($summary['savings_balance'])</dd>
                    <p class="mt-0.5 text-[11px] text-slate-500">Calculado por movimientos</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-inset ring-white/10">
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Deuda pendiente</dt>
                    <dd class="mt-1.5 text-lg font-semibold text-white">@money($summary['loans_outstanding'])</dd>
                    <p class="mt-0.5 text-[11px] text-slate-500">Préstamos por reponer</p>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Ingresos recibidos" :value="Money::format($summary['income_received'])" :hint="$summary['income_pending_count'].' pendiente(s) por '.Money::format($summary['income_pending'])" tone="emerald" icon="income" />
        <x-ui.stat label="Gastos pagados" :value="Money::format($summary['expenses_paid'])" :hint="'Fijos pendientes: '.Money::format($summary['fixed_pending'])" tone="rose" icon="expense" />
        <x-ui.stat label="Ahorro acumulado" :value="Money::format($summary['savings_balance'])" hint="Saldo real de todos los movimientos" tone="sky" icon="savings" />
        <x-ui.stat label="Presupuesto variable" :value="Money::format($summary['variable_remaining'])" :hint="'Presupuesto: '.Money::format($summary['variable_budget'])" :tone="$variableNegative ? 'rose' : 'amber'" icon="wallet" />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2" title="Cómo se calcula tu dinero disponible" description="Todos los importes se calculan con precisión decimal.">
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-slate-500">Ingresos recibidos</dt>
                    <dd class="font-medium text-emerald-700">+ @money($summary['income_received'])</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-slate-500">Préstamos recibidos del ahorro</dt>
                    <dd class="font-medium text-emerald-700">+ @money($summary['loans_received'])</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-slate-500">Gastos pagados</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['expenses_paid'])</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-slate-500">Gastos fijos pendientes</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['fixed_pending'])</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-slate-500">Presupuesto variable restante</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['variable_remaining'])</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-slate-500">Ahorro por separar</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['savings_missing'])</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-slate-500">Reposiciones vencidas</dt>
                    <dd class="font-medium text-rose-600">− @money($summary['overdue_repayments'])</dd>
                </div>
                <div class="flex items-center justify-between pt-3">
                    <dt class="font-semibold text-slate-900">Dinero disponible</dt>
                    <dd class="text-base font-semibold {{ $availableNegative ? 'text-rose-600' : 'text-emerald-700' }}">@money($summary['available_money'])</dd>
                </div>
            </dl>
        </x-ui.card>

        <div class="space-y-5">
            <x-ui.card title="Meta de ahorro" description="{{ $period->label() }}">
                @if ($hasSavingsGoal)
                    <div class="flex items-end justify-between">
                        <p class="text-2xl font-semibold text-slate-900">@money($summary['savings_deposited'])</p>
                        <p class="text-xs text-slate-500">de @money($summary['savings_goal'])</p>
                    </div>
                    <x-ui.progress class="mt-3" :value="$summary['savings_deposited']" :max="$summary['savings_goal']" />
                    <p class="mt-2 text-xs {{ Money::compare($summary['savings_missing'], '0.00') === 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ Money::compare($summary['savings_missing'], '0.00') === 0 ? 'Meta cumplida.' : 'Faltan '.Money::format($summary['savings_missing']).'.' }}
                    </p>
                @else
                    <p class="text-sm text-slate-500">Aún no has definido una meta mensual.</p>
                    <a href="{{ route('settings.edit') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                        Configurar meta
                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    </a>
                @endif
            </x-ui.card>

            <x-ui.card title="Próxima reposición" description="Obligación más cercana">
                @if ($nextRepayment)
                    <p class="text-xl font-semibold text-slate-900">@money($nextRepayment['outstanding'])</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $nextRepayment['loan']->description }}</p>
                    <p class="mt-2 text-xs text-slate-400">Vence en {{ $nextRepayment['loan']->duePeriod->label() }}</p>
                    <a href="{{ route('loans.index', ['period' => $period->id]) }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                        Registrar reposición
                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    </a>
                @else
                    <p class="text-sm text-slate-500">Sin reposiciones pendientes.</p>
                @endif
            </x-ui.card>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2" title="Condiciones de cierre" description="El cierre protege el historial del período.">
            <ul class="space-y-3">
                @foreach ($readiness['checks'] as $check)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            <x-ui.icon :name="$check['ok'] ? 'check' : 'warning'" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $check['label'] }}</p>
                            <p class="text-xs text-slate-500">{{ $check['detail'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                <a href="{{ route('periods.show', $period) }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Ver resumen de cierre</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('periods.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Historial de períodos</a>
            </div>
        </x-ui.card>

        <x-ui.card title="Gastos del mes" description="Fijos y variables">
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Fijos pagados</dt>
                    <dd class="font-medium text-slate-800">@money($summary['fixed_paid'])</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Fijos pendientes</dt>
                    <dd class="font-medium {{ $summary['fixed_pending_count'] > 0 ? 'text-amber-700' : 'text-slate-800' }}">@money($summary['fixed_pending'])</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Variables realizados</dt>
                    <dd class="font-medium text-slate-800">@money($summary['variable_spent'])</dd>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <dt class="text-slate-500">Reposiciones del mes</dt>
                    <dd class="font-medium text-sky-700">@money($summary['savings_repaid'])</dd>
                </div>
            </dl>
            <a href="{{ route('expenses.index', ['period' => $period->id]) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                Gestionar gastos
                <x-ui.icon name="arrow-right" class="h-4 w-4" />
            </a>
        </x-ui.card>
    </div>

    @if ($pendingIncomes->isNotEmpty())
        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex items-start gap-3">
                <x-ui.icon name="warning" class="mt-0.5 h-5 w-5 text-amber-600" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-900">Ingresos esperados sin recibir</p>
                    <p class="mt-1 text-xs text-amber-800">
                        No impiden el cierre, pero revisa si realmente fueron recibidos.
                    </p>
                    <ul class="mt-3 grid gap-1 text-sm text-amber-900 sm:grid-cols-2">
                        @foreach ($pendingIncomes as $income)
                            <li class="flex items-center justify-between gap-3 rounded-lg bg-amber-100/60 px-3 py-2">
                                <span class="truncate">{{ $income->description }}</span>
                                <span class="font-medium">@money($income->amount)</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <a href="{{ route('incomes.index', ['period' => $period->id]) }}" class="hidden shrink-0 text-xs font-semibold text-amber-800 hover:text-amber-900 sm:block">Revisar</a>
            </div>
        </div>
    @endif
</x-layouts.app>

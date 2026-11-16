@php
    use App\Support\Money;

    $operable = ! $period->isClosed();
    $reference = $period->year * 100 + $period->month;
@endphp

<x-layouts.app title="Préstamos">
    <x-ui.page-header title="Préstamos del ahorro" subtitle="Préstamos personales y reposiciones de {{ $period->label() }}">
        <x-slot:actions>
            <x-ui.period-select :periods="$periods" :selected="$period" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($period->isClosed())
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            <x-ui.icon name="lock" class="h-4 w-4 text-slate-400" />
            Período cerrado: no se pueden registrar préstamos ni reposiciones en él.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Disponible para prestar" :value="Money::format($loanable)" hint="Saldo del ahorro menos mínimo protegido" tone="emerald" icon="savings" />
        <x-ui.stat label="Deuda pendiente" :value="Money::format($summary['loans_outstanding'])" :hint="$pendingLoans->count().' préstamo(s) por reponer'" tone="amber" icon="loans" />
        <x-ui.stat label="Dinero disponible" :value="Money::format($summary['available_money'])" hint="Incluye reposiciones vencidas" :tone="Money::compare($summary['available_money'], '0.00') < 0 ? 'rose' : 'sky'" icon="wallet" />
        <x-ui.stat label="Préstamos del mes" :value="Money::format($summary['loans_received'])" hint="Recibidos desde el ahorro" tone="violet" icon="income" />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <x-ui.card title="Nuevo préstamo" description="El vencimiento se asigna al siguiente período.">
            @if ($operable)
                <form method="POST" action="{{ route('loans.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="period_id" value="{{ $period->id }}">

                    <x-ui.field label="Importe" name="amount">
                        <x-ui.input name="amount" inputmode="decimal" value="{{ old('amount') }}" placeholder="0,00" required />
                    </x-ui.field>

                    <x-ui.field label="Descripción" name="description">
                        <x-ui.input name="description" value="{{ old('description') }}" placeholder="Ej. Arreglo del vehículo" required />
                    </x-ui.field>

                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-500">
                        Vence en <span class="font-semibold text-slate-700">{{ $nextDuePeriod->label() }}</span>.
                        Disponible: <span class="font-semibold text-emerald-700">@money($loanable)</span>.
                    </div>

                    <x-ui.button type="submit" class="w-full">Registrar préstamo</x-ui.button>
                </form>
                <p class="mt-3 text-[11px] leading-5 text-slate-400">
                    Al crearlo se registra automáticamente un retiro del ahorro por el mismo importe.
                </p>
            @else
                <p class="text-sm text-slate-500">Selecciona un período abierto para registrar un préstamo.</p>
            @endif
        </x-ui.card>

        <x-ui.card class="lg:col-span-2" title="Obligaciones pendientes" description="Reposiciones parciales o completas." :padding="'p-0'">
            @if ($pendingLoans->isEmpty())
                <div class="p-5">
                    <x-ui.empty title="Sin préstamos pendientes" description="No hay deuda del ahorro por reponer." icon="check" />
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($pendingLoans as $item)
                        @php
                            $loan = $item['loan'];
                            $isOverdue = ($loan->duePeriod->year * 100 + $loan->duePeriod->month) < $reference;
                        @endphp
                        <div class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $loan->description }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        Origen: {{ $loan->originPeriod->label() }} · Vence: {{ $loan->duePeriod->label() }}
                                    </p>
                                </div>
                                <x-ui.badge :status="$isOverdue ? 'overdue' : 'pending'" :label="$isOverdue ? 'Vencido' : 'Pendiente'" />
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Préstamo</p>
                                    <p class="font-medium text-slate-700">@money($loan->amount)</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Pendiente</p>
                                    <p class="font-semibold {{ $isOverdue ? 'text-rose-600' : 'text-amber-700' }}">@money($item['outstanding'])</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Repuesto</p>
                                    <p class="font-medium text-emerald-700">@money(Money::sub($loan->amount, $item['outstanding']))</p>
                                </div>
                            </div>

                            @if ($operable)
                                <form method="POST" action="{{ route('loans.repay', $loan) }}" class="mt-3 flex flex-wrap items-end gap-2">
                                    @csrf
                                    <input type="hidden" name="period_id" value="{{ $period->id }}">
                                    <div class="min-w-36 flex-1">
                                        <x-ui.field label="Reponer" name="amount">
                                            <x-ui.input name="amount" inputmode="decimal" value="{{ $item['outstanding'] }}" placeholder="0,00" required />
                                        </x-ui.field>
                                    </div>
                                    <x-ui.button type="submit" variant="secondary">Registrar reposición</x-ui.button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card class="mt-5" title="Historial de préstamos" description="Todos los préstamos registrados" :padding="'p-0'">
        @if ($loans->isEmpty())
            <div class="p-5">
                <x-ui.empty title="Aún no hay préstamos" description="Los préstamos del ahorro aparecerán aquí." icon="loans" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Descripción</th>
                            <th class="px-3 py-3 font-medium">Origen</th>
                            <th class="px-3 py-3 font-medium">Vencimiento</th>
                            <th class="px-3 py-3 text-right font-medium">Importe</th>
                            <th class="px-3 py-3 text-right font-medium">Pendiente</th>
                            <th class="px-5 py-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($loans as $item)
                            @php
                                $loan = $item['loan'];
                                $isOverdue = ($loan->duePeriod->year * 100 + $loan->duePeriod->month) < $reference && Money::isPositive($item['outstanding']);
                                $isRepaid = ! Money::isPositive($item['outstanding']);
                            @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $loan->description }}</td>
                                <td class="px-3 py-3 text-slate-500">{{ $loan->originPeriod->shortLabel() }}</td>
                                <td class="px-3 py-3 text-slate-500">{{ $loan->duePeriod->shortLabel() }}</td>
                                <td class="px-3 py-3 text-right font-medium text-slate-800">@money($loan->amount)</td>
                                <td class="px-3 py-3 text-right {{ $isRepaid ? 'text-emerald-700' : 'text-amber-700' }}">@money($item['outstanding'])</td>
                                <td class="px-5 py-3">
                                    @if ($isRepaid)
                                        <x-ui.badge status="paid" label="Repuesto" />
                                    @elseif ($isOverdue)
                                        <x-ui.badge status="overdue" />
                                    @else
                                        <x-ui.badge status="pending" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    @if ($nextRepayment)
        <p class="mt-4 text-xs text-slate-400">
            Próxima reposición: <span class="font-semibold text-slate-600">@money($nextRepayment['outstanding'])</span>
            · {{ $nextRepayment['loan']->description }} · vence {{ $nextRepayment['loan']->duePeriod->label() }}.
        </p>
    @endif
</x-layouts.app>

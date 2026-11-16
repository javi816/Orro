@php
    $operable = ! $period->isClosed();
@endphp

<x-layouts.app title="Ingresos">
    <x-ui.page-header title="Ingresos" subtitle="Ingresos esperados y recibidos de {{ $period->label() }}">
        <x-slot:actions>
            <x-ui.period-select :periods="$periods" :selected="$period" />
            @if ($operable)
                <form method="POST" action="{{ route('incomes.generate') }}">
                    @csrf
                    <input type="hidden" name="period_id" value="{{ $period->id }}">
                    <x-ui.button variant="secondary" type="submit">
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        Generar faltantes
                    </x-ui.button>
                </form>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($period->isClosed())
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            <x-ui.icon name="lock" class="h-4 w-4 text-slate-400" />
            Período cerrado: los ingresos son de solo lectura.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat label="Recibidos" :value="\App\Support\Money::format($summary['income_received'])" :hint="$incomes->filter(fn ($i) => $i->status->value === 'received')->count().' ingreso(s)'" tone="emerald" icon="income" />
        <x-ui.stat label="Pendientes" :value="\App\Support\Money::format($summary['income_pending'])" :hint="$summary['income_pending_count'].' ingreso(s) por recibir'" tone="amber" icon="calendar" />
        <x-ui.stat label="Total esperado" :value="\App\Support\Money::format($summary['income_expected'])" hint="Suma de todos los ingresos del período" tone="sky" icon="wallet" />
    </div>

    <x-ui.card class="mt-5" title="Ingresos del período" description="Los ingresos se generan desde las fuentes activas de configuración." :padding="'p-0'">
        @if ($incomes->isEmpty())
            <div class="p-5">
                <x-ui.empty title="No hay ingresos en este período" description="Agrega fuentes de ingreso en configuración y pulsa «Generar faltantes»." icon="income">
                    <a href="{{ route('settings.edit') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Configurar fuentes de ingreso</a>
                </x-ui.empty>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Descripción</th>
                            <th class="px-3 py-3 font-medium">Pago</th>
                            <th class="px-3 py-3 text-right font-medium">Importe</th>
                            <th class="px-3 py-3 font-medium">Estado</th>
                            <th class="px-3 py-3 font-medium">Recibido</th>
                            <th class="px-5 py-3 text-right font-medium">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($incomes as $income)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-800">{{ $income->description }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ $income->source?->description ?? 'Ingreso del período' }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 text-slate-500">N.º {{ $income->payment_number }}</td>
                                <td class="px-3 py-3 text-right font-medium text-slate-800">@money($income->amount)</td>
                                <td class="px-3 py-3"><x-ui.badge :status="$income->status" /></td>
                                <td class="px-3 py-3 text-slate-500">
                                    {{ $income->received_at ? $income->received_at->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if ($operable && $income->status->value === 'pending')
                                        <form method="POST" action="{{ route('incomes.receive', $income) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                                                Marcar recibido
                                            </button>
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

    <p class="mt-4 text-xs text-slate-400">
        Historial: selecciona otro período en el selector superior para consultar sus ingresos.
    </p>
</x-layouts.app>

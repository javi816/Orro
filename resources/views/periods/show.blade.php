@php
    use App\Support\Money;
@endphp

<x-layouts.app :title="'Cierre de '.$period->label()">
    <x-ui.page-header :title="'Cierre de '.$period->label()" subtitle="Resumen financiero y condiciones del mes">
        <x-slot:actions>
            <x-ui.period-select :periods="$periods" :selected="$period" />
            <a href="{{ route('periods.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Ver historial
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($period->isClosed())
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            <x-ui.badge status="closed" />
            <span>Cerrado el {{ $period->closed_at?->format('d/m/Y H:i') }}. El período es de solo lectura.</span>
            <span class="ml-auto inline-flex items-center gap-1 text-xs text-slate-400">
                <x-ui.icon name="lock" class="h-3.5 w-3.5" />
                Historial protegido
            </span>
        </div>

        <x-ui.card title="Resumen guardado al cierre" description="Estas cifras quedaron registradas y no se recalculan.">
            <x-ui.closing-summary :summary="$period->closing_summary ?? []" />
        </x-ui.card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat label="Ingresos recibidos" :value="Money::format($summary['income_received'])" hint="Del período" tone="emerald" icon="income" />
            <x-ui.stat label="Gastos pagados" :value="Money::format($summary['expenses_paid'])" hint="Fijos y variables" tone="rose" icon="expense" />
            <x-ui.stat label="Ahorro del mes" :value="Money::format($summary['savings_deposited'])" :hint="'Meta: '.Money::format($summary['savings_goal'])" tone="sky" icon="savings" />
            <x-ui.stat label="Dinero disponible" :value="Money::format($summary['available_money'])" :tone="Money::compare($summary['available_money'], '0.00') < 0 ? 'rose' : 'amber'" icon="wallet" />
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <x-ui.card title="Condiciones para cerrar" description="Se verifican en el servidor antes de permitir el cierre.">
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

                @if (count($readiness['warnings']) > 0)
                    <div class="mt-5 space-y-2 border-t border-slate-100 pt-4">
                        @foreach ($readiness['warnings'] as $warning)
                            <div class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                <x-ui.icon name="warning" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                <p>{{ $warning }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-5 border-t border-slate-100 pt-4">
                    @if ($readiness['can_close'])
                        <p class="text-sm font-semibold text-emerald-700">Todo listo para cerrar el mes.</p>
                    @else
                        <p class="text-sm font-semibold text-amber-700">Aún faltan condiciones por cumplir.</p>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card title="Resumen financiero del cierre" description="Vista previa de lo que se guardará.">
                <x-ui.closing-summary :summary="$closingPreview" />

                @if ($readiness['can_close'])
                    <form
                        method="POST"
                        action="{{ route('periods.close', $period) }}"
                        class="mt-5 border-t border-slate-100 pt-5"
                        data-confirm="¿Confirmas el cierre de {{ $period->label() }}? No podrás reabrir el período ni modificar sus movimientos."
                    >
                        @csrf
                        <x-ui.button type="submit">
                            <x-ui.icon name="lock" class="h-4 w-4" />
                            Cerrar mes
                        </x-ui.button>
                        <p class="mt-2 text-[11px] leading-5 text-slate-400">
                            El cierre guarda el resumen y bloquea ingresos, gastos, ahorros y préstamos del período.
                        </p>
                    </form>
                @endif
            </x-ui.card>
        </div>
    @endif
</x-layouts.app>

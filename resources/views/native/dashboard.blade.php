@php
    use App\Support\Money;

    $availableNegative = Money::compare($summary['available_money'], '0.00') < 0;
    $variableNegative = Money::compare($summary['variable_remaining'], '0.00') < 0;
@endphp

<native:top-bar title="Inicio" subtitle="{{ $period->label() }}" />

<native:column class="w-full h-full bg-theme-background">
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full gap-3 p-4">
            <native:select
                label="Período"
                :options="$periodLabels"
                :value="$periodLabel"
                @change="choosePeriod" />

            @if ($period->isClosed())
                <native:row class="w-full items-center gap-2 rounded-lg bg-theme-surface-variant p-3">
                    <native:icon name="lock" :size="18" class="text-theme-on-surface-variant" />
                    <native:text class="flex-1 text-sm text-theme-on-surface-variant">
                        Período cerrado. Solo lectura.
                    </native:text>
                </native:row>
            @elseif ($readiness['can_close'])
                <native:row class="w-full items-center gap-2 rounded-lg bg-theme-success/15 p-3">
                    <native:icon name="check" :size="18" class="text-theme-success" />
                    <native:text class="flex-1 text-sm text-theme-on-surface">
                        Todo listo para cerrar el mes.
                    </native:text>
                    <native:button size="sm" variant="secondary" @navigate="'/periodos/' . $period->id">
                        Ver cierre
                    </native:button>
                </native:row>
            @endif

            <native:column class="w-full gap-1 rounded-2xl p-5 {{ $availableNegative ? 'bg-theme-destructive' : 'bg-theme-primary' }}">
                <native:text class="text-xs uppercase text-theme-on-primary">Dinero disponible</native:text>
                <native:text class="text-4xl font-bold text-theme-on-primary">
                    {{ Money::format($summary['available_money']) }}
                </native:text>
                <native:text class="text-xs text-theme-on-primary">
                    Comprometido: {{ Money::format($summary['committed_money']) }}
                </native:text>
            </native:column>

            <native:row class="w-full gap-2">
                <native:column class="flex-1 flex-col items-center gap-1 rounded-xl bg-theme-surface p-3" @navigate='/ingresos'>
                    <native:icon name="receipt" :size="22" class="text-theme-primary" />
                    <native:text class="text-xs font-semibold text-theme-on-surface">Ingresos</native:text>
                </native:column>
                <native:column class="flex-1 flex-col items-center gap-1 rounded-xl bg-theme-surface p-3" @navigate='/gastos'>
                    <native:icon name="cart" :size="22" class="text-theme-primary" />
                    <native:text class="text-xs font-semibold text-theme-on-surface">Gastos</native:text>
                </native:column>
                <native:column class="flex-1 flex-col items-center gap-1 rounded-xl bg-theme-surface p-3" @navigate='/presupuesto'>
                    <native:icon name="wallet" :size="22" class="text-theme-primary" />
                    <native:text class="text-xs font-semibold text-theme-on-surface">Presupuesto</native:text>
                </native:column>
                <native:column class="flex-1 flex-col items-center gap-1 rounded-xl bg-theme-surface p-3" @navigate='/periodos'>
                    <native:icon name="history" :size="22" class="text-theme-primary" />
                    <native:text class="text-xs font-semibold text-theme-on-surface">Períodos</native:text>
                </native:column>
            </native:row>

            <native:row class="w-full gap-3">
                <native:column class="flex-1 gap-1 rounded-xl bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Ingresos recibidos</native:text>
                    <native:text class="text-lg font-semibold text-theme-success">{{ Money::format($summary['income_received']) }}</native:text>
                </native:column>
                <native:column class="flex-1 gap-1 rounded-xl bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Gastos pagados</native:text>
                    <native:text class="text-lg font-semibold text-theme-on-surface">{{ Money::format($summary['expenses_paid']) }}</native:text>
                </native:column>
            </native:row>

            <native:row class="w-full gap-3">
                <native:column class="flex-1 gap-1 rounded-xl bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Ahorro aportado</native:text>
                    <native:text class="text-lg font-semibold text-theme-on-surface">{{ Money::format($summary['savings_deposited']) }}</native:text>
                </native:column>
                <native:column class="flex-1 gap-1 rounded-xl bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Presupuesto variable</native:text>
                    <native:text class="text-lg font-semibold {{ $variableNegative ? 'text-theme-destructive' : 'text-theme-on-surface' }}">
                        {{ Money::format($summary['variable_remaining']) }}
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">de {{ Money::format($summary['variable_budget']) }}</native:text>
                </native:column>
            </native:row>

            <native:column class="w-full gap-1 rounded-xl bg-theme-surface p-4">
                <native:row class="w-full items-center justify-between">
                    <native:text class="text-sm font-semibold text-theme-on-surface">Ingresos pendientes</native:text>
                    <native:badge :count="$pendingIncomes->count()" variant="accent" />
                </native:row>

                @forelse ($pendingIncomes as $income)
                    <native:row class="w-full items-center justify-between pt-2" @navigate='/ingresos'>
                        <native:text class="flex-1 text-sm text-theme-on-surface">{{ $income->description }}</native:text>
                        <native:text class="text-sm font-semibold text-theme-on-surface">{{ Money::format((string) $income->amount) }}</native:text>
                    </native:row>
                @empty
                    <native:text class="text-xs text-theme-on-surface-variant">No hay ingresos pendientes.</native:text>
                @endforelse
            </native:column>

            @if ($nextRepayment)
                <native:column class="w-full gap-1 rounded-xl bg-theme-surface p-4">
                    <native:text class="text-sm font-semibold text-theme-on-surface">Próxima reposición</native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">
                        {{ $nextRepayment['loan']->description }} · vence {{ $nextRepayment['loan']->duePeriod->label() }}
                    </native:text>
                    <native:text class="text-lg font-semibold text-theme-accent">{{ Money::format($nextRepayment['outstanding']) }}</native:text>
                </native:column>
            @endif

            <native:text class="text-xs text-theme-on-surface-variant">
                Deuda pendiente del ahorro: {{ Money::format($summary['loans_outstanding']) }}
            </native:text>
        </native:column>
    </native:scroll-view>
</native:column>

@include('native.partials.tabs')

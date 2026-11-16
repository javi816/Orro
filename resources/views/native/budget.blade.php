@php
    use App\Support\Money;

    $operable = ! $period->isClosed();
@endphp

<native:top-bar title="Presupuesto" subtitle="Distribución y límites de {{ $period->label() }}" back />

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
                        Período cerrado: el presupuesto no se puede modificar.
                    </native:text>
                </native:row>
            @endif

            @if ($error !== '')
                <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif

            @if ($success !== '')
                <native:text class="text-sm text-theme-success">{{ $success }}</native:text>
            @endif

            <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                <native:text class="text-xs uppercase text-theme-on-surface-variant">
                    Presupuesto variable del mes
                </native:text>

                <native:text class="text-3xl font-semibold tracking-tight {{ Money::compare($summary['variable_remaining'], '0.00') < 0 ? 'text-theme-destructive' : 'text-theme-primary' }}">
                    {{ Money::format($summary['variable_remaining']) }}
                </native:text>

                <native:text class="text-xs text-theme-on-surface-variant">
                    Restante de {{ Money::format($summary['variable_budget']) }}
                </native:text>

                <native:progress-bar :value="$progress" />

                <native:row class="w-full gap-3 pt-1">
                    <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface-variant p-2">
                        <native:text class="text-xs text-theme-on-surface-variant">Gastado</native:text>
                        <native:text class="text-base font-semibold text-theme-on-surface">
                            {{ Money::format($summary['variable_spent']) }}
                        </native:text>
                    </native:column>
                    <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface-variant p-2">
                        <native:text class="text-xs text-theme-on-surface-variant">Pendiente</native:text>
                        <native:text class="text-base font-semibold text-theme-on-surface">
                            {{ Money::format($summary['variable_pending']) }}
                        </native:text>
                    </native:column>
                </native:row>
            </native:column>

            <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                <native:text class="text-sm font-semibold text-theme-on-surface">
                    Definir presupuesto variable
                </native:text>
                <native:text class="text-xs text-theme-on-surface-variant">
                    Define cuánto puedes gastar en gastos variables este mes.
                </native:text>

                <native:outlined-text-input
                    label="Presupuesto variable"
                    placeholder="0,00"
                    keyboard="decimal"
                    native:model.debounce.300ms="variableBudget" />

                <native:button
                    @press="save"
                    :disabled="! $operable">
                    Guardar presupuesto
                </native:button>
            </native:column>

            <native:row class="w-full gap-3">
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Fijos pagados</native:text>
                    <native:text class="text-base font-semibold text-theme-on-surface">
                        {{ Money::format($summary['fixed_paid']) }}
                    </native:text>
                </native:column>
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Fijos pendientes</native:text>
                    <native:text class="text-base font-semibold text-theme-on-surface">
                        {{ Money::format($summary['fixed_pending']) }}
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">
                        {{ $summary['fixed_pending_count'] }} por pagar
                    </native:text>
                </native:column>
            </native:row>

            <native:text class="text-xs text-theme-on-surface-variant">
                Los cambios en las plantillas de gastos fijos solo afectan a períodos generados después; nunca modifican el historial.
            </native:text>
        </native:column>
    </native:scroll-view>
</native:column>

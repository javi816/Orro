@php
    use App\Support\Money;

    $operable = ! $period->isClosed();
@endphp

<native:top-bar title="Gastos" subtitle="Fijos y variables de {{ $period->label() }}" back />

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
                        Período cerrado: los gastos son de solo lectura.
                    </native:text>
                </native:row>
            @endif

            @if ($error !== '')
                <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif

            @if ($success !== '')
                <native:text class="text-sm text-theme-success">{{ $success }}</native:text>
            @endif

            <native:row class="w-full gap-2">
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Fijos pagados</native:text>
                    <native:text class="text-base font-semibold text-theme-success">
                        {{ Money::format($summary['fixed_paid']) }}
                    </native:text>
                </native:column>
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Fijos pendientes</native:text>
                    <native:text class="text-base font-semibold text-theme-accent">
                        {{ Money::format($summary['fixed_pending']) }}
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">{{ $summary['fixed_pending_count'] }} por pagar</native:text>
                </native:column>
            </native:row>

            <native:row class="w-full gap-2">
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Variables realizados</native:text>
                    <native:text class="text-base font-semibold text-theme-on-surface">
                        {{ Money::format($summary['variable_spent']) }}
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">
                        Presupuesto: {{ Money::format($summary['variable_budget']) }}
                    </native:text>
                </native:column>
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Total pagado</native:text>
                    <native:text class="text-base font-semibold text-theme-on-surface">
                        {{ Money::format($summary['expenses_paid']) }}
                    </native:text>
                </native:column>
            </native:row>

            @if ($operable)
                <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                    <native:text class="text-sm font-semibold text-theme-on-surface">
                        Registrar gasto variable
                    </native:text>

                    <native:outlined-text-input
                        label="Descripción"
                        placeholder="Mercado, transporte…"
                        native:model.debounce.300ms="description" />

                    <native:outlined-text-input
                        label="Importe"
                        placeholder="0,00"
                        keyboard="decimal"
                        native:model.debounce.300ms="amount" />

                    <native:date-picker
                        label="Fecha"
                        mode="date"
                        locale="es"
                        native:model="expenseDate" />

                    <native:outlined-text-input
                        label="N.º de pago"
                        placeholder="1"
                        keyboard="number"
                        native:model.debounce.300ms="paymentNumber" />

                    <native:select
                        label="Estado"
                        :options="$statusLabels"
                        :value="$statusLabel"
                        @change="chooseStatus" />

                    <native:button @press="registerVariable">
                        Registrar gasto variable
                    </native:button>
                </native:column>
            @endif

            <native:button variant="secondary" icon="plus" @press="generateFixed">
                Generar gastos fijos
            </native:button>

            <native:text class="text-sm font-semibold text-theme-on-surface">Gastos fijos</native:text>

            @forelse ($fixedExpenses as $expense)
                <native:column class="w-full gap-1 rounded-lg bg-theme-surface p-3">
                    <native:row class="w-full items-center gap-2">
                        <native:text class="flex-1 text-sm font-semibold text-theme-on-surface">
                            {{ $expense->description }}
                        </native:text>
                        <native:badge
                            :label="$expense->status->label()"
                            :variant="$expense->status->value === 'paid' ? 'success' : 'accent'" />
                    </native:row>

                    <native:text class="text-xs text-theme-on-surface-variant">
                        N.º {{ $expense->payment_number }} · {{ $expense->planned_amount ? Money::format((string) $expense->planned_amount) : '—' }}
                    </native:text>

                    @if ($operable && $expense->status->value === 'pending')
                        <native:button size="sm" variant="secondary" @press="markPaid({{ $expense->id }})">
                            Marcar pagado
                        </native:button>
                    @else
                        <native:text class="text-xs text-theme-on-surface-variant">
                            {{ $expense->actual_amount ? Money::format((string) $expense->actual_amount) : '—' }}
                        </native:text>
                    @endif

                    @if ($operable)
                        <native:button size="sm" variant="destructive" @press="deleteExpense({{ $expense->id }})">
                            Eliminar
                        </native:button>
                    @endif
                </native:column>
            @empty
                <native:column class="w-full items-center gap-2 rounded-lg bg-theme-surface p-5">
                    <native:text class="text-sm text-theme-on-surface-variant">
                        Sin gastos fijos en este período.
                    </native:text>
                </native:column>
            @endforelse

            <native:text class="text-sm font-semibold text-theme-on-surface">Gastos variables</native:text>

            @forelse ($variableExpenses as $expense)
                <native:column class="w-full gap-1 rounded-lg bg-theme-surface p-3">
                    <native:row class="w-full items-center gap-2">
                        <native:text class="flex-1 text-sm font-semibold text-theme-on-surface">
                            {{ $expense->description }}
                        </native:text>
                        <native:badge
                            :label="$expense->status->label()"
                            :variant="$expense->status->value === 'paid' ? 'success' : 'accent'" />
                    </native:row>

                    <native:text class="text-xs text-theme-on-surface-variant">
                        {{ $expense->expense_date?->format('d/m/Y') ?? 'Sin fecha' }}
                    </native:text>

                    <native:row class="w-full items-center gap-2 pt-1">
                        <native:text class="flex-1 text-base font-semibold text-theme-on-surface">
                            {{ Money::format((string) ($expense->actual_amount ?? $expense->planned_amount)) }}
                        </native:text>

                        @if ($operable && $expense->status->value === 'pending')
                            <native:button size="sm" variant="secondary" @press="markPaid({{ $expense->id }})">
                                Marcar pagado
                            </native:button>
                        @endif

                        @if ($operable)
                            <native:button size="sm" variant="destructive" @press="deleteExpense({{ $expense->id }})">
                                Eliminar
                            </native:button>
                        @endif
                    </native:row>
                </native:column>
            @empty
                <native:column class="w-full items-center gap-2 rounded-lg bg-theme-surface p-5">
                    <native:text class="text-sm text-theme-on-surface-variant">
                        Sin gastos variables en este período.
                    </native:text>
                </native:column>
            @endforelse
        </native:column>
    </native:scroll-view>
</native:column>

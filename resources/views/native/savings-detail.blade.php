@php
    use App\Support\Money;
@endphp

<native:top-bar title="{{ $goalName }}" subtitle="Detalle del ahorro" back />

<native:column class="w-full h-full bg-theme-background">
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full gap-3 p-4">
            <native:column class="w-full gap-2 rounded-2xl bg-theme-primary p-4">
                <native:text class="text-xs uppercase text-theme-on-primary/80">Saldo acumulado</native:text>
                <native:text class="text-3xl font-bold text-theme-on-primary">
                    {{ Money::format($balance) }}
                </native:text>
                <native:text class="text-xs text-theme-on-primary/80">
                    Meta mensual: {{ Money::format($monthlyGoal) }} · Meta final: {{ Money::format($finalGoal) }}
                </native:text>

                @if ((float) $finalGoal > 0)
                    <native:progress-bar :value="$progress" />
                    <native:text class="text-xs text-theme-on-primary/80">
                        {{ round($progress * 100) }}% de la meta final
                    </native:text>
                @endif
            </native:column>

            @if ($error !== '')
                <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif
            @if ($success !== '')
                <native:text class="text-sm text-theme-success">{{ $success }}</native:text>
            @endif

            <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                <native:text class="text-sm font-semibold text-theme-on-surface">Registrar movimiento</native:text>

                <native:select
                    label="Tipo"
                    :options="['Aportación', 'Retiro', 'Saldo inicial']"
                    :value="$typeLabel"
                    @change="chooseType" />

                <native:outlined-text-input
                    label="Importe"
                    placeholder="0,00"
                    keyboard="decimal"
                    native:model.debounce.300ms="amount" />

                <native:date-picker label="Fecha" mode="date" locale="es" native:model="movementDate" />

                <native:outlined-text-input
                    label="Descripción"
                    placeholder="Opcional"
                    native:model.debounce.300ms="description" />

                <native:button @press="register">Registrar movimiento</native:button>
            </native:column>

            <native:text class="text-sm font-semibold text-theme-on-surface">Movimientos</native:text>

            @forelse ($movements as $movement)
                <native:column class="w-full gap-1 rounded-lg bg-theme-surface p-3">
                    <native:row class="w-full items-center gap-2">
                        <native:text class="flex-1 text-sm font-semibold text-theme-on-surface">
                            {{ $movement->description ?: '—' }}
                        </native:text>
                        <native:text class="text-base font-semibold {{ $movement->type->sign() > 0 ? 'text-theme-success' : 'text-theme-destructive' }}">
                            {{ $movement->type->sign() > 0 ? '+' : '−' }} {{ Money::format((string) $movement->amount) }}
                        </native:text>
                    </native:row>
                    <native:text class="text-xs text-theme-on-surface-variant">
                        {{ $movement->type->label() }} · {{ $movement->period->label() }} · {{ $movement->movement_date->format('d/m/Y') }}
                    </native:text>
                </native:column>
            @empty
                <native:text class="text-xs text-theme-on-surface-variant">Sin movimientos.</native:text>
            @endforelse
        </native:column>
    </native:scroll-view>
</native:column>

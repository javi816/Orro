@php
    use App\Support\Money;

    $operable = ! $period->isClosed();
    $reference = $period->year * 100 + $period->month;
@endphp

<native:top-bar title="Préstamos" subtitle="Préstamos personales y reposiciones de {{ $period->label() }}" back />

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
                        Período cerrado: no se pueden registrar préstamos ni reposiciones en él.
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
                    <native:text class="text-xs text-theme-on-surface-variant">Disponible para prestar</native:text>
                    <native:text class="text-base font-semibold text-theme-success">
                        {{ Money::format($loanable) }}
                    </native:text>
                </native:column>
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Deuda pendiente</native:text>
                    <native:text class="text-base font-semibold text-theme-accent">
                        {{ Money::format($summary['loans_outstanding']) }}
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">
                        {{ $pendingLoans->count() }} por reponer
                    </native:text>
                </native:column>
            </native:row>

            <native:row class="w-full gap-2">
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Dinero disponible</native:text>
                    <native:text class="text-base font-semibold text-theme-on-surface">
                        {{ Money::format($summary['available_money']) }}
                    </native:text>
                </native:column>
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Préstamos del mes</native:text>
                    <native:text class="text-base font-semibold text-theme-on-surface">
                        {{ Money::format($summary['loans_received']) }}
                    </native:text>
                </native:column>
            </native:row>

            @if ($operable)
                <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                    <native:text class="text-sm font-semibold text-theme-on-surface">
                        Nuevo préstamo
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">
                        El vencimiento se asigna al siguiente período.
                    </native:text>

                    <native:outlined-text-input
                        label="Importe"
                        placeholder="0,00"
                        keyboard="decimal"
                        native:model.debounce.300ms="amount" />

                    <native:outlined-text-input
                        label="Descripción"
                        placeholder="Ej. Arreglo del vehículo"
                        native:model.debounce.300ms="description" />

                    <native:text class="text-xs text-theme-on-surface-variant">
                        @if ($nextDuePeriod)
                            Vence en {{ $nextDuePeriod->label() }}. Disponible: {{ Money::format($loanable) }}.
                        @else
                            Disponible: {{ Money::format($loanable) }}.
                        @endif
                    </native:text>

                    <native:button @press="create">
                        Registrar préstamo
                    </native:button>

                    <native:text class="text-[11px] text-theme-on-surface-variant">
                        Al crearlo se registra automáticamente un retiro del ahorro por el mismo importe.
                    </native:text>
                </native:column>
            @else
                <native:text class="text-sm text-theme-on-surface-variant">
                    Selecciona un período abierto para registrar un préstamo.
                </native:text>
            @endif

            <native:text class="text-sm font-semibold text-theme-on-surface">
                Obligaciones pendientes
            </native:text>

            @forelse ($pendingLoans as $item)
                @php
                    $loan = $item['loan'];
                    $isOverdue = ($loan->duePeriod->year * 100 + $loan->duePeriod->month) < $reference;
                @endphp
                <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                    <native:row class="w-full items-center gap-2">
                        <native:text class="flex-1 text-sm font-semibold text-theme-on-surface">
                            {{ $loan->description }}
                        </native:text>
                        <native:badge
                            :label="$isOverdue ? 'Vencido' : 'Pendiente'"
                            :variant="$isOverdue ? 'destructive' : 'accent'" />
                    </native:row>

                    <native:text class="text-xs text-theme-on-surface-variant">
                        Origen: {{ $loan->originPeriod->label() }} · Vence: {{ $loan->duePeriod->label() }}
                    </native:text>

                    <native:row class="w-full gap-2">
                        <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface-variant p-2">
                            <native:text class="text-[10px] uppercase text-theme-on-surface-variant">Préstamo</native:text>
                            <native:text class="text-sm font-medium text-theme-on-surface">
                                {{ Money::format((string) $loan->amount) }}
                            </native:text>
                        </native:column>
                        <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface-variant p-2">
                            <native:text class="text-[10px] uppercase text-theme-on-surface-variant">Pendiente</native:text>
                            <native:text class="text-sm font-semibold {{ $isOverdue ? 'text-theme-destructive' : 'text-theme-accent' }}">
                                {{ Money::format($item['outstanding']) }}
                            </native:text>
                        </native:column>
                        <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface-variant p-2">
                            <native:text class="text-[10px] uppercase text-theme-on-surface-variant">Repuesto</native:text>
                            <native:text class="text-sm font-medium text-theme-success">
                                {{ Money::format(Money::sub((string) $loan->amount, $item['outstanding'])) }}
                            </native:text>
                        </native:column>
                    </native:row>

                    @if ($operable)
                        <native:outlined-text-input
                            label="Reponer"
                            placeholder="{{ $item['outstanding'] }}"
                            keyboard="decimal"
                            native:model.debounce.300ms="repayAmount" />

                        <native:button size="sm" variant="secondary" @press="repay({{ $loan->id }})">
                            Registrar reposición
                        </native:button>
                    @endif
                </native:column>
            @empty
                <native:column class="w-full items-center gap-2 rounded-lg bg-theme-surface p-5">
                    <native:icon name="check" :size="28" class="text-theme-success" />
                    <native:text class="text-sm text-theme-on-surface-variant">
                        Sin préstamos pendientes.
                    </native:text>
                </native:column>
            @endforelse

            <native:text class="text-sm font-semibold text-theme-on-surface">
                Historial de préstamos
            </native:text>

            @forelse ($loans as $item)
                @php
                    $loan = $item['loan'];
                    $isOverdue = ($loan->duePeriod->year * 100 + $loan->duePeriod->month) < $reference && Money::isPositive($item['outstanding']);
                    $isRepaid = ! Money::isPositive($item['outstanding']);
                @endphp
                <native:column class="w-full gap-1 rounded-lg bg-theme-surface p-3">
                    <native:row class="w-full items-center gap-2">
                        <native:text class="flex-1 text-sm font-semibold text-theme-on-surface">
                            {{ $loan->description }}
                        </native:text>
                        <native:badge
                            :label="$isRepaid ? 'Repuesto' : ($isOverdue ? 'Vencido' : 'Pendiente')"
                            :variant="$isRepaid ? 'success' : ($isOverdue ? 'destructive' : 'accent')" />
                    </native:row>

                    <native:text class="text-xs text-theme-on-surface-variant">
                        Origen: {{ $loan->originPeriod->shortLabel() }} · Vence: {{ $loan->duePeriod->shortLabel() }}
                    </native:text>

                    <native:row class="w-full items-center gap-2 pt-1">
                        <native:text class="flex-1 text-sm text-theme-on-surface">
                            Importe: {{ Money::format((string) $loan->amount) }}
                        </native:text>
                        <native:text class="text-sm font-semibold {{ $isRepaid ? 'text-theme-success' : 'text-theme-accent' }}">
                            Pendiente: {{ Money::format($item['outstanding']) }}
                        </native:text>
                    </native:row>
                </native:column>
            @empty
                <native:column class="w-full items-center gap-2 rounded-lg bg-theme-surface p-5">
                    <native:text class="text-sm text-theme-on-surface-variant">
                        Aún no hay préstamos.
                    </native:text>
                </native:column>
            @endforelse
        </native:column>
    </native:scroll-view>
</native:column>

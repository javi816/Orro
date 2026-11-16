@php
    use App\Support\Money;

    $operable = ! $period->isClosed();
    $receivedCount = $incomes->filter(fn ($income) => $income->status->value === 'received')->count();
@endphp

<native:top-bar title="Ingresos" subtitle="Recibidos y esperados de {{ $period->label() }}" back />

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
                        Período cerrado: los ingresos son de solo lectura.
                    </native:text>
                </native:row>
            @endif

            @if ($error !== '')
                <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif

            @if ($success !== '')
                <native:text class="text-sm text-theme-success">{{ $success }}</native:text>
            @endif

            <native:row class="w-full gap-3">
                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Recibidos</native:text>
                    <native:text class="text-lg font-semibold text-theme-success">
                        {{ Money::format($summary['income_received']) }}
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">{{ $receivedCount }} ingreso(s)</native:text>
                </native:column>

                <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                    <native:text class="text-xs text-theme-on-surface-variant">Pendientes</native:text>
                    <native:text class="text-lg font-semibold text-theme-accent">
                        {{ Money::format($summary['income_pending']) }}
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">{{ $summary['income_pending_count'] }} por recibir</native:text>
                </native:column>
            </native:row>

            <native:column class="w-full gap-1 rounded-lg bg-theme-surface p-3">
                <native:text class="text-xs text-theme-on-surface-variant">Total esperado</native:text>
                <native:text class="text-2xl font-bold text-theme-primary">
                    {{ Money::format($summary['income_expected']) }}
                </native:text>
                <native:text class="text-xs text-theme-on-surface-variant">
                    Suma de todos los ingresos del período
                </native:text>
            </native:column>

            @if ($operable)
                <native:button variant="secondary" icon="plus" @press="generate">
                    Generar faltantes
                </native:button>
            @endif

            <native:text class="text-sm font-semibold text-theme-on-surface">Ingresos del período</native:text>

            @forelse ($incomes as $income)
                <native:column class="w-full gap-1 rounded-lg bg-theme-surface p-3">
                    <native:row class="w-full items-center gap-2">
                        <native:text class="flex-1 text-sm font-semibold text-theme-on-surface">
                            {{ $income->description }}
                        </native:text>
                        <native:badge
                            :label="$income->status->label()"
                            :variant="$income->status->value === 'received' ? 'success' : 'accent'" />
                    </native:row>

                    <native:text class="text-xs text-theme-on-surface-variant">
                        N.º {{ $income->payment_number }} · {{ $income->source?->description ?? 'Ingreso del período' }}
                    </native:text>

                    <native:row class="w-full items-center gap-2 pt-1">
                        <native:text class="flex-1 text-base font-semibold text-theme-on-surface">
                            {{ Money::format((string) $income->amount) }}
                        </native:text>

                        @if ($operable && $income->status->value === 'pending')
                            <native:button size="sm" variant="secondary" @press="markReceived({{ $income->id }})">
                                Marcar recibido
                            </native:button>
                        @else
                            <native:text class="text-xs text-theme-on-surface-variant">
                                {{ $income->received_at ? $income->received_at->format('d/m/Y') : '—' }}
                            </native:text>
                        @endif
                    </native:row>
                </native:column>
            @empty
                <native:column class="w-full items-center gap-2 rounded-lg bg-theme-surface p-5">
                    <native:icon name="receipt" :size="28" class="text-theme-on-surface-variant" />
                    <native:text class="text-sm text-theme-on-surface-variant">
                        No hay ingresos en este período.
                    </native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">
                        Configura fuentes de ingreso y pulsa «Generar faltantes».
                    </native:text>
                </native:column>
            @endforelse

            <native:text class="text-xs text-theme-on-surface-variant">
                Selecciona otro período para consultar su historial de ingresos.
            </native:text>
        </native:column>
    </native:scroll-view>
</native:column>



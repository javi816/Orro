@php
    use App\Support\Money;

    $operable = ! $period->isClosed();
@endphp

<native:top-bar title="Cierre de {{ $period->label() }}" subtitle="Resumen financiero y condiciones del mes" back />

<native:column class="w-full h-full bg-theme-background">
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full gap-3 p-4">
            <native:select
                label="Período"
                :options="$periodLabels"
                :value="$periodLabel"
                @change="choosePeriod" />

            @if ($error !== '')
                <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif

            @if ($success !== '')
                <native:text class="text-sm text-theme-success">{{ $success }}</native:text>
            @endif

            @if ($period->isClosed())
                <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                    <native:row class="w-full items-center gap-2">
                        <native:icon name="lock" :size="18" class="text-theme-on-surface-variant" />
                        <native:text class="flex-1 text-sm text-theme-on-surface-variant">
                            Cerrado el {{ $period->closed_at?->format('d/m/Y H:i') }}. El período es de solo lectura.
                        </native:text>
                    </native:row>
                </native:column>

                <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                    <native:text class="text-sm font-semibold text-theme-on-surface">
                        Resumen guardado al cierre
                    </native:text>
                    @foreach ($period->closing_summary ?? [] as $key => $value)
                        <native:row class="w-full justify-between">
                            <native:text class="text-xs text-theme-on-surface-variant">{{ str_replace('_', ' ', ucfirst($key)) }}</native:text>
                            <native:text class="text-xs font-semibold text-theme-on-surface">{{ $value }}</native:text>
                        </native:row>
                    @endforeach
                </native:column>
            @else
                <native:row class="w-full gap-2">
                    <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                        <native:text class="text-xs text-theme-on-surface-variant">Ingresos recibidos</native:text>
                        <native:text class="text-base font-semibold text-theme-success">
                            {{ Money::format($summary['income_received']) }}
                        </native:text>
                    </native:column>
                    <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                        <native:text class="text-xs text-theme-on-surface-variant">Gastos pagados</native:text>
                        <native:text class="text-base font-semibold text-theme-on-surface">
                            {{ Money::format($summary['expenses_paid']) }}
                        </native:text>
                    </native:column>
                </native:row>

                <native:row class="w-full gap-2">
                    <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                        <native:text class="text-xs text-theme-on-surface-variant">Ahorro del mes</native:text>
                        <native:text class="text-base font-semibold text-theme-on-surface">
                            {{ Money::format($summary['savings_deposited']) }}
                        </native:text>
                        <native:text class="text-xs text-theme-on-surface-variant">
                            Meta: {{ Money::format($summary['savings_goal']) }}
                        </native:text>
                    </native:column>
                    <native:column class="flex-1 gap-1 rounded-lg bg-theme-surface p-3">
                        <native:text class="text-xs text-theme-on-surface-variant">Dinero disponible</native:text>
                        <native:text class="text-base font-semibold {{ Money::compare($summary['available_money'], '0.00') < 0 ? 'text-theme-destructive' : 'text-theme-on-surface' }}">
                            {{ Money::format($summary['available_money']) }}
                        </native:text>
                    </native:column>
                </native:row>

                <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                    <native:text class="text-sm font-semibold text-theme-on-surface">
                        Condiciones para cerrar
                    </native:text>

                    @foreach ($readiness['checks'] as $check)
                        <native:row class="w-full items-start gap-2">
                            <native:icon
                                :name="$check['ok'] ? 'check_circle' : 'warning'"
                                :size="18"
                                class="{{ $check['ok'] ? 'text-theme-success' : 'text-theme-accent' }}" />
                            <native:column class="flex-1 gap-0">
                                <native:text class="text-sm font-medium text-theme-on-surface">
                                    {{ $check['label'] }}
                                </native:text>
                                <native:text class="text-xs text-theme-on-surface-variant">
                                    {{ $check['detail'] }}
                                </native:text>
                            </native:column>
                        </native:row>
                    @endforeach

                    @foreach ($readiness['warnings'] as $warning)
                        <native:row class="w-full items-start gap-2 rounded-lg bg-theme-surface-variant p-2">
                            <native:icon name="warning" :size="16" class="text-theme-accent" />
                            <native:text class="flex-1 text-xs text-theme-on-surface-variant">
                                {{ $warning }}
                            </native:text>
                        </native:row>
                    @endforeach

                    <native:text class="text-sm font-semibold {{ $readiness['can_close'] ? 'text-theme-success' : 'text-theme-accent' }}">
                        {{ $readiness['can_close'] ? 'Todo listo para cerrar el mes.' : 'Aún faltan condiciones por cumplir.' }}
                    </native:text>
                </native:column>

                <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                    <native:text class="text-sm font-semibold text-theme-on-surface">
                        Resumen financiero del cierre
                    </native:text>

                    @foreach ($closingPreview as $key => $value)
                        <native:row class="w-full justify-between">
                            <native:text class="text-xs text-theme-on-surface-variant">
                                {{ str_replace('_', ' ', ucfirst($key)) }}
                            </native:text>
                            <native:text class="text-xs font-semibold text-theme-on-surface">
                                {{ $value }}
                            </native:text>
                        </native:row>
                    @endforeach

                    @if ($readiness['can_close'])
                        <native:button variant="destructive" @press="close" class="mt-2">
                            Cerrar mes
                        </native:button>
                        <native:text class="text-[11px] text-theme-on-surface-variant">
                            El cierre guarda el resumen y bloquea ingresos, gastos, ahorros y préstamos del período. No se puede reabrir.
                        </native:text>
                    @endif
                </native:column>
            @endif
        </native:column>
    </native:scroll-view>
</native:column>

<native:top-bar title="Ahorros" subtitle="Metas y saldo del ahorro" />

<native:column class="w-full h-full bg-theme-background">
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full gap-3 p-4">
            <native:button variant="secondary" icon="plus" @navigate='/configuracion'>
                Gestionar metas en Configuración
            </native:button>

            <native:text class="text-sm font-semibold text-theme-on-surface">Metas de ahorro</native:text>

            <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4" @press="openGoal(null)">
                <native:row class="w-full items-center gap-2">
                    <native:icon name="savings" :size="24" class="text-theme-primary" />
                    <native:column class="flex-1 gap-0">
                        <native:text class="text-sm font-semibold text-theme-on-surface">Ahorro general</native:text>
                        <native:text class="text-xs text-theme-on-surface-variant">
                            Sin meta asignada
                        </native:text>
                    </native:column>
                    <native:text class="text-base font-bold text-theme-primary">
                        {{ \App\Support\Money::format($generalBalance) }}
                    </native:text>
                </native:row>
            </native:column>

            @forelse ($goals as $goal)
                <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4" @press="openGoal({{ $goal->id }})">
                    <native:row class="w-full items-center gap-2">
                        <native:icon name="flag" :size="24" class="text-theme-accent" />
                        <native:column class="flex-1 gap-0">
                            <native:text class="text-sm font-semibold text-theme-on-surface">
                                {{ $goal->name }}
                            </native:text>
                            <native:text class="text-xs text-theme-on-surface-variant">
                                Meta mensual: {{ \App\Support\Money::format((string) $goal->monthly_goal) }}
                            </native:text>
                        </native:column>
                        <native:text class="text-base font-bold text-theme-primary">
                            {{ \App\Support\Money::format($goalBalances[$goal->id] ?? '0.00') }}
                        </native:text>
                    </native:row>
                </native:column>
            @empty
                <native:text class="text-xs text-theme-on-surface-variant">
                    Aún no tienes metas. Créalas en Configuración.
                </native:text>
            @endforelse
        </native:column>
    </native:scroll-view>
</native:column>

@include('native.partials.tabs')

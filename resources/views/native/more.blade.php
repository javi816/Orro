<native:top-bar title="Más" subtitle="Herramientas y configuración" />

<native:column class="w-full h-full bg-theme-background">
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full gap-2 p-4">
            <native:row class="w-full items-center gap-3 rounded-xl bg-theme-surface p-4" @navigate='/ingresos'>
                <native:icon name="receipt" :size="22" class="text-theme-primary" />
                <native:column class="flex-1 gap-1">
                    <native:text class="text-sm font-semibold text-theme-on-surface">Ingresos</native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">Cobros del período</native:text>
                </native:column>
            </native:row>

            <native:row class="w-full items-center gap-3 rounded-xl bg-theme-surface p-4" @navigate='/presupuesto'>
                <native:icon name="wallet" :size="22" class="text-theme-primary" />
                <native:column class="flex-1 gap-1">
                    <native:text class="text-sm font-semibold text-theme-on-surface">Presupuesto</native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">Define tu presupuesto variable</native:text>
                </native:column>
            </native:row>

            <native:row class="w-full items-center gap-3 rounded-xl bg-theme-surface p-4" @navigate='/periodos'>
                <native:icon name="history" :size="22" class="text-theme-primary" />
                <native:column class="flex-1 gap-1">
                    <native:text class="text-sm font-semibold text-theme-on-surface">Historial de períodos</native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">Meses anteriores y cierres</native:text>
                </native:column>
            </native:row>

            <native:row class="w-full items-center gap-3 rounded-xl bg-theme-surface p-4" @navigate='/configuracion'>
                <native:icon name="settings" :size="22" class="text-theme-primary" />
                <native:column class="flex-1 gap-1">
                    <native:text class="text-sm font-semibold text-theme-on-surface">Configuración</native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">Fuentes de ingreso y gastos fijos</native:text>
                </native:column>
            </native:row>

            <native:column class="w-full gap-1 rounded-lg bg-theme-surface p-4 mt-2">
                <native:text class="text-sm font-semibold text-theme-on-surface">
                    Seguridad local
                </native:text>
                <native:text class="text-xs text-theme-on-surface-variant">
                    Tus datos se guardan cifrados en este dispositivo. Bloquea la app para volver a pedir el PIN.
                </native:text>
                <native:button variant="destructive" icon="lock" @press="lock" class="mt-2">
                    Bloquear aplicación
                </native:button>
            </native:column>

            <native:text class="text-xs text-theme-on-surface-variant mt-2">
                Orro · Finanzas personales 100% en tu dispositivo · SQLite local
            </native:text>
        </native:column>
    </native:scroll-view>
</native:column>

@include('native.partials.tabs')

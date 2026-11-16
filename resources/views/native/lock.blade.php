<native:column class="w-full h-full items-center justify-center gap-4 bg-theme-background p-6 safe-area">
    <native:text class="text-3xl font-bold text-theme-primary">Orro</native:text>
    <native:text class="text-sm text-theme-on-surface-variant">Finanzas personales, 100% en tu dispositivo</native:text>

    <native:column class="mt-4 w-full gap-3 rounded-lg bg-theme-surface p-5">
        <native:text class="text-lg font-semibold text-theme-on-surface">
            {{ $creating ? 'Crea tu PIN de acceso' : 'Introduce tu PIN' }}
        </native:text>

        <native:text class="text-xs text-theme-on-surface-variant">
            {{ $creating
                ? 'El PIN protege tus datos locales. Se guarda cifrado en este dispositivo.'
                : 'Desbloquea la aplicación para consultar tus finanzas.' }}
        </native:text>

        <native:outlined-text-input
            label="PIN"
            placeholder="4 a 8 dígitos"
            keyboard="number"
            secure
            :max-length="8"
            native:model.debounce.300ms="pin"
            @submit="submit" />

        @if ($creating)
            <native:outlined-text-input
                label="Repite el PIN"
                placeholder="4 a 8 dígitos"
                keyboard="number"
                secure
                :max-length="8"
                native:model.debounce.300ms="pinConfirmation" />
        @endif

        @if ($error !== '')
            <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button @press="submit" class="w-full">
            {{ $creating ? 'Crear PIN y entrar' : 'Desbloquear' }}
        </native:button>
    </native:column>

    <native:text class="text-xs text-theme-on-surface-variant">Sin conexión · SQLite local</native:text>
</native:column>

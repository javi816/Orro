@php
    use App\Support\Money;
@endphp

<native:top-bar title="Historial de períodos" subtitle="Consulta meses anteriores y el resultado de cada cierre" back />

<native:column class="w-full h-full bg-theme-background">
    @if ($periods->isEmpty())
        <native:column class="w-full h-full items-center justify-center gap-2 p-6">
            <native:text class="text-sm text-theme-on-surface-variant">
                Aún no hay períodos.
            </native:text>
            <native:text class="text-xs text-theme-on-surface-variant">
                El período actual se crea al entrar al dashboard.
            </native:text>
        </native:column>
    @else
        <native:list class="w-full flex-1" separator>
            @foreach ($periods as $period)
                <native:list-item
                    native:key="period-{{ $period->id }}"
                    :headline="$period->label()"
                    :supporting="$period->isClosed() ? 'Cerrado el '.$period->closed_at?->format('d/m/Y') : 'Abierto'"
                    :trailing-text="Money::format((string) $summaries[$period->id]['income_received']).' · '.Money::format((string) $summaries[$period->id]['expenses_paid'])"
                    @press="open({{ $period->id }})" />
            @endforeach
        </native:list>
    @endif
</native:column>

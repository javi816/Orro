<x-layouts.app title="Historial de períodos">
    <x-ui.page-header title="Historial de períodos" subtitle="Consulta meses anteriores y el resultado de cada cierre" />

    @if ($periods->isEmpty())
        <x-ui.card>
            <x-ui.empty title="Aún no hay períodos" description="El período actual se crea al entrar al dashboard." icon="history" />
        </x-ui.card>
    @else
        <x-ui.card :padding="'p-0'">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Período</th>
                            <th class="px-3 py-3 font-medium">Estado</th>
                            <th class="px-3 py-3 text-right font-medium">Ingresos</th>
                            <th class="px-3 py-3 text-right font-medium">Gastos</th>
                            <th class="px-3 py-3 text-right font-medium">Ahorro aportado</th>
                            <th class="px-3 py-3 text-right font-medium">Deuda pendiente</th>
                            <th class="px-3 py-3 text-right font-medium">Disponible</th>
                            <th class="px-5 py-3 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($periods as $item)
                            @php
                                $summary = $summaries[$item->id] ?? [];
                                $pendingDebt = $summary['pending_debt'] ?? $summary['loans_outstanding'] ?? '0.00';
                                $isCurrent = $item->year === now()->year && $item->month === now()->month;
                            @endphp
                            <tr class="{{ $isCurrent ? 'bg-emerald-50/40' : 'hover:bg-slate-50/60' }}">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-slate-800">{{ $item->label() }}</span>
                                        @if ($isCurrent)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Actual</span>
                                        @endif
                                    </div>
                                    @if ($item->closed_at)
                                        <p class="text-xs text-slate-400">Cerrado el {{ $item->closed_at->format('d/m/Y H:i') }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3"><x-ui.badge :status="$item->status" /></td>
                                <td class="px-3 py-3 text-right text-slate-700">@money($summary['income_received'] ?? '0.00')</td>
                                <td class="px-3 py-3 text-right text-slate-700">@money($summary['expenses_paid'] ?? '0.00')</td>
                                <td class="px-3 py-3 text-right text-slate-700">@money($summary['savings_deposited'] ?? '0.00')</td>
                                <td class="px-3 py-3 text-right {{ \App\Support\Money::compare((string) $pendingDebt, '0.00') > 0 ? 'text-amber-700' : 'text-slate-700' }}">@money($pendingDebt)</td>
                                <td class="px-3 py-3 text-right font-semibold {{ \App\Support\Money::compare((string) ($summary['available_money'] ?? '0.00'), '0.00') < 0 ? 'text-rose-600' : 'text-emerald-700' }}">@money($summary['available_money'] ?? '0.00')</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('periods.show', $item) }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <p class="mt-4 text-xs text-slate-400">
        Los períodos cerrados conservan su resumen financiero y no admiten modificaciones.
    </p>
</x-layouts.app>

@php
    $operable = ! $period->isClosed();
@endphp

<x-layouts.app title="Ahorros">
    <x-ui.page-header title="Ahorros" subtitle="Saldo real y movimientos del ahorro">
        <x-slot:actions>
            <x-ui.period-select :periods="$periods" :selected="$period" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($period->isClosed())
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            <x-ui.icon name="lock" class="h-4 w-4 text-slate-400" />
            Período cerrado: no se pueden registrar movimientos en él.
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="relative overflow-hidden rounded-3xl bg-slate-950 p-6 shadow-xl shadow-slate-900/10 lg:col-span-2">
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-sky-500/20 blur-3xl"></div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-300/80">Saldo acumulado del ahorro</p>
                <p class="mt-3 text-4xl font-semibold tracking-tight text-white">@money($balance)</p>
                <p class="mt-2 text-sm text-slate-400">Saldo inicial + aportaciones + reposiciones − retiros.</p>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-white/5 p-3 ring-1 ring-inset ring-white/10">
                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Aportado este mes</p>
                        <p class="mt-1 text-sm font-semibold text-emerald-300">@money($summary['savings_deposited'])</p>
                    </div>
                    <div class="rounded-xl bg-white/5 p-3 ring-1 ring-inset ring-white/10">
                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Repuesto este mes</p>
                        <p class="mt-1 text-sm font-semibold text-sky-300">@money($summary['savings_repaid'])</p>
                    </div>
                    <div class="rounded-xl bg-white/5 p-3 ring-1 ring-inset ring-white/10">
                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Mínimo protegido</p>
                        <p class="mt-1 text-sm font-semibold text-white">@money($settings->minimum_savings_balance)</p>
                    </div>
                </div>
            </div>
        </div>

        <x-ui.card title="Registrar movimiento" description="El saldo se recalcula desde el historial.">
            @if ($operable)
                <form method="POST" action="{{ route('savings.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="period_id" value="{{ $period->id }}">

                    <x-ui.field label="Tipo de movimiento" name="type">
                        <select name="type" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            @unless ($hasOpeningBalance)
                                <option value="opening_balance" @selected(old('type') === 'opening_balance')>Saldo inicial</option>
                            @endunless
                            <option value="deposit" @selected(old('type', 'deposit') === 'deposit')>Aportación</option>
                            <option value="withdrawal" @selected(old('type') === 'withdrawal')>Retiro</option>
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Importe" name="amount">
                        <x-ui.input name="amount" inputmode="decimal" value="{{ old('amount') }}" placeholder="0,00" required />
                    </x-ui.field>

                    <x-ui.field label="Fecha" name="movement_date">
                        <x-ui.input type="date" name="movement_date" value="{{ old('movement_date', now()->toDateString()) }}" />
                    </x-ui.field>

                    <x-ui.field label="Descripción" name="description">
                        <x-ui.input name="description" value="{{ old('description') }}" placeholder="Opcional" />
                    </x-ui.field>

                    <x-ui.button type="submit" class="w-full">Registrar movimiento</x-ui.button>
                </form>
            @else
                <p class="text-sm text-slate-500">El período está cerrado. Selecciona un período abierto para registrar movimientos.</p>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card class="mt-5" title="Historial de movimientos" description="Todos los períodos" :padding="'p-0'">
        @if ($movements->isEmpty())
            <div class="p-5">
                <x-ui.empty title="Aún no hay movimientos" description="Registra el saldo inicial o una aportación para comenzar." icon="savings" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Fecha</th>
                            <th class="px-3 py-3 font-medium">Período</th>
                            <th class="px-3 py-3 font-medium">Tipo</th>
                            <th class="px-3 py-3 font-medium">Descripción</th>
                            <th class="px-5 py-3 text-right font-medium">Importe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($movements as $movement)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3 text-slate-500">{{ $movement->movement_date->format('d/m/Y') }}</td>
                                <td class="px-3 py-3 text-slate-500">{{ $movement->period->label() }}</td>
                                <td class="px-3 py-3"><x-ui.badge :status="$movement->type" /></td>
                                <td class="px-3 py-3">
                                    <p class="text-slate-700">{{ $movement->description ?: '—' }}</p>
                                    @if ($movement->loan)
                                        <p class="text-xs text-slate-400">Préstamo: {{ $movement->loan->description }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-semibold {{ $movement->type->sign() > 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $movement->type->sign() > 0 ? '+' : '−' }} @money($movement->amount)
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>

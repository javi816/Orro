<x-layouts.app title="Configuración">
    <x-ui.page-header title="Configuración" subtitle="Metas, fuentes de ingreso y gastos fijos recurrentes" />

    <div class="grid gap-5 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-1" title="Reglas generales" description="Aplican al cálculo de todos los períodos.">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-ui.field label="Meta mensual de ahorro" name="monthly_savings_goal" hint="Ahorro que debe separarse cada mes.">
                    <x-ui.input name="monthly_savings_goal" inputmode="decimal" value="{{ old('monthly_savings_goal', $settings->monthly_savings_goal) }}" required />
                </x-ui.field>

                <x-ui.field label="Saldo mínimo protegido" name="minimum_savings_balance" hint="No se presta por debajo de este saldo.">
                    <x-ui.input name="minimum_savings_balance" inputmode="decimal" value="{{ old('minimum_savings_balance', $settings->minimum_savings_balance) }}" required />
                </x-ui.field>

                <x-ui.button type="submit" class="w-full">Guardar configuración</x-ui.button>
            </form>

            <div class="mt-4 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-500">
                Cambiar estas reglas no altera los movimientos ni los cierres de períodos anteriores.
            </div>
        </x-ui.card>

        <div class="space-y-5 lg:col-span-2">
            <x-ui.card title="Fuentes de ingreso habituales" description="Se usan para generar los ingresos esperados de cada período nuevo.">
                @if ($incomeSources->isEmpty())
                    <x-ui.empty title="Sin fuentes de ingreso" description="Crea la primera fuente para generar ingresos esperados." icon="income" />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($incomeSources as $source)
                            <div class="py-3 first:pt-0 last:pb-0">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-medium text-slate-800">{{ $source->description }}</p>
                                            <x-ui.badge :status="$source->active ? 'active' : 'inactive'" />
                                        </div>
                                        <p class="mt-0.5 text-xs text-slate-400">
                                            @money($source->amount) · Pago N.º {{ $source->payment_number }}
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('settings.income-sources.toggle', $source) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold {{ $source->active ? 'text-rose-600 hover:text-rose-700' : 'text-emerald-700 hover:text-emerald-800' }}">
                                            {{ $source->active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>

                                <details class="mt-2">
                                    <summary class="cursor-pointer text-xs font-semibold text-slate-500 hover:text-slate-700">Editar</summary>
                                    <form method="POST" action="{{ route('settings.income-sources.update', $source) }}" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="active" value="0">
                                        <div class="sm:col-span-2">
                                            <x-ui.field label="Descripción" name="description">
                                                <x-ui.input name="description" value="{{ $source->description }}" required />
                                            </x-ui.field>
                                        </div>
                                        <x-ui.field label="Importe" name="amount">
                                            <x-ui.input name="amount" inputmode="decimal" value="{{ $source->amount }}" required />
                                        </x-ui.field>
                                        <x-ui.field label="N.º de pago" name="payment_number">
                                            <x-ui.input type="number" name="payment_number" min="1" max="31" value="{{ $source->payment_number }}" required />
                                        </x-ui.field>
                                        <label class="flex items-center gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="active" value="1" @checked($source->active) class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            Activa
                                        </label>
                                        <div class="flex items-end sm:col-span-2 lg:col-span-4">
                                            <x-ui.button type="submit" variant="secondary">Guardar fuente</x-ui.button>
                                        </div>
                                    </form>
                                </details>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.income-sources.store') }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                    @csrf
                    <input type="hidden" name="active" value="1">
                    <div class="sm:col-span-2">
                        <x-ui.field label="Nueva fuente" name="description">
                            <x-ui.input name="description" placeholder="Ej. Salario" required />
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Importe" name="amount">
                        <x-ui.input name="amount" inputmode="decimal" placeholder="0,00" required />
                    </x-ui.field>
                    <x-ui.field label="N.º de pago" name="payment_number">
                        <x-ui.input type="number" name="payment_number" min="1" max="31" value="1" required />
                    </x-ui.field>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <x-ui.button type="submit">
                            <x-ui.icon name="plus" class="h-4 w-4" />
                            Agregar fuente de ingreso
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Gastos fijos recurrentes" description="Se copian como gastos pendientes al iniciar cada período.">
                @if ($expenseTemplates->isEmpty())
                    <x-ui.empty title="Sin gastos fijos" description="Crea una plantilla para generar los gastos fijos del mes." icon="expense" />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($expenseTemplates as $template)
                            <div class="py-3 first:pt-0 last:pb-0">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-medium text-slate-800">{{ $template->description }}</p>
                                            <x-ui.badge :status="$template->active ? 'active' : 'inactive'" />
                                        </div>
                                        <p class="mt-0.5 text-xs text-slate-400">
                                            @money($template->amount) · Pago N.º {{ $template->payment_number }}
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('settings.expense-templates.toggle', $template) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold {{ $template->active ? 'text-rose-600 hover:text-rose-700' : 'text-emerald-700 hover:text-emerald-800' }}">
                                            {{ $template->active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>

                                <details class="mt-2">
                                    <summary class="cursor-pointer text-xs font-semibold text-slate-500 hover:text-slate-700">Editar</summary>
                                    <form method="POST" action="{{ route('settings.expense-templates.update', $template) }}" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="active" value="0">
                                        <div class="sm:col-span-2">
                                            <x-ui.field label="Descripción" name="description">
                                                <x-ui.input name="description" value="{{ $template->description }}" required />
                                            </x-ui.field>
                                        </div>
                                        <x-ui.field label="Importe" name="amount">
                                            <x-ui.input name="amount" inputmode="decimal" value="{{ $template->amount }}" required />
                                        </x-ui.field>
                                        <x-ui.field label="N.º de pago" name="payment_number">
                                            <x-ui.input type="number" name="payment_number" min="1" max="31" value="{{ $template->payment_number }}" required />
                                        </x-ui.field>
                                        <label class="flex items-center gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="active" value="1" @checked($template->active) class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            Activo
                                        </label>
                                        <div class="flex items-end sm:col-span-2 lg:col-span-4">
                                            <x-ui.button type="submit" variant="secondary">Guardar gasto fijo</x-ui.button>
                                        </div>
                                    </form>
                                </details>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.expense-templates.store') }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                    @csrf
                    <input type="hidden" name="active" value="1">
                    <div class="sm:col-span-2">
                        <x-ui.field label="Nuevo gasto fijo" name="description">
                            <x-ui.input name="description" placeholder="Ej. Arriendo" required />
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Importe" name="amount">
                        <x-ui.input name="amount" inputmode="decimal" placeholder="0,00" required />
                    </x-ui.field>
                    <x-ui.field label="N.º de pago" name="payment_number">
                        <x-ui.input type="number" name="payment_number" min="1" max="31" value="1" required />
                    </x-ui.field>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <x-ui.button type="submit">
                            <x-ui.icon name="plus" class="h-4 w-4" />
                            Agregar gasto fijo
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>

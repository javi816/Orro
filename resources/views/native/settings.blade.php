@php
    use App\Support\Money;
@endphp

<native:top-bar title="Configuración" subtitle="Metas, fuentes de ingreso y gastos fijos recurrentes" back />

<native:column class="w-full h-full bg-theme-background">
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full gap-3 p-4">
            @if ($error !== '')
                <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif

            @if ($success !== '')
                <native:text class="text-sm text-theme-success">{{ $success }}</native:text>
            @endif

            <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                <native:text class="text-sm font-semibold text-theme-on-surface">
                    Reglas generales
                </native:text>
                <native:text class="text-xs text-theme-on-surface-variant">
                    Aplican al cálculo de todos los períodos.
                </native:text>

                <native:outlined-text-input
                    label="Meta de ahorro (general)"
                    placeholder="0,00"
                    keyboard="decimal"
                    native:model.debounce.300ms="monthlySavingsGoal" />

                <native:outlined-text-input
                    label="Saldo mínimo protegido"
                    placeholder="0,00"
                    keyboard="decimal"
                    native:model.debounce.300ms="minimumSavingsBalance" />

                <native:button @press="saveSettings">
                    Guardar configuración
                </native:button>
            </native:column>

            <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                <native:text class="text-sm font-semibold text-theme-on-surface">
                    Metas de ahorro
                </native:text>
                <native:text class="text-xs text-theme-on-surface-variant">
                    Crea metas (moto, play, etc.).
                </native:text>

                <native:column class="w-full gap-2">
                    @foreach ($savingsGoals as $goal)
                        <native:row class="w-full items-center gap-2 rounded-lg bg-theme-surface-variant p-3">
                            <native:column class="flex-1 gap-0">
                                <native:text class="text-sm font-semibold text-theme-on-surface">
                                    {{ $goal->name }}
                                </native:text>
                                <native:text class="text-xs text-theme-on-surface-variant">
                                    Meta mensual: {{ Money::format((string) $goal->monthly_goal) }} · Meta final: {{ Money::format((string) $goal->final_goal) }}
                                </native:text>
                            </native:column>
                            <native:button size="sm" variant="destructive" @press="deleteGoal({{ $goal->id }})">
                                Eliminar
                            </native:button>
                        </native:row>
                    @endforeach
                </native:column>

                <native:column class="w-full gap-2 border-t border-theme-surface-variant pt-3">
                    <native:outlined-text-input
                        label="Nombre de la meta"
                        placeholder="Ej. Moto, Play"
                        native:model.debounce.300ms="goalName" />

                    <native:outlined-text-input
                        label="Meta mensual (opcional)"
                        placeholder="0,00"
                        keyboard="decimal"
                        native:model.debounce.300ms="goalMonthlyGoal" />

                    <native:outlined-text-input
                        label="Meta final (opcional)"
                        placeholder="0,00"
                        keyboard="decimal"
                        native:model.debounce.300ms="goalFinalGoal" />

                    <native:button @press="saveGoal">
                        Agregar meta
                    </native:button>
                </native:column>
            </native:column>

            <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                <native:text class="text-sm font-semibold text-theme-on-surface">
                    Fuentes de ingreso
                </native:text>

                @foreach ($incomeSources as $source)
                    <native:row class="w-full items-center gap-2 rounded-lg bg-theme-surface-variant p-3">
                        <native:column class="flex-1 gap-0">
                            <native:text class="text-sm font-semibold text-theme-on-surface">
                                {{ $source->description }}
                            </native:text>
                            <native:text class="text-xs text-theme-on-surface-variant">
                                {{ Money::format((string) $source->amount) }} · {{ $source->frequency === 'biweekly' ? 'Quincenal' : 'Mensual' }} · Pago N.º {{ $source->payment_number }}
                            </native:text>
                        </native:column>

                        <native:toggle
                            :value="$source->active"
                            @change="toggleSource({{ $source->id }})" />

                        <native:button size="sm" variant="ghost" icon="edit" @press="editSource({{ $source->id }})">
                            Editar
                        </native:button>
                    </native:row>
                @endforeach

                <native:column class="w-full gap-2 border-t border-theme-surface-variant pt-3">
                    <native:text class="text-xs font-semibold text-theme-on-surface-variant">
                        {{ $editingSourceId === null ? 'Nueva fuente de ingreso' : 'Editar fuente de ingreso' }}
                    </native:text>

                    <native:outlined-text-input
                        label="Descripción"
                        native:model.debounce.300ms="sourceDescription" />

                    <native:outlined-text-input
                        label="Importe"
                        placeholder="0,00"
                        keyboard="decimal"
                        native:model.debounce.300ms="sourceAmount" />

                    <native:outlined-text-input
                        label="N.º de pago"
                        placeholder="1"
                        keyboard="number"
                        native:model.debounce.300ms="sourcePaymentNumber" />

                    <native:select
                        label="Frecuencia"
                        :options="['Mensual', 'Quincenal']"
                        :value="$sourceFrequency === 'biweekly' ? 'Quincenal' : 'Mensual'"
                        @change="chooseSourceFrequency" />

                    <native:toggle label="Activa" native:model="sourceActive" />

                    <native:row class="w-full gap-2">
                        <native:button @press="saveSource">
                            {{ $editingSourceId === null ? 'Agregar fuente' : 'Guardar fuente' }}
                        </native:button>

                        @if ($editingSourceId !== null)
                            <native:button variant="ghost" @press="cancelSourceEdit">
                                Cancelar
                            </native:button>
                        @endif
                    </native:row>
                </native:column>
            </native:column>

            <native:column class="w-full gap-2 rounded-lg bg-theme-surface p-4">
                <native:text class="text-sm font-semibold text-theme-on-surface">
                    Gastos fijos recurrentes
                </native:text>

                @foreach ($expenseTemplates as $template)
                    <native:row class="w-full items-center gap-2 rounded-lg bg-theme-surface-variant p-3">
                        <native:column class="flex-1 gap-0">
                            <native:text class="text-sm font-semibold text-theme-on-surface">
                                {{ $template->description }}
                            </native:text>
                            <native:text class="text-xs text-theme-on-surface-variant">
                                {{ Money::format((string) $template->amount) }} · {{ $template->frequency === 'biweekly' ? 'Quincenal' : 'Mensual' }} · Pago N.º {{ $template->payment_number }}
                            </native:text>
                        </native:column>

                        <native:toggle
                            :value="$template->active"
                            @change="toggleTemplate({{ $template->id }})" />

                        <native:button size="sm" variant="ghost" icon="edit" @press="editTemplate({{ $template->id }})">
                            Editar
                        </native:button>
                    </native:row>
                @endforeach

                <native:column class="w-full gap-2 border-t border-theme-surface-variant pt-3">
                    <native:text class="text-xs font-semibold text-theme-on-surface-variant">
                        {{ $editingTemplateId === null ? 'Nuevo gasto fijo' : 'Editar gasto fijo' }}
                    </native:text>

                    <native:outlined-text-input
                        label="Descripción"
                        native:model.debounce.300ms="templateDescription" />

                    <native:outlined-text-input
                        label="Importe"
                        placeholder="0,00"
                        keyboard="decimal"
                        native:model.debounce.300ms="templateAmount" />

                    <native:outlined-text-input
                        label="N.º de pago"
                        placeholder="1"
                        keyboard="number"
                        native:model.debounce.300ms="templatePaymentNumber" />

                    <native:select
                        label="Frecuencia"
                        :options="['Mensual', 'Quincenal']"
                        :value="$templateFrequency === 'biweekly' ? 'Quincenal' : 'Mensual'"
                        @change="chooseTemplateFrequency" />

                    <native:toggle label="Activo" native:model="templateActive" />

                    <native:row class="w-full gap-2">
                        <native:button @press="saveTemplate">
                            {{ $editingTemplateId === null ? 'Agregar gasto fijo' : 'Guardar gasto fijo' }}
                        </native:button>

                        @if ($editingTemplateId !== null)
                            <native:button variant="ghost" @press="cancelTemplateEdit">
                                Cancelar
                            </native:button>
                        @endif
                    </native:row>
                </native:column>
            </native:column>
        </native:column>
    </native:scroll-view>
</native:column>

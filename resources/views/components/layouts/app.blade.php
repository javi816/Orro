@props(['title' => null])

@php
    $user = auth()->user();

    $navigation = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'pattern' => 'dashboard'],
        ['route' => 'budget.index', 'label' => 'Presupuesto', 'icon' => 'wallet', 'pattern' => 'budget.*'],
        ['route' => 'incomes.index', 'label' => 'Ingresos', 'icon' => 'income', 'pattern' => 'incomes.*'],
        ['route' => 'expenses.index', 'label' => 'Gastos', 'icon' => 'expense', 'pattern' => 'expenses.*'],
        ['route' => 'savings.index', 'label' => 'Ahorros', 'icon' => 'savings', 'pattern' => 'savings.*'],
        ['route' => 'loans.index', 'label' => 'Préstamos', 'icon' => 'loans', 'pattern' => 'loans.*'],
        ['route' => 'periods.index', 'label' => 'Historial', 'icon' => 'history', 'pattern' => 'periods.*'],
        ['route' => 'settings.edit', 'label' => 'Configuración', 'icon' => 'settings', 'pattern' => 'settings.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">
    <div class="min-h-screen lg:flex">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-800 bg-slate-950 lg:flex">
            <div class="flex h-20 items-center gap-3 px-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-lg font-bold text-slate-950 shadow-lg shadow-emerald-500/20">
                    O
                </span>
                <div>
                    <p class="text-base font-semibold tracking-tight text-white">{{ config('app.name') }}</p>
                    <p class="text-[11px] uppercase tracking-widest text-slate-500">Finanzas personales</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @foreach ($navigation as $item)
                    @php $active = request()->routeIs($item['pattern']); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                            'bg-emerald-500/10 text-emerald-300 ring-1 ring-inset ring-emerald-500/20' => $active,
                            'text-slate-400 hover:bg-slate-900 hover:text-slate-100' => ! $active,
                        ])
                        @if ($active) aria-current="page" @endif
                    >
                        <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-slate-800 p-3">
                <div class="flex items-center gap-3 rounded-xl bg-slate-900/70 p-3">
                    @if ($user?->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-slate-700" referrerpolicy="no-referrer">
                    @else
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-700 text-sm font-semibold text-white">
                            {{ mb_strtoupper(mb_substr($user?->name ?? 'U', 0, 1)) }}
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ $user?->name }}</p>
                        <p class="truncate text-[11px] text-slate-500">{{ $user?->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-rose-300" title="Cerrar sesión">
                            <x-ui.icon name="logout" class="h-4 w-4" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur lg:hidden">
                <div class="flex h-16 items-center justify-between px-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-base font-bold text-slate-950">O</span>
                        <span class="text-base font-semibold text-slate-900">{{ config('app.name') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($user?->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="" class="h-8 w-8 rounded-full object-cover ring-2 ring-slate-200" referrerpolicy="no-referrer">
                        @else
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-xs font-semibold text-white">
                                {{ mb_strtoupper(mb_substr($user?->name ?? 'U', 0, 1)) }}
                            </span>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-rose-600" title="Cerrar sesión">
                                <x-ui.icon name="logout" class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                </div>
                <nav class="flex gap-1 overflow-x-auto px-3 pb-3">
                    @foreach ($navigation as $item)
                        @php $active = request()->routeIs($item['pattern']); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium',
                                'bg-slate-900 text-white' => $active,
                                'text-slate-500 hover:bg-slate-100 hover:text-slate-800' => ! $active,
                            ])
                        >
                            <x-ui.icon :name="$item['icon']" class="h-4 w-4" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if (session('success'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <x-ui.icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <x-ui.icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <div class="flex items-start gap-3">
                            <x-ui.icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                            <ul class="list-inside list-disc space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="no-print border-t border-slate-200 px-4 py-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                {{ config('app.name') }} · Datos guardados localmente en SQLite
            </footer>
        </div>
    </div>
</body>
</html>

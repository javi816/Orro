<?php

use App\NativeComponents\BudgetScreen;
use App\NativeComponents\DashboardScreen;
use App\NativeComponents\ExpensesScreen;
use App\NativeComponents\IncomesScreen;
use App\NativeComponents\LockScreen;
use App\NativeComponents\LoansScreen;
use App\NativeComponents\MoreScreen;
use App\NativeComponents\PeriodDetailScreen;
use App\NativeComponents\PeriodsScreen;
use App\NativeComponents\SavingsDetailScreen;
use App\NativeComponents\SavingsScreen;
use App\NativeComponents\SettingsScreen;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas nativas (Android)
|--------------------------------------------------------------------------
|
| NativePHP carga este archivo solo en contextos nativos (dispositivo,
| Jump, comandos native:* y pruebas), por lo que la web nunca las expone.
| El desbloqueo local con PIN protege todas las pantallas salvo /lock.
|
*/

Route::native('/lock', LockScreen::class)->name('mobile.lock');

Route::middleware('mobile.auth')->group(function () {
    Route::native('/', DashboardScreen::class)->name('mobile.dashboard');
    Route::native('/ingresos', IncomesScreen::class)->name('mobile.incomes');
    Route::native('/gastos', ExpensesScreen::class)->name('mobile.expenses');
    Route::native('/presupuesto', BudgetScreen::class)->name('mobile.budget');
    Route::native('/ahorros', SavingsScreen::class)->name('mobile.savings');
    Route::native('/ahorros/{goal}', SavingsDetailScreen::class)->name('mobile.savings.detail');
    Route::native('/prestamos', LoansScreen::class)->name('mobile.loans');
    Route::native('/periodos', PeriodsScreen::class)->name('mobile.periods');
    Route::native('/periodos/{period}', PeriodDetailScreen::class)->name('mobile.periods.show');
    Route::native('/configuracion', SettingsScreen::class)->name('mobile.settings');
    Route::native('/mas', MoreScreen::class)->name('mobile.more');
});

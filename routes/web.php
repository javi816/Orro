<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/auth/firebase', [AuthController::class, 'store'])->name('auth.firebase');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/presupuesto', [BudgetController::class, 'index'])->name('budget.index');
    Route::put('/presupuesto', [BudgetController::class, 'update'])->name('budget.update');

    Route::get('/ingresos', [IncomeController::class, 'index'])->name('incomes.index');
    Route::post('/ingresos/generar', [IncomeController::class, 'generate'])->name('incomes.generate');
    Route::post('/ingresos/{income}/recibir', [IncomeController::class, 'receive'])->name('incomes.receive');

    Route::get('/gastos', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/gastos', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::post('/gastos/generar', [ExpenseController::class, 'generate'])->name('expenses.generate');
    Route::post('/gastos/{expense}/pagar', [ExpenseController::class, 'pay'])->name('expenses.pay');

    Route::get('/ahorros', [SavingsController::class, 'index'])->name('savings.index');
    Route::post('/ahorros', [SavingsController::class, 'store'])->name('savings.store');

    Route::get('/prestamos', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/prestamos', [LoanController::class, 'store'])->name('loans.store');
    Route::post('/prestamos/{loan}/reponer', [LoanController::class, 'repay'])->name('loans.repay');

    Route::get('/periodos', [PeriodController::class, 'index'])->name('periods.index');
    Route::get('/periodos/{period}', [PeriodController::class, 'show'])->name('periods.show');
    Route::post('/periodos/{period}/cerrar', [PeriodController::class, 'close'])->name('periods.close');

    Route::get('/configuracion', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/configuracion', [SettingsController::class, 'update'])->name('settings.update');

    Route::post('/configuracion/fuentes-ingreso', [SettingsController::class, 'storeIncomeSource'])
        ->name('settings.income-sources.store');
    Route::put('/configuracion/fuentes-ingreso/{source}', [SettingsController::class, 'updateIncomeSource'])
        ->name('settings.income-sources.update');
    Route::post('/configuracion/fuentes-ingreso/{source}/alternar', [SettingsController::class, 'toggleIncomeSource'])
        ->name('settings.income-sources.toggle');

    Route::post('/configuracion/gastos-fijos', [SettingsController::class, 'storeExpenseTemplate'])
        ->name('settings.expense-templates.store');
    Route::put('/configuracion/gastos-fijos/{template}', [SettingsController::class, 'updateExpenseTemplate'])
        ->name('settings.expense-templates.update');
    Route::post('/configuracion/gastos-fijos/{template}/alternar', [SettingsController::class, 'toggleExpenseTemplate'])
        ->name('settings.expense-templates.toggle');
});

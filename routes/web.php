<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('employee-jabatan', \App\Http\Controllers\EmpJabatanController::class);
    Route::resource('employee', \App\Http\Controllers\EmpDataController::class);
    Route::resource('payroll-settings', \App\Http\Controllers\PayrollSettingController::class);
    Route::resource('payroll-components', \App\Http\Controllers\PayrollComponentController::class);
    Route::resource('emp-allowance', \App\Http\Controllers\EmpAllowanceController::class)->only(['index', 'edit', 'update']);
    Route::resource('emp-loans', \App\Http\Controllers\EmpLoanController::class);

    Route::post('/payroll-results/process', [\App\Http\Controllers\PayrollResultController::class, 'process'])->name('payroll-results.process');
    Route::resource('payroll-results', \App\Http\Controllers\PayrollResultController::class)->only(['index']);
    Route::get('/payroll-results/{emp_id}/{month}/{year}/slip', [\App\Http\Controllers\PayrollResultController::class, 'slip'])->name('payroll-results.slip');

    Route::resource('emp-bpjs', \App\Http\Controllers\EmpBpjsController::class)
        ->only(['index', 'edit', 'update'])
        ->parameters(['emp-bpjs' => 'emp_bpjs']);
});



Route::get('/git-pull', [\App\Http\Controllers\DeployController::class, 'pull']);

require __DIR__ . '/auth.php';

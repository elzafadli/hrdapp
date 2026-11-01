<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('timeline.index') : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('timeline.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // ESS - Timeline (Arahkan pertama kali saat login & buka web/mobile)
    Route::get('/timeline', [\App\Http\Controllers\TimelineController::class, 'index'])->name('timeline.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // BACKOFFICE (Master Data & Payroll) - Khusus Jabatan: Finance, Admin Finance, dan Cashier
    Route::middleware('finance.access')->group(function () {
        Route::resource('employee-jabatan', \App\Http\Controllers\EmpJabatanController::class);
        Route::post('employee/{employee}/career', [\App\Http\Controllers\EmpDataController::class, 'storeCareer'])->name('employee.career.store');
        Route::delete('employee/{employee}/career/{career}', [\App\Http\Controllers\EmpDataController::class, 'destroyCareer'])->name('employee.career.destroy');
        Route::post('employee/{employee}/tenure', [\App\Http\Controllers\EmpDataController::class, 'updateTenure'])->name('employee.tenure.update');
        Route::post('employee/{employee}/education', [\App\Http\Controllers\EmpDataController::class, 'storeEducation'])->name('employee.education.store');
        Route::delete('employee/{employee}/education/{education}', [\App\Http\Controllers\EmpDataController::class, 'destroyEducation'])->name('employee.education.destroy');
        Route::post('employee/{employee}/experience', [\App\Http\Controllers\EmpDataController::class, 'storeExperience'])->name('employee.experience.store');
        Route::delete('employee/{employee}/experience/{experience}', [\App\Http\Controllers\EmpDataController::class, 'destroyExperience'])->name('employee.experience.destroy');
        Route::post('employee/{employee}/training', [\App\Http\Controllers\EmpDataController::class, 'storeTraining'])->name('employee.training.store');
        Route::delete('employee/{employee}/training/{training}', [\App\Http\Controllers\EmpDataController::class, 'destroyTraining'])->name('employee.training.destroy');
        Route::post('employee/{employee}/assignment', [\App\Http\Controllers\EmpDataController::class, 'updateAssignment'])->name('employee.assignment.update');
        Route::post('employee/{employee}/bpjs', [\App\Http\Controllers\EmpDataController::class, 'updateBpjs'])->name('employee.bpjs.update');
        Route::resource('employee', \App\Http\Controllers\EmpDataController::class);
        Route::resource('approval-settings', \App\Http\Controllers\ApprovalSettingController::class);
        Route::resource('payroll-settings', \App\Http\Controllers\PayrollSettingController::class);
        Route::resource('payroll-components', \App\Http\Controllers\PayrollComponentController::class);
        Route::resource('emp-allowance', \App\Http\Controllers\EmpAllowanceController::class)->only(['index', 'edit', 'update']);
        Route::post('emp-loans/{emp_loan}/post-to-budget', [\App\Http\Controllers\EmpLoanController::class, 'postToBudget'])->name('emp-loans.post-to-budget');
        Route::resource('emp-loans', \App\Http\Controllers\EmpLoanController::class);
        Route::resource('potongan-karyawan', \App\Http\Controllers\PotonganKaryawanController::class);

        Route::resource('payroll-results', \App\Http\Controllers\PayrollResultController::class)->only(['index', 'create']);
        Route::post('/payroll-results/process', [\App\Http\Controllers\PayrollResultController::class, 'process'])->name('payroll-results.process');
        Route::get('/payroll-results/{emp_id}/{month}/{year}/slip', [\App\Http\Controllers\PayrollResultController::class, 'slip'])->name('payroll-results.slip');
        Route::get('/payroll-results/export', [\App\Http\Controllers\PayrollResultController::class, 'export'])->name('payroll-results.export');
        Route::get('/payroll-results-deductions', [\App\Http\Controllers\PayrollResultController::class, 'deductions'])->name('payroll-results.deductions');
        Route::get('/payroll-results-deductions/export', [\App\Http\Controllers\PayrollResultController::class, 'exportDeductions'])->name('payroll-results.export-deductions');

        Route::resource('emp-bpjs', \App\Http\Controllers\EmpBpjsController::class)
            ->only(['index', 'edit', 'update'])
            ->parameters(['emp-bpjs' => 'emp_bpjs']);
    });

    // ESS - Cuti & Izin
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/', [\App\Http\Controllers\LeaveRequestController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\LeaveRequestController::class, 'create'])->name('create');
        Route::post('/calculate-preview', [\App\Http\Controllers\LeaveRequestController::class, 'calculatePreview'])->name('calculate-preview');
        Route::post('/', [\App\Http\Controllers\LeaveRequestController::class, 'store'])->name('store');
        Route::get('/{leave}', [\App\Http\Controllers\LeaveRequestController::class, 'show'])->name('show');
        Route::post('/{leave}/cancel', [\App\Http\Controllers\LeaveRequestController::class, 'cancel'])->name('cancel');
        Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\LeaveRequestController::class, 'approve'])->name('approve');
        Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\LeaveRequestController::class, 'reject'])->name('reject');
    });

    // ESS - Cuti Khusus
    Route::prefix('special-leave')->name('special-leave.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SpecialLeaveController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SpecialLeaveController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SpecialLeaveController::class, 'store'])->name('store');
        Route::get('/{specialLeave}', [\App\Http\Controllers\SpecialLeaveController::class, 'show'])->name('show');
        Route::post('/{specialLeave}/cancel', [\App\Http\Controllers\SpecialLeaveController::class, 'cancel'])->name('cancel');
        Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\SpecialLeaveController::class, 'approve'])->name('approve');
        Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\SpecialLeaveController::class, 'reject'])->name('reject');
    });

    // ESS - Izin
    Route::prefix('permit')->name('permit.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PermitRequestController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\PermitRequestController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PermitRequestController::class, 'store'])->name('store');
        Route::get('/{permit}', [\App\Http\Controllers\PermitRequestController::class, 'show'])->name('show');
        Route::post('/{permit}/cancel', [\App\Http\Controllers\PermitRequestController::class, 'cancel'])->name('cancel');
        Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\PermitRequestController::class, 'approve'])->name('approve');
        Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\PermitRequestController::class, 'reject'])->name('reject');
    });

    // ESS - Izin Sakit
    Route::prefix('sick')->name('sick.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SickLeaveController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SickLeaveController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SickLeaveController::class, 'store'])->name('store');
        Route::get('/{sick}', [\App\Http\Controllers\SickLeaveController::class, 'show'])->name('show');
        Route::post('/{sick}/cancel', [\App\Http\Controllers\SickLeaveController::class, 'cancel'])->name('cancel');
        Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\SickLeaveController::class, 'approve'])->name('approve');
        Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\SickLeaveController::class, 'reject'])->name('reject');
    });

    // ESS - Lembur
    Route::prefix('overtime')->name('overtime.')->group(function () {
        Route::get('/', [\App\Http\Controllers\OvertimeRequestController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\OvertimeRequestController::class, 'create'])->name('create');
        Route::post('/calculate-preview', [\App\Http\Controllers\OvertimeRequestController::class, 'calculatePreview'])->name('calculate-preview');
        Route::post('/', [\App\Http\Controllers\OvertimeRequestController::class, 'store'])->name('store');
        Route::get('/{overtime}', [\App\Http\Controllers\OvertimeRequestController::class, 'show'])->name('show');
        Route::post('/{overtime}/cancel', [\App\Http\Controllers\OvertimeRequestController::class, 'cancel'])->name('cancel');
        Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\OvertimeRequestController::class, 'approve'])->name('approve');
        Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\OvertimeRequestController::class, 'reject'])->name('reject');
    });

    // Approval Inbox — terpusat semua modul
    Route::prefix('approval-inbox')->name('approval-inbox.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ApprovalInboxController::class, 'index'])->name('index');
        Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\ApprovalInboxController::class, 'approve'])->name('approve');
        Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\ApprovalInboxController::class, 'reject'])->name('reject');
    });

    // ESS - Presensi (Masuk & Keluar)
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/check-in', [\App\Http\Controllers\AttendanceController::class, 'checkInView'])->name('check-in');
        Route::post('/check-in', [\App\Http\Controllers\AttendanceController::class, 'storeCheckIn'])->name('check-in.store');
        Route::get('/check-out', [\App\Http\Controllers\AttendanceController::class, 'checkOutView'])->name('check-out');
        Route::post('/check-out', [\App\Http\Controllers\AttendanceController::class, 'storeCheckOut'])->name('check-out.store');
        Route::get('/history', [\App\Http\Controllers\AttendanceController::class, 'history'])->name('history');
    });

    // ESS - Kalender Pengajuan
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EssCalendarController::class, 'index'])->name('index');
    });

    // ESS - Profil Karyawan (Tanpa ID, otomatis ambil dari session/auth user)
    Route::get('/ess/profile', [\App\Http\Controllers\EssProfileController::class, 'index'])->name('ess.profile');
    Route::put('/ess/profile', [\App\Http\Controllers\EssProfileController::class, 'updatePersonal'])->name('ess.profile.update');
});

Route::get('/git-pull', [\App\Http\Controllers\DeployController::class, 'pull']);

require __DIR__ . '/auth.php';

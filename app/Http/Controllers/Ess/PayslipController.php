<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\EmpData;
use App\Models\PayrollResult;
use App\Services\LatenessDeductionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayslipController extends Controller
{
    /**
     * Dapatkan instance employee milik user yang sedang terautentikasi.
     */
    protected function getAuthenticatedEmployee(): ?EmpData
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        return $user->employee ?? $user->getEmployeeRecord();
    }

    /**
     * Menampilkan daftar riwayat slip gaji milik karyawan yang sedang login.
     */
    public function index(Request $request, LatenessDeductionService $service)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return view('ess.payslip.no_employee');
        }

        // Ambil daftar tahun penggajian yang tersedia untuk karyawan ini
        $availableYearsQuery = DB::table('payroll_results')
            ->where('emp_id', $employee->id);

        if (Schema::hasColumn('payroll_results', 'status')) {
            $availableYearsQuery->where('status', 'final');
        }

        $availableYears = $availableYearsQuery
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $currentYear = (int) now()->year;
        if ($availableYears->isNotEmpty() && !$availableYears->contains($currentYear)) {
            $currentYear = (int) $availableYears->first();
        }

        $selectedYear = (int) $request->input('year', $currentYear);

        // Ambil seluruh periode slip gaji karyawan yang berstatus final
        $periodQuery = DB::table('payroll_results')
            ->where('emp_id', $employee->id);

        if (Schema::hasColumn('payroll_results', 'status')) {
            $periodQuery->where('status', 'final');
        }

        if ($request->filled('year')) {
            $periodQuery->where('year', $selectedYear);
        }

        $periodsRaw = $periodQuery
            ->select(
                'year',
                'month',
                DB::raw("SUM(CASE WHEN type IN ('allowance', 'thr') THEN amount ELSE 0 END) as total_income"),
                DB::raw("SUM(CASE WHEN type IN ('deduction', 'bpjs') THEN amount ELSE 0 END) as total_deduction"),
                DB::raw('MAX(updated_at) as last_updated_at')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $periods = $periodsRaw->map(function ($p) use ($service) {
            $cutoff = $service->getCutoffPeriod((int) $p->year, (int) $p->month);
            $p->cutoff_range = $cutoff['label'] ?? ($cutoff['start_date']->translatedFormat('d M Y') . ' s/d ' . $cutoff['end_date']->translatedFormat('d M Y'));
            $p->total_income = (float) $p->total_income;
            $p->total_deduction = (float) $p->total_deduction;
            $p->thp = $p->total_income - $p->total_deduction;
            $dateObj = Carbon::createFromDate((int) $p->year, (int) $p->month, 1);
            $p->period_label = $dateObj->translatedFormat('F Y');
            $p->status = 'final';
            return $p;
        });

        // KPI Ringkasan
        $latestPayslip = $periods->first();
        $totalPayslips = $periods->count();
        $latestThp = $latestPayslip ? $latestPayslip->thp : 0;
        $latestPeriodLabel = $latestPayslip ? $latestPayslip->period_label : '-';

        return view('ess.payslip.index', compact(
            'employee',
            'periods',
            'availableYears',
            'selectedYear',
            'latestPayslip',
            'totalPayslips',
            'latestThp',
            'latestPeriodLabel'
        ));
    }

    /**
     * Menampilkan detail rincian slip gaji periode tertentu milik karyawan yang login.
     * Mengunci akses HANYA untuk employee login dan HANYA status final.
     */
    public function show($month, $year, LatenessDeductionService $service)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            abort(403, 'Data profil karyawan Anda tidak ditemukan.');
        }

        $month = (int) $month;
        $year  = (int) $year;

        $query = PayrollResult::with(['payrollComponent', 'payrollSetting'])
            ->where('emp_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year);

        // Validasi status: Harus 'final'
        if (Schema::hasColumn('payroll_results', 'status')) {
            $nonFinalExists = PayrollResult::where('emp_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('status', '!=', 'final')
                ->exists();

            if ($nonFinalExists) {
                abort(403, 'Slip gaji untuk periode ini belum berstatus final / belum dipublikasikan oleh HRD.');
            }

            $query->where('status', 'final');
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return redirect()->route('ess.payslip.index')
                ->with('error', 'Slip gaji untuk periode ' . Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') . ' tidak ditemukan atau belum dipublikasikan.');
        }

        // Kelompokkan komponen penghasilan & potongan
        $allowances = $results->filter(fn($r) =>
            $r->payrollComponent &&
            (
                $r->payrollComponent->type === 'allowance' ||
                ($r->payrollComponent->type === 'thr' && $r->amount > 0)
            )
        );

        $deductions = $results->filter(fn($r) =>
            $r->payrollComponent && in_array($r->payrollComponent->type, ['deduction', 'bpjs'])
        );

        $totalIncome = (float) $allowances->sum('amount');
        $totalDeduction = (float) $deductions->sum('amount');
        $thp = $totalIncome - $totalDeduction;

        $cutoffPeriod = $service->getCutoffPeriod($year, $month);
        $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');

        return view('ess.payslip.show', compact(
            'employee',
            'month',
            'year',
            'periodLabel',
            'cutoffPeriod',
            'allowances',
            'deductions',
            'totalIncome',
            'totalDeduction',
            'thp'
        ));
    }

    /**
     * Tampilan cetak bersih / print view slip gaji.
     * Mengunci akses HANYA untuk employee login dan HANYA status final.
     */
    public function print($month, $year)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            abort(403, 'Data profil karyawan Anda tidak ditemukan.');
        }

        $month = (int) $month;
        $year  = (int) $year;

        $query = PayrollResult::with(['payrollComponent', 'payrollSetting'])
            ->where('emp_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year);

        if (Schema::hasColumn('payroll_results', 'status')) {
            $nonFinalExists = PayrollResult::where('emp_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('status', '!=', 'final')
                ->exists();

            if ($nonFinalExists) {
                abort(403, 'Slip gaji untuk periode ini belum berstatus final.');
            }

            $query->where('status', 'final');
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            abort(404, 'Data slip gaji tidak ditemukan.');
        }

        $allowances = $results->filter(fn($r) =>
            $r->payrollComponent &&
            (
                $r->payrollComponent->type === 'allowance' ||
                ($r->payrollComponent->type === 'thr' && $r->amount > 0)
            )
        );
        $subsidies = collect();
        $bpjs = collect();
        $deductions = $results->filter(fn($r) => $r->payrollComponent && in_array($r->payrollComponent->type, ['deduction', 'bpjs']));

        return view('payroll_results.slip', compact('employee', 'month', 'year', 'results', 'allowances', 'subsidies', 'bpjs', 'deductions'));
    }
}

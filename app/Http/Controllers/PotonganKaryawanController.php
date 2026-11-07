<?php

namespace App\Http\Controllers;

use App\Models\EmpData;
use App\Models\EmpLoan;
use App\Models\PayrollComponent;
use App\Models\PayrollSettingDetail;
use App\Models\PotonganKaryawanDetail;
use App\Models\PotonganKaryawan;
use App\Services\LatenessDeductionService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PotonganKaryawanController extends Controller
{
    public function index(Request $request, LatenessDeductionService $service)
    {
        if ($request->ajax()) {
            $data = PotonganKaryawan::query()->orderBy('year', 'desc')->orderBy('month', 'desc');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('period', function ($row) {
                    $date = \Carbon\Carbon::createFromDate($row->year, $row->month, 1);
                    return '<div class="font-bold text-slate-900 text-sm">' . $date->translatedFormat('F Y') . '</div>'
                        . '<div class="text-xs text-slate-500 font-mono">Tahun ' . $row->year . ' • Periode ' . $row->month . '</div>';
                })
                ->addColumn('start_date', function ($row) use ($service) {
                    $startDate = !empty($row->start_date) ? \Carbon\Carbon::parse($row->start_date) : $service->getCutoffPeriod((int) $row->year, (int) $row->month)['start_date'];
                    return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 font-mono border border-slate-200">'
                        . '<i class="fa-regular fa-calendar text-slate-400"></i>'
                        . $startDate->translatedFormat('d M Y')
                        . '</span>';
                })
                ->addColumn('end_date', function ($row) use ($service) {
                    $endDate = !empty($row->end_date) ? \Carbon\Carbon::parse($row->end_date) : $service->getCutoffPeriod((int) $row->year, (int) $row->month)['end_date'];
                    return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 font-mono">'
                        . '<i class="fa-solid fa-calendar-check text-blue-500"></i>'
                        . $endDate->translatedFormat('d M Y')
                        . '</span>';
                })
                ->addColumn('cutoff_range', function ($row) use ($service) {
                    $startDate = !empty($row->start_date) ? \Carbon\Carbon::parse($row->start_date) : $service->getCutoffPeriod((int) $row->year, (int) $row->month)['start_date'];
                    $endDate = !empty($row->end_date) ? \Carbon\Carbon::parse($row->end_date) : $service->getCutoffPeriod((int) $row->year, (int) $row->month)['end_date'];
                    $label = $startDate->translatedFormat('d M Y') . ' s/d ' . $endDate->translatedFormat('d M Y');

                    return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">'
                        . '<i class="fa-solid fa-clock-rotate-left text-amber-600"></i>'
                        . $label
                        . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('potongan-karyawan.edit', $row->id);
                    $deleteUrl = route('potongan-karyawan.destroy', $row->id);
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    return '<div class="flex items-center gap-2 justify-center">
                        <a href="' . $editUrl . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition duration-150" title="Kelola Potongan">
                            <i class="fa-solid fa-pen-to-square"></i> Kelola Potongan
                        </a>
                        <form action="' . $deleteUrl . '" method="POST" class="inline" onsubmit="return confirm(\'Apakah Anda yakin ingin menghapus data potongan periode ini?\');">
                            ' . $csrf . '
                            ' . $method . '
                            <button type="submit" class="inline-flex items-center gap-1 p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition" title="Hapus Periode">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </div>';
                })
                ->rawColumns(['period', 'start_date', 'end_date', 'cutoff_range', 'action'])
                ->make(true);
        }

        return view('potongan_karyawan.index');
    }

    public function create(LatenessDeductionService $service)
    {
        $employees = EmpData::with('jabatan')->get();
        $components = PayrollSettingDetail::with('component')
            ->where(function($q) {
                $q->where('is_variable', true)
                  ->orWhereHas('component', function($sub) {
                      $sub->where('name', 'like', '%Pinjaman%')
                          ->orWhere('name', 'like', '%Keterlambatan%');
                  });
            })
            ->whereHas('component', function($q) {
                $q->where('type', 'deduction');
            })
            ->get()
            ->pluck('component')
            ->unique('id')
            ->filter();
        $existingDeductions = collect();

        $year = (int) date('Y');
        $month = (int) date('n');
        $cutoffPeriod = $service->getCutoffPeriod($year, $month);
        $lateRate = $service->getLateRate();
        $lateComponent = $service->getLateComponent();
        $loanComponent = $service->getLoanComponent();
        $activeLoans = EmpLoan::where('status', 'open')->get()->groupBy('emp_id');
        $initialLateData = $service->calculate($year, $month)['deductions'] ?? [];

        return view('potongan_karyawan.form', compact('employees', 'components', 'existingDeductions', 'cutoffPeriod', 'lateRate', 'lateComponent', 'loanComponent', 'activeLoans', 'initialLateData'));
    }

    public function store(Request $request, LatenessDeductionService $service)
    {
        $request->validate([
            'period_date'  => 'nullable|date',
            'year'         => 'nullable|integer|min:2000|max:2100',
            'month'        => 'nullable|integer|min:1|max:12',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
            'deductions'   => 'array',
            'deductions.*' => 'nullable|numeric',
        ]);

        if ($request->filled('period_date')) {
            $parsed = \Carbon\Carbon::parse($request->period_date);
            $year = $parsed->year;
            $month = $parsed->month;
            $periodDate = $parsed->toDateString();
        } else {
            $year = (int) $request->input('year', date('Y'));
            $month = (int) $request->input('month', date('n'));
            $periodDate = \Carbon\Carbon::createFromDate($year, $month, 25)->toDateString();
        }

        $cutoff = $service->getCutoffPeriod($year, $month);
        $startDate = $request->filled('start_date') ? $request->start_date : $cutoff['start_date_formatted'];
        $endDate   = $request->filled('end_date') ? $request->end_date : $cutoff['end_date_formatted'];

        $headerData = [
            'year'  => $year,
            'month' => $month,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('potongan_karyawans', 'period_date')) {
            $headerData['period_date'] = $periodDate;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('potongan_karyawans', 'start_date')) {
            $headerData['start_date'] = $startDate;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('potongan_karyawans', 'end_date')) {
            $headerData['end_date'] = $endDate;
        }

        // Create header
        $header = PotonganKaryawan::create($headerData);

        // Create new details
        $deductionsData = $request->input('deductions', []);

        foreach ($deductionsData as $key => $value) {
            if ($value !== null && $value != '') {
                // Parse key format: employeeId_componentId
                [$employeeId, $componentId] = explode('_', $key);

                PotonganKaryawanDetail::create([
                    'header_id'            => $header->id,
                    'employee_id'          => $employeeId,
                    'payroll_component_id' => $componentId,
                    'value'                => $value,
                ]);
            }
        }

        return redirect()->route('potongan-karyawan.index')->with('success', 'Deductions created successfully.');
    }

    public function edit(PotonganKaryawan $potonganKaryawan, LatenessDeductionService $service)
    {
        $header = $potonganKaryawan;
        $employees = EmpData::with('jabatan')->get();
        $components = PayrollSettingDetail::with('component')
            ->where(function($q) {
                $q->where('is_variable', true)
                  ->orWhereHas('component', function($sub) {
                      $sub->where('name', 'like', '%Pinjaman%')
                          ->orWhere('name', 'like', '%Keterlambatan%');
                  });
            })
            ->whereHas('component', function($q) {
                $q->where('type', 'deduction');
            })
            ->get()
            ->pluck('component')
            ->unique('id')
            ->filter();

        // Get existing deductions
        $existingDeductions = PotonganKaryawanDetail::where('header_id', $header->id)
            ->get()
            ->map(function ($detail) {
                return [
                    'employee_id' => $detail->employee_id,
                    'component_id' => $detail->payroll_component_id,
                    'value' => $detail->value,
                ];
            });

        $year = (int) $header->year;
        $month = (int) $header->month;
        $cutoffPeriod = $service->getCutoffPeriod($year, $month);
        $lateRate = $service->getLateRate();
        $lateComponent = $service->getLateComponent();
        $loanComponent = $service->getLoanComponent();
        $activeLoans = EmpLoan::where('status', 'open')->get()->groupBy('emp_id');
        $initialLateData = $service->calculate($year, $month)['deductions'] ?? [];

        return view('potongan_karyawan.form', compact('header', 'employees', 'components', 'existingDeductions', 'cutoffPeriod', 'lateRate', 'lateComponent', 'loanComponent', 'activeLoans', 'initialLateData'));
    }

    /**
     * Hitung preview akumulasi keterlambatan & cicilan pinjaman per karyawan (AJAX).
     */
    public function calculateLateDeduction(Request $request, LatenessDeductionService $service)
    {
        $request->validate([
            'period_date' => 'nullable|date',
            'year'        => 'nullable|integer|min:2000|max:2100',
            'month'       => 'nullable|integer|min:1|max:12',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'rate'        => 'nullable|numeric|min:0',
        ]);

        if ($request->filled('period_date')) {
            $parsed = \Carbon\Carbon::parse($request->period_date);
            $year = $parsed->year;
            $month = $parsed->month;
        } else {
            $year = (int) $request->input('year', date('Y'));
            $month = (int) $request->input('month', date('n'));
        }

        $calc = $service->calculateAll(
            $year,
            $month,
            $request->start_date,
            $request->end_date,
            $request->filled('rate') ? (float) $request->rate : null
        );

        return response()->json($calc);
    }

    /**
     * Sinkronkan & simpan denda keterlambatan dan cicilan pinjaman langsung ke tabel potongan_karyawan_details.
     */
    public function syncLateDeduction(Request $request, PotonganKaryawan $potonganKaryawan, LatenessDeductionService $service)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'rate'       => 'nullable|numeric|min:0',
        ]);

        $calc = $service->syncToPotonganKaryawan(
            $potonganKaryawan,
            $request->start_date,
            $request->end_date,
            $request->filled('rate') ? (float) $request->rate : null
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Potongan keterlambatan dan cicilan pinjaman berhasil disinkronkan dan disimpan ke database.',
                'data'    => $calc,
            ]);
        }

        return redirect()->route('potongan-karyawan.edit', $potonganKaryawan->id)
            ->with('success', 'Potongan keterlambatan dan cicilan pinjaman berhasil disinkronkan dan disimpan.');
    }

    public function update(Request $request, PotonganKaryawan $potonganKaryawan, LatenessDeductionService $service)
    {
        $header = $potonganKaryawan;

        $request->validate([
            'period_date'  => 'nullable|date',
            'year'         => 'nullable|integer|min:2000|max:2100',
            'month'        => 'nullable|integer|min:1|max:12',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
            'deductions'   => 'array',
            'deductions.*' => 'nullable|numeric',
        ]);

        if ($request->filled('period_date')) {
            $parsed = \Carbon\Carbon::parse($request->period_date);
            $year = $parsed->year;
            $month = $parsed->month;
            $periodDate = $parsed->toDateString();
        } else {
            $year = (int) $request->input('year', $header->year);
            $month = (int) $request->input('month', $header->month);
            $periodDate = \Carbon\Carbon::createFromDate($year, $month, 25)->toDateString();
        }

        $cutoff = $service->getCutoffPeriod($year, $month);
        $startDate = $request->filled('start_date') ? $request->start_date : $cutoff['start_date_formatted'];
        $endDate   = $request->filled('end_date') ? $request->end_date : $cutoff['end_date_formatted'];

        $updateData = [
            'year'  => $year,
            'month' => $month,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('potongan_karyawans', 'period_date')) {
            $updateData['period_date'] = $periodDate;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('potongan_karyawans', 'start_date')) {
            $updateData['start_date'] = $startDate;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('potongan_karyawans', 'end_date')) {
            $updateData['end_date'] = $endDate;
        }

        // Update header
        $header->update($updateData);

        // Delete existing details for this header
        PotonganKaryawanDetail::where('header_id', $header->id)->delete();

        // Create new details
        $deductionsData = $request->input('deductions', []);

        foreach ($deductionsData as $key => $value) {
            if ($value !== null && $value != '') {
                // Parse key format: employeeId_componentId
                [$employeeId, $componentId] = explode('_', $key);

                PotonganKaryawanDetail::create([
                    'header_id' => $header->id,
                    'employee_id' => $employeeId,
                    'payroll_component_id' => $componentId,
                    'value' => $value,
                ]);
            }
        }

        return redirect()->route('potongan-karyawan.index')->with('success', 'Deductions updated successfully.');
    }

    public function destroy(PotonganKaryawan $potonganKaryawan)
    {
        $potonganKaryawan->details()->delete();
        $potonganKaryawan->delete();
        return redirect()->route('potongan-karyawan.index')->with('success', 'Deductions deleted successfully.');
    }
}

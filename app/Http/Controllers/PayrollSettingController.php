<?php

namespace App\Http\Controllers;

use App\Models\PayrollSetting;
use App\Models\PayrollSettingDetail;
use App\Models\PayrollComponent;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollSettingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PayrollSetting::select('payroll_settings.*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('components.datatable-actions', [
                        'editRoute' => route('payroll-settings.edit', $row->id),
                        'deleteRoute' => route('payroll-settings.destroy', $row->id),
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('payroll_settings.index');
    }

    public function create()
    {
        $payrollComponents = PayrollComponent::all();
        return view('payroll_settings.form', [
            'payrollSetting' => new PayrollSetting(),
            'payrollComponents' => $payrollComponents,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_aktif' => 'boolean',
            'cutoff_start_day' => 'nullable|integer|min:1|max:31',
            'cutoff_end_day' => 'nullable|integer|min:1|max:31',
            'details' => 'required|array|min:1',
            'details.*.payroll_component_id' => 'required|exists:payroll_components,id',
            'details.*.base_amount' => 'nullable|numeric',
            'details.*.value' => 'nullable|numeric',
            'details.*.is_thr' => 'boolean',
            'details.*.is_variable' => 'boolean',
            'details.*.urutan' => 'integer',
        ]);

        DB::transaction(function () use ($request) {
            $data = [
                'name' => $request->name,
                'is_aktif' => $request->has('is_aktif') ? 1 : 0,
            ];
            if (Schema::hasColumn('payroll_settings', 'cutoff_start_day')) {
                $data['cutoff_start_day'] = $request->input('cutoff_start_day', 25);
            }
            if (Schema::hasColumn('payroll_settings', 'cutoff_end_day')) {
                $data['cutoff_end_day'] = $request->input('cutoff_end_day', 25);
            }

            $payrollSetting = PayrollSetting::create($data);

            foreach ($request->details as $detail) {
                $payrollSetting->details()->create($detail);
            }
        });

        return redirect()->route('payroll-settings.index')->with('success', 'Payroll Setting created successfully.');
    }

    public function edit(PayrollSetting $payrollSetting)
    {
        $payrollSetting->load('details');
        $payrollComponents = PayrollComponent::all();
        return view('payroll_settings.form', [
            'payrollSetting' => $payrollSetting,
            'payrollComponents' => $payrollComponents,
        ]);
    }

    public function update(Request $request, PayrollSetting $payrollSetting)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_aktif' => 'boolean',
            'cutoff_start_day' => 'nullable|integer|min:1|max:31',
            'cutoff_end_day' => 'nullable|integer|min:1|max:31',
            'details' => 'array',
            'details.*.payroll_component_id' => 'required|exists:payroll_components,id',
            'details.*.base_amount' => 'nullable|numeric',
            'details.*.value' => 'nullable|numeric',
            'details.*.is_thr' => 'boolean',
            'details.*.is_variable' => 'boolean',
            'details.*.urutan' => 'integer',
        ]);

        DB::transaction(function () use ($request, $payrollSetting) {
            $data = [
                'name' => $request->name,
                'is_aktif' => $request->has('is_aktif') ? 1 : 0,
            ];
            if (Schema::hasColumn('payroll_settings', 'cutoff_start_day')) {
                $data['cutoff_start_day'] = $request->input('cutoff_start_day', 25);
            }
            if (Schema::hasColumn('payroll_settings', 'cutoff_end_day')) {
                $data['cutoff_end_day'] = $request->input('cutoff_end_day', 25);
            }

            $payrollSetting->update($data);

            // Simple strategy: delete all and recreate
            // Alternatively, we could sync if we passed IDs
            $payrollSetting->details()->delete();
            if ($request->has('details')) {
                foreach ($request->details as $detail) {
                    $payrollSetting->details()->create($detail);
                }
            }
        });

        return redirect()->route('payroll-settings.index')->with('success', 'Payroll Setting updated successfully.');
    }

    public function destroy(PayrollSetting $payrollSetting)
    {
        $payrollSetting->delete();
        return redirect()->route('payroll-settings.index')->with('success', 'Payroll Setting deleted successfully.');
    }
}

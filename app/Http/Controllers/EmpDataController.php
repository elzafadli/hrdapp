<?php

namespace App\Http\Controllers;

use App\Models\EmpData;
use App\Models\EmpJabatan;
use App\Models\Project;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmpDataController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EmpData::with('jabatan', 'project.company')->select('emp_data.*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('jabatan_name', function ($row) {
                    return $row->jabatan ? $row->jabatan->jabatan : '-';
                })
                ->addColumn('project_name', function ($row) {
                    if (!$row->project) return '-';
                    $companyName = $row->project->company ? $row->project->company->code : 'No Company';
                    return $companyName . ' - ' . $row->project->name;
                })
                ->addColumn('action', function ($row) {
                    return view('components.datatable-actions', [
                        'editRoute' => route('employee.edit', $row->id),
                        'deleteRoute' => route('employee.destroy', $row->id),
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('emp_data.index');
    }

    public function create()
    {
        $jabatanOptions = EmpJabatan::pluck('jabatan', 'id');
        $projects = Project::with('company')->get();
        $projectOptions = $projects->pluck('name', 'id')->map(function ($name, $id) use ($projects) {
            $project = $projects->find($id);
            $companyName = $project->company ? $project->company->code : 'No Company';
            return $companyName . ' - ' . $name;
        });
        $ptkpOptions = [
            'TK0' => 'TK/0 - Tidak Kawin, 0 Tanggungan (TER A)',
            'TK1' => 'TK/1 - Tidak Kawin, 1 Tanggungan (TER A)',
            'TK2' => 'TK/2 - Tidak Kawin, 2 Tanggungan (TER B)',
            'TK3' => 'TK/3 - Tidak Kawin, 3 Tanggungan (TER B)',
            'K0' => 'K/0 - Kawin, 0 Tanggungan (TER A)',
            'K1' => 'K/1 - Kawin, 1 Tanggungan (TER B)',
            'K2' => 'K/2 - Kawin, 2 Tanggungan (TER B)',
            'K3' => 'K/3 - Kawin, 3 Tanggungan (TER C)',
        ];
        return view('emp_data.form', [
            'empData' => new EmpData(),
            'jabatanOptions' => $jabatanOptions,
            'projectOptions' => $projectOptions,
            'ptkpOptions' => $ptkpOptions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'jabatan_id' => 'required|exists:emp_jabatan,id',
            'project_id' => 'nullable|exists:projects,id',
            'status_ptkp' => 'required|in:TK0,TK1,TK2,TK3,K0,K1,K2,K3',
        ]);

        EmpData::create($request->all());

        return redirect()->route('employee.index')->with('success', 'Employee Data created successfully.');
    }

    public function edit(EmpData $employee)
    {
        $jabatanOptions = EmpJabatan::pluck('jabatan', 'id');
        $projects = Project::with('company')->get();
        $projectOptions = $projects->pluck('name', 'id')->map(function ($name, $id) use ($projects) {
            $project = $projects->find($id);
            $companyName = $project->company ? $project->company->name : 'No Company';
            return $companyName . ' - ' . $name;
        });
        $ptkpOptions = [
            'TK0' => 'TK/0 - Tidak Kawin, 0 Tanggungan (TER A)',
            'TK1' => 'TK/1 - Tidak Kawin, 1 Tanggungan (TER B)',
            'TK2' => 'TK/2 - Tidak Kawin, 2 Tanggungan (TER B)',
            'TK3' => 'TK/3 - Tidak Kawin, 3 Tanggungan (TER B)',
            'K0' => 'K/0 - Kawin, 0 Tanggungan (TER A)',
            'K1' => 'K/1 - Kawin, 1 Tanggungan (TER B)',
            'K2' => 'K/2 - Kawin, 2 Tanggungan (TER B)',
            'K3' => 'K/3 - Kawin, 3 Tanggungan (TER C)',
        ];
        return view('emp_data.form', [
            'empData' => $employee,
            'jabatanOptions' => $jabatanOptions,
            'projectOptions' => $projectOptions,
            'ptkpOptions' => $ptkpOptions,
        ]);
    }

    public function update(Request $request, EmpData $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'jabatan_id' => 'required|exists:emp_jabatan,id',
            'project_id' => 'nullable|exists:projects,id',
            'status_ptkp' => 'required|in:TK0,TK1,TK2,TK3,K0,K1,K2,K3',
        ]);

        $employee->update($request->all());

        return redirect()->route('employee.index')->with('success', 'Employee Data updated successfully.');
    }

    public function destroy(EmpData $employee)
    {
        $employee->delete();

        return redirect()->route('employee.index')->with('success', 'Employee Data deleted successfully.');
    }
}

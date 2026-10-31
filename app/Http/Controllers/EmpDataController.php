<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmpData;
use App\Models\EmpJabatan;
use App\Models\LeaveBalance;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class EmpDataController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EmpData::with('jabatan', 'project.company', 'company')->select('emp_data.*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('gender_label', function ($row) {
                    $gender = (Schema::hasColumn('emp_data', 'gender') && $row->gender) ? $row->gender : 'male';
                    return ($gender === 'female')
                        ? '<span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-pink-100 text-pink-700">Perempuan</span>'
                        : '<span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">Laki-laki</span>';
                })
                ->addColumn('company_name', function ($row) {
                    $comp = $row->company ?? $row->project?->company;
                    return $comp ? $comp->code : '-';
                })
                ->addColumn('jabatan_name', function ($row) {
                    return $row->jabatan ? $row->jabatan->jabatan : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('components.datatable-actions', [
                        'editRoute' => route('employee.edit', $row->id),
                        'deleteRoute' => route('employee.destroy', $row->id),
                    ])->render();
                })
                ->rawColumns(['gender_label', 'action'])
                ->make(true);
        }

        return view('emp_data.index');
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $userOptions = $users->pluck('name', 'id')->map(function ($name, $id) use ($users) {
            $u = $users->find($id);
            return $name . ' (' . $u->email . ' - ' . ucfirst(str_replace('_', ' ', $u->role)) . ')';
        });

        $jabatanOptions = EmpJabatan::pluck('jabatan', 'id');
        $companies = Company::orderBy('code')->get();
        $companyOptions = $companies->pluck('name', 'id')->map(function ($name, $id) use ($companies) {
            $c = $companies->find($id);
            return $c ? ($c->code . ' - ' . $name) : $name;
        });

        $projects = Project::with('company')->get();
        $projectOptions = $projects->pluck('name', 'id')->map(function ($name, $id) use ($projects) {
            $project = $projects->find($id);
            $companyName = $project->company ? $project->company->code : 'No Company';
            return $companyName . ' - ' . $name;
        });

        $genderOptions = [
            'male'   => 'Laki-laki',
            'female' => 'Perempuan',
        ];

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
            'empData'        => new EmpData(),
            'userOptions'    => $userOptions,
            'companyOptions' => $companyOptions,
            'genderOptions'  => $genderOptions,
            'jabatanOptions' => $jabatanOptions,
            'projectOptions' => $projectOptions,
            'ptkpOptions'    => $ptkpOptions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'     => 'nullable|exists:users,id',
            'name'        => 'required|string|max:255',
            'gender'      => 'nullable|in:male,female',
            'company_id'  => 'nullable|exists:companies,id',
            'jabatan_id'  => 'required|exists:emp_jabatan,id',
            'project_id'  => 'nullable|exists:projects,id',
            'status_ptkp' => 'required|in:TK0,TK1,TK2,TK3,K0,K1,K2,K3',
        ]);

        if (Schema::hasColumn('emp_data', 'gender')) {
            $validated['gender'] = $validated['gender'] ?? 'male';
        } else {
            unset($validated['gender']);
        }

        if (!Schema::hasColumn('emp_data', 'company_id')) {
            unset($validated['company_id']);
        }

        $employee = EmpData::create($validated);

        // Inisialisasi saldo cuti default jika belum ada
        if (Schema::hasTable('leave_balances')) {
            LeaveBalance::firstOrCreate(
                ['emp_id' => $employee->id, 'year' => (int) date('Y')],
                [
                    'total_leave'     => 12.0,
                    'used_leave'      => 0.0,
                    'remaining_leave' => 12.0,
                    'notes'           => 'Saldo cuti awal tahun ' . date('Y'),
                ]
            );
        }

        return redirect()->route('employee.index')->with('success', 'Employee Data created successfully.');
    }

    public function edit(EmpData $employee)
    {
        $users = User::orderBy('name')->get();
        $userOptions = $users->pluck('name', 'id')->map(function ($name, $id) use ($users) {
            $u = $users->find($id);
            return $name . ' (' . $u->email . ' - ' . ucfirst(str_replace('_', ' ', $u->role)) . ')';
        });

        $jabatanOptions = EmpJabatan::pluck('jabatan', 'id');
        $companies = Company::orderBy('code')->get();
        $companyOptions = $companies->pluck('name', 'id')->map(function ($name, $id) use ($companies) {
            $c = $companies->find($id);
            return $c ? ($c->code . ' - ' . $name) : $name;
        });

        $projects = Project::with('company')->get();
        $projectOptions = $projects->pluck('name', 'id')->map(function ($name, $id) use ($projects) {
            $project = $projects->find($id);
            $companyName = $project->company ? $project->company->name : 'No Company';
            return $companyName . ' - ' . $name;
        });

        $genderOptions = [
            'male'   => 'Laki-laki',
            'female' => 'Perempuan',
        ];

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
            'empData'        => $employee,
            'userOptions'    => $userOptions,
            'companyOptions' => $companyOptions,
            'genderOptions'  => $genderOptions,
            'jabatanOptions' => $jabatanOptions,
            'projectOptions' => $projectOptions,
            'ptkpOptions'    => $ptkpOptions,
        ]);
    }

    public function update(Request $request, EmpData $employee)
    {
        $validated = $request->validate([
            'user_id'     => 'nullable|exists:users,id',
            'name'        => 'required|string|max:255',
            'gender'      => 'nullable|in:male,female',
            'company_id'  => 'nullable|exists:companies,id',
            'jabatan_id'  => 'required|exists:emp_jabatan,id',
            'project_id'  => 'nullable|exists:projects,id',
            'status_ptkp' => 'required|in:TK0,TK1,TK2,TK3,K0,K1,K2,K3',
        ]);

        if (Schema::hasColumn('emp_data', 'gender')) {
            $validated['gender'] = $validated['gender'] ?? 'male';
        } else {
            unset($validated['gender']);
        }

        if (!Schema::hasColumn('emp_data', 'company_id')) {
            unset($validated['company_id']);
        }

        $employee->update($validated);

        return redirect()->route('employee.index')->with('success', 'Employee Data updated successfully.');
    }

    public function destroy(EmpData $employee)
    {
        $employee->delete();

        return redirect()->route('employee.index')->with('success', 'Employee Data deleted successfully.');
    }
}

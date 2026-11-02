<?php

namespace App\Http\Controllers;

use App\Models\EmpJabatan;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmpJabatanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EmpJabatan::query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('components.datatable-actions', [
                        'editRoute' => route('employee-jabatan.edit', $row->id),
                        'deleteRoute' => route('employee-jabatan.destroy', $row->id),
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('emp_jabatan.index');
    }

    public function create()
    {
        return view('emp_jabatan.form', ['jabatan' => new EmpJabatan()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'jabatan' => 'required|string|max:255',
        ]);

        EmpJabatan::create($request->all());

        return redirect()->route('employee-jabatan.index')->with('success', 'Jabatan created successfully.');
    }

    public function edit(EmpJabatan $employeeJabatan)
    {
        return view('emp_jabatan.form', ['jabatan' => $employeeJabatan]);
    }

    public function update(Request $request, EmpJabatan $employeeJabatan)
    {
        $request->validate([
            'jabatan' => 'required|string|max:255',
        ]);

        $employeeJabatan->update($request->all());

        return redirect()->route('employee-jabatan.index')->with('success', 'Jabatan updated successfully.');
    }

    public function destroy(EmpJabatan $employeeJabatan)
    {
        if ($employeeJabatan->employees()->exists()) {
            return redirect()->route('employee-jabatan.index')->with('error', 'Jabatan tidak dapat dihapus karena masih digunakan oleh data karyawan.');
        }

        try {
            $employeeJabatan->delete();
            return redirect()->route('employee-jabatan.index')->with('success', 'Jabatan deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('employee-jabatan.index')->with('error', 'Gagal menghapus jabatan: ' . $e->getMessage());
        }
    }
}

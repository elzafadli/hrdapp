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

    public function edit(EmpJabatan $empJabatan)
    {
        return view('emp_jabatan.form', ['jabatan' => $empJabatan]);
    }

    public function update(Request $request, EmpJabatan $empJabatan)
    {
        $request->validate([
            'jabatan' => 'required|string|max:255',
        ]);

        $empJabatan->update($request->all());

        return redirect()->route('employee-jabatan.index')->with('success', 'Jabatan updated successfully.');
    }

    public function destroy(EmpJabatan $empJabatan)
    {
        $empJabatan->delete();

        return redirect()->route('employee-jabatan.index')->with('success', 'Jabatan deleted successfully.');
    }
}

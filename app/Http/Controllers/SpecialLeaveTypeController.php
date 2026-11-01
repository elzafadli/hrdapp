<?php

namespace App\Http\Controllers;

use App\Models\SpecialLeaveType;
use Illuminate\Http\Request;

class SpecialLeaveTypeController extends Controller
{
    public function index()
    {
        $types = SpecialLeaveType::withCount('leaveRequests')->orderBy('id', 'asc')->get();

        return view('special_leave_types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'code'                => 'required|string|max:50|unique:special_leave_types,code',
            'days_allowed'        => 'required|numeric|min:0.5',
            'gender_restriction'  => 'required|in:all,male,female',
            'requires_attachment' => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
            'description'         => 'nullable|string',
        ]);

        $validated['code'] = strtolower($validated['code']);
        $validated['requires_attachment'] = $request->boolean('requires_attachment');
        $validated['is_active'] = $request->boolean('is_active');

        SpecialLeaveType::create($validated);

        return redirect()->route('special-leave-types.index')
            ->with('success', 'Tipe cuti khusus berhasil ditambahkan.');
    }

    public function update(Request $request, SpecialLeaveType $specialLeaveType)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'code'                => 'required|string|max:50|unique:special_leave_types,code,' . $specialLeaveType->id,
            'days_allowed'        => 'required|numeric|min:0.5',
            'gender_restriction'  => 'required|in:all,male,female',
            'requires_attachment' => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
            'description'         => 'nullable|string',
        ]);

        $validated['code'] = strtolower($validated['code']);
        $validated['requires_attachment'] = $request->boolean('requires_attachment');
        $validated['is_active'] = $request->boolean('is_active');

        $specialLeaveType->update($validated);

        return redirect()->route('special-leave-types.index')
            ->with('success', 'Tipe cuti khusus berhasil diperbarui.');
    }

    public function destroy(SpecialLeaveType $specialLeaveType)
    {
        if ($specialLeaveType->leaveRequests()->exists()) {
            return redirect()->back()->withErrors([
                'error' => 'Tipe cuti khusus ini sudah pernah diajukan oleh karyawan dalam riwayat permohonan dan tidak dapat dihapus. Anda dapat menonaktifkannya.'
            ]);
        }

        $specialLeaveType->delete();

        return redirect()->route('special-leave-types.index')
            ->with('success', 'Tipe cuti khusus berhasil dihapus.');
    }
}


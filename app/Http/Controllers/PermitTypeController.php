<?php

namespace App\Http\Controllers;

use App\Models\PermitType;
use Illuminate\Http\Request;

class PermitTypeController extends Controller
{
    /**
     * Tampilkan daftar master jenis/tipe izin.
     */
    public function index()
    {
        $types = PermitType::withCount('leaveRequests')->orderBy('id', 'asc')->get();

        return view('permit_types.index', compact('types'));
    }

    /**
     * Simpan jenis izin baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:150',
            'code'                => 'required|string|max:50|unique:permit_types,code',
            'is_paid'             => 'nullable|boolean',
            'requires_attachment' => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
            'description'         => 'nullable|string',
        ]);

        $validated['code'] = strtolower(trim($validated['code']));
        $validated['is_paid'] = $request->boolean('is_paid');
        $validated['requires_attachment'] = $request->boolean('requires_attachment');
        $validated['is_active'] = $request->boolean('is_active');

        PermitType::create($validated);

        return redirect()->route('permit-types.index')
            ->with('success', 'Jenis izin baru berhasil ditambahkan.');
    }

    /**
     * Perbarui data jenis izin.
     */
    public function update(Request $request, PermitType $permitType)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:150',
            'code'                => 'required|string|max:50|unique:permit_types,code,' . $permitType->id,
            'is_paid'             => 'nullable|boolean',
            'requires_attachment' => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
            'description'         => 'nullable|string',
        ]);

        $validated['code'] = strtolower(trim($validated['code']));
        $validated['is_paid'] = $request->boolean('is_paid');
        $validated['requires_attachment'] = $request->boolean('requires_attachment');
        $validated['is_active'] = $request->boolean('is_active');

        $permitType->update($validated);

        return redirect()->route('permit-types.index')
            ->with('success', 'Data jenis izin berhasil diperbarui.');
    }

    /**
     * Hapus jenis izin jika belum pernah digunakan dalam permohonan.
     */
    public function destroy(PermitType $permitType)
    {
        if ($permitType->leaveRequests()->exists()) {
            return redirect()->back()->withErrors([
                'error' => 'Jenis izin ini sudah pernah digunakan dalam riwayat permohonan izin karyawan dan tidak dapat dihapus. Anda dapat menonaktifkannya.'
            ]);
        }

        $permitType->delete();

        return redirect()->route('permit-types.index')
            ->with('success', 'Jenis izin berhasil dihapus.');
    }
}


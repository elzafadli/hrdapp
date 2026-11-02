<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AttendanceSettingController extends Controller
{
    /**
     * Tampilkan daftar seluruh titik lokasi & radius presensi.
     */
    public function index()
    {
        $hasCompanyCol = Schema::hasColumn('attendance_settings', 'company_id');

        if ($hasCompanyCol) {
            $settings = AttendanceSetting::with(['company', 'project'])
                ->orderByRaw('company_id IS NULL, project_id IS NULL')
                ->get();
        } else {
            $settings = AttendanceSetting::all();
        }

        $companies = Schema::hasTable('companies') ? Company::orderBy('name')->get() : collect();
        $projects = Schema::hasTable('projects') ? Project::with('company')->orderBy('name')->get() : collect();

        return view('attendance_settings.index', compact('settings', 'companies', 'projects', 'hasCompanyCol'));
    }

    /**
     * Simpan pengaturan titik lokasi baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id'           => 'nullable|exists:companies,id',
            'project_id'           => 'nullable|exists:projects,id',
            'office_name'          => 'required|string|max:255',
            'latitude'             => 'required|numeric|between:-90,90',
            'longitude'            => 'required|numeric|between:-180,180',
            'radius_meters'        => 'required|integer|min:10|max:50000',
            'allow_outside_radius' => 'nullable|boolean',
        ]);

        $validated['allow_outside_radius'] = $request->boolean('allow_outside_radius');

        // Jika project dipilih, pastikan project sesuai atau company_id otomatis terisi jika belum ada
        if (!empty($validated['project_id']) && empty($validated['company_id'])) {
            $proj = Project::find($validated['project_id']);
            if ($proj && $proj->company_id) {
                $validated['company_id'] = $proj->company_id;
            }
        }

        AttendanceSetting::create($validated);

        return redirect()->route('attendance-settings.index')
            ->with('success', 'Titik lokasi & radius presensi baru berhasil ditambahkan.');
    }

    /**
     * Perbarui pengaturan titik lokasi.
     */
    public function update(Request $request, $id = null)
    {
        $targetId = $id ?? $request->input('id');
        $setting = $targetId ? AttendanceSetting::findOrFail($targetId) : AttendanceSetting::first();

        if (!$setting) {
            return $this->store($request);
        }

        $validated = $request->validate([
            'company_id'           => 'nullable|exists:companies,id',
            'project_id'           => 'nullable|exists:projects,id',
            'office_name'          => 'required|string|max:255',
            'latitude'             => 'required|numeric|between:-90,90',
            'longitude'            => 'required|numeric|between:-180,180',
            'radius_meters'        => 'required|integer|min:10|max:50000',
            'allow_outside_radius' => 'nullable|boolean',
        ]);

        $validated['allow_outside_radius'] = $request->boolean('allow_outside_radius');

        if (!empty($validated['project_id']) && empty($validated['company_id'])) {
            $proj = Project::find($validated['project_id']);
            if ($proj && $proj->company_id) {
                $validated['company_id'] = $proj->company_id;
            }
        }

        $setting->update($validated);

        return redirect()->route('attendance-settings.index')
            ->with('success', 'Titik lokasi & radius presensi berhasil diperbarui.');
    }

    /**
     * Hapus pengaturan titik lokasi.
     */
    public function destroy($id)
    {
        $setting = AttendanceSetting::findOrFail($id);
        $setting->delete();

        return redirect()->route('attendance-settings.index')
            ->with('success', 'Titik lokasi presensi berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\EmpData;
use App\Models\PayrollComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EssProfileController extends Controller
{
    /**
     * Tampilkan profil karyawan ESS untuk user yang sedang login (ambil dari session).
     * Bersifat Read-Only (tanpa tombol edit) & responsif optimal untuk mobile.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->loadMissing(['employee.jabatan', 'employee.company', 'employee.project.company']);
        }

        $employee = $user?->employee;

        // Fallback untuk superadmin / user tanpa link employee
        if (!$employee) {
            // Cek apakah ada record employee sama sekali untuk preview admin jika diperlukan
            $firstEmp = EmpData::with(['jabatan', 'company', 'project.company', 'user'])->first();

            return view('ess.profile.unlinked', [
                'user' => $user,
                'sampleEmployee' => $firstEmp,
            ]);
        }

        // Load relasi lengkap profil
        $relations = ['jabatan', 'company', 'project.company', 'user'];
        if (Schema::hasTable('emp_careers')) {
            $relations[] = 'careers';
        }
        if (Schema::hasTable('emp_educations')) {
            $relations[] = 'educations';
        }
        if (Schema::hasTable('emp_experiences')) {
            $relations[] = 'experiences';
        }
        if (Schema::hasTable('emp_trainings')) {
            $relations[] = 'trainings';
        }
        if (Schema::hasTable('emp_bpjs')) {
            $relations[] = 'bpjs.payrollComponent';
        }
        $employee->loadMissing($relations);

        // Komponen BPJS
        $bpjsComponents = collect();
        if (Schema::hasTable('payroll_components')) {
            $bpjsComponents = PayrollComponent::where('type', 'subsidi')->orderBy('id', 'asc')->get();
        }

        return view('ess.profile.index', compact('employee', 'bpjsComponents', 'user'));
    }

    /**
     * Update data karyawan mandiri oleh user ESS (khusus Data Karyawan).
     */
    public function updatePersonal(Request $request)
    {
        $user = Auth::user();
        $employee = $user?->employee;

        if (!$employee) {
            return redirect()->route('ess.profile')->with('error', 'Data karyawan belum terhubung ke akun Anda.');
        }

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'photo'                 => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gender'                => 'nullable|in:male,female',
            'citizenship'           => 'nullable|string|max:50',
            'nik_ktp'               => 'nullable|string|max:50',
            'birth_place'           => 'nullable|string|max:100',
            'birth_date'            => 'nullable|date',
            'marital_status'        => 'nullable|string|max:50',
            'religion'              => 'nullable|string|max:50',
            'blood_type'            => 'nullable|string|max:10',
            'last_education'        => 'nullable|string|max:50',
            'education_institution' => 'nullable|string|max:255',
        ], [
            'name.required'   => 'Nama Lengkap wajib diisi.',
            'photo.image'     => 'Berkas foto profil harus berupa file gambar.',
            'photo.max'       => 'Ukuran foto profil maksimal 2 MB.',
            'birth_date.date' => 'Format tanggal lahir tidak valid.',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $folder = 'employee_photos/' . date('Y') . '/' . date('m');
            try {
                $validated['photo'] = $file->store($folder, 'is3');
            } catch (\Throwable $e) {
                $validated['photo'] = $file->store($folder, 'public');
            }
        }

        $dataToSave = [];
        foreach ($validated as $key => $value) {
            if ($key === 'photo' && !$request->hasFile('photo')) {
                continue;
            }
            if ($value === '') {
                $value = null;
            }
            $dataToSave[$key] = $value;
        }

        // Keamanan: pastikan data sistem dan penugasan HRD tidak bisa dimanipulasi
        unset($dataToSave['nik_karyawan'], $dataToSave['user_id'], $dataToSave['company_id'], $dataToSave['jabatan_id'], $dataToSave['project_id'], $dataToSave['status_ptkp'], $dataToSave['join_date']);

        $employee->update($dataToSave);

        // Sinkronisasi nama user login jika nama karyawan diperbarui
        if (!empty($validated['name']) && $user->name !== $validated['name']) {
            $user->update(['name' => $validated['name']]);
        }

        return redirect()->route('ess.profile')->with('success', 'Data profil karyawan Anda berhasil diperbarui!');
    }
}

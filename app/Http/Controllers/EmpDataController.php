<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmpBpjs;
use App\Models\EmpCareer;
use App\Models\EmpData;
use App\Models\EmpEducation;
use App\Models\EmpExperience;
use App\Models\EmpJabatan;
use App\Models\EmpTraining;
use App\Models\LeaveBalance;
use App\Models\PayrollComponent;
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
            $data = EmpData::with(['jabatan', 'project.company', 'company', 'user'])->select('emp_data.*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('employee_code', function ($row) {
                    $code = $row->nik_karyawan ?: ('#' . $row->id);
                    return '<span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-gray-100 text-gray-800">' . e($code) . '</span>';
                })
                ->addColumn('employee_info', function ($row) {
                    $avatar = $row->photo_url
                        ? '<img src="' . e($row->photo_url) . '" class="w-9 h-9 rounded-full object-cover border border-gray-200 shrink-0">'
                        : '<div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-200">' . strtoupper(substr($row->name, 0, 2)) . '</div>';

                    $subItems = [];
                    if ($row->effective_email) {
                        $subItems[] = e($row->effective_email);
                    }
                    $effectivePhone = $row->effective_phone ?? $row->phone ?? $row->user?->phone;
                    if ($effectivePhone) {
                        $subItems[] = '<span class="inline-flex items-center gap-1 text-slate-600"><i class="fa-solid fa-phone text-[9px] text-gray-400"></i>' . e($effectivePhone) . '</span>';
                    }
                    if (empty($subItems) && $row->nik_ktp) {
                        $subItems[] = 'KTP: ' . e($row->nik_ktp);
                    }

                    $sub = !empty($subItems) ? implode(' &bull; ', $subItems) : '-';

                    return '<div class="flex items-center gap-3">' . $avatar . '<div><div class="font-bold text-gray-900 text-sm">' . e($row->name) . '</div><div class="text-xs text-gray-500 font-mono flex flex-wrap items-center gap-1.5">' . $sub . '</div></div></div>';
                })
                ->addColumn('gender_label', function ($row) {
                    $gender = (Schema::hasColumn('emp_data', 'gender') && $row->gender) ? $row->gender : 'male';
                    return ($gender === 'female')
                        ? '<span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-pink-100 text-pink-700">Perempuan</span>'
                        : '<span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">Laki-laki</span>';
                })
                ->addColumn('company_name', function ($row) {
                    $comp = $row->company ?? $row->project?->company;
                    return $comp ? '<span class="font-mono text-xs font-semibold bg-slate-100 px-2 py-0.5 rounded">' . e($comp->code) . '</span>' : '-';
                })
                ->addColumn('jabatan_name', function ($row) {
                    return $row->jabatan ? e($row->jabatan->jabatan) : '-';
                })
                ->addColumn('action', function ($row) {
                    return '<div class="flex items-center gap-1.5">
                        <a href="' . route('employee.show', $row->id) . '" title="Detail Karyawan" class="inline-flex items-center px-2 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-semibold transition">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="' . route('employee.edit', $row->id) . '" title="Edit Karyawan" class="inline-flex items-center px-2 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md text-xs font-semibold transition">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form action="' . route('employee.destroy', $row->id) . '" method="POST" class="inline-block" onsubmit="return confirm(\'Hapus data karyawan ini?\');">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" title="Hapus Karyawan" class="inline-flex items-center px-2 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold transition">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>';
                })
                ->rawColumns(['employee_code', 'employee_info', 'gender_label', 'company_name', 'action'])
                ->make(true);
        }

        return view('emp_data.index');
    }

    public function show(EmpData $employee)
    {
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

        $bpjsComponents = collect();
        if (Schema::hasTable('payroll_components')) {
            $bpjsComponents = PayrollComponent::where('type', 'subsidi')->orderBy('id', 'asc')->get();
        }

        return view('emp_data.show', compact('employee', 'bpjsComponents'));
    }

    public function create()
    {
        $options = $this->getFormOptions();
        $options['empData'] = new EmpData();
        $options['bpjsComponents'] = Schema::hasTable('payroll_components')
            ? PayrollComponent::where('type', 'subsidi')->orderBy('id', 'asc')->get()
            : collect();
        $options['bpjsData'] = [];

        return view('emp_data.form', $options);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'               => 'nullable',
            'email'                 => 'nullable|email|max:255',
            'phone'                 => 'nullable|string|max:50',
            'name'                  => 'required|string|max:255',
            'nik_karyawan'          => 'nullable|string|max:50',
            'photo'                 => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gender'                => 'nullable|in:male,female',
            'citizenship'           => 'nullable|string|max:20',
            'nik_ktp'               => 'nullable|string|max:50',
            'birth_place'           => 'nullable|string|max:100',
            'birth_date'            => 'nullable|date',
            'marital_status'        => 'nullable|string|max:50',
            'religion'              => 'nullable|string|max:50',
            'blood_type'            => 'nullable|string|max:10',
            'last_education'        => 'nullable|string|max:50',
            'education_institution' => 'nullable|string|max:255',
            'company_id'            => 'nullable|exists:companies,id',
            'jabatan_id'            => 'required|exists:emp_jabatan,id',
            'project_id'            => 'nullable|exists:projects,id',
            'status_ptkp'           => 'required|in:TK0,TK1,TK2,TK3,K0,K1,K2,K3',
            'join_date'             => 'nullable|date',
        ], [
            'name.required'        => 'Nama Lengkap (Sesuai KTP) wajib diisi.',
            'photo.image'          => 'Berkas foto profil harus berupa file gambar.',
            'photo.max'            => 'Ukuran foto profil maksimal 2 MB.',
            'birth_date.date'      => 'Format tanggal lahir tidak valid.',
            'join_date.date'       => 'Format tanggal mulai kerja tidak valid.',
            'jabatan_id.required'  => 'Jabatan karyawan wajib dipilih.',
            'status_ptkp.required' => 'Status PTKP karyawan wajib dipilih.',
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

        // Simpan hanya kolom yang sudah tersedia di database agar aman sebelum migrate dijalankan
        $dataToSave = [];
        foreach ($validated as $key => $value) {
            if ($key === 'photo' && !$request->hasFile('photo')) {
                continue;
            }
            if (Schema::hasColumn('emp_data', $key)) {
                $dataToSave[$key] = $value;
            }
        }

        if (Schema::hasColumn('emp_data', 'gender')) {
            $dataToSave['gender'] = $dataToSave['gender'] ?? 'male';
        }

        if (Schema::hasColumn('emp_data', 'user_id')) {
            $dataToSave['user_id'] = !empty($validated['user_id']) ? $validated['user_id'] : null;
            if (empty($dataToSave['email']) && !empty($dataToSave['user_id'])) {
                $linkedUser = User::find($dataToSave['user_id']);
                if ($linkedUser && Schema::hasColumn('emp_data', 'email')) {
                    $dataToSave['email'] = $linkedUser->email;
                }
            }
        }

        if (Schema::hasColumn('emp_data', 'jabatan_id') && empty($dataToSave['jabatan_id'])) {
            $fallbackJabatan = \App\Models\EmpJabatan::first();
            $dataToSave['jabatan_id'] = $fallbackJabatan?->id ?? 1;
        }

        if (Schema::hasColumn('emp_data', 'status_ptkp') && empty($dataToSave['status_ptkp'])) {
            $dataToSave['status_ptkp'] = 'TK0';
        }

        $employee = EmpData::create($dataToSave);

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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'       => true,
                'message'       => 'Data Karyawan berhasil disimpan. Tab Karir, Lama Bekerja, Pendidikan, Penugasan & BPJS kini telah aktif.',
                'employee'      => $employee,
                'employee_id'   => $employee->id,
                'employee_name' => $employee->name,
                'photo_url'     => $employee->photo_url,
                'is_new'        => true,
                'edit_url'      => route('employee.edit', $employee),
                'show_url'      => route('employee.show', $employee),
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'data-karyawan'])
            ->with('success', 'Data Karyawan berhasil disimpan. Tab Karir dan Lama Bekerja kini telah aktif.');
    }

    public function edit(EmpData $employee)
    {
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

        $options = $this->getFormOptions();
        $options['empData'] = $employee;
        $options['bpjsComponents'] = Schema::hasTable('payroll_components')
            ? PayrollComponent::where('type', 'subsidi')->orderBy('id', 'asc')->get()
            : collect();
        $options['bpjsData'] = (Schema::hasTable('emp_bpjs'))
            ? $employee->bpjs->pluck('value', 'payroll_component_id')->toArray()
            : [];

        return view('emp_data.form', $options);
    }

    public function update(Request $request, EmpData $employee)
    {
        $validated = $request->validate([
            'user_id'               => 'nullable',
            'email'                 => 'nullable|email|max:255',
            'phone'                 => 'nullable|string|max:50',
            'name'                  => 'required|string|max:255',
            'nik_karyawan'          => 'nullable|string|max:50',
            'photo'                 => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gender'                => 'nullable|in:male,female',
            'citizenship'           => 'nullable|string|max:20',
            'nik_ktp'               => 'nullable|string|max:50',
            'birth_place'           => 'nullable|string|max:100',
            'birth_date'            => 'nullable|date',
            'marital_status'        => 'nullable|string|max:50',
            'religion'              => 'nullable|string|max:50',
            'blood_type'            => 'nullable|string|max:10',
            'last_education'        => 'nullable|string|max:50',
            'education_institution' => 'nullable|string|max:255',
            'company_id'            => 'nullable|exists:companies,id',
            'jabatan_id'            => 'required|exists:emp_jabatan,id',
            'project_id'            => 'nullable|exists:projects,id',
            'status_ptkp'           => 'required|in:TK0,TK1,TK2,TK3,K0,K1,K2,K3',
            'join_date'             => 'nullable|date',
        ], [
            'name.required'        => 'Nama Lengkap (Sesuai KTP) wajib diisi.',
            'photo.image'          => 'Berkas foto profil harus berupa file gambar.',
            'photo.max'            => 'Ukuran foto profil maksimal 2 MB.',
            'birth_date.date'      => 'Format tanggal lahir tidak valid.',
            'join_date.date'       => 'Format tanggal mulai kerja tidak valid.',
            'jabatan_id.required'  => 'Jabatan karyawan wajib dipilih.',
            'status_ptkp.required' => 'Status PTKP karyawan wajib dipilih.',
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
            if (in_array($key, ['company_id', 'jabatan_id', 'project_id', 'status_ptkp']) && !$request->has($key)) {
                continue;
            }
            if (Schema::hasColumn('emp_data', $key)) {
                $dataToSave[$key] = $value;
            }
        }

        if (Schema::hasColumn('emp_data', 'gender')) {
            $dataToSave['gender'] = $dataToSave['gender'] ?? 'male';
        }

        if (Schema::hasColumn('emp_data', 'user_id')) {
            $dataToSave['user_id'] = !empty($validated['user_id']) ? $validated['user_id'] : null;
        }

        $employee->update($dataToSave);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'       => true,
                'message'       => 'Data Karyawan berhasil diperbarui.',
                'employee'      => $employee,
                'photo_url'     => $employee->photo_url,
                'employee_name' => $employee->name,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'data-karyawan'])
            ->with('success', 'Data Karyawan berhasil diperbarui.');
    }

    public function destroy(EmpData $employee)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($employee) {
                // Hapus data anak payroll results yang tidak memiliki cascade on delete di level DB
                $employee->payrollResults()->delete();
                $employee->delete();
            });

            return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->route('employee.index')->with('error', 'Gagal menghapus data karyawan: ' . $e->getMessage());
        }
    }

    /**
     * Simpan data riwayat karir (bisa multi).
     */
    public function storeCareer(Request $request, EmpData $employee)
    {
        $validated = $request->validate([
            'status_karyawan'  => 'required|string|max:50',
            'penempatan_kerja' => 'required|string|max:100',
            'tanggal_efektif'  => 'required|date',
            'organisasi'       => 'nullable|string|max:100',
            'pangkat'          => 'nullable|string|max:100',
            'jabatan'          => 'nullable|string|max:100',
            'notes'            => 'nullable|string',
        ]);

        $validated['emp_id'] = $employee->id;
        $validated['is_current'] = true;

        $career = null;
        if (Schema::hasTable('emp_careers')) {
            // Set karir sebelumnya menjadi non-current jika ini posisi terbaru
            EmpCareer::where('emp_id', $employee->id)->update(['is_current' => false]);
            $career = EmpCareer::create($validated);
        }

        // Jika join_date employee belum ada, set otomatis dari tanggal_efektif awal
        if (Schema::hasColumn('emp_data', 'join_date') && empty($employee->join_date)) {
            $employee->update(['join_date' => $validated['tanggal_efektif']]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat Karir berhasil ditambahkan.',
                'career'  => $career,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'karir'])
            ->with('success', 'Riwayat Karir berhasil ditambahkan.');
    }

    /**
     * Hapus satu data riwayat karir.
     */
    public function destroyCareer(Request $request, EmpData $employee, EmpCareer $career)
    {
        if ($career->emp_id === $employee->id) {
            $career->delete();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat karir berhasil dihapus.',
                'id'      => $career->id,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'karir'])
            ->with('success', 'Riwayat karir berhasil dihapus.');
    }

    /**
     * Perbarui data riwayat karir.
     */
    public function updateCareer(Request $request, EmpData $employee, EmpCareer $career)
    {
        if ($career->emp_id !== $employee->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status_karyawan'  => 'required|string|max:50',
            'penempatan_kerja' => 'required|string|max:100',
            'tanggal_efektif'  => 'required|date',
            'organisasi'       => 'nullable|string|max:100',
            'pangkat'          => 'nullable|string|max:100',
            'jabatan'          => 'nullable|string|max:100',
            'notes'            => 'nullable|string',
        ]);

        $career->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat Karir berhasil diperbarui.',
                'career'  => $career->fresh(),
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'karir'])
            ->with('success', 'Riwayat Karir berhasil diperbarui.');
    }

    /**
     * Update data masa / lama bekerja (join_date).
     */
    public function updateTenure(Request $request, EmpData $employee)
    {
        $validated = $request->validate([
            'join_date' => 'required|date',
        ]);

        if (Schema::hasColumn('emp_data', 'join_date')) {
            $employee->update(['join_date' => $validated['join_date']]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'               => true,
                'message'               => 'Data Lama Bekerja berhasil diperbarui.',
                'join_date'             => $validated['join_date'],
                'formatted_join_date'   => $employee->fresh()->formatted_join_date,
                'tenure_display_string' => $employee->fresh()->tenure_display_string,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'lama-bekerja'])
            ->with('success', 'Data Lama Bekerja berhasil diperbarui.');
    }

    /**
     * Simpan data riwayat pendidikan (bisa multi).
     */
    public function storeEducation(Request $request, EmpData $employee)
    {
        $validated = $request->validate([
            'jenjang'     => 'required|string|max:50',
            'institusi'   => 'required|string|max:255',
            'jurusan'     => 'nullable|string|max:100',
            'tahun_masuk' => 'nullable|string|max:10',
            'tahun_lulus' => 'nullable|string|max:10',
            'nilai'       => 'nullable|string|max:20',
            'notes'       => 'nullable|string',
        ]);

        $validated['emp_id'] = $employee->id;

        $education = null;
        if (Schema::hasTable('emp_educations')) {
            $education = EmpEducation::create($validated);
        }

        // Sinkronisasi field ringkasan pendidikan terakhir di emp_data jika ada
        $dataToSync = [];
        if (Schema::hasColumn('emp_data', 'last_education')) {
            $dataToSync['last_education'] = $validated['jenjang'];
        }
        if (Schema::hasColumn('emp_data', 'education_institution')) {
            $dataToSync['education_institution'] = $validated['institusi'];
        }
        if (!empty($dataToSync)) {
            $employee->update($dataToSync);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Riwayat Pendidikan berhasil ditambahkan.',
                'education' => $education,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pendidikan'])
            ->with('success', 'Riwayat Pendidikan berhasil ditambahkan.');
    }

    /**
     * Hapus data riwayat pendidikan.
     */
    public function destroyEducation(Request $request, EmpData $employee, EmpEducation $education)
    {
        if ($education->emp_id === $employee->id) {
            $education->delete();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pendidikan berhasil dihapus.',
                'id'      => $education->id,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pendidikan'])
            ->with('success', 'Riwayat pendidikan berhasil dihapus.');
    }

    /**
     * Perbarui data riwayat pendidikan.
     */
    public function updateEducation(Request $request, EmpData $employee, EmpEducation $education)
    {
        if ($education->emp_id !== $employee->id) {
            abort(404);
        }

        $validated = $request->validate([
            'jenjang'     => 'required|string|max:50',
            'institusi'   => 'required|string|max:255',
            'jurusan'     => 'nullable|string|max:100',
            'tahun_masuk' => 'nullable|string|max:10',
            'tahun_lulus' => 'nullable|string|max:10',
            'nilai'       => 'nullable|string|max:20',
            'notes'       => 'nullable|string',
        ]);

        $education->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Riwayat Pendidikan berhasil diperbarui.',
                'education' => $education->fresh(),
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pendidikan'])
            ->with('success', 'Riwayat Pendidikan berhasil diperbarui.');
    }

    /**
     * Simpan data riwayat pengalaman kerja (bisa multi).
     */
    public function storeExperience(Request $request, EmpData $employee)
    {
        $validated = $request->validate([
            'perusahaan'          => 'required|string|max:255',
            'jabatan'             => 'required|string|max:150',
            'tanggal_mulai'       => 'nullable|date',
            'tanggal_selesai'     => 'nullable|date',
            'is_current'          => 'nullable|boolean',
            'gaji_terakhir'       => 'nullable|string|max:50',
            'deskripsi_pekerjaan' => 'nullable|string',
            'alasan_berhenti'     => 'nullable|string',
        ]);

        $validated['emp_id'] = $employee->id;
        $validated['is_current'] = $request->boolean('is_current');

        $experience = null;
        if (Schema::hasTable('emp_experiences')) {
            $experience = EmpExperience::create($validated);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => 'Riwayat Pengalaman Kerja berhasil ditambahkan.',
                'experience' => $experience,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pengalaman-kerja'])
            ->with('success', 'Riwayat Pengalaman Kerja berhasil ditambahkan.');
    }

    /**
     * Hapus data riwayat pengalaman kerja.
     */
    public function destroyExperience(Request $request, EmpData $employee, EmpExperience $experience)
    {
        if ($experience->emp_id === $employee->id) {
            $experience->delete();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pengalaman kerja berhasil dihapus.',
                'id'      => $experience->id,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pengalaman-kerja'])
            ->with('success', 'Riwayat pengalaman kerja berhasil dihapus.');
    }

    /**
     * Perbarui data riwayat pengalaman kerja.
     */
    public function updateExperience(Request $request, EmpData $employee, EmpExperience $experience)
    {
        if ($experience->emp_id !== $employee->id) {
            abort(404);
        }

        $validated = $request->validate([
            'perusahaan'          => 'required|string|max:255',
            'jabatan'             => 'required|string|max:150',
            'tanggal_mulai'       => 'nullable|date',
            'tanggal_selesai'     => 'nullable|date',
            'is_current'          => 'nullable|boolean',
            'gaji_terakhir'       => 'nullable|string|max:50',
            'deskripsi_pekerjaan' => 'nullable|string',
            'alasan_berhenti'     => 'nullable|string',
        ]);

        $validated['is_current'] = $request->boolean('is_current');
        $experience->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => 'Riwayat Pengalaman Kerja berhasil diperbarui.',
                'experience' => $experience->fresh(),
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pengalaman-kerja'])
            ->with('success', 'Riwayat Pengalaman Kerja berhasil diperbarui.');
    }

    /**
     * Simpan data pelatihan, sertifikasi, & skill (bisa multi).
     */
    public function storeTraining(Request $request, EmpData $employee)
    {
        $validated = $request->validate([
            'nama_pelatihan'   => 'required|string|max:255',
            'penyelenggara'    => 'required|string|max:255',
            'tahun'            => 'nullable|string|max:20',
            'nomor_sertifikat' => 'nullable|string|max:100',
            'masa_berlaku'     => 'nullable|string|max:50',
            'deskripsi'        => 'nullable|string',
        ]);

        $validated['emp_id'] = $employee->id;

        $training = null;
        if (Schema::hasTable('emp_trainings')) {
            $training = EmpTraining::create($validated);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Data Pelatihan & Keahlian berhasil ditambahkan.',
                'training' => $training,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pelatihan-skill'])
            ->with('success', 'Data Pelatihan & Keahlian berhasil ditambahkan.');
    }

    /**
     * Hapus data pelatihan & sertifikasi.
     */
    public function destroyTraining(Request $request, EmpData $employee, EmpTraining $training)
    {
        if ($training->emp_id === $employee->id) {
            $training->delete();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pelatihan & sertifikasi berhasil dihapus.',
                'id'      => $training->id,
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pelatihan-skill'])
            ->with('success', 'Data pelatihan & sertifikasi berhasil dihapus.');
    }

    /**
     * Perbarui data pelatihan & sertifikasi.
     */
    public function updateTraining(Request $request, EmpData $employee, EmpTraining $training)
    {
        if ($training->emp_id !== $employee->id) {
            abort(404);
        }

        $validated = $request->validate([
            'nama_pelatihan'   => 'required|string|max:255',
            'penyelenggara'    => 'required|string|max:255',
            'tahun'            => 'nullable|string|max:20',
            'nomor_sertifikat' => 'nullable|string|max:100',
            'masa_berlaku'     => 'nullable|string|max:50',
            'deskripsi'        => 'nullable|string',
        ]);

        $training->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Data Pelatihan & Keahlian berhasil diperbarui.',
                'training' => $training->fresh(),
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'pelatihan-skill'])
            ->with('success', 'Data Pelatihan & Keahlian berhasil diperbarui.');
    }

    /**
     * Update data penugasan & status payroll karyawan.
     */
    public function updateAssignment(Request $request, EmpData $employee)
    {
        $validated = $request->validate([
            'company_id'  => 'nullable|exists:companies,id',
            'jabatan_id'  => 'required|exists:emp_jabatan,id',
            'project_id'  => 'nullable|exists:projects,id',
            'status_ptkp' => 'required|in:TK0,TK1,TK2,TK3,K0,K1,K2,K3',
        ], [
            'jabatan_id.required'  => 'Jabatan karyawan wajib dipilih.',
            'status_ptkp.required' => 'Status PTKP karyawan wajib dipilih.',
        ]);

        $employee->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan & Status Payroll berhasil diperbarui.',
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'penugasan-payroll'])
            ->with('success', 'Penugasan & Status Payroll berhasil diperbarui.');
    }

    /**
     * Update data BPJS karyawan secara mandiri di tab BPJS.
     */
    public function updateBpjs(Request $request, EmpData $employee)
    {
        $request->validate([
            'bpjs'   => 'nullable|array',
            'bpjs.*' => 'nullable|string|max:100',
        ]);

        $bpjsInput = $request->input('bpjs', []);

        if (Schema::hasTable('emp_bpjs')) {
            foreach ($bpjsInput as $componentId => $value) {
                if ($value !== null && trim((string)$value) !== '') {
                    EmpBpjs::updateOrCreate(
                        [
                            'employee_id'          => $employee->id,
                            'payroll_component_id' => $componentId,
                        ],
                        [
                            'value' => trim((string)$value),
                        ]
                    );
                } else {
                    EmpBpjs::where('employee_id', $employee->id)
                        ->where('payroll_component_id', $componentId)
                        ->delete();
                }
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data BPJS karyawan berhasil diperbarui.',
            ]);
        }

        return redirect()->route('employee.edit', ['employee' => $employee, 'tab' => 'bpjs'])
            ->with('success', 'Data BPJS karyawan berhasil diperbarui.');
    }

    protected function getFormOptions(): array
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

        $citizenshipOptions = [
            'WNI' => 'WNI (Warga Negara Indonesia)',
            'WNA' => 'WNA (Warga Negara Asing)',
        ];

        $maritalStatusOptions = [
            'Belum Menikah' => 'Belum Menikah',
            'Menikah'       => 'Menikah',
            'Cerai Hidup'   => 'Cerai Hidup',
            'Cerai Mati'    => 'Cerai Mati',
        ];

        $religionOptions = [
            'Islam'             => 'Islam',
            'Kristen Protestan' => 'Kristen Protestan',
            'Katolik'           => 'Katolik',
            'Hindu'             => 'Hindu',
            'Buddha'            => 'Buddha',
            'Khonghucu'         => 'Khonghucu',
        ];

        $bloodTypeOptions = [
            '-'  => '-',
            'A'  => 'A',
            'B'  => 'B',
            'AB' => 'AB',
            'O'  => 'O',
        ];

        $educationLevelOptions = [
            '-'       => '-',
            'SD'      => 'SD / Sederajat',
            'SMP'     => 'SMP / Sederajat',
            'SMA/SMK' => 'SMA / SMK / Sederajat',
            'D1'      => 'Diploma 1 (D1)',
            'D2'      => 'Diploma 2 (D2)',
            'D3'      => 'Diploma 3 (D3)',
            'D4/S1'   => 'Sarjana (S1 / D4)',
            'S2'      => 'Magister (S2)',
            'S3'      => 'Doktor (S3)',
            'Lainnya' => 'Lainnya',
        ];

        $statusKaryawanOptions = [
            'PKWT'         => 'PKWT (Perjanjian Kerja Waktu Tertentu)',
            'PKWTT'        => 'PKWTT (Karyawan Tetap)',
            'Probation'    => 'Probation (Masa Percobaan)',
            'Internship'   => 'Internship / Magang',
            'Freelance'    => 'Freelance / Mitra',
            'Harian Lepas' => 'Harian Lepas',
        ];

        $penempatanKerjaOptions = [
            'Baru Direkrut' => 'Baru Direkrut',
            'Promosi'       => 'Promosi',
            'Mutasi'        => 'Mutasi',
            'Demosi'        => 'Demosi',
            'Rotasi'        => 'Rotasi',
            'Penyesuaian'   => 'Penyesuaian',
        ];

        $pangkatOptions = [
            'Staff'             => 'Staff',
            'Senior Staff'      => 'Senior Staff',
            'Supervisor'        => 'Supervisor',
            'Assistant Manager' => 'Assistant Manager',
            'Manager'           => 'Manager',
            'General Manager'   => 'General Manager',
            'Director'          => 'Director',
        ];

        $organisasiOptions = [
            'IT'                   => 'IT (Information Technology)',
            'HRD'                  => 'HRD (Human Resource Department)',
            'Finance & Accounting' => 'Finance & Accounting',
            'Operational'          => 'Operational',
            'Marketing & Sales'    => 'Marketing & Sales',
            'General Affairs'      => 'General Affairs',
            'Management'           => 'Management',
        ];

        $ptkpOptions = [
            'TK0' => 'TK/0 - Tidak Kawin, 0 Tanggungan (TER A)',
            'TK1' => 'TK/1 - Tidak Kawin, 1 Tanggungan (TER A)',
            'TK2' => 'TK/2 - Tidak Kawin, 2 Tanggungan (TER B)',
            'TK3' => 'TK/3 - Tidak Kawin, 3 Tanggungan (TER B)',
            'K0'  => 'K/0 - Kawin, 0 Tanggungan (TER A)',
            'K1'  => 'K/1 - Kawin, 1 Tanggungan (TER B)',
            'K2'  => 'K/2 - Kawin, 2 Tanggungan (TER B)',
            'K3'  => 'K/3 - Kawin, 3 Tanggungan (TER C)',
        ];

        return [
            'userOptions'            => $userOptions,
            'companyOptions'         => $companyOptions,
            'genderOptions'          => $genderOptions,
            'citizenshipOptions'     => $citizenshipOptions,
            'maritalStatusOptions'   => $maritalStatusOptions,
            'religionOptions'        => $religionOptions,
            'bloodTypeOptions'       => $bloodTypeOptions,
            'educationLevelOptions'  => $educationLevelOptions,
            'statusKaryawanOptions'  => $statusKaryawanOptions,
            'penempatanKerjaOptions' => $penempatanKerjaOptions,
            'pangkatOptions'         => $pangkatOptions,
            'organisasiOptions'      => $organisasiOptions,
            'jabatanOptions'         => $jabatanOptions,
            'projects'               => $projects,
            'projectOptions'         => $projectOptions,
            'ptkpOptions'            => $ptkpOptions,
        ];
    }
}

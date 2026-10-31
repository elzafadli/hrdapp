<?php

namespace App\Http\Controllers;

use App\Models\ApprovalSetting;
use App\Models\ApprovalSettingDetail;
use App\Models\EmpJabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class ApprovalSettingController extends Controller
{
    /**
     * Label modul dalam format bahasa Indonesia yang mudah dibaca.
     */
    public static function moduleLabels(): array
    {
        return [
            'leave'         => 'Cuti Tahunan (CT)',
            'special_leave' => 'Cuti Khusus (CK)',
            'permit'        => 'Izin (IZ)',
            'sick'          => 'Sakit (SK)',
            'overtime'      => 'Lembur (SPL)',
            'all'           => 'Semua Modul ESS (Global)',
        ];
    }

    /**
     * Tampilkan daftar alur approval Header - Detail.
     */
    public function index(Request $request)
    {
        $hasDetailsTable = Schema::hasTable('approval_setting_details');

        if ($request->ajax()) {
            if ($hasDetailsTable) {
                $query = ApprovalSetting::with(['requesterJabatan', 'details.approverJabatan'])->latest('id');

                if ($request->filled('module_filter') && $request->module_filter !== 'all_filter') {
                    $query->where('module', $request->module_filter);
                }

                if ($request->filled('jabatan_filter') && $request->jabatan_filter !== 'all_filter') {
                    if ($request->jabatan_filter === 'default_only') {
                        $query->whereNull('requester_jabatan_id');
                    } else {
                        $query->where('requester_jabatan_id', $request->jabatan_filter);
                    }
                }

                $labels = self::moduleLabels();

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->addColumn('module_badge', function ($row) use ($labels) {
                        $key = $row->module;
                        $label = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                        $colors = [
                            'all'           => 'bg-purple-50 text-purple-700 border-purple-200',
                            'leave'         => 'bg-blue-50 text-blue-700 border-blue-200',
                            'special_leave' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'permit'        => 'bg-amber-50 text-amber-700 border-amber-200',
                            'sick'          => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'overtime'      => 'bg-rose-50 text-rose-700 border-rose-200',
                        ];
                        $cls = $colors[$key] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                        return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold border ' . $cls . '">
                            <i class="fa-solid fa-folder-tree text-[11px]"></i> ' . e($label) . '
                        </span>';
                    })
                    ->addColumn('requester_jabatan', function ($row) {
                        if ($row->requester_jabatan_id && $row->requesterJabatan) {
                            return '<div class="space-y-0.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 text-white shadow-xs">
                                    <i class="fa-solid fa-user-tag text-[10px] text-amber-400"></i> ' . e($row->requesterJabatan->jabatan) . '
                                </span>
                                <span class="block text-[11px] text-gray-400">Khusus Karyawan ini</span>
                            </div>';
                        }
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                            <i class="fa-solid fa-users text-[10px] text-gray-400"></i> Semua Jabatan (Default)
                        </span>';
                    })
                    ->addColumn('workflow_pipeline', function ($row) {
                        $details = $row->details->sortBy('level');
                        if ($details->isEmpty()) {
                            return '<span class="text-xs text-gray-400 italic">Belum ada tahapan</span>';
                        }

                        $html = '<div class="flex items-center gap-1.5 flex-wrap py-1">';
                        foreach ($details as $idx => $step) {
                            $jabName = $step->approverJabatan ? $step->approverJabatan->jabatan : 'Jabatan #' . $step->approver_jabatan_id;
                            $levelBg = $step->level === 1 ? 'bg-blue-600' : ($step->level === 2 ? 'bg-indigo-600' : 'bg-emerald-600');

                            $html .= '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-200 text-xs shadow-xs">';
                            $html .= '<span class="w-4 h-4 rounded-full ' . $levelBg . ' text-white flex items-center justify-center text-[10px] font-extrabold shrink-0">' . $step->level . '</span>';
                            $html .= '<span class="font-bold text-gray-800">' . e($jabName) . '</span>';
                            $html .= '</div>';

                            if ($idx < $details->count() - 1) {
                                $html .= '<i class="fa-solid fa-arrow-right text-[10px] text-gray-300"></i>';
                            }
                        }
                        $html .= '</div>';
                        return $html;
                    })
                    ->addColumn('total_levels', function ($row) {
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            <i class="fa-solid fa-layer-group text-[10px]"></i> ' . $row->details->count() . ' Tingkat
                        </span>';
                    })
                    ->addColumn('description_text', function ($row) {
                        return $row->description ? '<span class="text-xs text-gray-600">' . e($row->description) . '</span>' : '<span class="text-gray-400 text-xs">-</span>';
                    })
                    ->addColumn('status_badge', function ($row) {
                        if ($row->is_active) {
                            return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                <i class="fa-solid fa-circle-check text-[10px]"></i> Aktif
                            </span>';
                        }
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                            <i class="fa-solid fa-circle-xmark text-[10px]"></i> Nonaktif
                        </span>';
                    })
                    ->addColumn('action', function ($row) {
                        return '<div class="inline-flex items-center gap-1.5">
                            <a href="' . route('approval-settings.edit', $row->id) . '"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-xs hover:bg-gray-50 hover:text-blue-600 transition" title="Edit Alur">
                                <i class="fa-solid fa-pen-to-square text-[11px] text-amber-500"></i> Edit
                            </a>
                            <form action="' . route('approval-settings.destroy', $row->id) . '" method="POST" class="inline" onsubmit="return confirm(\'Apakah Anda yakin ingin menghapus alur approval ini?\');">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-rose-700 bg-white border border-rose-200 rounded-lg shadow-xs hover:bg-rose-50 transition" title="Hapus Alur">
                                    <i class="fa-solid fa-trash-can text-[11px]"></i>
                                </button>
                            </form>
                        </div>';
                    })
                    ->rawColumns(['module_badge', 'requester_jabatan', 'workflow_pipeline', 'total_levels', 'description_text', 'status_badge', 'action'])
                    ->make(true);
            }

            // Fallback skema legacy (jika migration belum dieksekusi)
            return DataTables::of(collect([]))->make(true);
        }

        $modules = self::moduleLabels();
        $jabatans = EmpJabatan::orderBy('jabatan')->get();

        return view('approval_settings.index', compact('modules', 'jabatans', 'hasDetailsTable'));
    }

    /**
     * Form tambah konfigurasi alur baru (Header - Detail).
     */
    public function create()
    {
        $approvalSetting = new ApprovalSetting();
        $modules = self::moduleLabels();
        $jabatans = EmpJabatan::orderBy('jabatan')->get();

        // Default: 1 level awal
        $levels = [
            [
                'level'         => 1,
                'jabatan_id'    => $jabatans->first()?->id ?? 1,
                'min_approvers' => 1,
                'description'   => 'Persetujuan Level 1: Atasan Langsung',
            ]
        ];

        return view('approval_settings.form', [
            'approvalSetting' => $approvalSetting,
            'modules'         => $modules,
            'jabatans'        => $jabatans,
            'levels'          => $levels,
            'isEdit'          => false,
        ]);
    }

    /**
     * Simpan alur baru Header - Detail.
     */
    public function store(Request $request)
    {
        $request->validate([
            'module'                 => 'required|string|max:50',
            'requester_jabatan_id'   => 'nullable|exists:emp_jabatan,id',
            'description'            => 'nullable|string|max:255',
            'is_active'              => 'nullable|boolean',
            'levels'                 => 'required|array|min:1',
            'levels.*.jabatan_id'    => 'required|exists:emp_jabatan,id',
            'levels.*.min_approvers' => 'nullable|integer|min:1',
            'levels.*.description'   => 'nullable|string|max:255',
        ], [
            'module.required'        => 'Modul ESS wajib dipilih.',
            'levels.required'        => 'Minimal harus menambahkan 1 tahapan level approver.',
            'levels.min'             => 'Minimal harus menambahkan 1 tahapan level approver.',
            'levels.*.jabatan_id.required' => 'Jabatan approver pada setiap tahapan wajib dipilih.',
        ]);

        // Cek duplikasi aturan (Modul + Jabatan Pemohon yang sama)
        $existing = ApprovalSetting::where('module', $request->module)
            ->where(function ($q) use ($request) {
                if ($request->filled('requester_jabatan_id')) {
                    $q->where('requester_jabatan_id', $request->requester_jabatan_id);
                } else {
                    $q->whereNull('requester_jabatan_id');
                }
            })->first();

        if ($existing) {
            return back()->withInput()->with('error', 'Konfigurasi alur approval untuk kombinasi modul dan jabatan pemohon tersebut sudah terdaftar. Silakan edit aturan yang sudah ada.');
        }

        DB::transaction(function () use ($request) {
            $setting = ApprovalSetting::create([
                'module'               => $request->module,
                'requester_jabatan_id' => $request->filled('requester_jabatan_id') ? $request->requester_jabatan_id : null,
                'description'          => $request->description,
                'is_active'            => $request->has('is_active') ? 1 : 0,
            ]);

            foreach ($request->levels as $idx => $lvl) {
                $setting->details()->create([
                    'level'               => $idx + 1,
                    'approver_jabatan_id' => $lvl['jabatan_id'],
                    'description'         => $lvl['description'] ?? ('Persetujuan Level ' . ($idx + 1)),
                    'min_approvers'       => $lvl['min_approvers'] ?? 1,
                ]);
            }
        });

        return redirect()->route('approval-settings.index')->with('success', 'Konfigurasi alur approval baru berhasil disimpan.');
    }

    /**
     * Form edit alur Header - Detail.
     */
    public function edit(ApprovalSetting $approvalSetting)
    {
        $approvalSetting->load(['requesterJabatan', 'details.approverJabatan']);

        $modules = self::moduleLabels();
        $jabatans = EmpJabatan::orderBy('jabatan')->get();

        $levels = $approvalSetting->details->map(function ($d) {
            return [
                'level'         => $d->level,
                'jabatan_id'    => $d->approver_jabatan_id,
                'min_approvers' => $d->min_approvers ?? 1,
                'description'   => $d->description,
            ];
        })->values()->all();

        if (empty($levels)) {
            $levels = [
                [
                    'level'         => 1,
                    'jabatan_id'    => $jabatans->first()?->id ?? 1,
                    'min_approvers' => 1,
                    'description'   => 'Persetujuan Level 1',
                ]
            ];
        }

        return view('approval_settings.form', [
            'approvalSetting' => $approvalSetting,
            'modules'         => $modules,
            'jabatans'        => $jabatans,
            'levels'          => $levels,
            'isEdit'          => true,
        ]);
    }

    /**
     * Update alur Header - Detail.
     */
    public function update(Request $request, ApprovalSetting $approvalSetting)
    {
        $request->validate([
            'module'                 => 'required|string|max:50',
            'requester_jabatan_id'   => 'nullable|exists:emp_jabatan,id',
            'description'            => 'nullable|string|max:255',
            'is_active'              => 'nullable|boolean',
            'levels'                 => 'required|array|min:1',
            'levels.*.jabatan_id'    => 'required|exists:emp_jabatan,id',
            'levels.*.min_approvers' => 'nullable|integer|min:1',
            'levels.*.description'   => 'nullable|string|max:255',
        ]);

        // Cek duplikasi aturan (Modul + Jabatan Pemohon yang sama, abaikan current id)
        $existing = ApprovalSetting::where('id', '!=', $approvalSetting->id)
            ->where('module', $request->module)
            ->where(function ($q) use ($request) {
                if ($request->filled('requester_jabatan_id')) {
                    $q->where('requester_jabatan_id', $request->requester_jabatan_id);
                } else {
                    $q->whereNull('requester_jabatan_id');
                }
            })->first();

        if ($existing) {
            return back()->withInput()->with('error', 'Konfigurasi alur approval untuk kombinasi modul dan jabatan pemohon tersebut sudah terdaftar pada aturan lain.');
        }

        DB::transaction(function () use ($request, $approvalSetting) {
            $approvalSetting->update([
                'module'               => $request->module,
                'requester_jabatan_id' => $request->filled('requester_jabatan_id') ? $request->requester_jabatan_id : null,
                'description'          => $request->description,
                'is_active'            => $request->has('is_active') ? 1 : 0,
            ]);

            // Hapus detail lama dan insert ulang dengan level terurut
            $approvalSetting->details()->delete();

            foreach ($request->levels as $idx => $lvl) {
                $approvalSetting->details()->create([
                    'level'               => $idx + 1,
                    'approver_jabatan_id' => $lvl['jabatan_id'],
                    'description'         => $lvl['description'] ?? ('Persetujuan Level ' . ($idx + 1)),
                    'min_approvers'       => $lvl['min_approvers'] ?? 1,
                ]);
            }
        });

        return redirect()->route('approval-settings.index')->with('success', 'Konfigurasi alur approval berhasil diperbarui.');
    }

    /**
     * Hapus konfigurasi alur Header (detail otomatis cascade terhapus).
     */
    public function destroy(ApprovalSetting $approvalSetting)
    {
        $approvalSetting->delete();

        return redirect()->route('approval-settings.index')->with('success', 'Konfigurasi alur approval berhasil dihapus.');
    }
}

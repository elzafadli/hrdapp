<?php

namespace App\Console\Commands;

use App\Models\ApprovalSetting;
use App\Models\EmpData;
use App\Models\EmpJabatan;
use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Console\Command;

class SyncUserEmployeeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ess:sync-users {--setup-approvals : Also setup default multi-level approval settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data Jabatan, Employee (Karyawan), dan Akun User untuk alur Approval ESS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi data Jabatan, Karyawan, dan User...');

        // 1. Sinkronisasi Data Jabatan
        $this->line('1. Sinkronisasi Data Jabatan (emp_jabatan)...');
        $standardJabatan = [
            'Admin'           => 'Admin / HRD',
            'Finance'         => 'Finance',
            'Project Manager' => 'Project Manager',
            'Cashier'         => 'Cashier',
            'Admin Finance'   => 'Admin Finance',
            'Direktur'        => 'Direktur',
            'Staff / Karyawan'=> 'Staff / Karyawan',
        ];

        $jabatanMap = [];
        foreach ($standardJabatan as $name => $desc) {
            $jab = EmpJabatan::firstOrCreate(['jabatan' => $name]);
            $jabatanMap[$name] = $jab->id;
            $this->info("   - Jabatan [ID: {$jab->id}]: {$jab->jabatan}");
        }

        // 2. Hubungkan Karyawan yang ada jika cocok dengan user
        $this->line('2. Menghubungkan karyawan yang sudah ada ke akun user...');
        $existingEmployees = EmpData::all();
        foreach ($existingEmployees as $emp) {
            if ($emp->user_id) {
                continue;
            }

            // Cari user berdasarkan nama yang cocok
            $matchedUser = User::where('name', $emp->name)
                ->orWhere('name', 'like', '%' . $emp->name . '%')
                ->first();

            if ($matchedUser && !EmpData::where('user_id', $matchedUser->id)->exists()) {
                $emp->user_id = $matchedUser->id;
                $emp->save();
                $this->info("   - Karyawan '{$emp->name}' (ID: {$emp->id}) berhasil dihubungkan ke User '{$matchedUser->name}' (ID: {$matchedUser->id}).");
            }
        }

        // Link Karyawan 1 ke User 01 jika belum terhubung
        $karyawan1 = EmpData::find(1);
        if ($karyawan1 && !$karyawan1->user_id) {
            $user01 = User::find(1);
            if ($user01) {
                $karyawan1->user_id = $user01->id;
                $karyawan1->save();
                $this->info("   - Karyawan 1 dihubungkan ke User 01 (ID: 1).");
            }
        }

        // 3. Buat data Karyawan untuk setiap User yang belum memilikinya
        $this->line('3. Menyinkronkan semua User ke Data Karyawan (emp_data)...');
        $users = User::all();
        $currentYear = (int) date('Y');

        foreach ($users as $user) {
            $emp = EmpData::where('user_id', $user->id)->first();

            // Tentukan jabatan_id berdasarkan role user
            $targetJabatanId = match ($user->role) {
                'admin'           => $jabatanMap['Admin'] ?? 1,
                'project_manager' => $jabatanMap['Project Manager'] ?? 1,
                'finance'         => $jabatanMap['Finance'] ?? 2,
                'cashier'         => $jabatanMap['Cashier'] ?? 1,
                'admin_finance'   => $jabatanMap['Admin Finance'] ?? 1,
                default           => $jabatanMap['Staff / Karyawan'] ?? 1,
            };

            if (!$emp) {
                // Cek apakah ada emp dengan nama sama tapi user_id null
                $empByName = EmpData::whereNull('user_id')->where('name', $user->name)->first();
                if ($empByName) {
                    $empByName->user_id = $user->id;
                    $empByName->jabatan_id = $targetJabatanId;
                    $empByName->save();
                    $emp = $empByName;
                    $this->info("   - User '{$user->name}' (ID: {$user->id}) ditautkan ke data karyawan ID {$emp->id}.");
                } else {
                    $newEmpData = [
                        'user_id'     => $user->id,
                        'name'        => $user->name,
                        'jabatan_id'  => $targetJabatanId,
                        'status_ptkp' => 'TK0',
                        'project_id'  => 1,
                    ];
                    if (\Illuminate\Support\Facades\Schema::hasColumn('emp_data', 'gender')) {
                        $newEmpData['gender'] = 'male';
                    }
                    $emp = EmpData::create($newEmpData);
                    $this->info("   - Dibuat Karyawan baru untuk User '{$user->name}' (ID: {$user->id}, Jabatan ID: {$targetJabatanId}).");
                }
            } else {
                // Perbarui jabatan_id jika masih default atau kosong
                if (!$emp->jabatan_id || $emp->jabatan_id === 1 && $user->role !== 'admin') {
                    $emp->jabatan_id = $targetJabatanId;
                    $emp->save();
                }
            }

            // Inisialisasi Saldo Cuti jika belum ada
            LeaveBalance::firstOrCreate(
                ['emp_id' => $emp->id, 'year' => $currentYear],
                [
                    'total_leave'     => 12.0,
                    'used_leave'      => 0.0,
                    'remaining_leave' => 12.0,
                    'notes'           => 'Saldo cuti awal tahun ' . $currentYear,
                ]
            );
        }

        // 4. Setup Alur Approval Default (approval_settings)
        $this->line('4. Memeriksa pengaturan Approval Multi-Level (approval_settings)...');
        $pmJabatanId = $jabatanMap['Project Manager'] ?? 3;
        $financeJabatanId = $jabatanMap['Finance'] ?? 2;

        if (\Illuminate\Support\Facades\Schema::hasTable('approval_setting_details')) {
            $existingAll = ApprovalSetting::where('module', 'all')->whereNull('requester_jabatan_id')->first();
            if (!$existingAll) {
                $head = ApprovalSetting::create([
                    'module'               => 'all',
                    'requester_jabatan_id' => null,
                    'description'          => 'Semua Modul ESS (Semua Jabatan)',
                    'is_active'            => true,
                ]);

                $head->details()->create([
                    'level'               => 1,
                    'approver_jabatan_id' => $pmJabatanId,
                    'description'         => 'Persetujuan Level 1: Project Manager / Atasan Langsung',
                    'min_approvers'       => 1,
                ]);

                $head->details()->create([
                    'level'               => 2,
                    'approver_jabatan_id' => $financeJabatanId,
                    'description'         => 'Persetujuan Level 2: HRD / Finance Approval',
                    'min_approvers'       => 1,
                ]);

                $this->info("   - Workflow Approval Default untuk Semua Modul ('all') berhasil di-set.");
            } else {
                $this->line("   - Workflow Approval Default untuk Semua Modul ('all') sudah ada.");
            }
        }

        $this->info('Sinkronisasi selesai dengan sukses!');
        return 0;
    }
}

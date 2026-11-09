<?php

namespace Database\Seeders;

use App\Models\EmpData;
use App\Models\EmpJabatan;
use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("=== 1. Sinkronisasi Master Jabatan (emp_jabatan) ===");

        $standardJabatans = [
            'Admin',
            'Finance',
            'Project Manager',
            'Cashier',
            'Admin Finance',
            'Direktur',
            'Admin HSE',
            'HSE Coordinator',
            'Technician',
            'Engineer',
            'Head Engineer',
        ];

        $jabatanMap = [];
        foreach ($standardJabatans as $jabName) {
            $jab = EmpJabatan::firstOrCreate(['jabatan' => $jabName]);
            $jabatanMap[strtolower(trim($jabName))] = $jab->id;
            $this->command->line("   - Jabatan: {$jab->jabatan} (ID: {$jab->id})");
        }

        // Alias penyesuaian penulisan Excel
        $jabatanMap['hse coordinator'] = $jabatanMap['hse coordinator'] ?? $jabatanMap['hse coordinator'];
        $jabatanMap['hse coordinator'] = $jabatanMap[strtolower('HSE Coordinator')];

        $this->command->info("\n=== 2. Memproses Data Karyawan dari Excel ===");

        $defaultData = [
            ['email' => 'ferdi.bima@pt-tjtm.com', 'name' => 'Bima', 'jabatan' => 'Technician'],
            ['email' => 'm.maruf@pt-tjtm.com', 'name' => 'Maruf', 'jabatan' => 'Technician'],
            ['email' => 'faisal.asmi@pt-tjtm.com', 'name' => 'Asmi', 'jabatan' => 'Technician'],
            ['email' => 'eko.wijayadi@pt-tjtm.com', 'name' => 'Eko Wijayadi', 'jabatan' => 'Technician'],
            ['email' => 'basrie@pt-tjtm.com', 'name' => 'Basri', 'jabatan' => 'Technician'],
            ['email' => 'wahyu.k@pt-tjtm.com', 'name' => 'Wahyu', 'jabatan' => 'Technician'],
            ['email' => 'hansensarief@pt-tjtm.com', 'name' => 'Hansen', 'jabatan' => 'Engineer'],
            ['email' => 'moch.efendi@pt-tjtm.com', 'name' => 'Fendi', 'jabatan' => 'HSE Coordinator'],
            ['email' => 'natasya@pt-tjtm.com', 'name' => 'Natasya Aurelia', 'jabatan' => 'Admin'],
            ['email' => 'hse.dept@pt-tjtm.com', 'name' => 'Sekar', 'jabatan' => 'Admin HSE'],
            ['email' => 'sales@pt-tjtm.com', 'name' => 'M. Yusuf', 'jabatan' => 'Admin'],
            ['email' => 'adm.support@pt-tjtm.com', 'name' => 'Rois Yanuar', 'jabatan' => 'Admin Finance'],
            ['email' => 'dwisetyaningsih@pt-tjtm.com', 'name' => 'Dwi Setyaningsih', 'jabatan' => 'Cashier'],
            ['email' => 'siti.nurhayati@pt-tjtm.com', 'name' => 'Siti Nurhayati', 'jabatan' => 'Project Manager'],
            ['email' => 'rayhan@pt-tjtm.com', 'name' => 'M. Rayhan', 'jabatan' => 'Admin'],
            ['email' => 'freddy.purwanto@pt-tjtm.com', 'name' => 'Freddy Purwanto', 'jabatan' => 'Engineer'],
            ['email' => 'endang@pt-tjtm.com', 'name' => 'Endang Dahlan', 'jabatan' => 'Finance'],
        ];

        $excelPath = 'c:\\Users\\MSI-KATANA\\Downloads\\Data Karyawan.xlsx';
        $items = [];

        if (file_exists($excelPath)) {
            try {
                $spreadsheet = IOFactory::load($excelPath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                foreach ($rows as $idx => $row) {
                    if ($idx === 0) continue;
                    $email = strtolower(trim((string)($row[0] ?? '')));
                    $name = trim((string)($row[1] ?? ''));
                    $jabatan = trim((string)($row[2] ?? ''));

                    if (!empty($email) || !empty($name)) {
                        $items[] = [
                            'email' => $email,
                            'name' => $name,
                            'jabatan' => $jabatan,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $items = $defaultData;
            }
        }

        if (empty($items)) {
            $items = $defaultData;
        }

        $currentYear = (int) date('Y');
        $createdCount = 0;
        $updatedCount = 0;

        foreach ($items as $item) {
            $email = strtolower(trim($item['email']));
            $name = trim($item['name']);
            $jabatanStr = trim($item['jabatan']);

            // Tentukan jabatan_id
            $jabKey = strtolower($jabatanStr);
            if (!isset($jabatanMap[$jabKey])) {
                $newJab = EmpJabatan::firstOrCreate(['jabatan' => ucwords($jabatanStr)]);
                $jabatanMap[$jabKey] = $newJab->id;
            }
            $targetJabatanId = $jabatanMap[$jabKey];

            // Cari User terkait
            $user = User::where('email', $email)->first();

            // Khusus: Penanganan kasus Rois, Sekar, Dwi
            if ($email === 'hse.dept@pt-tjtm.com') {
                $existingEmp = EmpData::find(13)
                    ?? EmpData::where('email', $email)->first()
                    ?? ($user ? EmpData::where('user_id', $user->id)->first() : null)
                    ?? EmpData::where('name', 'LIKE', '%Sekar%')->first();
                if ($existingEmp) {
                    $existingEmp->name = 'Sekar';
                }
            } elseif ($email === 'adm.support@pt-tjtm.com') {
                // Lepaskan user_id 14 dari Novita Sari (ID 18) jika masih tertaut
                if ($user) {
                    EmpData::where('user_id', $user->id)->where('id', '!=', 10)->update(['user_id' => null]);
                }
                $existingEmp = EmpData::find(10)
                    ?? EmpData::where('email', $email)->first()
                    ?? EmpData::where('name', 'LIKE', '%Rois%')->first();
            } elseif ($email === 'dwisetyaningsih@pt-tjtm.com') {
                // Lepaskan user_id 12 dari Ir Nuraini S (ID 1) jika masih tertaut
                if ($user) {
                    EmpData::where('user_id', $user->id)->where('id', '!=', 16)->update(['user_id' => null]);
                }
                $existingEmp = EmpData::find(16)
                    ?? EmpData::where('email', $email)->first()
                    ?? EmpData::where('name', 'LIKE', '%Dwi Setyaningsih%')->first();
            } else {
                // Pencarian normal: berdasarkan email, user_id, atau kemiripan nama
                $existingEmp = EmpData::where('email', $email)->first();
                if (!$existingEmp && $user) {
                    $existingEmp = EmpData::where('user_id', $user->id)->first();
                }
                if (!$existingEmp) {
                    $existingEmp = EmpData::where('name', $name)
                        ->orWhere('name', 'LIKE', '%' . $name . '%')
                        ->first();
                }
            }

            if ($existingEmp) {
                // Update data karyawan yang sudah ada
                $existingEmp->email = $email;
                if ($user) {
                    $existingEmp->user_id = $user->id;
                }
                $existingEmp->jabatan_id = $targetJabatanId;
                
                // Jika di Excel ada nama baru khusus seperti Sekar
                if ($email === 'hse.dept@pt-tjtm.com' && !str_contains($existingEmp->name, 'Sekar')) {
                    $existingEmp->name = 'Sekar';
                }

                $existingEmp->save();
                $this->command->line("<fg=yellow>[UPDATED]</> Karyawan ID {$existingEmp->id} ({$existingEmp->name}) -> Email: {$email}, Jabatan: {$jabatanStr}");
                $updatedCount++;
                $empRecord = $existingEmp;
            } else {
                // Buat data karyawan baru
                $newEmpPayload = [
                    'name'        => $name,
                    'email'       => $email,
                    'user_id'     => $user?->id,
                    'jabatan_id'  => $targetJabatanId,
                    'status_ptkp' => 'TK0',
                    'project_id'  => 1,
                    'citizenship' => 'WNI',
                ];

                if (Schema::hasColumn('emp_data', 'gender')) {
                    $newEmpPayload['gender'] = 'male';
                }

                $empRecord = EmpData::create($newEmpPayload);
                $this->command->line("<fg=green>[CREATED]</> Karyawan baru ID {$empRecord->id} ({$name}) -> Email: {$email}, Jabatan: {$jabatanStr}");
                $createdCount++;
            }

            // Inisialisasi Saldo Cuti (Leave Balance)
            LeaveBalance::firstOrCreate(
                ['emp_id' => $empRecord->id, 'year' => $currentYear],
                [
                    'total_leave'     => 12.0,
                    'used_leave'      => 0.0,
                    'remaining_leave' => 12.0,
                    'notes'           => 'Saldo cuti awal tahun ' . $currentYear,
                ]
            );
        }

        $this->command->info("\n=== Selesai Sinkronisasi Karyawan ===");
        $this->command->info("Karyawan Dibuat Baru : {$createdCount}");
        $this->command->info("Karyawan Diperbarui  : {$updatedCount}");
    }
}

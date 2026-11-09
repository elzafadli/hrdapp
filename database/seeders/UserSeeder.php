<?php

namespace Database\Seeders;

use App\Models\EmpData;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai seeding data user...');

        // 1. Data default dari Excel (17 Karyawan)
        $defaultData = [
            ['email' => 'ferdi.bima@pt-tjtm.com', 'name' => 'Bima', 'jabatan' => 'Technician'],
            ['email' => 'm.maruf@pt-tjtm.com', 'name' => 'Maruf', 'jabatan' => 'Technician'],
            ['email' => 'faisal.asmi@pt-tjtm.com', 'name' => 'Asmi', 'jabatan' => 'Technician'],
            ['email' => 'eko.wijayadi@pt-tjtm.com', 'name' => 'Eko Wijayadi', 'jabatan' => 'Technician'],
            ['email' => 'basrie@pt-tjtm.com', 'name' => 'Basri', 'jabatan' => 'Technician'],
            ['email' => 'wahyu.k@pt-tjtm.com', 'name' => 'Wahyu', 'jabatan' => 'Technician'],
            ['email' => 'hansensarief@pt-tjtm.com', 'name' => 'Hansen', 'jabatan' => 'Engineer'],
            ['email' => 'moch.efendi@pt-tjtm.com', 'name' => 'Fendi', 'jabatan' => 'Hse Coordinator'],
            ['email' => 'natasya@pt-tjtm.com', 'name' => 'Natasya', 'jabatan' => 'Admin'],
            ['email' => 'hse.dept@pt-tjtm.com', 'name' => 'Sekar', 'jabatan' => 'Admin HSE'],
            ['email' => 'sales@pt-tjtm.com', 'name' => 'Yusuf', 'jabatan' => 'Admin'],
            ['email' => 'adm.support@pt-tjtm.com', 'name' => 'Rois', 'jabatan' => 'Admin Finance'],
            ['email' => 'dwisetyaningsih@pt-tjtm.com', 'name' => 'Dwi', 'jabatan' => 'Cashier'],
            ['email' => 'siti.nurhayati@pt-tjtm.com', 'name' => 'Siti', 'jabatan' => 'Project Manager'],
            ['email' => 'rayhan@pt-tjtm.com', 'name' => 'Rayhan', 'jabatan' => 'Admin'],
            ['email' => 'freddy.purwanto@pt-tjtm.com', 'name' => 'Freddy Purwanto', 'jabatan' => 'Engineer'],
            ['email' => 'endang@pt-tjtm.com', 'name' => 'Endang Dahlan', 'jabatan' => 'Finance'],
        ];

        // Jika file Excel tersedia di Downloads, baca langsung dari Excel agar dinamis
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
                $this->command->warn('Gagal membaca file Excel, menggunakan data fallback: ' . $e->getMessage());
                $items = $defaultData;
            }
        }

        if (empty($items)) {
            $items = $defaultData;
        }

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($items as $item) {
            $email = strtolower(trim($item['email']));
            $name = trim($item['name']);

            if (empty($email)) {
                continue;
            }

            // Cek apakah user sudah ada
            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                $this->command->line("<fg=yellow>[SKIP]</> User sudah ada: {$email} (ID: {$existingUser->id}, Role: {$existingUser->role})");
                $skippedCount++;
                continue;
            }

            // Buat user baru dengan default password 'password' dan role 'user'
            $newUser = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make('password'),
                'role'     => 'user',
            ]);

            // Jika ada emp_data yang cocok dan belum memiliki user_id, hubungkan
            $emp = EmpData::where('email', $email)->first()
                ?? EmpData::where('name', $name)->whereNull('user_id')->first();

            if ($emp && !$emp->user_id) {
                $emp->user_id = $newUser->id;
                $emp->save();
                $this->command->line("<fg=green>[CREATED & LINKED]</> {$name} ({$email}) -> User ID: {$newUser->id}, EmpData ID: {$emp->id}");
            } else {
                $this->command->line("<fg=green>[CREATED]</> {$name} ({$email}) -> User ID: {$newUser->id}");
            }

            $createdCount++;
        }

        $this->command->info("\n=== Selesai Seeding User ===");
        $this->command->info("Total dibuat baru : {$createdCount}");
        $this->command->info("Total di-skip     : {$skippedCount}");
    }
}


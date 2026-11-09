<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\EmpData;
use App\Models\EmpJabatan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("=== 1. Membaca Laporan Presensi Mesin (.xls) ===");

        $filePath = 'c:\\Users\\MSI-KATANA\\Downloads\\report 25 sept - 25 okt.xls';
        if (!file_exists($filePath)) {
            $this->command->error("File tidak ditemukan di: {$filePath}");
            return;
        }

        $html = file_get_contents($filePath);
        $rawRows = explode('</tr>', $html);

        $parsedRows = [];
        $uniquePersons = [];

        foreach ($rawRows as $r) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $r, $tds);
            $cells = array_map(function ($c) {
                $c = strip_tags($c);
                $c = html_entity_decode($c, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return trim($c);
            }, $tds[1]);

            if (count($cells) >= 10 && is_numeric($cells[0])) {
                $personId = $cells[1];
                $name = $cells[2];
                $dept = $cells[3];
                $gender = strtolower($cells[5]) === 'female' ? 'female' : 'male';
                $date = $cells[6];
                $records = $cells[9];

                if (!isset($uniquePersons[$personId])) {
                    $uniquePersons[$personId] = [
                        'person_id' => $personId,
                        'name'      => $name,
                        'gender'    => $gender,
                    ];
                }

                if ($records !== '-' && !empty($records)) {
                    $parsedRows[] = [
                        'person_id' => $personId,
                        'name'      => $name,
                        'date'      => $date,
                        'records'   => $records,
                    ];
                }
            }
        }

        $this->command->info("Total data tap presensi ditemukan : " . count($parsedRows));
        $this->command->info("Total person ID (NID) ditemukan     : " . count($uniquePersons));

        $this->command->info("\n=== 2. Sinkronisasi NID (nik_karyawan) ke emp_data ===");

        // Pemetaan NID -> EmpData ID
        $nidMap = [
            '2'   => 26, // Bima
            '4'   => 27, // Maruf
            '5'   => 32, // Hansen
            '6'   => 14, // Siti Nurhayati
            '7'   => 16, // Dwi Setyaningsih
            '8'   => 11, // Natasya Aurelia
            '9'   => 12, // M. Yusuf
            '11'  => 31, // Wahyu
            '12'  => 10, // Rois Yanuar
            '13'  => 29, // Eko Wijayadi
            '16'  => 1,  // Ir. Nuraini S
            '17'  => 33, // Fendi
            '18'  => 17, // M. Rayhan
            '19'  => 7,  // Endang Dahlan
            '26'  => 30, // Basri
            '31'  => 34, // Freddy Purwanto
            '44'  => 13, // Sekar
            '53'  => 28, // Asmi
        ];

        // Default jabatan jika membuat data baru untuk person aktif di mesin
        $defaultJabatan = EmpJabatan::where('jabatan', 'Technician')->value('id') ?? 1;

        $empByNid = [];

        foreach ($nidMap as $nid => $empId) {
            $emp = EmpData::find($empId);
            if ($emp) {
                $emp->nik_karyawan = (string) $nid;
                $emp->save();
                $empByNid[(string) $nid] = $emp;
                $this->command->line("   - NID [{$nid}] -> EmpData ID {$emp->id} ({$emp->name}) berhasil diperbarui.");
            }
        }

        // Person tambahan dari mesin yang memiliki record presensi aktif (Ibrahim, Ronal, Agus, Hatta)
        $extraPersonsWithAttendance = ['10', '29', '43', '64'];
        foreach ($extraPersonsWithAttendance as $extraNid) {
            if (isset($uniquePersons[$extraNid])) {
                $p = $uniquePersons[$extraNid];
                $emp = EmpData::firstOrCreate(
                    ['nik_karyawan' => (string) $extraNid],
                    [
                        'name'        => $p['name'],
                        'jabatan_id'  => $defaultJabatan,
                        'project_id'  => 1,
                        'status_ptkp' => 'TK0',
                        'citizenship' => 'WNI',
                        'gender'      => $p['gender'],
                    ]
                );
                $empByNid[(string) $extraNid] = $emp;
                $this->command->line("   - NID [{$extraNid}] -> EmpData ID {$emp->id} ({$emp->name}) terdaftar untuk presensi.");
            }
        }

        $this->command->info("\n=== 3. Mengimpor Data Presensi Awal (Skip Jika Sudah Ada) ===");

        $insertedCount = 0;
        $skippedCount = 0;

        foreach ($parsedRows as $row) {
            $nid = (string) $row['person_id'];
            $date = $row['date'];
            $recordsStr = $row['records'];

            if (!isset($empByNid[$nid])) {
                continue;
            }

            $emp = $empByNid[$nid];

            // Cek apakah data presensi karyawan pada tanggal tersebut sudah ada di database
            $exists = Attendance::where('emp_id', $emp->id)->where('date', $date)->exists();
            if ($exists) {
                $skippedCount++;
                continue;
            }

            // Pecah waktu tap (e.g. "08:03:10 17:12:40")
            $times = preg_split('/\s+/', trim($recordsStr));
            sort($times);

            $firstTime = $times[0];
            $lastTime = count($times) > 1 ? end($times) : null;
            if ($lastTime === $firstTime) {
                $lastTime = null;
            }

            // Tentukan status keterlambatan (toleransi s/d 08:30)
            $isLate = ($firstTime > '08:30:00');
            $checkInStatus = $isLate ? 'late' : 'on_time';
            $mainStatus = $isLate ? 'late' : 'present';

            // Check out & durasi kerja (menit)
            $checkOutTime = $lastTime;
            $checkOutStatus = null;
            $workDuration = null;

            if ($checkOutTime !== null) {
                $isEarly = ($checkOutTime < '17:00:00');
                $checkOutStatus = $isEarly ? 'early_leave' : 'normal';

                try {
                    $inCarbon = Carbon::parse("{$date} {$firstTime}");
                    $outCarbon = Carbon::parse("{$date} {$checkOutTime}");
                    $workDuration = $inCarbon->diffInMinutes($outCarbon);
                } catch (\Throwable $e) {
                    $workDuration = null;
                }
            }

            // Simpan data presensi baru
            Attendance::create([
                'emp_id'                => $emp->id,
                'date'                  => $date,
                'check_in_time'         => $firstTime,
                'check_in_status'       => $checkInStatus,
                'check_out_time'        => $checkOutTime,
                'check_out_status'      => $checkOutStatus,
                'work_duration_minutes' => $workDuration,
                'status'                => $mainStatus,
            ]);

            $insertedCount++;
        }

        $this->command->info("\n=== Selesai Impor Presensi ===");
        $this->command->info("Data Baru Berhasil Di-insert : {$insertedCount}");
        $this->command->info("Data Dilewati (Sudah Ada)    : {$skippedCount}");
    }
}


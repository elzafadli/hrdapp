<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\EmpData;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDate;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EmployeeAttendanceController extends Controller
{
    /**
     * Tampilan Rekap Presensi Karyawan & Monitoring Klarifikasi Terlambat.
     */
    public function index(Request $request)
    {
        $data = $this->buildRecapData($request, true);

        return view('employee_attendance.index', [
            'employees'    => $data['employees'],
            'recapData'    => $data['recapData'],
            'startDateStr' => $data['startDateStr'],
            'endDateStr'   => $data['endDateStr'],
            'allEmployees' => $data['allEmployees'],
        ]);
    }

    /**
     * Ekspor Rekap Presensi & Rincian Keterlambatan ke Excel (.xlsx).
     */
    public function export(Request $request)
    {
        $data = $this->buildRecapData($request, false);
        $recapData = $data['recapData'];
        $startDateStr = $data['startDateStr'];
        $endDateStr = $data['endDateStr'];

        $startDateFormatted = Carbon::parse($startDateStr)->translatedFormat('d F Y');
        $endDateFormatted = Carbon::parse($endDateStr)->translatedFormat('d F Y');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name', 'HRD Antigravity'))
            ->setTitle('Rekap Presensi Karyawan ' . $startDateStr . ' - ' . $endDateStr)
            ->setSubject('Rekapitulasi Presensi & Monitoring Keterlambatan');

        // =========================================================================
        // SHEET 1: REKAPITULASI PRESENSI KARYAWAN
        // =========================================================================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Rekap Presensi');

        // Title Block
        $sheet1->setCellValue('A1', 'LAPORAN REKAPITULASI PRESENSI KARYAWAN');
        $sheet1->mergeCells('A1:K1');
        $sheet1->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('1E293B');

        $sheet1->setCellValue('A2', 'Periode Cut-Off: ' . $startDateFormatted . ' s/d ' . $endDateFormatted);
        $sheet1->mergeCells('A2:K2');
        $sheet1->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->getColor()->setRGB('475569');

        $sheet1->setCellValue('A3', 'Dicetak pada: ' . now()->translatedFormat('d F Y H:i:s') . ' WIB');
        $sheet1->mergeCells('A3:K3');
        $sheet1->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('94A3B8');

        // Table Headers
        $headers1 = [
            'A5' => 'No',
            'B5' => 'Nama Karyawan',
            'C5' => 'NID / NIK',
            'D5' => 'Jabatan',
            'E5' => 'Perusahaan',
            'F5' => 'Total Hari Kerja',
            'G5' => 'Total Masuk',
            'H5' => 'Tepat Waktu',
            'I5' => 'Klarifikasi Terlambat',
            'J5' => 'Tidak Klarifikasi',
            'K5' => 'Tidak Masuk (Alpa)',
            'L5' => 'Total Terlambat',
            'M5' => '% Kehadiran',
        ];

        foreach ($headers1 as $cell => $text) {
            $sheet1->setCellValue($cell, $text);
        }

        // Header Styling
        $sheet1->getStyle('A5:M5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']], // Dark Blue
        ]);
        $sheet1->getRowDimension(5)->setRowHeight(28);

        // Fill Data Rows
        $currentRow = 6;
        $no = 1;

        foreach ($recapData as $row) {
            $pct = $row->jumlah_hari_kerja > 0 ? round(($row->total_masuk / $row->jumlah_hari_kerja) * 100, 1) : 0;
            $totalLate = $row->klarifikasi_terlambat + $row->tidak_klarifikasi;

            $sheet1->setCellValue('A' . $currentRow, $no++);
            $sheet1->setCellValue('B' . $currentRow, $row->nama);
            $sheet1->setCellValueExplicit('C' . $currentRow, (string) $row->nid, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet1->setCellValue('D' . $currentRow, $row->jabatan ?? '-');
            $sheet1->setCellValue('E' . $currentRow, $row->company ?? '-');
            $sheet1->setCellValue('F' . $currentRow, $row->jumlah_hari_kerja);
            $sheet1->setCellValue('G' . $currentRow, $row->total_masuk);
            $sheet1->setCellValue('H' . $currentRow, $row->jumlah_tepat_waktu);
            $sheet1->setCellValue('I' . $currentRow, $row->klarifikasi_terlambat);
            $sheet1->setCellValue('J' . $currentRow, $row->tidak_klarifikasi);
            $sheet1->setCellValue('K' . $currentRow, $row->total_tidak_masuk ?? 0);
            $sheet1->setCellValue('L' . $currentRow, $totalLate);
            $sheet1->setCellValue('M' . $currentRow, $pct . '%');

            // Alignments & Styles per Row
            $sheet1->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle('C' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle('E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle('F' . $currentRow . ':L' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle('M' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Highlight tidak klarifikasi jika > 0
            if ($row->tidak_klarifikasi > 0) {
                $sheet1->getStyle('J' . $currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                ]);
            }

            // Highlight tidak masuk / alpa jika > 0
            if (($row->total_tidak_masuk ?? 0) > 0) {
                $sheet1->getStyle('K' . $currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                ]);
            }

            // Zebra striping
            if ($no % 2 === 0) {
                $sheet1->getStyle('A' . $currentRow . ':I' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                $sheet1->getStyle('L' . $currentRow . ':M' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $currentRow++;
        }

        // Summary Row
        $lastDataRow = $currentRow - 1;
        if ($lastDataRow >= 6) {
            $sheet1->setCellValue('A' . $currentRow, 'TOTAL KESELURUHAN');
            $sheet1->mergeCells('A' . $currentRow . ':E' . $currentRow);
            $sheet1->setCellValue('F' . $currentRow, "=SUM(F6:F{$lastDataRow})");
            $sheet1->setCellValue('G' . $currentRow, "=SUM(G6:G{$lastDataRow})");
            $sheet1->setCellValue('H' . $currentRow, "=SUM(H6:H{$lastDataRow})");
            $sheet1->setCellValue('I' . $currentRow, "=SUM(I6:I{$lastDataRow})");
            $sheet1->setCellValue('J' . $currentRow, "=SUM(J6:J{$lastDataRow})");
            $sheet1->setCellValue('K' . $currentRow, "=SUM(K6:K{$lastDataRow})");
            $sheet1->setCellValue('L' . $currentRow, "=SUM(L6:L{$lastDataRow})");
            $sheet1->setCellValue('M' . $currentRow, "-");

            $sheet1->getStyle('A' . $currentRow . ':M' . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
                ],
            ]);
            $sheet1->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet1->getStyle('F' . $currentRow . ':M' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getRowDimension($currentRow)->setRowHeight(24);

            // Border grid for all data
            $sheet1->getStyle('A5:M' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        }

        // Auto column width
        foreach (range('A', 'M') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // =========================================================================
        // SHEET 2: RINCIAN KETERLAMBATAN & KLARIFIKASI
        // =========================================================================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rincian Keterlambatan');

        $sheet2->setCellValue('A1', 'RINCIAN KETERLAMBATAN & MONITORING KLARIFIKASI');
        $sheet2->mergeCells('A1:I1');
        $sheet2->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('1E293B');

        $sheet2->setCellValue('A2', 'Periode Cut-Off: ' . $startDateFormatted . ' s/d ' . $endDateFormatted);
        $sheet2->mergeCells('A2:I2');
        $sheet2->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->getColor()->setRGB('475569');

        $headers2 = [
            'A4' => 'No',
            'B4' => 'Nama Karyawan',
            'C4' => 'NID / NIK',
            'D4' => 'Tanggal Terlambat',
            'E4' => 'Jam Masuk (Tap)',
            'F4' => 'Batas Toleransi',
            'G4' => 'Nomor Dokumen Izin / Cuti',
            'H4' => 'Status Klarifikasi',
            'I4' => 'Catatan / Alasan',
        ];

        foreach ($headers2 as $cell => $text) {
            $sheet2->setCellValue($cell, $text);
        }

        $sheet2->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']], // Slate 700
        ]);
        $sheet2->getRowDimension(4)->setRowHeight(26);

        $curRow2 = 5;
        $no2 = 1;

        foreach ($recapData as $row) {
            if (empty($row->late_details)) continue;

            foreach ($row->late_details as $late) {
                $statusLabel = $late['status'] ?: 'Belum Klarifikasi';

                $sheet2->setCellValue('A' . $curRow2, $no2++);
                $sheet2->setCellValue('B' . $curRow2, $late['nama']);
                $sheet2->setCellValueExplicit('C' . $curRow2, (string) $late['nid'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet2->setCellValue('D' . $curRow2, $late['tgl_telat']);
                $sheet2->setCellValue('E' . $curRow2, $late['check_in_time']);
                $sheet2->setCellValue('F' . $curRow2, '08:30');
                $sheet2->setCellValue('G' . $curRow2, $late['izin_cuti'] ?: '-');
                $sheet2->setCellValue('H' . $curRow2, $statusLabel);
                $sheet2->setCellValue('I' . $curRow2, $late['notes'] ?: '-');

                $sheet2->getStyle('A' . $curRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle('C' . $curRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle('D' . $curRow2 . ':F' . $curRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle('G' . $curRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle('H' . $curRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if (!$late['has_clarification']) {
                    $sheet2->getStyle('H' . $curRow2)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                    ]);
                } elseif ($late['status_badge'] === 'success') {
                    $sheet2->getStyle('H' . $curRow2)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '15803D']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    ]);
                }

                $curRow2++;
            }
        }

        if ($curRow2 > 5) {
            $sheet2->getStyle('A4:I' . ($curRow2 - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        } else {
            $sheet2->setCellValue('A5', 'Tidak ada riwayat keterlambatan pada periode ini.');
            $sheet2->mergeCells('A5:I5');
            $sheet2->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        foreach (range('A', 'I') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // Set active sheet back to Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        // Download Response
        $fileName = 'Rekap_Presensi_' . $startDateStr . '_sd_' . $endDateStr . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Impor Presensi dari file mesin biometric/fingerprint (.xls/.xlsx).
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // max 20 MB
        ], [
            'file.required' => 'Silakan pilih berkas Excel/laporan presensi yang ingin diimpor.',
            'file.file'     => 'Berkas unggahan tidak valid.',
            'file.max'      => 'Ukuran berkas maksimal adalah 20 MB.',
        ]);

        $file = $request->file('file');
        $updateExisting = $request->boolean('update_existing', true);
        $toleranceTime = $request->input('tolerance_time', '08:30:00');
        if (strlen($toleranceTime) === 5) {
            $toleranceTime .= ':00';
        }

        try {
            $parsedRows = $this->parseAttendanceFile($file);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca berkas: ' . $e->getMessage());
        }

        if (empty($parsedRows)) {
            return back()->with('warning', 'Tidak ditemukan data tap presensi yang valid dalam berkas.');
        }

        // Pre-cache employees untuk pencarian cepat (by nik_karyawan, by id, by name)
        $employees = EmpData::all();
        $empByNik = $employees->whereNotNull('nik_karyawan')->keyBy(fn($e) => (string) $e->nik_karyawan);
        $empById = $employees->keyBy(fn($e) => (string) $e->id);
        $empByName = $employees->keyBy(fn($e) => strtolower(trim($e->name)));

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $unmatchedPersons = [];

        foreach ($parsedRows as $row) {
            $personId = (string) $row['person_id'];
            $name = trim($row['name']);
            $date = $row['date'];
            $recordsStr = $row['records'];

            // Match employee
            $emp = $empByNik->get($personId)
                ?? $empById->get($personId)
                ?? $empByName->get(strtolower($name));

            if (!$emp) {
                // Fuzzy/contains match on name
                $emp = $employees->first(function ($e) use ($name) {
                    return stripos($e->name, $name) !== false || stripos($name, $e->name) !== false;
                });
            }

            if (!$emp) {
                $unmatchedPersons[$personId] = $name;
                continue;
            }

            // Parse tap times
            $times = preg_split('/[\s,]+/', trim($recordsStr));
            $times = array_values(array_filter($times, function ($t) {
                return preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $t);
            }));

            if (empty($times)) {
                $skippedCount++;
                continue;
            }

            // Normalisasi ke H:i:s
            $times = array_map(function ($t) {
                return strlen($t) === 5 ? "{$t}:00" : $t;
            }, $times);
            sort($times);

            $firstTime = $times[0];
            $lastTime = count($times) > 1 ? end($times) : null;
            if ($lastTime === $firstTime) {
                $lastTime = null;
            }

            $isLate = ($firstTime > $toleranceTime);
            $checkInStatus = $isLate ? 'late' : 'on_time';
            $mainStatus = $isLate ? 'late' : 'present';

            $checkOutStatus = null;
            $workDuration = null;
            if ($lastTime !== null) {
                $checkOutStatus = ($lastTime < '17:00:00') ? 'early_leave' : 'normal';
                try {
                    $inC = Carbon::parse("{$date} {$firstTime}");
                    $outC = Carbon::parse("{$date} {$lastTime}");
                    $workDuration = $inC->diffInMinutes($outC);
                } catch (\Throwable $e) {
                    $workDuration = null;
                }
            }

            $existing = Attendance::where('emp_id', $emp->id)->where('date', $date)->first();

            if ($existing) {
                if ($updateExisting) {
                    $existing->update([
                        'check_in_time'         => $firstTime,
                        'check_in_status'       => $checkInStatus,
                        'check_out_time'        => $lastTime,
                        'check_out_status'      => $checkOutStatus,
                        'work_duration_minutes' => $workDuration,
                        'status'                => $mainStatus,
                    ]);
                    $updatedCount++;
                } else {
                    $skippedCount++;
                }
            } else {
                Attendance::create([
                    'emp_id'                => $emp->id,
                    'date'                  => $date,
                    'check_in_time'         => $firstTime,
                    'check_in_status'       => $checkInStatus,
                    'check_out_time'        => $lastTime,
                    'check_out_status'      => $checkOutStatus,
                    'work_duration_minutes' => $workDuration,
                    'status'                => $mainStatus,
                ]);
                $insertedCount++;
            }
        }

        $msg = "Impor presensi berhasil diproses: {$insertedCount} data baru ditambahkan, {$updatedCount} data diperbarui";
        if ($skippedCount > 0) {
            $msg .= ", {$skippedCount} data dilewati (sudah ada)";
        }
        $msg .= ".";

        if (!empty($unmatchedPersons)) {
            $unmatchedList = [];
            foreach (array_slice($unmatchedPersons, 0, 5, true) as $pid => $pname) {
                $unmatchedList[] = "{$pname} (ID: {$pid})";
            }
            $moreCount = count($unmatchedPersons) - count($unmatchedList);
            $moreText = $moreCount > 0 ? " dan {$moreCount} lainnya" : "";
            $msg .= " Catatan: " . count($unmatchedPersons) . " ID karyawan tidak ditemukan di master data: " . implode(', ', $unmatchedList) . $moreText . ".";
        }

        return back()->with('success', $msg);
    }

    /**
     * Deteksi dan parsing file laporan presensi (HTML-based .xls atau binary .xlsx/.xls).
     */
    protected function parseAttendanceFile($file): array
    {
        $realPath = $file->getRealPath();
        $head = file_get_contents($realPath, false, null, 0, 1024);

        // Jika diawali tag HTML / table (seperti output mesin ZKTeco / Punch Report)
        if (stripos($head, '<html') !== false || stripos($head, '<table') !== false) {
            return $this->parseHtmlAttendance(file_get_contents($realPath));
        }

        // Binary spreadsheet (.xlsx atau .xls)
        return $this->parseSpreadsheetAttendance($realPath);
    }

    /**
     * Parse laporan mesin format HTML (contoh: report 25 sept - 25 okt.xls).
     */
    protected function parseHtmlAttendance(string $html): array
    {
        $rawRows = explode('</tr>', $html);
        $parsed = [];

        foreach ($rawRows as $r) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $r, $tds);
            if (empty($tds[1])) continue;

            $cells = array_map(function ($c) {
                $c = strip_tags($c);
                $c = html_entity_decode($c, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return trim($c);
            }, $tds[1]);

            // Format standard mesin: Index, Person ID, Name, Department, Position, Gender, Date, Day, Timetable, Records
            if (count($cells) >= 10 && is_numeric($cells[0])) {
                $personId = $cells[1];
                $name     = $cells[2];
                $dateRaw  = $cells[6];
                $records  = $cells[9];

                try {
                    $date = Carbon::parse($dateRaw)->toDateString();
                } catch (\Throwable $e) {
                    continue;
                }

                if ($records !== '-' && !empty($records)) {
                    $parsed[] = [
                        'person_id' => $personId,
                        'name'      => $name,
                        'date'      => $date,
                        'records'   => $records,
                    ];
                }
            }
        }

        return $parsed;
    }

    /**
     * Parse file spreadsheet format Excel biasa (.xlsx/.xls) via PhpSpreadsheet.
     */
    protected function parseSpreadsheetAttendance(string $filePath): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) return [];

        $headerRowIdx = null;
        $colIndex = [];

        foreach ($rows as $idx => $row) {
            $rowLower = array_map(fn($val) => strtolower(trim((string)$val)), $row);
            if (in_array('person id', $rowLower) || in_array('nid', $rowLower) || in_array('nik', $rowLower) || in_array('records', $rowLower)) {
                $headerRowIdx = $idx;
                foreach ($rowLower as $cIdx => $cName) {
                    if (str_contains($cName, 'person id') || $cName === 'nid' || $cName === 'nik') $colIndex['person_id'] = $cIdx;
                    if (str_contains($cName, 'name') || str_contains($cName, 'nama')) $colIndex['name'] = $cIdx;
                    if (str_contains($cName, 'date') || str_contains($cName, 'tanggal')) $colIndex['date'] = $cIdx;
                    if (str_contains($cName, 'record') || str_contains($cName, 'jam') || str_contains($cName, 'masuk')) $colIndex['records'] = $cIdx;
                }
                break;
            }
        }

        $parsed = [];
        $startIdx = $headerRowIdx !== null ? $headerRowIdx + 1 : 1;

        for ($i = $startIdx; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty($row) || !array_filter($row)) continue;

            $personId = isset($colIndex['person_id']) ? ($row[$colIndex['person_id']] ?? '') : ($row[1] ?? '');
            $name     = isset($colIndex['name']) ? ($row[$colIndex['name']] ?? '') : ($row[2] ?? '');
            $dateRaw  = isset($colIndex['date']) ? ($row[$colIndex['date']] ?? '') : ($row[6] ?? '');
            $records  = isset($colIndex['records']) ? ($row[$colIndex['records']] ?? '') : ($row[9] ?? ($row[3] ?? ''));

            if (empty($personId) && empty($name)) continue;

            try {
                $date = Carbon::parse($dateRaw)->toDateString();
            } catch (\Throwable $e) {
                continue;
            }

            if ($records !== '-' && !empty($records)) {
                $parsed[] = [
                    'person_id' => (string) $personId,
                    'name'      => (string) $name,
                    'date'      => $date,
                    'records'   => (string) $records,
                ];
            }
        }

        return $parsed;
    }

    /**
     * Hitung Rekapitulasi Presensi Karyawan & Rincian Keterlambatan.
     */
    protected function buildRecapData(Request $request, bool $paginate = false): array
    {
        // 1. Filter Rentang Tanggal (Default Cut-Off Penggajian Tanggal 25)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        } else {
            $now = Carbon::today();
            if ($now->day < 25) {
                $startDate = $now->copy()->subMonth()->day(25)->startOfDay();
                $endDate = $now->copy()->day(25)->endOfDay();
            } else {
                $startDate = $now->copy()->day(25)->startOfDay();
                $endDate = $now->copy()->addMonth()->day(25)->endOfDay();
            }
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();

        // 2. Query Karyawan
        $employeeQuery = EmpData::with(['jabatan', 'project.company', 'company'])->orderBy('name');

        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->input('employee_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $employeeQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik_karyawan', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if ($paginate) {
            $employees = $employeeQuery->paginate(100)->withQueryString();
            $empIds = $employees->pluck('id')->toArray();
        } else {
            $employees = $employeeQuery->get();
            $empIds = $employees->pluck('id')->toArray();
        }

        // 3. Pre-fetch Data Attendances & Leave Requests
        $attendances = collect();
        if (Schema::hasTable('attendances') && !empty($empIds)) {
            $attendances = Attendance::whereIn('emp_id', $empIds)
                ->whereBetween('date', [$startDateStr, $endDateStr])
                ->orderBy('date', 'asc')
                ->get()
                ->groupBy('emp_id');
        }

        $leaveDates = collect();
        if (Schema::hasTable('leave_request_dates') && !empty($empIds)) {
            $leaveDates = LeaveRequestDate::with(['leaveRequest.approvals', 'leaveRequest.files'])
                ->whereIn('emp_id', $empIds)
                ->whereBetween('date', [$startDateStr, $endDateStr])
                ->get()
                ->groupBy('emp_id');
        }

        $holidayDates = Holiday::getHolidayDatesBetween($startDate, $endDate);
        $defaultSchedule = WorkSchedule::getDefault();

        // 4. Kalkulasi Rekapitulasi per Karyawan
        $recapData = [];

        foreach ($employees as $emp) {
            $empAtts = $attendances->get($emp->id, collect());
            $empLeaves = $leaveDates->get($emp->id, collect())->keyBy(function ($ld) {
                return Carbon::parse($ld->date)->toDateString();
            });

            // Kumpulkan data attendances berdasarkan tanggal
            $empAttsByDate = $empAtts->keyBy(function ($att) {
                return Carbon::parse($att->date)->toDateString();
            });

            // Jadwal kerja karyawan
            $schedule = $emp->getWorkScheduleForDate($startDate) ?? $defaultSchedule;
            $workDaysMap = $schedule ? $schedule->days->pluck('is_work_day', 'day_of_week')->toArray() : [1 => true, 2 => true, 3 => true, 4 => true, 5 => true];

            $period = CarbonPeriod::create($startDate, $endDate);
            $workingDays = 0;
            $attendanceDetails = [];
            $lateDetails = [];
            $onTimeCount = 0;
            $klarifikasiCount = 0;
            $tidakKlarifikasiCount = 0;
            $absentCount = 0;
            $leaveCount = 0;
            $processedDates = [];

            // Iterasi per HARI KERJA karyawan
            foreach ($period as $currentDate) {
                $dateStr = $currentDate->toDateString();
                $dayOfWeek = $currentDate->dayOfWeekIso; // 1 = Senin, ..., 7 = Minggu
                $isHoliday = in_array($dateStr, $holidayDates);
                $isWorkDay = !$isHoliday && !empty($workDaysMap[$dayOfWeek]);

                // Hanya proses hari kerja!
                if (!$isWorkDay) {
                    continue;
                }

                $workingDays++;
                $processedDates[] = $dateStr;
                $dateCarbon = $currentDate->copy();
                $att = $empAttsByDate->get($dateStr);

                // Cek pengajuan izin / cuti / sakit pada tanggal ini
                $leaveDateRecord = $empLeaves->get($dateStr);
                $leaveReq = $leaveDateRecord?->leaveRequest;

                if (!$leaveReq) {
                    $leaveReq = LeaveRequest::with(['approvals', 'files'])
                        ->where('emp_id', $emp->id)
                        ->where(function ($q) use ($dateStr) {
                            $q->where(function ($sub) use ($dateStr) {
                                $sub->whereDate('full_day_start_date', '<=', $dateStr)
                                    ->whereDate('full_day_end_date', '>=', $dateStr);
                            })->orWhere(function ($sub) use ($dateStr) {
                                $sub->whereDate('half_day_start_date', '<=', $dateStr)
                                    ->whereDate('half_day_end_date', '>=', $dateStr);
                            });
                        })->first();
                }

                $hasClarification = $leaveReq !== null;
                $izinCuti = '';
                $statusText = '';
                $statusBadge = 'none';

                if ($hasClarification) {
                    $prefix = match ($leaveReq->leave_type) {
                        'permit'  => 'SI',
                        'annual'  => 'SC',
                        'special' => 'SCK',
                        'sick'    => 'SK',
                        default   => 'IZ',
                    };

                    $reqNum = $leaveReq->request_number ?? '';
                    if (!empty($reqNum) && preg_match('/^(\d+)/', $reqNum, $matches)) {
                        $seq = ltrim($matches[1], '0');
                        $izinCuti = "{$prefix} " . (!empty($seq) ? $seq : $matches[1]);
                    } elseif (!empty($reqNum)) {
                        $izinCuti = "{$prefix} " . $reqNum;
                    } else {
                        $izinCuti = "{$prefix} " . $leaveReq->id;
                    }

                    if (in_array($leaveReq->status, ['completed', 'approved'])) {
                        $statusText = 'Ok';
                        $statusBadge = 'success';
                    } elseif ($leaveReq->status === 'pending') {
                        $pendingApproval = $leaveReq->getCurrentPendingApproval();
                        $level = $pendingApproval ? $pendingApproval->level : 1;
                        $statusText = "Aproval {$level}";
                        $statusBadge = 'warning';
                    } elseif ($leaveReq->status === 'rejected') {
                        $statusText = 'Ditolak';
                        $statusBadge = 'danger';
                    } else {
                        $statusText = ucfirst($leaveReq->status);
                        $statusBadge = 'secondary';
                    }
                }

                $attachmentUrl = null;
                if ($leaveReq && $leaveReq->files && $leaveReq->files->isNotEmpty()) {
                    $attachmentUrl = $leaveReq->files->first()->url ?? null;
                }

                // KONDISI 1: ADA ABSENSI MASUK
                if ($att && !empty($att->check_in_time)) {
                    $isLate = ($att->check_in_status === 'late' || $att->status === 'late');

                    if ($isLate) {
                        if ($hasClarification) {
                            $klarifikasiCount++;
                            $type = 'clarified';
                        } else {
                            $tidakKlarifikasiCount++;
                            $type = 'unclarified';
                            $izinCuti = '';
                            $statusText = '';
                            $statusBadge = 'none';
                        }
                    } else {
                        $onTimeCount++;
                        $type = 'on_time';
                    }

                    $detailItem = [
                        'nama'              => $emp->name,
                        'nid'               => (string) ($emp->nik_karyawan ?: $emp->id),
                        'hari'              => $dateCarbon->translatedFormat('l'),
                        'tgl_telat'         => $dateCarbon->format('d/m/Y'),
                        'tgl_lengkap'       => $dateCarbon->translatedFormat('l, d/m/Y'),
                        'tgl_raw'           => $dateStr,
                        'is_late'           => $isLate,
                        'type'              => $type,
                        'attendance_type'   => $type,
                        'attendance_status' => $isLate ? 'Terlambat' : 'Tepat Waktu',
                        'izin_cuti'         => $izinCuti,
                        'full_doc_number'   => $leaveReq?->request_number ?? '',
                        'status'            => $statusText,
                        'status_badge'      => $statusBadge,
                        'has_clarification' => $hasClarification,
                        'check_in_time'     => substr($att->check_in_time, 0, 5),
                        'check_out_time'    => $att->check_out_time ? substr($att->check_out_time, 0, 5) : '-',
                        'photo_url'         => $att->check_in_photo_url,
                        'check_out_photo'   => $att->check_out_photo_url,
                        'attachment_url'    => $attachmentUrl,
                        'notes'             => $att->check_in_notes ?: ($leaveReq?->reason ?: '-'),
                    ];

                    $attendanceDetails[] = $detailItem;
                    if ($isLate) {
                        $lateDetails[] = $detailItem;
                    }
                }
                // KONDISI 2: TIDAK ADA ABSENSI MASUK PADA HARI KERJA INI
                else {
                    $isFuture = $currentDate->isFuture();

                    if ($hasClarification) {
                        $leaveCount++;
                        $type = 'leave';
                        $attendanceStatus = match ($leaveReq->leave_type) {
                            'permit'  => 'Izin',
                            'annual'  => 'Cuti Tahunan',
                            'special' => 'Cuti Khusus',
                            'sick'    => 'Sakit',
                            default   => 'Izin / Cuti',
                        };
                        $notes = $leaveReq->reason ?: 'Izin / Cuti terdaftar';
                    } elseif ($isFuture) {
                        $type = 'upcoming';
                        $attendanceStatus = 'Belum Berjalan';
                        $notes = 'Jadwal hari kerja mendatang';
                    } else {
                        $absentCount++;
                        $type = 'absent';
                        $attendanceStatus = 'Tidak Masuk / Alpa';
                        $statusText = 'Tanpa Keterangan';
                        $statusBadge = 'danger';
                        $notes = 'Tidak melakukan presensi masuk (Alpha)';
                    }

                    $detailItem = [
                        'nama'              => $emp->name,
                        'nid'               => (string) ($emp->nik_karyawan ?: $emp->id),
                        'hari'              => $dateCarbon->translatedFormat('l'),
                        'tgl_telat'         => $dateCarbon->format('d/m/Y'),
                        'tgl_lengkap'       => $dateCarbon->translatedFormat('l, d/m/Y'),
                        'tgl_raw'           => $dateStr,
                        'is_late'           => false,
                        'type'              => $type,
                        'attendance_type'   => $type,
                        'attendance_status' => $attendanceStatus,
                        'izin_cuti'         => $izinCuti,
                        'full_doc_number'   => $leaveReq?->request_number ?? '',
                        'status'            => $statusText,
                        'status_badge'      => $statusBadge,
                        'has_clarification' => $hasClarification,
                        'check_in_time'     => '-',
                        'check_out_time'    => '-',
                        'photo_url'         => null,
                        'check_out_photo'   => null,
                        'attachment_url'    => $attachmentUrl,
                        'notes'             => $notes,
                    ];

                    $attendanceDetails[] = $detailItem;
                }
            }

            // Tambahkan jika ada presensi yang dilakukan di luar hari kerja reguler (misal lembur Sabtu/Minggu/Libur)
            foreach ($empAtts as $att) {
                $dateStr = Carbon::parse($att->date)->toDateString();
                if (in_array($dateStr, $processedDates) || empty($att->check_in_time)) {
                    continue;
                }
                $dateCarbon = Carbon::parse($att->date);
                $detailItem = [
                    'nama'              => $emp->name,
                    'nid'               => (string) ($emp->nik_karyawan ?: $emp->id),
                    'hari'              => $dateCarbon->translatedFormat('l'),
                    'tgl_telat'         => $dateCarbon->format('d/m/Y'),
                    'tgl_lengkap'       => $dateCarbon->translatedFormat('l, d/m/Y'),
                    'tgl_raw'           => $dateStr,
                    'is_late'           => false,
                    'type'              => 'holiday_work',
                    'attendance_type'   => 'on_time',
                    'attendance_status' => 'Lembur / Masuk Hari Libur',
                    'izin_cuti'         => '',
                    'full_doc_number'   => '',
                    'status'            => 'Lembur',
                    'status_badge'      => 'secondary',
                    'has_clarification' => false,
                    'check_in_time'     => substr($att->check_in_time, 0, 5),
                    'check_out_time'    => $att->check_out_time ? substr($att->check_out_time, 0, 5) : '-',
                    'photo_url'         => $att->check_in_photo_url,
                    'check_out_photo'   => $att->check_out_photo_url,
                    'attachment_url'    => null,
                    'notes'             => $att->check_in_notes ?: 'Presensi di luar jadwal hari kerja',
                ];
                $attendanceDetails[] = $detailItem;
            }

            // Urutkan attendanceDetails berdasarkan tanggal ascending
            usort($attendanceDetails, function ($a, $b) {
                return strcmp($a['tgl_raw'], $b['tgl_raw']);
            });

            $totalMasuk = $onTimeCount + $klarifikasiCount + $tidakKlarifikasiCount;

            $recapData[] = (object) [
                'employee'              => $emp,
                'nama'                  => $emp->name,
                'nid'                   => (string) ($emp->nik_karyawan ?: $emp->id),
                'jabatan'               => $emp->jabatan?->jabatan ?? '-',
                'company'               => $emp->company?->code ?? ($emp->project?->company?->code ?? '-'),
                'project'               => $emp->project?->name ?? '-',
                'jumlah_hari_kerja'     => $workingDays,
                'total_masuk'           => $totalMasuk,
                'jumlah_tepat_waktu'    => $onTimeCount,
                'klarifikasi_terlambat' => $klarifikasiCount,
                'tidak_klarifikasi'     => $tidakKlarifikasiCount,
                'total_tidak_masuk'     => $absentCount,
                'total_izin_cuti'       => $leaveCount,
                'late_details'          => $lateDetails,
                'attendance_details'    => $attendanceDetails,
            ];
        }

        $allEmployees = EmpData::orderBy('name')->pluck('name', 'id')->toArray();

        return [
            'employees'    => $employees,
            'recapData'    => $recapData,
            'startDateStr' => $startDateStr,
            'endDateStr'   => $endDateStr,
            'allEmployees' => $allEmployees,
        ];
    }

    /**
     * Endpoint API JSON untuk mengambil detail keterlambatan karyawan (opsional).
     */
    public function getLateDetail(Request $request, EmpData $employee)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $lateAtts = Attendance::where('emp_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('check_in_status', 'late')->orWhere('status', 'late');
            })
            ->orderBy('date', 'asc')
            ->get();

        $empLeaves = LeaveRequestDate::with(['leaveRequest.approvals', 'leaveRequest.files'])
            ->where('emp_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn($ld) => Carbon::parse($ld->date)->toDateString());

        $details = [];
        foreach ($lateAtts as $late) {
            $dateStr = Carbon::parse($late->date)->toDateString();
            $leaveReq = $empLeaves->get($dateStr)?->leaveRequest;

            $hasClarification = $leaveReq !== null;
            $izinCuti = '';
            $statusText = '';
            $statusBadge = 'none';

            if ($hasClarification) {
                $prefix = match ($leaveReq->leave_type) {
                    'permit'  => 'SI',
                    'annual'  => 'SC',
                    'special' => 'SCK',
                    'sick'    => 'SK',
                    default   => 'IZ',
                };

                $reqNum = $leaveReq->request_number ?? '';
                if (!empty($reqNum) && preg_match('/^(\d+)/', $reqNum, $matches)) {
                    $seq = ltrim($matches[1], '0');
                    $izinCuti = "{$prefix} " . (!empty($seq) ? $seq : $matches[1]);
                } elseif (!empty($reqNum)) {
                    $izinCuti = "{$prefix} " . $reqNum;
                } else {
                    $izinCuti = "{$prefix} " . $leaveReq->id;
                }

                if (in_array($leaveReq->status, ['completed', 'approved'])) {
                    $statusText = 'Ok';
                    $statusBadge = 'success';
                } elseif ($leaveReq->status === 'pending') {
                    $pendingApproval = $leaveReq->getCurrentPendingApproval();
                    $level = $pendingApproval ? $pendingApproval->level : 1;
                    $statusText = "Aproval {$level}";
                    $statusBadge = 'warning';
                } elseif ($leaveReq->status === 'rejected') {
                    $statusText = 'Ditolak';
                    $statusBadge = 'danger';
                } else {
                    $statusText = ucfirst($leaveReq->status);
                    $statusBadge = 'secondary';
                }
            }

            $attachmentUrl = null;
            if ($leaveReq && $leaveReq->files && $leaveReq->files->isNotEmpty()) {
                $attachmentUrl = $leaveReq->files->first()->url ?? null;
            }

            $details[] = [
                'nama'              => $employee->name,
                'nid'               => (string) ($employee->nik_karyawan ?: $employee->id),
                'tgl_telat'         => Carbon::parse($late->date)->format('d/m/Y'),
                'tgl_raw'           => $dateStr,
                'izin_cuti'         => $izinCuti,
                'status'            => $statusText,
                'status_badge'      => $statusBadge,
                'has_clarification' => $hasClarification,
                'check_in_time'     => $late->check_in_time ? substr($late->check_in_time, 0, 5) : '-',
                'photo_url'         => $late->check_in_photo_url,
                'check_out_photo'   => $late->check_out_photo_url,
                'attachment_url'    => $attachmentUrl,
                'notes'             => $late->check_in_notes ?: ($leaveReq?->reason ?: '-'),
            ];
        }

        return response()->json([
            'employee' => [
                'name' => $employee->name,
                'nid'  => (string) ($employee->nik_karyawan ?: $employee->id),
            ],
            'details'  => $details,
        ]);
    }
}

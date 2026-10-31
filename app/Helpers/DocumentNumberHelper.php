<?php

namespace App\Helpers;

use App\Models\EmpData;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use Carbon\Carbon;

class DocumentNumberHelper
{
    /**
     * Konversi angka bulan (1-12) ke angka Romawi.
     */
    public static function getRomanMonth(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $romans[$month] ?? 'I';
    }

    /**
     * Generate format nomor dokumen ESS:
     * Contoh: 049/PO-TJTM/RY/X/26
     * Format: {3DigitSequence}/{DocType}-{CompanyCode}/{UserInitial}/{RomanMonth}/{2DigitYear}
     *
     * @param string $docType Singkatan jenis dokumen (CT, CK, IZ, SK, SPL)
     * @param EmpData|null $employee Karyawan pemohon
     * @param User|null $user User pemohon
     * @return string Nomor dokumen lengkap
     */
    public static function generate(string $docType, ?EmpData $employee = null, ?User $user = null): string
    {
        $user = $user ?? auth()->user();
        $date = Carbon::now();
        $currentYear = (int) $date->format('Y');
        $twoDigitYear = $date->format('y');
        $romanMonth = self::getRomanMonth((int) $date->format('n'));

        // 1. Company Code (from direct company_id or project->company, default TJTM)
        $companyCode = 'TJTM';
        if ($employee) {
            $comp = $employee->company ?? $employee->project?->company;
            if ($comp && !empty($comp->code)) {
                $companyCode = strtoupper(trim($comp->code));
            }
        }

        // 2. User Initial (from prefix or initials of user name)
        $initial = 'HR';
        if ($user && !empty($user->prefix)) {
            $initial = strtoupper(trim($user->prefix));
        } elseif ($user && !empty($user->name)) {
            $words = preg_split('/\s+/', trim($user->name));
            $initial = count($words) >= 2
                ? strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1))
                : strtoupper(substr($words[0], 0, 2));
        }

        // 3. Hitung nomor urut (Sequence) berdasarkan tahun berjalan
        if ($docType === 'SPL') {
            $count = OvertimeRequest::whereYear('created_at', $currentYear)->count();
        } else {
            $leaveTypeMap = [
                'CT' => 'annual',
                'CK' => 'special',
                'IZ' => 'permit',
                'SK' => 'sick',
            ];
            $leaveType = $leaveTypeMap[$docType] ?? 'annual';
            $count = LeaveRequest::whereYear('created_at', $currentYear)
                ->where('leave_type', $leaveType)
                ->count();
        }

        $seqNumber = $count + 1;
        do {
            $sequence = str_pad($seqNumber, 3, '0', STR_PAD_LEFT);
            $candidate = "{$sequence}/{$docType}-{$companyCode}/{$initial}/{$romanMonth}/{$twoDigitYear}";

            $exists = ($docType === 'SPL')
                ? OvertimeRequest::where('request_number', $candidate)->exists()
                : LeaveRequest::where('request_number', $candidate)->exists();

            $seqNumber++;
        } while ($exists);

        return $candidate;
    }
}

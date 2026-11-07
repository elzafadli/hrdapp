<?php

if (!function_exists('indoMonth')) {
    /**
     * Convert month number to Indonesian month name (uppercase).
     *
     * @param int|string $month Month number (1-12)
     * @return string Indonesian month name in uppercase
     */
    function indoMonth($month): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return strtoupper($months[(int)$month] ?? '');
    }
}

if (!function_exists('indoMonthFull')) {
    /**
     * Convert month number to Indonesian month name (not uppercase).
     *
     * @param int|string $month Month number (1-12)
     * @return string Indonesian month name
     */
    function indoMonthFull($month): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[(int)$month] ?? '';
    }
}

if (!function_exists('terbilang')) {
    /**
     * Convert number to Indonesian words (terbilang).
     *
     * @param float|int $number The number to convert
     * @return string Indonesian words representation
     */
    function terbilang($number): string
    {
        $number = abs($number);
        $words = "";

        $units = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam',
            'Tujuh', 'Delapan', 'Sembilan'
        ];

        // Levels for number conversion
        $levels = [
            '', 'Ribu', 'Juta', 'Milyar', 'Triliun'
        ];

        if ($number == 0) {
            return "Nol";
        }

        // Handle decimal numbers
        if (strpos((string)$number, '.') !== false) {
            $parts = explode('.', (string)$number);
            $integerPart = terbilang($parts[0]);
            $decimalPart = '';

            if (isset($parts[1]) && $parts[1] > 0) {
                $decimalPart = ' Koma';
                for ($i = 0; $i < strlen($parts[1]); $i++) {
                    $digit = (int)$parts[1][$i];
                    $decimalPart .= ' ' . $units[$digit];
                }
            }

            return trim($integerPart . $decimalPart);
        }

        $i = 0;
        while ($number > 0) {
            $remainder = $number % 1000;

            if ($remainder != 0) {
                $hundreds = floor($remainder / 100);
                $tens = floor(($remainder % 100) / 10);
                $ones = $remainder % 10;

                $hundredWords = "";
                $tenWords = "";
                $oneWords = "";

                // Hundreds
                if ($hundreds > 0) {
                    if ($hundreds == 1) {
                        $hundredWords = "Seratus";
                    } else {
                        $hundredWords = $units[$hundreds] . " Ratus";
                    }
                }

                // Tens
                if ($tens >= 2) {
                    $tenWords = $units[(int)$tens] . " Puluh";
                    if ($ones > 0) {
                        $tenWords .= " " . $units[$ones];
                    }
                } elseif ($tens == 1) {
                    if ($ones == 0) {
                        $tenWords = "Sepuluh";
                    } elseif ($ones == 1) {
                        $tenWords = "Sebelas";
                    } else {
                        $tenWords = $units[$ones] . " Belas";
                    }
                } elseif ($ones > 0 && $tens == 0) {
                    if ($ones == 1 && $hundreds == 0 && $i == 1) {
                        $oneWords = "Se";
                    } else {
                        $oneWords = $units[$ones];
                    }
                } elseif ($ones == 0 && $tens == 0 && $hundreds == 0) {
                    // This shouldn't happen since remainder != 0
                }

                $levelWords = ($hundredWords . " " . $tenWords . " " . $oneWords);
                $levelWords = trim($levelWords);
                $levelWords .= " " . $levels[$i];

                $words = $levelWords . " " . $words;
            }

            $number = floor($number / 1000);
            $i++;
        }

        return trim($words);
    }
}

if (!function_exists('is_approver_role')) {
    /**
     * Cek apakah role user termasuk role approver / manajerial khusus
     * (admin, superadmin, hrd, project_manager).
     *
     * @param \App\Models\User|string|null $userOrRole Objek User atau string role
     * @return bool
     */
    function is_approver_role($userOrRole = null): bool
    {
        $role = $userOrRole instanceof \App\Models\User
            ? $userOrRole->role
            : ($userOrRole ?? auth()->user()?->role);

        if (!$role) {
            return false;
        }

        $approverRoles = ['admin', 'superadmin', 'hrd', 'project_manager', 'cashier'];
        return in_array(strtolower(trim((string) $role)), $approverRoles, true);
    }
}

if (!function_exists('is_user_approver')) {
    /**
     * Cek apakah user berwenang sebagai approver:
     * - Default ($includeRoles = false): Hanya approver fungsional yang jabatannya aktif
     *   di alur persetujuan (identik dengan syarat menu Approval Inbox di sidebar).
     * - Jika $includeRoles = true: Termasuk juga jika user memiliki role manajerial/admin
     *   (admin, superadmin, hrd, project_manager).
     *
     * @param \App\Models\User|null $user
     * @param bool $includeRoles
     * @return bool
     */
    function is_user_approver(?\App\Models\User $user = null, bool $includeRoles = false): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        if ($includeRoles && is_approver_role($user)) {
            return true;
        }

        try {
            $emp = $user->employee;
            if (!$emp && !$user->relationLoaded('employee')) {
                $emp = \App\Models\EmpData::where('user_id', $user->id)->first()
                    ?? \App\Models\EmpData::where('name', $user->name)->first();
            }

            if ($emp) {
                return $emp->canApprove();
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
}

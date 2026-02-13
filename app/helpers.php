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
                $decimalValue = (int)$parts[1];
                if ($decimalValue < 10) {
                    $decimalPart = ' Koma Nol';
                    for ($i = 0; $i < strlen((string)$decimalValue); $i++) {
                        $decimalPart .= ' ' . $units[(int)$parts[1][$i]];
                    }
                } else {
                    $decimalPart = terbilang($decimalValue);
                }
            }

            return trim($integerPart . $decimalPart);
        }

        $i = 0;
        while ($number > 0) {
            $remainder = $number % 1000;

            if ($remainder != 0) {
                $hundreds = floor($remainder / 100);
                $tens = ($remainder % 100) / 10;
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

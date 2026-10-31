<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestDate extends Model
{
    use HasFactory;

    protected $table = 'leave_request_dates';

    protected $fillable = [
        'leave_request_id',
        'emp_id',
        'date',
        'day_type',
        'half_day_type',
        'deduction_value',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'deduction_value' => 'decimal:1',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }

    /**
     * Cek apakah karyawan sedang cuti pada tanggal tertentu (status pending, approved, atau completed).
     */
    public static function isEmployeeOnLeave($empId, $date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return self::where('emp_id', $empId)
            ->where('date', $dateStr)
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'completed']);
            })
            ->exists();
    }

    /**
     * Mendapatkan daftar tanggal cuti yang sudah diambil/pending oleh karyawan dalam rentang tertentu.
     * Berguna untuk mencegah overlap saat pengajuan baru.
     */
    public static function getExistingLeaveDates($empId, $startDate, $endDate, $excludeRequestId = null): array
    {
        $start = $startDate instanceof Carbon ? $startDate->toDateString() : Carbon::parse($startDate)->toDateString();
        $end = $endDate instanceof Carbon ? $endDate->toDateString() : Carbon::parse($endDate)->toDateString();

        $query = self::where('emp_id', $empId)
            ->whereBetween('date', [$start, $end])
            ->whereHas('leaveRequest', function ($q) use ($excludeRequestId) {
                $q->whereIn('status', ['pending', 'approved', 'completed']);
                if ($excludeRequestId) {
                    $q->where('id', '!=', $excludeRequestId);
                }
            });

        return $query->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Memeriksa apakah ada tanggal cuti yang bentrok/overlap dengan pengajuan yang sudah ada.
     * Mengembalikan array berisi tanggal & rincian bentrok jika ada.
     */
    public static function checkDateConflicts($empId, array $requestedDates, bool $isHalfDay = false, ?string $halfDayType = null, $excludeRequestId = null): array
    {
        if (empty($requestedDates)) {
            return [];
        }

        $minDate = min($requestedDates);
        $maxDate = max($requestedDates);

        $existing = self::with('leaveRequest')
            ->where('emp_id', $empId)
            ->whereBetween('date', [$minDate, $maxDate])
            ->whereHas('leaveRequest', function ($q) use ($excludeRequestId) {
                $q->whereIn('status', ['pending', 'approved', 'completed']);
                if ($excludeRequestId) {
                    $q->where('id', '!=', $excludeRequestId);
                }
            })
            ->get();

        $conflicts = [];

        foreach ($requestedDates as $dateStr) {
            $matching = $existing->filter(function ($item) use ($dateStr) {
                return Carbon::parse($item->date)->toDateString() === $dateStr;
            });

            if ($matching->isNotEmpty()) {
                foreach ($matching as $exist) {
                    $reqNum = $exist->leaveRequest?->request_number ?? ('#' . $exist->leave_request_id);
                    $existType = $exist->day_type === 'half_day'
                        ? ('Cuti Setengah Hari ' . ($exist->half_day_type === 'first_half' ? 'Pagi' : 'Siang'))
                        : 'Cuti Sehari Penuh';

                    // 1. Jika yang diminta Cuti Sehari Penuh -> apapun yang sudah ada pasti bentrok
                    if (!$isHalfDay) {
                        $conflicts[] = [
                            'date' => $dateStr,
                            'formatted' => Carbon::parse($dateStr)->format('d/m/Y'),
                            'message' => "Tanggal " . Carbon::parse($dateStr)->format('d/m/Y') . " sudah ada pengajuan ({$existType} - {$reqNum})",
                        ];
                        break;
                    }

                    // 2. Jika yang diminta Cuti Setengah Hari:
                    // Bentrok jika tanggal itu sudah ada cuti sehari penuh ATAU sudah ada cuti setengah hari pada jam/waktu yang sama
                    if ($exist->day_type === 'full_day' || $exist->half_day_type === $halfDayType) {
                        $conflicts[] = [
                            'date' => $dateStr,
                            'formatted' => Carbon::parse($dateStr)->format('d/m/Y'),
                            'message' => "Tanggal " . Carbon::parse($dateStr)->format('d/m/Y') . " sudah ada pengajuan ({$existType} - {$reqNum})",
                        ];
                        break;
                    }

                    // 3. Jika total kuota hari itu sudah terpakai 1.0 hari
                    $totalDeduction = $matching->sum('deduction_value');
                    if ($totalDeduction + 0.5 > 1.0) {
                        $conflicts[] = [
                            'date' => $dateStr,
                            'formatted' => Carbon::parse($dateStr)->format('d/m/Y'),
                            'message' => "Tanggal " . Carbon::parse($dateStr)->format('d/m/Y') . " sudah mencapai batas kuota harian (sudah terpakai {$totalDeduction} hari).",
                        ];
                        break;
                    }
                }
            }
        }

        return $conflicts;
    }
}


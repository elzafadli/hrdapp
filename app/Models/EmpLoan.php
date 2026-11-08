<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class EmpLoan extends Model
{
    use HasFactory;

    protected $table = 'emp_loans';

    protected $fillable = [
        'emp_id',
        'project_id',
        'amount',
        'paid_amount',
        'remaining_amount',
        'installment_amount',
        'duration',
        'loan_date',
        'start_date',
        'end_date',
        'description',
        'attachment',
        'status',
        'budget_id',
        'budget_reference_number',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
    ];

    /**
     * Hitung total cicilan yang sudah dipotong. Mengutamakan kolom database fisik (paid_amount).
     */
    public function getPaidAmount(?int $beforeYear = null, ?int $beforeMonth = null): float
    {
        // Jika tidak ada filter periode tertentu dan kolom paid_amount sudah ada isinya, pakai langsung tanpa query tambahan
        if ($beforeYear === null && $beforeMonth === null && isset($this->attributes['paid_amount']) && $this->attributes['paid_amount'] !== null) {
            return (float) $this->attributes['paid_amount'];
        }

        $loanComp = PayrollComponent::where('name', 'like', '%Pinjaman%')->first();
        if (!$loanComp) {
            return (float) ($this->attributes['paid_amount'] ?? 0.0);
        }

        $query = PayrollResult::where('emp_id', $this->emp_id)
            ->where('payroll_component_id', $loanComp->id);

        if ($this->start_date) {
            $startY = (int) $this->start_date->year;
            $startM = (int) $this->start_date->month;
            $query->where(function ($q) use ($startY, $startM) {
                $q->where('year', '>', $startY)
                  ->orWhere(function ($sub) use ($startY, $startM) {
                      $sub->where('year', '=', $startY)
                          ->where('month', '>=', $startM);
                  });
            });
        }

        if ($beforeYear !== null && $beforeMonth !== null) {
            $query->where(function ($q) use ($beforeYear, $beforeMonth) {
                $q->where('year', '<', $beforeYear)
                  ->orWhere(function ($sub) use ($beforeYear, $beforeMonth) {
                      $sub->where('year', '=', $beforeYear)
                          ->where('month', '<', $beforeMonth);
                  });
            });
        }

        return (float) $query->sum('amount');
    }

    /**
     * Mengambil saldo pinjaman saat ini langsung dari kolom fisik (remaining_amount) tanpa kalkulasi on-the-fly.
     */
    public function getRemainingBalanceAttribute(): float
    {
        if (isset($this->attributes['remaining_amount']) && $this->attributes['remaining_amount'] !== null) {
            return (float) $this->attributes['remaining_amount'];
        }

        $paid = isset($this->attributes['paid_amount']) && $this->attributes['paid_amount'] !== null
            ? (float) $this->attributes['paid_amount']
            : $this->getPaidAmount();

        return max(0.0, (float) $this->amount - $paid);
    }

    /**
     * Hitung kalkulasi saldo sebelum dan sesudah potongan untuk suatu periode payroll (Year, Month).
     */
    public function calculateBalanceForPeriod(int $year, int $month): array
    {
        $totalLoan = (float) $this->amount;

        // Utamakan kolom database fisik jika tersedia
        if (isset($this->attributes['remaining_amount']) && $this->attributes['remaining_amount'] !== null) {
            $saldoBefore = (float) $this->attributes['remaining_amount'];
            $totalPaidBefore = max(0.0, $totalLoan - $saldoBefore);
        } else {
            $totalPaidBefore = $this->getPaidAmount($year, $month);
            $saldoBefore = max(0.0, $totalLoan - $totalPaidBefore);
        }

        if ($saldoBefore <= 0) {
            return [
                'loan_id'           => $this->id,
                'total_loan'        => $totalLoan,
                'total_paid_before' => $totalPaidBefore,
                'saldo_before'      => 0.0,
                'installment_due'   => 0.0,
                'saldo_after'       => 0.0,
                'is_paid_off'       => true,
            ];
        }

        $normalInstallment = (float) ($this->installment_amount ?: ($this->duration > 0 ? ($totalLoan / $this->duration) : $totalLoan));
        // Potongan tidak boleh melebihi sisa saldo
        $installmentDue = min($normalInstallment, $saldoBefore);
        $saldoAfter = max(0.0, $saldoBefore - $installmentDue);

        return [
            'loan_id'           => $this->id,
            'total_loan'        => $totalLoan,
            'total_paid_before' => $totalPaidBefore,
            'saldo_before'      => $saldoBefore,
            'installment_due'   => $installmentDue,
            'saldo_after'       => $saldoAfter,
            'is_paid_off'       => ($saldoAfter <= 0),
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }
}

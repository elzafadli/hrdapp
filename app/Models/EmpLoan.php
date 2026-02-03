<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpLoan extends Model
{
    use HasFactory;

    protected $table = 'emp_loans';

    protected $fillable = [
        'emp_id',
        'amount',
        'installment_amount',
        'loan_date',
        'start_date',
        'end_date',
        'description',
        'status',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }
}

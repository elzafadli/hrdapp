<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $table = 'budgets';

    protected $fillable = [
        'request_no',
        'user_id',
        'project_id',
        'account_from_id',
        'account_to',
        'document_date',
        'payment_date',
        'description',
        'total_amount',
        'approved_total',
        'is_cash',
        'biaya_admin_bank',
        'status',
    ];

    protected $casts = [
        'document_date' => 'date',
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_total' => 'decimal:2',
        'biaya_admin_bank' => 'decimal:2',
        'is_cash' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(BudgetItem::class, 'budget_id');
    }

    public function empLoan()
    {
        return $this->hasOne(EmpLoan::class, 'budget_id');
    }
}


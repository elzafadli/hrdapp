<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    use HasFactory;

    protected $table = 'budget_items';

    protected $fillable = [
        'budget_id',
        'account_id',
        'qty',
        'unit_price',
        'total_price',
        'remarks',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }
}


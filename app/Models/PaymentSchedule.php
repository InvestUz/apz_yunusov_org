<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'year',
        'quarter',
        'period',
        'planned_amount',
        'actual_amount',
        'debt_amount',
        'due_date',
        'is_overdue',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'debt_amount' => 'decimal:2',
        'due_date' => 'date',
        'is_overdue' => 'boolean',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function checkOverdue()
    {
        if ($this->due_date && $this->due_date->isPast() && $this->debt_amount > 0) {
            $this->is_overdue = true;
            $this->save();
        }
    }
}

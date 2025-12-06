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
        'month',
        'period_date',
        'period_label',
        'planned_amount',
        'actual_amount',
        'debt_amount',
        'is_overdue',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'debt_amount' => 'decimal:2',
        'period_date' => 'date',
        'is_overdue' => 'boolean',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function checkOverdue()
    {
        if ($this->period_date && $this->period_date->isPast() && $this->debt_amount > 0) {
            $this->is_overdue = true;
            $this->save();
        }
    }

    public function getQuarter(): int
    {
        return (int)ceil($this->month / 3);
    }
}

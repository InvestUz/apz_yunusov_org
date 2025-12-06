<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'additional_contract_number',
        'inn',
        'pinfl',
        'company_name',
        'district',
        'status',
        'contract_date',
        'completion_date',
        'payment_terms',
        'payment_period',
        'advance_percent',
        'contract_amount',
        'one_time_payment',
        'monthly_payment',
        'total_payment',
        'remaining_amount',
        'total_fact',
        'total_plan',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'completion_date' => 'date',
        'contract_amount' => 'decimal:2',
        'one_time_payment' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'total_payment' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'total_fact' => 'decimal:2',
        'total_plan' => 'decimal:2',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function schedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    // Alias for paymentSchedules
    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount_debit');
    }

    public function getTotalDebtAttribute()
    {
        return $this->contract_amount - $this->getTotalPaidAttribute();
    }

    public function getIdentifier()
    {
        return $this->inn ?? $this->pinfl;
    }

    public function scopeActive($query)
    {
        return $query->where('status', config('dashboard.statuses.active'));
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotIn('status', [
            config('dashboard.statuses.active'),
            config('dashboard.statuses.completed'),
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', config('dashboard.statuses.completed'));
    }
}
